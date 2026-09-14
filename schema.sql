-- ============================================================
-- CS12012 Web Development - Group Assignment
-- University Alumni Network Platform
-- Database Schema (MySQL)
-- ============================================================

CREATE DATABASE IF NOT EXISTS alumni_network CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE alumni_network;

-- ------------------------------------------------------------
-- Users table (both alumni and admin accounts)
-- ------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('alumni', 'admin') NOT NULL DEFAULT 'alumni',
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Alumni profile details (1-1 with users)
-- ------------------------------------------------------------
CREATE TABLE alumni_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    graduation_year YEAR NOT NULL,
    degree_programme VARCHAR(150) NOT NULL,
    faculty VARCHAR(150),
    current_job_title VARCHAR(150),
    current_company VARCHAR(150),
    location VARCHAR(150),
    phone VARCHAR(30),
    linkedin_url VARCHAR(255),
    bio TEXT,
    profile_picture VARCHAR(255) DEFAULT 'default.svg',
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Events (reunions, webinars, career talks, etc.)
-- ------------------------------------------------------------
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(200) NOT NULL,
    organizer_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Event registrations (many-to-many: users <-> events)
-- ------------------------------------------------------------
CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_registration (event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Job postings shared by alumni
-- ------------------------------------------------------------
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    company VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(150) NOT NULL,
    job_type ENUM('Full-Time', 'Part-Time', 'Internship', 'Contract', 'Remote') NOT NULL,
    application_link VARCHAR(255),
    posted_by INT NOT NULL,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATE,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Job applications / interest expressed by alumni
-- ------------------------------------------------------------
CREATE TABLE job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_application (job_id, user_id),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Direct messages between alumni (simple networking/messaging)
-- ------------------------------------------------------------
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Seed data: one admin account
-- Email: admin@alumni.edu   Password: Admin@123
-- ------------------------------------------------------------
INSERT INTO users (first_name, last_name, email, password, role) VALUES
('System', 'Administrator', 'admin@alumni.edu', '$2y$10$0vTq2kWX4IM1BAZnbi0zouWGNpipqbMXhykF/fMTX61Z10fa8znX2', 'admin');
-- The hash above is password_hash("Admin@123", PASSWORD_DEFAULT).
-- Please change this password after first login.
