-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255),
    name VARCHAR(255) NOT NULL,
    google_id VARCHAR(255) UNIQUE,
    picture VARCHAR(255),
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expires DATETIME,
    created_at DATETIME NOT NULL,
    updated_at DATETIME,
    last_login DATETIME,
    INDEX idx_email (email),
    INDEX idx_google_id (google_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Devices table
CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(8) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    last_sync DATETIME,
    created_at DATETIME NOT NULL,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_device_id (device_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Device settings table
CREATE TABLE device_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(8) NOT NULL,
    setting_key VARCHAR(50) NOT NULL,
    setting_value TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE,
    UNIQUE KEY unique_device_setting (device_id, setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alarms table
CREATE TABLE alarms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(8) NOT NULL,
    time TIME NOT NULL,
    label VARCHAR(255),
    days JSON,
    enabled BOOLEAN DEFAULT TRUE,
    sound VARCHAR(255) DEFAULT 'default.mp3',
    rgb_enabled BOOLEAN DEFAULT TRUE,
    rgb_color VARCHAR(7) DEFAULT '#FF0000',
    created_at DATETIME NOT NULL,
    updated_at DATETIME,
    FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE,
    INDEX idx_device_time (device_id, time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Calendar credentials table
CREATE TABLE calendar_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    access_token TEXT NOT NULL,
    refresh_token TEXT,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Weather cache table
CREATE TABLE weather_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(255) NOT NULL,
    data JSON NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_location_time (location, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Themes table
CREATE TABLE themes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    colors JSON NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alarm sounds table
CREATE TABLE alarm_sounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL UNIQUE,
    user_id INT,
    is_default BOOLEAN DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default themes
INSERT INTO themes (name, description, colors, is_default, created_at) VALUES
('Default', 'Default light theme', 
 '{"primary": "#007bff", "background": "#ffffff", "text": "#333333", "accent": "#17a2b8"}',
 TRUE, NOW()),
('Dark', 'Dark theme', 
 '{"primary": "#375a7f", "background": "#222222", "text": "#ffffff", "accent": "#00bc8c"}',
 FALSE, NOW()),
('Nature', 'Nature-inspired theme', 
 '{"primary": "#2ecc71", "background": "#f5f5f5", "text": "#2c3e50", "accent": "#e67e22"}',
 FALSE, NOW());

-- Insert default alarm sounds
INSERT INTO alarm_sounds (name, filename, is_default, created_at) VALUES
('Classic Beep', 'classic-beep.mp3', TRUE, NOW()),
('Digital', 'digital.mp3', TRUE, NOW()),
('Gentle Wake', 'gentle-wake.mp3', TRUE, NOW()); 