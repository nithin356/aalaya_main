# Aalaya - Property & Advertisement Management Platform

## Project Overview

A Progressive Web Application (PWA) for property and advertisement management with a referral-based network marketing system. The platform features separate admin and user interfaces with DigiLocker integration for user authentication.

---

## Tech Stack

- **Frontend**: HTML5, CSS3, Bootstrap 5 (Fully Responsive), JavaScript (ES6+)
- **Backend**: PHP 8.x
- **Database**: MySQL (via XAMPP)
- **Server**: XAMPP (Installed at `C:\xampp`)
- **Architecture**: PWA with AJAX-based API calls
- **Authentication**: DigiLocker API (User Side), Username/Password (Admin Side)

---

## Project Structure

```
aalaya/
├── config/
│   └── config.ini                 # Database and system configuration
├── admin/
│   ├── index.html                 # Admin login page
│   ├── dashboard.html             # Admin dashboard
│   ├── users.html                 # User management
│   ├── properties.html            # Property management
│   ├── advertisements.html        # Advertisement management
│   ├── css/
│   │   └── admin-style.css
│   └── js/
│       ├── admin-login.js
│       ├── admin-dashboard.js
│       ├── admin-users.js
│       ├── admin-properties.js
│       └── admin-advertisements.js
├── user/
│   ├── index.html                 # User login/register page
│   ├── dashboard.html             # User dashboard
│   ├── properties.html            # Property listing
│   ├── advertisements.html        # Advertisement listing
│   ├── enquiries.html             # Enquiry management
│   ├── profile.html               # User profile with referral
│   ├── css/
│   │   └── user-style.css
│   └── js/
│       ├── user-auth.js
│       ├── user-dashboard.js
│       ├── user-properties.js
│       ├── user-advertisements.js
│       ├── user-enquiries.js
│       └── user-profile.js
├── api/
│   ├── admin/
│   │   ├── login.php
│   │   ├── users.php
│   │   ├── properties.php
│   │   └── advertisements.php
│   ├── user/
│   │   ├── auth.php
│   │   ├── properties.php
│   │   ├── advertisements.php
│   │   ├── enquiries.php
│   │   └── profile.php
│   └── digilocker.php
├── assets/
│   ├── images/
│   │   └── logo-placeholder.png   # Replaceable logo
│   ├── uploads/
│   │   ├── properties/
│   │   └── advertisements/
│   └── icons/                     # PWA icons
├── includes/
│   ├── db.php                     # Database connection
│   ├── functions.php              # Common functions
│   └── auth.php                   # Authentication helpers
├── manifest.json                  # PWA manifest
├── service-worker.js              # PWA service worker
└── database/
    └── schema.sql                 # Database schema
```

---

## Database Schema

### Configuration File (`config/config.ini`)

```ini
[database]
host = localhost
username = root
password =
database = aalaya_db
port = 3306

[paths]
upload_path = C:/xampp/htdocs/aalaya/assets/uploads/
base_url = http://localhost/aalaya/

[referral]
level1_percentage = 20
level2_percentage = 10
max_levels = 2

[images]
property_max_size = 2097152
advertisement_max_size = 2097152
allowed_extensions = jpg,jpeg,png,webp
property_image_width = 800
property_image_height = 600
advertisement_image_width = 1200
advertisement_image_height = 400
```

### Database Tables

#### 1. `admin_users`

```sql
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 2. `users`

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    digilocker_id VARCHAR(255) UNIQUE,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    referral_code VARCHAR(20) UNIQUE NOT NULL,
    referred_by INT DEFAULT NULL,
    total_points DECIMAL(10,2) DEFAULT 0.00,
    is_banned TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### 3. `properties`

```sql
CREATE TABLE properties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    property_type ENUM('residential', 'commercial', 'land', 'other') NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    location VARCHAR(255),
    area DECIMAL(10,2),
    area_unit ENUM('sqft', 'sqm', 'acre') DEFAULT 'sqft',
    bedrooms INT,
    bathrooms INT,
    image_path VARCHAR(255),
    status ENUM('available', 'sold', 'rented') DEFAULT 'available',
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
);
```

#### 4. `advertisements`

```sql
CREATE TABLE advertisements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    company_name VARCHAR(255),
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    image_path VARCHAR(255),
    ad_type ENUM('banner', 'featured', 'standard') DEFAULT 'standard',
    start_date DATE,
    end_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
);
```

#### 5. `enquiries`

```sql
CREATE TABLE enquiries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    enquiry_type ENUM('property', 'advertisement') NOT NULL,
    reference_id INT NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'resolved', 'closed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 6. `referral_transactions`

