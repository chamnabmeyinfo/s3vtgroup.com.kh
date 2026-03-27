<?php
namespace App\Models;

class Permission {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * Get all permissions grouped by category
     */
    public function getAll($groupByCategory = true) {
        $permissions = $this->db->fetchAll(
            "SELECT * FROM permissions ORDER BY category, name"
        );
        
        if (!$groupByCategory) {
            return $permissions;
        }
        
        $grouped = [];
        foreach ($permissions as $permission) {
            $category = $permission['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $permission;
        }
        
        return $grouped;
    }
    
    /**
     * Get permission by ID
     */
    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM permissions WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Get permission by slug
     */
    public function getBySlug($slug) {
        return $this->db->fetchOne(
            "SELECT * FROM permissions WHERE slug = :slug",
            ['slug' => $slug]
        );
    }
    
    /**
     * Get all categories
     */
    public function getCategories() {
        $categories = $this->db->fetchAll(
            "SELECT DISTINCT category FROM permissions ORDER BY category"
        );
        
        return array_column($categories, 'category');
    }
}

