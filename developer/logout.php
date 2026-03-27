<?php
/**
 * Developer Panel Logout
 * Since developer login is removed, this just redirects to admin panel
 */
require_once __DIR__ . '/../bootstrap/app.php';

// Redirect to admin panel (developer panel is accessed via admin panel only)
header('Location: ' . url('admin/index.php'));
exit;