```sql
CREATE TABLE referral_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    referred_user_id INT NOT NULL,
    level TINYINT(1) NOT NULL,
    points_earned DECIMAL(10,2) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    transaction_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 7. `system_config`

```sql
CREATE TABLE system_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default referral configuration
INSERT INTO system_config (config_key, config_value, description) VALUES
('referral_level1_percentage', '20', 'Level 1 referral percentage'),
('referral_level2_percentage', '10', 'Level 2 referral percentage'),
('referral_max_levels', '2', 'Maximum referral levels');
```

---

## Feature Requirements

### Admin Side

#### 1. Login (`admin/index.html`)

- **Fields**: Username, Password
- **Features**:
  - Form validation
  - Secure password handling
  - Session management
  - Remember me option (optional)
  - Error messaging
- **Endpoint**: `POST /api/admin/login.php`

#### 2. Dashboard (`admin/dashboard.html`)

- **Statistics Cards**:
  - Total Users (Active)
  - Total Properties (Available)
  - Total Advertisements (Active)
  - Total Enquiries (Pending)
- **Recent Activities**: Latest 10 enquiries
- **Charts**: User growth, Property statistics
- **Endpoint**: `GET /api/admin/dashboard.php`

#### 3. User Management (`admin/users.html`)

- **Table Columns**: ID, Name, Email, Referral Code, Status, Actions
- **Features**:
  - View all users
  - Search and filter
  - Ban user (toggle)
  - Soft delete user (is_deleted = 1)
  - View referral tree
  - Pagination
- **Actions**:
  - Ban/Unban: Toggle `is_banned` field
  - Delete: Set `is_deleted = 1` (soft delete)
- **Endpoint**: `GET/POST /api/admin/users.php`

#### 4. Property Management (`admin/properties.html`)

- **Form Fields**:
  - Title (required)
  - Description
  - Property Type (dropdown)
  - Price (required)
  - Location
  - Area + Unit
  - Bedrooms, Bathrooms
  - Image Upload (fixed dimensions: 800x600px)
  - Status (dropdown)
- **Features**:
  - Add new property
  - Edit existing property
  - Delete property
  - View property list with images
  - Image preview before upload
  - Automatic image resizing to fixed dimensions
  - Pagination and search
- **Image Requirements**:
  - Fixed size: 800x600px
  - Max file size: 2MB
  - Formats: JPG, PNG, WebP
  - Auto-resize on upload
- **Endpoint**: `GET/POST/PUT/DELETE /api/admin/properties.php`

#### 5. Advertisement Management (`admin/advertisements.html`)

- **Form Fields**:
  - Title (required)
  - Description
  - Company Name
  - Contact Email & Phone
  - Image Upload (fixed dimensions: 1200x400px)
  - Ad Type (dropdown)
  - Start Date, End Date
  - Active status
- **Features**:
  - Add new advertisement
  - Edit existing advertisement
  - Delete advertisement
  - View advertisement list with images
  - Image preview before upload
  - Automatic image resizing to fixed dimensions
  - Pagination and search
- **Image Requirements**:
  - Fixed size: 1200x400px (banner format)
  - Max file size: 2MB
  - Formats: JPG, PNG, WebP
  - Auto-resize on upload
- **Endpoint**: `GET/POST/PUT/DELETE /api/admin/advertisements.php`

---

### User Side

#### 1. Login/Register (`user/index.html`)

- **DigiLocker Integration**:
  - OAuth 2.0 flow
  - Fetch user details from DigiLocker
  - Auto-populate profile information
  - Generate unique referral code on registration
- **Registration Process**:
  - Check for referral code parameter in URL
  - Link to referring user
  - Create user account
  - Generate unique referral code
- **Endpoint**: `POST /api/user/auth.php`, `POST /api/digilocker.php`

#### 2. Dashboard (`user/dashboard.html`)

- **Overview Cards**:
  - Total Points
  - Referrals Count (Level 1 & 2)
  - Enquiries Count
  - Referral Link (copyable)
- **Recent Properties**: Latest 6 properties
- **Recent Advertisements**: Latest 4 advertisements
- **Endpoint**: `GET /api/user/dashboard.php`

#### 3. Property Listing (`user/properties.html`)

- **Display**:
  - Grid/List view toggle
  - Property cards with fixed-size images (800x600px)
  - Title, price, location, area, bedrooms/bathrooms
  - "Enquire Now" button
- **Features**:
  - Filter by type, price range, location
  - Search functionality
  - Pagination
  - Property detail modal
- **Image Display**: Consistent 800x600px display ensuring no UI breakage
- **Endpoint**: `GET /api/user/properties.php`

#### 4. Advertisement Listing (`user/advertisements.html`)

- **Display**:
  - Grid view with fixed-size banner images (1200x400px)
  - Title, company name, description
  - "Enquire Now" button
- **Features**:
  - Filter by ad type, date range
  - Search functionality
  - Pagination
  - Advertisement detail modal
- **Image Display**: Consistent 1200x400px banner display ensuring no UI breakage
- **Endpoint**: `GET /api/user/advertisements.php`

#### 5. Enquiry Management (`user/enquiries.html`)

- **Create Enquiry**:
  - Select enquiry type (Property/Advertisement)
  - Select property or advertisement
  - Subject
  - Message
  - Submit button
- **View Enquiries**:
  - Table with: Type, Reference, Subject, Status, Date
  - Status badges
  - View detail modal
  - Pagination
- **Endpoint**: `GET/POST /api/user/enquiries.php`

#### 6. User Profile (`user/profile.html`)

- **Profile Information**:
  - Name, Email, Phone (from DigiLocker)
  - Edit profile option
- **Referral System**:
  - Unique Referral Code (displayed prominently)
  - Referral Link (copyable with click)
  - Share buttons (WhatsApp, Email, Copy)
  - Total Points earned
- **Referral Tree Visualization**:
  - Level 1 Referrals list with points earned (20%)
  - Level 2 Referrals list with points earned (10%)
  - Total referrals count
- **Points History Table**:
  - Date, Referred User, Level, Points Earned
  - Pagination
- **Endpoint**: `GET /api/user/profile.php`

---

## Referral System Logic

### Two-Level Network Marketing Structure

#### Level 1 (Direct Referral) - 20%

- User A invites User B using referral code
- User B registers and performs qualifying action
- User A earns 20% points

#### Level 2 (Indirect Referral) - 10%

- User B (who was referred by User A) invites User C
- User C registers and performs qualifying action
- User A earns 10% points (from User C's action)
- User B earns 20% points (from User C's action)

#### Configurable Settings (Admin)

- `referral_level1_percentage`: Default 20%
- `referral_level2_percentage`: Default 10%
- `referral_max_levels`: Fixed at 2
- Can be modified in `system_config` table

#### Points Calculation Example

```
Base Points per Enquiry: 100

