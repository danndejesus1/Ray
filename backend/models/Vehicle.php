<?php
/**
 * Vehicle Model
 * 
 * Handles database operations for vehicles
 */

require_once __DIR__ . '/Model.php';

class Vehicle extends Model {
    protected $table = 'vehicles';
    protected $primaryKey = 'vehicle_id';
    protected $fillable = [
        'make', 'model', 'year', 'color', 'license_plate', 'vin',
        'vehicle_type', 'fuel_type', 'transmission', 'mileage',
        'capacity', 'daily_rate', 'weekly_rate', 'monthly_rate',
        'with_driver_rate', 'insurance_details', 'is_available',
        'maintenance_status', 'maintenance_notes', 'features',
        'created_at', 'updated_at'
    ];
    
    /**
     * Get all available vehicles with optional filters
     * 
     * @param array $filters Filter criteria
     * @return array Available vehicles
     */
    public function getAvailableVehicles($filters = []) {
        $query = "
            SELECT v.*, 
                   (SELECT image_url FROM vehicle_images 
                    WHERE vehicle_id = v.vehicle_id AND is_primary = 1 LIMIT 1) AS primary_image
            FROM {$this->table} v
            WHERE v.is_available = 1
        ";
        
        $params = [];
        
        // Add filters if provided
        if (isset($filters['vehicle_type']) && $filters['vehicle_type']) {
            $query .= " AND v.vehicle_type = :vehicle_type";
            $params[':vehicle_type'] = $filters['vehicle_type'];
        }
        
        if (isset($filters['make']) && $filters['make']) {
            $query .= " AND v.make = :make";
            $params[':make'] = $filters['make'];
        }
        
        if (isset($filters['fuel_type']) && $filters['fuel_type']) {
            $query .= " AND v.fuel_type = :fuel_type";
            $params[':fuel_type'] = $filters['fuel_type'];
        }
        
        if (isset($filters['capacity']) && $filters['capacity']) {
            $query .= " AND v.capacity >= :capacity";
            $params[':capacity'] = $filters['capacity'];
        }
        
        if (isset($filters['max_daily_rate']) && $filters['max_daily_rate']) {
            $query .= " AND v.daily_rate <= :max_daily_rate";
            $params[':max_daily_rate'] = $filters['max_daily_rate'];
        }
        
        $query .= " ORDER BY v.daily_rate ASC";
        
        return $this->db->executeQuery($query, $params);
    }
    
    /**
     * Get vehicle with all images
     * 
     * @param int $vehicleId Vehicle ID
     * @return array|null Vehicle data with images or null if not found
     */
    public function getVehicleWithImages($vehicleId) {
        // Get vehicle data
        $vehicle = $this->getById($vehicleId);
        
        if (!$vehicle) {
            return null;
        }
        
        // Get vehicle images
        $imagesQuery = "
            SELECT image_id, image_url, is_primary
            FROM vehicle_images
            WHERE vehicle_id = :vehicle_id
            ORDER BY is_primary DESC
        ";
        
        $params = [':vehicle_id' => $vehicleId];
        $images = $this->db->executeQuery($imagesQuery, $params);
        
        $vehicle['images'] = $images;
        
        return $vehicle;
    }
    
    /**
     * Check if a vehicle is available for booking
     * 
     * @param int $vehicleId Vehicle ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return bool True if available, false otherwise
     */
    public function isAvailableForBooking($vehicleId, $startDate, $endDate) {
        // Check if the vehicle exists and is marked as available
        $vehicle = $this->getById($vehicleId);
        
        if (!$vehicle || !$vehicle['is_available']) {
            return false;
        }
        
        // Check if there are any overlapping bookings
        $query = "
            SELECT COUNT(*) as count
            FROM bookings
            WHERE vehicle_id = :vehicle_id
            AND status NOT IN ('cancelled', 'completed')
            AND (
                (start_date <= :end_date AND end_date >= :start_date)
            )
        ";
        
        $params = [
            ':vehicle_id' => $vehicleId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ];
        
        $result = $this->db->executeQuery($query, $params);
        
        return $result[0]['count'] == 0;
    }
    
    /**
     * Add an image to a vehicle
     * 
     * @param int $vehicleId Vehicle ID
     * @param string $imageUrl Image URL
     * @param bool $isPrimary Whether the image is primary
     * @return int|bool Last insert ID or false on failure
     */
    public function addImage($vehicleId, $imageUrl, $isPrimary = false) {
        // If this is a primary image, reset all other primary images
        if ($isPrimary) {
            $resetQuery = "
                UPDATE vehicle_images
                SET is_primary = 0
                WHERE vehicle_id = :vehicle_id
            ";
            
            $this->db->executeQuery($resetQuery, [':vehicle_id' => $vehicleId]);
        }
        
        // Insert the new image
        $query = "
            INSERT INTO vehicle_images (vehicle_id, image_url, is_primary)
            VALUES (:vehicle_id, :image_url, :is_primary)
        ";
        
        $params = [
            ':vehicle_id' => $vehicleId,
            ':image_url' => $imageUrl,
            ':is_primary' => $isPrimary ? 1 : 0
        ];
        
        return $this->db->executeQuery($query, $params);
    }
    
    /**
     * Get vehicle makes and models for filtering
     * 
     * @return array Vehicle makes and models
     */
    public function getVehicleMakesAndModels() {
        $query = "
            SELECT DISTINCT make, model
            FROM {$this->table}
            ORDER BY make, model
        ";
        
        return $this->db->executeQuery($query);
    }
    
    /**
     * Get vehicle types for filtering
     * 
     * @return array Vehicle types
     */
    public function getVehicleTypes() {
        $query = "
            SELECT DISTINCT vehicle_type
            FROM {$this->table}
            ORDER BY vehicle_type
        ";
        
        return $this->db->executeQuery($query);
    }
    
    /**
     * Get fuel types for filtering
     * 
     * @return array Fuel types
     */
    public function getFuelTypes() {
        $query = "
            SELECT DISTINCT fuel_type
            FROM {$this->table}
            ORDER BY fuel_type
        ";
        
        return $this->db->executeQuery($query);
    }
}
