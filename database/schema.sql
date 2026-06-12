
CREATE DATABASE IF NOT EXISTS `Crime-map`;
USE `Crime-map`;

DROP TABLE IF EXISTS `dispatches`;
DROP TABLE IF EXISTS `incidents`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'viewer') DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    severity INT NOT NULL CHECK (severity >= 1 AND severity <= 5),
    status ENUM('active', 'resolved', 'dispatched') DEFAULT 'active',
    incident_type ENUM('police', 'fire', 'medical') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_severity (severity),
    INDEX idx_type (incident_type),
    INDEX idx_location (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dispatches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    unit_type ENUM('police', 'fire', 'medical') NOT NULL,
    dispatched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    arrived_at TIMESTAMP NULL,
    status ENUM('en_route', 'on_scene', 'completed') DEFAULT 'en_route',
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
    INDEX idx_incident (incident_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (email, password_hash, full_name, role) VALUES
('admin@crimemap.com', '$2y$10$GOiJjforlfr/NIASrUUD7eOY0OIH2cav7K.Tr1SR9f3th9LVVhIre', 'System Administrator', 'admin')
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO incidents (title, description, latitude, longitude, severity, status, incident_type) VALUES
('Vehicle Theft', 'Vehicle reported stolen from parking lot', 42.6629, 21.1655, 3, 'active', 'police'),
('Building Fire', 'Fire reported in commercial building', 42.6650, 21.1600, 5, 'active', 'fire'),
('Medical Emergency', 'Person unconscious in public park', 42.6590, 21.1710, 4, 'active', 'medical'),
('Assault Report', 'Physical altercation reported', 42.6670, 21.1640, 2, 'resolved', 'police'),
('Gas Leak', 'Strong gas odor detected in residential area', 42.6570, 21.1550, 5, 'dispatched', 'fire')
ON DUPLICATE KEY UPDATE title = title;
