CREATE DATABASE IF NOT EXISTS ctu_lost_found;
USE ctu_lost_found;

-- Create tables only if they don't exist to prevent duplication
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    student_id  VARCHAR(20)  DEFAULT NULL,
    course      VARCHAR(100) DEFAULT NULL,
    year_level  VARCHAR(20)  DEFAULT NULL,
    phone       VARCHAR(20)  DEFAULT NULL,
    avatar      VARCHAR(255) DEFAULT NULL,
    is_admin    TINYINT(1)   DEFAULT 0,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    type        ENUM('lost','found') NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category    VARCHAR(60)  NOT NULL,
    location    VARCHAR(150) NOT NULL,
    location_zone VARCHAR(100) DEFAULT NULL, -- For campus taxonomy
    location_details VARCHAR(255) DEFAULT NULL, -- Specific room/landmark details
    image       VARCHAR(255) DEFAULT NULL,
    contact_number VARCHAR(11) NULL,
    status      ENUM('active','claimed','returned') DEFAULT 'active',
    -- Feature 2: Secret Identifier & Anti-Fraud
    verification_question VARCHAR(255) NULL,
    verification_answer TEXT NULL, -- Encrypted or hashed
    -- Feature 4: Office Custody
    custody_office VARCHAR(100) NULL, -- Security, SAS, SSG, Dean
    custody_reference VARCHAR(50) NULL, -- Ref #CTU-2026-089
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_type    (type),
    INDEX idx_status  (status),
    INDEX idx_category (category),
    INDEX idx_location_zone (location_zone),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS claims (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT NOT NULL,
    claimant_id INT NOT NULL,
    description TEXT NOT NULL,
    -- Feature 2: Secret Identifier & Anti-Fraud
    verification_answer TEXT NULL, -- Claimant's answer to verification question
    proof_image VARCHAR(255) NULL, -- Optional proof upload
    status      ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id)     REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (claimant_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_claim (post_id, claimant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Feature 1: Automatic Smart Matching
CREATE TABLE IF NOT EXISTS post_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lost_post_id INT NOT NULL,
    found_post_id INT NOT NULL,
    match_score DECIMAL(5,2) NOT NULL COMMENT 'Match confidence percentage (0-100)',
    status ENUM('pending', 'confirmed', 'dismissed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lost_post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (found_post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_score (match_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Feature 6: Secure In-App Messaging
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claim_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (claim_id) REFERENCES claims(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_claim_id (claim_id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_receiver_id (receiver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Feature 5: Student ID Detection Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('id_match', 'new_match', 'claim_update', 'system') DEFAULT 'system',
    related_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add new columns to existing tables if they don't exist (for existing installations)
-- Check and add location_zone to posts table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'ctu_lost_found' 
                   AND TABLE_NAME = 'posts' 
                   AND COLUMN_NAME = 'location_zone');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE posts ADD COLUMN location_zone VARCHAR(100) DEFAULT NULL COMMENT ''For campus taxonomy'' AFTER location',
    'SELECT ''Column location_zone already exists''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add location_details to posts table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'ctu_lost_found' 
                   AND TABLE_NAME = 'posts' 
                   AND COLUMN_NAME = 'location_details');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE posts ADD COLUMN location_details VARCHAR(255) DEFAULT NULL COMMENT ''Specific room/landmark details'' AFTER location_zone',
    'SELECT ''Column location_details already exists''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add verification_question to posts table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'ctu_lost_found' 
                   AND TABLE_NAME = 'posts' 
                   AND COLUMN_NAME = 'verification_question');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE posts ADD COLUMN verification_question VARCHAR(255) NULL COMMENT ''Feature 2: Secret Identifier'' AFTER contact_number',
    'SELECT ''Column verification_question already exists''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add verification_answer to posts table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'ctu_lost_found' 
                   AND TABLE_NAME = 'posts' 
                   AND COLUMN_NAME = 'verification_answer');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE posts ADD COLUMN verification_answer TEXT NULL COMMENT ''Encrypted or hashed answer'' AFTER verification_question',
    'SELECT ''Column verification_answer already exists''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add custody_office to posts table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'ctu_lost_found' 
                   AND TABLE_NAME = 'posts' 
                   AND COLUMN_NAME = 'custody_office');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE posts ADD COLUMN custody_office VARCHAR(100) NULL COMMENT ''Feature 4: Office Custody - Security, SAS, SSG, Dean'' AFTER verification_answer',
    'SELECT ''Column custody_office already exists''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add custody_reference to posts table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'ctu_lost_found' 
                   AND TABLE_NAME = 'posts' 
                   AND COLUMN_NAME = 'custody_reference');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE posts ADD COLUMN custody_reference VARCHAR(50) NULL COMMENT ''Ref #CTU-2026-089'' AFTER custody_office',
    'SELECT ''Column custody_reference already exists''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add contact_number to posts table (if missing from original schema)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'ctu_lost_found' 
                   AND TABLE_NAME = 'posts' 
                   AND COLUMN_NAME = 'contact_number');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE posts ADD COLUMN contact_number VARCHAR(11) NULL AFTER image',
    'SELECT ''Column contact_number already exists''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add verification_answer to claims table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'ctu_lost_found' 
                   AND TABLE_NAME = 'claims' 
                   AND COLUMN_NAME = 'verification_answer');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE claims ADD COLUMN verification_answer TEXT NULL COMMENT ''Claimant''s answer to verification question'' AFTER description',
    'SELECT ''Column verification_answer already exists in claims''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add proof_image to claims table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'ctu_lost_found' 
                   AND TABLE_NAME = 'claims' 
                   AND COLUMN_NAME = 'proof_image');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE claims ADD COLUMN proof_image VARCHAR(255) NULL COMMENT ''Optional proof upload'' AFTER verification_answer',
    'SELECT ''Column proof_image already exists in claims''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for location_zone if it doesn't exist
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                      WHERE TABLE_SCHEMA = 'ctu_lost_found' 
                      AND TABLE_NAME = 'posts' 
                      AND INDEX_NAME = 'idx_location_zone');

SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE posts ADD INDEX idx_location_zone (location_zone)',
    'SELECT ''Index idx_location_zone already exists''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for category if it doesn't exist
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                      WHERE TABLE_SCHEMA = 'ctu_lost_found' 
                      AND TABLE_NAME = 'posts' 
                      AND INDEX_NAME = 'idx_category');

SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE posts ADD INDEX idx_category (category)',
    'SELECT ''Index idx_category already exists''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Email: admin@ctu.edu.ph | Password: ctulostfound1
-- Insert admin user only if they don't exist to prevent duplicates
INSERT IGNORE INTO users (name, email, password, is_admin)
VALUES (
    'Administrator',
    'admin@ctu.edu.ph',
    '$2y$10$4dt55TE5lYoQ3THzJpwBYOE0H8Gdv3J89HdqnaNPeGKKLetDqRh76',
    1
);



