<?php
/**
 * Contact Model
 * 
 * Handles database operations for contact form submissions
 */

require_once __DIR__ . '/Model.php';

class Contact extends Model {
    protected $table = 'contacts';
    protected $primaryKey = 'contact_id';
    protected $fillable = [
        'name', 'email', 'message', 'ip_address', 
        'user_agent', 'is_read', 'created_at', 'updated_at'
    ];
    
    /**
     * Get all contacts with optional filters and pagination
     * 
     * @param array $filters Filter criteria
     * @param int $page Page number
     * @param int $limit Results per page
     * @return array Contacts and pagination data
     */
    public function getContacts($filters = [], $page = 1, $limit = 20) {
        $conditions = [];
        $params = [];
        
        // Add filters
        if (isset($filters['is_read']) && $filters['is_read'] !== null) {
            $conditions[] = "is_read = :is_read";
            $params[':is_read'] = (int)$filters['is_read'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(name LIKE :search OR email LIKE :search OR message LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Calculate offset
        $offset = ($page - 1) * $limit;
        
        // Build query
        $query = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        // Add order by
        $query .= " ORDER BY created_at DESC";
        
        // Count total results
        $countQuery = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
        $countResult = $this->db->executeQuery($countQuery, $params);
        $totalContacts = $countResult[0]['total'] ?? 0;
        
        // Add limit and offset
        $query .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        // Get results
        $contacts = $this->db->executeQuery($query, $params);
        
        // Calculate pagination data
        $totalPages = ceil($totalContacts / $limit);
        
        return [
            'contacts' => $contacts,
            'pagination' => [
                'total' => $totalContacts,
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ];
    }
    
    /**
     * Mark a contact as read
     * 
     * @param int $contactId Contact ID
     * @return bool Success status
     */
    public function markAsRead($contactId) {
        return $this->update($contactId, [
            'is_read' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get contact by ID
     * 
     * @param int $contactId Contact ID
     * @return array|null Contact data
     */
    public function getContactById($contactId) {
        return $this->getById($contactId);
    }
    
    /**
     * Get unread contacts count
     * 
     * @return int Number of unread contacts
     */
    public function getUnreadCount() {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE is_read = 0";
        $result = $this->db->executeQuery($query);
        
        return $result[0]['count'] ?? 0;
    }
    
    /**
     * Delete a contact
     * 
     * @param int $contactId Contact ID
     * @return bool Success status
     */
    public function deleteContact($contactId) {
        return $this->delete($contactId);
    }
}
