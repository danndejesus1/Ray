<?php
/**
 * Notifications API Endpoints
 * 
 * Handles notification-related operations
 */

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

// Create notification model
require_once __DIR__ . '/../models/Notification.php';
$notificationModel = new Notification();

// Extract endpoint and parameters
$endpoint = $pathParts[count($pathParts) - 2] ?? '';
$param = $pathParts[count($pathParts) - 1] ?? '';

// Check if param is a number (ID)
if (is_numeric($param)) {
    $notificationId = (int)$param;
    $endpoint = $pathParts[count($pathParts) - 3] ?? '';
} else {
    $notificationId = null;
    $endpoint = $pathParts[count($pathParts) - 2] ?? '';
    $action = $param;
}

// Validate JWT for protected endpoints
$auth = new Auth();
$userId = $auth->validateToken();

if (!$userId) {
    Utils::jsonError('Unauthorized access', 401);
    exit;
}

// Route requests
switch ($endpoint) {
    case 'notifications':
        if ($notificationId) {
            // Single notification operations
            if ($requestMethod === 'GET') {
                getNotificationById($notificationId);
            } elseif ($requestMethod === 'PUT') {
                updateNotification($notificationId);
            } elseif ($requestMethod === 'DELETE') {
                deleteNotification($notificationId);
            } else {
                Utils::jsonError('Method not allowed', 405);
            }
        } else {
            // Multiple notifications operations
            if ($requestMethod === 'GET') {
                getUserNotifications();
            } else {
                Utils::jsonError('Method not allowed', 405);
            }
        }
        break;
        
    case 'read':
        if ($requestMethod === 'PUT') {
            markAsRead($notificationId);
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'read-all':
        if ($requestMethod === 'PUT') {
            markAllAsRead();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'count':
        if ($requestMethod === 'GET') {
            getUnreadCount();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    case 'send':
        if ($requestMethod === 'POST') {
            sendNotification();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
        break;
        
    default:
        Utils::jsonError('Endpoint not found', 404);
        break;
}

/**
 * Get user's notifications
 */
function getUserNotifications() {
    global $notificationModel, $userId;
    
    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $isRead = isset($_GET['is_read']) ? ($_GET['is_read'] === 'true') : null;
    
    // Get notifications
    $notifications = $notificationModel->getUserNotifications($userId, $isRead, $page, $limit);
    
    Utils::jsonResponse(['data' => $notifications]);
}

/**
 * Get notification by ID
 * 
 * @param int $notificationId Notification ID
 */
function getNotificationById($notificationId) {
    global $notificationModel, $userId;
    
    $notification = $notificationModel->getNotificationById($notificationId);
    
    if (!$notification) {
        Utils::jsonError('Notification not found', 404);
        return;
    }
    
    // Ensure user can only access their own notifications
    if ($notification['user_id'] != $userId) {
        Utils::jsonError('Forbidden: You can only access your own notifications', 403);
        return;
    }
    
    Utils::jsonResponse(['data' => $notification]);
}

/**
 * Mark notification as read
 * 
 * @param int $notificationId Notification ID
 */
function markAsRead($notificationId) {
    global $notificationModel, $userId;
    
    $notification = $notificationModel->getNotificationById($notificationId);
    
    if (!$notification) {
        Utils::jsonError('Notification not found', 404);
        return;
    }
    
    // Ensure user can only modify their own notifications
    if ($notification['user_id'] != $userId) {
        Utils::jsonError('Forbidden: You can only modify your own notifications', 403);
        return;
    }
    
    // Mark as read
    $result = $notificationModel->markAsRead($notificationId);
    
    if ($result) {
        Utils::jsonResponse(['message' => 'Notification marked as read']);
    } else {
        Utils::jsonError('Failed to mark notification as read', 500);
    }
}

/**
 * Mark all notifications as read for the current user
 */
function markAllAsRead() {
    global $notificationModel, $userId;
    
    // Mark all as read
    $result = $notificationModel->markAllAsRead($userId);
    
    if ($result) {
        Utils::jsonResponse(['message' => 'All notifications marked as read']);
    } else {
        Utils::jsonError('Failed to mark all notifications as read', 500);
    }
}

/**
 * Get unread notification count for the current user
 */
function getUnreadCount() {
    global $notificationModel, $userId;
    
    $count = $notificationModel->getUnreadCount($userId);
    
    Utils::jsonResponse(['data' => ['count' => $count]]);
}

/**
 * Delete a notification
 * 
 * @param int $notificationId Notification ID
 */
function deleteNotification($notificationId) {
    global $notificationModel, $userId;
    
    $notification = $notificationModel->getNotificationById($notificationId);
    
    if (!$notification) {
        Utils::jsonError('Notification not found', 404);
        return;
    }
    
    // Ensure user can only delete their own notifications
    if ($notification['user_id'] != $userId) {
        Utils::jsonError('Forbidden: You can only delete your own notifications', 403);
        return;
    }
    
    // Delete notification
    $result = $notificationModel->deleteNotification($notificationId);
    
    if ($result) {
        Utils::jsonResponse(['message' => 'Notification deleted']);
    } else {
        Utils::jsonError('Failed to delete notification', 500);
    }
}

/**
 * Send a notification (admin only)
 */
function sendNotification() {
    global $notificationModel, $auth;
    
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
    $requiredFields = ['user_id', 'type', 'title', 'message'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            Utils::jsonError("Missing required field: $field", 400);
            return;
        }
    }
    
    // Send notification
    $notificationId = $notificationModel->createNotification($data);
    
    if ($notificationId) {
        $notification = $notificationModel->getNotificationById($notificationId);
        Utils::jsonResponse(['message' => 'Notification sent successfully', 'data' => $notification], 201);
    } else {
        Utils::jsonError('Failed to send notification', 500);
    }
}

/**
 * Update a notification
 * 
 * @param int $notificationId Notification ID
 */
function updateNotification($notificationId) {
    global $notificationModel, $userId;
    
    // Get PUT data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        Utils::jsonError('Invalid input data', 400);
        return;
    }
    
    $notification = $notificationModel->getNotificationById($notificationId);
    
    if (!$notification) {
        Utils::jsonError('Notification not found', 404);
        return;
    }
    
    // Ensure user can only modify their own notifications
    if ($notification['user_id'] != $userId) {
        Utils::jsonError('Forbidden: You can only modify your own notifications', 403);
        return;
    }
    
    // Only allow updating is_read status
    if (isset($data['is_read'])) {
        $result = $notificationModel->update($notificationId, ['is_read' => $data['is_read'] ? 1 : 0]);
        
        if ($result) {
            $updatedNotification = $notificationModel->getNotificationById($notificationId);
            Utils::jsonResponse(['message' => 'Notification updated successfully', 'data' => $updatedNotification]);
        } else {
            Utils::jsonError('Failed to update notification', 500);
        }
    } else {
        Utils::jsonError('No valid fields to update', 400);
    }
}
