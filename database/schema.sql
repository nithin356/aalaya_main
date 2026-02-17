-- Aalaya Database Schema

CREATE DATABASE IF NOT EXISTS aalaya_db;
USE aalaya_db;

-- 1. admin_users
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. users
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    digilocker_id VARCHAR(255) UNIQUE,
    digilocker_verified TINYINT(1) DEFAULT 0,
    email VARCHAR(100) UNIQUE,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    aadhaar_number VARCHAR(20),
    pan_number VARCHAR(20),
    dob VARCHAR(20),
    gender VARCHAR(10),
    address TEXT,
    photo_link TEXT,
    referral_code VARCHAR(20) UNIQUE NOT NULL,
    referred_by INT DEFAULT NULL,
    total_points DECIMAL(10,2) DEFAULT 0.00,
    is_banned TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 3. properties
CREATE TABLE IF NOT EXISTS properties (
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

-- 4. advertisements
CREATE TABLE IF NOT EXISTS advertisements (
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

-- 5. enquiries
CREATE TABLE IF NOT EXISTS enquiries (
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

-- 6. referral_transactions
CREATE TABLE IF NOT EXISTS referral_transactions (
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
 
 -- 7. share_transactions (New)
 CREATE TABLE IF NOT EXISTS share_transactions (
     id INT PRIMARY KEY AUTO_INCREMENT,
     user_id INT NOT NULL,
     shares_added INT NOT NULL,
     reason VARCHAR(255),
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
 );
 
 -- 8. bids (New)
 CREATE TABLE IF NOT EXISTS bids (
     id INT PRIMARY KEY AUTO_INCREMENT,
     user_id INT NOT NULL,
     property_id INT NOT NULL,
     bid_amount DECIMAL(15,2) NOT NULL,
     status ENUM('active', 'withdrawn', 'accepted', 'rejected') DEFAULT 'active',
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
     FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
 );
 
 -- 9. invoices
CREATE TABLE IF NOT EXISTS invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    base_amount DECIMAL(10,2),
    gst_amount DECIMAL(10,2),
    description VARCHAR(255),
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    payment_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 7. system_config
CREATE TABLE IF NOT EXISTS system_config (
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
('referral_level1_percentage', '20', 'Level 1 referral percentage'),
('referral_level2_percentage', '10', 'Level 2 referral percentage'),
('referral_max_levels', '2', 'Maximum referral levels'),
('registration_fee', '0', 'Registration fee amount')
ON DUPLICATE KEY UPDATE config_value=VALUES(config_value);

-- Insert a default admin user (password: admin123)
INSERT INTO admin_users (username, password, email, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@aalaya.com', 'System Admin')
ON DUPLICATE KEY UPDATE username=VALUES(username);
