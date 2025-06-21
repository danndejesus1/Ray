/**
 * CarGo Booking Management Module
 * Handles all booking-related functionality including creation, modification, and cancellation
 */

class BookingManager {
    constructor() {
        this.currentBooking = null;
        this.bookingHistory = [];
        this.availableVehicles = [];
        this.init();
    }

    init() {
        this.loadBookingHistory();
        this.bindEvents();
        this.initializeDatePickers();
    }

    /**
     * Create a new booking
     * @param {Object} bookingData - Booking information
     * @returns {Promise<Object>} Booking result
     */
    async createBooking(bookingData) {
        try {
            // Validate booking data
            const validation = this.validateBookingData(bookingData);
            if (!validation.isValid) {
                throw new Error(validation.message);
            }

            // Check vehicle availability
            const isAvailable = await this.checkVehicleAvailability(
                bookingData.vehicleId,
                bookingData.pickupDate,
                bookingData.returnDate
            );

            if (!isAvailable) {
                throw new Error('Vehicle is not available for the selected dates');
            }

            // Calculate total cost
            const totalCost = this.calculateTotalCost(bookingData);
            bookingData.totalCost = totalCost;

            // Create booking via API
            const response = await this.apiCall('/bookings', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${AuthManager.sessionToken}` },
                body: JSON.stringify(bookingData)
            });

            if (response.success) {
                this.currentBooking = response.data;
                this.addToBookingHistory(response.data);
                return { success: true, booking: response.data };
            } else {
                throw new Error(response.message || 'Booking creation failed');
            }
        } catch (error) {
            console.error('Booking creation error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Get user's booking history
     * @returns {Promise<Array>} List of bookings
     */
    async getBookingHistory() {
        try {
            const response = await this.apiCall('/bookings/history', {
                headers: { 'Authorization': `Bearer ${AuthManager.sessionToken}` }
            });

            if (response.success) {
                this.bookingHistory = response.data;
                return response.data;
            } else {
                throw new Error(response.message || 'Failed to fetch booking history');
            }
        } catch (error) {
            console.error('Booking history error:', error);
            return [];
        }
    }

    /**
     * Get active bookings
     * @returns {Promise<Array>} List of active bookings
     */
    async getActiveBookings() {
        try {
            const response = await this.apiCall('/bookings/active', {
                headers: { 'Authorization': `Bearer ${AuthManager.sessionToken}` }
            });

            if (response.success) {
                return response.data;
            } else {
                throw new Error(response.message || 'Failed to fetch active bookings');
            }
        } catch (error) {
            console.error('Active bookings error:', error);
            return [];
        }
    }

    /**
     * Cancel a booking
     * @param {string} bookingId - Booking ID to cancel
     * @returns {Promise<Object>} Cancellation result
     */
    async cancelBooking(bookingId) {
        try {
            const response = await this.apiCall(`/bookings/${bookingId}/cancel`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${AuthManager.sessionToken}` }
            });

