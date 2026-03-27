<?php
/**
 * Advanced Analytics Service
 * Provides comprehensive analytics and reporting
 */
namespace App\Services;

class AnalyticsService {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * Get sales analytics
     */
    public function getSalesAnalytics($period = '30days') {
        $dateFormat = $this->getDateFormat($period);
        $dateCondition = $this->getDateCondition($period);
        $startDate = date('Y-m-d', strtotime($dateCondition));
        
        // Safe string interpolation - format is controlled by our method
        $format = addslashes($dateFormat);
        
        try {
            $sales = $this->db->fetchAll(
                "SELECT 
                    DATE_FORMAT(created_at, '{$format}') as date,
                    COUNT(*) as orders,
                    COUNT(*) * 1000 as revenue
                 FROM quote_requests
                 WHERE created_at >= :start_date
                 AND (message LIKE 'Order Request%' OR message LIKE 'Guest Order%')
                 GROUP BY DATE_FORMAT(created_at, '{$format}')
                 ORDER BY date ASC",
                [
                    'start_date' => $startDate
                ]
            );
            
            return $sales ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get product performance
     */
    public function getProductPerformance($limit = 10) {
        try {
            $limit = (int)$limit;
            
            // Check which tables exist
            $hasWishlists = $this->tableExists('wishlists');
            $hasReviews = $this->tableExists('product_reviews');
            $hasBehavior = $this->tableExists('customer_behavior');
            
            $wishlistSubquery = $hasWishlists ? 
                "(SELECT COUNT(*) FROM wishlists WHERE product_id = p.id)" : "0";
            $reviewsSubquery = $hasReviews ? 
                "(SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND is_approved = 1)" : "0";
            
            $sql = "SELECT 
                    p.id,
                    p.name,
                    p.view_count,
                    " . ($hasBehavior ? "COALESCE(COUNT(DISTINCT cb.id), 0)" : "0") . " as interactions,
                    {$wishlistSubquery} as wishlist_count,
                    {$reviewsSubquery} as review_count
                 FROM products p";
            
            if ($hasBehavior) {
                $sql .= " LEFT JOIN customer_behavior cb ON p.id = cb.product_id";
            }
            
            $sql .= " WHERE p.is_active = 1
                 GROUP BY p.id
                 ORDER BY interactions DESC, p.view_count DESC
                 LIMIT {$limit}";
            
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get customer insights
     */
    public function getCustomerInsights() {
        $customersCount = 0;
        $newCustomers = 0;
        $activeCustomers = 0;
        
        try {
            if ($this->tableExists('customers')) {
                $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM customers WHERE is_active = 1");
                $customersCount = $result['count'] ?? 0;
                
                $result = $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM customers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
                );
                $newCustomers = $result['count'] ?? 0;
            }
        } catch (Exception $e) {
            // Table doesn't exist or error
        }
        
        try {
            if ($this->tableExists('customer_behavior')) {
                $result = $this->db->fetchOne(
                    "SELECT COUNT(DISTINCT customer_id) as count FROM customer_behavior WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND customer_id IS NOT NULL"
                );
                $activeCustomers = $result['count'] ?? 0;
            }
        } catch (Exception $e) {
            // Table doesn't exist
        }
        
        return [
            'total_customers' => $customersCount,
            'new_customers_this_month' => $newCustomers,
            'active_customers' => $activeCustomers,
            'average_order_value' => $this->getAverageOrderValue(),
        ];
    }
    
    /**
     * Get traffic analytics
     */
    public function getTrafficAnalytics($period = '30days') {
        $dateCondition = $this->getDateCondition($period);
        $startDate = date('Y-m-d', strtotime($dateCondition));
        
        if (!$this->tableExists('customer_behavior')) {
            return [
                'total_views' => 0,
                'unique_visitors' => 0,
                'page_views' => 0,
                'bounce_rate' => 0,
            ];
        }
        
        try {
            return [
                'total_views' => $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM customer_behavior WHERE action_type = 'view' AND created_at >= :start_date",
                    ['start_date' => $startDate]
                )['count'] ?? 0,
                'unique_visitors' => $this->db->fetchOne(
                    "SELECT COUNT(DISTINCT session_id) as count FROM customer_behavior WHERE created_at >= :start_date",
                    ['start_date' => $startDate]
                )['count'] ?? 0,
                'page_views' => $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM customer_behavior WHERE created_at >= :start_date",
                    ['start_date' => $startDate]
                )['count'] ?? 0,
                'bounce_rate' => $this->calculateBounceRate($dateCondition),
            ];
        } catch (Exception $e) {
            return [
                'total_views' => 0,
                'unique_visitors' => 0,
                'page_views' => 0,
                'bounce_rate' => 0,
            ];
        }
    }
    
    /**
     * Get conversion funnel
     */
    public function getConversionFunnel($period = '30days') {
        $dateCondition = $this->getDateCondition($period);
        $startDate = date('Y-m-d', strtotime($dateCondition));
        
        $views = 0;
        $cartAdds = 0;
        
        try {
            if ($this->tableExists('customer_behavior')) {
                $result = $this->db->fetchOne(
                    "SELECT COUNT(DISTINCT session_id) as count FROM customer_behavior WHERE action_type = 'view' AND created_at >= :start_date",
                    ['start_date' => $startDate]
                );
                $views = $result['count'] ?? 0;
                
                $result = $this->db->fetchOne(
                    "SELECT COUNT(DISTINCT session_id) as count FROM customer_behavior WHERE action_type = 'cart_add' AND created_at >= :start_date",
                    ['start_date' => $startDate]
                );
                $cartAdds = $result['count'] ?? 0;
            }
        } catch (Exception $e) {
            // Table doesn't exist
        }
        
        try {
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM quote_requests WHERE created_at >= :start_date",
                ['start_date' => $startDate]
            );
            $checkouts = $result['count'] ?? 0;
        } catch (Exception $e) {
            $checkouts = 0;
        }
        
        return [
            'views' => $views,
            'cart_adds' => $cartAdds,
            'checkouts' => $checkouts,
        ];
    }
    
