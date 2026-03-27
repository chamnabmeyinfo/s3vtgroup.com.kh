<?php
namespace App\Models;

class Role {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * Get all roles
     */
    public function getAll($includeInactive = false) {
        $sql = "SELECT r.*, 
                COUNT(DISTINCT rp.permission_id) as permission_count,
                COUNT(DISTINCT u.id) as user_count
                FROM roles r
                LEFT JOIN role_permissions rp ON r.id = rp.role_id
                LEFT JOIN admin_users u ON r.id = u.role_id";
        
        if (!$includeInactive) {
            $sql .= " WHERE r.is_active = 1";
        }
        
        $sql .= " GROUP BY r.id ORDER BY r.name ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get role by ID
     */
    public function getById($id) {
        $role = $this->db->fetchOne(
            "SELECT * FROM roles WHERE id = :id",
            ['id' => $id]
        );
        
        if ($role) {
            $role['permissions'] = $this->getPermissions($id);
        }
        
        return $role;
    }
    
    /**
     * Get role by slug
     */
    public function getBySlug($slug) {
        return $this->db->fetchOne(
            "SELECT * FROM roles WHERE slug = :slug",
            ['slug' => $slug]
        );
    }
    
    /**
     * Get permissions for a role
     */
    public function getPermissions($roleId) {
        return $this->db->fetchAll(
            "SELECT p.* FROM permissions p
             INNER JOIN role_permissions rp ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id
             ORDER BY p.category, p.name",
            ['role_id' => $roleId]
        );
    }
    
    /**
     * Check if role has permission
     */
    public function hasPermission($roleId, $permissionSlug) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM role_permissions rp
             INNER JOIN permissions p ON rp.permission_id = p.id
             WHERE rp.role_id = :role_id AND p.slug = :permission",
            [
                'role_id' => $roleId,
                'permission' => $permissionSlug
            ]
        );
        
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * Create new role
     */
    public function create($data) {
        return $this->db->insert('roles', [
            'name' => $data['name'],
            'slug' => $this->generateSlug($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ]);
    }
    
    /**
     * Update role
     */
    public function update($id, $data) {
        $updateData = [];
        
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
            $updateData['slug'] = $this->generateSlug($data['name']);
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }
        
        if (empty($updateData)) {
            return false;
        }
        
        return $this->db->update('roles', $updateData, 'id = :id', ['id' => $id]);
    }
    
    /**
     * Delete role
     */
    public function delete($id) {
        // Check if it's a system role
        $role = $this->getById($id);
        if ($role && $role['is_system']) {
            throw new \Exception('Cannot delete system role');
        }
        
        // Check if role has users
        $userCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM admin_users WHERE role_id = :role_id",
            ['role_id' => $id]
        )['count'] ?? 0;
        
        if ($userCount > 0) {
            throw new \Exception("Cannot delete role with {$userCount} user(s) assigned");
        }
        
        return $this->db->delete('roles', 'id = :id', ['id' => $id]);
    }
    
    /**
     * Assign permissions to role
     */
    public function assignPermissions($roleId, $permissionIds) {
        // Remove existing permissions
        $this->db->delete('role_permissions', 'role_id = :role_id', ['role_id' => $roleId]);
        
        // Add new permissions
        if (!empty($permissionIds)) {
            foreach ($permissionIds as $permissionId) {
                try {
                    $this->db->insert('role_permissions', [
                        'role_id' => $roleId,
                        'permission_id' => (int)$permissionId
                    ]);
                } catch (\Exception $e) {
                    // Skip duplicates
                }
            }
        }
        
        return true;
    }
    
    /**
     * Generate slug from name
     */
    private function generateSlug($name) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $baseSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Check if slug exists
     */
    private function slugExists($slug, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM roles WHERE slug = :slug";
        $params = ['slug' => $slug];
        
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return ($result['count'] ?? 0) > 0;
    }
}