            if (response.success) {
                // Update local booking history
                this.bookingHistory = this.bookingHistory.map(booking => 
                    booking.id === bookingId 
                        ? { ...booking, status: 'cancelled' }
                        : booking
                );
                this.saveBookingHistory();
                
                return { success: true, message: 'Booking cancelled successfully' };
            } else {
                throw new Error(response.message || 'Booking cancellation failed');
            }
        } catch (error) {
            console.error('Booking cancellation error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Modify an existing booking
     * @param {string} bookingId - Booking ID to modify
     * @param {Object} updateData - Updated booking data
     * @returns {Promise<Object>} Modification result
     */
    async modifyBooking(bookingId, updateData) {
        try {
            const response = await this.apiCall(`/bookings/${bookingId}`, {
                method: 'PUT',
                headers: { 'Authorization': `Bearer ${AuthManager.sessionToken}` },
                body: JSON.stringify(updateData)
            });

            if (response.success) {
                // Update local booking history
                this.bookingHistory = this.bookingHistory.map(booking => 
                    booking.id === bookingId 
                        ? { ...booking, ...response.data }
                        : booking
                );
                this.saveBookingHistory();
                
                return { success: true, booking: response.data };
            } else {
                throw new Error(response.message || 'Booking modification failed');
            }
        } catch (error) {
            console.error('Booking modification error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Check vehicle availability for given dates
     * @param {string} vehicleId - Vehicle ID
     * @param {string} pickupDate - Pickup date
     * @param {string} returnDate - Return date
     * @returns {Promise<boolean>} Availability status
     */
    async checkVehicleAvailability(vehicleId, pickupDate, returnDate) {
        try {
            const response = await this.apiCall('/vehicles/availability', {
                method: 'POST',
                body: JSON.stringify({ vehicleId, pickupDate, returnDate })
            });

            return response.success && response.data.available;
        } catch (error) {
            console.error('Availability check error:', error);
            return false;
        }
    }

    /**
     * Calculate total booking cost
     * @param {Object} bookingData - Booking data
     * @returns {number} Total cost
     */
    calculateTotalCost(bookingData) {
        const pickupDate = new Date(bookingData.pickupDate);
        const returnDate = new Date(bookingData.returnDate);
        const days = Math.ceil((returnDate - pickupDate) / (1000 * 60 * 60 * 24));
        
        let baseCost = bookingData.pricePerDay * days;
        
        // Add driver cost if applicable
        if (bookingData.withDriver) {
            baseCost += CONFIG.PRICING.DRIVER_COST_PER_DAY * days;
        }
        
        // Add insurance if selected
        if (bookingData.insurance) {
            baseCost += CONFIG.PRICING.INSURANCE_COST_PER_DAY * days;
        }
        
        // Calculate tax
        const tax = baseCost * CONFIG.PAYMENT.TAX_RATE;
        
        // Calculate processing fee
        const processingFee = baseCost * CONFIG.PAYMENT.PROCESSING_FEE;
        
        return baseCost + tax + processingFee;
    }

    /**
     * Validate booking data
     * @param {Object} bookingData - Booking data to validate
     * @returns {Object} Validation result
     */
    validateBookingData(bookingData) {
        const errors = [];

        // Check required fields
        if (!bookingData.vehicleId) errors.push('Vehicle ID is required');
        if (!bookingData.pickupDate) errors.push('Pickup date is required');
        if (!bookingData.returnDate) errors.push('Return date is required');

        // Validate dates
        const pickupDate = new Date(bookingData.pickupDate);
        const returnDate = new Date(bookingData.returnDate);
        const today = new Date();

        if (pickupDate < today) {
            errors.push('Pickup date cannot be in the past');
        }

        if (returnDate <= pickupDate) {
            errors.push('Return date must be after pickup date');
        }

        const rentalDays = Math.ceil((returnDate - pickupDate) / (1000 * 60 * 60 * 24));
        
        if (rentalDays > CONFIG.BOOKING.MAX_RENTAL_DAYS) {
            errors.push(`Maximum rental period is ${CONFIG.BOOKING.MAX_RENTAL_DAYS} days`);
        }

        if (rentalDays < CONFIG.BOOKING.MIN_RENTAL_DAYS) {
            errors.push(`Minimum rental period is ${CONFIG.BOOKING.MIN_RENTAL_DAYS} day(s)`);
        }

        return {
            isValid: errors.length === 0,
            message: errors.join(', ')
        };
    }

    /**
     * Show booking modal with vehicle details
     * @param {Object} vehicle - Vehicle object
     */
    showBookingModal(vehicle) {
        const modal = document.getElementById('booking-modal');
        const carImage = document.getElementById('booking-car-image');
        const carName = document.getElementById('booking-car-name');
        const carPrice = document.getElementById('booking-car-price');
        const carIdInput = document.getElementById('booking-car-id');

        if (modal && carImage && carName && carPrice && carIdInput) {
            carImage.src = vehicle.image;
            carImage.alt = vehicle.name;
            carName.textContent = vehicle.name;
            carPrice.textContent = `${CONFIG.LOCATION.CURRENCY_SYMBOL}${vehicle.pricePerDay}/day`;
            carIdInput.value = vehicle.id;
            
            modal.classList.remove('hidden');
            
            // Reset form
            const form = document.getElementById('booking-form');
            if (form) form.reset();
        }
    }

    /**
     * Hide booking modal
     */
    hideBookingModal() {
        const modal = document.getElementById('booking-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    /**
     * Initialize date pickers
     */
    initializeDatePickers() {
        const pickupDateInput = document.getElementById('pickup-date');
        const returnDateInput = document.getElementById('return-date');

        if (pickupDateInput && returnDateInput && typeof flatpickr !== 'undefined') {
            // Pickup date picker
            flatpickr(pickupDateInput, {
                dateFormat: "Y-m-d",
                minDate: "today",
                maxDate: new Date().fp_incr(CONFIG.BOOKING.ADVANCE_BOOKING_DAYS),
                onChange: (selectedDates, dateStr) => {
                    // Update return date minimum
                    const returnPicker = returnDateInput._flatpickr;
                    if (returnPicker) {
                        returnPicker.set('minDate', new Date(selectedDates[0]).fp_incr(1));
                    }
                    this.updateTotalPrice();
                }
            });

            // Return date picker
            flatpickr(returnDateInput, {
                dateFormat: "Y-m-d",
                minDate: new Date().fp_incr(1),
                maxDate: new Date().fp_incr(CONFIG.BOOKING.ADVANCE_BOOKING_DAYS),
                onChange: () => {
                    this.updateTotalPrice();
                }
            });
        }
    }

    /**
     * Update total price in booking modal
     */
    updateTotalPrice() {
        const pickupDate = document.getElementById('pickup-date')?.value;
        const returnDate = document.getElementById('return-date')?.value;
        const carIdInput = document.getElementById('booking-car-id');
        const totalPriceInput = document.getElementById('total-price');

        if (pickupDate && returnDate && carIdInput && totalPriceInput) {
            const vehicleId = carIdInput.value;
            const vehicle = this.getVehicleById(vehicleId);
            
            if (vehicle) {
                const bookingData = {
                    vehicleId,
                    pickupDate,
                    returnDate,
                    pricePerDay: vehicle.pricePerDay,
                    withDriver: false, // Get from form if applicable
                    insurance: false   // Get from form if applicable
                };
                
                const totalCost = this.calculateTotalCost(bookingData);
                totalPriceInput.value = `${CONFIG.LOCATION.CURRENCY_SYMBOL}${totalCost.toFixed(2)}`;
            }
        }
    }

    /**
     * Get vehicle by ID (placeholder - should come from vehicle manager)
     * @param {string} vehicleId - Vehicle ID
     * @returns {Object|null} Vehicle object
     */
    getVehicleById(vehicleId) {
        // This should be integrated with VehicleManager
        return this.availableVehicles.find(v => v.id === vehicleId) || null;
    }

    /**
     * Bind booking-related events
     */
    bindEvents() {
        // Booking form submission
        const bookingForm = document.getElementById('booking-form');
        if (bookingForm) {
            bookingForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleBookingSubmission(e.target);
            });
        }

        // Close booking modal
        const closeBookingModal = document.getElementById('close-booking-modal');
        if (closeBookingModal) {
            closeBookingModal.addEventListener('click', () => {
                this.hideBookingModal();
            });
        }

        // Book now buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('book-now-button') && !e.target.disabled) {
                const vehicleCard = e.target.closest('.vehicle-card');
                if (vehicleCard) {
                    const vehicleData = this.extractVehicleDataFromCard(vehicleCard);
                    this.showBookingModal(vehicleData);
                }
            }
        });
    }

    /**
     * Handle booking form submission
     * @param {HTMLFormElement} form - Booking form
     */
    async handleBookingSubmission(form) {
        const formData = new FormData(form);
        const bookingData = {
            vehicleId: formData.get('vehicleId') || document.getElementById('booking-car-id')?.value,
            pickupDate: formData.get('pickup_date'),
            returnDate: formData.get('return_date'),
            // Add other form fields as needed
        };

        // Get vehicle details for pricing
        const vehicle = this.getVehicleById(bookingData.vehicleId);
        if (vehicle) {
            bookingData.pricePerDay = vehicle.pricePerDay;
        }

        const result = await this.createBooking(bookingData);
        this.handleBookingResponse(result);
    }

    /**
     * Handle booking response
     * @param {Object} result - Booking result
     */
    handleBookingResponse(result) {
        const responseMessage = document.getElementById('booking-response-message');
        if (responseMessage) {
            responseMessage.textContent = result.message || (result.success ? 'Booking created successfully!' : 'Booking failed');
            responseMessage.classList.remove('hidden', 'text-green-600', 'text-red-600');
            responseMessage.classList.add(result.success ? 'text-green-600' : 'text-red-600');
            
            if (result.success) {
                setTimeout(() => {
                    this.hideBookingModal();
                    // Redirect to booking confirmation or user dashboard
                    window.location.href = '/pages/user/bookings.html';
                }, 2000);
            }
        }
    }

    /**
     * Extract vehicle data from vehicle card
     * @param {HTMLElement} card - Vehicle card element
     * @returns {Object} Vehicle data
     */
    extractVehicleDataFromCard(card) {
        // This is a placeholder implementation
        return {
            id: card.dataset.vehicleId || 'temp-id',
            name: card.querySelector('h3')?.textContent || 'Unknown Vehicle',
            image: card.querySelector('img')?.src || '',
            pricePerDay: parseInt(card.querySelector('.price')?.textContent.replace(/[^\d]/g, '')) || 0
        };
    }

    /**
     * Load booking history from localStorage
     */
    loadBookingHistory() {
        const stored = localStorage.getItem('cargo_booking_history');
        if (stored) {
            this.bookingHistory = JSON.parse(stored);
        }
    }

    /**
     * Save booking history to localStorage
     */
    saveBookingHistory() {
        localStorage.setItem('cargo_booking_history', JSON.stringify(this.bookingHistory));
    }

    /**
     * Add booking to history
     * @param {Object} booking - Booking object
     */
    addToBookingHistory(booking) {
        this.bookingHistory.unshift(booking);
        this.saveBookingHistory();
    }

    /**
     * Make authenticated API call
     * @param {string} endpoint - API endpoint
     * @param {Object} options - Fetch options
     * @returns {Promise<Object>} API response
     */
    async apiCall(endpoint, options = {}) {
        // Use the same API call method as AuthManager
        return AuthManager.apiCall(endpoint, options);
    }
}

// Initialize booking manager
const bookingManager = new BookingManager();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BookingManager;
} else {
    window.BookingManager = bookingManager;
}
