<?php
/**
 * Booking Model
 * 
 * Handles database operations for bookings
 */

require_once __DIR__ . '/Model.php';

class Booking extends Model {
    protected $table = 'bookings';
    protected $primaryKey = 'booking_id';
    protected $fillable = [
        'user_id', 'vehicle_id', 'booking_number', 'start_date', 'end_date',
        'pickup_location', 'return_location', 'with_driver', 'status',
        'cancellation_reason', 'booking_notes', 'total_amount',
        'created_at', 'updated_at'
    ];
    
    /**
     * Create a new booking
     * 
     * @param array $bookingData Booking data
     * @return int|bool Last insert ID or false on failure
     */
    public function createBooking($bookingData) {
        // Generate a unique booking number
        $bookingNumber = $this->generateBookingNumber();
        $bookingData['booking_number'] = $bookingNumber;
        
        // Set initial status to 'pending'
        $bookingData['status'] = 'pending';
        
        return $this->create($bookingData);
    }
    
    /**
     * Generate a unique booking number
     * 
     * @return string Booking number
     */
    private function generateBookingNumber() {
        return 'BK-' . strtoupper(substr(uniqid(), -6)) . '-' . date('ymd');
    }
      /**
     * Get bookings for a user with pagination
     * 
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string|null $status Optional booking status filter
     * @return array User bookings
     */
    public function getUserBookings($userId, $status = null, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $query = "
            SELECT b.*, v.make, v.model, v.license_plate, v.vehicle_type,
                   (SELECT image_url FROM vehicle_images 
                    WHERE vehicle_id = v.vehicle_id AND is_primary = 1 LIMIT 1) AS vehicle_image
            FROM {$this->table} b
            JOIN vehicles v ON b.vehicle_id = v.vehicle_id
            WHERE b.user_id = :user_id
        ";
        
        $params = [':user_id' => $userId];
        
        if ($status) {
            $query .= " AND b.status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY b.start_date DESC";
        $query .= " LIMIT {$limit} OFFSET {$offset}";
        
        return $this->db->executeQuery($query, $params);
    }
    
    /**
     * Update booking status
     * 
     * @param int $bookingId Booking ID
     * @param string $status New status
     * @param string|null $notes Optional notes
     * @return int|bool Number of affected rows or false on failure
     */
    public function updateStatus($bookingId, $status, $notes = null) {
        $data = ['status' => $status];
        
        if ($notes) {
            $data['booking_notes'] = $notes;
        }
        
        return $this->update($bookingId, $data);
    }
    
    /**
     * Cancel a booking
     * 
     * @param int $bookingId Booking ID
     * @param string $reason Cancellation reason
     * @return int|bool Number of affected rows or false on failure
     */
    public function cancelBooking($bookingId, $reason) {
        $data = [
            'status' => 'cancelled',
            'cancellation_reason' => $reason
        ];
        
        return $this->update($bookingId, $data);
    }
    
    /**
     * Get booking details with vehicle and user information
     * 
     * @param int $bookingId Booking ID
     * @return array|null Booking details or null if not found
     */
    public function getBookingDetails($bookingId) {
        $query = "
            SELECT b.*, 
                   v.make, v.model, v.license_plate, v.vehicle_type, v.fuel_type, v.capacity,
                   u.first_name, u.last_name, u.email, u.phone,
                   (SELECT image_url FROM vehicle_images 
                    WHERE vehicle_id = v.vehicle_id AND is_primary = 1 LIMIT 1) AS vehicle_image
            FROM {$this->table} b
            JOIN vehicles v ON b.vehicle_id = v.vehicle_id
            JOIN users u ON b.user_id = u.user_id
            WHERE b.booking_id = :booking_id
        ";
        
        $params = [':booking_id' => $bookingId];
        $result = $this->db->executeQuery($query, $params);
        
        return $result && count($result) > 0 ? $result[0] : null;
    }
    
    /**
     * Get booking by ID with additional vehicle information
     * 
     * @param int $bookingId Booking ID
     * @return array|null Booking data or null if not found
     */
    public function getBookingById($bookingId) {
        $query = "
            SELECT b.*, v.make, v.model, v.license_plate, v.vehicle_type,
                   (SELECT image_url FROM vehicle_images 
                    WHERE vehicle_id = v.vehicle_id AND is_primary = 1 LIMIT 1) AS vehicle_image
            FROM {$this->table} b
            JOIN vehicles v ON b.vehicle_id = v.vehicle_id
            WHERE b.{$this->primaryKey} = :booking_id
            LIMIT 1
        ";
        
        $params = [':booking_id' => $bookingId];
        $result = $this->db->executeQuery($query, $params);
        
        return $result && count($result) > 0 ? $result[0] : null;
    }
    
    /**
     * Get all bookings with filters
     * 
     * @param array $filters Filters for status, dates, vehicle_id
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array Bookings
     */
    public function getBookings($filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $query = "
            SELECT b.*, v.make, v.model, v.license_plate, v.vehicle_type,
                   u.first_name, u.last_name, u.email,
                   (SELECT image_url FROM vehicle_images 
                    WHERE vehicle_id = v.vehicle_id AND is_primary = 1 LIMIT 1) AS vehicle_image
            FROM {$this->table} b
            JOIN vehicles v ON b.vehicle_id = v.vehicle_id
            JOIN users u ON b.user_id = u.user_id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND b.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['from_date'])) {
            $query .= " AND b.start_date >= :from_date";
            $params[':from_date'] = $filters['from_date'];
        }
        
