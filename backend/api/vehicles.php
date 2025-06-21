<?php
/**
 * Vehicles API Endpoints
 * 
 * Handles vehicle-related operations
 */

require_once __DIR__ . '/../../models/Vehicle.php';
require_once __DIR__ . '/../../utils/Utils.php';
require_once __DIR__ . '/../../utils/Auth.php';

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

// Initialize vehicle model
$vehicleModel = new Vehicle();

// Extract endpoint and parameters
$endpoint = $pathParts[count($pathParts) - 2] ?? '';
$param = $pathParts[count($pathParts) - 1] ?? '';

// Check if param is a number (ID)
if (is_numeric($param)) {
    $vehicleId = (int)$param;
    $endpoint = $pathParts[count($pathParts) - 3] ?? '';
} else {
    $vehicleId = null;
    $endpoint = $pathParts[count($pathParts) - 2] ?? '';
    $action = $param;
}

// Route requests
switch ($endpoint) {
    case 'vehicles':
        if ($vehicleId) {
            // Single vehicle operations
            if ($requestMethod === 'GET') {
                getVehicleById($vehicleId);
            } elseif ($requestMethod === 'PUT') {
                updateVehicle($vehicleId);
            } elseif ($requestMethod === 'DELETE') {
                deleteVehicle($vehicleId);
            } else {
                Utils::jsonError('Method not allowed', 405);
            }
        } else {
            // Multiple vehicles operations
            if ($requestMethod === 'GET') {
                getVehicles();
            } elseif ($requestMethod === 'POST') {
                createVehicle();
            } else {
                Utils::jsonError('Method not allowed', 405);
            }
        }
        break;
        
    case 'filters':
        if ($requestMethod === 'GET') {
            getVehicleFilters();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'availability':
        if ($requestMethod === 'POST') {
            checkVehicleAvailability();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    default:
        Utils::jsonError('Endpoint not found', 404);
        break;
}

/**
 * Get all vehicles with optional filters
 */
function getVehicles() {
    global $vehicleModel;
    
    // Extract filter parameters from query string
    $filters = [
        'vehicle_type' => $_GET['vehicle_type'] ?? null,
        'make' => $_GET['make'] ?? null,
        'fuel_type' => $_GET['fuel_type'] ?? null,
        'capacity' => $_GET['capacity'] ?? null,
        'max_daily_rate' => $_GET['max_daily_rate'] ?? null
    ];
    
    // Clean up null values
    $filters = array_filter($filters);
    
    // Get vehicles
    $vehicles = $vehicleModel->getAvailableVehicles($filters);
    
    // Parse features JSON
    foreach ($vehicles as &$vehicle) {
        if (isset($vehicle['features']) && !empty($vehicle['features'])) {
            $vehicle['features'] = json_decode($vehicle['features'], true);
        } else {
            $vehicle['features'] = [];
        }
    }
    
    Utils::jsonResponse([
        'success' => true,
        'data' => $vehicles
    ]);
}

/**
 * Get vehicle by ID
 * 
 * @param int $vehicleId Vehicle ID
 */
function getVehicleById($vehicleId) {
    global $vehicleModel;
    
    // Get vehicle with images
    $vehicle = $vehicleModel->getVehicleWithImages($vehicleId);
    
    if (!$vehicle) {
        Utils::jsonError('Vehicle not found', 404);
        return;
    }
    
    // Parse features JSON
    if (isset($vehicle['features']) && !empty($vehicle['features'])) {
        $vehicle['features'] = json_decode($vehicle['features'], true);
    } else {
        $vehicle['features'] = [];
    }
    
    Utils::jsonResponse([
        'success' => true,
        'data' => $vehicle
    ]);
}

/**
 * Create a new vehicle
 */
function createVehicle() {
    global $vehicleModel;
    
    // Authenticate admin or booking staff
    $user = Auth::validateAuth([CONFIG['USER_ROLES']['ADMIN'], CONFIG['USER_ROLES']['BOOKING_STAFF']]);
    
    if (!$user) {
        Utils::jsonError('Unauthorized', 401);
        return;
    }
    
    $data = Utils::getPostData();
    
    // Validate required fields
    $requiredFields = [
        'make', 'model', 'year', 'color', 'license_plate',
        'vehicle_type', 'fuel_type', 'transmission', 'mileage',
        'capacity', 'daily_rate'
    ];
    
    $validation = Utils::validateRequired($data, $requiredFields);
    
    if (!$validation['valid']) {
        Utils::jsonError($validation['message']);
        return;
    }
    
    // Check if license plate already exists
    if ($vehicleModel->exists(['license_plate' => $data['license_plate']])) {
        Utils::jsonError('License plate already exists');
        return;
    }
    
    // Convert features array to JSON if provided
    if (isset($data['features']) && is_array($data['features'])) {
        $data['features'] = json_encode($data['features']);
    }
    
    // Create vehicle
    $vehicleId = $vehicleModel->create($data);
    
    if (!$vehicleId) {
        Utils::jsonError('Failed to create vehicle');
        return;
    }
    
    // Handle images if provided
    if (isset($data['images']) && is_array($data['images'])) {
        foreach ($data['images'] as $index => $imageUrl) {
            $isPrimary = $index === 0; // First image is primary
            $vehicleModel->addImage($vehicleId, $imageUrl, $isPrimary);
        }
    }
    
    // Get created vehicle
    $vehicle = $vehicleModel->getVehicleWithImages($vehicleId);
    
    // Log vehicle creation
    Utils::log("Vehicle created: {$vehicle['make']} {$vehicle['model']} ({$vehicle['license_plate']})", 'info');
    
    Utils::jsonResponse([
        'success' => true,
        'data' => $vehicle
    ], 201);
}

/**
 * Update a vehicle
 * 
 * @param int $vehicleId Vehicle ID
 */
function updateVehicle($vehicleId) {
    global $vehicleModel;
    
    // Authenticate admin or booking staff
    $user = Auth::validateAuth([CONFIG['USER_ROLES']['ADMIN'], CONFIG['USER_ROLES']['BOOKING_STAFF']]);
    
    if (!$user) {
        Utils::jsonError('Unauthorized', 401);
        return;
    }
    
    // Check if vehicle exists
    $vehicle = $vehicleModel->getById($vehicleId);
    
    if (!$vehicle) {
        Utils::jsonError('Vehicle not found', 404);
        return;
    }
    
    $data = Utils::getPostData();
    
    // Check if license plate is changed and already exists
    if (isset($data['license_plate']) && $data['license_plate'] !== $vehicle['license_plate']) {
        if ($vehicleModel->exists(['license_plate' => $data['license_plate']])) {
            Utils::jsonError('License plate already exists');
            return;
        }
    }
    
    // Convert features array to JSON if provided
    if (isset($data['features']) && is_array($data['features'])) {
        $data['features'] = json_encode($data['features']);
    }
    
    // Update vehicle
    $result = $vehicleModel->update($vehicleId, $data);
    
    if (!$result) {
        Utils::jsonError('Failed to update vehicle');
        return;
    }
      // Handle images if provided
    if (isset($data['images']) && is_array($data['images'])) {
        // Delete existing images
        $query = "DELETE FROM vehicle_images WHERE vehicle_id = :vehicle_id";
        $db = new Database();
        $db->executeQuery($query, [':vehicle_id' => $vehicleId]);
        
        // Add new images
        foreach ($data['images'] as $index => $imageUrl) {
            $isPrimary = $index === 0; // First image is primary
            $vehicleModel->addImage($vehicleId, $imageUrl, $isPrimary);
        }
    }
    
    // Get updated vehicle
    $updatedVehicle = $vehicleModel->getVehicleWithImages($vehicleId);
    
    // Log vehicle update
    Utils::log("Vehicle updated: {$updatedVehicle['make']} {$updatedVehicle['model']} ({$updatedVehicle['license_plate']})", 'info');
    
    Utils::jsonResponse([
        'success' => true,
        'data' => $updatedVehicle
    ]);
}

/**
 * Delete a vehicle
 * 
 * @param int $vehicleId Vehicle ID
 */
function deleteVehicle($vehicleId) {
    global $vehicleModel;
    
    // Authenticate admin
    $user = Auth::validateAuth([CONFIG['USER_ROLES']['ADMIN']]);
    
    if (!$user) {
        Utils::jsonError('Unauthorized', 401);
        return;
    }
    
    // Check if vehicle exists
    $vehicle = $vehicleModel->getById($vehicleId);
    
    if (!$vehicle) {
        Utils::jsonError('Vehicle not found', 404);
        return;
    }
    
    // Check if vehicle has active bookings
    $query = "
        SELECT COUNT(*) as count
        FROM bookings
        WHERE vehicle_id = :vehicle_id
        AND status IN ('pending', 'confirmed', 'active')
    ";
    
    $db = new Database();
    $result = $db->executeQuery($query, [':vehicle_id' => $vehicleId]);
    
    if ($result[0]['count'] > 0) {
        Utils::jsonError('Cannot delete vehicle with active bookings');
        return;
    }
    
    // Delete vehicle images
    $query = "DELETE FROM vehicle_images WHERE vehicle_id = :vehicle_id";
    $db->executeQuery($query, [':vehicle_id' => $vehicleId]);
    
    // Delete vehicle
    $result = $vehicleModel->delete($vehicleId);
    
    if (!$result) {
        Utils::jsonError('Failed to delete vehicle');
        return;
    }
    
    // Log vehicle deletion
    Utils::log("Vehicle deleted: {$vehicle['make']} {$vehicle['model']} ({$vehicle['license_plate']})", 'info');
    
    Utils::jsonResponse([
        'success' => true,
        'message' => 'Vehicle deleted successfully'
    ]);
}

/**
 * Get vehicle filters
 */
function getVehicleFilters() {
    global $vehicleModel;
    
    // Get vehicle makes and models
    $makesAndModels = $vehicleModel->getVehicleMakesAndModels();
    
    // Get vehicle types
    $types = $vehicleModel->getVehicleTypes();
    
    // Get fuel types
    $fuelTypes = $vehicleModel->getFuelTypes();
    
    // Organize makes and models
    $makes = [];
    foreach ($makesAndModels as $item) {
        if (!isset($makes[$item['make']])) {
            $makes[$item['make']] = [];
        }
        
        $makes[$item['make']][] = $item['model'];
    }
    
    // Extract unique vehicle types
    $vehicleTypes = array_map(function($item) {
        return $item['vehicle_type'];
    }, $types);
    
    // Extract unique fuel types
    $fuelTypesList = array_map(function($item) {
        return $item['fuel_type'];
    }, $fuelTypes);
    
    Utils::jsonResponse([
        'success' => true,
        'data' => [
            'makes' => $makes,
            'vehicle_types' => $vehicleTypes,
            'fuel_types' => $fuelTypesList,
            'capacities' => CONFIG['VEHICLE']['CAPACITIES']
        ]
    ]);
}

/**
 * Check vehicle availability
 */
function checkVehicleAvailability() {
    global $vehicleModel;
    
    $data = Utils::getPostData();
    
    // Validate required fields
    $validation = Utils::validateRequired($data, ['vehicle_id', 'start_date', 'end_date']);
    
    if (!$validation['valid']) {
        Utils::jsonError($validation['message']);
        return;
    }
    
    // Check if vehicle exists
    $vehicle = $vehicleModel->getById($data['vehicle_id']);
    
    if (!$vehicle) {
        Utils::jsonError('Vehicle not found', 404);
        return;
    }
    
    // Check if vehicle is available for booking
    $isAvailable = $vehicleModel->isAvailableForBooking(
        $data['vehicle_id'],
        $data['start_date'],
        $data['end_date']
    );
    
    Utils::jsonResponse([
        'success' => true,
        'available' => $isAvailable
    ]);
}
