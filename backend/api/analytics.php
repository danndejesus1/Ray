<?php
/**
 * Analytics API Endpoints
 * 
 * Handles analytics-related operations
 */

require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/Vehicle.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Utils.php';
require_once __DIR__ . '/../utils/Auth.php';

// Set headers for CORS and JSON content type
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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

// Initialize models
$bookingModel = new Booking();
$vehicleModel = new Vehicle();
$paymentModel = new Payment();
$userModel = new User();

// Extract endpoint
$endpoint = $pathParts[count($pathParts) - 1] ?? '';

// Validate JWT for protected endpoints
$auth = new Auth();
$userId = $auth->validateToken();

if (!$userId) {
    Utils::jsonError('Unauthorized access', 401);
    exit;
}

// Check for admin or booking staff role for analytics access
$user = $userModel->getById($userId);
$userRole = $user['role_name'] ?? '';

$allowedRoles = [CONFIG['USER_ROLES']['ADMIN'], CONFIG['USER_ROLES']['BOOKING_STAFF']];
if (!in_array($userRole, $allowedRoles)) {
    Utils::jsonError('Forbidden: You do not have access to analytics', 403);
    exit;
}

// Route requests
switch ($endpoint) {
    case 'dashboard':
        if ($requestMethod === 'GET') {
            getDashboardStats();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'bookings':
        if ($requestMethod === 'GET') {
            getBookingStats();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'vehicles':
        if ($requestMethod === 'GET') {
            getVehicleStats();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'revenue':
        if ($requestMethod === 'GET') {
            getRevenueStats();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'users':
        if ($requestMethod === 'GET') {
            getUserStats();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    default:
        Utils::jsonError('Endpoint not found', 404);
        break;
}

/**
 * Get dashboard statistics for the admin panel
 */
function getDashboardStats() {
    global $bookingModel, $vehicleModel, $paymentModel, $userModel;
    
    // Get date ranges
    $today = date('Y-m-d');
    $startOfMonth = date('Y-m-01');
    $startOfLastMonth = date('Y-m-01', strtotime('-1 month'));
    $endOfLastMonth = date('Y-m-t', strtotime('-1 month'));
    
    // Get booking stats
    $activeBookings = $bookingModel->countBookingsByStatus(['confirmed', 'ongoing']);
    $pendingBookings = $bookingModel->countBookingsByStatus(['pending']);
    $completedBookings = $bookingModel->countBookingsByStatus(['completed']);
    $todayBookings = $bookingModel->countBookingsByDate($today, $today);
    
    // Get vehicle stats
    $totalVehicles = $vehicleModel->countAll();
    $availableVehicles = $vehicleModel->countByAvailability(true);
    $maintenanceVehicles = $vehicleModel->countByStatus('maintenance');
    
    // Get revenue stats
    $todayRevenue = $paymentModel->getTotalRevenue($today, $today);
    $monthlyRevenue = $paymentModel->getTotalRevenue($startOfMonth, $today);
    $lastMonthRevenue = $paymentModel->getTotalRevenue($startOfLastMonth, $endOfLastMonth);
    
    // Get user stats
    $totalUsers = $userModel->countAll();
    $newUsers = $userModel->countNewUsers($startOfMonth, $today);
    
    // Compile dashboard stats
    $dashboardStats = [
        'bookings' => [
            'active' => $activeBookings,
            'pending' => $pendingBookings,
            'completed' => $completedBookings,
            'today' => $todayBookings
        ],
        'vehicles' => [
            'total' => $totalVehicles,
            'available' => $availableVehicles,
            'maintenance' => $maintenanceVehicles,
            'utilization_rate' => $totalVehicles > 0 ? (($totalVehicles - $availableVehicles) / $totalVehicles) * 100 : 0
        ],
        'revenue' => [
            'today' => $todayRevenue,
            'month_to_date' => $monthlyRevenue,
            'last_month' => $lastMonthRevenue,
            'growth' => $lastMonthRevenue > 0 ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0
        ],
        'users' => [
            'total' => $totalUsers,
            'new_this_month' => $newUsers
        ]
    ];
    
    Utils::jsonResponse(['data' => $dashboardStats]);
}

/**
 * Get detailed booking statistics
 */
function getBookingStats() {
    global $bookingModel;
    
    // Get filter parameters
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Get booking trends by date
    $bookingTrends = $bookingModel->getBookingTrends($startDate, $endDate);
    
    // Get booking status distribution
    $statusDistribution = $bookingModel->getBookingStatusDistribution();
    
    // Get top booking locations
    $topLocations = $bookingModel->getTopBookingLocations();
    
    // Get average rental duration
    $avgDuration = $bookingModel->getAverageRentalDuration($startDate, $endDate);
    
    // Get booking completion rate
    $completionRate = $bookingModel->getBookingCompletionRate($startDate, $endDate);
    
    // Get cancellation rate
    $cancellationRate = $bookingModel->getBookingCancellationRate($startDate, $endDate);
    
    // Compile booking stats
    $bookingStats = [
        'trends' => $bookingTrends,
        'status_distribution' => $statusDistribution,
        'top_locations' => $topLocations,
        'avg_duration' => $avgDuration,
        'completion_rate' => $completionRate,
        'cancellation_rate' => $cancellationRate
    ];
    
    Utils::jsonResponse(['data' => $bookingStats]);
}

/**
 * Get detailed vehicle statistics
 */
function getVehicleStats() {
    global $vehicleModel, $bookingModel;
    
    // Get filter parameters
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Get vehicle type distribution
    $typeDistribution = $vehicleModel->getVehicleTypeDistribution();
    
    // Get vehicle availability rate
    $availabilityRate = $vehicleModel->getAvailabilityRate();
    
    // Get most booked vehicles
    $mostBooked = $vehicleModel->getMostBookedVehicles($startDate, $endDate);
    
    // Get vehicle utilization by type
    $utilizationByType = $vehicleModel->getUtilizationByType($startDate, $endDate);
    
    // Get average revenue per vehicle
    $avgRevenuePerVehicle = $vehicleModel->getAverageRevenuePerVehicle($startDate, $endDate);
    
    // Get maintenance frequency
    $maintenanceFrequency = $vehicleModel->getMaintenanceFrequency($startDate, $endDate);
    
    // Compile vehicle stats
    $vehicleStats = [
        'type_distribution' => $typeDistribution,
        'availability_rate' => $availabilityRate,
        'most_booked' => $mostBooked,
        'utilization_by_type' => $utilizationByType,
        'avg_revenue' => $avgRevenuePerVehicle,
        'maintenance_frequency' => $maintenanceFrequency
    ];
    
    Utils::jsonResponse(['data' => $vehicleStats]);
}

/**
 * Get detailed revenue statistics
 */
function getRevenueStats() {
    global $paymentModel;
    
    // Get filter parameters
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Get revenue trends by date
    $revenueTrends = $paymentModel->getRevenueTrends($startDate, $endDate);
    
    // Get revenue by payment method
    $revenueByMethod = $paymentModel->getRevenueByPaymentMethod($startDate, $endDate);
    
    // Get revenue by vehicle type
    $revenueByVehicleType = $paymentModel->getRevenueByVehicleType($startDate, $endDate);
    
    // Get average transaction value
    $avgTransactionValue = $paymentModel->getAverageTransactionValue($startDate, $endDate);
    
    // Get peak revenue days
    $peakRevenueDays = $paymentModel->getPeakRevenueDays($startDate, $endDate);
    
    // Compile revenue stats
    $revenueStats = [
        'trends' => $revenueTrends,
        'by_payment_method' => $revenueByMethod,
        'by_vehicle_type' => $revenueByVehicleType,
        'avg_transaction' => $avgTransactionValue,
        'peak_days' => $peakRevenueDays
    ];
    
    Utils::jsonResponse(['data' => $revenueStats]);
}

/**
 * Get detailed user statistics
 */
function getUserStats() {
    global $userModel, $bookingModel;
    
    // Get filter parameters
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Get user registration trends
    $registrationTrends = $userModel->getRegistrationTrends($startDate, $endDate);
    
    // Get user role distribution
    $roleDistribution = $userModel->getUserRoleDistribution();
    
    // Get active vs inactive users
    $statusDistribution = $userModel->getUserStatusDistribution();
    
    // Get top customers by bookings
    $topCustomersByBookings = $userModel->getTopCustomersByBookings($startDate, $endDate);
    
    // Get top customers by revenue
    $topCustomersByRevenue = $userModel->getTopCustomersByRevenue($startDate, $endDate);
    
    // Get user retention rate
    $retentionRate = $userModel->getUserRetentionRate($startDate, $endDate);
    
    // Compile user stats
    $userStats = [
        'registration_trends' => $registrationTrends,
        'role_distribution' => $roleDistribution,
        'status_distribution' => $statusDistribution,
        'top_by_bookings' => $topCustomersByBookings,
        'top_by_revenue' => $topCustomersByRevenue,
        'retention_rate' => $retentionRate
    ];
    
    Utils::jsonResponse(['data' => $userStats]);
}
