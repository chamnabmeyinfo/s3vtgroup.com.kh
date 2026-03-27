# Menu System Upgrade Guide

## 🎉 Overview

The menu system has been upgraded to be **WordPress-like** with extensive extensibility features. This guide covers all new features and how to use them.

---

## ✨ New Features

### 1. **Post Type Registry System**

The system now supports **any post type** through a registry system, similar to WordPress.

#### Registering a New Post Type

```php
use App\Registry\PostTypeRegistry;

PostTypeRegistry::registerPostType('custom_post', [
    'label' => 'Custom Post',
    'labels' => [
        'name' => 'Custom Posts',
        'singular_name' => 'Custom Post',
    ],
    'menu_icon' => 'fa-file',
    'menu_item_type' => 'custom_post',
    'get_items_callback' => function($options) {
        // Return array of items for menu
        return [
            ['id' => 1, 'name' => 'Item 1', 'url' => '/item-1'],
            ['id' => 2, 'name' => 'Item 2', 'url' => '/item-2'],
        ];
    },
    'get_item_callback' => function($id) {
        // Return single item by ID
        return ['id' => $id, 'name' => 'Item', 'url' => '/item'];
    },
]);
```

#### Registering a Taxonomy

```php
PostTypeRegistry::registerTaxonomy('custom_taxonomy', ['custom_post'], [
    'label' => 'Custom Taxonomy',
    'hierarchical' => true,
    'menu_item_type' => 'taxonomy_custom_taxonomy',
    'get_items_callback' => function($options) {
        // Return taxonomy terms
        return [
            ['id' => 1, 'name' => 'Term 1', 'slug' => 'term-1'],
        ];
    },
]);
```

**Location**: Add registration code to `app/Support/post-types.php` or create a custom plugin file.

---

### 2. **Enhanced Drag & Drop Menu Editor**

The menu editor now features a **WordPress-like interface**:

- **Left Panel**: "Add Items" panel with tabs for:
  - **Content Tab**: Shows all registered post types and taxonomies
  - **Custom Tab**: Add custom links
- **Right Panel**: Menu structure with drag-and-drop reordering
- **Visual Feedback**: Real-time updates as you drag items

#### Features:
- ✅ Drag items to reorder
- ✅ Drag items under other items to create sub-menus
- ✅ Click "Add" next to any item to add it to the menu
- ✅ Visual hierarchy with indentation
- ✅ Save button to persist changes

**Access**: `admin/menu-edit-enhanced.php` (or integrated into `menu-edit.php`)

---

### 3. **Menu Locations with Layout/Theme Support**

Menu locations now support:
- **Layout/Theme**: Assign locations to specific layouts or themes
- **Display Conditions**: Control when menus appear (future feature)
- **Custom CSS**: Per-location styling (future feature)

#### Creating a Location with Layout

1. Go to **Admin → Menu Locations**
2. Fill in:
   - **Location Name**: `custom_header`
   - **Area**: `header`
   - **Layout/Theme**: `modern` (optional)
   - **Description**: Brief description
3. Click **Create Location**
4. Assign a menu to the location

#### Using in Templates

```php
// Get menu for a specific location
$locationModel = new \App\Models\MenuLocation();
$menuData = $locationModel->getByLocation('custom_header');

if ($menuData && !empty($menuData['menu_id'])) {
    // Render menu
    $menuHelper = new \App\Helpers\MenuHelper();
    echo $menuHelper->render($menuData['menu_id'], ['location' => 'header']);
}
```

---

### 4. **Enhanced Mega Menu System**

Mega menus now support:
- **Animation Types**: Fade, slide, zoom (configurable)
- **Delay Settings**: Control hover delay
- **Mobile Breakpoint**: Custom breakpoint for mobile
- **Multiple Layouts**: Columns, grid, full-width

#### Enabling Mega Menu

1. Edit a menu item that has children
2. Scroll to **Mega Menu Settings**
3. Toggle **Enable Mega Menu** ON
4. Configure:
   - **Layout**: Columns, Grid, or Full Width
   - **Width**: Auto, 600px, 800px, 1000px, 1200px, or Full
   - **Columns**: 1-6 columns
   - **Animation**: Fade, Slide, Zoom
   - **Delay**: Hover delay in milliseconds
   - **Mobile Breakpoint**: Screen width for mobile view
5. Click **Update Item**
6. Click **Manage Mega Menu Content** to add widgets, products, or categories

---

### 5. **Hook/Filter System for Extensibility**

A WordPress-like hook system allows you to extend functionality:

#### Using Actions

```php
use App\Hooks\HookManager;

// Add an action
HookManager::addAction('menu_item_saved', function($itemId, $itemData) {
    // Do something when menu item is saved
    error_log("Menu item {$itemId} was saved");
}, 10, 2);

// Or use helper function
add_action('menu_item_saved', function($itemId, $itemData) {
    // Your code
}, 10, 2);
```

#### Using Filters

