<?php
/**
 * Bookings API Endpoints
 * 
 * Handles booking-related operations
 */

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

// Initialize booking model
$bookingModel = new Booking();

// Extract endpoint and parameters
$endpoint = $pathParts[count($pathParts) - 2] ?? '';
$param = $pathParts[count($pathParts) - 1] ?? '';

// Check if param is a number (ID)
if (is_numeric($param)) {
    $bookingId = (int)$param;
    $endpoint = $pathParts[count($pathParts) - 3] ?? '';
} else {
    $bookingId = null;
    $endpoint = $pathParts[count($pathParts) - 2] ?? '';
    $action = $param;
}

// Validate JWT for protected endpoints
$isProtected = true;
$excludeEndpoints = ['options'];

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
    case 'bookings':
        if ($bookingId) {
            // Single booking operations
            if ($requestMethod === 'GET') {
                getBookingById($bookingId);
            } elseif ($requestMethod === 'PUT') {
                updateBooking($bookingId);
            } elseif ($requestMethod === 'DELETE') {
                cancelBooking($bookingId);
            } else {
                Utils::jsonError('Method not allowed', 405);
            }
        } else {
            // Multiple bookings operations
            if ($requestMethod === 'GET') {
                getBookings();
            } elseif ($requestMethod === 'POST') {
                createBooking();
            } else {
                Utils::jsonError('Method not allowed', 405);
            }
        }
        break;
        
    case 'status':
        if ($requestMethod === 'PUT' && $bookingId) {
            updateBookingStatus($bookingId);
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'user':
        if ($requestMethod === 'GET' && isset($action) && $action === 'bookings') {
            getUserBookings();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    default:
        Utils::jsonError('Endpoint not found', 404);
        break;
}

/**
 * Get all bookings with optional filters
 */
function getBookings() {
    global $bookingModel;
    
    // Extract filter parameters from query string
    $filters = [
        'status' => $_GET['status'] ?? null,
        'from_date' => $_GET['from_date'] ?? null,
        'to_date' => $_GET['to_date'] ?? null,
        'vehicle_id' => $_GET['vehicle_id'] ?? null
    ];
    
    // Handle pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Get bookings
    $bookings = $bookingModel->getBookings($filters, $page, $limit);
    
    if ($bookings) {
        Utils::jsonResponse(['data' => $bookings]);
    } else {
        Utils::jsonError('Failed to retrieve bookings', 500);
    }
}

/**
 * Get booking by ID
 * 
 * @param int $bookingId Booking ID
 */
function getBookingById($bookingId) {
    global $bookingModel;
    
    $booking = $bookingModel->getBookingById($bookingId);
    
    if ($booking) {
        Utils::jsonResponse(['data' => $booking]);
    } else {
        Utils::jsonError('Booking not found', 404);
    }
}

/**
 * Create a new booking
 */
function createBooking() {
    global $bookingModel;
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        Utils::jsonError('Invalid input data', 400);
        return;
    }
    
    // Validate required fields
    $requiredFields = ['vehicle_id', 'user_id', 'start_date', 'end_date'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            Utils::jsonError("Missing required field: $field", 400);
            return;
        }
    }
    
    // Check vehicle availability
    if (!$bookingModel->isVehicleAvailable($data['vehicle_id'], $data['start_date'], $data['end_date'])) {
        Utils::jsonError('Vehicle is not available for the selected dates', 409);
        return;
    }
    
    // Create booking
    $bookingId = $bookingModel->createBooking($data);
    
    if ($bookingId) {
        $booking = $bookingModel->getBookingById($bookingId);
        Utils::jsonResponse(['message' => 'Booking created successfully', 'data' => $booking], 201);
    } else {
        Utils::jsonError('Failed to create booking', 500);
    }
}

/**
 * Update an existing booking
 * 
 * @param int $bookingId Booking ID
 */
function updateBooking($bookingId) {
    global $bookingModel;
    
    // Get PUT data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        Utils::jsonError('Invalid input data', 400);
        return;
    }
    
    // Get existing booking
    $existingBooking = $bookingModel->getBookingById($bookingId);
    
    if (!$existingBooking) {
        Utils::jsonError('Booking not found', 404);
        return;
    }
    
    // Check if date changed and if vehicle is available
    if (isset($data['start_date']) && isset($data['end_date']) && 
        ($data['start_date'] !== $existingBooking['start_date'] || $data['end_date'] !== $existingBooking['end_date'])) {
        
        if (!$bookingModel->isVehicleAvailable(
            $existingBooking['vehicle_id'], 
            $data['start_date'], 
            $data['end_date'], 
            $bookingId
        )) {
            Utils::jsonError('Vehicle is not available for the selected dates', 409);
            return;
        }
    }
    
    // Update booking
    $result = $bookingModel->updateBooking($bookingId, $data);
    
    if ($result) {
        $updatedBooking = $bookingModel->getBookingById($bookingId);
        Utils::jsonResponse(['message' => 'Booking updated successfully', 'data' => $updatedBooking]);
    } else {
        Utils::jsonError('Failed to update booking', 500);
    }
}

/**
 * Update booking status
 * 
 * @param int $bookingId Booking ID
 */
function updateBookingStatus($bookingId) {
    global $bookingModel;
    
    // Get PUT data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['status'])) {
        Utils::jsonError('Status is required', 400);
        return;
    }
    
    // Valid status values
    $validStatuses = ['pending', 'confirmed', 'ongoing', 'completed', 'cancelled'];
    
    if (!in_array($data['status'], $validStatuses)) {
        Utils::jsonError('Invalid status value', 400);
        return;
    }
    
    // Update status
    $result = $bookingModel->updateBookingStatus($bookingId, $data['status']);
    
    if ($result) {
        $updatedBooking = $bookingModel->getBookingById($bookingId);
        Utils::jsonResponse(['message' => 'Booking status updated successfully', 'data' => $updatedBooking]);
    } else {
        Utils::jsonError('Failed to update booking status', 500);
    }
}

/**
 * Cancel a booking
 * 
 * @param int $bookingId Booking ID
 */
function cancelBooking($bookingId) {
    global $bookingModel;
    
    // Get existing booking
    $existingBooking = $bookingModel->getBookingById($bookingId);
    
    if (!$existingBooking) {
        Utils::jsonError('Booking not found', 404);
        return;
    }
    
    // Check if booking can be cancelled (based on business rules)
    if (!$bookingModel->canCancelBooking($bookingId)) {
        Utils::jsonError('Booking cannot be cancelled due to cancellation policy', 400);
        return;
    }
      // Cancel booking
    $result = $bookingModel->cancelBooking($bookingId, 'Cancelled by user');
    
    if ($result) {
        Utils::jsonResponse(['message' => 'Booking cancelled successfully']);
    } else {
        Utils::jsonError('Failed to cancel booking', 500);
    }
}

/**
 * Get bookings for the current authenticated user
 */
function getUserBookings() {
    global $bookingModel, $auth;
    
    // Get user ID from token
    $userId = $auth->getCurrentUserId();
    
    if (!$userId) {
        Utils::jsonError('User not authenticated', 401);
        return;
    }
    
    // Handle pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      // Get user's bookings
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $bookings = $bookingModel->getUserBookings($userId, $status, $page, $limit);
    
    if ($bookings) {
        Utils::jsonResponse(['data' => $bookings]);
    } else {
        Utils::jsonError('Failed to retrieve bookings', 500);
    }
}
