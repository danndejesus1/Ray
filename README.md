# CarGo: Car Rental Service Web Application

## Project Overview
CarGo is a Progressive Web Application (PWA) for car rental services with integrated business analytics designed to address car-rental industry challenges and optimize operations.

## Main Objectives
- Develop a user-friendly booking module for customers and car rental owners
- Create a dashboard with insights into user preferences, rental trends, and performance
- Assess platform quality based on ISO/IEC 25010 Standard

## Target Users
- **Tourists**: Easy, affordable, and reliable transportation
- **Car Rental Owners**: Enhanced fleet management and customer reach
- **Researchers**: Case study for technology integration in car rental industry

## Project Structure
```
CarGo/
├── assets/                 # Static assets
│   ├── css/               # Stylesheets
│   ├── js/                # Main JavaScript files
│   └── images/            # Images and media
├── components/            # Reusable UI components
│   ├── auth/              # Authentication components
│   ├── booking/           # Booking-related components
│   ├── vehicle/           # Vehicle management components
│   ├── payment/           # Payment processing components
│   └── user/              # User profile components
├── database/              # Database schema and documentation
│   ├── cargo_schema.sql  # SQL database schema
│   └── SCHEMA_DOCUMENTATION.md # Database documentation
├── pages/                 # Individual pages
│   ├── admin/             # Admin dashboard pages
│   ├── user/              # User pages
│   └── booking-staff/     # Booking staff pages
├── modules/               # Core functionality modules
│   ├── analytics/         # Business analytics
│   ├── api/               # API communication
│   ├── database/          # Database utilities
│   ├── notifications/     # Notification system
│   ├── storage/           # Data storage management
│   └── utils/             # Utility functions
├── config/                # Configuration files
├── icons/                 # PWA icons
├── index.html            # Main entry point
└── manifest.json         # PWA manifest
```

## Features by User Role

### Admin/Car Rental Owner
- Business Analytics Dashboard
- Rental Car Management
- Reports Generation
- Account Management
- Payment Transactions

### Booking Staff
- Booking Management (View, Confirm, Modify, Cancel)

### Users
- Account Registration/Login
- Vehicle Search & Filtering
- Booking System
- Payment Processing (QR Code)
- Booking History
- Rating/Review System
- Transaction Logs
- Dispute Reports

## Technology Stack
- HTML5, CSS3, JavaScript (ES6+)
- Tailwind CSS for styling
- PWA capabilities
- External payment gateway integration
- Real-time notifications
- SQL database (SQLite for development, PostgreSQL for production)

## Database Schema
The CarGo platform uses a relational database with the following main tables:
- **Users**: Stores user information with different role types
- **Vehicles**: Contains all vehicle details including specifications and rates
- **Bookings**: Tracks all booking information and status
- **Payments**: Records payment transactions
- **Notifications**: Manages user notifications
- **Analytics**: Stores event data for business intelligence

For complete database documentation, see `database/SCHEMA_DOCUMENTATION.md`.

## Geographic Focus
Initial rollout: Metro Manila, Philippines
Future expansion: Other areas based on feedback

## Development Setup
1. Clone the repository
2. Open `index.html` in a web browser
3. For development, use a local server for best PWA experience
