<?php

namespace App\Helpers;

/**
 * Page Helper
 * Common functions for page setup
 */
class PageHelper
{
    /**
     * Initialize a public page with under construction check
     * Use this at the top of any public-facing page
     */
    public static function initPublicPage()
    {
        // Check under construction mode
        UnderConstruction::show();
    }
    
    /**
     * Initialize an admin page (no under construction check needed)
     */
    public static function initAdminPage()
    {
        require_once __DIR__ . '/../../admin/includes/auth.php';
        // Admin pages automatically bypass under construction
    }
    
    /**
     * Initialize an API endpoint (no under construction check needed)
     */
    public static function initApiEndpoint()
    {
        header('Content-Type: application/json');
        // API endpoints automatically bypass under construction
    }
}

