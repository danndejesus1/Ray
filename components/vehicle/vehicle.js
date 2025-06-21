/**
 * CarGo Vehicle Management Module
 * Handles vehicle listing, filtering, and management functionality
 */

class VehicleManager {
    constructor() {
        this.vehicles = [];
        this.filteredVehicles = [];
        this.currentFilters = {};
        this.init();
    }

    init() {
        this.loadVehicles();
        this.bindFilterEvents();
    }

    /**
     * Load vehicles from API or local storage
     * @returns {Promise<Array>} List of vehicles
     */
    async loadVehicles() {
        try {
            const response = await this.apiCall('/vehicles');
            
            if (response.success) {
                this.vehicles = response.data;
                this.filteredVehicles = [...this.vehicles];
                this.renderVehicles();
                return this.vehicles;
            } else {
                // Fallback to demo data if API fails
                this.loadDemoVehicles();
                return this.vehicles;
            }
        } catch (error) {
            console.error('Error loading vehicles:', error);
            this.loadDemoVehicles();
            return this.vehicles;
        }
    }

    /**
     * Load demo vehicles for development/testing
     */
    loadDemoVehicles() {
        this.vehicles = [
            {
                id: 'toyota-vios-001',
                name: 'Toyota Vios',
                brand: 'Toyota',
                type: 'Sedan',
                fuelType: 'Petrol',
                capacity: 5,
                pricePerDay: 1500,
                image: 'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d',
                features: ['Air Conditioning', 'Bluetooth', 'GPS'],
                available: true,
                location: 'Metro Manila',
                drivingOption: 'Self Drive',
                rating: 4.5,
                reviews: 128
            },
            {
                id: 'honda-crv-001',
                name: 'Honda CRV',
                brand: 'Honda',
                type: 'SUV',
                fuelType: 'Diesel',
                capacity: 7,
                pricePerDay: 2500,
                image: 'https://upload.wikimedia.org/wikipedia/commons/1/1b/Honda_CR-V_e-HEV_Elegance_AWD_%28VI%29_%E2%80%93_f_14072024.jpg',
                features: ['Air Conditioning', 'Bluetooth', 'GPS', '4WD'],
                available: true,
                location: 'Metro Manila',
                drivingOption: 'Self Drive',
                rating: 4.7,
                reviews: 89
            },
            {
                id: 'mitsubishi-adventure-001',
                name: 'Mitsubishi Adventure',
                brand: 'Mitsubishi',
                type: 'Van',
                fuelType: 'Diesel',
                capacity: 8,
                pricePerDay: 2000,
                image: 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b',
                features: ['Air Conditioning', 'GPS', 'Large Cargo'],
                available: true,
                location: 'Metro Manila',
                drivingOption: 'With Driver',
                rating: 4.3,
                reviews: 67
            }
        ];
        
        this.filteredVehicles = [...this.vehicles];
        this.renderVehicles();
    }

    /**
     * Apply filters to vehicle list
     * @param {Object} filters - Filter criteria
     */
    applyFilters(filters = {}) {
        this.currentFilters = { ...this.currentFilters, ...filters };
        
        this.filteredVehicles = this.vehicles.filter(vehicle => {
            // Brand filter
            if (this.currentFilters.brand && vehicle.brand !== this.currentFilters.brand) {
                return false;
            }
            
            // Type filter
            if (this.currentFilters.type && vehicle.type !== this.currentFilters.type) {
                return false;
            }
            
            // Fuel type filter
            if (this.currentFilters.fuelType && vehicle.fuelType !== this.currentFilters.fuelType) {
                return false;
            }
            
            // Capacity filter
            if (this.currentFilters.capacity && vehicle.capacity < parseInt(this.currentFilters.capacity)) {
                return false;
            }
            
            // Price range filter
            if (this.currentFilters.minPrice && vehicle.pricePerDay < parseInt(this.currentFilters.minPrice)) {
                return false;
            }
            
            if (this.currentFilters.maxPrice && vehicle.pricePerDay > parseInt(this.currentFilters.maxPrice)) {
                return false;
            }
            
            // Driving option filter
            if (this.currentFilters.drivingOption && vehicle.drivingOption !== this.currentFilters.drivingOption) {
                return false;
            }
            
            // Availability filter (check against dates if provided)
            if (this.currentFilters.pickupDate && this.currentFilters.returnDate) {
                // This would need to check against actual bookings
                // For now, assume all vehicles are available
                return vehicle.available;
            }
            
            return true;
        });
        
        this.renderVehicles();
    }

