<?php
/**
 * Notification Model
 * 
 * Handles database operations for notifications
 */

require_once __DIR__ . '/Model.php';

class Notification extends Model {
    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';
    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'related_id', 
        'is_read', 'created_at', 'updated_at'
    ];
    
    /**
     * Create a new notification
     * 
     * @param array $data Notification data
     * @return int|bool Last insert ID or false on failure
     */
    public function createNotification($data) {
        // Set default values
        $data['is_read'] = 0; // Unread by default
        
        return $this->create($data);
    }
    
    /**
     * Get notification by ID
     * 
     * @param int $notificationId Notification ID
     * @return array|null Notification data or null if not found
     */
    public function getNotificationById($notificationId) {
        return $this->getById($notificationId);
    }
    
    /**
     * Get user notifications
     * 
     * @param int $userId User ID
     * @param bool|null $isRead Filter by read status (true/false), or null for all
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array Notifications
     */
    public function getUserNotifications($userId, $isRead = null, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $params = [':user_id' => $userId];
        
        if ($isRead !== null) {
            $query .= " AND is_read = :is_read";
            $params[':is_read'] = $isRead ? 1 : 0;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
        
        return $this->db->executeQuery($query, $params);
    }
    
    /**
     * Mark notification as read
     * 
     * @param int $notificationId Notification ID
     * @return int|bool Number of affected rows or false on failure
     */
    public function markAsRead($notificationId) {
        return $this->update($notificationId, ['is_read' => 1]);
    }
    
    /**
     * Mark all notifications as read for a user
     * 
     * @param int $userId User ID
     * @return int|bool Number of affected rows or false on failure
     */
    public function markAllAsRead($userId) {
        $query = "UPDATE {$this->table} SET is_read = 1, updated_at = NOW() WHERE user_id = :user_id AND is_read = 0";
        $params = [':user_id' => $userId];
        
        return $this->db->executeQuery($query, $params);
    }
    
    /**
     * Get unread notification count
     * 
     * @param int $userId User ID
     * @return int Count of unread notifications
     */
    public function getUnreadCount($userId) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = :user_id AND is_read = 0";
        $params = [':user_id' => $userId];
        
        $result = $this->db->executeQuery($query, $params);
        
        return isset($result[0]['count']) ? (int)$result[0]['count'] : 0;
    }
    
    /**
     * Delete a notification
     * 
     * @param int $notificationId Notification ID
     * @return int|bool Number of affected rows or false on failure
     */
    public function deleteNotification($notificationId) {
        return $this->delete($notificationId);
    }
    
    /**
     * Send notification to multiple users
     * 
     * @param array $userIds Array of user IDs
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param int|null $relatedId Related entity ID (e.g., booking_id)
     * @return array Array of notification IDs
     */
    public function sendToMultipleUsers($userIds, $type, $title, $message, $relatedId = null) {
        $notificationIds = [];
        
        foreach ($userIds as $userId) {
            $data = [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'is_read' => 0
            ];
            
            if ($relatedId !== null) {
                $data['related_id'] = $relatedId;
            }
            
            $notificationId = $this->createNotification($data);
            
            if ($notificationId) {
                $notificationIds[] = $notificationId;
            }
        }
        
        return $notificationIds;
    }
    
    /**
     * Send a system notification to all users
     * 
     * @param string $title Notification title
     * @param string $message Notification message
     * @return int|bool Number of affected rows or false on failure
     */
    public function sendSystemNotification($title, $message) {
        require_once __DIR__ . '/User.php';
        $userModel = new User();
        
        // Get all active user IDs
        $users = $userModel->getAllActiveUserIds();
        $userIds = array_column($users, 'user_id');
        
        return $this->sendToMultipleUsers($userIds, 'system', $title, $message);
    }
    
    /**
     * Send a booking notification
     * 
     * @param int $userId User ID
     * @param string $type Notification type (e.g., 'booking_confirmed', 'booking_cancelled')
     * @param string $title Notification title
     * @param string $message Notification message
     * @param int $bookingId Booking ID
     * @return int|bool Last insert ID or false on failure
     */
    public function sendBookingNotification($userId, $type, $title, $message, $bookingId) {
        $data = [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $bookingId,
            'is_read' => 0
        ];
        
        return $this->createNotification($data);
    }
    
    /**
     * Send a payment notification
     * 
     * @param int $userId User ID
     * @param string $type Notification type (e.g., 'payment_success', 'payment_failed')
     * @param string $title Notification title
     * @param string $message Notification message
     * @param int $paymentId Payment ID
     * @return int|bool Last insert ID or false on failure
     */
    public function sendPaymentNotification($userId, $type, $title, $message, $paymentId) {
        $data = [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $paymentId,
            'is_read' => 0
        ];
        
        return $this->createNotification($data);
    }
}
