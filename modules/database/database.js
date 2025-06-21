/**
 * CarGo Database Utility Module
 * 
 * This module provides functions for interacting with the CarGo database.
 * It will be used by the API and other modules to perform database operations.
 */

// Import CONFIG from app.config.js
import { CONFIG } from '../config/app.config.js';
import { logError, logInfo } from '../modules/utils/utils.js';

/**
 * Database Utility Class
 * In production, this would use a proper SQL driver
 * For development/demo, we're providing the interface that would be implemented
 */
class DatabaseUtils {
    /**
     * Initialize the database connection
     * @returns {Promise<boolean>} True if connection is successful
     */
    async initializeConnection() {
        try {
            // In production, this would establish a real database connection
            logInfo('Database connection initialized');
            return true;
        } catch (error) {
            logError('Failed to initialize database connection', error);
            return false;
        }
    }

    /**
     * Execute a query against the database
     * @param {string} query - SQL query to execute
     * @param {Array} params - Parameters for the query
     * @returns {Promise<Object>} Query result
     */
    async executeQuery(query, params = []) {
        try {
            // In production, this would execute an actual SQL query
            logInfo(`Executing query: ${query}`);
            
            // Mock successful query execution
            return {
                success: true,
                rows: [],
                affectedRows: 0
            };
        } catch (error) {
            logError('Query execution failed', error);
            throw new Error(`Database query failed: ${error.message}`);
        }
    }

    /**
     * Get a user by ID
     * @param {number} userId - User ID
     * @returns {Promise<Object|null>} User object or null
     */
    async getUserById(userId) {
        const query = `
            SELECT u.*, r.role_name
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.user_id = ?
        `;
        
        const result = await this.executeQuery(query, [userId]);
        return result.rows.length > 0 ? result.rows[0] : null;
    }

    /**
     * Get a user by email
     * @param {string} email - User email
     * @returns {Promise<Object|null>} User object or null
     */
    async getUserByEmail(email) {
        const query = `
            SELECT u.*, r.role_name
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.email = ?
        `;
        
        const result = await this.executeQuery(query, [email]);
        return result.rows.length > 0 ? result.rows[0] : null;
    }

    /**
     * Create a new user
     * @param {Object} userData - User data
     * @returns {Promise<Object>} Created user with ID
     */
    async createUser(userData) {
        const { role_id, email, password_hash, first_name, last_name, phone } = userData;
        
        const query = `
            INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone)
            VALUES (?, ?, ?, ?, ?, ?)
        `;
        
        const result = await this.executeQuery(query, [
            role_id, email, password_hash, first_name, last_name, phone
        ]);
        
        if (result.success) {
            return {
                ...userData,
                user_id: result.insertId
            };
        }
        
        throw new Error('Failed to create user');
    }

    /**
     * Update user information
     * @param {number} userId - User ID
     * @param {Object} userData - User data to update
     * @returns {Promise<boolean>} True if update was successful
     */
    async updateUser(userId, userData) {
        const allowedFields = [
            'first_name', 'last_name', 'phone', 'address', 'city', 
            'country', 'profile_image_url', 'is_active'
        ];
        
        // Filter out fields that are not allowed to be updated
        const filteredData = Object.keys(userData)
            .filter(key => allowedFields.includes(key))
            .reduce((obj, key) => {
                obj[key] = userData[key];
                return obj;
            }, {});
        
        if (Object.keys(filteredData).length === 0) {
            return false;
        }
        
        // Build the query dynamically based on available fields
        const setClause = Object.keys(filteredData)
            .map(key => `${key} = ?`)
            .join(', ');
        
        const query = `
            UPDATE users
            SET ${setClause}, updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ?
        `;
        
        const params = [...Object.values(filteredData), userId];
        const result = await this.executeQuery(query, params);
        
        return result.success && result.affectedRows > 0;
    }

