<?php
/**
 * Payments API Endpoints
 * 
 * Handles payment-related operations
 */

require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../utils/Utils.php';
require_once __DIR__ . '/../utils/Auth.php';

// Set headers for CORS and JSON content type
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and path
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($requestPath, '/'));

// Initialize payment model
$paymentModel = new Payment();
$bookingModel = new Booking();

// Extract endpoint and parameters
$endpoint = $pathParts[count($pathParts) - 2] ?? '';
$param = $pathParts[count($pathParts) - 1] ?? '';

// Check if param is a number (ID)
if (is_numeric($param)) {
    $paymentId = (int)$param;
    $endpoint = $pathParts[count($pathParts) - 3] ?? '';
} else {
    $paymentId = null;
    $endpoint = $pathParts[count($pathParts) - 2] ?? '';
    $action = $param;
}

// Validate JWT for protected endpoints
$isProtected = true;
$excludeEndpoints = ['webhook', 'options'];

if ($isProtected && !in_array($endpoint, $excludeEndpoints)) {
    $auth = new Auth();
    $userId = $auth->validateToken();
    
    if (!$userId) {
        Utils::jsonError('Unauthorized access', 401);
        exit;
    }
}

// Route requests
switch ($endpoint) {
    case 'payments':
        if ($paymentId) {
            // Single payment operations
            if ($requestMethod === 'GET') {
                getPaymentById($paymentId);
            } elseif ($requestMethod === 'PUT') {
                updatePayment($paymentId);
            } else {
                Utils::jsonError('Method not allowed', 405);
            }
        } else {
            // Multiple payments operations
            if ($requestMethod === 'GET') {
                getPayments();
            } elseif ($requestMethod === 'POST') {
                createPayment();
            } else {
                Utils::jsonError('Method not allowed', 405);
            }
        }
        break;
        
    case 'booking':
        if ($requestMethod === 'GET' && is_numeric($param)) {
            getBookingPayments($param);
        } elseif ($requestMethod === 'POST' && is_numeric($param)) {
            processBookingPayment($param);
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'webhook':
        if ($requestMethod === 'POST') {
            handlePaymentWebhook();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'methods':
        if ($requestMethod === 'GET') {
            getPaymentMethods();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    default:
        Utils::jsonError('Endpoint not found', 404);
        break;
}

/**
 * Get all payments with optional filters
 */
function getPayments() {
    global $paymentModel;
    
    // Extract filter parameters from query string
    $filters = [
        'status' => $_GET['status'] ?? null,
        'from_date' => $_GET['from_date'] ?? null,
        'to_date' => $_GET['to_date'] ?? null,
        'booking_id' => $_GET['booking_id'] ?? null,
        'user_id' => $_GET['user_id'] ?? null
    ];
    
    // Handle pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Get payments
    $payments = $paymentModel->getPayments($filters, $page, $limit);
    
    if ($payments) {
        Utils::jsonResponse(['data' => $payments]);
    } else {
        Utils::jsonError('Failed to retrieve payments', 500);
    }
}

/**
 * Get payment by ID
 * 
 * @param int $paymentId Payment ID
 */
function getPaymentById($paymentId) {
    global $paymentModel;
    
    $payment = $paymentModel->getPaymentById($paymentId);
    
    if ($payment) {
        Utils::jsonResponse(['data' => $payment]);
    } else {
        Utils::jsonError('Payment not found', 404);
    }
}

/**
 * Create a new payment
 */
function createPayment() {
    global $paymentModel, $bookingModel;
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        Utils::jsonError('Invalid input data', 400);
        return;
    }
    
    // Validate required fields
    $requiredFields = ['booking_id', 'amount', 'payment_method'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            Utils::jsonError("Missing required field: $field", 400);
            return;
        }
    }
    
    // Verify booking exists
    $booking = $bookingModel->getBookingById($data['booking_id']);
    if (!$booking) {
        Utils::jsonError('Booking not found', 404);
        return;
    }
    
    // Set payment initial status
    $data['status'] = 'pending';
    
    // Generate unique transaction reference
    $data['transaction_reference'] = 'PMT-' . strtoupper(substr(uniqid(), -6)) . '-' . date('ymd');
    
    // Create payment record
    $paymentId = $paymentModel->createPayment($data);
    
    if ($paymentId) {
        // Get created payment
        $payment = $paymentModel->getPaymentById($paymentId);
        
        // Process the payment based on the selected method
        $paymentResult = processPayment($payment);
        
        if ($paymentResult['success']) {
            // Update payment status
            $paymentModel->updatePaymentStatus($paymentId, 'completed');
            $payment['status'] = 'completed';
            
            // Update booking payment status if needed
            if ($booking['payment_status'] !== 'paid') {
                $bookingModel->updateBooking($booking['booking_id'], ['payment_status' => 'paid']);
            }
            
            Utils::jsonResponse([
                'message' => 'Payment processed successfully',
                'data' => $payment,
                'payment_details' => $paymentResult['details']
            ], 201);
        } else {
            // Update payment status to failed
            $paymentModel->updatePaymentStatus($paymentId, 'failed', $paymentResult['error']);
            
            Utils::jsonError('Payment processing failed: ' . $paymentResult['error'], 400);
        }
    } else {
        Utils::jsonError('Failed to create payment record', 500);
    }
}

/**
 * Update an existing payment
 * 
 * @param int $paymentId Payment ID
 */
function updatePayment($paymentId) {
    global $paymentModel;
    
    // Get PUT data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        Utils::jsonError('Invalid input data', 400);
        return;
    }
    
    // Get existing payment
    $existingPayment = $paymentModel->getPaymentById($paymentId);
    
    if (!$existingPayment) {
        Utils::jsonError('Payment not found', 404);
        return;
    }
    
    // Prevent updating certain fields
    $protectedFields = ['booking_id', 'amount', 'transaction_reference'];
    foreach ($protectedFields as $field) {
        if (isset($data[$field])) {
            unset($data[$field]);
        }
    }
    
    // Update payment
    $result = $paymentModel->updatePayment($paymentId, $data);
    
    if ($result) {
        $updatedPayment = $paymentModel->getPaymentById($paymentId);
        Utils::jsonResponse(['message' => 'Payment updated successfully', 'data' => $updatedPayment]);
    } else {
        Utils::jsonError('Failed to update payment', 500);
    }
}

