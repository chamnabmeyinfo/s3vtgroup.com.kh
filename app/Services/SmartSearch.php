<?php
/**
 * Smart Search Engine
 * Provides intelligent search with autocomplete, suggestions, and smart matching
 */
namespace App\Services;

use App\Models\Product;

class SmartSearch {
    private $db;
    private $productModel;
    
    public function __construct() {
        $this->db = db();
        $this->productModel = new Product();
    }
    
    /**
     * Perform smart search
     */
    public function search($query, $options = []) {
        $query = trim($query);
        if (empty($query)) {
            return ['products' => [], 'suggestions' => [], 'related' => []];
        }
        
        // Track search
        $this->trackSearch($query);
        
        // Parse query
        $parsed = $this->parseQuery($query);
        
        // Search products
        $products = $this->searchProducts($parsed, $options);
        
        // Get suggestions
        $suggestions = $this->getSuggestions($query);
        
        // Get related searches
        $related = $this->getRelatedSearches($query);
        
        return [
            'products' => $products,
            'suggestions' => $suggestions,
            'related' => $related,
            'query' => $query
        ];
    }
    
    /**
     * Get autocomplete suggestions
     */
    public function autocomplete($query, $limit = 10) {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }
        
        $suggestions = [];
        
        // Product name suggestions
        $products = $this->db->fetchAll(
            "SELECT DISTINCT name, slug 
             FROM products 
             WHERE is_active = 1 
             AND (name LIKE :query OR name LIKE :query_start)
             ORDER BY 
                 CASE WHEN name LIKE :query_start THEN 1 ELSE 2 END,
                 name
             LIMIT :limit",
            [
                'query' => '%' . $query . '%',
                'query_start' => $query . '%',
                'limit' => $limit
            ]
        );
        
        foreach ($products as $product) {
            $suggestions[] = [
                'type' => 'product',
                'text' => $product['name'],
                'url' => url('product.php?slug=' . $product['slug']),
                'icon' => 'fa-box'
            ];
        }
        
        // Category suggestions
        $categories = $this->db->fetchAll(
            "SELECT DISTINCT name, slug 
             FROM categories 
             WHERE name LIKE :query
             LIMIT 5",
            ['query' => '%' . $query . '%']
        );
        
        foreach ($categories as $cat) {
            $suggestions[] = [
                'type' => 'category',
                'text' => $cat['name'],
                'url' => url('products.php?category=' . $cat['slug']),
                'icon' => 'fa-folder'
            ];
        }
        
        // Popular searches
        $popular = $this->getPopularSearches($query, 3);
        foreach ($popular as $pop) {
            $suggestions[] = [
                'type' => 'search',
                'text' => $pop,
                'url' => url('products.php?search=' . urlencode($pop)),
                'icon' => 'fa-search'
            ];
        }
        
        return array_slice($suggestions, 0, $limit);
    }
    
    /**
     * Search products with smart matching
     */
    private function searchProducts($parsed, $options = []) {
        $limit = $options['limit'] ?? 20;
        $categoryId = $options['category_id'] ?? null;
        
        $where = ["p.is_active = 1"];
        $params = [];
        
        // Search terms
        if (!empty($parsed['terms'])) {
            $conditions = [];
            foreach ($parsed['terms'] as $term) {
                $conditions[] = "(p.name LIKE :term OR p.description LIKE :term OR p.short_description LIKE :term OR p.sku LIKE :term)";
                $params['term'] = '%' . $term . '%';
            }
            if (!empty($conditions)) {
                $where[] = '(' . implode(' OR ', $conditions) . ')';
            }
        }
        
        // Category filter
        if ($categoryId) {
            $where[] = "p.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }
        
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY 
                    CASE WHEN p.name LIKE :best_match THEN 1 ELSE 2 END,
                    p.is_featured DESC,
                    p.view_count DESC,
                    p.name
                LIMIT :limit";
        
        $params['best_match'] = $parsed['original'] . '%';
        $params['limit'] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Parse search query
     */
    private function parseQuery($query) {
        // Remove extra spaces
        $query = preg_replace('/\s+/', ' ', trim($query));
        
        // Extract quoted phrases
        preg_match_all('/"([^"]+)"/', $query, $quoted);
        $query = preg_replace('/"[^"]+"/', '', $query);
        
        // Extract terms
        $terms = array_filter(explode(' ', $query));
        
        return [
            'original' => $query,
            'terms' => array_merge($quoted[1] ?? [], $terms),
            'quoted' => $quoted[1] ?? []
        ];
    }
    
    /**
     * Get search suggestions
     */
    private function getSuggestions($query) {
        // Get similar successful searches
        return $this->db->fetchAll(
            "SELECT DISTINCT query, results_count
             FROM search_analytics
             WHERE query LIKE :query
             AND results_count > 0
             ORDER BY results_count DESC, created_at DESC
             LIMIT 5",
            ['query' => '%' . $query . '%']
        );
    }
    
    /**
     * Get related searches
     */
    private function getRelatedSearches($query) {
        // Get searches that led to same products
        $terms = explode(' ', $query);
        if (empty($terms)) {
            return [];
        }
        
        return $this->db->fetchAll(
            "SELECT DISTINCT query
             FROM search_analytics
             WHERE query != :query
             AND query LIKE :term
             AND results_count > 0
             ORDER BY created_at DESC
             LIMIT 5",
            [
                'query' => $query,
                'term' => '%' . $terms[0] . '%'
            ]
        );
    }
    
    /**
     * Get popular searches
     */
    private function getPopularSearches($query, $limit = 5) {
        return array_column(
            $this->db->fetchAll(
                "SELECT DISTINCT query, COUNT(*) as count
                 FROM search_analytics
                 WHERE query LIKE :query
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY query
                 ORDER BY count DESC
                 LIMIT :limit",
                ['query' => '%' . $query . '%', 'limit' => $limit]
            ),
            'query'
        );
    }
    
    /**
     * Track search query
     */
    private function trackSearch($query) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $sessionId = session_id();
        
        // Count results
        $count = count($this->productModel->getAll(['search' => $query, 'limit' => 1000]));
        
        // Store search
        $this->db->insert('search_analytics', [
            'query' => $query,
            'results_count' => $count,
            'session_id' => $sessionId
        ]);
    }
}