    /**
     * Clear all filters
     */
    clearFilters() {
        this.currentFilters = {};
        this.filteredVehicles = [...this.vehicles];
        this.renderVehicles();
        this.resetFilterForm();
    }

    /**
     * Search vehicles by name or features
     * @param {string} query - Search query
     */
    searchVehicles(query) {
        if (!query.trim()) {
            this.filteredVehicles = [...this.vehicles];
        } else {
            const searchTerm = query.toLowerCase();
            this.filteredVehicles = this.vehicles.filter(vehicle => 
                vehicle.name.toLowerCase().includes(searchTerm) ||
                vehicle.brand.toLowerCase().includes(searchTerm) ||
                vehicle.type.toLowerCase().includes(searchTerm) ||
                vehicle.features.some(feature => feature.toLowerCase().includes(searchTerm))
            );
        }
        
        this.renderVehicles();
    }

    /**
     * Get vehicle by ID
     * @param {string} vehicleId - Vehicle ID
     * @returns {Object|null} Vehicle object
     */
    getVehicleById(vehicleId) {
        return this.vehicles.find(vehicle => vehicle.id === vehicleId) || null;
    }

    /**
     * Render vehicles in the UI
     */
    renderVehicles() {
        const container = document.getElementById('vehicle-listings');
        if (!container) return;

        if (this.filteredVehicles.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">🚗</div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No vehicles found</h3>
                    <p class="text-gray-500">Try adjusting your filters or search criteria.</p>
                    <button onclick="vehicleManager.clearFilters()" class="btn btn-primary mt-4">
                        Clear Filters
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = this.filteredVehicles.map(vehicle => 
            this.createVehicleCard(vehicle)
        ).join('');
    }

    /**
     * Create HTML for a vehicle card
     * @param {Object} vehicle - Vehicle object
     * @returns {string} HTML string
     */
    createVehicleCard(vehicle) {
        const isAuthenticated = AuthManager && AuthManager.isAuthenticated();
        const bookButtonClass = isAuthenticated ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-400 text-white cursor-not-allowed';
        const bookButtonText = isAuthenticated ? 'Book Now' : 'Login to Book';
        const bookButtonDisabled = !isAuthenticated ? 'disabled' : '';

        return `
            <div class="vehicle-card bg-white rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:scale-105" data-vehicle-id="${vehicle.id}">
                <div class="relative">
                    <img src="${vehicle.image}" alt="${vehicle.name}" class="w-full h-48 object-cover">
                    <div class="absolute top-2 right-2 bg-white rounded-full px-2 py-1 text-sm font-semibold ${vehicle.available ? 'text-green-600' : 'text-red-600'}">
                        ${vehicle.available ? 'Available' : 'Booked'}
                    </div>
                    ${vehicle.rating ? `
                        <div class="absolute top-2 left-2 bg-black bg-opacity-70 text-white rounded px-2 py-1 text-sm">
                            ⭐ ${vehicle.rating} (${vehicle.reviews})
                        </div>
                    ` : ''}
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">${vehicle.name}</h3>
                    <div class="text-gray-600 text-sm mb-3 space-y-1">
                        <p>${vehicle.type} • ${vehicle.fuelType}</p>
                        <p>Seats: ${vehicle.capacity} • ${vehicle.drivingOption}</p>
                        <p>📍 ${vehicle.location}</p>
                    </div>
                    
                    ${vehicle.features && vehicle.features.length > 0 ? `
                        <div class="mb-4">
                            <div class="flex flex-wrap gap-1">
                                ${vehicle.features.slice(0, 3).map(feature => 
                                    `<span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">${feature}</span>`
                                ).join('')}
                                ${vehicle.features.length > 3 ? 
                                    `<span class="text-gray-500 text-xs">+${vehicle.features.length - 3} more</span>` : ''
                                }
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="flex justify-between items-center">
                        <div class="price-section">
                            <span class="text-2xl font-bold text-blue-600">${CONFIG.LOCATION.CURRENCY_SYMBOL}${vehicle.pricePerDay}</span>
                            <span class="text-gray-500 text-sm">/day</span>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="vehicleManager.showVehicleDetails('${vehicle.id}')" 
                                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition duration-300">
                                Details
                            </button>
                            <button onclick="vehicleManager.bookVehicle('${vehicle.id}')" 
                                    class="book-now-button ${bookButtonClass} px-5 py-2 rounded-md transition duration-300" 
                                    ${bookButtonDisabled}>
                                ${bookButtonText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Show vehicle details modal
     * @param {string} vehicleId - Vehicle ID
     */
    showVehicleDetails(vehicleId) {
        const vehicle = this.getVehicleById(vehicleId);
        if (!vehicle) return;

        // Create and show modal with vehicle details
        const modal = this.createVehicleDetailsModal(vehicle);
        document.body.appendChild(modal);
    }

    /**
     * Create vehicle details modal
     * @param {Object} vehicle - Vehicle object
     * @returns {HTMLElement} Modal element
     */
    createVehicleDetailsModal(vehicle) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="modal-content bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-90vh overflow-y-auto">
                <div class="flex justify-between items-center p-6 border-b">
                    <h2 class="text-2xl font-bold text-gray-800">${vehicle.name}</h2>
                    <button onclick="this.closest('.modal-overlay').remove()" 
                            class="text-gray-500 hover:text-gray-700 text-3xl font-bold">&times;</button>
                </div>
                <div class="p-6">
                    <img src="${vehicle.image}" alt="${vehicle.name}" class="w-full h-64 object-cover rounded-lg mb-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold mb-3">Vehicle Details</h3>
                            <div class="space-y-2 text-sm">
                                <p><strong>Brand:</strong> ${vehicle.brand}</p>
                                <p><strong>Type:</strong> ${vehicle.type}</p>
                                <p><strong>Fuel Type:</strong> ${vehicle.fuelType}</p>
                                <p><strong>Capacity:</strong> ${vehicle.capacity} passengers</p>
                                <p><strong>Driving Option:</strong> ${vehicle.drivingOption}</p>
                                <p><strong>Location:</strong> ${vehicle.location}</p>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold mb-3">Features</h3>
                            <div class="flex flex-wrap gap-2">
                                ${vehicle.features ? vehicle.features.map(feature => 
                                    `<span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">${feature}</span>`
                                ).join('') : 'No features listed'}
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t pt-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-3xl font-bold text-blue-600">${CONFIG.LOCATION.CURRENCY_SYMBOL}${vehicle.pricePerDay}</span>
                                <span class="text-gray-500">/day</span>
                            </div>
                            <button onclick="vehicleManager.bookVehicle('${vehicle.id}'); this.closest('.modal-overlay').remove();" 
                                    class="btn btn-primary">
                                Book This Vehicle
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });

        return modal;
    }

