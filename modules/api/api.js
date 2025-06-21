/**
 * CarGo API Module
 * Handles all API communications and data management
 */

class APIManager {
    constructor() {
        this.baseURL = CONFIG.API.BASE_URL;
        this.timeout = CONFIG.API.TIMEOUT;
        this.retryAttempts = CONFIG.API.RETRY_ATTEMPTS;
        this.authToken = null;
        this.init();
    }

    init() {
        this.setupInterceptors();
        this.loadAuthToken();
    }

    /**
     * Load authentication token from storage
     */
    loadAuthToken() {
        this.authToken = localStorage.getItem('cargo_token');
    }

    /**
     * Set authentication token
     * @param {string} token - Authentication token
     */
    setAuthToken(token) {
        this.authToken = token;
        localStorage.setItem('cargo_token', token);
    }

    /**
     * Clear authentication token
     */
    clearAuthToken() {
        this.authToken = null;
        localStorage.removeItem('cargo_token');
    }

    /**
     * Setup request/response interceptors
     */
    setupInterceptors() {
        // Add request interceptor for debugging
        if (CONFIG.DEBUG.API_LOGS) {
            console.log('API Manager initialized with base URL:', this.baseURL);
        }
    }

    /**
     * Make HTTP request with retry logic
     * @param {string} endpoint - API endpoint
     * @param {Object} options - Request options
     * @returns {Promise<Object>} Response data
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                ...this.getAuthHeaders(),
                ...options.headers
            },
            timeout: this.timeout,
            ...options
        };

        let lastError;
        
        for (let attempt = 1; attempt <= this.retryAttempts; attempt++) {
            try {
                if (CONFIG.DEBUG.API_LOGS) {
                    console.log(`API Request (Attempt ${attempt}):`, { url, options: defaultOptions });
                }

                const response = await this.fetchWithTimeout(url, defaultOptions);
                const data = await this.handleResponse(response);

                if (CONFIG.DEBUG.API_LOGS) {
                    console.log('API Response:', data);
                }

                return data;
            } catch (error) {
                lastError = error;
                
                if (CONFIG.DEBUG.API_LOGS) {
                    console.error(`API Request failed (Attempt ${attempt}):`, error);
                }

                // Don't retry on authentication errors or client errors
                if (error.status && error.status >= 400 && error.status < 500) {
                    break;
                }

                // Wait before retry (exponential backoff)
                if (attempt < this.retryAttempts) {
                    await this.delay(Math.pow(2, attempt) * 1000);
                }
            }
        }

        throw lastError;
    }

    /**
     * Fetch with timeout support
     * @param {string} url - Request URL
     * @param {Object} options - Fetch options
     * @returns {Promise<Response>} Fetch response
     */
    async fetchWithTimeout(url, options) {
        const timeoutId = setTimeout(() => {
            throw new Error('Request timeout');
        }, this.timeout);

        try {
            const response = await fetch(url, options);
            clearTimeout(timeoutId);
            return response;
        } catch (error) {
            clearTimeout(timeoutId);
            throw error;
        }
    }

    /**
     * Handle API response
     * @param {Response} response - Fetch response
     * @returns {Promise<Object>} Parsed response data
     */
    async handleResponse(response) {
        const contentType = response.headers.get('content-type');
        
        if (!response.ok) {
            let errorMessage = `HTTP error! status: ${response.status}`;
            
            if (contentType && contentType.includes('application/json')) {
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorMessage;
                } catch (e) {
                    // Ignore JSON parsing errors for error responses
                }
            }
            
            const error = new Error(errorMessage);
            error.status = response.status;
            error.response = response;
            
            // Handle authentication errors
            if (response.status === 401) {
                this.handleAuthError();
            }
            
            throw error;
        }

        if (contentType && contentType.includes('application/json')) {
            return await response.json();
        }
        
