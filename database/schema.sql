-- ============================================================
-- University Alumni Network Platform
-- Database Schema & Cinematic Seed Data (MySQL / MariaDB)
-- ============================================================

CREATE DATABASE IF NOT EXISTS alumni_network CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE alumni_network;

-- ------------------------------------------------------------
-- 1. Users table (Alumni and Administrators)
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. Alumni profile details (1-1 with users)
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
    profile_picture VARCHAR(255) DEFAULT 'default-avatar.svg',
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. Events table (with moderation status)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(200) NOT NULL,
    organizer_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    image_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. Event Registrations (many-to-many: users <-> events)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_registration (event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. Jobs table (with moderation status)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    company VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(150) NOT NULL,
    job_type ENUM('Full-Time', 'Part-Time', 'Internship', 'Contract', 'Remote') NOT NULL DEFAULT 'Full-Time',
    application_link VARCHAR(255) NULL,
    posted_by INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATE NULL,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. Job Applications
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_application (job_id, user_id),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. Direct Messages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- Default Passwords:
-- Admin: Admin@123  (hash: $2y$10$0vTq2kWX4IM1BAZnbi0zouWGNpipqbMXhykF/fMTX61Z10fa8znX2)
-- Alumni: Alumni@123 (hash: $2y$10$pXKrWarCYTKp6kEadALbNeZYV0cuzozqsbZPvhX0gYhfjP8orKIT2)
-- ============================================================

INSERT INTO users (id, username, first_name, last_name, email, password, role, status) VALUES
(1, 'admin', 'System', 'Administrator', 'admin@alumni.edu', '$2y$10$0vTq2kWX4IM1BAZnbi0zouWGNpipqbMXhykF/fMTX61Z10fa8znX2', 'admin', 'active'),
(2, 'johndoe', 'John', 'Doe', 'john.doe@alumni.edu', '$2y$10$pXKrWarCYTKp6kEadALbNeZYV0cuzozqsbZPvhX0gYhfjP8orKIT2', 'alumni', 'active'),
(3, 'sarahj', 'Sarah', 'Jenkins', 'sarah.j@alumni.edu', '$2y$10$pXKrWarCYTKp6kEadALbNeZYV0cuzozqsbZPvhX0gYhfjP8orKIT2', 'alumni', 'active'),
(4, 'michaelc', 'Michael', 'Chen', 'm.chen@alumni.edu', '$2y$10$pXKrWarCYTKp6kEadALbNeZYV0cuzozqsbZPvhX0gYhfjP8orKIT2', 'alumni', 'active'),
(5, 'ananyap', 'Ananya', 'Perera', 'ananya.p@alumni.edu', '$2y$10$pXKrWarCYTKp6kEadALbNeZYV0cuzozqsbZPvhX0gYhfjP8orKIT2', 'alumni', 'active'),
(6, 'davidk', 'David', 'Kim', 'd.kim@alumni.edu', '$2y$10$pXKrWarCYTKp6kEadALbNeZYV0cuzozqsbZPvhX0gYhfjP8orKIT2', 'alumni', 'active'),
(7, 'priyas', 'Priya', 'Sharma', 'priya.s@alumni.edu', '$2y$10$pXKrWarCYTKp6kEadALbNeZYV0cuzozqsbZPvhX0gYhfjP8orKIT2', 'alumni', 'active')
ON DUPLICATE KEY UPDATE password=VALUES(password);

INSERT INTO alumni_profiles (user_id, graduation_year, degree_programme, department, current_job_title, current_company, location, phone, linkedin_url, bio, is_public) VALUES
(2, 2022, 'B.Sc. in Computer Engineering', 'Computer Engineering', 'Software Engineer', 'Google', 'Mountain View, CA, USA', '+1 650 253 0000', 'https://linkedin.com/in/johndoe', 'Passionate about distributed cloud systems, modern web platforms, and mentoring university students.', 1),
(3, 2020, 'B.Sc. in Computer Engineering', 'Computer Engineering', 'Lead AI Researcher', 'Google DeepMind', 'London, United Kingdom', '+44 20 7123 4567', 'https://linkedin.com/in/sarahjenkins', 'Focusing on large generative AI models and efficient on-device intelligence. Proud university alumna.', 1),
(4, 2019, 'B.Sc. in Electrical & Electronic Engineering', 'Electrical Engineering', 'Senior Systems Architect', 'Intel Corporation', 'Austin, TX, USA', '+1 512 794 0000', 'https://linkedin.com/in/michaelchen', 'Developing next-generation high performance computing architectures and semiconductor interconnects.', 1),
(5, 2021, 'B.Sc. in Software Engineering', 'Computer Engineering', 'Senior Cloud Consultant', 'Amazon Web Services', 'Singapore', '+65 6789 0123', 'https://linkedin.com/in/ananyaperera', 'Helping enterprises build resilient architectures on AWS. Active participant in community hackathons.', 1),
(6, 2018, 'B.Sc. in Information Technology', 'Information Technology', 'Director of Engineering', 'Stripe', 'Dublin, Ireland', '+353 1 234 5678', 'https://linkedin.com/in/davidkim', 'Leading developer experience and payments infrastructure teams. Committed to tech talent growth.', 1),
(7, 2021, 'B.Sc. in Computer Engineering', 'Computer Engineering', 'Product Lead', 'Microsoft', 'Seattle, WA, USA', '+1 425 882 8080', 'https://linkedin.com/in/priyasharma', 'Building intelligent enterprise workplace products. Passionate about empowering women in engineering.', 1)
ON DUPLICATE KEY UPDATE current_job_title=VALUES(current_job_title);

INSERT INTO events (id, title, description, event_date, event_time, location, organizer_id, status) VALUES
(1, 'ALUMNI MEET 2026', 'The flagship annual gathering of alumni, faculty, and graduating students. Reconnect with batchmates, tour the newly expanded engineering research quad, and celebrate community achievements under the campus sunset.', '2026-10-24', '17:00:00', 'KDU Campus Grand Auditorium', 1, 'approved'),
(2, 'GLOBAL TECH & AI SUMMIT 2026', 'A premier showcase of innovations featuring keynotes by prominent alumni leaders at Google, AWS, and Intel. Topics include generative AI, semiconductor hardware, and cloud resilience.', '2026-11-15', '10:00:00', 'Engineering Faculty Complex', 2, 'approved'),
(3, 'ANNUAL CAREER & INTERNSHIP EXPO', 'Connect directly with leading multinational tech giants, research institutes, and innovative startups. Resume reviews, on-site interviews, and networking booths.', '2026-12-05', '09:00:00', 'University Convocation Grounds', 1, 'approved'),
(4, 'ALUMNI MENTORSHIP WORKSHOP', 'An interactive mentoring session pairing senior engineering undergraduates with experienced alumni in the Silicon Valley and European tech scenes.', '2026-12-18', '14:00:00', 'Virtual (Live Zoom Stream)', 3, 'pending')
ON DUPLICATE KEY UPDATE title=VALUES(title);

INSERT INTO jobs (id, title, company, description, location, job_type, application_link, posted_by, status) VALUES
(1, 'Software Engineer', 'ABC Technologies', 'Seeking an ambitious software engineer proficient in modern full-stack web technologies, clean API design, and relational databases. Excellent mentorship provided.', 'Colombo, Sri Lanka', 'Full-Time', 'https://careers.abctechnologies.com', 2, 'approved'),
(2, 'Cloud Solutions Architect', 'Virtusa', 'Lead enterprise architecture transformations for Fortune 500 financial clients. Deep expertise in microservices and scalable cloud patterns required.', 'Colombo / Hybrid', 'Full-Time', 'https://virtusa.com/careers', 5, 'approved'),
(3, 'Associate AI Engineer', 'WSO2', 'Work on open-source API management combined with LLM orchestrations. Great opportunity for recent computer engineering graduates.', 'Colombo, Sri Lanka', 'Full-Time', 'https://wso2.com/careers', 3, 'approved'),
(4, 'DevOps & Platform Intern', 'Sysco LABS', 'Exciting 6-month internship on continuous deployment pipelines, Kubernetes cluster management, and infrastructure monitoring.', 'Colombo, Sri Lanka', 'Internship', 'https://syscolabs.com/careers', 4, 'approved'),
(5, 'Junior Full Stack Developer', 'Octave Analytics', 'Join our advanced analytics team to build responsive dashboards, ETL interfaces, and predictive data tools.', 'Colombo, Sri Lanka', 'Full-Time', 'https://octave.lk/careers', 6, 'pending')
ON DUPLICATE KEY UPDATE title=VALUES(title);
