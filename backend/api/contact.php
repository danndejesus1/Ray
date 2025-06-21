<?php
/**
 * Contact API Endpoint
 * 
 * Handles contact form submissions
 */

require_once __DIR__ . '/../utils/Utils.php';

// Set headers for CORS and JSON content type
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Route requests
if ($requestMethod === 'POST') {
    submitContactForm();
} else {
    Utils::jsonError('Method not allowed', 405);
}

/**
 * Process contact form submission
 */
function submitContactForm() {
    $data = Utils::getPostData();
    
    // Validate required fields
    $requiredFields = ['name', 'email', 'message'];
    $validation = Utils::validateRequired($data, $requiredFields);
    
    if (!$validation['valid']) {
        Utils::jsonError($validation['message']);
        return;
    }
    
    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        Utils::jsonError('Invalid email format');
        return;
    }
    
    // Create database connection
    $db = new Database();
    
    // Insert into contacts table
    $insertData = [
        'name' => $data['name'],
        'email' => $data['email'],
        'message' => $data['message'],
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $result = $db->insert('contacts', $insertData);
    
    if (!$result) {
        Utils::jsonError('Failed to submit contact form');
        return;
    }
    
    // Log contact submission
    Utils::log("Contact form submitted by {$data['name']} ({$data['email']})", 'info');
    
    // If configured, send email notification
    if (CONFIG['NOTIFICATIONS']['CONTACT_FORM_EMAILS']) {
        sendContactNotification($data);
    }
    
    Utils::jsonResponse([
        'success' => true,
        'message' => 'Thank you for your message. We will get back to you soon!'
    ]);
}

/**
 * Send email notification about contact form submission
 * 
 * @param array $data Contact form data
 */
function sendContactNotification($data) {
    $adminEmail = CONFIG['NOTIFICATIONS']['ADMIN_EMAIL'];
    $subject = "New Contact Form Submission - CarGo";
    
    $message = "
    <html>
    <head>
        <title>New Contact Form Submission</title>
    </head>
    <body>
        <h2>New Contact Form Submission</h2>
        <p><strong>Name:</strong> {$data['name']}</p>
        <p><strong>Email:</strong> {$data['email']}</p>
        <p><strong>Message:</strong></p>
        <p>" . nl2br(htmlspecialchars($data['message'])) . "</p>
        <p><strong>IP Address:</strong> {$_SERVER['REMOTE_ADDR']}</p>
        <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
    </body>
    </html>
    ";
    
    // Headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: CarGo <" . CONFIG['NOTIFICATIONS']['FROM_EMAIL'] . ">" . "\r\n";
    $headers .= "Reply-To: {$data['email']}" . "\r\n";
    
    // Send email
    @mail($adminEmail, $subject, $message, $headers);
}
