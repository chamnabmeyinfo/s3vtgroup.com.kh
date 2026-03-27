-- Menu Categories Selection
-- Allows selecting which categories appear in the Products menu dropdown

CREATE TABLE IF NOT EXISTS `menu_category_selections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `menu_item_id` INT NULL,
    `category_id` INT NOT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_menu_item` (`menu_item_id`),
    INDEX `idx_category` (`category_id`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_order` (`display_order`),
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_menu_category` (`menu_item_id`, `category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add a setting to enable/disable category selection feature
INSERT INTO `settings` (`key`, `value`) 
VALUES ('products_menu_use_selected_categories', '0')
ON DUPLICATE KEY UPDATE `value` = '0';
