-- Role Management System
-- Run this SQL file to add role and permission tables

USE forklift_equipment;

-- Add role_id column to admin_users table
ALTER TABLE admin_users 
ADD COLUMN role_id INT NULL AFTER id,
ADD INDEX idx_role (role_id);

-- Roles Table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_system TINYINT(1) DEFAULT 0 COMMENT 'System roles cannot be deleted',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions Table
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role Permissions Table (Many-to-Many)
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    INDEX idx_role (role_id),
    INDEX idx_permission (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Roles
INSERT INTO roles (name, slug, description, is_system) VALUES
('Super Administrator', 'super_admin', 'Full system access - all permissions', 1),
('Administrator', 'admin', 'Full administrative access', 1),
('Manager', 'manager', 'Can manage products, orders, and content', 0),
('Editor', 'editor', 'Can edit products and content', 0),
('Viewer', 'viewer', 'Read-only access to dashboard and reports', 0),
('Support', 'support', 'Can manage quotes, messages, and customer support', 0)
ON DUPLICATE KEY UPDATE name=name;

-- Insert Default Permissions
INSERT INTO permissions (name, slug, description, category) VALUES
-- Dashboard
('View Dashboard', 'view_dashboard', 'Access to dashboard', 'dashboard'),
-- Products
('View Products', 'view_products', 'View products list', 'products'),
('Create Products', 'create_products', 'Create new products', 'products'),
('Edit Products', 'edit_products', 'Edit existing products', 'products'),
('Delete Products', 'delete_products', 'Delete products', 'products'),
-- Categories
('View Categories', 'view_categories', 'View categories list', 'categories'),
('Create Categories', 'create_categories', 'Create new categories', 'categories'),
('Edit Categories', 'edit_categories', 'Edit existing categories', 'categories'),
('Delete Categories', 'delete_categories', 'Delete categories', 'categories'),
-- Orders/Quotes
('View Quotes', 'view_quotes', 'View quote requests', 'quotes'),
('Manage Quotes', 'manage_quotes', 'Update quote status and details', 'quotes'),
('Export Quotes', 'export_quotes', 'Export quote data', 'quotes'),
-- Messages
('View Messages', 'view_messages', 'View contact messages', 'messages'),
('Manage Messages', 'manage_messages', 'Respond to and manage messages', 'messages'),
-- Reviews
('View Reviews', 'view_reviews', 'View product reviews', 'reviews'),
('Manage Reviews', 'manage_reviews', 'Approve/delete reviews', 'reviews'),
-- Newsletter
('View Newsletter', 'view_newsletter', 'View newsletter subscribers', 'newsletter'),
('Manage Newsletter', 'manage_newsletter', 'Send emails and manage subscribers', 'newsletter'),
-- Settings
('View Settings', 'view_settings', 'View system settings', 'settings'),
('Manage Settings', 'manage_settings', 'Edit system settings', 'settings'),
-- Users
('View Users', 'view_users', 'View admin users list', 'users'),
('Create Users', 'create_users', 'Create new admin users', 'users'),
('Edit Users', 'edit_users', 'Edit admin users', 'users'),
('Delete Users', 'delete_users', 'Delete admin users', 'users'),
-- Roles
('View Roles', 'view_roles', 'View roles list', 'roles'),
('Create Roles', 'create_roles', 'Create new roles', 'roles'),
('Edit Roles', 'edit_roles', 'Edit existing roles', 'roles'),
('Delete Roles', 'delete_roles', 'Delete roles', 'roles'),
-- Analytics
('View Analytics', 'view_analytics', 'Access analytics dashboard', 'analytics'),
('View Advanced Analytics', 'view_advanced_analytics', 'Access advanced analytics', 'analytics'),
-- Backup & Logs
('View Backups', 'view_backups', 'View backup list', 'backups'),
('Manage Backups', 'manage_backups', 'Create and restore backups', 'backups'),
('View Logs', 'view_logs', 'View system logs', 'logs'),
-- Images
('View Images', 'view_images', 'View image gallery', 'images'),
('Upload Images', 'upload_images', 'Upload new images', 'images'),
('Delete Images', 'delete_images', 'Delete images', 'images'),
-- API
('Use API', 'use_api', 'Access API testing tools', 'api'),
('Manage API', 'manage_api', 'Manage API settings and keys', 'api')
ON DUPLICATE KEY UPDATE name=name;

-- Assign all permissions to Super Administrator
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions
ON DUPLICATE KEY UPDATE role_id=role_id;

-- Assign common permissions to Administrator
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions
WHERE slug NOT IN ('delete_users', 'delete_roles', 'manage_api', 'delete_products')
ON DUPLICATE KEY UPDATE role_id=role_id;

-- Assign permissions to Manager
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions
WHERE slug IN (
    'view_dashboard', 'view_products', 'create_products', 'edit_products',
    'view_categories', 'create_categories', 'edit_categories',
    'view_quotes', 'manage_quotes', 'export_quotes',
    'view_messages', 'manage_messages',
    'view_reviews', 'manage_reviews',
    'view_newsletter', 'view_analytics', 'view_images', 'upload_images'
)
ON DUPLICATE KEY UPDATE role_id=role_id;

-- Assign permissions to Editor
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions
WHERE slug IN (
    'view_dashboard', 'view_products', 'create_products', 'edit_products',
    'view_categories', 'create_categories', 'edit_categories',
    'view_reviews', 'manage_reviews', 'view_images', 'upload_images'
)
ON DUPLICATE KEY UPDATE role_id=role_id;

-- Assign permissions to Viewer
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions
WHERE slug IN (
    'view_dashboard', 'view_products', 'view_categories',
    'view_quotes', 'view_messages', 'view_reviews',
    'view_newsletter', 'view_analytics'
)
ON DUPLICATE KEY UPDATE role_id=role_id;

-- Assign permissions to Support
INSERT INTO role_permissions (role_id, permission_id)
SELECT 6, id FROM permissions
WHERE slug IN (
    'view_dashboard', 'view_quotes', 'manage_quotes', 'export_quotes',
    'view_messages', 'manage_messages', 'view_products'
)
ON DUPLICATE KEY UPDATE role_id=role_id;

-- Set default role for existing admin user (Super Admin)
UPDATE admin_users SET role_id = 1 WHERE username = 'admin' AND role_id IS NULL;

