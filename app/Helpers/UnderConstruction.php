<?php

namespace App\Helpers;

/**
 * Website Under Maintenance Helper
 * Handles maintenance mode for the website
 * Only logged-in admin users can access the frontend when enabled
 */
class UnderConstruction
{
    /**
     * Check if under construction mode is enabled
     */
    public static function isEnabled()
    {
        $config = self::getConfig();
        return $config['enabled'] ?? false;
    }
    
    /**
     * Check if current request should bypass under construction
     */
    public static function shouldBypass()
    {
        // Allow admin panel access
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Bypass for admin panel
        if (strpos($requestUri, '/admin/') !== false) {
            return true;
        }
        
        // Bypass for API endpoints
        if (strpos($requestUri, '/api/') !== false) {
            return true;
        }
        
        // Bypass for setup scripts
        if (strpos($requestUri, '/setup') !== false || 
            strpos($requestUri, 'setup.php') !== false ||
            strpos($requestUri, 'test-connection.php') !== false) {
            return true;
        }
        
        // Check if admin is logged in - allow logged-in admin users to bypass maintenance
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Allow logged-in admin users to access frontend during maintenance
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get configuration
     */
    private static function getConfig()
    {
        static $config = null;
        
        if ($config === null) {
            $configFile = __DIR__ . '/../../config/under-construction.php';
            
            if (file_exists($configFile)) {
                $config = require $configFile;
            } else {
                // Default config
                $config = [
                    'enabled' => false,
                    'message' => 'Website is under maintenance',
                    'progress' => 85,
                    'contact_email' => 'info@s3vgroup.com',
                    'contact_phone' => '+1 (234) 567-890'
                ];
            }
        }
        
        return $config;
    }
    
    /**
     * Show under construction page
     */
    public static function show()
    {
        if (self::shouldBypass()) {
            return false;
        }
        
        if (self::isEnabled()) {
            $pagePath = __DIR__ . '/../../under-construction.php';
            if (file_exists($pagePath)) {
                include $pagePath;
                exit;
            }
        }
        
        return false;
    }
    
    /**
     * Enable maintenance mode
     */
    public static function enable()
    {
        $configFile = __DIR__ . '/../../config/under-construction.php';
        $config = [
            'enabled' => true,
            'message' => 'Website is under maintenance',
            'progress' => 85,
            'contact_email' => 'info@s3vgroup.com',
            'contact_phone' => '+1 (234) 567-890'
        ];
        
        file_put_contents(
            $configFile,
            "<?php\nreturn " . var_export($config, true) . ";\n"
        );
    }
    
    /**
     * Disable maintenance mode
     */
    public static function disable()
    {
        $configFile = __DIR__ . '/../../config/under-construction.php';
        
        if (file_exists($configFile)) {
            $config = require $configFile;
            $config['enabled'] = false;
            
            file_put_contents(
                $configFile,
                "<?php\nreturn " . var_export($config, true) . ";\n"
            );
        }
    }
}

