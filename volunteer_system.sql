-- Create database
CREATE DATABASE IF NOT EXISTS nashaty_db;
USE nashaty_db;

-- Partners table
CREATE TABLE partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    center_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) DEFAULT NULL,
    unique_token VARCHAR(100) UNIQUE DEFAULT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    form_submitted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_token (unique_token)
);

-- Parents table
CREATE TABLE parents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);

-- Parent activities form
CREATE TABLE parent_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    preferred_contact VARCHAR(50) NOT NULL,
    number_of_kids INT NOT NULL,
    activities TEXT NOT NULL,
    other_activity VARCHAR(255),
    preferred_centers TEXT,
    class_preference VARCHAR(50),
    language_preference VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE,
    INDEX idx_parent_id (parent_id)
);


CREATE TABLE partner_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,

    -- Basic Info
    center_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(50) NOT NULL,
    description TEXT,

    -- Activities & Importance
    activities_offered TEXT,

    -- Age, Gender, Class Info
    age_groups VARCHAR(255),
    gender VARCHAR(50),
    class_days TEXT,
    class_timings JSON,  -- Changed to JSON to store structured timing data

    -- Locations
    location1 VARCHAR(255),
    location2 VARCHAR(255),
    location3 VARCHAR(255),
    location4 VARCHAR(255),

    -- Website/Social
    website VARCHAR(255),

    -- Pricing
    price_month VARCHAR(50),
    price_term VARCHAR(50),
    price_year VARCHAR(50),
    free_trial VARCHAR(10),

    -- Terms & Permissions
    terms_ack TINYINT(1) DEFAULT 0,
    social_post VARCHAR(20),
    confidentiality_ack TINYINT(1) DEFAULT 0,

    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    INDEX idx_partner_id (partner_id),
    INDEX idx_submitted_at (submitted_at)
);

-- Admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Insert default admin (username: admin, password: admin123)
INSERT INTO admin_users (username, password, email) VALUES
('admin', '$2y$10$8Zq9P5OGKqbADRBG42LCh.QZMXre4RwWR7K0JJaa8CN4GEWSGPbcW', 'ceo@nashaty.net');


-- Contact form submissions
CREATE TABLE contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    comments TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);