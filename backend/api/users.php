<?php
/**
 * Users API Endpoints
 * 
 * Handles user-related operations
 */

require_once __DIR__ . '/../models/User.php';
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

// Initialize user model
$userModel = new User();

// Extract endpoint and parameters
$endpoint = $pathParts[count($pathParts) - 2] ?? '';
$param = $pathParts[count($pathParts) - 1] ?? '';

// Check if param is a number (ID)
if (is_numeric($param)) {
    $userId = (int)$param;
    $endpoint = $pathParts[count($pathParts) - 3] ?? '';
} else {
    $userId = null;
    $endpoint = $pathParts[count($pathParts) - 2] ?? '';
    $action = $param;
}

// Validate JWT for protected endpoints
$isProtected = true;
$excludeEndpoints = ['login', 'register', 'forgot-password', 'reset-password', 'options'];

if ($isProtected && !in_array($endpoint, $excludeEndpoints) && !in_array($action, $excludeEndpoints)) {
    $auth = new Auth();
    $currentUserId = $auth->validateToken();
    
    if (!$currentUserId) {
        Utils::jsonError('Unauthorized access', 401);
        exit;
    }
}

// Route requests
switch ($endpoint) {
    case 'users':
        if ($userId) {
            // Single user operations
            if ($requestMethod === 'GET') {
                getUserById($userId);
            } elseif ($requestMethod === 'PUT') {
                updateUser($userId);
            } elseif ($requestMethod === 'DELETE') {
                deleteUser($userId);
            } else {
                Utils::jsonError('Method not allowed', 405);
            }
        } else {
            // Multiple users operations
            if ($requestMethod === 'GET') {
                getUsers();
            } elseif ($requestMethod === 'POST') {
                createUser();
            } else {
                Utils::jsonError('Method not allowed', 405);
            }
        }
        break;
        
    case 'profile':
        if ($requestMethod === 'GET') {
            getUserProfile();
        } elseif ($requestMethod === 'PUT') {
            updateUserProfile();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'register':
        if ($requestMethod === 'POST') {
            registerUser();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'login':
        if ($requestMethod === 'POST') {
            loginUser();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'forgot-password':
        if ($requestMethod === 'POST') {
            forgotPassword();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'reset-password':
        if ($requestMethod === 'POST') {
            resetPassword();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'change-password':
        if ($requestMethod === 'POST') {
            changePassword();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    default:
        Utils::jsonError('Endpoint not found', 404);
        break;
}

/**
 * Get all users with optional filters
 */
function getUsers() {
    global $userModel, $auth;
    
    // Check if user has admin privileges
    $currentUser = $auth->validateAuth([CONFIG['USER_ROLES']['ADMIN']]);
    
    if (!$currentUser) {
        Utils::jsonError('Forbidden: Admin access required', 403);
        return;
    }
    
    // Extract filter parameters from query string
    $filters = [
        'role' => $_GET['role'] ?? null,
        'status' => $_GET['status'] ?? null,
        'search' => $_GET['search'] ?? null
    ];
    
    // Handle pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Get users
    $users = $userModel->getUsers($filters, $page, $limit);
    
    if ($users) {
        Utils::jsonResponse(['data' => $users]);
    } else {
        Utils::jsonError('Failed to retrieve users', 500);
    }
}

/**
 * Get user by ID
 * 
 * @param int $userId User ID
 */
function getUserById($userId) {
    global $userModel, $auth, $currentUserId;
    
    // Check if user is requesting their own profile or has admin privileges
    $isAdmin = $auth->validateAuth([CONFIG['USER_ROLES']['ADMIN']]);
    
    if (!$isAdmin && $userId != $currentUserId) {
        Utils::jsonError('Forbidden: You can only access your own profile', 403);
        return;
    }
    
    $user = $userModel->getUserById($userId);
    
    if ($user) {
        // Remove sensitive data
        unset($user['password']);
        Utils::jsonResponse(['data' => $user]);
    } else {
        Utils::jsonError('User not found', 404);
    }
}

/**
 * Get current user profile
 */
function getUserProfile() {
    global $userModel, $currentUserId;
    
    $user = $userModel->getUserById($currentUserId);
    
    if ($user) {
        // Remove sensitive data
        unset($user['password']);
        Utils::jsonResponse(['data' => $user]);
    } else {
        Utils::jsonError('User not found', 404);
    }
}

/**
 * Create a new user (admin only)
 */
function createUser() {
    global $userModel, $auth;
    
    // Check if user has admin privileges
    $currentUser = $auth->validateAuth([CONFIG['USER_ROLES']['ADMIN']]);
    
    if (!$currentUser) {
        Utils::jsonError('Forbidden: Admin access required', 403);
        return;
    }
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        Utils::jsonError('Invalid input data', 400);
        return;
    }
    
    // Validate required fields
    $requiredFields = ['email', 'password', 'first_name', 'last_name', 'role_id'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            Utils::jsonError("Missing required field: $field", 400);
            return;
        }
    }
    
    // Check if email already exists
    if ($userModel->emailExists($data['email'])) {
        Utils::jsonError('Email already in use', 409);
        return;
    }
    
    // Hash password
    $data['password'] = Auth::hashPassword($data['password']);
    
    // Create user
    $userId = $userModel->createUser($data);
    
    if ($userId) {
        $user = $userModel->getUserById($userId);
        unset($user['password']); // Remove sensitive data
        Utils::jsonResponse(['message' => 'User created successfully', 'data' => $user], 201);
    } else {
        Utils::jsonError('Failed to create user', 500);
    }
}

/**
 * Update an existing user
 * 
 * @param int $userId User ID
 */
function updateUser($userId) {
    global $userModel, $auth, $currentUserId;
    
    // Check if user is updating their own profile or has admin privileges
    $isAdmin = $auth->validateAuth([CONFIG['USER_ROLES']['ADMIN']]);
    
    if (!$isAdmin && $userId != $currentUserId) {
        Utils::jsonError('Forbidden: You can only update your own profile', 403);
        return;
    }
    
    // Get PUT data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        Utils::jsonError('Invalid input data', 400);
        return;
    }
    
    // Get existing user
    $existingUser = $userModel->getUserById($userId);
    
    if (!$existingUser) {
        Utils::jsonError('User not found', 404);
        return;
    }
    
    // Prevent non-admins from changing role
    if (!$isAdmin && isset($data['role_id']) && $data['role_id'] != $existingUser['role_id']) {
        unset($data['role_id']);
    }
    
    // Prevent changing email to one that already exists
    if (isset($data['email']) && $data['email'] != $existingUser['email'] && $userModel->emailExists($data['email'])) {
        Utils::jsonError('Email already in use', 409);
        return;
    }
    
    // Don't update password through this endpoint
    if (isset($data['password'])) {
        unset($data['password']);
    }
    
    // Update user
    $result = $userModel->updateUser($userId, $data);
    
    if ($result) {
        $updatedUser = $userModel->getUserById($userId);
        unset($updatedUser['password']); // Remove sensitive data
        Utils::jsonResponse(['message' => 'User updated successfully', 'data' => $updatedUser]);
    } else {
        Utils::jsonError('Failed to update user', 500);
    }
}

/**
 * Update current user profile
 */
function updateUserProfile() {
    global $currentUserId;
    
    updateUser($currentUserId);
}

/**
 * Delete a user (admin only)
 * 
 * @param int $userId User ID
 */
function deleteUser($userId) {
    global $userModel, $auth;
    
    // Check if user has admin privileges
    $currentUser = $auth->validateAuth([CONFIG['USER_ROLES']['ADMIN']]);
    
    if (!$currentUser) {
        Utils::jsonError('Forbidden: Admin access required', 403);
        return;
    }
    
    // Get existing user
    $existingUser = $userModel->getUserById($userId);
    
    if (!$existingUser) {
        Utils::jsonError('User not found', 404);
        return;
    }
    
    // Don't allow deleting self
    if ($userId == $currentUser['id']) {
        Utils::jsonError('Cannot delete your own account', 400);
        return;
    }
    
    // Delete user
    $result = $userModel->deleteUser($userId);
    
    if ($result) {
        Utils::jsonResponse(['message' => 'User deleted successfully']);
    } else {
        Utils::jsonError('Failed to delete user', 500);
    }
}

/**
 * Register a new user
 */
function registerUser() {
    global $userModel;
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        Utils::jsonError('Invalid input data', 400);
        return;
    }
    
    // Validate required fields
    $requiredFields = ['email', 'password', 'first_name', 'last_name'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            Utils::jsonError("Missing required field: $field", 400);
            return;
        }
    }
    
    // Check if email already exists
    if ($userModel->emailExists($data['email'])) {
        Utils::jsonError('Email already in use', 409);
        return;
    }
    
    // Set default role to user
    $data['role_id'] = $userModel->getRoleIdByName(CONFIG['USER_ROLES']['USER']);
    
    // Hash password
    $data['password'] = Auth::hashPassword($data['password']);
    
    // Create user
    $userId = $userModel->createUser($data);
    
    if ($userId) {
        $user = $userModel->getUserById($userId);
        unset($user['password']); // Remove sensitive data
        
        // Generate JWT token
        $token = Auth::generateToken($user);
        
        Utils::jsonResponse([
            'message' => 'Registration successful',
            'data' => $user,
            'token' => $token
        ], 201);
    } else {
        Utils::jsonError('Failed to register user', 500);
    }
}

/**
 * Login user
 */
function loginUser() {
    global $userModel;
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['email']) || !isset($data['password'])) {
        Utils::jsonError('Email and password are required', 400);
        return;
    }
    
    // Get user by email
    $user = $userModel->getUserByEmail($data['email']);
    
    if (!$user) {
        Utils::jsonError('Invalid email or password', 401);
        return;
    }
    
    // Verify password
    if (!Auth::verifyPassword($data['password'], $user['password'])) {
        Utils::jsonError('Invalid email or password', 401);
        return;
    }
    
    // Check if account is active
    if ($user['status'] !== 'active') {
        Utils::jsonError('Account is not active', 403);
        return;
    }
    
    // Remove sensitive data
    unset($user['password']);
    
    // Generate JWT token
    $token = Auth::generateToken($user);
    
    // Update last login timestamp
    $userModel->updateLastLogin($user['user_id']);
    
    Utils::jsonResponse([
        'message' => 'Login successful',
        'data' => $user,
        'token' => $token
    ]);
}

