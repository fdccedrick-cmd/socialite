-- Socialite Database Initialization and Seeder Data
-- This file is automatically loaded when the MySQL container starts

-- Ensure we're using the correct database
USE socialite;

-- Create users table (based on CreateUsers migration)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    profile_photo_path VARCHAR(255) DEFAULT NULL,
    created DATETIME NOT NULL,
    modified DATETIME DEFAULT NULL,
    UNIQUE KEY username_unique (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert seed data for users table
-- Note: Passwords are bcrypt hashed using CakePHP's DefaultPasswordHasher
-- Password for all users: 'password123'
-- Hash generated with: password_hash('password123', PASSWORD_DEFAULT)

