<?php
/**
 * Payment Model
 * 
 * Handles database operations for payments
 */

require_once __DIR__ . '/Model.php';

class Payment extends Model {
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';
    protected $fillable = [
        'booking_id', 'payment_number', 'amount', 'processing_fee', 'tax_amount',
        'total_amount', 'payment_method', 'payment_status', 'transaction_reference',
        'card_last_four', 'refund_amount', 'refund_reason', 'created_at', 'updated_at'
    ];
    
    /**
     * Create a new payment
     * 
     * @param array $paymentData Payment data
     * @return int|bool Last insert ID or false on failure
     */
    public function createPayment($paymentData) {
        // Generate a unique payment number
        $paymentNumber = $this->generatePaymentNumber();
        $paymentData['payment_number'] = $paymentNumber;
        
        // Set initial status to 'pending'
        $paymentData['payment_status'] = 'pending';
        
        return $this->create($paymentData);
    }
    
    /**
     * Generate a unique payment number
     * 
     * @return string Payment number
     */
    private function generatePaymentNumber() {
        return 'PY-' . strtoupper(substr(uniqid(), -6)) . '-' . date('ymd');
    }
    
    /**
     * Update payment status
     * 
     * @param int $paymentId Payment ID
     * @param string $status New status
     * @param string|null $transactionReference Transaction reference
     * @return int|bool Number of affected rows or false on failure
     */
    public function updateStatus($paymentId, $status, $transactionReference = null) {
        $data = ['payment_status' => $status];
        
        if ($transactionReference) {
            $data['transaction_reference'] = $transactionReference;
        }
        
        $result = $this->update($paymentId, $data);
        
        // If payment is completed, update the booking status to confirmed
        if ($result && $status === 'completed') {
            $payment = $this->getById($paymentId);
            
            if ($payment) {
                require_once __DIR__ . '/Booking.php';
                $bookingModel = new Booking();
                $bookingModel->updateStatus($payment['booking_id'], 'confirmed', 'Payment completed');
            }
        }
        
        return $result;
    }
    
    /**
     * Process refund
     * 
     * @param int $paymentId Payment ID
     * @param float $refundAmount Refund amount
     * @param string $refundReason Refund reason
     * @return int|bool Number of affected rows or false on failure
     */
    public function processRefund($paymentId, $refundAmount, $refundReason) {
        $data = [
            'payment_status' => 'refunded',
            'refund_amount' => $refundAmount,
            'refund_reason' => $refundReason
        ];
        
        return $this->update($paymentId, $data);
    }
    
    /**
     * Get payment by ID with additional booking and user information
     * 
     * @param int $paymentId Payment ID
     * @return array|null Payment data or null if not found
     */
    public function getPaymentById($paymentId) {
        $query = "
            SELECT p.*, b.booking_number, b.start_date, b.end_date, 
                   u.first_name, u.last_name, u.email,
                   v.make, v.model, v.license_plate
            FROM {$this->table} p
            JOIN bookings b ON p.booking_id = b.booking_id
            JOIN users u ON b.user_id = u.user_id
            JOIN vehicles v ON b.vehicle_id = v.vehicle_id
            WHERE p.{$this->primaryKey} = :payment_id
            LIMIT 1
        ";
        
        $params = [':payment_id' => $paymentId];
        $result = $this->db->executeQuery($query, $params);
        
        return $result && count($result) > 0 ? $result[0] : null;
    }
    
    /**
     * Get all payments with filters
     * 
     * @param array $filters Filters for status, dates, booking_id, user_id
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array Payments
     */
    public function getPayments($filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $query = "
            SELECT p.*, b.booking_number, b.start_date, b.end_date,
                   u.first_name, u.last_name, u.email,
                   v.make, v.model, v.license_plate
            FROM {$this->table} p
            JOIN bookings b ON p.booking_id = b.booking_id
            JOIN users u ON b.user_id = u.user_id
            JOIN vehicles v ON b.vehicle_id = v.vehicle_id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND p.payment_status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['from_date'])) {
            $query .= " AND p.created_at >= :from_date";
            $params[':from_date'] = $filters['from_date'];
        }
        
        if (!empty($filters['to_date'])) {
            $query .= " AND p.created_at <= :to_date";
            $params[':to_date'] = $filters['to_date'];
        }
        
        if (!empty($filters['booking_id'])) {
            $query .= " AND p.booking_id = :booking_id";
            $params[':booking_id'] = $filters['booking_id'];
        }
        
        if (!empty($filters['user_id'])) {
            $query .= " AND b.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        // Order by created_at date descending
        $query .= " ORDER BY p.created_at DESC";
        
        // Add pagination
        $query .= " LIMIT {$limit} OFFSET {$offset}";
        
        return $this->db->executeQuery($query, $params);
    }
    
    /**
     * Update a payment
     * 
     * @param int $paymentId Payment ID
     * @param array $data Payment data
     * @return int|bool Number of affected rows or false on failure
     */
    public function updatePayment($paymentId, $data) {
        return $this->update($paymentId, $data);
    }
    
    /**
     * Update payment status
     * 
     * @param int $paymentId Payment ID
     * @param string $status New status
     * @param string|null $note Optional note
     * @return int|bool Number of affected rows or false on failure
     */
    public function updatePaymentStatus($paymentId, $status, $note = null) {
        $data = ['payment_status' => $status];
        
        if ($note) {
            $data['notes'] = $note;
        }
        
        return $this->updateStatus($paymentId, $status);
    }
    
    /**
     * Get payments for a booking
     * 
     * @param int $bookingId Booking ID
     * @return array Payments for the booking
     */
    public function getBookingPayments($bookingId) {
        return $this->findBy('booking_id', $bookingId);
    }
    
    /**
     * Get total revenue
     * 
     * @param string|null $startDate Start date (YYYY-MM-DD)
     * @param string|null $endDate End date (YYYY-MM-DD)
     * @return float Total revenue
     */
    public function getTotalRevenue($startDate = null, $endDate = null) {
        $query = "
            SELECT SUM(total_amount) AS total_revenue
            FROM {$this->table}
            WHERE payment_status = 'completed'
        ";
        
        $params = [];
        
        if ($startDate && $endDate) {
            $query .= " AND created_at BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $startDate . ' 00:00:00';
            $params[':end_date'] = $endDate . ' 23:59:59';
        }
        
        $result = $this->db->executeQuery($query, $params);
        
        return $result[0]['total_revenue'] ?? 0;
    }
}
