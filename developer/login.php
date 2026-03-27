<?php
/**
 * Developer Panel Login - DISABLED
 * Developer login has been removed. Only Super Administrators can access
 * the developer panel directly from the admin panel.
 */
require_once __DIR__ . '/../bootstrap/app.php';

// Check if user is super admin from admin panel
$isSuperAdmin = false;
if (session('admin_logged_in')) {
    $roleSlug = session('admin_role_slug');
    if ($roleSlug === 'super_admin') {
        $isSuperAdmin = true;
        // Redirect to developer panel
        header('Location: ' . url('developer/index.php'));
        exit;
    }
}

// If logged in as admin but not super admin
if (session('admin_logged_in')) {
    header('Location: ' . url('admin/index.php'));
    exit;
}

// Not logged in - redirect to admin login
header('Location: ' . url('admin/login.php'));
exit;
