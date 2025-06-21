<?php
/**
 * Database Connection Class
 * 
 * Handles database connections and operations
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private $port;
    private $charset;
    
    /**
     * Constructor - Sets database connection properties
     */
    public function __construct() {
        require_once __DIR__ . '/../config/config.php';
        
        $this->host = CONFIG['DATABASE']['HOST'];
        $this->db_name = CONFIG['DATABASE']['NAME'];
        $this->username = CONFIG['DATABASE']['USER'];
        $this->password = CONFIG['DATABASE']['PASSWORD'];
        $this->port = CONFIG['DATABASE']['PORT'];
        $this->charset = CONFIG['DATABASE']['CHARSET'];
    }
    
    /**
     * Connect to the database
     * 
     * @return PDO|null PDO connection object or null on failure
     */
    public function connect() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            return $this->conn;
        } catch(PDOException $e) {
            // Log the error
            error_log('Database Connection Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Execute a query with parameters
     * 
     * @param string $query SQL query
     * @param array $params Parameters for the query
     * @return array|bool Query result or false on failure
     */
    public function executeQuery($query, $params = []) {
        try {
            $stmt = $this->connect()->prepare($query);
            $stmt->execute($params);
            
            // If it's a SELECT query, return the results
            if (stripos($query, 'SELECT') === 0) {
                return $stmt->fetchAll();
            }
            
            // For INSERT, return the last insert ID
            if (stripos($query, 'INSERT') === 0) {
                return $this->conn->lastInsertId();
            }
            
            // For UPDATE or DELETE, return the number of affected rows
            return $stmt->rowCount();
        } catch(PDOException $e) {
            // Log the error
            error_log('Query Execution Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get a single record by ID
     * 
     * @param string $table Table name
     * @param string $id_field Name of ID field
     * @param mixed $id ID value
     * @return array|null Record data or null if not found
     */
    public function getById($table, $id_field, $id) {
        $query = "SELECT * FROM {$table} WHERE {$id_field} = :id LIMIT 1";
        $params = [':id' => $id];
        
        $result = $this->executeQuery($query, $params);
        
        return $result && count($result) > 0 ? $result[0] : null;
    }
    
    /**
     * Insert a record into a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of field => value
     * @return int|bool Last insert ID or false on failure
     */
    public function insert($table, $data) {
        // Build the query
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":{$field}";
        }, $fields);
        
        $fields_str = implode(', ', $fields);
        $placeholders_str = implode(', ', $placeholders);
        
        $query = "INSERT INTO {$table} ({$fields_str}) VALUES ({$placeholders_str})";
        
        // Build the parameters array
        $params = [];
        foreach ($data as $field => $value) {
            $params[":{$field}"] = $value;
        }
        
        return $this->executeQuery($query, $params);
    }
    
    /**
     * Update a record in a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of field => value
     * @param string $id_field Name of ID field
     * @param mixed $id ID value
     * @return int|bool Number of affected rows or false on failure
     */
    public function update($table, $data, $id_field, $id) {
        // Build the SET clause
        $set_parts = [];
        foreach (array_keys($data) as $field) {
            $set_parts[] = "{$field} = :{$field}";
        }
        
        $set_clause = implode(', ', $set_parts);
        
        $query = "UPDATE {$table} SET {$set_clause} WHERE {$id_field} = :id";
        
        // Build the parameters array
        $params = [];
        foreach ($data as $field => $value) {
            $params[":{$field}"] = $value;
        }
        $params[':id'] = $id;
        
        return $this->executeQuery($query, $params);
    }
    
    /**
     * Delete a record from a table
     * 
     * @param string $table Table name
     * @param string $id_field Name of ID field
     * @param mixed $id ID value
     * @return int|bool Number of affected rows or false on failure
     */
    public function delete($table, $id_field, $id) {
        $query = "DELETE FROM {$table} WHERE {$id_field} = :id";
        $params = [':id' => $id];
        
        return $this->executeQuery($query, $params);
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function beginTransaction() {
        return $this->connect()->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function rollback() {
        return $this->conn->rollback();
    }
}
