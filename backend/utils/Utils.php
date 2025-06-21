<?php
/**
 * Utilities Class
 * 
 * Common utility functions for the application
 */

class Utils {
    /**
     * Generate a JSON response
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Generate a JSON error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function jsonError($message, $statusCode = 400) {
        self::jsonResponse(['error' => $message], $statusCode);
    }
    
    /**
     * Get POST data
     * 
     * @return array POST data
     */
    public static function getPostData() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        return $data ?: $_POST;
    }
    
    /**
     * Validate required fields
     * 
     * @param array $data Data to validate
     * @param array $requiredFields Required fields
     * @return array|bool Validation result
     */
    public static function validateRequired($data, $requiredFields) {
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            return [
                'valid' => false,
                'message' => 'Missing required fields: ' . implode(', ', $missingFields)
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Generate a random string
     * 
     * @param int $length String length
     * @return string Random string
     */
    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    public static function getClientIp() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
    
    /**
     * Format currency
     * 
     * @param float $amount Amount
     * @param string $currencySymbol Currency symbol
     * @return string Formatted currency
     */
    public static function formatCurrency($amount, $currencySymbol = '₱') {
        return $currencySymbol . number_format($amount, 2);
    }
    
    /**
     * Format date
     * 
     * @param string $date Date string
     * @param string $format Date format
     * @return string Formatted date
     */
    public static function formatDate($date, $format = 'Y-m-d') {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    }
    
    /**
     * Calculate date difference in days
     * 
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return int Days difference
     */
    public static function dateDiffInDays($startDate, $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        
        return $interval->days;
    }
    
    /**
     * Sanitize input
     * 
     * @param string $input Input string
     * @return string Sanitized input
     */
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Log message to file
     * 
     * @param string $message Log message
     * @param string $level Log level
     * @return void
     */
    public static function log($message, $level = 'info') {
        require_once __DIR__ . '/../config/config.php';
        
        if (!CONFIG['DEBUG']['ENABLE_LOGS']) {
            return;
        }
        
        $logPath = CONFIG['DEBUG']['LOG_PATH'];
        
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        
        $logFile = $logPath . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
