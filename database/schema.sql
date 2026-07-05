-- Create database
CREATE DATABASE past_questions_archive;
USE past_questions_archive;

-- Departments table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL UNIQUE
);

-- Users table (for students)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matric_number VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    sex ENUM('Male', 'Female') NOT NULL,
    department_id INT NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Admins table (for administrators)
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Levels table
CREATE TABLE levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

-- Semesters table
CREATE TABLE semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

-- Past questions table
CREATE TABLE past_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    department_id INT NOT NULL,
    level_id INT NOT NULL,
    semester_id INT NOT NULL,
    year YEAR NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (level_id) REFERENCES levels(id),
    FOREIGN KEY (semester_id) REFERENCES semesters(id),
    FOREIGN KEY (uploaded_by) REFERENCES admins(id)
);

-- Insert initial data
INSERT INTO departments (name, code) VALUES 
( 'Accountancy', 'AC' ),
( 'Agricultural Technology', 'AG' ),
( 'Business Administration and management', 'BA' ),
( 'Computer Engineering', 'CE' ),
( 'Computer Science', 'CS' ),
( 'Electrical and Eletonics Engineering Technology', 'EE' ),
( 'Estate Management', 'EM' ),
( 'Office Technology and Management','OT' ),
( 'Public Administration', 'PA' ),
(  'Science Laboratory Technology','SL' ),
(  'Statistics', 'ST' ),
(  'Tourism and Leisure Management', 'TM' ),
(  'Urban and Regional Planning', 'UR' );



INSERT INTO levels (name) VALUES 
('ND 1'), ('ND 2'), ('HND 1'), ('HND 2');

INSERT INTO semesters (name) VALUES 
('First Semester'), ('Second Semester');

-- Insert default admin account (password: admin123)
INSERT INTO admins (email, full_name, password, role) VALUES 
('admin@school.edu', 'System Administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');