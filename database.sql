CREATE DATABASE IF NOT EXISTS ctu_lost_found;
USE ctu_lost_found;

CREATE TABLE users (
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

CREATE TABLE posts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    type        ENUM('lost','found') NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category    VARCHAR(60)  NOT NULL,
    location    VARCHAR(150) NOT NULL,
    image       VARCHAR(255) DEFAULT NULL,
    status      ENUM('active','claimed','returned') DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_type    (type),
    INDEX idx_status  (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE posts 
ADD contact_number VARCHAR(11) NULL;

CREATE TABLE claims (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT NOT NULL,
    claimant_id INT NOT NULL,
    description TEXT NOT NULL,
    status      ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id)     REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (claimant_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_claim (post_id, claimant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (name, email, password, is_admin)
VALUES (
    'Administrator',
    'admin@ctu.edu.ph',
    '$2y$10$4dt55TE5lYoQ3THzJpwBYOE0H8Gdv3J89HdqnaNPeGKKLetDqRh76',
    1
);



