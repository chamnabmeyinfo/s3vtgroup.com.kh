<?php
/**
 * Smart Product Recommendations Engine
 * Provides intelligent product recommendations based on user behavior
 */
namespace App\Services;

use App\Models\Product;

class SmartRecommendations {
    private $db;
    private $productModel;
    
    public function __construct() {
        $this->db = db();
        $this->productModel = new Product();
    }
    
    /**
     * Get smart recommendations for a user
     */
    public function getRecommendations($userIdentifier, $limit = 8) {
        // Check cache first
        $cached = $this->getCachedRecommendations($userIdentifier, $limit);
        if (!empty($cached)) {
            return $cached;
        }
        
        $recommendations = [];
        
        // 1. Based on recently viewed products
        $basedOnViews = $this->getBasedOnViews($userIdentifier, $limit);
        $recommendations = array_merge($recommendations, $basedOnViews);
        
        // 2. Based on cart items
        $basedOnCart = $this->getBasedOnCart($userIdentifier, $limit);
        $recommendations = array_merge($recommendations, $basedOnCart);
        
        // 3. Based on purchase history (if customer)
        if (is_numeric($userIdentifier)) {
            $basedOnPurchases = $this->getBasedOnPurchases($userIdentifier, $limit);
            $recommendations = array_merge($recommendations, $basedOnPurchases);
        }
        
        // 4. Based on category preferences
        $basedOnCategory = $this->getBasedOnCategory($userIdentifier, $limit);
        $recommendations = array_merge($recommendations, $basedOnCategory);
        
        // 5. Trending products
        $trending = $this->getTrending($limit);
        $recommendations = array_merge($recommendations, $trending);
        
        // Remove duplicates and score
        $scored = $this->scoreRecommendations($recommendations);
        
        // Sort by score and limit
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
        $final = array_slice($scored, 0, $limit);
        
        // Cache results
        $this->cacheRecommendations($userIdentifier, $final);
        
        return $final;
    }
    