    /**
     * Book a vehicle
     * @param {string} vehicleId - Vehicle ID
     */
    bookVehicle(vehicleId) {
        const vehicle = this.getVehicleById(vehicleId);
        if (!vehicle) return;

        if (!AuthManager || !AuthManager.isAuthenticated()) {
            AuthManager.showAuthModal();
            return;
        }

        // Use BookingManager to show booking modal
        if (window.BookingManager) {
            BookingManager.showBookingModal(vehicle);
        } else {
            console.error('BookingManager not available');
        }
    }

    /**
     * Bind filter-related events
     */
    bindFilterEvents() {
        // Apply filters button
        const applyFilterButton = document.getElementById('apply-filter-button');
        if (applyFilterButton) {
            applyFilterButton.addEventListener('click', () => {
                this.handleFilterSubmission();
            });
        }

        // Search input
        const searchInput = document.getElementById('vehicle-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchVehicles(e.target.value);
            });
        }

        // Filter form inputs
        const filterInputs = document.querySelectorAll('#vehicle-filters select, #vehicle-filters input');
        filterInputs.forEach(input => {
            input.addEventListener('change', () => {
                if (input.dataset.autoFilter === 'true') {
                    this.handleFilterSubmission();
                }
            });
        });

        // Clear filters button
        const clearFiltersButton = document.getElementById('clear-filters-button');
        if (clearFiltersButton) {
            clearFiltersButton.addEventListener('click', () => {
                this.clearFilters();
            });
        }
    }

    /**
     * Handle filter form submission
     */
    handleFilterSubmission() {
        const filters = {};
        
        // Get filter values from form
        const makeFilter = document.getElementById('make-filter');
        const typeFilter = document.getElementById('car-type-filter');
        const fuelFilter = document.getElementById('gas-type-filter');
        const capacityFilter = document.getElementById('seating-capacity-filter');
        const pickupDateFilter = document.getElementById('filter-pickup-date');
        const returnDateFilter = document.getElementById('filter-return-date');

        if (makeFilter && makeFilter.value) filters.brand = makeFilter.value;
        if (typeFilter && typeFilter.value) filters.type = typeFilter.value;
        if (fuelFilter && fuelFilter.value) filters.fuelType = fuelFilter.value;
        if (capacityFilter && capacityFilter.value) filters.capacity = capacityFilter.value;
        if (pickupDateFilter && pickupDateFilter.value) filters.pickupDate = pickupDateFilter.value;
        if (returnDateFilter && returnDateFilter.value) filters.returnDate = returnDateFilter.value;

        this.applyFilters(filters);
    }

    /**
     * Reset filter form
     */
    resetFilterForm() {
        const filterForm = document.querySelector('#vehicle-filters form, .filter-form');
        if (filterForm) {
            filterForm.reset();
        }
    }

    /**
     * Add new vehicle (Admin function)
     * @param {Object} vehicleData - Vehicle data
     * @returns {Promise<Object>} Result
     */
    async addVehicle(vehicleData) {
        try {
            const response = await this.apiCall('/vehicles', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${AuthManager.sessionToken}` },
                body: JSON.stringify(vehicleData)
            });

            if (response.success) {
                this.vehicles.push(response.data);
                this.applyFilters(); // Refresh display
                return { success: true, vehicle: response.data };
            } else {
                throw new Error(response.message || 'Failed to add vehicle');
            }
        } catch (error) {
            console.error('Add vehicle error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Update vehicle (Admin function)
     * @param {string} vehicleId - Vehicle ID
     * @param {Object} updateData - Update data
     * @returns {Promise<Object>} Result
     */
    async updateVehicle(vehicleId, updateData) {
        try {
            const response = await this.apiCall(`/vehicles/${vehicleId}`, {
                method: 'PUT',
                headers: { 'Authorization': `Bearer ${AuthManager.sessionToken}` },
                body: JSON.stringify(updateData)
            });

            if (response.success) {
                const index = this.vehicles.findIndex(v => v.id === vehicleId);
                if (index !== -1) {
                    this.vehicles[index] = { ...this.vehicles[index], ...response.data };
                }
                this.applyFilters(); // Refresh display
                return { success: true, vehicle: response.data };
            } else {
                throw new Error(response.message || 'Failed to update vehicle');
            }
        } catch (error) {
            console.error('Update vehicle error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Delete vehicle (Admin function)
     * @param {string} vehicleId - Vehicle ID
     * @returns {Promise<Object>} Result
     */
    async deleteVehicle(vehicleId) {
        try {
            const response = await this.apiCall(`/vehicles/${vehicleId}`, {
                method: 'DELETE',
                headers: { 'Authorization': `Bearer ${AuthManager.sessionToken}` }
            });

            if (response.success) {
                this.vehicles = this.vehicles.filter(v => v.id !== vehicleId);
                this.applyFilters(); // Refresh display
                return { success: true };
            } else {
                throw new Error(response.message || 'Failed to delete vehicle');
            }
        } catch (error) {
            console.error('Delete vehicle error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Make API call
     * @param {string} endpoint - API endpoint
     * @param {Object} options - Fetch options
     * @returns {Promise<Object>} API response
     */
    async apiCall(endpoint, options = {}) {
        const url = `${CONFIG.API.BASE_URL}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        try {
            const response = await fetch(url, defaultOptions);
            return await response.json();
        } catch (error) {
            console.error('API call error:', error);
            // Return mock success for development
            return { success: false, message: error.message };
        }
    }
}

// Initialize vehicle manager
const vehicleManager = new VehicleManager();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VehicleManager;
} else {
    window.VehicleManager = vehicleManager;
}
