-- Notre Dame - Siena College of Polomolok
-- Earthquake Monitoring System Database Schema

CREATE DATABASE IF NOT EXISTS earthquake_monitoring;
USE earthquake_monitoring;

-- Admin Users Table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seismic Logs Table
CREATE TABLE IF NOT EXISTS seismic_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(50) NOT NULL,
    intensity DECIMAL(10, 2) NOT NULL,
    mmi_level VARCHAR(10) DEFAULT NULL,
    mmi_name VARCHAR(50) DEFAULT NULL,
    percent_g DECIMAL(10, 4) DEFAULT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    alert_sent BOOLEAN DEFAULT FALSE,
    INDEX idx_timestamp (timestamp),
    INDEX idx_intensity (intensity),
    INDEX idx_mmi_level (mmi_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alert Recipients Table
CREATE TABLE IF NOT EXISTS alert_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    category ENUM('student', 'faculty', 'staff', 'admin') DEFAULT 'student',
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_phone (phone_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SMS Log Table
CREATE TABLE IF NOT EXISTS sms_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,  
    log_id INT NOT NULL,
    recipient_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (log_id) REFERENCES seismic_logs(id),
    FOREIGN KEY (recipient_id) REFERENCES alert_recipients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (username: adminSienna, password: admin123)
-- Note: Run create_admin.php to create the admin user with proper password hashing
-- Or manually insert: username: adminSienna, password: admin123

-- Insert sample recipients
INSERT INTO alert_recipients (name, phone_number, category) VALUES
('Admin User', '09171234567', 'admin'),
('Faculty Member', '09181234567', 'faculty'),
('Student User', '09191234567', 'student');