/**
 * Forgot password
 */
function forgotPassword() {
    global $userModel;
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['email'])) {
        Utils::jsonError('Email is required', 400);
        return;
    }
    
    // Check if email exists
    $user = $userModel->getUserByEmail($data['email']);
    
    if (!$user) {
        // For security reasons, don't disclose that the email doesn't exist
        Utils::jsonResponse(['message' => 'If your email is registered, you will receive a password reset link']);
        return;
    }
    
    // Generate reset token
    $resetToken = bin2hex(random_bytes(32));
    $resetExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Save reset token to database
    $userModel->saveResetToken($user['user_id'], $resetToken, $resetExpiry);
    
    // In a real application, send an email with the reset link
    // For this demo, we'll just return the token
    Utils::jsonResponse([
        'message' => 'If your email is registered, you will receive a password reset link',
        'debug_token' => $resetToken // Remove in production
    ]);
}

/**
 * Reset password
 */
function resetPassword() {
    global $userModel;
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['token']) || !isset($data['password'])) {
        Utils::jsonError('Token and new password are required', 400);
        return;
    }
    
    // Validate token
    $user = $userModel->validateResetToken($data['token']);
    
    if (!$user) {
        Utils::jsonError('Invalid or expired token', 400);
        return;
    }
    
    // Hash new password
    $newPassword = Auth::hashPassword($data['password']);
    
    // Update password and clear reset token
    $result = $userModel->resetPassword($user['user_id'], $newPassword);
    
    if ($result) {
        Utils::jsonResponse(['message' => 'Password has been reset successfully']);
    } else {
        Utils::jsonError('Failed to reset password', 500);
    }
}

/**
 * Change password
 */
function changePassword() {
    global $userModel, $currentUserId;
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['current_password']) || !isset($data['new_password'])) {
        Utils::jsonError('Current password and new password are required', 400);
        return;
    }
    
    // Get user
    $user = $userModel->getUserById($currentUserId);
    
    if (!$user) {
        Utils::jsonError('User not found', 404);
        return;
    }
    
    // Verify current password
    if (!Auth::verifyPassword($data['current_password'], $user['password'])) {
        Utils::jsonError('Current password is incorrect', 400);
        return;
    }
    
    // Hash new password
    $newPassword = Auth::hashPassword($data['new_password']);
    
    // Update password
    $result = $userModel->updatePassword($currentUserId, $newPassword);
    
    if ($result) {
        Utils::jsonResponse(['message' => 'Password changed successfully']);
    } else {
        Utils::jsonError('Failed to change password', 500);
    }
}
