# CarGo Project Structure Documentation

## Overview
This document outlines the complete folder and module structure for the CarGo car rental Progressive Web Application (PWA).

## Project Structure

```
CarGo/
├── README.md                          # Project documentation
├── index.html                         # Main landing page
├── manifest.json                      # PWA manifest
├── 
├── assets/                           # Static assets
│   ├── css/
│   │   ├── main.css                  # Main stylesheet with variables and utilities
│   │   ├── components.css            # Component-specific styles
│   │   └── responsive.css            # Responsive design styles
│   ├── js/
│   │   ├── main.js                   # Main application JavaScript
│   │   ├── utils.js                  # Utility functions
│   │   └── constants.js              # Application constants
│   └── images/
│       ├── logo/                     # Logo variants
│       ├── vehicles/                 # Vehicle images
│       ├── backgrounds/              # Background images
│       └── icons/                    # UI icons
│
├── components/                       # Reusable UI components
│   ├── auth/
│   │   ├── auth.js                   # Authentication component ✓
│   │   ├── login.html                # Login component template
│   │   ├── register.html             # Registration component template
│   │   └── forgot-password.html      # Password reset component
│   ├── booking/
│   │   ├── booking.js                # Booking management ✓
│   │   ├── booking-form.html         # Booking form component
│   │   ├── booking-confirmation.html # Booking confirmation
│   │   └── booking-calendar.html     # Calendar component
│   ├── vehicle/
│   │   ├── vehicle.js                # Vehicle management ✓
│   │   ├── vehicle-card.html         # Vehicle card component
│   │   ├── vehicle-details.html      # Vehicle details modal
│   │   └── vehicle-search.html       # Search and filter component
│   ├── payment/
│   │   ├── payment.js                # Payment processing
│   │   ├── payment-form.html         # Payment form component
│   │   ├── qr-payment.html           # QR code payment
│   │   └── payment-success.html      # Payment success page
│   └── user/
│       ├── profile.js                # User profile management
│       ├── profile-form.html         # Profile editing form
│       ├── user-dashboard.html       # User dashboard component
│       └── user-settings.html        # User settings component
│
├── pages/                           # Individual pages
│   ├── admin/
│   │   ├── dashboard.html            # Admin dashboard ✓
│   │   ├── vehicle-management.html   # Vehicle CRUD operations
│   │   ├── user-management.html      # User management
│   │   ├── booking-management.html   # Booking oversight
│   │   ├── reports.html              # Analytics and reports
│   │   └── settings.html             # Admin settings
│   ├── user/
│   │   ├── dashboard.html            # User dashboard ✓
│   │   ├── search.html               # Vehicle search page
│   │   ├── bookings.html             # User bookings page
│   │   ├── profile.html              # User profile page
│   │   ├── payment-history.html      # Payment history
│   │   └── help.html                 # Help and FAQ
│   └── booking-staff/
│       ├── dashboard.html            # Booking staff dashboard ✓
│       ├── pending-bookings.html     # Pending bookings management
│       ├── active-bookings.html      # Active bookings management
│       ├── calendar.html             # Booking calendar view
│       └── reports.html              # Staff reports
│
├── modules/                         # Core functionality modules
│   ├── analytics/
│   │   ├── analytics.js              # Business analytics ✓
│   │   ├── dashboard-widgets.js      # Dashboard widget components
│   │   ├── chart-generator.js        # Chart generation utilities
│   │   └── report-generator.js       # Report generation
│   ├── api/
│   │   ├── api.js                    # API communication ✓
│   │   ├── endpoints.js              # API endpoint definitions
│   │   ├── mock-data.js              # Mock data for development
│   │   └── db-api.js                 # Database API endpoints ✓
│   ├── database/
│   │   ├── database.js               # Database utility ✓
│   │   └── initialize.js             # Database initialization ✓
│   ├── notifications/
│   │   ├── notification.js           # Notification system
│   │   ├── push-notifications.js     # Push notification handling
│   │   ├── email-templates.js        # Email notification templates
│   │   └── sms-service.js            # SMS notification service
│   ├── storage/
│   │   ├── local-storage.js          # Local storage management
│   │   ├── session-storage.js        # Session storage management
│   │   ├── cache-manager.js          # Cache management
│   │   └── offline-storage.js        # Offline data storage
│   └── utils/
│       ├── validation.js             # Form validation utilities
│       ├── date-formatter.js         # Date formatting utilities
│       ├── currency-formatter.js     # Currency formatting utilities
│       └── error-handler.js          # Error handling utilities
│
├── database/                       # Database schema and documentation
│   ├── cargo_schema.sql            # SQL database schema
│   └── SCHEMA_DOCUMENTATION.md     # Database ER diagram and documentation
│
├── config/
│   ├── app.config.js                 # Main application configuration ✓
│   ├── environment.js                # Environment-specific settings
│   ├── routes.js                     # Application routing
│   └── security.js                  # Security configurations
│
├── icons/                           # PWA icons
│   ├── icon-72x72.png
│   ├── icon-96x96.png
│   ├── icon-128x128.png
│   ├── icon-144x144.png
│   ├── icon-152x152.png
│   ├── icon-192x192.png
│   ├── icon-384x384.png
│   ├── icon-512x512.png
│   ├── apple-touch-icon.png
│   └── favicon.ico
│
├── service-worker.js                 # PWA service worker
├── offline.html                      # Offline fallback page
└── .gitignore                       # Git ignore file
```