    /**
     * Get recommendations based on viewed products
     */
    private function getBasedOnViews($userIdentifier, $limit) {
        $views = $this->db->fetchAll(
            "SELECT DISTINCT product_id, category_id 
             FROM customer_behavior 
             WHERE (session_id = :identifier OR customer_id = :identifier) 
             AND action_type = 'view' 
             AND product_id IS NOT NULL
             ORDER BY created_at DESC 
             LIMIT 5",
            ['identifier' => $userIdentifier]
        );
        
        if (empty($views)) {
            return [];
        }
        
        $productIds = array_column($views, 'product_id');
        $categoryIds = array_unique(array_filter(array_column($views, 'category_id')));
        
        $recommendations = [];
        
        // Similar products in same categories
        if (!empty($categoryIds)) {
            $products = $this->productModel->getAll([
                'category_ids' => $categoryIds,
                'exclude_ids' => $productIds,
                'limit' => $limit
            ]);
            
            foreach ($products as $product) {
                $recommendations[] = [
                    'product' => $product,
                    'type' => 'based_on_view',
                    'score' => 0.7
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Get recommendations based on cart items
     */
    private function getBasedOnCart($userIdentifier, $limit) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $cartItems = $_SESSION['cart'] ?? [];
        if (empty($cartItems)) {
            return [];
        }
        
        $productIds = array_keys($cartItems);
        $products = $this->productModel->getAll([
            'ids' => $productIds,
            'limit' => 5
        ]);
        
        $recommendations = [];
        $categoryIds = [];
        
        foreach ($products as $product) {
            if (!empty($product['category_id'])) {
                $categoryIds[] = $product['category_id'];
            }
        }
        
        // Frequently bought together
        $frequentlyBought = $this->getFrequentlyBoughtTogether($productIds, $limit);
        $recommendations = array_merge($recommendations, $frequentlyBought);
        
        // Complementary products
        if (!empty($categoryIds)) {
            $complementary = $this->productModel->getAll([
                'category_ids' => array_unique($categoryIds),
                'exclude_ids' => $productIds,
                'limit' => $limit
            ]);
            
            foreach ($complementary as $product) {
                $recommendations[] = [
                    'product' => $product,
                    'type' => 'based_on_cart',
                    'score' => 0.8
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Get recommendations based on purchase history
     */
    private function getBasedOnPurchases($customerId, $limit) {
        // Get purchased products
        $purchases = $this->db->fetchAll(
            "SELECT DISTINCT oi.product_id 
             FROM orders o
             JOIN order_items oi ON o.id = oi.order_id
             WHERE o.customer_id = :customer_id
             LIMIT 10",
            ['customer_id' => $customerId]
        );
        
        if (empty($purchases)) {
            return [];
        }
        
        $productIds = array_column($purchases, 'product_id');
        return $this->getFrequentlyBoughtTogether($productIds, $limit);
    }
    
    /**
     * Get recommendations based on category preferences
     */
    private function getBasedOnCategory($userIdentifier, $limit) {
        $categories = $this->db->fetchAll(
            "SELECT category_id, COUNT(*) as views
             FROM customer_behavior
             WHERE (session_id = :identifier OR customer_id = :identifier)
             AND category_id IS NOT NULL
             GROUP BY category_id
             ORDER BY views DESC
             LIMIT 3",
            ['identifier' => $userIdentifier]
        );
        
        if (empty($categories)) {
            return [];
        }
        
        $categoryIds = array_column($categories, 'category_id');
        $products = $this->productModel->getAll([
            'category_ids' => $categoryIds,
            'limit' => $limit
        ]);
        
        $recommendations = [];
        foreach ($products as $product) {
            $recommendations[] = [
                'product' => $product,
                'type' => 'based_on_category',
                'score' => 0.6
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Get trending products
     */
    private function getTrending($limit) {
        $trending = $this->db->fetchAll(
            "SELECT product_id, COUNT(*) as views
             FROM customer_behavior
             WHERE action_type = 'view'
             AND product_id IS NOT NULL
             AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY product_id
             ORDER BY views DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
        
        $recommendations = [];
        foreach ($trending as $item) {
            $product = $this->productModel->getById($item['product_id']);
            if ($product && $product['is_active']) {
                $recommendations[] = [
                    'product' => $product,
                    'type' => 'trending',
                    'score' => 0.9
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Get frequently bought together products
     */
    private function getFrequentlyBoughtTogether($productIds, $limit) {
        if (empty($productIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $related = $this->db->fetchAll(
            "SELECT related_product_id, SUM(strength_score) as total_score
             FROM product_relationships
             WHERE product_id IN ($placeholders)
             AND relationship_type = 'frequently_bought_together'
             GROUP BY related_product_id
             ORDER BY total_score DESC
             LIMIT :limit",
            array_merge($productIds, [$limit])
        );
        
        $recommendations = [];
        foreach ($related as $item) {
            $product = $this->productModel->getById($item['related_product_id']);
            if ($product && $product['is_active']) {
                $recommendations[] = [
                    'product' => $product,
                    'type' => 'frequently_bought_together',
                    'score' => min(1.0, $item['total_score'] / 10)
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Score and deduplicate recommendations
     */
    private function scoreRecommendations($recommendations) {
        $scored = [];
        $seen = [];
        
        foreach ($recommendations as $rec) {
            $productId = $rec['product']['id'];
            
            if (isset($seen[$productId])) {
                // Increase score if already recommended
                $seen[$productId]['score'] += $rec['score'] * 0.5;
            } else {
                $seen[$productId] = $rec;
            }
        }
        
        return array_values($seen);
    }
    
    /**
     * Cache recommendations
     */
    private function cacheRecommendations($userIdentifier, $recommendations) {
        // Clear old cache
        $this->db->execute(
            "DELETE FROM smart_recommendations 
             WHERE user_identifier = :identifier 
             OR expires_at < NOW()",
            ['identifier' => $userIdentifier]
        );
        
        // Cache new recommendations
        foreach ($recommendations as $rec) {
            $this->db->insert('smart_recommendations', [
                'user_identifier' => (string)$userIdentifier,
                'product_id' => $rec['product']['id'],
                'recommendation_type' => $rec['type'],
                'score' => $rec['score'],
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ]);
        }
    }
    
    /**
     * Get cached recommendations
     */
    private function getCachedRecommendations($userIdentifier, $limit) {
        $cached = $this->db->fetchAll(
            "SELECT sr.*, p.* 
             FROM smart_recommendations sr
             JOIN products p ON sr.product_id = p.id
             WHERE sr.user_identifier = :identifier
             AND sr.expires_at > NOW()
             AND p.is_active = 1
             ORDER BY sr.score DESC
             LIMIT :limit",
            ['identifier' => (string)$userIdentifier, 'limit' => $limit]
        );
        
        if (empty($cached)) {
            return [];
        }
        
        $recommendations = [];
        foreach ($cached as $item) {
            $recommendations[] = [
                'product' => $item,
                'type' => $item['recommendation_type'],
                'score' => $item['score']
            ];
        }
        
        return $recommendations;
    }
}