    /**
     * Get all available vehicles
     * @param {Object} filters - Filter criteria
     * @returns {Promise<Array>} Array of vehicle objects
     */
    async getAvailableVehicles(filters = {}) {
        let query = `
            SELECT v.*, 
                   (SELECT image_url FROM vehicle_images 
                    WHERE vehicle_id = v.vehicle_id AND is_primary = 1 LIMIT 1) AS primary_image
            FROM vehicles v
            WHERE v.is_available = 1
        `;
        
        const params = [];
        
        // Add filters if provided
        if (filters.vehicle_type) {
            query += ` AND v.vehicle_type = ?`;
            params.push(filters.vehicle_type);
        }
        
        if (filters.make) {
            query += ` AND v.make = ?`;
            params.push(filters.make);
        }
        
        if (filters.fuel_type) {
            query += ` AND v.fuel_type = ?`;
            params.push(filters.fuel_type);
        }
        
        if (filters.capacity) {
            query += ` AND v.capacity >= ?`;
            params.push(filters.capacity);
        }
        
        if (filters.max_daily_rate) {
            query += ` AND v.daily_rate <= ?`;
            params.push(filters.max_daily_rate);
        }
        
        query += ` ORDER BY v.daily_rate ASC`;
        
        const result = await this.executeQuery(query, params);
        return result.rows;
    }

    /**
     * Get vehicle by ID
     * @param {number} vehicleId - Vehicle ID
     * @returns {Promise<Object|null>} Vehicle object or null
     */
    async getVehicleById(vehicleId) {
        const query = `
            SELECT v.*
            FROM vehicles v
            WHERE v.vehicle_id = ?
        `;
        
        const result = await this.executeQuery(query, [vehicleId]);
        
        if (result.rows.length === 0) {
            return null;
        }
        
        const vehicle = result.rows[0];
        
        // Get vehicle images
        const imagesQuery = `
            SELECT image_id, image_url, is_primary
            FROM vehicle_images
            WHERE vehicle_id = ?
            ORDER BY is_primary DESC
        `;
        
        const imagesResult = await this.executeQuery(imagesQuery, [vehicleId]);
        vehicle.images = imagesResult.rows;
        
        return vehicle;
    }

    /**
     * Create a new booking
     * @param {Object} bookingData - Booking data
     * @returns {Promise<Object>} Created booking with ID
     */
    async createBooking(bookingData) {
        const { 
            user_id, vehicle_id, start_date, end_date, 
            pickup_location, return_location, with_driver, total_amount 
        } = bookingData;
        
        // Generate a unique booking number
        const bookingNumber = `BK-${Date.now().toString().slice(-8)}`;
        
        const query = `
            INSERT INTO bookings (
                user_id, vehicle_id, booking_number, start_date, end_date,
                pickup_location, return_location, with_driver, total_amount, status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        `;
        
        const params = [
            user_id, vehicle_id, bookingNumber, start_date, end_date,
            pickup_location, return_location, with_driver, total_amount
        ];
        
        const result = await this.executeQuery(query, params);
        
        if (result.success) {
            return {
                ...bookingData,
                booking_id: result.insertId,
                booking_number: bookingNumber,
                status: 'pending',
                created_at: new Date().toISOString()
            };
        }
        
        throw new Error('Failed to create booking');
    }

    /**
     * Get bookings for a user
     * @param {number} userId - User ID
     * @param {string} status - Optional booking status filter
     * @returns {Promise<Array>} Array of booking objects
     */
    async getUserBookings(userId, status = null) {
        let query = `
            SELECT b.*, v.make, v.model, v.license_plate, v.vehicle_type,
                   (SELECT image_url FROM vehicle_images 
                    WHERE vehicle_id = v.vehicle_id AND is_primary = 1 LIMIT 1) AS vehicle_image
            FROM bookings b
            JOIN vehicles v ON b.vehicle_id = v.vehicle_id
            WHERE b.user_id = ?
        `;
        
        const params = [userId];
        
        if (status) {
            query += ` AND b.status = ?`;
            params.push(status);
        }
        
        query += ` ORDER BY b.start_date DESC`;
        
        const result = await this.executeQuery(query, params);
        return result.rows;
    }

    /**
     * Update booking status
     * @param {number} bookingId - Booking ID
     * @param {string} status - New status
     * @param {string} notes - Optional notes
     * @returns {Promise<boolean>} True if update was successful
     */
    async updateBookingStatus(bookingId, status, notes = null) {
        let query = `
            UPDATE bookings
            SET status = ?, updated_at = CURRENT_TIMESTAMP
        `;
        
        const params = [status];
        
        if (notes) {
            query += `, booking_notes = ?`;
            params.push(notes);
        }
        
        query += ` WHERE booking_id = ?`;
        params.push(bookingId);
        
        const result = await this.executeQuery(query, params);
        return result.success && result.affectedRows > 0;
    }

