# CarGo Database Schema Documentation

This document outlines the database schema for the CarGo car rental platform.

## Entity-Relationship Diagram

```
+----------------+     +----------------+     +----------------+
|     USERS      |     |     ROLES      |     |  PERMISSIONS   |
+----------------+     +----------------+     +----------------+
| user_id (PK)   |     | role_id (PK)   |     | permission_id  |
| role_id (FK)   |<----|                |---->| (PK)           |
| email          |     | role_name      |     |                |
| password_hash  |     | description    |     | permission_name|
| first_name     |     | created_at     |     | description    |
| last_name      |     +----------------+     | created_at     |
| phone          |                            +----------------+
| address        |                                   ^
| city           |                                   |
| ...            |                                   |
+----------------+                            +----------------+
        |                                     | ROLE_PERMISSIONS|
        |                                     +----------------+
        |                                     | role_id (PK,FK)|
        v                                     | permission_id  |
+----------------+                            | (PK,FK)        |
|  USER_DEVICES  |                            +----------------+
+----------------+
| device_id (PK) |
| user_id (FK)   |
| device_token   |
| device_type    |
| is_active      |
| ...            |
+----------------+

+----------------+     +----------------+     +----------------+
|    VEHICLES    |     |   BOOKINGS     |     |    PAYMENTS    |
+----------------+     +----------------+     +----------------+
| vehicle_id (PK)|     | booking_id (PK)|     | payment_id (PK)|
| make           |     | user_id (FK)   |     | booking_id (FK)|
| model          |<----| vehicle_id (FK)|---->| amount         |
| year           |     | booking_number |     | payment_method |
| color          |     | start_date     |     | payment_status |
| license_plate  |     | end_date       |     | ...            |
| ...            |     | ...            |     |                |
+----------------+     +----------------+     +----------------+
        |                     |                      |
        |                     |                      |
        v                     v                      v
+----------------+     +----------------+     +----------------+
| VEHICLE_IMAGES |     |    REVIEWS     |     | NOTIFICATIONS  |
+----------------+     +----------------+     +----------------+
| image_id (PK)  |     | review_id (PK) |     | notification_id|
| vehicle_id (FK)|     | booking_id (FK)|     | (PK)           |
| image_url      |     | user_id (FK)   |     | user_id (FK)   |
| is_primary     |     | vehicle_id (FK)|     | title          |
| ...            |     | rating         |     | message        |
+----------------+     | ...            |     | ...            |
                       +----------------+     +----------------+

+----------------+     +----------------+     +----------------+
|  MAINTENANCE   |     |   ANALYTICS    |     |   PROMOTIONS   |
|    RECORDS     |     |    EVENTS      |     |                |
+----------------+     +----------------+     +----------------+
| record_id (PK) |     | event_id (PK)  |     | promotion_id   |
| vehicle_id (FK)|     | user_id (FK)   |     | (PK)           |
| maintenance_   |     | event_type     |     | name           |
| type           |     | event_data     |     | code           |
| ...            |     | ...            |     | discount_type  |
+----------------+     +----------------+     | ...            |
                                              +----------------+

+----------------+     +----------------+     +----------------+
|   DOCUMENTS    |     |   LOCATIONS    |     |    SETTINGS    |
+----------------+     +----------------+     +----------------+
| document_id    |     | location_id    |     | setting_id     |
| (PK)           |     | (PK)           |     | (PK)           |
| user_id (FK)   |     | name           |     | setting_key    |
| document_type  |     | address        |     | setting_value  |
| document_url   |     | city           |     | setting_group  |
| ...            |     | ...            |     | ...            |
+----------------+     +----------------+     +----------------+

+----------------+
|   AUDIT_LOGS   |
+----------------+
| log_id (PK)    |
| user_id (FK)   |
| action         |
| entity_type    |
| entity_id      |
| ...            |
+----------------+
```

## Table Descriptions

### Users
Stores information about all system users including customers, admins, and booking staff.

### Roles
Defines different user roles in the system (admin, booking_staff, user, guest).

### Permissions
Stores individual permissions that can be assigned to roles.

### Role_Permissions
Junction table that links roles to permissions (many-to-many).

### Vehicles
Contains details about all vehicles available for rental.

### Vehicle_Images
Stores image URLs for vehicle photos, with one vehicle having multiple images.

### Bookings
Tracks all booking information including dates, locations, and status.

### Payments
Records all payment transactions linked to bookings.

### Notifications
Stores notifications sent to users.

### User_Devices
Keeps track of user devices for push notifications.

### Reviews
Stores vehicle and service reviews from users after completed bookings.

### Maintenance_Records
Tracks vehicle maintenance history.

### Analytics_Events
Records user actions and events for analytics purposes.

### Promotions
Stores promotional codes and discounts.

### Documents
Manages user document uploads (driving licenses, ID proofs, etc.).

### Locations
Stores pickup and drop-off locations.

### Settings
Maintains system-wide configuration settings.

### Audit_Logs
Records system changes for audit purposes.

## Key Relationships

1. **Users to Roles**: Many-to-one (Each user has one role)
2. **Roles to Permissions**: Many-to-many (via Role_Permissions junction table)
3. **Users to Bookings**: One-to-many (One user can have multiple bookings)
4. **Vehicles to Bookings**: One-to-many (One vehicle can have multiple bookings)
5. **Bookings to Payments**: One-to-many (One booking can have multiple payments)
6. **Users to Notifications**: One-to-many (One user can have multiple notifications)
7. **Vehicles to Vehicle_Images**: One-to-many (One vehicle can have multiple images)
8. **Bookings to Reviews**: One-to-one (One booking can have one review)
9. **Vehicles to Maintenance_Records**: One-to-many (One vehicle can have multiple maintenance records)

## Data Integrity Rules

1. Bookings can only be made for available vehicles.
2. Vehicle availability is automatically updated when bookings are created or completed.
3. Reviews can only be submitted for completed bookings.
4. Payment records are linked to specific bookings.
5. Users must have valid roles.
6. Foreign key constraints ensure referential integrity.
7. Deleted users' data is preserved for bookings and payment history.
8. Automatic timestamps track creation and modification times.

## Indexing Strategy

Indexes are created on frequently queried fields to improve performance:
- User email and role
- Vehicle type and availability
- Booking status and dates
- Payment status
- Notification user
- Analytics event types

## Future Extensions

The schema is designed to support future additions:
- Vehicle location tracking
- Loyalty program
- Insurance options
- Multilingual support
- Integration with external services
