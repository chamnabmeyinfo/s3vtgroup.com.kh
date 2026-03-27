<?php
/**
 * Developer Panel Authentication
 * Only allows access to Super Administrators logged into the admin panel
 * Developer login has been removed - access is only via admin panel
 */

// Check if logged into admin panel as super admin
$isSuperAdmin = false;
if (session('admin_logged_in')) {
    $roleSlug = session('admin_role_slug');
    // If role slug is not in session, try to load it
    if (empty($roleSlug)) {
        try {
            $userId = session('admin_user_id');
            if ($userId) {
                $user = db()->fetchOne(
                    "SELECT r.slug as role_slug 
                     FROM admin_users u 
                     LEFT JOIN roles r ON u.role_id = r.id 
                     WHERE u.id = :id",
                    ['id' => $userId]
                );
                if ($user && !empty($user['role_slug'])) {
                    $roleSlug = $user['role_slug'];
                    session('admin_role_slug', $roleSlug);
                }
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
    }
    if ($roleSlug === 'super_admin') {
        $isSuperAdmin = true;
    }
}

// Only allow super admins from admin panel
if (!$isSuperAdmin) {
    // If logged into admin but not super admin, show message
    if (session('admin_logged_in')) {
        http_response_code(403);
        die('
        <!DOCTYPE html>
        <html>
        <head>
            <title>Access Denied - Developer Panel</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        </head>
        <body class="bg-gray-100 flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-xl shadow-lg p-8 max-w-md text-center">
                <div class="mb-4">
                    <i class="fas fa-lock text-6xl text-red-500"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-4">Access Denied</h1>
                <p class="text-gray-600 mb-6">Only Super Administrators can access the Developer Panel.</p>
                <a href="' . url('admin/index.php') . '" class="btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Admin Panel
                </a>
            </div>
            <style>
                .btn-primary {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.5rem;
                    padding: 0.625rem 1.25rem;
                    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                    color: white;
                    border-radius: 0.5rem;
                    text-decoration: none;
                    font-weight: 600;
                    transition: all 0.2s ease;
                }
                .btn-primary:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
                }
            </style>
        </body>
        </html>');
    } else {
        // Not logged in at all - redirect to admin login
        header('Location: ' . url('admin/login.php'));
        exit;
    }
}

/**
 * Helper function to check if current user is super admin
 */
function isSuperAdmin() {
    // Only check admin session (developer login removed)
    if (session('admin_logged_in')) {
        $roleSlug = session('admin_role_slug');
        if ($roleSlug === 'super_admin') {
            return true;
        }
    }
    
    return false;
}
