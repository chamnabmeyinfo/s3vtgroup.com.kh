<?php
/**
 * Category Menu Sync Helper
 * Automatically syncs categories as menu items under Products
 */

namespace App\Helpers;

use App\Models\MenuItem;
use App\Models\Category;

if (!function_exists('App\Helpers\sync_categories_to_products_menu')) {
/**
 * Sync all categories as menu items under Products menu item
 */
function sync_categories_to_products_menu($productsMenuItemId, $options = [])
{
    try {
        $itemModel = new MenuItem();
        $categoryModel = new Category();
        
        // Get the Products menu item
        $productsItem = $itemModel->getById($productsMenuItemId);
        if (!$productsItem) {
            return ['success' => false, 'error' => 'Products menu item not found'];
        }
        
        $menuId = $productsItem['menu_id'];
        
        // Get all active categories
        $categories = $categoryModel->getAll(true);
        
        // Get existing category menu items under Products
        $existingItems = $itemModel->getByMenuId($menuId);
        $existingCategoryItems = [];
        foreach ($existingItems as $item) {
            if ($item['parent_id'] == $productsMenuItemId && $item['type'] === 'category') {
                $existingCategoryItems[$item['object_id']] = $item;
            }
        }
        
        $added = 0;
        $updated = 0;
        $removed = 0;
        
        // Get selected categories if option is enabled
        $useSelectedCategories = $options['use_selected_categories'] ?? false;
        $selectedCategoryIds = [];
        
        if ($useSelectedCategories) {
            try {
                $db = \App\Database\Connection::getInstance();
                $tableExists = false;
                try {
                    $db->fetchOne("SELECT 1 FROM menu_category_selections LIMIT 1");
                    $tableExists = true;
                } catch (\Exception $e) {
                    $tableExists = false;
                }
                
                if ($tableExists) {
                    $selected = $db->fetchAll(
                        "SELECT category_id, display_order FROM menu_category_selections 
                         WHERE menu_item_id IS NULL AND is_active = 1 
                         ORDER BY display_order ASC"
                    );
                    $selectedCategoryIds = array_column($selected, 'category_id');
                    $categoryOrders = [];
                    foreach ($selected as $sel) {
                        $categoryOrders[$sel['category_id']] = $sel['display_order'];
                    }
                }
            } catch (\Exception $e) {
                // Fallback to all categories
            }
        }
        
        // Filter categories if using selected
        if ($useSelectedCategories && !empty($selectedCategoryIds)) {
            $categories = array_filter($categories, function($cat) use ($selectedCategoryIds) {
                return in_array($cat['id'], $selectedCategoryIds);
            });
            // Reorder by selection order
            usort($categories, function($a, $b) use ($selectedCategoryIds, $categoryOrders) {
                $posA = array_search($a['id'], $selectedCategoryIds);
                $posB = array_search($b['id'], $selectedCategoryIds);
                if ($posA === false) return 1;
                if ($posB === false) return -1;
                $orderA = $categoryOrders[$a['id']] ?? $posA;
                $orderB = $categoryOrders[$b['id']] ?? $posB;
                return $orderA <=> $orderB;
            });
        }
        
        // Add or update category menu items
        foreach ($categories as $index => $category) {
            $categoryId = $category['id'];
            
            if (isset($existingCategoryItems[$categoryId])) {
                // Update existing item
                $existingItem = $existingCategoryItems[$categoryId];
                $itemModel->update($existingItem['id'], [
                    'title' => $category['name'],
                    'url' => url('products.php?category=' . $category['slug']),
                    'sort_order' => $index + 1,
                    'is_active' => 1
                ]);
                $updated++;
            } else {
                // Create new menu item
                $itemModel->create([
                    'menu_id' => $menuId,
                    'parent_id' => $productsMenuItemId,
                    'type' => 'category',
                    'object_id' => $categoryId,
                    'title' => $category['name'],
                    'url' => url('products.php?category=' . $category['slug']),
                    'sort_order' => $index + 1,
                    'is_active' => 1,
                    'target' => '_self'
                ]);
                $added++;
            }
        }
        
        // Remove menu items for categories that no longer exist or are not selected
        $currentCategoryIds = array_column($categories, 'id');
        foreach ($existingCategoryItems as $categoryId => $item) {
            if (!in_array($categoryId, $currentCategoryIds)) {
                $itemModel->delete($item['id']);
                $removed++;
            }
        }
        
        return [
            'success' => true,
            'added' => $added,
            'updated' => $updated,
            'removed' => $removed,
            'total' => count($categories)
        ];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Find Products menu item in a menu
 */
function find_products_menu_item($menuId)
{
    try {
        $itemModel = new MenuItem();
        $items = $itemModel->getByMenuId($menuId);
        
        // Look for menu item with title "Products" or URL containing "products.php"
        foreach ($items as $item) {
            if (empty($item['parent_id'])) {
                $title = strtolower(trim($item['title'] ?? ''));
                $url = strtolower($item['url'] ?? '');
                
                if ($title === 'products' || 
                    $title === 'product' ||
                    strpos($url, 'products.php') !== false ||
                    strpos($url, '/products') !== false) {
                    return $item;
                }
            }
        }
        
        return null;
    } catch (\Exception $e) {
        return null;
    }
}
}
