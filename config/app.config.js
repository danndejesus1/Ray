// CarGo Main Application Configuration
const CONFIG = {
    // Application Settings
    APP_NAME: 'CarGo',
    VERSION: '1.0.0',
    ENVIRONMENT: 'development', // development, staging, production
    
    // API Settings
    API: {
        BASE_URL: 'https://api.cargo.com/v1',
        TIMEOUT: 30000,
        RETRY_ATTEMPTS: 3
    },
    
    // Geographic Settings
    LOCATION: {
        DEFAULT_CITY: 'Metro Manila',
        COUNTRY: 'Philippines',
        TIMEZONE: 'Asia/Manila',
        CURRENCY: 'PHP',
        CURRENCY_SYMBOL: '₱'
    },
    
    // Business Rules
    BOOKING: {
        MIN_RENTAL_DAYS: 1,
        MAX_RENTAL_DAYS: 30,
        ADVANCE_BOOKING_DAYS: 90,
        CANCELLATION_HOURS: 24
    },
    
    // Payment Settings
    PAYMENT: {
        METHODS: ['QR_CODE', 'CREDIT_CARD', 'DEBIT_CARD', 'GCASH', 'PAYMAYA'],
        DEFAULT_METHOD: 'QR_CODE',
        PROCESSING_FEE: 0.03, // 3%
        TAX_RATE: 0.12 // 12% VAT
    },
    
    // User Roles
    USER_ROLES: {
        ADMIN: 'admin',
        BOOKING_STAFF: 'booking_staff',
        USER: 'user',
        GUEST: 'guest'
    },
    
    // Vehicle Categories
    VEHICLE: {
        TYPES: ['Sedan', 'SUV', 'Hatchback', 'Van', 'Pickup'],
        FUEL_TYPES: ['Petrol', 'Diesel', 'Hybrid', 'Electric'],
        DRIVING_OPTIONS: ['Self Drive', 'With Driver'],
        CAPACITIES: [2, 4, 5, 7, 8, 12]
    },
    
    // Notification Settings
    NOTIFICATIONS: {
        BOOKING_CONFIRMATION: true,
        PAYMENT_SUCCESS: true,
        REMINDER_24H: true,
        REMINDER_2H: true,
        CANCELLATION: true
    },
    
    // File Upload Settings
    UPLOAD: {
        MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
        ALLOWED_TYPES: ['image/jpeg', 'image/png', 'image/webp'],
        PROFILE_PICTURE_SIZE: 150,
        VEHICLE_IMAGE_SIZE: 800
    },
    
    // Security Settings
    SECURITY: {
        SESSION_TIMEOUT: 30 * 60 * 1000, // 30 minutes
        PASSWORD_MIN_LENGTH: 8,
        MFA_ENABLED: true,
        RATE_LIMIT: {
            LOGIN_ATTEMPTS: 5,
            API_REQUESTS: 100
        }
    },
    
    // Analytics Settings
    ANALYTICS: {
        TRACK_USER_ACTIONS: true,
        RETENTION_DAYS: 365,
        REAL_TIME_UPDATES: true
    },
    
    // PWA Settings
    PWA: {
        CACHE_NAME: 'cargo-v1',
        OFFLINE_PAGE: '/offline.html',
        UPDATE_CHECK_INTERVAL: 60000 // 1 minute
    },
    
    // Development Settings
    DEBUG: {
        CONSOLE_LOGS: true,
        API_LOGS: true,
        PERFORMANCE_LOGS: false
    }
};

// Environment-specific overrides
if (CONFIG.ENVIRONMENT === 'production') {
    CONFIG.API.BASE_URL = 'https://api.cargo.com/v1';
    CONFIG.DEBUG.CONSOLE_LOGS = false;
    CONFIG.DEBUG.API_LOGS = false;
} else if (CONFIG.ENVIRONMENT === 'staging') {
    CONFIG.API.BASE_URL = 'https://staging-api.cargo.com/v1';
}

// Freeze configuration to prevent modifications
Object.freeze(CONFIG);

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CONFIG;
} else {
    window.CONFIG = CONFIG;
}