        if (!empty($filters['to_date'])) {
            $query .= " AND b.end_date <= :to_date";
            $params[':to_date'] = $filters['to_date'];
        }
        
        if (!empty($filters['vehicle_id'])) {
            $query .= " AND b.vehicle_id = :vehicle_id";
            $params[':vehicle_id'] = $filters['vehicle_id'];
        }
        
        // Order by start date descending
        $query .= " ORDER BY b.start_date DESC";
        
        // Add pagination
        $query .= " LIMIT {$limit} OFFSET {$offset}";
        
        return $this->db->executeQuery($query, $params);
    }
    
    /**
     * Check if a vehicle is available for a given date range
     * 
     * @param int $vehicleId Vehicle ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @param int|null $excludeBookingId Optional booking ID to exclude
     * @return bool True if available, false if not
     */
    public function isVehicleAvailable($vehicleId, $startDate, $endDate, $excludeBookingId = null) {
        $query = "
            SELECT COUNT(*) as booking_count
            FROM {$this->table}
            WHERE vehicle_id = :vehicle_id
            AND status NOT IN ('cancelled', 'rejected')
            AND (
                (start_date <= :end_date AND end_date >= :start_date)
            )
        ";
        
        $params = [
            ':vehicle_id' => $vehicleId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ];
        
        if ($excludeBookingId) {
            $query .= " AND {$this->primaryKey} != :exclude_id";
            $params[':exclude_id'] = $excludeBookingId;
        }
        
        $result = $this->db->executeQuery($query, $params);
        
        return $result[0]['booking_count'] == 0;
    }
    
    /**
     * Update a booking
     * 
     * @param int $bookingId Booking ID
     * @param array $data Booking data
     * @return int|bool Number of affected rows or false on failure
     */
    public function updateBooking($bookingId, $data) {
        return $this->update($bookingId, $data);
    }
    
    /**
     * Update booking status
     * 
     * @param int $bookingId Booking ID
     * @param string $status New status
     * @return int|bool Number of affected rows or false on failure
     */
    public function updateBookingStatus($bookingId, $status) {
        return $this->updateStatus($bookingId, $status);
    }
    
    /**
     * Check if a booking can be cancelled based on business rules
     * 
     * @param int $bookingId Booking ID
     * @return bool True if can be cancelled, false if not
     */
    public function canCancelBooking($bookingId) {
        $booking = $this->getBookingById($bookingId);
        
        if (!$booking) {
            return false;
        }
        
        // If booking is already cancelled, completed, or rejected
        if (in_array($booking['status'], ['cancelled', 'completed', 'rejected'])) {
            return false;
        }
        
        // Check cancellation policy - within 24 hours of booking start
        $startDate = new DateTime($booking['start_date']);
        $now = new DateTime();
        $hoursDiff = ($startDate->getTimestamp() - $now->getTimestamp()) / 3600;
        
        // Get cancellation hours from config
        $cancellationHours = CONFIG['BOOKING']['CANCELLATION_HOURS'];
        
        // Can cancel if more than cancellation_hours before start
        return $hoursDiff >= $cancellationHours;
    }
  
}