User A refers User B (Level 1)
- User B creates enquiry
- User A gets: 100 × 20% = 20 points

User B refers User C (Level 2 for User A)
- User C creates enquiry
- User B gets: 100 × 20% = 20 points (Level 1)
- User A gets: 100 × 10% = 10 points (Level 2)
```

#### Implementation Notes

- Referral code generated on registration: `AAL{USER_ID}{RANDOM_5_DIGITS}`
- Track referral chain in `users.referred_by` field
- Log all transactions in `referral_transactions` table
- Display referral tree up to 2 levels only
- Prevent circular referrals

---

## Technical Requirements

### Frontend (HTML/CSS/JS/Bootstrap)

#### Responsive Design

- Mobile-first approach
- Breakpoints: 576px, 768px, 992px, 1200px
- Bootstrap 5 grid system
- Touch-friendly UI elements

#### JavaScript Architecture

- **Separation of Concerns**: All JS in separate files under `/admin/js/` and `/user/js/`
- **No inline JavaScript**: Use event listeners and data attributes
- **AJAX Communication**: All API calls via Fetch API or XMLHttpRequest
- **Error Handling**: Centralized error handling for all AJAX calls
- **Loading States**: Show spinners/skeletons during API calls
- **Form Validation**: Client-side validation before API submission

#### PWA Requirements

- `manifest.json` with app icons (192x192, 512x512)
- Service Worker for offline capability
- Caching strategy for static assets
- Installation prompt
- Responsive meta tags

#### Image Handling

- **Fixed Dimensions**: All images resized on upload
- **Object-fit CSS**: Use `object-fit: cover` for consistent display
- **Lazy Loading**: Implement lazy loading for property/ad listings
- **Placeholder**: Use loading placeholder while images load
- **Error Fallback**: Display default image if upload fails

### Backend (PHP)

#### API Structure

- RESTful API design
- JSON response format
- HTTP status codes (200, 201, 400, 401, 403, 404, 500)
- CORS headers if needed
- Input validation and sanitization
- Prepared statements for all database queries

#### Response Format

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {},
  "errors": []
}
```

#### Authentication

- Session-based authentication for admin
- DigiLocker OAuth 2.0 for users
- JWT tokens (optional for enhanced security)
- CSRF protection
- Password hashing with `password_hash()` and `PASSWORD_BCRYPT`

