<?php
/**
 * CarGo PHP Application Configuration
 * 
 * This file mirrors the JavaScript configuration with PHP equivalents
 */

// Define the base configuration
$CONFIG = [
    // Application Settings
    'APP_NAME' => 'CarGo',
    'VERSION' => '1.0.0',
    'ENVIRONMENT' => 'development', // development, staging, production
    
    // API Settings
    'API' => [
        'BASE_URL' => 'https://api.cargo.com/v1',
        'TIMEOUT' => 30000,
        'RETRY_ATTEMPTS' => 3
    ],
    
    // Geographic Settings
    'LOCATION' => [
        'DEFAULT_CITY' => 'Metro Manila',
        'COUNTRY' => 'Philippines',
        'TIMEZONE' => 'Asia/Manila',
        'CURRENCY' => 'PHP',
        'CURRENCY_SYMBOL' => '₱'
    ],
    
    // Business Rules
    'BOOKING' => [
        'MIN_RENTAL_DAYS' => 1,
        'MAX_RENTAL_DAYS' => 30,
        'ADVANCE_BOOKING_DAYS' => 90,
        'CANCELLATION_HOURS' => 24
    ],
    
    // Payment Settings
    'PAYMENT' => [
        'METHODS' => ['QR_CODE', 'CREDIT_CARD', 'DEBIT_CARD', 'GCASH', 'PAYMAYA'],
        'DEFAULT_METHOD' => 'QR_CODE',
        'PROCESSING_FEE' => 0.03, // 3%
        'TAX_RATE' => 0.12 // 12% VAT
    ],
    
    // User Roles
    'USER_ROLES' => [
        'ADMIN' => 'admin',
        'BOOKING_STAFF' => 'booking_staff',
        'USER' => 'user',
        'GUEST' => 'guest'
    ],
    
    // Vehicle Categories
    'VEHICLE' => [
        'TYPES' => ['Sedan', 'SUV', 'Hatchback', 'Van', 'Pickup'],
        'FUEL_TYPES' => ['Petrol', 'Diesel', 'Hybrid', 'Electric'],
        'DRIVING_OPTIONS' => ['Self Drive', 'With Driver'],
        'CAPACITIES' => [2, 4, 5, 7, 8, 12]
    ],
    
    // Notification Settings
    'NOTIFICATIONS' => [
        'BOOKING_CONFIRMATION' => true,
        'PAYMENT_SUCCESS' => true,
        'REMINDER_24H' => true,
        'REMINDER_2H' => true,
        'CANCELLATION' => true
    ],
    
    // File Upload Settings
    'UPLOAD' => [
        'MAX_FILE_SIZE' => 5 * 1024 * 1024, // 5MB
        'ALLOWED_TYPES' => ['image/jpeg', 'image/png', 'image/webp'],
        'PROFILE_PICTURE_SIZE' => 150,
        'VEHICLE_IMAGE_SIZE' => 800,
        'UPLOAD_DIR' => __DIR__ . '/../../uploads/' // Upload directory relative to this file
    ],
    
    // Security Settings
    'SECURITY' => [
        'SESSION_TIMEOUT' => 30 * 60, // 30 minutes in seconds
        'PASSWORD_MIN_LENGTH' => 8,
        'MFA_ENABLED' => true,
        'JWT_SECRET' => 'your-secret-key-here', // Change in production
        'JWT_EXPIRY' => 86400, // 24 hours in seconds
        'RATE_LIMIT' => [
            'LOGIN_ATTEMPTS' => 5,
            'API_REQUESTS' => 100
        ]
    ],
    
    // Analytics Settings
    'ANALYTICS' => [
        'TRACK_USER_ACTIONS' => true,
        'RETENTION_DAYS' => 365,
        'REAL_TIME_UPDATES' => true
    ],
    
    // Database Settings
    'DATABASE' => [
        'HOST' => 'localhost',
        'NAME' => 'cargo_db',
        'USER' => 'cargo_user',
        'PASSWORD' => 'cargo_password', // Change in production
        'CHARSET' => 'utf8mb4',
        'PORT' => 3306
    ],
    
    // Debug Settings
    'DEBUG' => [
        'ENABLE_LOGS' => true,
        'LOG_LEVEL' => 'debug', // debug, info, warning, error
        'LOG_PATH' => __DIR__ . '/../../logs/'
    ]
];

// Environment-specific overrides
if ($CONFIG['ENVIRONMENT'] === 'production') {
    $CONFIG['API']['BASE_URL'] = 'https://api.cargo.com/v1';
    $CONFIG['DEBUG']['ENABLE_LOGS'] = false;
    $CONFIG['DATABASE']['HOST'] = 'production-db-host';
    $CONFIG['DATABASE']['NAME'] = 'cargo_production';
    // Production credentials would be loaded from environment variables
    // $CONFIG['DATABASE']['USER'] = getenv('DB_USER');
    // $CONFIG['DATABASE']['PASSWORD'] = getenv('DB_PASSWORD');
} elseif ($CONFIG['ENVIRONMENT'] === 'staging') {
    $CONFIG['API']['BASE_URL'] = 'https://staging-api.cargo.com/v1';
    $CONFIG['DATABASE']['HOST'] = 'staging-db-host';
    $CONFIG['DATABASE']['NAME'] = 'cargo_staging';
}

// Make CONFIG a constant to prevent modifications
define('CONFIG', $CONFIG);
