<?php
/**
 * Authentication API Endpoints
 * 
 * Handles user login, registration, and authentication
 */

require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/Utils.php';
require_once __DIR__ . '/../../utils/Auth.php';

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
$endpoint = end($pathParts);

// Initialize user model
$userModel = new User();

// Route requests
switch ($endpoint) {
    case 'login':
        if ($requestMethod === 'POST') {
            handleLogin();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'register':
        if ($requestMethod === 'POST') {
            handleRegister();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'verify-token':
        if ($requestMethod === 'POST') {
            handleVerifyToken();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    default:
        Utils::jsonError('Endpoint not found', 404);
        break;
}

/**
 * Handle user login
 */
function handleLogin() {
    global $userModel;
    
    $data = Utils::getPostData();
    
    // Validate required fields
    $validation = Utils::validateRequired($data, ['email', 'password']);
    
    if (!$validation['valid']) {
        Utils::jsonError($validation['message']);
        return;
    }
    
    // Authenticate user
    $user = $userModel->authenticate($data['email'], $data['password']);
    
    if (!$user) {
        Utils::jsonError('Invalid email or password', 401);
        return;
    }
    
    // Check if user is active
    if (!$user['is_active']) {
        Utils::jsonError('Your account is inactive. Please contact support.', 403);
        return;
    }
    
    // Generate JWT token
    $token = Auth::generateToken($user);
    
    // Log successful login
    Utils::log("User logged in: {$user['email']}", 'info');
    
    // Return user data and token
    Utils::jsonResponse([
        'success' => true,
        'user' => [
            'id' => $user['user_id'],
            'email' => $user['email'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'role' => $user['role_name']
        ],
        'token' => $token
    ]);
}

/**
 * Handle user registration
 */
function handleRegister() {
    global $userModel;
    
    $data = Utils::getPostData();
    
    // Validate required fields
    $requiredFields = [
        'email', 'password', 'first_name', 'last_name', 'phone'
    ];
    
    $validation = Utils::validateRequired($data, $requiredFields);
    
    if (!$validation['valid']) {
        Utils::jsonError($validation['message']);
        return;
    }
    
    // Check if email already exists
    $existingUser = $userModel->getByEmail($data['email']);
    
    if ($existingUser) {
        Utils::jsonError('Email already exists');
        return;
    }
    
    // Validate password strength
    if (strlen($data['password']) < CONFIG['SECURITY']['PASSWORD_MIN_LENGTH']) {
        Utils::jsonError('Password must be at least ' . CONFIG['SECURITY']['PASSWORD_MIN_LENGTH'] . ' characters');
        return;
    }
      // Set user role to 'user'
    $roleQuery = "SELECT role_id FROM roles WHERE role_name = 'user'";
    $db = new Database();
    $roleResult = $db->executeQuery($roleQuery);
    $roleId = $roleResult[0]['role_id'] ?? 3; // Default to 3 if not found
    
    // Prepare user data
    $userData = [
        'role_id' => $roleId,
        'email' => $data['email'],
        'password' => $data['password'], // Will be hashed in createUser
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'phone' => $data['phone'],
        'is_verified' => 0,
        'is_active' => 1
    ];
    
    // Add optional fields if provided
    if (isset($data['address'])) $userData['address'] = $data['address'];
    if (isset($data['city'])) $userData['city'] = $data['city'];
    if (isset($data['country'])) $userData['country'] = $data['country'];
    if (isset($data['date_of_birth'])) $userData['date_of_birth'] = $data['date_of_birth'];
    
    // Create user
    $userId = $userModel->createUser($userData);
    
    if (!$userId) {
        Utils::jsonError('Failed to create user');
        return;
    }
    
    // Get created user
    $user = $userModel->getUserWithRole($userId);
    
    // Generate JWT token
    $token = Auth::generateToken($user);
    
    // Log registration
    Utils::log("User registered: {$user['email']}", 'info');
    
    // Return user data and token
    Utils::jsonResponse([
        'success' => true,
        'user' => [
            'id' => $user['user_id'],
            'email' => $user['email'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'role' => $user['role_name']
        ],
        'token' => $token
    ], 201);
}

/**
 * Handle token verification
 */
function handleVerifyToken() {
    $data = Utils::getPostData();
    
    // Validate required fields
    $validation = Utils::validateRequired($data, ['token']);
    
    if (!$validation['valid']) {
        Utils::jsonError($validation['message']);
        return;
    }
    
    // Decode token
    $payload = Auth::decodeToken($data['token']);
    
    if (!$payload) {
        Utils::jsonError('Invalid or expired token', 401);
        return;
    }
    
    // Return token payload
    Utils::jsonResponse([
        'success' => true,
        'user' => $payload['user']
    ]);
}