/**
 * Get payments for a specific booking
 * 
 * @param int $bookingId Booking ID
 */
function getBookingPayments($bookingId) {
    global $paymentModel, $bookingModel;
    
    // Verify booking exists
    $booking = $bookingModel->getBookingById($bookingId);
    if (!$booking) {
        Utils::jsonError('Booking not found', 404);
        return;
    }
    
    // Get payments for the booking
    $payments = $paymentModel->getBookingPayments($bookingId);
    
    Utils::jsonResponse(['data' => $payments]);
}

/**
 * Process payment for a booking
 * 
 * @param int $bookingId Booking ID
 */
function processBookingPayment($bookingId) {
    global $paymentModel, $bookingModel;
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['payment_method'])) {
        Utils::jsonError('Invalid input data or missing payment method', 400);
        return;
    }
    
    // Verify booking exists
    $booking = $bookingModel->getBookingById($bookingId);
    if (!$booking) {
        Utils::jsonError('Booking not found', 404);
        return;
    }
    
    // Check if booking is already paid
    if ($booking['payment_status'] === 'paid') {
        Utils::jsonError('Booking is already paid', 400);
        return;
    }
    
    // Create payment data
    $paymentData = [
        'booking_id' => $bookingId,
        'amount' => $booking['total_amount'],
        'payment_method' => $data['payment_method'],
        'user_id' => $booking['user_id'],
        'status' => 'pending',
        'transaction_reference' => 'PMT-' . strtoupper(substr(uniqid(), -6)) . '-' . date('ymd')
    ];
    
    // Create payment record
    $paymentId = $paymentModel->createPayment($paymentData);
    
    if ($paymentId) {
        // Get created payment
        $payment = $paymentModel->getPaymentById($paymentId);
        
        // Process the payment based on the selected method
        $paymentResult = processPayment($payment);
        
        if ($paymentResult['success']) {
            // Update payment status
            $paymentModel->updatePaymentStatus($paymentId, 'completed');
            $payment['status'] = 'completed';
            
            // Update booking payment status
            $bookingModel->updateBooking($booking['booking_id'], ['payment_status' => 'paid']);
            
            Utils::jsonResponse([
                'message' => 'Payment processed successfully',
                'data' => $payment,
                'payment_details' => $paymentResult['details']
            ], 201);
        } else {
            // Update payment status to failed
            $paymentModel->updatePaymentStatus($paymentId, 'failed', $paymentResult['error']);
            
            Utils::jsonError('Payment processing failed: ' . $paymentResult['error'], 400);
        }
    } else {
        Utils::jsonError('Failed to create payment record', 500);
    }
}

