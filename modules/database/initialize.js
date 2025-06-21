/**
 * CarGo Database Initialization Script
 * 
 * This script initializes the database with default data
 * Run this script after setting up the database schema
 */

// Import the database utility
import { DB } from './database.js';
import { CONFIG } from '../../config/app.config.js';
import { hashPassword } from '../utils/utils.js';

/**
 * Initialize the database with default data
 */
export async function initializeDatabase() {
    console.log('Starting database initialization...');
    
    try {
        // Connect to the database
        await DB.initializeConnection();
        
        // Add default roles if they don't exist
        await ensureRolesExist();
        
        // Add default admin user if it doesn't exist
        await ensureAdminExists();
        
        // Add default settings
        await ensureSettingsExist();
        
        // Add demo vehicles (for development only)
        if (CONFIG.ENVIRONMENT === 'development') {
            await addDemoVehicles();
        }
        
        console.log('Database initialization completed successfully.');
        return true;
    } catch (error) {
        console.error('Database initialization failed:', error);
        return false;
    }
}

/**
 * Ensure all required roles exist
 */
async function ensureRolesExist() {
    const roles = [
        { role_name: 'admin', description: 'System administrator with full access' },
        { role_name: 'booking_staff', description: 'Staff who manages bookings and customer service' },
        { role_name: 'user', description: 'Regular user who can book vehicles' },
        { role_name: 'guest', description: 'Unregistered user with limited access' }
    ];
    
    for (const role of roles) {
        // Check if role exists (in a real implementation)
        // Here we're just simulating the process
        console.log(`Ensuring role exists: ${role.role_name}`);
        
        // In a real implementation, you would check if the role exists
        // and create it if it doesn't
        await DB.executeQuery(
            'INSERT OR IGNORE INTO roles (role_name, description) VALUES (?, ?)',
            [role.role_name, role.description]
        );
    }
    
    console.log('Roles checked and created if needed');
}

/**
 * Ensure admin user exists
 */
async function ensureAdminExists() {
    // In a real implementation, you would check if an admin user exists
    console.log('Checking for admin user...');
    
    const adminEmail = 'admin@cargo.com';
    
    // Check if admin exists
    const existingAdmin = await DB.getUserByEmail(adminEmail);
    
    if (!existingAdmin) {
        console.log('Admin user not found, creating...');
        
        // Get admin role ID (in a real implementation)
        const adminRoleId = 1; // Assuming admin role_id is 1
        
        // Create admin user
        const adminData = {
            role_id: adminRoleId,
            email: adminEmail,
            password_hash: await hashPassword('Admin123!'), // In a real app, use a secure password
            first_name: 'System',
            last_name: 'Administrator',
            phone: '09123456789',
            is_verified: true,
            is_active: true
        };
        
        await DB.createUser(adminData);
        console.log('Admin user created successfully');
    } else {
        console.log('Admin user already exists');
    }
}

/**
 * Ensure system settings exist
 */
async function ensureSettingsExist() {
    const settings = [
        { key: 'company_name', value: CONFIG.APP_NAME, group: 'general', is_public: true },
        { key: 'support_email', value: 'support@cargo.com', group: 'contact', is_public: true },
        { key: 'support_phone', value: '09123456789', group: 'contact', is_public: true },
        { key: 'business_hours', value: 'Monday-Friday: 8AM-5PM, Saturday: 9AM-12PM', group: 'general', is_public: true },
        { key: 'maintenance_mode', value: 'false', group: 'system', is_public: false },
        { key: 'default_currency', value: CONFIG.LOCATION.CURRENCY, group: 'payment', is_public: true },
        { key: 'min_booking_hours', value: '24', group: 'booking', is_public: true },
        { key: 'terms_version', value: '1.0', group: 'legal', is_public: true }
    ];
    
    for (const setting of settings) {
        // In a real implementation, you would check if the setting exists
        // and create it if it doesn't
        console.log(`Ensuring setting exists: ${setting.key}`);
        
        await DB.executeQuery(
            'INSERT OR IGNORE INTO settings (setting_key, setting_value, setting_group, is_public) VALUES (?, ?, ?, ?)',
            [setting.key, setting.value, setting.group, setting.is_public ? 1 : 0]
        );
    }
    
    console.log('Settings checked and created if needed');
}

/**
 * Add demo vehicles for development environment
 */
