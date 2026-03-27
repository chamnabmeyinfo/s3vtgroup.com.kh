<?php
if (!session('admin_logged_in')) {
    header('Location: ' . url('admin/login.php'));
    exit;
}

// Load user role information (only if tables exist)
if (!session('admin_role_id')) {
    try {
        $userId = session('admin_user_id');
        if ($userId) {
            // Check if admin_users table exists first
            try {
                db()->fetchOne("SELECT 1 FROM admin_users LIMIT 1");
                
                $user = db()->fetchOne(
                    "SELECT u.*, r.id as role_id, r.name as role_name, r.slug as role_slug 
                     FROM admin_users u 
                     LEFT JOIN roles r ON u.role_id = r.id 
                     WHERE u.id = :id AND u.is_active = 1",
                    ['id' => $userId]
                );
                
                if ($user) {
                    session('admin_role_id', $user['role_id']);
                    session('admin_role_name', $user['role_name']);
                    session('admin_role_slug', $user['role_slug']);
                }
            } catch (\Exception $e) {
                // Tables don't exist yet - skip role loading
            }
        }
    } catch (\Exception $e) {
        // Ignore errors during initial setup
    }
}

/**
 * Helper function to check permission
 */
function hasPermission($permissionSlug) {
    try {
        $service = new \App\Services\PermissionService();
        return $service->hasPermission($permissionSlug);
    } catch (\Exception $e) {
        // On error, allow access (fail open during setup)
        return true;
    }
}

/**
 * Require permission (redirects if not authorized)
 */
function requirePermission($permissionSlug) {
    try {
        if (!hasPermission($permissionSlug)) {
            http_response_code(403);
            die('<div style="text-align:center;padding:50px;"><h1>Access Denied</h1><p>You do not have permission to access this resource.</p><a href="' . url('admin/index.php') . '">Go to Dashboard</a></div>');
        }
    } catch (\Exception $e) {
        // On error during setup, allow access
        return;
    }
}
