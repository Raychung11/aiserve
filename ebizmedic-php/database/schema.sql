-- eBizMedic MySQL Schema
-- Run this once to set up the database

CREATE DATABASE IF NOT EXISTS ebizmedic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ebizmedic;

-- Users (all roles share this table)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'medic', 'organisation', 'user') NOT NULL DEFAULT 'user',
    phone VARCHAR(50),
    avatar VARCHAR(255),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Organisations
CREATE TABLE organisations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    type ENUM('clinic', 'hospital', 'specialist_center', 'wellness_center') DEFAULT 'clinic',
    description TEXT,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Malaysia',
    phone VARCHAR(50),
    email VARCHAR(255),
    website VARCHAR(255),
    logo VARCHAR(255),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Doctor profiles
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    organisation_id INT,
    speciality VARCHAR(255),
    qualification VARCHAR(255),
    experience_years INT DEFAULT 0,
    bio TEXT,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    photo VARCHAR(255),
    is_available_online TINYINT(1) DEFAULT 0,
    is_available_onsite TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE SET NULL
);

-- Doctor weekly schedules
CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day_of_week ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_doctor_day (doctor_id, day_of_week),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Services offered by organisations
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) DEFAULT 0.00,
    duration_minutes INT DEFAULT 30,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE
);

-- Appointments
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    service_id INT,
    organisation_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    type ENUM('online', 'onsite') DEFAULT 'onsite',
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE SET NULL
);

-- Seed: default admin account (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@ebizmedic.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMqJqhN3JFe8kTjHMKJ5JDaJa2', 'admin');
-- Note: generate a real hash with password_hash('yourpassword', PASSWORD_BCRYPT)
