/**
 * CarGo Authentication Module
 * Handles user authentication, authorization, and session management
 */

class AuthenticationManager {
    constructor() {
        this.currentUser = null;
        this.sessionToken = null;
        this.refreshToken = null;
        this.sessionTimeout = null;
        this.init();
    }

    init() {
        this.loadStoredSession();
        this.setupSessionTimeout();
        this.bindEvents();
    }

    /**
     * User Login
     * @param {string} email - User email
     * @param {string} password - User password
     * @returns {Promise<Object>} Login result
     */
    async login(email, password) {
        try {
            const response = await this.apiCall('/auth/login', {
                method: 'POST',
                body: JSON.stringify({ email, password })
            });

            if (response.success) {
                await this.setSession(response.data);
                this.redirectBasedOnRole();
                return { success: true, user: this.currentUser };
            } else {
                throw new Error(response.message || 'Login failed');
            }
        } catch (error) {
            console.error('Login error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * User Registration
     * @param {Object} userData - User registration data
     * @returns {Promise<Object>} Registration result
     */
    async register(userData) {
        try {
            const response = await this.apiCall('/auth/register', {
                method: 'POST',
                body: JSON.stringify(userData)
            });

            if (response.success) {
                return { success: true, message: 'Registration successful. Please login.' };
            } else {
                throw new Error(response.message || 'Registration failed');
            }
        } catch (error) {
            console.error('Registration error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * User Logout
     */
    async logout() {
        try {
            if (this.sessionToken) {
                await this.apiCall('/auth/logout', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${this.sessionToken}` }
                });
            }
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.clearSession();
            window.location.href = '/index.html';
        }
    }

    /**
     * Check if user is authenticated
     * @returns {boolean} Authentication status
     */
    isAuthenticated() {
        return !!(this.currentUser && this.sessionToken);
    }

    /**
     * Check if user has specific role
     * @param {string} role - Role to check
     * @returns {boolean} Role check result
     */
    hasRole(role) {
        return this.currentUser && this.currentUser.role === role;
    }

    /**
     * Get current user information
     * @returns {Object|null} Current user data
     */
    getCurrentUser() {
        return this.currentUser;
    }

    /**
     * Update user profile
     * @param {Object} profileData - Updated profile data
     * @returns {Promise<Object>} Update result
     */
    async updateProfile(profileData) {
        try {
            const response = await this.apiCall('/auth/profile', {
                method: 'PUT',
                headers: { 'Authorization': `Bearer ${this.sessionToken}` },
                body: JSON.stringify(profileData)
            });

            if (response.success) {
                this.currentUser = { ...this.currentUser, ...response.data };
                this.saveToStorage();
                return { success: true, user: this.currentUser };
            } else {
                throw new Error(response.message || 'Profile update failed');
            }
        } catch (error) {
            console.error('Profile update error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Change user password
     * @param {string} currentPassword - Current password
     * @param {string} newPassword - New password
     * @returns {Promise<Object>} Change password result
     */
    async changePassword(currentPassword, newPassword) {
        try {
            const response = await this.apiCall('/auth/change-password', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${this.sessionToken}` },
                body: JSON.stringify({ currentPassword, newPassword })
            });

            if (response.success) {
                return { success: true, message: 'Password changed successfully' };
            } else {
                throw new Error(response.message || 'Password change failed');
            }
        } catch (error) {
            console.error('Password change error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Set user session
     * @param {Object} sessionData - Session data from login
     */
    async setSession(sessionData) {
        this.currentUser = sessionData.user;
        this.sessionToken = sessionData.token;
        this.refreshToken = sessionData.refreshToken;
        
        this.saveToStorage();
        this.setupSessionTimeout();
        
        // Update UI
        this.updateAuthUI();
    }

    /**
     * Clear user session
     */
    clearSession() {
        this.currentUser = null;
        this.sessionToken = null;
        this.refreshToken = null;
        
        localStorage.removeItem('cargo_user');
        localStorage.removeItem('cargo_token');
        localStorage.removeItem('cargo_refresh_token');
        
        if (this.sessionTimeout) {
            clearTimeout(this.sessionTimeout);
        }
        
        this.updateAuthUI();
    }

    /**
     * Load stored session from localStorage
     */
    loadStoredSession() {
        const storedUser = localStorage.getItem('cargo_user');
        const storedToken = localStorage.getItem('cargo_token');
        const storedRefreshToken = localStorage.getItem('cargo_refresh_token');

        if (storedUser && storedToken) {
            this.currentUser = JSON.parse(storedUser);
            this.sessionToken = storedToken;
            this.refreshToken = storedRefreshToken;
            this.updateAuthUI();
        }
    }

    /**
     * Save session to localStorage
     */
    saveToStorage() {
        if (this.currentUser && this.sessionToken) {
            localStorage.setItem('cargo_user', JSON.stringify(this.currentUser));
            localStorage.setItem('cargo_token', this.sessionToken);
            if (this.refreshToken) {
                localStorage.setItem('cargo_refresh_token', this.refreshToken);
            }
        }
    }

    /**
     * Setup session timeout
     */
    setupSessionTimeout() {
        if (this.sessionTimeout) {
            clearTimeout(this.sessionTimeout);
        }

        if (this.isAuthenticated()) {
            this.sessionTimeout = setTimeout(() => {
                this.handleSessionTimeout();
            }, CONFIG.SECURITY.SESSION_TIMEOUT);
        }
    }

    /**
     * Handle session timeout
     */
    handleSessionTimeout() {
        alert('Your session has expired. Please login again.');
        this.logout();
    }

    /**
     * Refresh authentication token
     */
    async refreshAuthToken() {
        try {
            if (!this.refreshToken) {
                throw new Error('No refresh token available');
            }

            const response = await this.apiCall('/auth/refresh', {
                method: 'POST',
                body: JSON.stringify({ refreshToken: this.refreshToken })
            });

            if (response.success) {
                this.sessionToken = response.data.token;
                this.saveToStorage();
                this.setupSessionTimeout();
                return true;
            } else {
                throw new Error('Token refresh failed');
            }
        } catch (error) {
            console.error('Token refresh error:', error);
            this.logout();
            return false;
        }
    }

    /**
     * Redirect user based on their role
     */
    redirectBasedOnRole() {
        if (!this.currentUser) return;

        const role = this.currentUser.role;
        const redirectMap = {
            [CONFIG.USER_ROLES.ADMIN]: '/pages/admin/dashboard.html',
            [CONFIG.USER_ROLES.BOOKING_STAFF]: '/pages/booking-staff/dashboard.html',
            [CONFIG.USER_ROLES.USER]: '/pages/user/dashboard.html'
        };

        const redirectUrl = redirectMap[role];
        if (redirectUrl && window.location.pathname !== redirectUrl) {
            window.location.href = redirectUrl;
        }
    }

    /**
     * Update authentication UI elements
     */
    updateAuthUI() {
        const authButton = document.getElementById('authButton');
        const accountLink = document.querySelector('a[href="#account"]');
        
        if (this.isAuthenticated()) {
            if (authButton) {
                authButton.textContent = 'Logout';
                authButton.onclick = () => this.logout();
            }
            
            if (accountLink) {
                accountLink.classList.remove('hidden');
            }
        } else {
            if (authButton) {
                authButton.textContent = 'Login';
                authButton.onclick = () => this.showAuthModal();
            }
            
            if (accountLink) {
                accountLink.classList.add('hidden');
            }
        }
    }

    /**
     * Show authentication modal
     */
    showAuthModal() {
        const authModal = document.getElementById('auth-modal');
        if (authModal) {
            authModal.classList.remove('hidden');
        }
    }

    /**
     * Bind authentication-related events
     */
    bindEvents() {
        // Login form submission
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(loginForm);
                const result = await this.login(
                    formData.get('email'),
                    formData.get('password')
                );
                this.handleAuthResponse(result);
            });
        }

        // Registration form submission
        const registerForm = document.getElementById('register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(registerForm);
                const userData = {
                    name: formData.get('name'),
                    email: formData.get('email'),
                    password: formData.get('password')
                };
                const result = await this.register(userData);
                this.handleAuthResponse(result);
            });
        }

        // Auth modal close
        const closeAuthModal = document.getElementById('close-auth-modal');
        if (closeAuthModal) {
            closeAuthModal.addEventListener('click', () => {
                document.getElementById('auth-modal').classList.add('hidden');
            });
        }
    }

    /**
     * Handle authentication response
     * @param {Object} result - Authentication result
     */
    handleAuthResponse(result) {
        const responseMessage = document.getElementById('auth-response-message');
        if (responseMessage) {
            responseMessage.textContent = result.message || (result.success ? 'Success!' : 'An error occurred');
            responseMessage.classList.remove('hidden', 'text-green-600', 'text-red-600');
            responseMessage.classList.add(result.success ? 'text-green-600' : 'text-red-600');
            
            if (result.success) {
                setTimeout(() => {
                    document.getElementById('auth-modal').classList.add('hidden');
                }, 1000);
            }
        }
    }

    /**
     * Make authenticated API call
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
            const data = await response.json();
            
            if (response.status === 401 && this.refreshToken) {
                // Try to refresh token and retry
                const refreshed = await this.refreshAuthToken();
                if (refreshed) {
                    defaultOptions.headers['Authorization'] = `Bearer ${this.sessionToken}`;
                    const retryResponse = await fetch(url, defaultOptions);
                    return await retryResponse.json();
                }
            }
            
            return data;
        } catch (error) {
            console.error('API call error:', error);
            throw error;
        }
    }
}

// Initialize authentication manager
const authManager = new AuthenticationManager();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthenticationManager;
} else {
    window.AuthManager = authManager;
}