```php
// Modify menu item URL before rendering
add_filter('menu_item_url', function($url, $item) {
    // Modify URL
    if ($item['type'] === 'custom') {
        $url = '/custom-prefix' . $url;
    }
    return $url;
}, 10, 2);

// Apply filter
$url = apply_filters('menu_item_url', $item['url'], $item);
```

#### Available Hooks

**Actions:**
- `post_type_registered` - When a post type is registered
- `taxonomy_registered` - When a taxonomy is registered
- `menu_item_saved` - When a menu item is saved
- `menu_saved` - When a menu is saved
- `menu_location_created` - When a location is created

**Filters:**
- `menu_item_url` - Modify menu item URL
- `menu_item_title` - Modify menu item title
- `menu_item_classes` - Modify CSS classes
- `menu_structure` - Modify entire menu structure
- `post_type_items` - Modify items returned for a post type

---

## 📋 Database Migrations

Run the following SQL migration to enable all new features:

```sql
-- Run: database/run sql phpmyadmin/menu-system-upgrade.sql
```

This adds:
- `layout` and `theme` columns to `menu_locations`
- `post_type` and `taxonomy` columns to `menu_items`
- Metadata tables for extensibility
- Enhanced mega menu features
- Visibility conditions support

---

## 🚀 Usage Examples

### Example 1: Register a Blog Post Type

```php
// In app/Support/post-types.php or a plugin file

PostTypeRegistry::registerPostType('blog_post', [
    'label' => 'Blog Post',
    'menu_icon' => 'fa-blog',
    'get_items_callback' => function($options) {
        $db = \App\Database\Connection::getInstance();
        return $db->fetchAll(
            "SELECT id, title as name, slug, CONCAT('/blog/', slug) as url 
             FROM blog_posts 
             WHERE is_published = 1 
             ORDER BY created_at DESC"
        );
    },
]);
```

### Example 2: Custom Menu Item Filter

```php
// Modify all menu item URLs to include language prefix
add_filter('menu_item_url', function($url, $item) {
    $lang = $_SESSION['language'] ?? 'en';
    if ($lang !== 'en' && strpos($url, '/') === 0) {
        return '/' . $lang . $url;
    }
    return $url;
}, 10, 2);
```

### Example 3: Add Custom Post Type to Menu Editor

The post type will automatically appear in the "Add Items" panel once registered. No additional code needed!

---

## 🔧 Architecture

### File Structure

```
app/
├── Registry/
│   └── PostTypeRegistry.php      # Post type registration system
├── Hooks/
│   └── HookManager.php           # Hook/filter system
├── Support/
│   └── post-types.php            # Default post type registrations
└── Models/
    ├── Menu.php                  # Menu model
    ├── MenuItem.php              # Menu item model
    └── MenuLocation.php          # Location model

admin/
├── menu-edit.php                 # Original menu editor
├── menu-edit-enhanced.php        # Enhanced WordPress-like editor
└── menu-locations.php            # Location management

database/run sql phpmyadmin/
└── menu-system-upgrade.sql       # Database migrations
```

### Extensibility Points

1. **Post Type Registry**: Add any content type
2. **Hook System**: Intercept and modify behavior
3. **Metadata Tables**: Store custom data per item/menu/location
4. **Mega Menu Widgets**: Add custom content blocks
5. **Location Layouts**: Assign menus to specific layouts

---

## 📝 Best Practices

1. **Register Post Types Early**: Register in `app/Support/post-types.php` or bootstrap
2. **Use Hooks Wisely**: Don't overuse hooks; prefer direct model methods when possible
3. **Cache Menu Queries**: Use caching for frequently accessed menus
4. **Test Mega Menus**: Always test mega menus on mobile devices
5. **Document Custom Post Types**: Document any custom post types you register

---

## 🐛 Troubleshooting

### Post Type Not Showing in Menu Editor

- Check if post type is registered: `PostTypeRegistry::postTypeExists('your_type')`
- Verify `get_items_callback` returns correct format
- Check browser console for JavaScript errors

### Menu Items Not Saving

- Check database migration was run
- Verify `menu_items` table has `post_type` column
- Check PHP error logs

### Hooks Not Firing

- Verify HookManager is loaded: `require_once` in bootstrap
- Check hook name spelling
- Ensure callback is callable

---

## 🔮 Future Enhancements

The system is designed to be extensible. Future features can include:

- **Menu Item Visibility Conditions**: Show/hide based on user role, page, etc.
- **Menu Item Icons**: Font Awesome icon picker
- **Menu Templates**: Pre-built menu structures
- **Menu Analytics**: Track menu item clicks
- **A/B Testing**: Test different menu structures
- **Menu Import/Export**: Backup and restore menus

---

## 📞 Support

For issues or questions:
1. Check this guide first
2. Review code comments in source files
3. Check PHP error logs
4. Verify database migrations are applied

---

**Last Updated**: 2024
**Version**: 2.0
