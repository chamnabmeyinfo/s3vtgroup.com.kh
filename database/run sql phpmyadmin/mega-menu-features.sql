-- Mega Menu Features
-- Adds support for advanced mega menu functionality

-- Add mega menu fields to menu_items table
ALTER TABLE `menu_items` 
ADD COLUMN IF NOT EXISTS `mega_menu_enabled` TINYINT(1) DEFAULT 0 AFTER `is_active`,
ADD COLUMN IF NOT EXISTS `mega_menu_layout` VARCHAR(50) DEFAULT 'columns' AFTER `mega_menu_enabled`,
ADD COLUMN IF NOT EXISTS `mega_menu_width` VARCHAR(20) DEFAULT 'auto' AFTER `mega_menu_layout`,
ADD COLUMN IF NOT EXISTS `mega_menu_columns` INT DEFAULT 3 AFTER `mega_menu_width`,
ADD COLUMN IF NOT EXISTS `mega_menu_content` TEXT NULL AFTER `mega_menu_columns`,
ADD COLUMN IF NOT EXISTS `mega_menu_background` VARCHAR(255) NULL AFTER `mega_menu_content`,
ADD COLUMN IF NOT EXISTS `mega_menu_custom_css` TEXT NULL AFTER `mega_menu_background`;

-- Create mega_menu_widgets table for custom content blocks
CREATE TABLE IF NOT EXISTS `mega_menu_widgets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `menu_item_id` INT NOT NULL,
    `widget_type` VARCHAR(50) NOT NULL DEFAULT 'text',
    `widget_title` VARCHAR(255) NULL,
    `widget_content` TEXT NULL,
    `widget_image` VARCHAR(255) NULL,
    `widget_url` VARCHAR(500) NULL,
    `widget_column` INT DEFAULT 1,
    `widget_order` INT DEFAULT 0,
    `widget_width` VARCHAR(20) DEFAULT 'full',
    `widget_style` TEXT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_menu_item` (`menu_item_id`),
    INDEX `idx_widget_type` (`widget_type`),
    INDEX `idx_active` (`is_active`),
    FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create mega_menu_products table for product displays
CREATE TABLE IF NOT EXISTS `mega_menu_products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `menu_item_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_menu_item` (`menu_item_id`),
    INDEX `idx_product` (`product_id`),
    FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_menu_product` (`menu_item_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create mega_menu_categories table for category displays
CREATE TABLE IF NOT EXISTS `mega_menu_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `menu_item_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `display_order` INT DEFAULT 0,
    `show_image` TINYINT(1) DEFAULT 1,
    `show_description` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_menu_item` (`menu_item_id`),
    INDEX `idx_category` (`category_id`),
    FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_menu_category` (`menu_item_id`, `category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
