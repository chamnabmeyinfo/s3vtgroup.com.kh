-- Database Schema for Forklift & Equipment Website

CREATE DATABASE IF NOT EXISTS forklift_equipment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE forklift_equipment;

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    sku VARCHAR(100),
    description TEXT,
    short_description TEXT,
    price DECIMAL(10,2),
    sale_price DECIMAL(10,2) NULL,
    category_id INT,
    image VARCHAR(255),
    gallery TEXT,
    specifications JSON,
    features TEXT,
    stock_status ENUM('in_stock', 'out_of_stock', 'on_order') DEFAULT 'in_stock',
    weight DECIMAL(10,2),
    dimensions VARCHAR(100),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    view_count INT DEFAULT 0,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_featured (is_featured),
    INDEX idx_active (is_active),
    INDEX idx_sku (sku),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quote Requests Table
CREATE TABLE IF NOT EXISTS quote_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    company VARCHAR(255),
    product_id INT NULL,
    message TEXT,
    status ENUM('pending', 'contacted', 'quoted', 'closed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_product (product_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact Messages Table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings Table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) NOT NULL UNIQUE,
    value TEXT,
    type VARCHAR(50) DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Users Table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin (password: admin - change this!)
INSERT INTO admin_users (username, email, password, name) VALUES
('admin', 'admin@example.com', '$2y$10$dPjofwVMqW03XB5xjnfC2e53E26xvn83lsxvUZE6XbssVEtXLL0zu', 'Administrator')
ON DUPLICATE KEY UPDATE username=username;

-- Insert default categories
INSERT INTO categories (name, slug, description) VALUES
('Forklifts', 'forklifts', 'Industrial forklifts for material handling'),
('Pallet Trucks', 'pallet-trucks', 'Manual and electric pallet trucks'),
('Stackers', 'stackers', 'Stacking equipment for warehouses'),
('Reach Trucks', 'reach-trucks', 'Reach trucks for narrow aisles'),
('Trolleys', 'trolleys', 'Transport trolleys and carts'),
('Lifting Equipment', 'lifting-equipment', 'Cranes and lifting solutions')
ON DUPLICATE KEY UPDATE name=name;

-- Insert default settings
INSERT INTO settings (`key`, value, type) VALUES
('site_name', 'Forklift & Equipment Pro', 'text'),
('site_email', 'info@example.com', 'text'),
('site_phone', '+1 (555) 123-4567', 'text'),
('site_address', '123 Industrial Way, City, State 12345', 'text'),
('footer_text', '© 2024 Forklift & Equipment Pro. All rights reserved.', 'text')
ON DUPLICATE KEY UPDATE `key`=`key`;

