<?php
/**
 * CarGo Contacts Table Structure
 * 
 * This file contains the SQL to create the contacts table
 */

$sql = "
CREATE TABLE IF NOT EXISTS `contacts` (
  `contact_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// If this file is being run directly, execute the SQL
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    require_once __DIR__ . '/../includes/Database.php';
      $db = new Database();
    try {
        $db->executeQuery($sql);
        echo "Contacts table created successfully";
    } catch (PDOException $e) {
        echo "Error creating contacts table: " . $e->getMessage();
    }
}
