-- ============================================
-- SUSTAIN-U DATABASE SCHEMA
-- ============================================

CREATE DATABASE IF NOT EXISTS sustain_u;
USE sustain_u;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','admin') DEFAULT 'student',
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- ISSUES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    category VARCHAR(50) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    building VARCHAR(100) NOT NULL,
    floor VARCHAR(50) NOT NULL,
    room VARCHAR(255) NOT NULL,
    urgency ENUM('can_wait', 'needs_attention', 'emergency') NOT NULL,
    status ENUM('submitted', 'in_progress', 'resolved') DEFAULT 'submitted',
    report_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT SAMPLE ADMIN USER (optional)
-- ============================================
-- Email: admin@srmist.edu.in
-- Password: admin123 (hashed)
INSERT INTO users (name, email, password, role) VALUES
('Administrator', 'admin@srmist.edu.in', '$2y$10$7J.K8YnL2pK5q9M3X8vL/eOq5m1Zm2K8X9pY3vZ4L5q8R6S2U1T0', 'admin')
ON DUPLICATE KEY UPDATE id=id;
