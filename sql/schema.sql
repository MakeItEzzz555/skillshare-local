CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('learner','instructor','admin') DEFAULT 'learner',
    city VARCHAR(100),
    suspended TINYINT(1) DEFAULT 0
);

CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    description TEXT,
    duration VARCHAR(50),
    fee DECIMAL(10,2) DEFAULT 0,
    location VARCHAR(255),
    photo VARCHAR(255),
    sustainability_impact TEXT,
    capacity INT DEFAULT 10,
    participants_confirmed INT DEFAULT 0,
    event_date DATE,
    start_time TIME DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    status ENUM('active','completed') DEFAULT 'active'
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    learner_id INT NOT NULL,
    status ENUM('pending','confirmed','declined') DEFAULT 'pending'
);

CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    learner_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    feedback TEXT
);


-- Safe additive alters for existing databases
ALTER TABLE sessions
  ADD COLUMN IF NOT EXISTS start_time TIME DEFAULT NULL AFTER event_date,
  ADD COLUMN IF NOT EXISTS end_time TIME DEFAULT NULL AFTER start_time;


CREATE TABLE impact_factors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_category VARCHAR(100) NOT NULL UNIQUE,
    co2_saved_per_participant_kg DECIMAL(10,2) NOT NULL
);
