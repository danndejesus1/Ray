/**
 * CarGo API Endpoints for Database Access
 * 
 * This module provides the API endpoints that will connect to the database.
 * It extends the core API module with database-specific operations.
 */

import { CONFIG } from '../../config/app.config.js';
import { DB } from '../database/database.js';
import { validateBookingData, validatePaymentData, validateVehicleFilters } from '../utils/validation.js';

/**
 * Database API Class
 * Extends the core API functionality with database operations
 */
class DatabaseAPI {
    /**
     * Initialize the Database API
     */
    constructor() {
        this.baseUrl = CONFIG.API.BASE_URL;
        this.isInitialized = false;
    }
    
    /**
     * Initialize the database connection
     * @returns {Promise<boolean>} True if initialization is successful
     */
    async initialize() {
        if (this.isInitialized) {
            return true;
        }
        
        try {
            // Initialize database connection
            this.isInitialized = await DB.initializeConnection();
            return this.isInitialized;
        } catch (error) {
            console.error('Failed to initialize Database API:', error);
            return false;
        }
    }
    
    /**
     * User Authentication
     * @param {string} email - User email
     * @param {string} password - User password
     * @returns {Promise<Object>} Authentication result
     */
    async authenticateUser(email, password) {
        await this.initialize();
        
        try {
            // Get user by email
            const user = await DB.getUserByEmail(email);
            
            if (!user) {
                return {
                    success: false,
                    message: 'User not found'
                };
            }
            
            // In a real implementation, verify password hash
            // For demo, assume password is correct
            
            return {
                success: true,
                user: {
                    id: user.user_id,
                    email: user.email,
                    firstName: user.first_name,
                    lastName: user.last_name,
                    role: user.role_name
                }
            };
        } catch (error) {
            console.error('Authentication error:', error);
            return {
                success: false,
                message: 'Authentication failed'
            };
        }
    }
    
    /**
     * Get vehicles with optional filters
     * @param {Object} filters - Filter criteria
     * @returns {Promise<Object>} List of vehicles
     */
    async getVehicles(filters = {}) {
        await this.initialize();
        
        try {
            // Validate filters
            const validatedFilters = validateVehicleFilters(filters);
            
            // Get vehicles from database
            const vehicles = await DB.getAvailableVehicles(validatedFilters);
            
            return {
                success: true,
                data: vehicles
            };
        } catch (error) {
            console.error('Error getting vehicles:', error);
            return {
                success: false,
                message: 'Failed to retrieve vehicles'
            };
        }
    }
    
    /**
     * Get vehicle details by ID
     * @param {number} vehicleId - Vehicle ID
     * @returns {Promise<Object>} Vehicle details
     */
    async getVehicleDetails(vehicleId) {
        await this.initialize();
        
        try {
            // Get vehicle details from database
            const vehicle = await DB.getVehicleById(vehicleId);
            
            if (!vehicle) {
                return {
                    success: false,
                    message: 'Vehicle not found'
                };
            }
            
            return {
                success: true,
                data: vehicle
            };
        } catch (error) {
            console.error('Error getting vehicle details:', error);
            return {
                success: false,
                message: 'Failed to retrieve vehicle details'
            };
        }
    }
    
    /**
     * Create a new booking
     * @param {Object} bookingData - Booking data
     * @returns {Promise<Object>} Created booking
     */
    async createBooking(bookingData) {
        await this.initialize();
        
        try {
            // Validate booking data
            const validatedData = validateBookingData(bookingData);
            
            if (!validatedData.isValid) {
                return {
                    success: false,
                    message: validatedData.message
                };
            }
            
            // Check vehicle availability
            const vehicle = await DB.getVehicleById(bookingData.vehicle_id);
            
            if (!vehicle || !vehicle.is_available) {
                return {
                    success: false,
                    message: 'Vehicle is not available for booking'
                };
            }
            
            // Create booking in database
            const booking = await DB.createBooking(bookingData);
            
            return {
                success: true,
                data: booking
            };
        } catch (error) {
            console.error('Error creating booking:', error);
            return {
                success: false,
                message: 'Failed to create booking'
            };
        }
    }
    
