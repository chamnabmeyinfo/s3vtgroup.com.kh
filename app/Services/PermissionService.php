<?php
namespace App\Services;

class PermissionService {
    private $db;
    private static $tablesChecked = false;
    private static $tablesExist = null;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * Check if role management tables exist
     */
    private function checkTablesExist() {
        if (self::$tablesChecked) {
            return self::$tablesExist;
        }
        
        try {
            // Check if admin_users table exists
            $this->db->fetchOne("SELECT 1 FROM admin_users LIMIT 1");
            
            // Check if roles table exists (optional - if it doesn't, role management isn't set up)
            try {
                $this->db->fetchOne("SELECT 1 FROM roles LIMIT 1");
                self::$tablesExist = true;
            } catch (\Exception $e) {
                // Roles table doesn't exist - role management not set up yet
                self::$tablesExist = false;
            }
            
            self::$tablesChecked = true;
            return self::$tablesExist;
        } catch (\Exception $e) {
            // admin_users table doesn't exist - database not set up
            self::$tablesChecked = true;
            self::$tablesExist = false;
            return false;
        }
    }
    
    /**
     * Check if current user has permission
     */
    public function hasPermission($permissionSlug) {
        // If not logged in, no permission
        if (!session('admin_logged_in')) {
            return false;
        }
        
        // If tables don't exist, allow access (initial setup phase)
        if (!$this->checkTablesExist()) {
            return true; // Allow access during setup
        }
        
        try {
            $userId = session('admin_user_id');
            
            // Get user's role with slug
            $user = $this->db->fetchOne(
                "SELECT u.role_id, r.slug as role_slug 
                 FROM admin_users u 
                 LEFT JOIN roles r ON u.role_id = r.id 
                 WHERE u.id = :id AND u.is_active = 1",
                ['id' => $userId]
            );
            
            // If no user or no role, deny permission (unless role management not set up)
            if (!$user) {
                return false;
            }
            
            // Super Admin has access to everything
            if (!empty($user['role_slug']) && $user['role_slug'] === 'super_admin') {
                return true;
            }
            
            // If user has no role assigned, allow access (backward compatibility)
            if (empty($user['role_id'])) {
                return true;
            }
            
            // Check if role has permission
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM role_permissions rp
                 INNER JOIN permissions p ON rp.permission_id = p.id
                 WHERE rp.role_id = :role_id AND p.slug = :permission",
                [
                    'role_id' => $user['role_id'],
                    'permission' => $permissionSlug
                ]
            );
            
            return ($result['count'] ?? 0) > 0;
        } catch (\Exception $e) {
            // On error, allow access (fail open during setup)
            return true;
        }
    }
    
    /**
     * Check if current user has any of the permissions
     */
    public function hasAnyPermission(array $permissionSlugs) {
        foreach ($permissionSlugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if current user has all of the permissions
     */
    public function hasAllPermissions(array $permissionSlugs) {
        foreach ($permissionSlugs as $slug) {
            if (!$this->hasPermission($slug)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get current user's role
     */
    public function getCurrentUserRole() {
        if (!session('admin_logged_in')) {
            return null;
        }
        
        if (!$this->checkTablesExist()) {
            return null;
        }
        
        try {
            $userId = session('admin_user_id');
            
            $role = $this->db->fetchOne(
                "SELECT r.* FROM roles r
                 INNER JOIN admin_users u ON r.id = u.role_id
                 WHERE u.id = :id",
                ['id' => $userId]
            );
            
            return $role;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Get current user's permissions
     */
    public function getCurrentUserPermissions() {
        if (!session('admin_logged_in')) {
            return [];
        }
        
        if (!$this->checkTablesExist()) {
            return [];
        }
        
        try {
            $userId = session('admin_user_id');
            
            $permissions = $this->db->fetchAll(
                "SELECT p.* FROM permissions p
                 INNER JOIN role_permissions rp ON p.id = rp.permission_id
                 INNER JOIN admin_users u ON rp.role_id = u.role_id
                 WHERE u.id = :id
                 ORDER BY p.category, p.name",
                ['id' => $userId]
            );
            
            return $permissions;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Require permission (throws exception if not authorized)
     */
    public function requirePermission($permissionSlug) {
        if (!$this->hasPermission($permissionSlug)) {
            http_response_code(403);
            die('Access Denied: You do not have permission to access this resource.');
        }
    }
}