#### File Upload Handling

```php
// Image resize function using GD library
function resizeImage($sourcePath, $targetPath, $width, $height) {
    // Implementation for automatic resizing
    // Maintain aspect ratio with cropping
    // Save as optimized JPG/PNG/WebP
}
```

#### Security Measures

- SQL injection prevention (prepared statements)
- XSS prevention (htmlspecialchars)
- CSRF tokens
- File upload validation
- Rate limiting on API endpoints
- Input sanitization

### Database (MySQL)

#### Connection (`includes/db.php`)

```php
<?php
// Read config.ini
$config = parse_ini_file('../config/config.ini', true);

// PDO connection
$dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['database']};charset=utf8mb4";
$pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
]);
```

#### Soft Delete Pattern

- Use `is_deleted = 1` instead of actual deletion
- Filter out deleted records in all queries
- Admin can view deleted records in a separate section (optional)

---

## UI/UX Guidelines

### Corporate & Modern Design

- **Color Scheme**: Professional blue/gray palette (customizable)
- **Typography**: Clean sans-serif fonts (Inter, Roboto, or system fonts)
- **Spacing**: Consistent padding and margins using Bootstrap utilities
- **Cards**: Shadow effects for depth (box-shadow)
- **Buttons**: Clear CTAs with hover effects
- **Icons**: Use Bootstrap Icons or Font Awesome
- **Logo Placeholder**: Prominent placement with easy replacement

### Logo Implementation

```html
<img
  src="/assets/images/logo-placeholder.png"
  alt="Aalaya"
  class="logo"
  id="company-logo"
/>
```

- SVG or PNG format
- Dimensions: 200x60px (header), 120x120px (login pages)
- Replace placeholder with actual logo by swapping file

### Image Display Consistency

```css
/* Property Images */
.property-image {
  width: 100%;
  height: 450px;
  object-fit: cover;
  border-radius: 8px;
}

/* Advertisement Banners */
.advertisement-banner {
  width: 100%;
  height: 300px;
  object-fit: cover;
  border-radius: 8px;
}

/* Thumbnail Views */
.property-thumbnail {
  width: 100%;
  height: 200px;
  object-fit: cover;
}
```

### Loading States

- Skeleton screens for list loading
- Spinner overlays for form submissions
- Progress bars for file uploads
- Disabled states for buttons during processing

---

## Development Phases

### Phase 1: Setup & Configuration (Week 1)

- [x] Install XAMPP at `C:\xampp`
- [/] Create database schema (User needs to import)
- [x] Setup project folder structure
- [x] Create `config.ini`
- [x] Initialize Git repository
- [x] Create PWA manifest and service worker
- [x] Create root index.php redirect

### Phase 2: Admin Panel (Week 2-3)

- [ ] Admin login system
- [ ] Admin dashboard with statistics
- [ ] User management (view, ban, delete)
- [ ] Property management (CRUD operations)
- [ ] Advertisement management (CRUD operations)
- [ ] Image upload and resize functionality

### Phase 3: User Panel (Week 4-5)

- [ ] DigiLocker integration
- [ ] User authentication and registration
- [ ] User dashboard
- [ ] Property listing and enquiry
- [ ] Advertisement listing and enquiry
- [ ] Enquiry management

### Phase 4: Referral System (Week 6)

- [x] Referral code generation
- [x] Referral link sharing
- [x] Two-level tracking logic
- [x] Points calculation (Percentage-based awards to referrers)
- [x] Share Conversion (Points -> Shares logic)
- [ ] Referral tree visualization (Admin/User)
- [x] Points history (Referral Transactions)

### Phase 5: Testing & Deployment (Week 7)

- [ ] Unit testing (PHP functions)
- [ ] Integration testing (API endpoints)
- [ ] UI/UX testing (responsive design)
- [ ] Security testing (SQL injection, XSS)
- [ ] Performance optimization
- [ ] PWA testing (offline mode)

---

## Testing Checklist

### Functional Testing

- [ ] Admin login/logout
- [ ] User authentication via DigiLocker
- [ ] Property CRUD operations
- [ ] Advertisement CRUD operations
- [ ] Enquiry creation and management
- [ ] Referral code generation
- [ ] Points calculation accuracy
- [ ] Soft delete functionality
- [ ] Ban/Unban user functionality

### Security Testing

- [ ] SQL injection attempts
- [ ] XSS vulnerability checks
- [ ] CSRF protection validation
- [ ] File upload validation (type, size)
- [ ] Session hijacking prevention
- [ ] Password strength enforcement

### Responsive Testing