    /**
     * Get user bookings
     * @param {number} userId - User ID
     * @param {string} status - Optional booking status filter
     * @returns {Promise<Object>} List of bookings
     */
    async getUserBookings(userId, status = null) {
        await this.initialize();
        
        try {
            // Get bookings from database
            const bookings = await DB.getUserBookings(userId, status);
            
            return {
                success: true,
                data: bookings
            };
        } catch (error) {
            console.error('Error getting user bookings:', error);
            return {
                success: false,
                message: 'Failed to retrieve bookings'
            };
        }
    }
    
    /**
     * Process payment for booking
     * @param {Object} paymentData - Payment data
     * @returns {Promise<Object>} Payment result
     */
    async processPayment(paymentData) {
        await this.initialize();
        
        try {
            // Validate payment data
            const validatedData = validatePaymentData(paymentData);
            
            if (!validatedData.isValid) {
                return {
                    success: false,
                    message: validatedData.message
                };
            }
            
            // Create payment record in database
            const payment = await DB.createPayment(paymentData);
            
            // In a real implementation, integrate with payment gateway
            // For demo, simulate successful payment
            const transactionReference = `TX-${Date.now().toString().slice(-10)}`;
            
            // Update payment status
            await DB.updatePaymentStatus(payment.payment_id, 'completed', transactionReference);
            
            // Create notification for user
            await DB.createNotification({
                user_id: paymentData.user_id,
                title: 'Payment Successful',
                message: `Your payment of ${CONFIG.LOCATION.CURRENCY_SYMBOL}${paymentData.total_amount} has been processed successfully.`,
                type: 'payment',
                related_id: payment.payment_id
            });
            
            return {
                success: true,
                data: {
                    ...payment,
                    payment_status: 'completed',
                    transaction_reference: transactionReference
                }
            };
        } catch (error) {
            console.error('Error processing payment:', error);
            return {
                success: false,
                message: 'Failed to process payment'
            };
        }
    }
    
    /**
     * Get analytics data
     * @param {string} startDate - Start date (YYYY-MM-DD)
     * @param {string} endDate - End date (YYYY-MM-DD)
     * @returns {Promise<Object>} Analytics data
     */
    async getAnalyticsData(startDate, endDate) {
        await this.initialize();
        
        try {
            // Get analytics data from database
            const analyticsData = await DB.getAnalyticsData(startDate, endDate);
            
            return {
                success: true,
                data: analyticsData
            };
        } catch (error) {
            console.error('Error getting analytics data:', error);
            return {
                success: false,
                message: 'Failed to retrieve analytics data'
            };
        }
    }
    
    /**
     * Create user notification
     * @param {Object} notificationData - Notification data
     * @returns {Promise<Object>} Created notification
     */
    async createNotification(notificationData) {
        await this.initialize();
        
        try {
            // Create notification in database
            const notification = await DB.createNotification(notificationData);
            
            return {
                success: true,
                data: notification
            };
        } catch (error) {
            console.error('Error creating notification:', error);
            return {
                success: false,
                message: 'Failed to create notification'
            };
        }
    }
    
    /**
     * Get user notifications
     * @param {number} userId - User ID
     * @param {boolean} unreadOnly - Get only unread notifications
     * @returns {Promise<Object>} List of notifications
     */
    async getUserNotifications(userId, unreadOnly = false) {
        await this.initialize();
        
        try {
            // Simulate getting notifications from database
            const query = `
                SELECT * FROM notifications
                WHERE user_id = ?
                ${unreadOnly ? 'AND is_read = 0' : ''}
                ORDER BY created_at DESC
            `;
            
            const result = await DB.executeQuery(query, [userId]);
            
            return {
                success: true,
                data: result.rows || []
            };
        } catch (error) {
            console.error('Error getting user notifications:', error);
            return {
                success: false,
                message: 'Failed to retrieve notifications'
            };
        }
    }
}

// Export singleton instance
export const dbAPI = new DatabaseAPI();
