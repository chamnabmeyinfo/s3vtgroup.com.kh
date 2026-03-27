<?php
/**
 * Post Type Registry
 * WordPress-like post type registration system
 */

namespace App\Registry;

class PostTypeRegistry
{
    private static $postTypes = [];
    private static $taxonomies = [];
    
    /**
     * Register a post type
     */
    public static function registerPostType($postType, $args = [])
    {
        $defaults = [
            'label' => ucfirst($postType),
            'labels' => [],
            'public' => true,
            'show_in_menu' => true,
            'menu_icon' => 'fa-file',
            'supports' => ['title', 'editor'],
            'has_archive' => false,
            'rewrite' => ['slug' => $postType],
            'query_var' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'menu_position' => null,
            'can_export' => true,
            'show_in_rest' => false,
            'rest_base' => $postType,
            'rest_controller_class' => null,
            'menu_item_type' => $postType, // For menu system
            'menu_item_label' => null, // Custom label for menu items
            'get_items_callback' => null, // Callback to get items for menu
            'get_item_callback' => null, // Callback to get single item
        ];
        
        $args = array_merge($defaults, $args);
        
        // Generate labels if not provided
        if (empty($args['labels'])) {
            $singular = $args['label'];
            $plural = $singular . 's';
            
            $args['labels'] = [
                'name' => $plural,
                'singular_name' => $singular,
                'add_new' => 'Add New',
                'add_new_item' => 'Add New ' . $singular,
                'edit_item' => 'Edit ' . $singular,
                'new_item' => 'New ' . $singular,
                'view_item' => 'View ' . $singular,
                'view_items' => 'View ' . $plural,
                'search_items' => 'Search ' . $plural,
                'not_found' => 'No ' . strtolower($plural) . ' found',
                'not_found_in_trash' => 'No ' . strtolower($plural) . ' found in trash',
                'all_items' => 'All ' . $plural,
                'archives' => $singular . ' Archives',
                'attributes' => $singular . ' Attributes',
                'insert_into_item' => 'Insert into ' . strtolower($singular),
                'uploaded_to_this_item' => 'Uploaded to this ' . strtolower($singular),
            ];
        }
        
        self::$postTypes[$postType] = $args;
        
        // Trigger hook
        self::doAction('post_type_registered', $postType, $args);
        
        return true;
    }
    
    /**
     * Get registered post type
     */
    public static function getPostType($postType)
    {
        return self::$postTypes[$postType] ?? null;
    }
    
    /**
     * Get all registered post types
     */
    public static function getPostTypes()
    {
        return self::$postTypes;
    }
    
    /**
     * Check if post type is registered
     */
    public static function postTypeExists($postType)
    {
        return isset(self::$postTypes[$postType]);
    }
    
    /**
     * Register a taxonomy
     */
    public static function registerTaxonomy($taxonomy, $objectTypes, $args = [])
    {
        $defaults = [
            'label' => ucfirst($taxonomy),
            'labels' => [],
            'public' => true,
            'hierarchical' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'query_var' => true,
            'rewrite' => ['slug' => $taxonomy],
            'capabilities' => [],
            'menu_item_type' => 'taxonomy_' . $taxonomy,
            'menu_item_label' => null,
            'get_items_callback' => null,
            'get_item_callback' => null,
        ];
        
        $args = array_merge($defaults, $args);
        
        // Ensure object_types is an array
        if (!is_array($objectTypes)) {
            $objectTypes = [$objectTypes];
        }
        
        $args['object_types'] = $objectTypes;
        
        self::$taxonomies[$taxonomy] = $args;
        
        // Trigger hook
        self::doAction('taxonomy_registered', $taxonomy, $args);
        
        return true;
    }
    
    /**
     * Get registered taxonomy
     */
    public static function getTaxonomy($taxonomy)
    {
        return self::$taxonomies[$taxonomy] ?? null;
    }
    
    /**
     * Get all registered taxonomies
     */
    public static function getTaxonomies()
    {
        return self::$taxonomies;
    }
    
    /**
     * Get taxonomies for a post type
     */
    public static function getTaxonomiesForPostType($postType)
    {
        $taxonomies = [];
        foreach (self::$taxonomies as $taxonomy => $args) {
            if (in_array($postType, $args['object_types'] ?? [])) {
                $taxonomies[$taxonomy] = $args;
            }
        }
        return $taxonomies;
    }
    
    /**
     * Get items for a post type (for menu system)
     */
    public static function getPostTypeItems($postType, $options = [])
    {
        $postTypeData = self::getPostType($postType);
        if (!$postTypeData) {
            return [];
        }
        
        // Use custom callback if provided
        if (!empty($postTypeData['get_items_callback']) && is_callable($postTypeData['get_items_callback'])) {
            return call_user_func($postTypeData['get_items_callback'], $options);
        }
        
        // Default: try to use model
        $modelClass = 'App\\Models\\' . ucfirst($postType);
        if (class_exists($modelClass)) {
            $model = new $modelClass();
            if (method_exists($model, 'getAll')) {
                $activeOnly = $options['active_only'] ?? true;
                return $model->getAll($activeOnly);
            }
        }
        
        return [];
    }
    
    /**
     * Get single item for a post type
     */
    public static function getPostTypeItem($postType, $id)
    {
        $postTypeData = self::getPostType($postType);
        if (!$postTypeData) {
            return null;
        }
        
        // Use custom callback if provided
        if (!empty($postTypeData['get_item_callback']) && is_callable($postTypeData['get_item_callback'])) {
            return call_user_func($postTypeData['get_item_callback'], $id);
        }
        
        // Default: try to use model
        $modelClass = 'App\\Models\\' . ucfirst($postType);
        if (class_exists($modelClass)) {
            $model = new $modelClass();
            if (method_exists($model, 'getById')) {
                return $model->getById($id);
            }
        }
        
        return null;
    }
    
    /**
     * Get items for a taxonomy (for menu system)
     */
    public static function getTaxonomyItems($taxonomy, $options = [])
    {
        $taxonomyData = self::getTaxonomy($taxonomy);
        if (!$taxonomyData) {
            return [];
        }
        
        // Use custom callback if provided
        if (!empty($taxonomyData['get_items_callback']) && is_callable($taxonomyData['get_items_callback'])) {
            return call_user_func($taxonomyData['get_items_callback'], $options);
        }
        
        // Default: try to use Category model (most common)
        if ($taxonomy === 'category') {
            $model = new \App\Models\Category();
            $activeOnly = $options['active_only'] ?? true;
            return $model->getAll($activeOnly);
        }
        
        return [];
    }
    
    /**
     * Execute action hook
     */
    private static function doAction($hook, ...$args)
    {
        // This will be integrated with Hook system
        if (class_exists('App\\Hooks\\HookManager')) {
            \App\Hooks\HookManager::doAction($hook, ...$args);
        }
    }
}