        return { success: true, data: await response.text() };
    }

    /**
     * Handle authentication errors
     */
    handleAuthError() {
        this.clearAuthToken();
        
        // Emit authentication error event
        window.dispatchEvent(new CustomEvent('authError', {
            detail: { message: 'Authentication failed. Please login again.' }
        }));
    }

    /**
     * Get authentication headers
     * @returns {Object} Auth headers
     */
    getAuthHeaders() {
        if (this.authToken) {
            return { 'Authorization': `Bearer ${this.authToken}` };
        }
        return {};
    }

    /**
     * Delay execution
     * @param {number} ms - Milliseconds to delay
     * @returns {Promise} Delay promise
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // HTTP Methods

    /**
     * GET request
     * @param {string} endpoint - API endpoint
     * @param {Object} params - Query parameters
     * @param {Object} options - Additional options
     * @returns {Promise<Object>} Response data
     */
    async get(endpoint, params = {}, options = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        
        return this.request(url, {
            method: 'GET',
            ...options
        });
    }

    /**
     * POST request
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request body data
     * @param {Object} options - Additional options
     * @returns {Promise<Object>} Response data
     */
    async post(endpoint, data = {}, options = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data),
            ...options
        });
    }

    /**
     * PUT request
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request body data
     * @param {Object} options - Additional options
     * @returns {Promise<Object>} Response data
     */
    async put(endpoint, data = {}, options = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data),
            ...options
        });
    }

    /**
     * DELETE request
     * @param {string} endpoint - API endpoint
     * @param {Object} options - Additional options
     * @returns {Promise<Object>} Response data
     */
    async delete(endpoint, options = {}) {
        return this.request(endpoint, {
            method: 'DELETE',
            ...options
        });
    }

    /**
     * PATCH request
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request body data
     * @param {Object} options - Additional options
     * @returns {Promise<Object>} Response data
     */
    async patch(endpoint, data = {}, options = {}) {
        return this.request(endpoint, {
            method: 'PATCH',
            body: JSON.stringify(data),
            ...options
        });
    }

    // File upload
    /**
     * Upload file
     * @param {string} endpoint - API endpoint
     * @param {FormData} formData - Form data with file
     * @param {Object} options - Additional options
     * @returns {Promise<Object>} Response data
     */
    async uploadFile(endpoint, formData, options = {}) {
        // Remove Content-Type header for file upload (let browser set it)
        const uploadOptions = {
            method: 'POST',
            body: formData,
            headers: {
                ...this.getAuthHeaders(),
                ...options.headers
            },
            ...options
        };

        delete uploadOptions.headers['Content-Type'];

        return this.request(endpoint, uploadOptions);
    }

    // API Health Check
    /**
     * Check API health
     * @returns {Promise<Object>} Health status
     */
    async healthCheck() {
        try {
            return await this.get('/health');
        } catch (error) {
            return {
                success: false,
                status: 'unhealthy',
                message: error.message
            };
        }
    }

    // Cache Management
    /**
     * Clear API cache (if implemented)
     */
    clearCache() {
        // Implementation for cache clearing
        if ('caches' in window) {
            caches.delete('cargo-api-cache');
        }
    }

    // Batch Requests
    /**
     * Execute multiple requests in batch
     * @param {Array} requests - Array of request configurations
     * @returns {Promise<Array>} Array of responses
     */
    async batch(requests) {
        const promises = requests.map(request => {
            const { endpoint, method = 'GET', data, options = {} } = request;
            
            switch (method.toLowerCase()) {
                case 'get':
                    return this.get(endpoint, data, options);
                case 'post':
                    return this.post(endpoint, data, options);
                case 'put':
                    return this.put(endpoint, data, options);
                case 'delete':
                    return this.delete(endpoint, options);
                case 'patch':
                    return this.patch(endpoint, data, options);
                default:
                    return Promise.reject(new Error(`Unsupported method: ${method}`));
            }
        });

        return Promise.allSettled(promises);
    }

    // Mock API responses for development
    /**
     * Get mock response for development
     * @param {string} endpoint - API endpoint
     * @param {string} method - HTTP method
     * @returns {Object} Mock response
     */
    getMockResponse(endpoint, method = 'GET') {
        const mockResponses = {
            '/auth/login': {
                success: true,
                data: {
                    user: { id: 1, name: 'Test User', email: 'test@example.com', role: 'user' },
                    token: 'mock-jwt-token',
                    refreshToken: 'mock-refresh-token'
                }
            },
            '/auth/register': {
                success: true,
                message: 'Registration successful'
            },
            '/vehicles': {
                success: true,
                data: [
                    {
                        id: '1',
                        name: 'Toyota Vios',
                        brand: 'Toyota',
                        type: 'Sedan',
                        fuelType: 'Petrol',
                        capacity: 5,
                        pricePerDay: 1500,
                        image: 'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d',
                        available: true
                    }
                ]
            },
            '/bookings': {
                success: true,
                data: {
                    id: 'BK001',
                    vehicleId: '1',
                    userId: '1',
                    pickupDate: '2025-06-21',
                    returnDate: '2025-06-23',
                    status: 'pending',
                    total: 4500
                }
            },
            '/analytics/dashboard': {
                success: true,
                data: {
                    totalBookings: { weekly: 24, monthly: 98, yearly: 1247 },
                    activeUsers: 156,
                    availableVehicles: 45,
                    revenue: { quarterly: 875000, annually: 3500000 }
                }
            }
        };

        return mockResponses[endpoint] || { success: false, message: 'Mock endpoint not found' };
    }

    /**
     * Check if should use mock responses
     * @returns {boolean} Whether to use mock responses
     */
    shouldUseMock() {
        return CONFIG.ENVIRONMENT === 'development' && !navigator.onLine;
    }
}

// Create global API manager instance
const apiManager = new APIManager();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = APIManager;
} else {
    window.APIManager = apiManager;
}

// Setup global error handler for authentication errors
window.addEventListener('authError', (event) => {
    console.warn('Authentication error:', event.detail.message);
    
    // Redirect to login if not already there
    if (!window.location.pathname.includes('index.html')) {
        window.location.href = '/index.html';
    }
});
