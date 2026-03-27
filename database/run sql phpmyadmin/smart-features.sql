-- Smart Features Database Tables

-- Customer Behavior Tracking
CREATE TABLE IF NOT EXISTS customer_behavior (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255),
    customer_id INT NULL,
    action_type ENUM('view', 'search', 'cart_add', 'cart_remove', 'wishlist_add', 'compare', 'purchase') NOT NULL,
    product_id INT NULL,
    category_id INT NULL,
    search_query VARCHAR(255),
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_customer (customer_id),
    INDEX idx_action (action_type),
    INDEX idx_product (product_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Smart Recommendations Cache
CREATE TABLE IF NOT EXISTS smart_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_identifier VARCHAR(255) NOT NULL, -- session_id or customer_id
    product_id INT NOT NULL,
    recommendation_type ENUM('based_on_view', 'based_on_cart', 'based_on_purchase', 'based_on_category', 'trending', 'similar') NOT NULL,
    score DECIMAL(10,4) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_user (user_identifier),
    INDEX idx_product (product_id),
    INDEX idx_type (recommendation_type),
    INDEX idx_score (score),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Smart Search Analytics
CREATE TABLE IF NOT EXISTS search_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query VARCHAR(255) NOT NULL,
    results_count INT DEFAULT 0,
    clicked_product_id INT NULL,
    session_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_query (query),
    INDEX idx_created (created_at),
    FOREIGN KEY (clicked_product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Smart Inventory Alerts
CREATE TABLE IF NOT EXISTS inventory_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    alert_type ENUM('low_stock', 'out_of_stock', 'back_in_stock', 'price_change') NOT NULL,
    threshold_value INT NULL,
    current_value INT NULL,
    is_sent TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_type (alert_type),
    INDEX idx_sent (is_sent),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Smart Customer Insights
CREATE TABLE IF NOT EXISTS customer_insights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    insight_type VARCHAR(100) NOT NULL,
    insight_data JSON,
    confidence_score DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer (customer_id),
    INDEX idx_type (insight_type),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Smart Price History
CREATE TABLE IF NOT EXISTS price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    old_price DECIMAL(10,2),
    new_price DECIMAL(10,2),
    discount_percentage DECIMAL(5,2),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_changed (changed_at),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Smart Product Relationships
CREATE TABLE IF NOT EXISTS product_relationships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    related_product_id INT NOT NULL,
    relationship_type ENUM('frequently_bought_together', 'similar', 'complementary', 'alternative') NOT NULL,
    strength_score DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_related (related_product_id),
    INDEX idx_type (relationship_type),
    INDEX idx_strength (strength_score),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (related_product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_relationship (product_id, related_product_id, relationship_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