    /**
     * Create a payment record
     * @param {Object} paymentData - Payment data
     * @returns {Promise<Object>} Created payment with ID
     */
    async createPayment(paymentData) {
        const { 
            booking_id, amount, processing_fee, tax_amount, 
            total_amount, payment_method
        } = paymentData;
        
        // Generate a unique payment number
        const paymentNumber = `PY-${Date.now().toString().slice(-8)}`;
        
        const query = `
            INSERT INTO payments (
                booking_id, payment_number, amount, processing_fee, tax_amount,
                total_amount, payment_method, payment_status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        `;
        
        const params = [
            booking_id, paymentNumber, amount, processing_fee, tax_amount,
            total_amount, payment_method
        ];
        
        const result = await this.executeQuery(query, params);
        
        if (result.success) {
            return {
                ...paymentData,
                payment_id: result.insertId,
                payment_number: paymentNumber,
                payment_status: 'pending',
                created_at: new Date().toISOString()
            };
        }
        
        throw new Error('Failed to create payment');
    }

    /**
     * Update payment status
     * @param {number} paymentId - Payment ID
     * @param {string} status - New status
     * @param {string} transactionReference - Transaction reference from payment provider
     * @returns {Promise<boolean>} True if update was successful
     */
    async updatePaymentStatus(paymentId, status, transactionReference = null) {
        let query = `
            UPDATE payments
            SET payment_status = ?, updated_at = CURRENT_TIMESTAMP
        `;
        
        const params = [status];
        
        if (transactionReference) {
            query += `, transaction_reference = ?`;
            params.push(transactionReference);
        }
        
        query += ` WHERE payment_id = ?`;
        params.push(paymentId);
        
        const result = await this.executeQuery(query, params);
        
        if (result.success && result.affectedRows > 0) {
            // If payment is completed, update the booking status to confirmed
            if (status === 'completed') {
                const paymentQuery = `
                    SELECT booking_id FROM payments WHERE payment_id = ?
                `;
                const paymentResult = await this.executeQuery(paymentQuery, [paymentId]);
                
                if (paymentResult.rows.length > 0) {
                    const bookingId = paymentResult.rows[0].booking_id;
                    await this.updateBookingStatus(bookingId, 'confirmed', 'Payment completed');
                }
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Create notification
     * @param {Object} notificationData - Notification data
     * @returns {Promise<Object>} Created notification with ID
     */
    async createNotification(notificationData) {
        const { user_id, title, message, type, related_id } = notificationData;
        
        const query = `
            INSERT INTO notifications (user_id, title, message, type, related_id)
            VALUES (?, ?, ?, ?, ?)
        `;
        
        const params = [user_id, title, message, type, related_id || null];
        const result = await this.executeQuery(query, params);
        
        if (result.success) {
            return {
                ...notificationData,
                notification_id: result.insertId,
                is_read: false,
                created_at: new Date().toISOString()
            };
        }
        
        throw new Error('Failed to create notification');
    }

    /**
     * Get analytics data for a specific date range
     * @param {string} startDate - Start date (YYYY-MM-DD)
     * @param {string} endDate - End date (YYYY-MM-DD)
     * @returns {Promise<Object>} Analytics data
     */
    async getAnalyticsData(startDate, endDate) {
        // Booking stats
        const bookingStatsQuery = `
            SELECT 
                COUNT(*) AS total_bookings,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_bookings,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_bookings,
                SUM(total_amount) AS total_revenue
            FROM bookings
            WHERE created_at BETWEEN ? AND ?
        `;
        
        // Vehicle popularity
        const vehiclePopularityQuery = `
            SELECT 
                v.vehicle_type,
                COUNT(*) AS booking_count
            FROM bookings b
            JOIN vehicles v ON b.vehicle_id = v.vehicle_id
            WHERE b.created_at BETWEEN ? AND ?
            GROUP BY v.vehicle_type
            ORDER BY booking_count DESC
        `;
        
        // User registrations
        const userRegistrationsQuery = `
            SELECT 
                COUNT(*) AS new_users
            FROM users
            WHERE created_at BETWEEN ? AND ?
        `;
        
        // Execute all queries
        const [bookingStats, vehiclePopularity, userRegistrations] = await Promise.all([
            this.executeQuery(bookingStatsQuery, [startDate, endDate]),
            this.executeQuery(vehiclePopularityQuery, [startDate, endDate]),
            this.executeQuery(userRegistrationsQuery, [startDate, endDate])
        ]);
        
        return {
            bookingStats: bookingStats.rows[0] || {},
            vehiclePopularity: vehiclePopularity.rows || [],
            userRegistrations: userRegistrations.rows[0] || {}
        };
    }
}

// Export singleton instance
export const DB = new DatabaseUtils();
