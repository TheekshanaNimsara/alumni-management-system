-- ============================================================
-- Alumni Connect Platform
-- Complete Database Schema & Two-Example Demo Dataset (MySQL / MariaDB)
-- Character Set: utf8mb4 | Collation: utf8mb4_unicode_ci | Engine: InnoDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS alumni_network CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE alumni_network;

-- ------------------------------------------------------------
-- 1. Users table (Administrators and Alumni)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('alumni', 'admin') NOT NULL DEFAULT 'alumni',
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. Alumni profile details (1-to-1 with users)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS alumni_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    graduation_year INT NOT NULL,
    degree_programme VARCHAR(150) NOT NULL,
    department VARCHAR(150) NOT NULL,
    current_job_title VARCHAR(150) NULL,
    current_company VARCHAR(150) NULL,
    location VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    linkedin_url VARCHAR(255) NULL,
    bio TEXT NULL,
    skills TEXT NULL,
    profile_picture VARCHAR(255) DEFAULT 'default-avatar.svg',
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_profiles_user (user_id),
    INDEX idx_profiles_grad_year (graduation_year),
    INDEX idx_profiles_department (department),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. Events table (with moderation status, RSVP deadlines & capacity)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL,
    reg_date DATE DEFAULT NULL,
    event_time TIME NOT NULL,
    start_time TIME DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    location VARCHAR(200) NOT NULL,
    capacity INT NOT NULL DEFAULT 100,
    organizer_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    is_closed TINYINT(1) NOT NULL DEFAULT 0,
    image_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_events_organizer (organizer_id),
    INDEX idx_events_status (status),
    INDEX idx_events_date (event_date),
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. Event Registrations (many-to-many: users <-> events RSVP)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('attending', 'cancelled') NOT NULL DEFAULT 'attending',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_registration (event_id, user_id),
    INDEX idx_er_user (user_id),
    INDEX idx_er_event (event_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. Jobs table (with moderation status, deadline & contact)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    company VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    contact_email VARCHAR(200) DEFAULT NULL,
    requirements TEXT DEFAULT NULL,
    location VARCHAR(150) NOT NULL,
    job_type ENUM('Full-Time', 'Part-Time', 'Internship', 'Contract', 'Remote') NOT NULL DEFAULT 'Full-Time',
    application_link VARCHAR(255) NULL,
    deadline DATE DEFAULT NULL,
    posted_by INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATE NULL,
    INDEX idx_jobs_posted_by (posted_by),
    INDEX idx_jobs_status (status),
    INDEX idx_jobs_type (job_type),
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. Job Applications (many-to-many: users <-> jobs)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NULL,
    status ENUM('Applied', 'Under Review', 'Accepted', 'Rejected') NOT NULL DEFAULT 'Applied',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_application (job_id, user_id),
    INDEX idx_ja_job (job_id),
    INDEX idx_ja_user (user_id),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. Conversations Table (1-to-1 Private Messaging)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_one_id INT NOT NULL,
    user_two_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_participants (user_one_id, user_two_id),
    INDEX idx_conv_user_one (user_one_id),
    INDEX idx_conv_user_two (user_two_id),
    FOREIGN KEY (user_one_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_two_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. Direct Messages Table (with soft-delete unsend support)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    is_unsent TINYINT(1) NOT NULL DEFAULT 0,
    unsent_at TIMESTAMP NULL DEFAULT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_messages_conv (conversation_id),
    INDEX idx_messages_sender (sender_id),
    INDEX idx_messages_receiver (receiver_id),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 9. Content & User Moderation Reports
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_user_id INT NULL,
    event_id INT NULL,
    job_id INT NULL,
    message_id INT NULL,
    reason VARCHAR(150) NOT NULL,
    description TEXT NULL,
    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reports_reporter (reporter_id),
    INDEX idx_reports_status (status),
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- CANONICAL TWO-EXAMPLE DEMO DATASET
-- Retains exactly two distinct and representative examples per section
-- Default Passwords:
-- Admin:  Admin@123  (hash: $2y$10$0vTq2kWX4IM1BAZnbi0zouWGNpipqbMXhykF/fMTX61Z10fa8znX2)
-- Alumni: Alumni@123 (hash: $2y$10$pXKrWarCYTKp6kEadALbNeZYV0cuzozqsbZPvhX0gYhfjP8orKIT2)
-- ============================================================

-- 1. Users (Admin + Exactly Two Demo Alumni)
INSERT INTO users (id, username, first_name, last_name, email, password, role, status) VALUES
(1, 'admin', 'System', 'Administrator', 'admin@alumni.edu', '$2y$10$0vTq2kWX4IM1BAZnbi0zouWGNpipqbMXhykF/fMTX61Z10fa8znX2', 'admin', 'active'),
(2, 'johndoe', 'John', 'Doe', 'john.doe@alumni.edu', '$2y$10$pXKrWarCYTKp6kEadALbNeZYV0cuzozqsbZPvhX0gYhfjP8orKIT2', 'alumni', 'active'),
(3, 'sarahj', 'Sarah', 'Jenkins', 'sarah.j@alumni.edu', '$2y$10$pXKrWarCYTKp6kEadALbNeZYV0cuzozqsbZPvhX0gYhfjP8orKIT2', 'alumni', 'active')
ON DUPLICATE KEY UPDATE password=VALUES(password);

-- 2. Alumni Profiles (Exactly Two Demo Profiles with Skills)
INSERT INTO alumni_profiles (id, user_id, graduation_year, degree_programme, department, current_job_title, current_company, location, phone, linkedin_url, bio, skills, profile_picture, is_public) VALUES
(1, 2, 2022, 'B.Sc. in Computer Engineering', 'Computer Engineering', 'Software Engineer', 'Google', 'Mountain View, CA, USA', '+1 650 253 0000', 'https://linkedin.com/in/johndoe', 'Passionate about distributed cloud systems, modern web platforms, and mentoring university students.', 'Distributed Cloud Systems, Go, Python, React, Kubernetes, Mentoring', 'default-avatar.svg', 1),
(2, 3, 2020, 'B.Sc. in Computer Engineering', 'Computer Engineering', 'Lead AI Researcher', 'Google DeepMind', 'London, United Kingdom', '+44 20 7123 4567', 'https://linkedin.com/in/sarahjenkins', 'Focusing on large generative AI models and efficient on-device intelligence. Proud university alumna.', 'Generative AI, Large Language Models, Deep Learning, PyTorch, On-Device ML', 'default-avatar.svg', 1)
ON DUPLICATE KEY UPDATE current_job_title=VALUES(current_job_title), skills=VALUES(skills);

-- 3. Events (Exactly Two Demo Events: 1 Approved, 1 Pending Moderation)
INSERT INTO events (id, title, description, event_date, reg_date, event_time, start_time, end_time, location, capacity, organizer_id, status) VALUES
(1, 'ALUMNI MEET 2026', 'The flagship annual gathering of alumni, faculty, and graduating students. Reconnect with batchmates, tour the newly expanded engineering research quad, and celebrate community achievements under the campus sunset.', '2026-10-24', '2026-10-20', '17:00:00', '17:00:00', '21:00:00', 'Main Campus Grand Auditorium', 250, 1, 'approved'),
(2, 'GLOBAL TECH & AI SUMMIT 2026', 'A premier showcase of innovations featuring keynotes by prominent alumni leaders at Google, AWS, and Intel. Topics include generative AI, semiconductor hardware, and cloud resilience.', '2026-11-15', '2026-11-10', '10:00:00', '10:00:00', '16:00:00', 'Engineering Faculty Complex', 150, 2, 'pending')
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- 4. Event Registrations (Exactly Two Demo Registrations)
INSERT INTO event_registrations (id, event_id, user_id, status) VALUES
(1, 1, 2, 'attending'),
(2, 1, 3, 'attending')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- 5. Jobs (Exactly Two Demo Jobs: 1 Approved, 1 Pending Moderation)
INSERT INTO jobs (id, title, company, description, contact_email, requirements, location, job_type, application_link, deadline, posted_by, status) VALUES
(1, 'Software Engineer', 'ABC Technologies', 'Seeking an ambitious software engineer proficient in modern full-stack web technologies, clean API design, and relational databases. Excellent mentorship provided.', 'careers@abctechnologies.com', 'Proficiency in PHP, JavaScript, SQL, Git, and RESTful APIs.', 'Colombo, Sri Lanka', 'Full-Time', 'https://careers.abctechnologies.com', '2026-12-31', 2, 'approved'),
(2, 'Cloud Solutions Architect', 'Virtusa', 'Lead enterprise architecture transformations for Fortune 500 financial clients. Deep expertise in microservices and scalable cloud patterns required.', 'talent@virtusa.com', 'Extensive experience in AWS or GCP cloud architecture, microservices, and Kubernetes.', 'Colombo / Hybrid', 'Full-Time', 'https://virtusa.com/careers', '2026-11-30', 3, 'pending')
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- 6. Job Applications (Exactly Two Demo Applications)
INSERT INTO job_applications (id, job_id, user_id, message, status) VALUES
(1, 1, 3, 'Excited to apply my software engineering background to this role.', 'Applied'),
(2, 2, 2, 'I have over 4 years of cloud architecture experience.', 'Under Review')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- 7. Conversations (Exactly One Demo Conversation Linking the Two Alumni)
INSERT INTO conversations (id, user_one_id, user_two_id) VALUES
(1, 2, 3)
ON DUPLICATE KEY UPDATE updated_at=NOW();

-- 8. Messages (Exactly Two Demo Messages: One from Each Alumni)
INSERT INTO messages (id, conversation_id, sender_id, receiver_id, message, is_read, sent_at) VALUES
(1, 1, 3, 2, 'Hi John! Welcome back to Alumni Connect.', 1, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 1, 2, 3, 'Hi Sarah! Great to connect. Are you attending the Alumni Meet next month?', 1, DATE_SUB(NOW(), INTERVAL 1 HOUR))
ON DUPLICATE KEY UPDATE message=VALUES(message);

-- 9. Reports (Exactly Two Demo Reports: 1 User Report Pending, 1 Event Report Reviewed)
INSERT INTO reports (id, reporter_id, reported_user_id, event_id, job_id, message_id, reason, description, status) VALUES
(1, 2, 3, NULL, NULL, NULL, 'Suspicious / Inactive Profile Information', 'Please verify profile details.', 'pending'),
(2, 3, NULL, 1, NULL, NULL, 'Outdated Venue Location', 'Event location mentions the old campus quad instead of the newly expanded Grand Auditorium.', 'reviewed')
ON DUPLICATE KEY UPDATE reason=VALUES(reason);
