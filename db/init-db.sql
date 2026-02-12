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

--posts table
CREATE TABLE IF NOT EXISTS posts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_text TEXT DEFAULT NULL,
    privacy ENUM('public', 'friends', 'private') NOT NULL DEFAULT 'public',
    created DATETIME NOT NULL,
    modified DATETIME DEFAULT NULL,
    deleted DATETIME DEFAULT NULL,

    INDEX idx_posts_user_id (user_id),

    CONSTRAINT fk_posts_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--post_images table
CREATE TABLE IF NOT EXISTS post_images (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created DATETIME NOT NULL,

    INDEX idx_post_images_post_id (post_id),

    CONSTRAINT fk_post_images_post
        FOREIGN KEY (post_id)
        REFERENCES posts(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert seed data for users table
-- Note: Passwords are bcrypt hashed using CakePHP's DefaultPasswordHasher
-- Password for all users: 'password123'
-- Hash generated with: password_hash('password123', PASSWORD_DEFAULT)