- [ ] Mobile devices (375px, 414px)
- [ ] Tablets (768px, 1024px)
- [ ] Desktop (1920px)
- [ ] Image consistency across devices
- [ ] Form usability on touch devices

### Performance Testing

- [ ] Page load time < 3 seconds
- [ ] Image optimization
- [ ] Database query optimization
- [ ] AJAX call response time
- [ ] Concurrent user handling

---

## Deployment Instructions

### Local Development (XAMPP)

1. Copy project to `C:\xampp\htdocs\aalaya`
2. Import `database/schema.sql` via phpMyAdmin
3. Configure `config/config.ini` with database credentials
4. Start Apache and MySQL from XAMPP Control Panel
5. Access admin: `http://localhost/aalaya/admin`
6. Access user: `http://localhost/aalaya/user`

### Production Deployment

1. Upload files to web server
2. Update `config.ini` with production database credentials
3. Set proper file permissions (755 for directories, 644 for files)
4. Configure SSL certificate for HTTPS
5. Update `base_url` in config
6. Test DigiLocker OAuth redirect URLs
7. Enable PWA service worker

---

## API Endpoints Reference

### Admin API

| Method | Endpoint                                  | Description            |
| ------ | ----------------------------------------- | ---------------------- |
| POST   | `/api/admin/login.php`                    | Admin login            |
| GET    | `/api/admin/dashboard.php`                | Dashboard statistics   |
| GET    | `/api/admin/users.php`                    | Get all users          |
| PUT    | `/api/admin/users.php?action=ban&id={id}` | Ban/Unban user         |
| DELETE | `/api/admin/users.php?id={id}`            | Soft delete user       |
| GET    | `/api/admin/properties.php`               | Get all properties     |
| POST   | `/api/admin/properties.php`               | Create property        |
| PUT    | `/api/admin/properties.php?id={id}`       | Update property        |
| DELETE | `/api/admin/properties.php?id={id}`       | Delete property        |
| GET    | `/api/admin/advertisements.php`           | Get all advertisements |
| POST   | `/api/admin/advertisements.php`           | Create advertisement   |
| PUT    | `/api/admin/advertisements.php?id={id}`   | Update advertisement   |
| DELETE | `/api/admin/advertisements.php?id={id}`   | Delete advertisement   |

### User API

| Method | Endpoint                               | Description                      |
| ------ | -------------------------------------- | -------------------------------- |
| POST   | `/api/user/auth.php?action=login`      | User login via DigiLocker        |
| POST   | `/api/user/auth.php?action=register`   | User registration                |
| GET    | `/api/user/dashboard.php`              | User dashboard data              |
| GET    | `/api/user/properties.php`             | Get properties list              |
| GET    | `/api/user/properties.php?id={id}`     | Get property details             |
| GET    | `/api/user/advertisements.php`         | Get advertisements list          |
| GET    | `/api/user/advertisements.php?id={id}` | Get advertisement details        |
| GET    | `/api/user/enquiries.php`              | Get user enquiries               |
| POST   | `/api/user/enquiries.php`              | Create new enquiry               |
| GET    | `/api/user/profile.php`                | Get user profile & referral info |
| PUT    | `/api/user/profile.php`                | Update user profile              |

---

## Maintenance & Updates

### Regular Maintenance Tasks

- Database backup (daily automated)
- Log file rotation
- Image cleanup (orphaned files)
- Session cleanup
- Cache clearing

### Configuration Updates

- Referral percentages via `system_config` table
- Image size limits in `config.ini`
- Upload paths and base URL
- DigiLocker API credentials

---

## Support & Documentation

### Developer Documentation

- API documentation (Postman collection)
- Database schema diagrams
- Code comments and inline documentation
- README.md with setup instructions

### User Documentation

- Admin user manual
- User guide for referral system
- FAQ section
- Video tutorials (optional)

---

## Notes

- All monetary values stored as DECIMAL(15,2)
- All timestamps use MySQL TIMESTAMP with automatic updates
- Foreign keys with appropriate CASCADE rules
- Indexes on frequently queried columns (email, referral_code, user_id)
- Transaction support for critical operations (referral points)
- Logging system for admin actions and user activities
- Email notifications for enquiries (optional enhancement)
- SMS notifications for referrals (optional enhancement)

---

**Project Timeline**: 7 weeks (estimated)  
**Primary Technologies**: PHP, MySQL, Bootstrap, JavaScript, AJAX, PWA  
**Development Environment**: XAMPP (C:\xampp)  
**Target Browsers**: Chrome, Firefox, Safari, Edge (latest versions)  
**Mobile Support**: iOS 12+, Android 8+