## Module Descriptions

### Assets
- **CSS**: Modular stylesheets with Tailwind CSS integration
- **JS**: Core JavaScript files and utilities
- **Images**: Organized image assets for different purposes

### Components
Reusable UI components organized by functionality:

#### Authentication (`components/auth/`)
- **auth.js**: Complete authentication management with login, register, logout, session handling
- Login/register forms, password reset functionality
- JWT token management and automatic refresh

#### Booking (`components/booking/`)
- **booking.js**: Comprehensive booking management system
- Booking creation, modification, cancellation
- Date validation and pricing calculation
- Integration with payment and vehicle systems

#### Vehicle (`components/vehicle/`)
- **vehicle.js**: Vehicle management and search functionality
- Vehicle listing, filtering, and search
- CRUD operations for admin users
- Availability checking and booking integration

#### Payment (`components/payment/`)
- QR code payment processing
- Multiple payment method support
- Transaction history and receipts

#### User (`components/user/`)
- User profile management
- Settings and preferences
- Account information updates

### Pages
Role-specific dashboards and interfaces:

#### Admin Pages (`pages/admin/`)
- **dashboard.html**: Business analytics dashboard with charts and statistics
- Vehicle fleet management
- User and booking oversight
- Comprehensive reporting system

#### User Pages (`pages/user/`)
- **dashboard.html**: Personal dashboard with booking history and quick actions
- Vehicle search and booking interface
- Profile management and settings

#### Booking Staff Pages (`pages/booking-staff/`)
- **dashboard.html**: Booking management interface
- Pending, active, and completed booking management
- Calendar view and scheduling tools

### Core Modules

#### Analytics (`modules/analytics/`)
- **analytics.js**: Business intelligence and reporting
- Dashboard statistics and KPIs
- Chart generation and data visualization
- Export functionality for reports

#### API (`modules/api/`)
- **api.js**: Centralized API communication
- Request/response handling with retry logic
- Authentication token management
- Mock data support for development

#### Database (`modules/database/`)
- **database.js**: Database utility for common operations
- **initialize.js**: Database initialization and seeding

#### Notifications (`modules/notifications/`)
- Push notification system
- Email and SMS integration
- Real-time updates for bookings

#### Storage (`modules/storage/`)
- Local and session storage management
- Offline data synchronization
- Cache management for performance

#### Utilities (`modules/utils/`)
- Form validation
- Date/time utilities
- Currency formatting
- Geolocation services

### Configuration (`config/`)
- **app.config.js**: Centralized application configuration
- Environment-specific settings
- API endpoints and business rules
- Security configurations

## Key Features by Module

### User Roles and Access Control
1. **Admin/Car Rental Owner**
   - Business analytics dashboard
   - Vehicle fleet management
   - Booking oversight
   - Revenue reports
   - User management

2. **Booking Staff**
   - Booking confirmation and management
   - Calendar scheduling
   - Customer communication
   - Operational reports

3. **Users (Customers)**
   - Vehicle search and booking
   - Payment processing
   - Booking history
   - Profile management
   - Transaction reports

### Core Functionalities
- **Progressive Web App** capabilities
- **Offline functionality** with service worker
- **Real-time notifications** for booking updates
- **Multi-role authentication** system
- **Comprehensive analytics** and reporting
- **Mobile-responsive** design
- **QR code payment** integration
- **Advanced vehicle filtering**
- **Calendar-based booking** system

## Development Guidelines

### File Naming Conventions
- Use kebab-case for file names (e.g., `booking-management.html`)
- Use camelCase for JavaScript variables and functions
- Use PascalCase for class names

### Code Organization
- Each module should be self-contained
- Use ES6+ features and modern JavaScript
- Implement proper error handling
- Follow separation of concerns principle

### Responsive Design
- Mobile-first approach
- Tailwind CSS utility classes
- Custom CSS variables for theming

### Performance Optimization
- Lazy loading for non-critical components
- Image optimization
- API response caching
- Service worker for offline functionality

This modular structure ensures scalability, maintainability, and clear separation of concerns for the CarGo car rental application.
