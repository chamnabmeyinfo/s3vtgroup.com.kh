<?php
/**
 * Menu Helper Functions
 * WordPress-style menu helper functions
 */

namespace App\Helpers;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuLocation;

if (!function_exists('App\Helpers\get_menu')) {
/**
 * Get menu by ID or slug
 */
function get_menu($identifier)
{
    try {
        $menuModel = new Menu();
        
        if (is_numeric($identifier)) {
            return $menuModel->getById((int)$identifier);
        } else {
            return $menuModel->getBySlug($identifier);
        }
    } catch (\Exception $e) {
        return null;
    }
}
}

if (!function_exists('App\Helpers\get_menu_by_location')) {
/**
 * Get menu assigned to a location
 */
function get_menu_by_location($location)
{
    try {
        $locationModel = new MenuLocation();
        $locationData = $locationModel->getByLocation($location);
        
        if ($locationData && !empty($locationData['menu_id'])) {
            $menuModel = new Menu();
            return $menuModel->getById($locationData['menu_id']);
        }
        
        return null;
    } catch (\Exception $e) {
        return null;
    }
}
}

if (!function_exists('App\Helpers\render_menu')) {
/**
 * Render menu HTML
 */
function render_menu($menu_id_or_slug, $options = [])
{
    try {
        $menuModel = new Menu();
        $menu = is_numeric($menu_id_or_slug) 
            ? $menuModel->getById((int)$menu_id_or_slug) 
            : $menuModel->getBySlug($menu_id_or_slug);
        
        if (!$menu) {
            return '';
        }
        
        $itemModel = new MenuItem();
        $items = $itemModel->getByMenuId($menu['id'], true);
        
        if (empty($items)) {
            return '';
        }
        
        $menuFile = __DIR__ . '/../../includes/menu.php';
        if (file_exists($menuFile)) {
            extract([
                'menu' => $menu,
                'items' => $items,
                'options' => $options
            ]);
            ob_start();
            include $menuFile;
            return ob_get_clean();
        }
        
        return '';
    } catch (\Exception $e) {
        error_log('Menu render error: ' . $e->getMessage());
        return '';
    }
}
}

if (!function_exists('App\Helpers\menu_exists')) {
/**
 * Check if menu exists
 */
function menu_exists($identifier)
{
    return get_menu($identifier) !== null;
}
}