/**
 * Handle payment provider webhook
 */
function handlePaymentWebhook() {
    global $paymentModel, $bookingModel;
    
    // Get raw POST data
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_PAYMENT_SIGNATURE'] ?? '';
    
    // Verify webhook signature (implementation depends on payment provider)
    if (!verifyWebhookSignature($payload, $signature)) {
        Utils::jsonError('Invalid webhook signature', 400);
        return;
    }
    
    // Parse payload
    $data = json_decode($payload, true);
    
    if (!$data) {
        Utils::jsonError('Invalid webhook payload', 400);
        return;
    }
    
    // Process webhook based on event type
    $eventType = $data['event'] ?? '';
    $transactionReference = $data['transaction_reference'] ?? '';
    
    if (empty($eventType) || empty($transactionReference)) {
        Utils::jsonError('Missing required webhook data', 400);
        return;
    }
    
    // Find payment by transaction reference
    $payment = $paymentModel->findOneBy('transaction_reference', $transactionReference);
    
    if (!$payment) {
        Utils::jsonError('Payment not found', 404);
        return;
    }
    
    // Process different event types
    switch ($eventType) {
        case 'payment.success':
            // Update payment status
            $paymentModel->updatePaymentStatus($payment['payment_id'], 'completed');
            
            // Update booking payment status
            $bookingModel->updateBooking($payment['booking_id'], ['payment_status' => 'paid']);
            break;
            
        case 'payment.failed':
            // Update payment status
            $paymentModel->updatePaymentStatus($payment['payment_id'], 'failed', $data['error_message'] ?? 'Payment failed');
            break;
            
        case 'payment.refunded':
            // Update payment status
            $paymentModel->updatePaymentStatus($payment['payment_id'], 'refunded');
            break;
            
        default:
            // Unhandled event type
            Utils::jsonError('Unhandled event type', 400);
            return;
    }
    
    // Return success response
    Utils::jsonResponse(['status' => 'success']);
}

/**
 * Get available payment methods
 */
function getPaymentMethods() {
    global $paymentModel;
    
    // Get payment methods from config
    $methods = CONFIG['PAYMENT']['METHODS'];
    
    Utils::jsonResponse(['data' => $methods]);
}

/**
 * Process payment through payment provider
 * 
 * @param array $payment Payment data
 * @return array Result with success flag and details
 */
function processPayment($payment) {
    // This is a placeholder function that would integrate with actual payment processors
    // For demo purposes, we'll simulate successful payments
    
    // Get payment method
    $method = $payment['payment_method'];
    
    // Simulate payment processing
    $success = true; // In a real app, this would depend on the payment provider's response
    $error = '';
    $details = [];
    
    // Simulate different payment methods
    switch ($method) {
        case 'CREDIT_CARD':
            $details = [
                'processor' => 'Stripe',
                'transaction_id' => 'tx_' . uniqid(),
                'card_last4' => '4242'
            ];
            break;
            
        case 'GCASH':
            $details = [
                'processor' => 'GCash',
                'transaction_id' => 'gc_' . uniqid(),
                'reference_number' => 'GC' . rand(100000, 999999)
            ];
            break;
            
        case 'PAYMAYA':
            $details = [
                'processor' => 'PayMaya',
                'transaction_id' => 'pm_' . uniqid(),
                'reference_number' => 'PM' . rand(100000, 999999)
            ];
            break;
            
        case 'QR_CODE':
            $details = [
                'processor' => 'QR Pay',
                'transaction_id' => 'qr_' . uniqid(),
                'qr_reference' => 'QR' . rand(100000, 999999)
            ];
            break;
            
        default:
            $success = false;
            $error = 'Unsupported payment method';
            break;
    }
    
    return [
        'success' => $success,
        'error' => $error,
        'details' => $details
    ];
}

/**
 * Verify webhook signature from payment provider
 * 
 * @param string $payload Webhook payload
 * @param string $signature Webhook signature
 * @return bool Verification result
 */
function verifyWebhookSignature($payload, $signature) {
    // This is a placeholder function that would verify webhook signatures from payment providers
    // For demo purposes, we'll return true
    return true;
}