    /**
     * Get top categories
     */
    public function getTopCategories($limit = 5) {
        try {
            $hasBehaviorTable = $this->tableExists('customer_behavior');
            $limit = (int)$limit;
            
            $sql = "SELECT 
                    c.id,
                    c.name,
                    COUNT(DISTINCT p.id) as product_count";
            
            if ($hasBehaviorTable) {
                $sql .= ", COALESCE(COUNT(cb.id), 0) as views
                 FROM categories c
                 LEFT JOIN products p ON c.id = p.category_id
                 LEFT JOIN customer_behavior cb ON p.id = cb.product_id";
            } else {
                $sql .= ", 0 as views
                 FROM categories c
                 LEFT JOIN products p ON c.id = p.category_id";
            }
            
            $sql .= " GROUP BY c.id
                 ORDER BY views DESC
                 LIMIT {$limit}";
            
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Check if table exists
     */
    private function tableExists($tableName) {
        try {
            // Try to query the table - if it exists, query succeeds
            $result = $this->db->fetchOne("SHOW TABLES LIKE :table", ['table' => $tableName]);
            return !empty($result);
        } catch (\Exception $e) {
            // Table doesn't exist or error
            return false;
        }
    }
    
    private function getDateFormat($period) {
        switch ($period) {
            case '7days':
            case '30days':
                return '%Y-%m-%d';
            case '12months':
                return '%Y-%m';
            default:
                return '%Y-%m-%d';
        }
    }
    
    private function getDateCondition($period) {
        switch ($period) {
            case '7days':
                return '-7 days';
            case '30days':
                return '-30 days';
            case '12months':
                return '-12 months';
            default:
                return '-30 days';
        }
    }
    
    private function getAverageOrderValue() {
        try {
            if (!$this->tableExists('orders')) {
                return 0;
            }
            
            $result = $this->db->fetchOne(
                "SELECT AVG(total) as avg FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            return (float)($result['avg'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function calculateBounceRate($dateCondition) {
        try {
            if (!$this->tableExists('customer_behavior')) {
                return 0;
            }
            
            $startDate = date('Y-m-d', strtotime($dateCondition));
            
            // Simplified bounce rate calculation
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM (
                    SELECT session_id FROM customer_behavior 
                    WHERE created_at >= :start_date 
                    GROUP BY session_id 
                    HAVING COUNT(*) = 1
                ) as single",
                ['start_date' => $startDate]
            );
            $singlePageSessions = $result['count'] ?? 0;
            
            $result = $this->db->fetchOne(
                "SELECT COUNT(DISTINCT session_id) as count FROM customer_behavior WHERE created_at >= :start_date",
                ['start_date' => $startDate]
            );
            $totalSessions = $result['count'] ?? 1;
            
            return $totalSessions > 0 ? round(($singlePageSessions / $totalSessions) * 100, 2) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
}
