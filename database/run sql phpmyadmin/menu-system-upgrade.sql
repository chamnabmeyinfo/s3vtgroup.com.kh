-- Menu System Upgrade
-- WordPress-like menu system enhancements

-- Add layout/theme support to menu_locations
ALTER TABLE `menu_locations` 
ADD COLUMN IF NOT EXISTS `layout` VARCHAR(100) NULL AFTER `area`,
ADD COLUMN IF NOT EXISTS `theme` VARCHAR(100) NULL AFTER `layout`,
ADD COLUMN IF NOT EXISTS `display_conditions` TEXT NULL AFTER `theme`,
ADD COLUMN IF NOT EXISTS `custom_css` TEXT NULL AFTER `display_conditions`;

-- Add post_type support to menu_items
ALTER TABLE `menu_items` 
ADD COLUMN IF NOT EXISTS `post_type` VARCHAR(50) NULL AFTER `type`,
ADD COLUMN IF NOT EXISTS `taxonomy` VARCHAR(50) NULL AFTER `post_type`,
ADD INDEX IF NOT EXISTS `idx_post_type` (`post_type`),
ADD INDEX IF NOT EXISTS `idx_taxonomy` (`taxonomy`);

-- Add menu item metadata table for extensibility
CREATE TABLE IF NOT EXISTS `menu_item_meta` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `menu_item_id` INT NOT NULL,
    `meta_key` VARCHAR(255) NOT NULL,
    `meta_value` LONGTEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_menu_item` (`menu_item_id`),
    INDEX `idx_meta_key` (`meta_key`),
    UNIQUE KEY `unique_item_meta` (`menu_item_id`, `meta_key`),
    FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add menu metadata table
CREATE TABLE IF NOT EXISTS `menu_meta` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `menu_id` INT NOT NULL,
    `meta_key` VARCHAR(255) NOT NULL,
    `meta_value` LONGTEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_menu` (`menu_id`),
    INDEX `idx_meta_key` (`meta_key`),
    UNIQUE KEY `unique_menu_meta` (`menu_id`, `meta_key`),
    FOREIGN KEY (`menu_id`) REFERENCES `menus`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add location metadata table
CREATE TABLE IF NOT EXISTS `menu_location_meta` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `location_id` INT NOT NULL,
    `meta_key` VARCHAR(255) NOT NULL,
    `meta_value` LONGTEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_location` (`location_id`),
    INDEX `idx_meta_key` (`meta_key`),
    UNIQUE KEY `unique_location_meta` (`location_id`, `meta_key`),
    FOREIGN KEY (`location_id`) REFERENCES `menu_locations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add enhanced mega menu features
ALTER TABLE `menu_items` 
ADD COLUMN IF NOT EXISTS `mega_menu_animation` VARCHAR(50) DEFAULT 'fade' AFTER `mega_menu_custom_css`,
ADD COLUMN IF NOT EXISTS `mega_menu_delay` INT DEFAULT 0 AFTER `mega_menu_animation`,
ADD COLUMN IF NOT EXISTS `mega_menu_mobile_breakpoint` INT DEFAULT 768 AFTER `mega_menu_delay`;

-- Add menu item visibility conditions
ALTER TABLE `menu_items` 
ADD COLUMN IF NOT EXISTS `visibility_conditions` TEXT NULL AFTER `is_active`,
ADD COLUMN IF NOT EXISTS `css_id` VARCHAR(100) NULL AFTER `css_classes`;