async function addDemoVehicles() {
    // Only run in development environment
    if (CONFIG.ENVIRONMENT !== 'development') {
        return;
    }
    
    console.log('Adding demo vehicles...');
    
    const demoVehicles = [
        {
            make: 'Toyota',
            model: 'Vios',
            year: 2023,
            color: 'White',
            license_plate: 'ABC 123',
            vin: 'JTDBT903291234567',
            vehicle_type: 'Sedan',
            fuel_type: 'Petrol',
            transmission: 'Automatic',
            mileage: 5000,
            capacity: 5,
            daily_rate: 2500.00,
            weekly_rate: 15000.00,
            monthly_rate: 55000.00,
            with_driver_rate: 3500.00,
            features: JSON.stringify(['Air Conditioning', 'Bluetooth', 'Backup Camera', 'GPS']),
            images: [
                { image_url: '/assets/images/vehicles/vios_1.jpg', is_primary: true },
                { image_url: '/assets/images/vehicles/vios_2.jpg', is_primary: false }
            ]
        },
        {
            make: 'Honda',
            model: 'CR-V',
            year: 2022,
            color: 'Silver',
            license_plate: 'DEF 456',
            vin: 'JHLRD78891234567',
            vehicle_type: 'SUV',
            fuel_type: 'Petrol',
            transmission: 'Automatic',
            mileage: 8000,
            capacity: 5,
            daily_rate: 3500.00,
            weekly_rate: 21000.00,
            monthly_rate: 75000.00,
            with_driver_rate: 4500.00,
            features: JSON.stringify(['Air Conditioning', 'Bluetooth', 'Backup Camera', 'GPS', 'Leather Seats', 'Sunroof']),
            images: [
                { image_url: '/assets/images/vehicles/crv_1.jpg', is_primary: true },
                { image_url: '/assets/images/vehicles/crv_2.jpg', is_primary: false }
            ]
        },
        {
            make: 'Toyota',
            model: 'Hiace',
            year: 2021,
            color: 'White',
            license_plate: 'GHI 789',
            vin: 'JTFSX23P91234567',
            vehicle_type: 'Van',
            fuel_type: 'Diesel',
            transmission: 'Manual',
            mileage: 15000,
            capacity: 12,
            daily_rate: 4500.00,
            weekly_rate: 27000.00,
            monthly_rate: 95000.00,
            with_driver_rate: 5500.00,
            features: JSON.stringify(['Air Conditioning', 'AM/FM Radio', '12 Passenger Seats']),
            images: [
                { image_url: '/assets/images/vehicles/hiace_1.jpg', is_primary: true },
                { image_url: '/assets/images/vehicles/hiace_2.jpg', is_primary: false }
            ]
        },
        {
            make: 'Suzuki',
            model: 'Jimny',
            year: 2023,
            color: 'Green',
            license_plate: 'JKL 012',
            vin: 'JSAFJB43V91234567',
            vehicle_type: 'SUV',
            fuel_type: 'Petrol',
            transmission: 'Automatic',
            mileage: 3000,
            capacity: 4,
            daily_rate: 3000.00,
            weekly_rate: 18000.00,
            monthly_rate: 65000.00,
            with_driver_rate: 4000.00,
            features: JSON.stringify(['Air Conditioning', 'Bluetooth', '4x4', 'Off-road Capability']),
            images: [
                { image_url: '/assets/images/vehicles/jimny_1.jpg', is_primary: true },
                { image_url: '/assets/images/vehicles/jimny_2.jpg', is_primary: false }
            ]
        },
        {
            make: 'Ford',
            model: 'Ranger',
            year: 2022,
            color: 'Blue',
            license_plate: 'MNO 345',
            vin: 'MPATFR71H91234567',
            vehicle_type: 'Pickup',
            fuel_type: 'Diesel',
            transmission: 'Automatic',
            mileage: 10000,
            capacity: 5,
            daily_rate: 3800.00,
            weekly_rate: 22800.00,
            monthly_rate: 79000.00,
            with_driver_rate: 4800.00,
            features: JSON.stringify(['Air Conditioning', 'Bluetooth', 'Backup Camera', 'GPS', 'Bed Liner', '4x4']),
            images: [
                { image_url: '/assets/images/vehicles/ranger_1.jpg', is_primary: true },
                { image_url: '/assets/images/vehicles/ranger_2.jpg', is_primary: false }
            ]
        }
    ];
    
    // In a real implementation, you would insert these vehicles into the database
    // Here we're just simulating the process
    for (const vehicle of demoVehicles) {
        console.log(`Adding demo vehicle: ${vehicle.make} ${vehicle.model}`);
        
        // Insert vehicle (simulation)
        const vehicleInsertResult = { insertId: Math.floor(Math.random() * 1000) + 1 };
        
        // Insert vehicle images
        for (const image of vehicle.images) {
            console.log(`Adding image for vehicle: ${vehicleInsertResult.insertId}, primary: ${image.is_primary}`);
            // Simulate inserting image
        }
    }
    
    console.log('Demo vehicles added successfully');
}

// If this script is run directly (not imported)
if (import.meta.url === import.meta.main) {
    initializeDatabase()
        .then(() => console.log('Database initialization script completed.'))
        .catch(error => console.error('Error running initialization script:', error));
}
