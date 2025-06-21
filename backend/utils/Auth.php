<?php
/**
 * Authentication Utility Class
 * 
 * Handles user authentication and JWT token management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Utils.php';

class Auth {
    /**
     * Generate a JWT token
     * 
     * @param array $userData User data
     * @return string JWT token
     */
    public static function generateToken($userData) {
        $issuedAt = time();
        $expirationTime = $issuedAt + CONFIG['SECURITY']['JWT_EXPIRY'];
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user' => [
                'id' => $userData['user_id'],
                'email' => $userData['email'],
                'role' => $userData['role_name'] ?? $userData['role_id']
            ]
        ];
        
        return self::encodeToken($payload);
    }
    
    /**
     * Encode a JWT token
     * 
     * @param array $payload Token payload
     * @return string Encoded token
     */
    private static function encodeToken($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, CONFIG['SECURITY']['JWT_SECRET'], true);
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }
    
    /**
     * Decode a JWT token
     * 
     * @param string $token JWT token
     * @return array|bool Decoded token payload or false on failure
     */
    public static function decodeToken($token) {
        $tokenParts = explode('.', $token);
        
        if (count($tokenParts) != 3) {
            return false;
        }
        
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $tokenParts;
        
        $signature = self::base64UrlDecode($base64UrlSignature);
        $rawSignature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, CONFIG['SECURITY']['JWT_SECRET'], true);
        
        if (!hash_equals($signature, $rawSignature)) {
            return false;
        }
        
        $payload = json_decode(self::base64UrlDecode($base64UrlPayload), true);
        
        // Check if token is expired
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Validate user authorization
     * 
     * @param array $allowedRoles Allowed roles
     * @return array|bool User data or false on failure
     */
    public static function validateAuth($allowedRoles = []) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return false;
        }
        
        $token = $matches[1];
        $payload = self::decodeToken($token);
        
        if (!$payload) {
            return false;
        }
        
        $user = $payload['user'];
        
        // Check if user role is allowed
        if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles)) {
            return false;
        }
        
        return $user;
    }
    
    /**
     * Validate token and return user ID
     * 
     * @return int|bool User ID or false on failure
     */
    public function validateToken() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return false;
        }
        
        $token = $matches[1];
        $payload = self::decodeToken($token);
        
        if (!$payload || !isset($payload['user']['id'])) {
            return false;
        }
        
        return $payload['user']['id'];
    }
    
    /**
     * Get current user ID from token
     * 
     * @return int|bool User ID or false on failure
     */
    public function getCurrentUserId() {
        return $this->validateToken();
    }
    
    /**
     * Base64 URL encode
     * 
     * @param string $data Data to encode
     * @return string Encoded data
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     * 
     * @param string $data Data to decode
     * @return string Decoded data
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Hash password
     * 
     * @param string $password Password
     * @return string Hashed password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     * 
     * @param string $password Password
     * @param string $hash Password hash
     * @return bool Verification result
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
