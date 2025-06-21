<?php
/**
 * CarGo API Documentation
 * 
 * This file provides documentation for the CarGo API endpoints
 */

header('Content-Type: application/json');

// API documentation
$apiDocs = [
    'name' => 'CarGo API',
    'version' => '1.0.0',
    'description' => 'API for the CarGo car rental platform',
    'base_url' => '/backend/api',
    'endpoints' => [
        // Vehicle Search endpoint
        [
            'path' => '/search',
            'method' => 'GET',
            'description' => 'Advanced search for vehicles with filtering and pagination',
            'params' => [
                'make' => 'Filter by vehicle make (optional)',
                'model' => 'Filter by vehicle model (optional)',
                'vehicle_type' => 'Filter by vehicle type (optional)',
                'fuel_type' => 'Filter by fuel type (optional)',
                'transmission' => 'Filter by transmission type (optional)',
                'min_capacity' => 'Filter by minimum capacity (optional)',
                'max_capacity' => 'Filter by maximum capacity (optional)',
                'min_daily_rate' => 'Filter by minimum daily rate (optional)',
                'max_daily_rate' => 'Filter by maximum daily rate (optional)',
                'available_from' => 'Filter by availability start date (optional)',
                'available_to' => 'Filter by availability end date (optional)',
                'with_driver' => 'Filter by driver availability (optional)',
                'features' => 'Filter by comma-separated features (optional)',
                'location' => 'Filter by location (optional)',
                'keyword' => 'Search across multiple fields (optional)',
                'page' => 'Page number for pagination (optional)',
                'limit' => 'Items per page (optional)',
                'sort_by' => 'Field to sort by (optional)',
                'sort_dir' => 'Sort direction (ASC/DESC, optional)'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'data' => 'Array of vehicles',
                'meta' => 'Pagination metadata'
            ]
        ],
        
        // Contact form endpoint
        [
            'path' => '/contact',
            'method' => 'POST',
            'description' => 'Submit contact form',
            'params' => [
                'name' => 'Contact name',
                'email' => 'Contact email',
                'message' => 'Contact message'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'message' => 'Confirmation message'
            ]
        ],
        
        // Authentication endpoints
        [
            'path' => '/auth/login',
            'method' => 'POST',
            'description' => 'Authenticate user',
            'params' => [
                'email' => 'User email',
                'password' => 'User password'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'user' => 'User information',
                'token' => 'JWT token for authentication'
            ]
        ],
        [
            'path' => '/auth/register',
            'method' => 'POST',
            'description' => 'Register a new user',
            'params' => [
                'email' => 'User email',
                'password' => 'User password',
                'first_name' => 'User first name',
                'last_name' => 'User last name',
                'phone' => 'User phone number',
                'address' => 'User address (optional)',
                'city' => 'User city (optional)',
                'country' => 'User country (optional)',
                'date_of_birth' => 'User date of birth (optional)'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'user' => 'User information',
                'token' => 'JWT token for authentication'
            ]
        ],
        [
            'path' => '/auth/verify-token',
            'method' => 'POST',
            'description' => 'Verify JWT token',
            'params' => [
                'token' => 'JWT token'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'user' => 'User information from token'
            ]
        ],
        
        // Vehicle endpoints
        [
            'path' => '/vehicles',
            'method' => 'GET',
            'description' => 'Get all available vehicles with optional filters',
            'params' => [
                'vehicle_type' => 'Filter by vehicle type (optional)',
                'make' => 'Filter by make (optional)',
                'fuel_type' => 'Filter by fuel type (optional)',
                'capacity' => 'Filter by minimum capacity (optional)',
                'max_daily_rate' => 'Filter by maximum daily rate (optional)'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'data' => 'Array of vehicles'
            ]
        ],
        [
            'path' => '/vehicles/{id}',
            'method' => 'GET',
            'description' => 'Get vehicle by ID',
            'params' => [
                'id' => 'Vehicle ID'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'data' => 'Vehicle information with images'
            ]
        ],
        [
            'path' => '/vehicles',
            'method' => 'POST',
            'description' => 'Create a new vehicle (requires admin or booking staff role)',
            'params' => [
                'make' => 'Vehicle make',
                'model' => 'Vehicle model',
                'year' => 'Vehicle year',
                'color' => 'Vehicle color',
                'license_plate' => 'Vehicle license plate',
                'vin' => 'Vehicle VIN (optional)',
                'vehicle_type' => 'Vehicle type',
                'fuel_type' => 'Vehicle fuel type',
                'transmission' => 'Vehicle transmission',
                'mileage' => 'Vehicle mileage',
                'capacity' => 'Vehicle capacity',
                'daily_rate' => 'Vehicle daily rate',
                'weekly_rate' => 'Vehicle weekly rate (optional)',
                'monthly_rate' => 'Vehicle monthly rate (optional)',
                'with_driver_rate' => 'Vehicle with driver rate (optional)',
                'features' => 'Array of vehicle features (optional)',
                'images' => 'Array of image URLs (optional)'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'data' => 'Created vehicle information'
            ]
        ],
        [
            'path' => '/vehicles/{id}',
            'method' => 'PUT',
            'description' => 'Update a vehicle (requires admin or booking staff role)',
            'params' => [
                'id' => 'Vehicle ID',
                '...' => 'Any vehicle properties to update'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'data' => 'Updated vehicle information'
            ]
        ],
        [
            'path' => '/vehicles/{id}',
            'method' => 'DELETE',
            'description' => 'Delete a vehicle (requires admin role)',
            'params' => [
                'id' => 'Vehicle ID'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'message' => 'Success message'
            ]
        ],
        [
            'path' => '/vehicles/filters',
            'method' => 'GET',
            'description' => 'Get vehicle filters',
            'params' => [],
            'response' => [
                'success' => 'Boolean indicating success',
                'data' => 'Filter options for vehicles'
            ]
        ],
        [
            'path' => '/vehicles/availability',
            'method' => 'POST',
            'description' => 'Check vehicle availability for booking',
            'params' => [
                'vehicle_id' => 'Vehicle ID',
                'start_date' => 'Start date (YYYY-MM-DD)',
                'end_date' => 'End date (YYYY-MM-DD)'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'available' => 'Boolean indicating availability'
            ]
        ],
        
        // Booking endpoints
        [
            'path' => '/bookings',
            'method' => 'GET',
            'description' => 'Get all bookings (with optional filters)',
            'params' => [
                'status' => 'Filter by booking status (optional)',
                'from_date' => 'Filter by start date (optional)',
                'to_date' => 'Filter by end date (optional)',
                'vehicle_id' => 'Filter by vehicle ID (optional)',
                'page' => 'Page number for pagination (optional)',
                'limit' => 'Items per page (optional)'
            ],
            'response' => [
                'data' => 'Array of booking objects'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/bookings/{id}',
            'method' => 'GET',
            'description' => 'Get a specific booking by ID',
            'params' => [
                'id' => 'Booking ID'
            ],
            'response' => [
                'data' => 'Booking object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/bookings',
            'method' => 'POST',
            'description' => 'Create a new booking',
            'params' => [
                'vehicle_id' => 'Vehicle ID',
                'user_id' => 'User ID',
                'start_date' => 'Rental start date',
                'end_date' => 'Rental end date',
                'pickup_location' => 'Pickup location',
                'return_location' => 'Return location',
                'with_driver' => 'Boolean indicating if booking includes driver',
                'booking_notes' => 'Additional notes (optional)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Created booking object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/bookings/{id}',
            'method' => 'PUT',
            'description' => 'Update an existing booking',
            'params' => [
                'id' => 'Booking ID',
                'start_date' => 'Rental start date (optional)',
                'end_date' => 'Rental end date (optional)',
                'pickup_location' => 'Pickup location (optional)',
                'return_location' => 'Return location (optional)',
                'with_driver' => 'Boolean indicating if booking includes driver (optional)',
                'booking_notes' => 'Additional notes (optional)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Updated booking object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/bookings/{id}',
            'method' => 'DELETE',
            'description' => 'Cancel a booking',
            'params' => [
                'id' => 'Booking ID'
            ],
            'response' => [
                'message' => 'Success message'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/bookings/status/{id}',
            'method' => 'PUT',
            'description' => 'Update booking status',
            'params' => [
                'id' => 'Booking ID',
                'status' => 'New status (pending, confirmed, ongoing, completed, cancelled)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Updated booking object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/bookings/user/bookings',
            'method' => 'GET',
            'description' => 'Get bookings for the current authenticated user',
            'params' => [
                'status' => 'Filter by booking status (optional)',
                'page' => 'Page number for pagination (optional)',
                'limit' => 'Items per page (optional)'
            ],
            'response' => [
                'data' => 'Array of booking objects'
            ],
            'auth_required' => true
        ],
        
        // Payment endpoints
        [
            'path' => '/payments',
            'method' => 'GET',
            'description' => 'Get all payments (with optional filters)',
            'params' => [
                'status' => 'Filter by payment status (optional)',
                'from_date' => 'Filter by date (optional)',
                'to_date' => 'Filter by date (optional)',
                'booking_id' => 'Filter by booking ID (optional)',
                'user_id' => 'Filter by user ID (optional)',
                'page' => 'Page number for pagination (optional)',
                'limit' => 'Items per page (optional)'
            ],
            'response' => [
                'data' => 'Array of payment objects'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/payments/{id}',
            'method' => 'GET',
            'description' => 'Get a specific payment by ID',
            'params' => [
                'id' => 'Payment ID'
            ],
            'response' => [
                'data' => 'Payment object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/payments',
            'method' => 'POST',
            'description' => 'Create a new payment',
            'params' => [
                'booking_id' => 'Booking ID',
                'amount' => 'Payment amount',
                'payment_method' => 'Payment method (CREDIT_CARD, DEBIT_CARD, GCASH, PAYMAYA, QR_CODE)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Created payment object',
                'payment_details' => 'Additional payment details'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/payments/booking/{id}',
            'method' => 'GET',
            'description' => 'Get payments for a specific booking',
            'params' => [
                'id' => 'Booking ID'
            ],
            'response' => [
                'data' => 'Array of payment objects'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/payments/booking/{id}',
            'method' => 'POST',
            'description' => 'Process payment for a booking',
            'params' => [
                'id' => 'Booking ID',
                'payment_method' => 'Payment method (CREDIT_CARD, DEBIT_CARD, GCASH, PAYMAYA, QR_CODE)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Created payment object',
                'payment_details' => 'Additional payment details'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/payments/methods',
            'method' => 'GET',
            'description' => 'Get available payment methods',
            'params' => [],
            'response' => [
                'data' => 'Array of payment methods'
            ],
            'auth_required' => true
        ],
        
        // User endpoints
        [
            'path' => '/users',
            'method' => 'GET',
            'description' => 'Get all users (with optional filters) - Admin only',
            'params' => [
                'role' => 'Filter by role (optional)',
                'status' => 'Filter by status (optional)',
                'search' => 'Search by name or email (optional)',
                'page' => 'Page number for pagination (optional)',
                'limit' => 'Items per page (optional)'
            ],
            'response' => [
                'data' => 'Array of user objects'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/users/{id}',
            'method' => 'GET',
            'description' => 'Get a specific user by ID',
            'params' => [
                'id' => 'User ID'
            ],
            'response' => [
                'data' => 'User object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/users/profile',
            'method' => 'GET',
            'description' => 'Get current user profile',
            'params' => [],
            'response' => [
                'data' => 'User object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/users/{id}',
            'method' => 'PUT',
            'description' => 'Update an existing user',
            'params' => [
                'id' => 'User ID',
                'first_name' => 'First name (optional)',
                'last_name' => 'Last name (optional)',
                'email' => 'Email (optional)',
                'phone' => 'Phone number (optional)',
                'address' => 'Address (optional)',
                'city' => 'City (optional)',
                'country' => 'Country (optional)',
                'profile_picture' => 'Profile picture URL (optional)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Updated user object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/users/profile',
            'method' => 'PUT',
            'description' => 'Update current user profile',
            'params' => [
                'first_name' => 'First name (optional)',
                'last_name' => 'Last name (optional)',
                'email' => 'Email (optional)',
                'phone' => 'Phone number (optional)',
                'address' => 'Address (optional)',
                'city' => 'City (optional)',
                'country' => 'Country (optional)',
                'profile_picture' => 'Profile picture URL (optional)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Updated user object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/users/change-password',
            'method' => 'POST',
            'description' => 'Change user password',
            'params' => [
                'current_password' => 'Current password',
                'new_password' => 'New password'
            ],
            'response' => [
                'message' => 'Success message'
            ],
            'auth_required' => true
        ],
        
        // Notification endpoints
        [
            'path' => '/notifications',
            'method' => 'GET',
            'description' => 'Get user notifications',
            'params' => [
                'is_read' => 'Filter by read status (true/false) (optional)',
                'page' => 'Page number for pagination (optional)',
                'limit' => 'Items per page (optional)'
            ],
            'response' => [
                'data' => 'Array of notification objects'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/notifications/{id}',
            'method' => 'GET',
            'description' => 'Get a specific notification by ID',
            'params' => [
                'id' => 'Notification ID'
            ],
            'response' => [
                'data' => 'Notification object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/notifications/read/{id}',
            'method' => 'PUT',
            'description' => 'Mark a notification as read',
            'params' => [
                'id' => 'Notification ID'
            ],
            'response' => [
                'message' => 'Success message'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/notifications/read-all',
            'method' => 'PUT',
            'description' => 'Mark all notifications as read',
            'params' => [],
            'response' => [
                'message' => 'Success message'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/notifications/count',
            'method' => 'GET',
            'description' => 'Get unread notification count',
            'params' => [],
            'response' => [                'data' => [
                    'count' => 'Number of unread notifications'
                ]
            ],
            'auth_required' => true
        },
        
        // Analytics endpoints
        [
            'path' => '/analytics/dashboard',
            'method' => 'GET',
            'description' => 'Get dashboard statistics',
            'params' => [],
            'response' => [
                'data' => 'Dashboard statistics object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/analytics/bookings',
            'method' => 'GET',
            'description' => 'Get booking statistics',
            'params' => [
                'start_date' => 'Start date for analysis (optional)',
                'end_date' => 'End date for analysis (optional)'
            ],
            'response' => [
                'data' => 'Booking statistics object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/analytics/vehicles',
            'method' => 'GET',
            'description' => 'Get vehicle statistics',
            'params' => [
                'start_date' => 'Start date for analysis (optional)',
                'end_date' => 'End date for analysis (optional)'
            ],
            'response' => [
                'data' => 'Vehicle statistics object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/analytics/revenue',
            'method' => 'GET',
            'description' => 'Get revenue statistics',
            'params' => [
                'start_date' => 'Start date for analysis (optional)',
                'end_date' => 'End date for analysis (optional)'
            ],
            'response' => [
                'data' => 'Revenue statistics object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/analytics/users',
            'method' => 'GET',
            'description' => 'Get user statistics',
            'params' => [
                'start_date' => 'Start date for analysis (optional)',
                'end_date' => 'End date for analysis (optional)'
            ],
            'response' => [
                'data' => 'User statistics object'
            ],
            'auth_required' => true
        ],
        
        // Vehicle Search endpoint
        [
            'path' => '/search',
            'method' => 'GET',
            'description' => 'Advanced search for vehicles with filtering and pagination',
            'params' => [
                'make' => 'Filter by vehicle make (optional)',
                'model' => 'Filter by vehicle model (optional)',
                'vehicle_type' => 'Filter by vehicle type (optional)',
                'fuel_type' => 'Filter by fuel type (optional)',
                'transmission' => 'Filter by transmission type (optional)',
                'min_capacity' => 'Filter by minimum capacity (optional)',
                'max_capacity' => 'Filter by maximum capacity (optional)',
                'min_daily_rate' => 'Filter by minimum daily rate (optional)',
                'max_daily_rate' => 'Filter by maximum daily rate (optional)',
                'available_from' => 'Filter by availability start date (optional)',
                'available_to' => 'Filter by availability end date (optional)',
                'with_driver' => 'Filter by driver availability (optional)',
                'features' => 'Filter by comma-separated features (optional)',
                'location' => 'Filter by location (optional)',
                'keyword' => 'Search across multiple fields (optional)',
                'page' => 'Page number for pagination (optional)',
                'limit' => 'Items per page (optional)',
                'sort_by' => 'Field to sort by (optional)',
                'sort_dir' => 'Sort direction (ASC/DESC, optional)'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'data' => 'Array of vehicles',
                'meta' => 'Pagination metadata'
            ]
        ],
        
        // Contact form endpoint
        [
            'path' => '/contact',
            'method' => 'POST',
            'description' => 'Submit contact form',
            'params' => [
                'name' => 'Contact name',
                'email' => 'Contact email',
                'message' => 'Contact message'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'message' => 'Confirmation message'
            ]
        ],
        
        // Authentication endpoints
        [
            'path' => '/auth/login',
            'method' => 'POST',
            'description' => 'Authenticate user',
            'params' => [
                'email' => 'User email',
                'password' => 'User password'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'user' => 'User information',
                'token' => 'JWT token for authentication'
            ]
        ],
        [
            'path' => '/auth/register',
            'method' => 'POST',
            'description' => 'Register a new user',
            'params' => [
                'email' => 'User email',
                'password' => 'User password',
                'first_name' => 'User first name',
                'last_name' => 'User last name',
                'phone' => 'User phone number',
                'address' => 'User address (optional)',
                'city' => 'User city (optional)',
                'country' => 'User country (optional)',
                'date_of_birth' => 'User date of birth (optional)'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'user' => 'User information',
                'token' => 'JWT token for authentication'
            ]
        ],
        [
            'path' => '/auth/verify-token',
            'method' => 'POST',
            'description' => 'Verify JWT token',
            'params' => [
                'token' => 'JWT token'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'user' => 'User information from token'
            ]
        ],
        
        // Vehicle endpoints
        [
            'path' => '/vehicles',
            'method' => 'GET',
            'description' => 'Get all available vehicles with optional filters',
            'params' => [
                'vehicle_type' => 'Filter by vehicle type (optional)',
                'make' => 'Filter by make (optional)',
                'fuel_type' => 'Filter by fuel type (optional)',
                'capacity' => 'Filter by minimum capacity (optional)',
                'max_daily_rate' => 'Filter by maximum daily rate (optional)'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'data' => 'Array of vehicles'
            ]
        ],
        [
            'path' => '/vehicles/{id}',
            'method' => 'GET',
            'description' => 'Get vehicle by ID',
            'params' => [
                'id' => 'Vehicle ID'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'data' => 'Vehicle information with images'
            ]
        ],
        [
            'path' => '/vehicles',
            'method' => 'POST',
            'description' => 'Create a new vehicle (requires admin or booking staff role)',
            'params' => [
                'make' => 'Vehicle make',
                'model' => 'Vehicle model',
                'year' => 'Vehicle year',
                'color' => 'Vehicle color',
                'license_plate' => 'Vehicle license plate',
                'vin' => 'Vehicle VIN (optional)',
                'vehicle_type' => 'Vehicle type',
                'fuel_type' => 'Vehicle fuel type',
                'transmission' => 'Vehicle transmission',
                'mileage' => 'Vehicle mileage',
                'capacity' => 'Vehicle capacity',
                'daily_rate' => 'Vehicle daily rate',
                'weekly_rate' => 'Vehicle weekly rate (optional)',
                'monthly_rate' => 'Vehicle monthly rate (optional)',
                'with_driver_rate' => 'Vehicle with driver rate (optional)',
                'features' => 'Array of vehicle features (optional)',
                'images' => 'Array of image URLs (optional)'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'data' => 'Created vehicle information'
            ]
        ],
        [
            'path' => '/vehicles/{id}',
            'method' => 'PUT',
            'description' => 'Update a vehicle (requires admin or booking staff role)',
            'params' => [
                'id' => 'Vehicle ID',
                '...' => 'Any vehicle properties to update'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'data' => 'Updated vehicle information'
            ]
        ],
        [
            'path' => '/vehicles/{id}',
            'method' => 'DELETE',
            'description' => 'Delete a vehicle (requires admin role)',
            'params' => [
                'id' => 'Vehicle ID'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'message' => 'Success message'
            ]
        ],
        [
            'path' => '/vehicles/filters',
            'method' => 'GET',
            'description' => 'Get vehicle filters',
            'params' => [],
            'response' => [
                'success' => 'Boolean indicating success',
                'data' => 'Filter options for vehicles'
            ]
        ],
        [
            'path' => '/vehicles/availability',
            'method' => 'POST',
            'description' => 'Check vehicle availability for booking',
            'params' => [
                'vehicle_id' => 'Vehicle ID',
                'start_date' => 'Start date (YYYY-MM-DD)',
                'end_date' => 'End date (YYYY-MM-DD)'
            ],
            'response' => [
                'success' => 'Boolean indicating success',
                'available' => 'Boolean indicating availability'
            ]
        ],
        
        // Booking endpoints
        [
            'path' => '/bookings',
            'method' => 'GET',
            'description' => 'Get all bookings (with optional filters)',
            'params' => [
                'status' => 'Filter by booking status (optional)',
                'from_date' => 'Filter by start date (optional)',
                'to_date' => 'Filter by end date (optional)',
                'vehicle_id' => 'Filter by vehicle ID (optional)',
                'page' => 'Page number for pagination (optional)',
                'limit' => 'Items per page (optional)'
            ],
            'response' => [
                'data' => 'Array of booking objects'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/bookings/{id}',
            'method' => 'GET',
            'description' => 'Get a specific booking by ID',
            'params' => [
                'id' => 'Booking ID'
            ],
            'response' => [
                'data' => 'Booking object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/bookings',
            'method' => 'POST',
            'description' => 'Create a new booking',
            'params' => [
                'vehicle_id' => 'Vehicle ID',
                'user_id' => 'User ID',
                'start_date' => 'Rental start date',
                'end_date' => 'Rental end date',
                'pickup_location' => 'Pickup location',
                'return_location' => 'Return location',
                'with_driver' => 'Boolean indicating if booking includes driver',
                'booking_notes' => 'Additional notes (optional)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Created booking object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/bookings/{id}',
            'method' => 'PUT',
            'description' => 'Update an existing booking',
            'params' => [
                'id' => 'Booking ID',
                'start_date' => 'Rental start date (optional)',
                'end_date' => 'Rental end date (optional)',
                'pickup_location' => 'Pickup location (optional)',
                'return_location' => 'Return location (optional)',
                'with_driver' => 'Boolean indicating if booking includes driver (optional)',
                'booking_notes' => 'Additional notes (optional)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Updated booking object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/bookings/{id}',
            'method' => 'DELETE',
            'description' => 'Cancel a booking',
            'params' => [
                'id' => 'Booking ID'
            ],
            'response' => [
                'message' => 'Success message'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/bookings/status/{id}',
            'method' => 'PUT',
            'description' => 'Update booking status',
            'params' => [
                'id' => 'Booking ID',
                'status' => 'New status (pending, confirmed, ongoing, completed, cancelled)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Updated booking object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/bookings/user/bookings',
            'method' => 'GET',
            'description' => 'Get bookings for the current authenticated user',
            'params' => [
                'status' => 'Filter by booking status (optional)',
                'page' => 'Page number for pagination (optional)',
                'limit' => 'Items per page (optional)'
            ],
            'response' => [
                'data' => 'Array of booking objects'
            ],
            'auth_required' => true
        ],
        
        // Payment endpoints
        [
            'path' => '/payments',
            'method' => 'GET',
            'description' => 'Get all payments (with optional filters)',
            'params' => [
                'status' => 'Filter by payment status (optional)',
                'from_date' => 'Filter by date (optional)',
                'to_date' => 'Filter by date (optional)',
                'booking_id' => 'Filter by booking ID (optional)',
                'user_id' => 'Filter by user ID (optional)',
                'page' => 'Page number for pagination (optional)',
                'limit' => 'Items per page (optional)'
            ],
            'response' => [
                'data' => 'Array of payment objects'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/payments/{id}',
            'method' => 'GET',
            'description' => 'Get a specific payment by ID',
            'params' => [
                'id' => 'Payment ID'
            ],
            'response' => [
                'data' => 'Payment object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/payments',
            'method' => 'POST',
            'description' => 'Create a new payment',
            'params' => [
                'booking_id' => 'Booking ID',
                'amount' => 'Payment amount',
                'payment_method' => 'Payment method (CREDIT_CARD, DEBIT_CARD, GCASH, PAYMAYA, QR_CODE)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Created payment object',
                'payment_details' => 'Additional payment details'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/payments/booking/{id}',
            'method' => 'GET',
            'description' => 'Get payments for a specific booking',
            'params' => [
                'id' => 'Booking ID'
            ],
            'response' => [
                'data' => 'Array of payment objects'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/payments/booking/{id}',
            'method' => 'POST',
            'description' => 'Process payment for a booking',
            'params' => [
                'id' => 'Booking ID',
                'payment_method' => 'Payment method (CREDIT_CARD, DEBIT_CARD, GCASH, PAYMAYA, QR_CODE)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Created payment object',
                'payment_details' => 'Additional payment details'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/payments/methods',
            'method' => 'GET',
            'description' => 'Get available payment methods',
            'params' => [],
            'response' => [
                'data' => 'Array of payment methods'
            ],
            'auth_required' => true
        ],
        
        // User endpoints
        [
            'path' => '/users',
            'method' => 'GET',
            'description' => 'Get all users (with optional filters) - Admin only',
            'params' => [
                'role' => 'Filter by role (optional)',
                'status' => 'Filter by status (optional)',
                'search' => 'Search by name or email (optional)',
                'page' => 'Page number for pagination (optional)',
                'limit' => 'Items per page (optional)'
            ],
            'response' => [
                'data' => 'Array of user objects'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/users/{id}',
            'method' => 'GET',
            'description' => 'Get a specific user by ID',
            'params' => [
                'id' => 'User ID'
            ],
            'response' => [
                'data' => 'User object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/users/profile',
            'method' => 'GET',
            'description' => 'Get current user profile',
            'params' => [],
            'response' => [
                'data' => 'User object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/users/{id}',
            'method' => 'PUT',
            'description' => 'Update an existing user',
            'params' => [
                'id' => 'User ID',
                'first_name' => 'First name (optional)',
                'last_name' => 'Last name (optional)',
                'email' => 'Email (optional)',
                'phone' => 'Phone number (optional)',
                'address' => 'Address (optional)',
                'city' => 'City (optional)',
                'country' => 'Country (optional)',
                'profile_picture' => 'Profile picture URL (optional)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Updated user object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/users/profile',
            'method' => 'PUT',
            'description' => 'Update current user profile',
            'params' => [
                'first_name' => 'First name (optional)',
                'last_name' => 'Last name (optional)',
                'email' => 'Email (optional)',
                'phone' => 'Phone number (optional)',
                'address' => 'Address (optional)',
                'city' => 'City (optional)',
                'country' => 'Country (optional)',
                'profile_picture' => 'Profile picture URL (optional)'
            ],
            'response' => [
                'message' => 'Success message',
                'data' => 'Updated user object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/users/change-password',
            'method' => 'POST',
            'description' => 'Change user password',
            'params' => [
                'current_password' => 'Current password',
                'new_password' => 'New password'
            ],
            'response' => [
                'message' => 'Success message'
            ],
            'auth_required' => true
        ],
        
        // Notification endpoints
        [
            'path' => '/notifications',
            'method' => 'GET',
            'description' => 'Get user notifications',
            'params' => [
                'is_read' => 'Filter by read status (true/false) (optional)',
                'page' => 'Page number for pagination (optional)',
                'limit' => 'Items per page (optional)'
            ],
            'response' => [
                'data' => 'Array of notification objects'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/notifications/{id}',
            'method' => 'GET',
            'description' => 'Get a specific notification by ID',
            'params' => [
                'id' => 'Notification ID'
            ],
            'response' => [
                'data' => 'Notification object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/notifications/read/{id}',
            'method' => 'PUT',
            'description' => 'Mark a notification as read',
            'params' => [
                'id' => 'Notification ID'
            ],
            'response' => [
                'message' => 'Success message'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/notifications/read-all',
            'method' => 'PUT',
            'description' => 'Mark all notifications as read',
            'params' => [],
            'response' => [
                'message' => 'Success message'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/notifications/count',
            'method' => 'GET',
            'description' => 'Get unread notification count',
            'params' => [],
            'response' => [                'data' => [
                    'count' => 'Number of unread notifications'
                ]
            ],
            'auth_required' => true
        },
        
        // Analytics endpoints
        [
            'path' => '/analytics/dashboard',
            'method' => 'GET',
            'description' => 'Get dashboard statistics',
            'params' => [],
            'response' => [
                'data' => 'Dashboard statistics object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/analytics/bookings',
            'method' => 'GET',
            'description' => 'Get booking statistics',
            'params' => [
                'start_date' => 'Start date for analysis (optional)',
                'end_date' => 'End date for analysis (optional)'
            ],
            'response' => [
                'data' => 'Booking statistics object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/analytics/vehicles',
            'method' => 'GET',
            'description' => 'Get vehicle statistics',
            'params' => [
                'start_date' => 'Start date for analysis (optional)',
                'end_date' => 'End date for analysis (optional)'
            ],
            'response' => [
                'data' => 'Vehicle statistics object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/analytics/revenue',
            'method' => 'GET',
            'description' => 'Get revenue statistics',
            'params' => [
                'start_date' => 'Start date for analysis (optional)',
                'end_date' => 'End date for analysis (optional)'
            ],
            'response' => [
                'data' => 'Revenue statistics object'
            ],
            'auth_required' => true
        ],
        [
            'path' => '/analytics/users',
            'method' => 'GET',
            'description' => 'Get user statistics',
            'params' => [
                'start_date' => 'Start date for analysis (optional)',
                'end_date' => 'End date for analysis (optional)'
            ],
            'response' => [
                'data' => 'User statistics object'
            ],
            'auth_required' => true
        ]
    ]
];

// Return API documentation
echo json_encode($apiDocs, JSON_PRETTY_PRINT);
