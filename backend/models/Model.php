<?php
/**
 * Base Model Class
 * 
 * All models will extend this class to inherit common functionality
 */

require_once __DIR__ . '/../includes/Database.php';

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    
    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Get all records from the table
     * 
     * @param array $conditions Optional WHERE conditions as field => value
     * @param string $orderBy Optional ORDER BY clause
     * @param int $limit Optional LIMIT value
     * @param int $offset Optional OFFSET value
     * @return array Records
     */
    public function getAll($conditions = [], $orderBy = null, $limit = null, $offset = null) {
        $query = "SELECT * FROM {$this->table}";
        $params = [];
        
        // Add WHERE conditions if provided
        if (!empty($conditions)) {
            $where_parts = [];
            foreach ($conditions as $field => $value) {
                $where_parts[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }
            
            $query .= " WHERE " . implode(' AND ', $where_parts);
        }
        
        // Add ORDER BY if provided
        if ($orderBy) {
            $query .= " ORDER BY {$orderBy}";
        }
        
        // Add LIMIT and OFFSET if provided
        if ($limit) {
            $query .= " LIMIT {$limit}";
            
            if ($offset) {
                $query .= " OFFSET {$offset}";
            }
        }
        
        return $this->db->executeQuery($query, $params);
    }
    
    /**
     * Get a single record by ID
     * 
     * @param mixed $id ID value
     * @return array|null Record data or null if not found
     */
    public function getById($id) {
        return $this->db->getById($this->table, $this->primaryKey, $id);
    }
    
    /**
     * Create a new record
     * 
     * @param array $data Record data
     * @return int|bool Last insert ID or false on failure
     */
    public function create($data) {
        // Filter data to only include fillable fields
        $filtered_data = array_intersect_key($data, array_flip($this->fillable));
        
        // Add created_at timestamp if not provided
        if (!isset($filtered_data['created_at']) && in_array('created_at', $this->fillable)) {
            $filtered_data['created_at'] = date('Y-m-d H:i:s');
        }
        
        // Add updated_at timestamp if not provided
        if (!isset($filtered_data['updated_at']) && in_array('updated_at', $this->fillable)) {
            $filtered_data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->insert($this->table, $filtered_data);
    }
    
    /**
     * Update a record
     * 
     * @param mixed $id ID value
     * @param array $data Record data
     * @return int|bool Number of affected rows or false on failure
     */
    public function update($id, $data) {
        // Filter data to only include fillable fields
        $filtered_data = array_intersect_key($data, array_flip($this->fillable));
        
        // Add updated_at timestamp if not provided
        if (!isset($filtered_data['updated_at']) && in_array('updated_at', $this->fillable)) {
            $filtered_data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update($this->table, $filtered_data, $this->primaryKey, $id);
    }
    
    /**
     * Delete a record
     * 
     * @param mixed $id ID value
     * @return int|bool Number of affected rows or false on failure
     */
    public function delete($id) {
        return $this->db->delete($this->table, $this->primaryKey, $id);
    }
    
    /**
     * Find records by a specific field value
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $orderBy Optional ORDER BY clause
     * @return array Records
     */
    public function findBy($field, $value, $orderBy = null) {
        return $this->getAll([$field => $value], $orderBy);
    }
    
    /**
     * Find a single record by a specific field value
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @return array|null Record data or null if not found
     */
    public function findOneBy($field, $value) {
        $result = $this->findBy($field, $value);
        return $result && count($result) > 0 ? $result[0] : null;
    }
    
    /**
     * Check if a record exists with the given conditions
     * 
     * @param array $conditions Conditions as field => value
     * @return bool True if exists, false otherwise
     */
    public function exists($conditions) {
        $query = "SELECT 1 FROM {$this->table} WHERE ";
        $where_parts = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $where_parts[] = "{$field} = :{$field}";
            $params[":{$field}"] = $value;
        }
        
        $query .= implode(' AND ', $where_parts) . " LIMIT 1";
        
        $result = $this->db->executeQuery($query, $params);
        return $result && count($result) > 0;
    }
    
    /**
     * Count records with the given conditions
     * 
     * @param array $conditions Optional WHERE conditions as field => value
     * @return int Number of records
     */
    public function count($conditions = []) {
        $query = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        // Add WHERE conditions if provided
        if (!empty($conditions)) {
            $where_parts = [];
            foreach ($conditions as $field => $value) {
                $where_parts[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }
            
            $query .= " WHERE " . implode(' AND ', $where_parts);
        }
        
        $result = $this->db->executeQuery($query, $params);
        return $result[0]['count'] ?? 0;
    }
}
