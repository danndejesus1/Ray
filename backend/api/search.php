<?php
/**
 * Vehicle Search API Endpoint
 * 
 * Handles advanced search and filtering of vehicles
 */

require_once __DIR__ . '/../models/Vehicle.php';
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

// Get request method
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Initialize vehicle model
$vehicleModel = new Vehicle();

// Route requests
if ($requestMethod === 'GET') {
    searchVehicles();
} else {
    Utils::jsonError('Method not allowed', 405);
}

/**
 * Search vehicles with advanced filtering
 */
function searchVehicles() {
    global $vehicleModel;
    
    // Extract filter parameters from query string
    $filters = [
        'make' => $_GET['make'] ?? null,
        'model' => $_GET['model'] ?? null,
        'vehicle_type' => $_GET['vehicle_type'] ?? null,
        'fuel_type' => $_GET['fuel_type'] ?? null,
        'transmission' => $_GET['transmission'] ?? null,
        'min_capacity' => $_GET['min_capacity'] ?? null,
        'max_capacity' => $_GET['max_capacity'] ?? null,
        'min_daily_rate' => $_GET['min_daily_rate'] ?? null,
        'max_daily_rate' => $_GET['max_daily_rate'] ?? null,
        'available_from' => $_GET['available_from'] ?? null,
        'available_to' => $_GET['available_to'] ?? null,
        'with_driver' => isset($_GET['with_driver']) ? (bool)$_GET['with_driver'] : null,
        'features' => isset($_GET['features']) ? explode(',', $_GET['features']) : [],
        'location' => $_GET['location'] ?? null,
        'keyword' => $_GET['keyword'] ?? null
    ];
    
    // Pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : CONFIG['PAGINATION']['VEHICLES_PER_PAGE'];
    $offset = ($page - 1) * $limit;
    
    // Sort parameters
    $sortBy = $_GET['sort_by'] ?? 'daily_rate';
    $sortDir = strtoupper($_GET['sort_dir'] ?? 'ASC');
    
    // Clean up null values and validate
    $filters = array_filter($filters, function($value) {
        return $value !== null;
    });
    
    // Validate sort direction
    if (!in_array($sortDir, ['ASC', 'DESC'])) {
        $sortDir = 'ASC';
    }
    
    // Build the search query
    $query = "
        SELECT v.*, 
               (SELECT image_url FROM vehicle_images 
                WHERE vehicle_id = v.vehicle_id AND is_primary = 1 LIMIT 1) AS primary_image
        FROM vehicles v
        WHERE v.is_available = 1
    ";
    
    $params = [];
    $conditions = [];
    
    // Add filter conditions
    if (!empty($filters['make'])) {
        $conditions[] = "v.make = :make";
        $params[':make'] = $filters['make'];
    }
    
    if (!empty($filters['model'])) {
        $conditions[] = "v.model = :model";
        $params[':model'] = $filters['model'];
    }
    
    if (!empty($filters['vehicle_type'])) {
        $conditions[] = "v.vehicle_type = :vehicle_type";
        $params[':vehicle_type'] = $filters['vehicle_type'];
    }
    
    if (!empty($filters['fuel_type'])) {
        $conditions[] = "v.fuel_type = :fuel_type";
        $params[':fuel_type'] = $filters['fuel_type'];
    }
    
    if (!empty($filters['transmission'])) {
        $conditions[] = "v.transmission = :transmission";
        $params[':transmission'] = $filters['transmission'];
    }
    
    if (!empty($filters['min_capacity'])) {
        $conditions[] = "v.capacity >= :min_capacity";
        $params[':min_capacity'] = $filters['min_capacity'];
    }
    
    if (!empty($filters['max_capacity'])) {
        $conditions[] = "v.capacity <= :max_capacity";
        $params[':max_capacity'] = $filters['max_capacity'];
    }
    
    if (!empty($filters['min_daily_rate'])) {
        $conditions[] = "v.daily_rate >= :min_daily_rate";
        $params[':min_daily_rate'] = $filters['min_daily_rate'];
    }
    
    if (!empty($filters['max_daily_rate'])) {
        $conditions[] = "v.daily_rate <= :max_daily_rate";
        $params[':max_daily_rate'] = $filters['max_daily_rate'];
    }
    
    if (!empty($filters['location'])) {
        $conditions[] = "v.location LIKE :location";
        $params[':location'] = '%' . $filters['location'] . '%';
    }
    
    if (isset($filters['with_driver'])) {
        $conditions[] = "v.with_driver_available = :with_driver";
        $params[':with_driver'] = $filters['with_driver'] ? 1 : 0;
    }
    
    // Handle date availability filtering
    if (!empty($filters['available_from']) && !empty($filters['available_to'])) {
        $conditions[] = "NOT EXISTS (
            SELECT 1 FROM bookings b 
            WHERE b.vehicle_id = v.vehicle_id 
            AND b.status NOT IN ('cancelled', 'completed')
            AND (
                (b.start_date <= :available_to AND b.end_date >= :available_from)
            )
        )";
        $params[':available_from'] = $filters['available_from'];
        $params[':available_to'] = $filters['available_to'];
    }
    
    // Handle keyword search across multiple fields
    if (!empty($filters['keyword'])) {
        $keywordCondition = "(
            v.make LIKE :keyword OR 
            v.model LIKE :keyword OR 
            v.vehicle_type LIKE :keyword OR 
            v.fuel_type LIKE :keyword OR
            v.color LIKE :keyword OR
            v.transmission LIKE :keyword
        )";
        $conditions[] = $keywordCondition;
        $params[':keyword'] = '%' . $filters['keyword'] . '%';
    }
    
    // Handle features filtering
    if (!empty($filters['features'])) {
        foreach ($filters['features'] as $index => $feature) {
            $featureParam = ":feature{$index}";
            $conditions[] = "v.features LIKE {$featureParam}";
            $params[$featureParam] = '%' . $feature . '%';
        }
    }
    
    // Add conditions to the query
    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }
    
    // Add sorting
    $query .= " ORDER BY v.{$sortBy} {$sortDir}";
    
    // Count total results for pagination
    $countQuery = str_replace("SELECT v.*, (SELECT image_url FROM vehicle_images WHERE vehicle_id = v.vehicle_id AND is_primary = 1 LIMIT 1) AS primary_image", "SELECT COUNT(*) as total", $query);
    $countQuery = preg_replace('/ORDER BY.*$/', '', $countQuery);
    
    // Execute count query
    $db = new Database();
    $countResult = $db->executeQuery($countQuery, $params);
    $totalVehicles = $countResult[0]['total'] ?? 0;
    
    // Add limit and offset
    $query .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    // Execute main query
    $vehicles = $db->executeQuery($query, $params);
    
    // Parse features JSON for each vehicle
    foreach ($vehicles as &$vehicle) {
        if (isset($vehicle['features']) && !empty($vehicle['features'])) {
            $vehicle['features'] = json_decode($vehicle['features'], true);
        } else {
            $vehicle['features'] = [];
        }
    }
    
    // Calculate pagination metadata
    $totalPages = ceil($totalVehicles / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;
    
    Utils::jsonResponse([
        'success' => true,
        'data' => $vehicles,
        'meta' => [
            'total' => $totalVehicles,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages,
            'has_next_page' => $hasNextPage,
            'has_prev_page' => $hasPrevPage
        ]
    ]);
}
