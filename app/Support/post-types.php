<?php
/**
 * Register Default Post Types
 * This file registers the default post types for the system
 */

use App\Registry\PostTypeRegistry;

// Register Product post type
PostTypeRegistry::registerPostType('product', [
    'label' => 'Product',
    'labels' => [
        'name' => 'Products',
        'singular_name' => 'Product',
        'menu_name' => 'Products',
    ],
    'menu_icon' => 'fa-box',
    'menu_item_type' => 'product',
    'menu_item_label' => 'Product',
    'get_items_callback' => function($options) {
        $model = new \App\Models\Product();
        return $model->getAll(['is_active' => $options['active_only'] ?? 1]);
    },
    'get_item_callback' => function($id) {
        $model = new \App\Models\Product();
        return $model->getById($id);
    },
]);

// Register Page post type
PostTypeRegistry::registerPostType('page', [
    'label' => 'Page',
    'labels' => [
        'name' => 'Pages',
        'singular_name' => 'Page',
        'menu_name' => 'Pages',
    ],
    'menu_icon' => 'fa-file-alt',
    'menu_item_type' => 'page',
    'menu_item_label' => 'Page',
    'get_items_callback' => function($options) {
        $model = new \App\Models\Page();
        return $model->getAll(['is_active' => $options['active_only'] ?? 1]);
    },
    'get_item_callback' => function($id) {
        $model = new \App\Models\Page();
        return $model->getById($id);
    },
]);

// Register Service post type
PostTypeRegistry::registerPostType('service', [
    'label' => 'Service',
    'labels' => [
        'name' => 'Services',
        'singular_name' => 'Service',
        'menu_name' => 'Services',
    ],
    'menu_icon' => 'fa-concierge-bell',
    'menu_item_type' => 'service',
    'menu_item_label' => 'Service',
    'get_items_callback' => function($options) {
        $model = new \App\Models\Service();
        return $model->getAll(['is_active' => $options['active_only'] ?? 1]);
    },
    'get_item_callback' => function($id) {
        $model = new \App\Models\Service();
        return $model->getById($id);
    },
]);

// Register Category taxonomy
PostTypeRegistry::registerTaxonomy('category', ['product'], [
    'label' => 'Category',
    'labels' => [
        'name' => 'Categories',
        'singular_name' => 'Category',
        'menu_name' => 'Categories',
    ],
    'hierarchical' => true,
    'menu_item_type' => 'category',
    'menu_item_label' => 'Category',
    'get_items_callback' => function($options) {
        $model = new \App\Models\Category();
        return $model->getAll($options['active_only'] ?? true);
    },
    'get_item_callback' => function($id) {
        $model = new \App\Models\Category();
        return $model->getById($id);
    },
]);

// Trigger hook for custom post type registration
if (function_exists('do_action')) {
    do_action('register_post_types');
    do_action('register_taxonomies');
}
