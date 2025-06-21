<?php
/**
 * User Model
 * 
 * Handles database operations for users
 */

require_once __DIR__ . '/Model.php';

class User extends Model {
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    protected $fillable = [
        'role_id', 'email', 'password_hash', 'first_name', 'last_name', 
        'phone', 'address', 'city', 'country', 'date_of_birth', 
        'driving_license_number', 'driving_license_expiry', 'profile_image_url', 
        'is_verified', 'is_active', 'mfa_enabled', 'mfa_secret', 
        'failed_login_attempts', 'last_login_date', 'created_at', 'updated_at'
    ];
    
    /**
     * Get user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function getByEmail($email) {
        return $this->findOneBy('email', $email);
    }
    
    /**
     * Get user with role information
     * 
     * @param int $userId User ID
     * @return array|null User data with role information or null if not found
     */
    public function getUserWithRole($userId) {
        $query = "
            SELECT u.*, r.role_name
            FROM {$this->table} u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.user_id = :user_id
        ";
        
        $params = [':user_id' => $userId];
        $result = $this->db->executeQuery($query, $params);
        
        return $result && count($result) > 0 ? $result[0] : null;
    }
    
    /**
     * Authenticate user
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array|bool User data or false on failure
     */
    public function authenticate($email, $password) {
        // Get user by email
        $user = $this->getByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            // Increment failed login attempts
            $this->incrementFailedLoginAttempts($user['user_id']);
            return false;
        }
        
        // Update last login date and reset failed attempts
        $this->updateLoginData($user['user_id']);
        
        // Get user with role information
        return $this->getUserWithRole($user['user_id']);
    }
    
    /**
     * Increment failed login attempts
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    private function incrementFailedLoginAttempts($userId) {
        $query = "
            UPDATE {$this->table}
            SET failed_login_attempts = failed_login_attempts + 1
            WHERE user_id = :user_id
        ";
        
        $params = [':user_id' => $userId];
        return $this->db->executeQuery($query, $params) !== false;
    }
    
    /**
     * Update last login date and reset failed attempts
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    private function updateLoginData($userId) {
        $query = "
            UPDATE {$this->table}
            SET last_login_date = NOW(), failed_login_attempts = 0
            WHERE user_id = :user_id
        ";
        
        $params = [':user_id' => $userId];
        return $this->db->executeQuery($query, $params) !== false;
    }
    
    /**
     * Create a new user
     * 
     * @param array $userData User data
     * @return int|bool Last insert ID or false on failure
     */
    public function createUser($userData) {
        // Check if email already exists
        if ($this->exists(['email' => $userData['email']])) {
            return false;
        }
        
        // Hash password
        if (isset($userData['password'])) {
            $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            unset($userData['password']);
        }
        
        return $this->create($userData);
    }
    
    /**
     * Update user information
     * 
     * @param int $userId User ID
     * @param array $userData User data
     * @return int|bool Number of affected rows or false on failure
     */
    public function updateUser($userId, $userData) {
        // Handle password update
        if (isset($userData['password'])) {
            $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            unset($userData['password']);
        }
        
        return $this->update($userId, $userData);
    }
    
    /**
     * Get users by role
     * 
     * @param int $roleId Role ID
     * @return array Users with the specified role
     */
    public function getUsersByRole($roleId) {
        return $this->findBy('role_id', $roleId);
    }
}
