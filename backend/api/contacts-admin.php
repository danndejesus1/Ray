<?php
/**
 * Contact Management API Endpoint
 * 
 * Handles admin operations for contact form submissions
 */

require_once __DIR__ . '/../models/Contact.php';
require_once __DIR__ . '/../utils/Utils.php';
require_once __DIR__ . '/../utils/Auth.php';

// Set headers for CORS and JSON content type
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
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

// Initialize contact model
$contactModel = new Contact();

// Extract endpoint and parameters
$endpoint = $pathParts[count($pathParts) - 2] ?? '';
$param = $pathParts[count($pathParts) - 1] ?? '';

// Check if param is a number (ID)
if (is_numeric($param)) {
    $contactId = (int)$param;
    $endpoint = $pathParts[count($pathParts) - 3] ?? '';
} else {
    $contactId = null;
    $endpoint = $pathParts[count($pathParts) - 2] ?? '';
    $action = $param;
}

// Authenticate admin user
$user = Auth::validateAuth([CONFIG['USER_ROLES']['ADMIN']]);

if (!$user) {
    Utils::jsonError('Unauthorized', 401);
    exit;
}

// Route requests
if ($endpoint === 'contacts-admin') {
    if ($contactId) {
        // Single contact operations
        if ($requestMethod === 'GET') {
            getContactById($contactId);
        } elseif ($requestMethod === 'DELETE') {
            deleteContact($contactId);
        } elseif ($requestMethod === 'PUT' && $action === 'read') {
            markContactAsRead($contactId);
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
    } else {
        // Multiple contacts operations
        if ($requestMethod === 'GET') {
            getContacts();
        } else {
            Utils::jsonError('Method not allowed', 405);
        }
    }
} elseif ($endpoint === 'contacts-admin' && $action === 'unread-count') {
    if ($requestMethod === 'GET') {
        getUnreadCount();
    } else {
        Utils::jsonError('Method not allowed', 405);
    }
} else {
    Utils::jsonError('Endpoint not found', 404);
}

/**
 * Get all contacts with optional filters
 */
function getContacts() {
    global $contactModel;
    
    // Extract filter parameters
    $filters = [
        'is_read' => isset($_GET['is_read']) ? (int)$_GET['is_read'] : null,
        'search' => $_GET['search'] ?? null
    ];
    
    // Extract pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : CONFIG['PAGINATION']['DEFAULT_LIMIT'];
    
    // Get contacts
    $result = $contactModel->getContacts($filters, $page, $limit);
    
    Utils::jsonResponse([
        'success' => true,
        'data' => $result['contacts'],
        'meta' => $result['pagination']
    ]);
}

/**
 * Get contact by ID
 * 
 * @param int $contactId Contact ID
 */
function getContactById($contactId) {
    global $contactModel;
    
    $contact = $contactModel->getContactById($contactId);
    
    if (!$contact) {
        Utils::jsonError('Contact not found', 404);
        return;
    }
    
    Utils::jsonResponse([
        'success' => true,
        'data' => $contact
    ]);
}

/**
 * Mark contact as read
 * 
 * @param int $contactId Contact ID
 */
function markContactAsRead($contactId) {
    global $contactModel;
    
    $contact = $contactModel->getContactById($contactId);
    
    if (!$contact) {
        Utils::jsonError('Contact not found', 404);
        return;
    }
    
    $result = $contactModel->markAsRead($contactId);
    
    if (!$result) {
        Utils::jsonError('Failed to mark contact as read');
        return;
    }
    
    Utils::jsonResponse([
        'success' => true,
        'message' => 'Contact marked as read successfully'
    ]);
}

/**
 * Delete contact
 * 
 * @param int $contactId Contact ID
 */
function deleteContact($contactId) {
    global $contactModel;
    
    $contact = $contactModel->getContactById($contactId);
    
    if (!$contact) {
        Utils::jsonError('Contact not found', 404);
        return;
    }
    
    $result = $contactModel->deleteContact($contactId);
    
    if (!$result) {
        Utils::jsonError('Failed to delete contact');
        return;
    }
    
    Utils::jsonResponse([
        'success' => true,
        'message' => 'Contact deleted successfully'
    ]);
}

/**
 * Get unread contacts count
 */
function getUnreadCount() {
    global $contactModel;
    
    $count = $contactModel->getUnreadCount();
    
    Utils::jsonResponse([
        'success' => true,
        'count' => $count
    ]);
}
