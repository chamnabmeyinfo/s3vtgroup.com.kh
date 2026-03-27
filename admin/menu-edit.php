<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Category;
use App\Models\Product;

$menuModel = new Menu();
$itemModel = new MenuItem();
$categoryModel = new Category();
$productModel = new Product();

$message = '';
$error = '';
$menu = null;
$menuId = !empty($_GET['id']) ? (int)$_GET['id'] : (!empty($_GET['menu_id']) ? (int)$_GET['menu_id'] : 0);
$isEdit = false;

// Handle AJAX requests FIRST - before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    // Check if JSON request
    if (strpos($contentType, 'application/json') !== false && !empty($input)) {
        $data = json_decode($input, true);
        
        if (!empty($data) && !empty($data['action'])) {
            header('Content-Type: application/json');
            
            if ($data['action'] === 'delete_item') {
                $itemId = (int)($data['item_id'] ?? 0);
                if ($itemId > 0) {
                    $deleted = $itemModel->delete($itemId);
                    echo json_encode(['success' => $deleted]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
                }
                exit;
            }
            
            if ($data['action'] === 'save_order') {
                $menuId = (int)($data['menu_id'] ?? 0);
                $orders = $data['orders'] ?? [];
                if ($menuId > 0 && !empty($orders)) {
                    $saved = $itemModel->reorder($menuId, $orders);
                    echo json_encode(['success' => $saved]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid data']);
                }
                exit;
            }
            
            if ($data['action'] === 'get_item') {
                $itemId = (int)($data['item_id'] ?? 0);
                if ($itemId > 0) {
                    $item = $itemModel->getById($itemId);
                    if ($item) {
                        echo json_encode(['success' => true, 'item' => $item]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Item not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
                }
                exit;
            }
            
            if ($data['action'] === 'find_products_item') {
                require_once __DIR__ . '/../app/Helpers/CategoryMenuSync.php';
                $menuId = (int)($data['menu_id'] ?? 0);
                if ($menuId > 0) {
                    $productsItem = \App\Helpers\find_products_menu_item($menuId);
                    if ($productsItem) {
                        echo json_encode(['success' => true, 'item_id' => $productsItem['id'], 'item' => $productsItem]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Products menu item not found. Please create a menu item titled "Products" first.']);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid menu ID']);
                }
                exit;
            }
            
            if ($data['action'] === 'sync_categories') {
                require_once __DIR__ . '/../app/Helpers/CategoryMenuSync.php';
                $menuId = (int)($data['menu_id'] ?? 0);
                $productsItemId = (int)($data['products_item_id'] ?? 0);
                
                if ($menuId > 0 && $productsItemId > 0) {
                    // Check if use selected categories
                    $useSelected = false;
                    try {
                        $setting = db()->fetchOne(
                            "SELECT value FROM settings WHERE `key` = 'products_menu_use_selected_categories'"
                        );
                        $useSelected = !empty($setting) && $setting['value'] == '1';
                    } catch (\Exception $e) {
                        $useSelected = false;
                    }
                    
                    $result = \App\Helpers\sync_categories_to_products_menu($productsItemId, [
                        'use_selected_categories' => $useSelected
                    ]);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid menu ID or Products item ID']);
                }
                exit;
            }
        }
    }
    
    // Handle form submission for add item
    if (!empty($_POST['action']) && $_POST['action'] === 'add_item') {
        header('Content-Type: application/json');
        
        $menuId = (int)($_POST['menu_id'] ?? 0);
        if ($menuId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid menu ID']);
            exit;
        }
        
        // Get custom title/URL from form (may come from custom fields or type-specific fields)
        $customTitle = null;
        $customUrl = null;
        
        // Check for title field - could be 'title' or type-specific field
        if (isset($_POST['title']) && trim($_POST['title']) !== '') {
            $customTitle = trim($_POST['title']);
        }
        // Check for type-specific title fields (for add form)
        if (empty($customTitle)) {
            $typeSpecificTitleFields = [
                'custom_title',
                'services_title',
                'ceo_message_title',
                'partners_title',
                'clients_title',
                'quality_certifications_title'
            ];
            foreach ($typeSpecificTitleFields as $fieldName) {
                if (isset($_POST[$fieldName]) && trim($_POST[$fieldName]) !== '') {
                    $customTitle = trim($_POST[$fieldName]);
                    break;
                }
            }
        }
        
        // Check for URL field
        if (isset($_POST['url']) && trim($_POST['url']) !== '') {
            $customUrl = trim($_POST['url']);
        }
        // Check for type-specific URL fields (for add form)
        if (empty($customUrl)) {
            $typeSpecificUrlFields = [
                'custom_url',
                'services_url',
                'ceo_message_url',
                'partners_url',
                'clients_url',
                'quality_certifications_url'
            ];
            foreach ($typeSpecificUrlFields as $fieldName) {
                if (isset($_POST[$fieldName]) && trim($_POST[$fieldName]) !== '') {
                    $customUrl = trim($_POST[$fieldName]);
                    break;
                }
            }
        }
        
        // Get object_id - check multiple possible field names
        $objectId = null;
        if (!empty($_POST['object_id'])) {
            $objectId = (int)$_POST['object_id'];
        } elseif (!empty($_POST['category_object_id'])) {
            $objectId = (int)$_POST['category_object_id'];
        } elseif (!empty($_POST['product_object_id'])) {
            $objectId = (int)$_POST['product_object_id'];
        } elseif (!empty($_POST['page_object_id'])) {
            $objectId = (int)$_POST['page_object_id'];
        } elseif (!empty($_POST['post_object_id'])) {
            $objectId = (int)$_POST['post_object_id'];
        }
        
        $data = [
            'menu_id' => $menuId,
            'type' => $_POST['item_type'] ?? 'custom',
            'object_id' => $objectId > 0 ? $objectId : null,
            'target' => $_POST['target'] ?? '_self',
            'icon' => trim($_POST['icon'] ?? '')
        ];
        
        // Handle custom title/URL for custom type
        if ($data['type'] === 'custom') {
            if (!empty($customTitle)) {
                $data['title'] = $customTitle;
            } elseif (isset($_POST['title']) && trim($_POST['title']) !== '') {
                $data['title'] = trim($_POST['title']);
            } elseif (isset($_POST['custom_title']) && trim($_POST['custom_title']) !== '') {
                $data['title'] = trim($_POST['custom_title']);
            }
            
            if (!empty($customUrl)) {
                $data['url'] = $customUrl;
            } elseif (isset($_POST['url']) && trim($_POST['url']) !== '') {
                $data['url'] = trim($_POST['url']);
            } elseif (isset($_POST['custom_url']) && trim($_POST['custom_url']) !== '') {
                $data['url'] = trim($_POST['custom_url']);
            } else {
                $data['url'] = '#';
            }
        }
        
        // Validate based on type
        if ($data['type'] === 'custom') {
            // For custom links, title is required
            if (empty($data['title'])) {
                echo json_encode(['success' => false, 'error' => 'Title is required for custom links']);
                exit;
            }
            // URL is optional for custom links, default to #
            if (empty($data['url'])) {
                $data['url'] = '#';
            }
        } elseif ($data['type'] === 'category') {
            if (empty($data['object_id'])) {
                echo json_encode(['success' => false, 'error' => 'Please select a category']);
                exit;
            }
            $cat = $categoryModel->getById($data['object_id']);
            if ($cat) {
                $data['title'] = $cat['name'];
                $data['url'] = url('products.php?category=' . $cat['slug']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Category not found']);
                exit;
            }
        } elseif ($data['type'] === 'product') {
            if (empty($data['object_id'])) {
                echo json_encode(['success' => false, 'error' => 'Please select a product']);
                exit;
            }
            $prod = $productModel->getById($data['object_id']);
            if ($prod) {
                $data['title'] = $prod['name'];
                $data['url'] = url('product.php?slug=' . $prod['slug']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Product not found']);
                exit;
            }
        } elseif ($data['type'] === 'page') {
            if (empty($data['object_id'])) {
                echo json_encode(['success' => false, 'error' => 'Please select a page']);
                exit;
            }
            try {
                $page = db()->fetchOne("SELECT id, title, slug FROM pages WHERE id = :id AND is_active = 1", ['id' => $data['object_id']]);
                if ($page) {
                    $data['title'] = $page['title'];
                    $data['url'] = url('page.php?slug=' . $page['slug']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Page not found']);
                    exit;
                }
            } catch (\Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Pages table not available']);
                exit;
            }
        } elseif ($data['type'] === 'post') {
            if (empty($data['object_id'])) {
                echo json_encode(['success' => false, 'error' => 'Please select a blog post']);
                exit;
            }
            try {
                $post = db()->fetchOne("SELECT id, title, slug FROM blog_posts WHERE id = :id AND is_published = 1", ['id' => $data['object_id']]);
                if ($post) {
                    $data['title'] = $post['title'];
                    // Blog posts use blog-post.php?slug=...
                    $data['url'] = url('blog-post.php?slug=' . urlencode($post['slug']));
                } else {
                    echo json_encode(['success' => false, 'error' => 'Blog post not found']);
                    exit;
                }
            } catch (\Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Blog posts table not available']);
                exit;
            }
        } elseif ($data['type'] === 'post_category') {
            if (empty($_POST['category_name'])) {
                echo json_encode(['success' => false, 'error' => 'Please select a blog category']);
                exit;
            }
            $categoryName = trim($_POST['category_name']);
            try {
                // Verify category exists
                $categoryExists = db()->fetchOne("SELECT DISTINCT category FROM blog_posts WHERE category = :cat AND is_published = 1 LIMIT 1", ['cat' => $categoryName]);
                if ($categoryExists) {
                    $data['title'] = $categoryName;
                    $data['url'] = url('blog.php?category=' . urlencode($categoryName));
                    $data['object_id'] = null; // Post categories don't have object_id
                } else {
                    echo json_encode(['success' => false, 'error' => 'Blog category not found']);
                    exit;
                }
            } catch (\Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Blog posts table not available']);
                exit;
            }
        } elseif ($data['type'] === 'services') {
            // Services page
            $data['title'] = !empty($customTitle) ? $customTitle : 'Services';
            $data['url'] = !empty($customUrl) ? $customUrl : url('services.php');
            $data['object_id'] = null;
        } elseif ($data['type'] === 'ceo_message') {
            // CEO Message page - use absolute path /ceo-message.php
            $data['title'] = !empty($customTitle) ? $customTitle : 'CEO Message';
            $data['url'] = !empty($customUrl) ? $customUrl : '/ceo-message.php';
            $data['object_id'] = null;
        } elseif ($data['type'] === 'partners') {
            // Partners - link to homepage with anchor or custom URL
            $data['title'] = !empty($customTitle) ? $customTitle : 'Partners';
            $data['url'] = !empty($customUrl) ? $customUrl : url('index.php#partners');
            $data['object_id'] = null;
        } elseif ($data['type'] === 'clients') {
            // Clients - link to homepage with anchor or custom URL
            $data['title'] = !empty($customTitle) ? $customTitle : 'Clients';
            $data['url'] = !empty($customUrl) ? $customUrl : url('index.php#clients');
            $data['object_id'] = null;
        } elseif ($data['type'] === 'quality_certifications') {
            // Quality Certifications - link to homepage with anchor or custom URL
            $data['title'] = !empty($customTitle) ? $customTitle : 'Quality Certifications';
            $data['url'] = !empty($customUrl) ? $customUrl : url('index.php#quality-certifications');
            $data['object_id'] = null;
        } elseif ($data['type'] === 'custom') {
            // URL is optional for custom links, default to #
            if (empty($data['url'])) {
                $data['url'] = '#';
            }
        }
        
        $itemId = $itemModel->create($data);
        echo json_encode(['success' => $itemId > 0, 'item_id' => $itemId]);
        exit;
    }
    
    // Handle form submission for update item
    if (!empty($_POST['action']) && $_POST['action'] === 'update_item') {
        header('Content-Type: application/json');
        
        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid item ID']);
            exit;
        }
        
        $data = [
            'type' => $_POST['item_type'] ?? 'custom',
            'target' => $_POST['target'] ?? $_POST['edit_target'] ?? '_self',
            'icon' => trim($_POST['icon'] ?? $_POST['edit_iconInput'] ?? ''),
            'mega_menu_enabled' => !empty($_POST['mega_menu_enabled']) ? 1 : 0,
            'mega_menu_layout' => $_POST['mega_menu_layout'] ?? 'columns',
            'mega_menu_width' => $_POST['mega_menu_width'] ?? 'auto',
            'mega_menu_columns' => !empty($_POST['mega_menu_columns']) ? (int)$_POST['mega_menu_columns'] : 3,
            'mega_menu_background' => $_POST['mega_menu_background'] ?? null,
            'mega_menu_custom_css' => $_POST['mega_menu_custom_css'] ?? null
        ];
        
        // Get object_id - check multiple possible field names
        $objectId = null;
        if (!empty($_POST['object_id'])) {
            $objectId = (int)$_POST['object_id'];
        } elseif (!empty($_POST['edit_category_object_id'])) {
            $objectId = (int)$_POST['edit_category_object_id'];
        } elseif (!empty($_POST['edit_product_object_id'])) {
            $objectId = (int)$_POST['edit_product_object_id'];
        } elseif (!empty($_POST['edit_page_object_id'])) {
            $objectId = (int)$_POST['edit_page_object_id'];
        } elseif (!empty($_POST['edit_post_object_id'])) {
            $objectId = (int)$_POST['edit_post_object_id'];
        }
        $data['object_id'] = $objectId > 0 ? $objectId : null;
        
        // Get existing item to preserve values if not provided
        $existingItem = $itemModel->getById($itemId);
        if (!$existingItem) {
            echo json_encode(['success' => false, 'error' => 'Item not found']);
            exit;
        }
        
        // Get custom title/URL from form (may come from custom fields or type-specific fields)
        $customTitle = null;
        $customUrl = null;
        
        // Check for title field - could be 'title' or type-specific field
        if (isset($_POST['title']) && trim($_POST['title']) !== '') {
            $customTitle = trim($_POST['title']);
        }
        // Check for edit_custom_title (custom link field - use before generic title due to duplicate names)
        if (empty($customTitle) && isset($_POST['edit_custom_title']) && trim($_POST['edit_custom_title']) !== '') {
            $customTitle = trim($_POST['edit_custom_title']);
        }
        // Check for type-specific title fields
        if (empty($customTitle)) {
            $typeSpecificTitleFields = [
                'edit_services_title',
                'edit_ceo_message_title',
                'edit_partners_title',
                'edit_clients_title',
                'edit_quality_certifications_title'
            ];
            foreach ($typeSpecificTitleFields as $fieldName) {
                if (isset($_POST[$fieldName]) && trim($_POST[$fieldName]) !== '') {
                    $customTitle = trim($_POST[$fieldName]);
                    break;
                }
            }
        }
        
        // Check for URL field
        if (isset($_POST['url']) && trim($_POST['url']) !== '') {
            $customUrl = trim($_POST['url']);
        }
        // Also check for edit_custom_url (the actual field ID in the form)
        if (empty($customUrl) && isset($_POST['edit_custom_url']) && trim($_POST['edit_custom_url']) !== '') {
            $customUrl = trim($_POST['edit_custom_url']);
        }
        // Check for type-specific URL fields
        if (empty($customUrl)) {
            $typeSpecificUrlFields = [
                'edit_services_url',
                'edit_ceo_message_url',
                'edit_partners_url',
                'edit_clients_url',
                'edit_quality_certifications_url'
            ];
            foreach ($typeSpecificUrlFields as $fieldName) {
                if (isset($_POST[$fieldName]) && trim($_POST[$fieldName]) !== '') {
                    $customUrl = trim($_POST[$fieldName]);
                    break;
                }
            }
        }
        
        // Validate based on type
        if ($data['type'] === 'custom') {
            // For custom links, title is required
            // First try to get from POST data (could be 'title' or 'edit_custom_title')
            $titleValue = null;
            if (!empty($customTitle)) {
                $titleValue = $customTitle;
            } elseif (isset($_POST['title']) && trim($_POST['title']) !== '') {
                $titleValue = trim($_POST['title']);
            } elseif (isset($_POST['edit_custom_title']) && trim($_POST['edit_custom_title']) !== '') {
                $titleValue = trim($_POST['edit_custom_title']);
            }
            
            if (!empty($titleValue)) {
                $data['title'] = $titleValue;
            } elseif (!empty($existingItem['title'])) {
                // If no title provided in form, preserve existing title
                $data['title'] = $existingItem['title'];
            } else {
                // Only error if there's no existing title to preserve
                echo json_encode(['success' => false, 'error' => 'Title is required for custom links']);
                exit;
            }
            
            // Use custom URL if provided, otherwise use existing URL, or default to #
            $urlValue = null;
            if (!empty($customUrl)) {
                $urlValue = $customUrl;
            } elseif (isset($_POST['url']) && trim($_POST['url']) !== '') {
                $urlValue = trim($_POST['url']);
            } elseif (isset($_POST['edit_custom_url']) && trim($_POST['edit_custom_url']) !== '') {
                $urlValue = trim($_POST['edit_custom_url']);
            }
            
            if (!empty($urlValue)) {
                $data['url'] = $urlValue;
            } elseif (!empty($existingItem['url'])) {
                $data['url'] = $existingItem['url'];
            } else {
                $data['url'] = '#';
            }
        } elseif ($data['type'] === 'category') {
            if (empty($data['object_id'])) {
                echo json_encode(['success' => false, 'error' => 'Please select a category']);
                exit;
            }
            $cat = $categoryModel->getById($data['object_id']);
            if ($cat) {
                // Use custom title if provided, otherwise use category name
                $data['title'] = !empty($customTitle) ? $customTitle : $cat['name'];
                // Use custom URL if provided, otherwise generate from category
                $data['url'] = !empty($customUrl) ? $customUrl : url('products.php?category=' . $cat['slug']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Category not found']);
                exit;
            }
        } elseif ($data['type'] === 'product') {
            if (empty($data['object_id'])) {
                echo json_encode(['success' => false, 'error' => 'Please select a product']);
                exit;
            }
            $prod = $productModel->getById($data['object_id']);
            if ($prod) {
                // Use custom title if provided, otherwise use product name
                $data['title'] = !empty($customTitle) ? $customTitle : $prod['name'];
                // Use custom URL if provided, otherwise generate from product
                $data['url'] = !empty($customUrl) ? $customUrl : url('product.php?slug=' . $prod['slug']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Product not found']);
                exit;
            }
        } elseif ($data['type'] === 'page') {
            if (empty($data['object_id'])) {
                echo json_encode(['success' => false, 'error' => 'Please select a page']);
                exit;
            }
            try {
                $page = db()->fetchOne("SELECT id, title, slug FROM pages WHERE id = :id AND is_active = 1", ['id' => $data['object_id']]);
                if ($page) {
                    // Use custom title if provided, otherwise use page title
                    $data['title'] = !empty($customTitle) ? $customTitle : $page['title'];
                    // Use custom URL if provided, otherwise generate from page
                    $data['url'] = !empty($customUrl) ? $customUrl : url('page.php?slug=' . $page['slug']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Page not found']);
                    exit;
                }
            } catch (\Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Pages table not available']);
                exit;
            }
        } elseif ($data['type'] === 'post') {
            if (empty($data['object_id'])) {
                echo json_encode(['success' => false, 'error' => 'Please select a blog post']);
                exit;
            }
            try {
                $post = db()->fetchOne("SELECT id, title, slug FROM blog_posts WHERE id = :id AND is_published = 1", ['id' => $data['object_id']]);
                if ($post) {
                    // Use custom title if provided, otherwise use post title
                    $data['title'] = !empty($customTitle) ? $customTitle : $post['title'];
                    // Use custom URL if provided, otherwise generate from post
                    $data['url'] = !empty($customUrl) ? $customUrl : url('blog-post.php?slug=' . urlencode($post['slug']));
                } else {
                    echo json_encode(['success' => false, 'error' => 'Blog post not found']);
                    exit;
                }
            } catch (\Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Blog posts table not available']);
                exit;
            }
        } elseif ($data['type'] === 'post_category') {
            // Check for category_name in POST (could be from edit form or add form)
            $categoryNameValue = null;
            if (isset($_POST['category_name']) && trim($_POST['category_name']) !== '') {
                $categoryNameValue = trim($_POST['category_name']);
            } elseif (isset($_POST['edit_post_category_name']) && trim($_POST['edit_post_category_name']) !== '') {
                $categoryNameValue = trim($_POST['edit_post_category_name']);
            }
            
            if (empty($categoryNameValue)) {
                echo json_encode(['success' => false, 'error' => 'Please select a blog category']);
                exit;
            }
            $categoryName = $categoryNameValue;
            
            try {
                $categoryExists = db()->fetchOne("SELECT DISTINCT category FROM blog_posts WHERE category = :cat AND is_published = 1 LIMIT 1", ['cat' => $categoryName]);
                if ($categoryExists) {
                    // Use custom title if provided, otherwise use category name
                    $data['title'] = !empty($customTitle) ? $customTitle : $categoryName;
                    // Use custom URL if provided, otherwise generate from category
                    $data['url'] = !empty($customUrl) ? $customUrl : url('blog.php?category=' . urlencode($categoryName));
                    $data['object_id'] = null;
                } else {
                    echo json_encode(['success' => false, 'error' => 'Blog category not found']);
                    exit;
                }
            } catch (\Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Blog posts table not available']);
                exit;
            }
        } elseif ($data['type'] === 'services') {
            // Services page
            $data['title'] = !empty($customTitle) ? $customTitle : 'Services';
            $data['url'] = !empty($customUrl) ? $customUrl : url('services.php');
            $data['object_id'] = null;
        } elseif ($data['type'] === 'ceo_message') {
            // CEO Message page - use absolute path /ceo-message.php
            $data['title'] = !empty($customTitle) ? $customTitle : 'CEO Message';
            $data['url'] = !empty($customUrl) ? $customUrl : '/ceo-message.php';
            $data['object_id'] = null;
        } elseif ($data['type'] === 'partners') {
            // Partners - link to homepage with anchor or custom URL
            $data['title'] = !empty($customTitle) ? $customTitle : 'Partners';
            $data['url'] = !empty($customUrl) ? $customUrl : url('index.php#partners');
            $data['object_id'] = null;
        } elseif ($data['type'] === 'clients') {
            // Clients - link to homepage with anchor or custom URL
            $data['title'] = !empty($customTitle) ? $customTitle : 'Clients';
            $data['url'] = !empty($customUrl) ? $customUrl : url('index.php#clients');
            $data['object_id'] = null;
        } elseif ($data['type'] === 'quality_certifications') {
            // Quality Certifications - link to homepage with anchor or custom URL
            $data['title'] = !empty($customTitle) ? $customTitle : 'Quality Certifications';
            $data['url'] = !empty($customUrl) ? $customUrl : url('index.php#quality-certifications');
            $data['object_id'] = null;
        }
        
        $updated = $itemModel->update($itemId, $data);
        echo json_encode(['success' => $updated]);
        exit;
    }
}

// Get menu if editing (after AJAX handling, but before menu save handling)
if ($menuId > 0) {
    $menu = $menuModel->getById($menuId);
    if (!$menu) {
        header('Location: ' . url('admin/menus.php'));
        exit;
    }
    $isEdit = true;
}

// Handle menu save (only if not AJAX request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['menu_name']) && empty($_POST['action'])) {
    try {
        $data = [
            'name' => trim($_POST['menu_name']),
            'slug' => trim($_POST['menu_slug'] ?? ''),
            'description' => trim($_POST['menu_description'] ?? '')
        ];
        
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name'])));
        }
        
        if (empty($data['name'])) {
            $error = 'Menu name is required.';
        } else {
            if ($isEdit) {
                $menuModel->update($menuId, $data);
                $message = 'Menu updated successfully.';
                $menu = $menuModel->getById($menuId);
            } else {
                $menuId = $menuModel->create($data);
                if ($menuId) {
                    $message = 'Menu created successfully.';
                    $menu = $menuModel->getById($menuId);
                    $isEdit = true;
                } else {
                    $error = 'Failed to create menu.';
                }
            }
        }
    } catch (\Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get menu items
$items = [];
if ($isEdit) {
    $items = $itemModel->getByMenuId($menuId);
}

// Get data for dropdowns
$categories = $categoryModel->getAll(true);
$products = $productModel->getAll(true, 100);

// Get pages (if pages table exists)
$pages = [];
try {
    db()->fetchOne("SELECT 1 FROM pages LIMIT 1");
    $pages = db()->fetchAll("SELECT id, title, slug FROM pages WHERE is_active = 1 ORDER BY title ASC");
} catch (\Exception $e) {
    // Pages table doesn't exist yet
}

// Get blog posts (if blog_posts table exists)
$posts = [];
$postCategories = [];
try {
    db()->fetchOne("SELECT 1 FROM blog_posts LIMIT 1");
    $posts = db()->fetchAll("SELECT id, title, slug, category FROM blog_posts WHERE is_published = 1 ORDER BY title ASC");
    
    // Get unique post categories
    $postCategoryData = db()->fetchAll("SELECT DISTINCT category FROM blog_posts WHERE category IS NOT NULL AND category != '' AND is_published = 1 ORDER BY category ASC");
    foreach ($postCategoryData as $pc) {
        if (!empty($pc['category'])) {
            $postCategories[] = $pc['category'];
        }
    }
} catch (\Exception $e) {
    // Blog posts table doesn't exist yet
}

$pageTitle = $isEdit ? 'Edit Menu' : 'Add Menu';
include __DIR__ . '/includes/header.php';
?>

<style>
.menu-item {
    cursor: move;
    transition: all 0.2s;
}
.menu-item:hover {
    background: #f3f4f6;
}
.menu-item.dragging {
    opacity: 0.5;
}
.menu-item-child {
    margin-left: 2rem;
    border-left: 2px solid #e5e7eb;
    padding-left: 1rem;
}
.sortable-ghost {
    opacity: 0.4;
    background: #dbeafe;
}
</style>

<div class="w-full p-4 md:p-6">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-xl p-4 md:p-6 mb-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-2">
                    <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> mr-2"></i>
                    <?= $isEdit ? 'Edit Menu' : 'Add Menu' ?>
                </h1>
                <p class="text-blue-100"><?= $isEdit ? 'Manage menu items' : 'Create a new menu' ?></p>
            </div>
            <div class="flex gap-3">
                <a href="<?= url('admin/menus.php') ?>" class="btn-secondary" style="background: rgba(255, 255, 255, 0.2); color: white;">
                    <i class="fas fa-arrow-left"></i>Back
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
        <i class="fas fa-check-circle mr-2"></i><?= escape($message) ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
        <i class="fas fa-exclamation-circle mr-2"></i><?= escape($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="menuForm" class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Menu Name *</label>
                <input type="text" name="menu_name" required value="<?= escape($menu['name'] ?? '') ?>"
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Menu Slug</label>
                <input type="text" name="menu_slug" value="<?= escape($menu['slug'] ?? '') ?>"
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="mt-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
            <textarea name="menu_description" rows="2"
                      class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= escape($menu['description'] ?? '') ?></textarea>
        </div>
        <div class="mt-6 flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-all">
                <i class="fas fa-save mr-2"></i><?= $isEdit ? 'Update Menu' : 'Create Menu' ?>
            </button>
        </div>
    </form>

    <?php if ($isEdit): ?>
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">Menu Items</h2>
            <div class="flex gap-2">
                <button onclick="syncCategoriesToProducts(event)" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold transition-all" title="Sync All Categories to Products Menu">
                    <i class="fas fa-sync-alt mr-2"></i>Sync Categories to Products
                </button>
                <button onclick="quickAddCeoMessage()" class="bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-lg font-semibold transition-all" title="Quick Add CEO Message">
                    <i class="fas fa-user-tie mr-2"></i>Quick Add: CEO Message
                </button>
                <button onclick="showAddItemModal()" class="btn-primary">
                    <i class="fas fa-plus"></i>Add Menu Item
                </button>
            </div>
        </div>

        <div id="menuItemsList" class="space-y-2">
            <?php if (empty($items)): ?>
                <p class="text-gray-500 text-center py-8">No menu items. Add your first item above.</p>
            <?php else: ?>
                <?php
                function renderMenuItems($items, $parentId = null) {
                    $children = array_filter($items, function($item) use ($parentId) {
                        $itemParentId = $item['parent_id'] ?? null;
                        return ($itemParentId === null && $parentId === null) || 
                               ($itemParentId !== null && (int)$itemParentId === (int)$parentId);
                    });
                    
                    if (empty($children)) return '';
                    
                    $html = '';
                    foreach ($children as $item) {
                        $itemChildren = renderMenuItems($items, $item['id']);
                        $hasChildren = !empty($itemChildren);
                        $indent = $parentId ? 'menu-item-child' : '';
                        $parentIdValue = $item['parent_id'] ?? '';
                        
                        $html .= '<div class="menu-item ' . $indent . ' bg-white border border-gray-200 rounded-lg p-4 flex items-center justify-between mb-2" data-item-id="' . $item['id'] . '" data-parent-id="' . $parentIdValue . '">';
                        $html .= '<div class="flex items-center gap-3 flex-1">';
                        $html .= '<i class="fas fa-grip-vertical text-gray-400 cursor-move hover:text-gray-600"></i>';
                        if ($item['icon']) {
                            $html .= '<i class="' . escape($item['icon']) . ' text-blue-600"></i>';
                        }
                        $html .= '<div class="flex-1">';
                        $html .= '<div class="font-semibold text-gray-900">' . escape($item['title']) . '</div>';
                        $html .= '<div class="text-sm text-gray-500 truncate">';
                        $html .= '<span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs mr-2">' . escape($item['type']) . '</span>';
                        $html .= escape($item['url'] ?? '');
                        $html .= '</div>';
                        $html .= '</div>';
                        $html .= '</div>';
                        $html .= '<div class="flex items-center gap-2">';
                        $html .= '<button onclick="editItem(' . $item['id'] . ')" class="action-btn action-btn-edit" title="Edit">';
                        $html .= '<i class="fas fa-edit"></i>';
                        $html .= '</button>';
                        $html .= '<button onclick="deleteItem(' . $item['id'] . ')" class="action-btn action-btn-delete" title="Delete">';
                        $html .= '<i class="fas fa-trash"></i>';
                        $html .= '</button>';
                        $html .= '</div>';
                        $html .= '</div>';
                        if ($hasChildren) {
                            $html .= '<div class="ml-8 mt-2 space-y-2 border-l-2 border-gray-200 pl-4" data-parent="' . $item['id'] . '">' . $itemChildren . '</div>';
                        }
                    }
                    return $html;
                }
                echo renderMenuItems($items, null);
                ?>
            <?php endif; ?>
        </div>

        <div class="mt-6 flex justify-end">
            <button onclick="saveMenuOrder()" class="btn-success btn-lg">
                <i class="fas fa-save"></i>Save Menu Order
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Edit Item Modal -->
<div id="editItemModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b bg-gradient-to-r from-green-600 to-emerald-600 text-white">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Edit Menu Item</h3>
                <button onclick="closeEditItemModal()" class="text-white hover:text-gray-200 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <form id="editItemForm" class="p-6">
            <input type="hidden" name="item_id" id="edit_item_id">
            <input type="hidden" name="action" value="update_item">
            <input type="hidden" name="item_type" id="edit_item_type" value="custom">
            
            <!-- Item Type Selection -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-tag mr-2 text-green-600"></i>Item Type *
                </label>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    <button type="button" onclick="selectEditItemType('custom')" class="edit-item-type-btn bg-blue-50 hover:bg-blue-100 border-2 border-blue-200 rounded-lg p-4 text-center transition-all active" data-type="custom">
                        <i class="fas fa-link text-blue-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Custom Link</div>
                        <div class="text-xs text-gray-500 mt-1">Any URL</div>
                    </button>
                    <button type="button" onclick="selectEditItemType('category')" class="edit-item-type-btn bg-indigo-50 hover:bg-indigo-100 border-2 border-indigo-200 rounded-lg p-4 text-center transition-all" data-type="category">
                        <i class="fas fa-folder text-indigo-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Category</div>
                        <div class="text-xs text-gray-500 mt-1">Product Category</div>
                    </button>
                    <button type="button" onclick="selectEditItemType('product')" class="edit-item-type-btn bg-purple-50 hover:bg-purple-100 border-2 border-purple-200 rounded-lg p-4 text-center transition-all" data-type="product">
                        <i class="fas fa-box text-purple-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Product</div>
                        <div class="text-xs text-gray-500 mt-1">Single Product</div>
                    </button>
                    <?php if (!empty($pages)): ?>
                    <button type="button" onclick="selectEditItemType('page')" class="edit-item-type-btn bg-green-50 hover:bg-green-100 border-2 border-green-200 rounded-lg p-4 text-center transition-all" data-type="page">
                        <i class="fas fa-file-alt text-green-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Page</div>
                        <div class="text-xs text-gray-500 mt-1">CMS Page</div>
                    </button>
                    <?php endif; ?>
                    <?php if (!empty($posts)): ?>
                    <button type="button" onclick="selectEditItemType('post')" class="edit-item-type-btn bg-orange-50 hover:bg-orange-100 border-2 border-orange-200 rounded-lg p-4 text-center transition-all" data-type="post">
                        <i class="fas fa-newspaper text-orange-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Blog Post</div>
                        <div class="text-xs text-gray-500 mt-1">Single Post</div>
                    </button>
                    <?php endif; ?>
                    <?php if (!empty($postCategories)): ?>
                    <button type="button" onclick="selectEditItemType('post_category')" class="edit-item-type-btn bg-yellow-50 hover:bg-yellow-100 border-2 border-yellow-200 rounded-lg p-4 text-center transition-all" data-type="post_category">
                        <i class="fas fa-tags text-yellow-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Blog Category</div>
                        <div class="text-xs text-gray-500 mt-1">Post Category</div>
                    </button>
                    <?php endif; ?>
                    <!-- New Feature Types -->
                    <button type="button" onclick="selectEditItemType('services')" class="edit-item-type-btn bg-teal-50 hover:bg-teal-100 border-2 border-teal-200 rounded-lg p-4 text-center transition-all" data-type="services">
                        <i class="fas fa-concierge-bell text-teal-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Services</div>
                        <div class="text-xs text-gray-500 mt-1">Services Page</div>
                    </button>
                    <button type="button" onclick="selectEditItemType('ceo_message')" class="edit-item-type-btn bg-pink-50 hover:bg-pink-100 border-2 border-pink-200 rounded-lg p-4 text-center transition-all" data-type="ceo_message">
                        <i class="fas fa-user-tie text-pink-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">CEO Message</div>
                        <div class="text-xs text-gray-500 mt-1">CEO Message Page</div>
                    </button>
                    <button type="button" onclick="selectEditItemType('partners')" class="edit-item-type-btn bg-cyan-50 hover:bg-cyan-100 border-2 border-cyan-200 rounded-lg p-4 text-center transition-all" data-type="partners">
                        <i class="fas fa-handshake text-cyan-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Partners</div>
                        <div class="text-xs text-gray-500 mt-1">Partners Section</div>
                    </button>
                    <button type="button" onclick="selectEditItemType('clients')" class="edit-item-type-btn bg-green-50 hover:bg-green-100 border-2 border-green-200 rounded-lg p-4 text-center transition-all" data-type="clients">
                        <i class="fas fa-building text-green-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Clients</div>
                        <div class="text-xs text-gray-500 mt-1">Clients Section</div>
                    </button>
                    <button type="button" onclick="selectEditItemType('quality_certifications')" class="edit-item-type-btn bg-amber-50 hover:bg-amber-100 border-2 border-amber-200 rounded-lg p-4 text-center transition-all" data-type="quality_certifications">
                        <i class="fas fa-certificate text-amber-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Quality Certifications</div>
                        <div class="text-xs text-gray-500 mt-1">Certifications Section</div>
                    </button>
                </div>
            </div>
            
            <!-- Dynamic Fields Based on Type (same as add modal) -->
            <div id="editItemTypeFields">
                <!-- Custom Link Fields -->
                <div id="editCustomLinkFields" class="item-type-fields">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2 text-gray-400"></i>Title *
                        </label>
                        <input type="text" name="edit_custom_title" id="edit_custom_title" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Menu Item Title">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-gray-400"></i>URL
                        </label>
                        <input type="text" name="edit_custom_url" id="edit_custom_url" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="/page.php, page.php?slug=about-us, or https://example.com/page.php">
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Accepts full URLs (https://...), absolute paths (/page.php), or relative paths (page.php?slug=...). Leave empty to use # as URL.
                        </p>
                    </div>
                </div>
                
                <!-- Category Fields -->
                <div id="editCategoryFields" class="item-type-fields hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-folder mr-2 text-indigo-600"></i>Select Category *
                        </label>
                        <select name="object_id" id="edit_category_object_id" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">-- Select Category --</option>
                            <?php 
                            $categoryTree = $categoryModel->getFlatTree(null, true);
                            foreach ($categoryTree as $cat): 
                                $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $cat['level'] ?? 0);
                                $prefix = ($cat['level'] ?? 0) > 0 ? '└─ ' : '';
                            ?>
                                <option value="<?= $cat['id'] ?>"><?= $indent . $prefix . escape($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Product Fields -->
                <div id="editProductFields" class="item-type-fields hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-box mr-2 text-purple-600"></i>Select Product *
                        </label>
                        <select name="object_id" id="edit_product_object_id" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" required>
                            <option value="">-- Select Product --</option>
                            <?php foreach ($products as $prod): ?>
                                <option value="<?= $prod['id'] ?>"><?= escape($prod['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Page Fields -->
                <?php if (!empty($pages)): ?>
                <div id="editPageFields" class="item-type-fields hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-file-alt mr-2 text-green-600"></i>Select Page *
                        </label>
                        <select name="object_id" id="edit_page_object_id" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                            <option value="">-- Select Page --</option>
                            <?php foreach ($pages as $page): ?>
                                <option value="<?= $page['id'] ?>"><?= escape($page['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Post Fields -->
                <?php if (!empty($posts)): ?>
                <div id="editPostFields" class="item-type-fields hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-newspaper mr-2 text-orange-600"></i>Select Blog Post *
                        </label>
                        <select name="object_id" id="edit_post_object_id" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                            <option value="">-- Select Blog Post --</option>
                            <?php foreach ($posts as $post): ?>
                                <option value="<?= $post['id'] ?>"><?= escape($post['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Post Category Fields -->
                <?php if (!empty($postCategories)): ?>
                <div id="editPostCategoryFields" class="item-type-fields hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-tags mr-2 text-yellow-600"></i>Select Blog Category *
                        </label>
                        <select name="category_name" id="edit_post_category_name" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" required>
                            <option value="">-- Select Blog Category --</option>
                            <?php foreach ($postCategories as $postCat): ?>
                                <option value="<?= escape($postCat) ?>"><?= escape($postCat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Services Fields -->
                <div id="editServicesFields" class="item-type-fields hidden">
                    <div class="mb-4 bg-teal-50 border border-teal-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-teal-600 mr-2 mt-1"></i>
                            <div>
                                <p class="text-sm text-teal-800 font-medium mb-2">Services Page</p>
                                <p class="text-xs text-teal-700">This will link to the Services page. You can customize the title and URL below if needed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2 text-teal-600"></i>Custom Title (Optional)
                        </label>
                        <input type="text" name="title" id="edit_services_title" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="Services (default)">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use "Services" as the default title</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-teal-600"></i>Custom URL (Optional)
                        </label>
                        <input type="text" name="url" id="edit_services_url" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="services.php or https://example.com/services.php">
                        <p class="text-xs text-gray-500 mt-1">Accepts full URLs (https://...) or relative paths (services.php). Leave empty to use default.</p>
                    </div>
                </div>
                
                <!-- CEO Message Fields -->
                <div id="editCeoMessageFields" class="item-type-fields hidden">
                    <div class="mb-4 bg-pink-50 border border-pink-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-pink-600 mr-2 mt-1"></i>
                            <div>
                                <p class="text-sm text-pink-800 font-medium mb-2">CEO Message Page</p>
                                <p class="text-xs text-pink-700">This will link to the CEO Message page. You can customize the title and URL below if needed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2 text-pink-600"></i>Custom Title (Optional)
                        </label>
                        <input type="text" name="title" id="edit_ceo_message_title" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" placeholder="CEO Message (default)">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use "CEO Message" as the default title</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-pink-600"></i>Custom URL (Optional)
                        </label>
                        <input type="text" name="url" id="edit_ceo_message_url" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" placeholder="ceo-message.php or https://example.com/ceo-message.php">
                        <p class="text-xs text-gray-500 mt-1">Accepts full URLs (https://...), absolute paths (/ceo-message.php), or relative paths (ceo-message.php). Leave empty to use default.</p>
                    </div>
                </div>
                
                <!-- Partners Fields -->
                <div id="editPartnersFields" class="item-type-fields hidden">
                    <div class="mb-4 bg-cyan-50 border border-cyan-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-cyan-600 mr-2 mt-1"></i>
                            <div>
                                <p class="text-sm text-cyan-800 font-medium mb-2">Partners</p>
                                <p class="text-xs text-cyan-700">This will link to the Partners section. You can customize the title and URL below if needed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2 text-cyan-600"></i>Custom Title (Optional)
                        </label>
                        <input type="text" name="title" id="edit_partners_title" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500" placeholder="Partners (default)">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use "Partners" as the default title</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-cyan-600"></i>Custom URL (Optional)
                        </label>
                        <input type="text" name="url" id="edit_partners_url" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500" placeholder="index.php#partners or https://example.com/index.php#partners">
                        <p class="text-xs text-gray-500 mt-1">Accepts full URLs (https://...) or relative paths (index.php#partners). Leave empty to use default.</p>
                    </div>
                </div>
                
                <!-- Clients Fields -->
                <div id="editClientsFields" class="item-type-fields hidden">
                    <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-green-600 mr-2 mt-1"></i>
                            <div>
                                <p class="text-sm text-green-800 font-medium mb-2">Clients</p>
                                <p class="text-xs text-green-700">This will link to the Clients section. You can customize the title and URL below if needed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2 text-green-600"></i>Custom Title (Optional)
                        </label>
                        <input type="text" name="title" id="edit_clients_title" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Clients (default)">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use "Clients" as the default title</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-green-600"></i>Custom URL (Optional)
                        </label>
                        <input type="text" name="url" id="edit_clients_url" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="index.php#clients or https://example.com/index.php#clients">
                        <p class="text-xs text-gray-500 mt-1">Accepts full URLs (https://...) or relative paths (index.php#clients). Leave empty to use default.</p>
                    </div>
                </div>
                
                <!-- Quality Certifications Fields -->
                <div id="editQualityCertificationsFields" class="item-type-fields hidden">
                    <div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-amber-600 mr-2 mt-1"></i>
                            <div>
                                <p class="text-sm text-amber-800 font-medium mb-2">Quality Certifications</p>
                                <p class="text-xs text-amber-700">This will link to the Quality Certifications section. You can customize the title and URL below if needed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2 text-amber-600"></i>Custom Title (Optional)
                        </label>
                        <input type="text" name="title" id="edit_quality_certifications_title" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="Quality Certifications (default)">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use "Quality Certifications" as the default title</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-amber-600"></i>Custom URL (Optional)
                        </label>
                        <input type="text" name="url" id="edit_quality_certifications_url" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="index.php#quality-certifications or https://example.com/index.php#quality-certifications">
                        <p class="text-xs text-gray-500 mt-1">Accepts full URLs (https://...) or relative paths (index.php#quality-certifications). Leave empty to use default.</p>
                    </div>
                </div>
            </div>
            
            <!-- Common Fields -->
            <div class="border-t pt-4 mt-4">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-icons mr-2 text-gray-400"></i>Icon
                    </label>
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <input type="text" name="icon" id="edit_iconInput" placeholder="Click to choose an icon" readonly
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg bg-gray-50 cursor-pointer"
                                   onclick="openEditIconPicker()">
                        </div>
                        <button type="button" onclick="openEditIconPicker()" 
                                class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all font-semibold">
                            <i class="fas fa-icons mr-2"></i>Choose Icon
                        </button>
                        <button type="button" onclick="clearEditIcon()" 
                                class="px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all"
                                title="Clear icon">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="editSelectedIconPreview" class="mt-2 text-center"></div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-external-link-alt mr-2 text-gray-400"></i>Link Target
                    </label>
                    <select name="target" id="edit_target" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="_self">Same Window (_self)</option>
                        <option value="_blank">New Window/Tab (_blank)</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t mt-4">
                <button type="button" onclick="closeEditItemModal()" class="btn-secondary">
                    <i class="fas fa-times"></i>Cancel
                </button>
                <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i class="fas fa-save"></i>Update Item
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addItemModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Add Menu Item</h3>
                <button onclick="closeAddItemModal()" class="text-white hover:text-gray-200 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <form id="addItemForm" class="p-6">
            <input type="hidden" name="menu_id" value="<?= $menuId ?>">
            <input type="hidden" name="action" value="add_item">
            <input type="hidden" name="item_type" id="item_type" value="custom">
            
            <!-- Item Type Selection -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-tag mr-2 text-blue-600"></i>Item Type *
                </label>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    <button type="button" onclick="selectItemType('custom')" class="item-type-btn bg-blue-50 hover:bg-blue-100 border-2 border-blue-200 rounded-lg p-4 text-center transition-all" data-type="custom">
                        <i class="fas fa-link text-blue-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Custom Link</div>
                        <div class="text-xs text-gray-500 mt-1">Any URL</div>
                    </button>
                    <button type="button" onclick="selectItemType('category')" class="item-type-btn bg-indigo-50 hover:bg-indigo-100 border-2 border-indigo-200 rounded-lg p-4 text-center transition-all" data-type="category">
                        <i class="fas fa-folder text-indigo-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Category</div>
                        <div class="text-xs text-gray-500 mt-1">Product Category</div>
                    </button>
                    <button type="button" onclick="selectItemType('product')" class="item-type-btn bg-purple-50 hover:bg-purple-100 border-2 border-purple-200 rounded-lg p-4 text-center transition-all" data-type="product">
                        <i class="fas fa-box text-purple-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Product</div>
                        <div class="text-xs text-gray-500 mt-1">Single Product</div>
                    </button>
                    <?php if (!empty($pages)): ?>
                    <button type="button" onclick="selectItemType('page')" class="item-type-btn bg-green-50 hover:bg-green-100 border-2 border-green-200 rounded-lg p-4 text-center transition-all" data-type="page">
                        <i class="fas fa-file-alt text-green-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Page</div>
                        <div class="text-xs text-gray-500 mt-1">CMS Page</div>
                    </button>
                    <?php endif; ?>
                    <?php if (!empty($posts)): ?>
                    <button type="button" onclick="selectItemType('post')" class="item-type-btn bg-orange-50 hover:bg-orange-100 border-2 border-orange-200 rounded-lg p-4 text-center transition-all" data-type="post">
                        <i class="fas fa-newspaper text-orange-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Blog Post</div>
                        <div class="text-xs text-gray-500 mt-1">Single Post</div>
                    </button>
                    <?php endif; ?>
                    <?php if (!empty($postCategories)): ?>
                    <button type="button" onclick="selectItemType('post_category')" class="item-type-btn bg-yellow-50 hover:bg-yellow-100 border-2 border-yellow-200 rounded-lg p-4 text-center transition-all" data-type="post_category">
                        <i class="fas fa-tags text-yellow-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Blog Category</div>
                        <div class="text-xs text-gray-500 mt-1">Post Category</div>
                    </button>
                    <?php endif; ?>
                    <!-- New Feature Types -->
                    <button type="button" onclick="selectItemType('services')" class="item-type-btn bg-teal-50 hover:bg-teal-100 border-2 border-teal-200 rounded-lg p-4 text-center transition-all" data-type="services">
                        <i class="fas fa-concierge-bell text-teal-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Services</div>
                        <div class="text-xs text-gray-500 mt-1">Services Page</div>
                    </button>
                    <button type="button" onclick="selectItemType('ceo_message')" class="item-type-btn bg-pink-50 hover:bg-pink-100 border-2 border-pink-200 rounded-lg p-4 text-center transition-all" data-type="ceo_message">
                        <i class="fas fa-user-tie text-pink-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">CEO Message</div>
                        <div class="text-xs text-gray-500 mt-1">CEO Message Page</div>
                    </button>
                    <button type="button" onclick="selectItemType('partners')" class="item-type-btn bg-cyan-50 hover:bg-cyan-100 border-2 border-cyan-200 rounded-lg p-4 text-center transition-all" data-type="partners">
                        <i class="fas fa-handshake text-cyan-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Partners</div>
                        <div class="text-xs text-gray-500 mt-1">Partners Section</div>
                    </button>
                    <button type="button" onclick="selectItemType('clients')" class="item-type-btn bg-green-50 hover:bg-green-100 border-2 border-green-200 rounded-lg p-4 text-center transition-all" data-type="clients">
                        <i class="fas fa-building text-green-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Clients</div>
                        <div class="text-xs text-gray-500 mt-1">Clients Section</div>
                    </button>
                    <button type="button" onclick="selectItemType('quality_certifications')" class="item-type-btn bg-amber-50 hover:bg-amber-100 border-2 border-amber-200 rounded-lg p-4 text-center transition-all" data-type="quality_certifications">
                        <i class="fas fa-certificate text-amber-600 text-2xl mb-2"></i>
                        <div class="font-semibold text-gray-800">Quality Certifications</div>
                        <div class="text-xs text-gray-500 mt-1">Certifications Section</div>
                    </button>
                </div>
            </div>
            
            <!-- Dynamic Fields Based on Type -->
            <div id="itemTypeFields">
                <!-- Custom Link Fields -->
                <div id="customLinkFields" class="item-type-fields">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2 text-gray-400"></i>Title *
                        </label>
                        <input type="text" name="custom_title" id="custom_title" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Menu Item Title">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-gray-400"></i>URL
                        </label>
                        <input type="text" name="custom_url" id="custom_url" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="/page.php, page.php?slug=about-us, or https://example.com/page.php">
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Accepts full URLs (https://...) or relative paths (page.php?slug=...)
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Leave empty to use # as URL
                        </p>
                    </div>
                </div>
                
                <!-- Category Fields -->
                <div id="categoryFields" class="item-type-fields hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-folder mr-2 text-indigo-600"></i>Select Category *
                        </label>
                        <select name="category_object_id" id="category_object_id" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Select Category --</option>
                            <?php 
                            $categoryTree = $categoryModel->getFlatTree(null, true);
                            foreach ($categoryTree as $cat): 
                                $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $cat['level'] ?? 0);
                                $prefix = ($cat['level'] ?? 0) > 0 ? '└─ ' : '';
                            ?>
                                <option value="<?= $cat['id'] ?>"><?= $indent . $prefix . escape($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Select a product category to link to
                        </p>
                    </div>
                </div>
                
                <!-- Product Fields -->
                <div id="productFields" class="item-type-fields hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-box mr-2 text-purple-600"></i>Select Product *
                        </label>
                        <select name="product_object_id" id="product_object_id" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <option value="">-- Select Product --</option>
                            <?php foreach ($products as $prod): ?>
                                <option value="<?= $prod['id'] ?>"><?= escape($prod['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Select a product to link to
                        </p>
                    </div>
                </div>
                
                <!-- Page Fields -->
                <?php if (!empty($pages)): ?>
                <div id="pageFields" class="item-type-fields hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-file-alt mr-2 text-green-600"></i>Select Page *
                        </label>
                        <select name="page_object_id" id="page_object_id" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">-- Select Page --</option>
                            <?php foreach ($pages as $page): ?>
                                <option value="<?= $page['id'] ?>"><?= escape($page['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Select a CMS page to link to
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Post Fields -->
                <?php if (!empty($posts)): ?>
                <div id="postFields" class="item-type-fields hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-newspaper mr-2 text-orange-600"></i>Select Blog Post *
                        </label>
                        <select name="post_object_id" id="post_object_id" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            <option value="">-- Select Blog Post --</option>
                            <?php foreach ($posts as $post): ?>
                                <option value="<?= $post['id'] ?>"><?= escape($post['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Select a blog post to link to
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Post Category Fields -->
                <?php if (!empty($postCategories)): ?>
                <div id="postCategoryFields" class="item-type-fields hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-tags mr-2 text-yellow-600"></i>Select Blog Category *
                        </label>
                        <select name="category_name" id="post_category_name" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="">-- Select Blog Category --</option>
                            <?php foreach ($postCategories as $postCat): ?>
                                <option value="<?= escape($postCat) ?>"><?= escape($postCat) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Select a blog post category to link to
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Services Fields -->
                <div id="servicesFields" class="item-type-fields hidden">
                    <div class="mb-4 bg-teal-50 border border-teal-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-teal-600 mr-2 mt-1"></i>
                            <div>
                                <p class="text-sm text-teal-800 font-medium mb-2">Services Page</p>
                                <p class="text-xs text-teal-700">This will link to the Services page. You can customize the title and URL below if needed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2 text-teal-600"></i>Custom Title (Optional)
                        </label>
                        <input type="text" name="title" id="services_title" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="Services (default)">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use "Services" as the default title</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-teal-600"></i>Custom URL (Optional)
                        </label>
                        <input type="text" name="url" id="services_url" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="services.php or https://example.com/services.php">
                        <p class="text-xs text-gray-500 mt-1">Accepts full URLs (https://...) or relative paths (services.php). Leave empty to use default.</p>
                    </div>
                </div>
                
                <!-- CEO Message Fields -->
                <div id="ceoMessageFields" class="item-type-fields hidden">
                    <div class="mb-4 bg-pink-50 border border-pink-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-pink-600 mr-2 mt-1"></i>
                            <div>
                                <p class="text-sm text-pink-800 font-medium mb-2">CEO Message Page</p>
                                <p class="text-xs text-pink-700">This will link to the CEO Message page. You can customize the title and URL below if needed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2 text-pink-600"></i>Custom Title (Optional)
                        </label>
                        <input type="text" name="title" id="ceo_message_title" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" placeholder="CEO Message (default)">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use "CEO Message" as the default title</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-pink-600"></i>Custom URL (Optional)
                        </label>
                        <input type="text" name="url" id="ceo_message_url" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500" placeholder="/ceo-message.php, ceo-message.php, or https://example.com/ceo-message.php">
                        <p class="text-xs text-gray-500 mt-1">Accepts full URLs (https://...), absolute paths (/ceo-message.php), or relative paths (ceo-message.php). Leave empty to use default.</p>
                    </div>
                </div>
                
                <!-- Partners Fields -->
                <div id="partnersFields" class="item-type-fields hidden">
                    <div class="mb-4 bg-cyan-50 border border-cyan-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-cyan-600 mr-2 mt-1"></i>
                            <div>
                                <p class="text-sm text-cyan-800 font-medium mb-2">Partners</p>
                                <p class="text-xs text-cyan-700">This will link to the Partners section. You can customize the title and URL below if needed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2 text-cyan-600"></i>Custom Title (Optional)
                        </label>
                        <input type="text" name="title" id="partners_title" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500" placeholder="Partners (default)">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use "Partners" as the default title</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-cyan-600"></i>Custom URL (Optional)
                        </label>
                        <input type="text" name="url" id="partners_url" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500" placeholder="index.php#partners or https://example.com/index.php#partners">
                        <p class="text-xs text-gray-500 mt-1">Accepts full URLs (https://...) or relative paths (index.php#partners). Leave empty to use default.</p>
                    </div>
                </div>
                
                <!-- Clients Fields -->
                <div id="clientsFields" class="item-type-fields hidden">
                    <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-green-600 mr-2 mt-1"></i>
                            <div>
                                <p class="text-sm text-green-800 font-medium mb-2">Clients</p>
                                <p class="text-xs text-green-700">This will link to the Clients section. You can customize the title and URL below if needed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2 text-green-600"></i>Custom Title (Optional)
                        </label>
                        <input type="text" name="title" id="clients_title" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Clients (default)">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use "Clients" as the default title</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-green-600"></i>Custom URL (Optional)
                        </label>
                        <input type="text" name="url" id="clients_url" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="index.php#clients or https://example.com/index.php#clients">
                        <p class="text-xs text-gray-500 mt-1">Accepts full URLs (https://...) or relative paths (index.php#clients). Leave empty to use default.</p>
                    </div>
                </div>
                
                <!-- Quality Certifications Fields -->
                <div id="qualityCertificationsFields" class="item-type-fields hidden">
                    <div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-amber-600 mr-2 mt-1"></i>
                            <div>
                                <p class="text-sm text-amber-800 font-medium mb-2">Quality Certifications</p>
                                <p class="text-xs text-amber-700">This will link to the Quality Certifications section. You can customize the title and URL below if needed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-2 text-amber-600"></i>Custom Title (Optional)
                        </label>
                        <input type="text" name="title" id="quality_certifications_title" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="Quality Certifications (default)">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use "Quality Certifications" as the default title</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-amber-600"></i>Custom URL (Optional)
                        </label>
                        <input type="text" name="url" id="quality_certifications_url" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="index.php#quality-certifications or https://example.com/index.php#quality-certifications">
                        <p class="text-xs text-gray-500 mt-1">Accepts full URLs (https://...) or relative paths (index.php#quality-certifications). Leave empty to use default.</p>
                    </div>
                </div>
            </div>
            
            <!-- Common Fields (shown for all types) -->
            <div class="border-t pt-4 mt-4">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-icons mr-2 text-gray-400"></i>Icon
                    </label>
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <input type="text" name="icon" id="iconInput" placeholder="Click to choose an icon" readonly
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg bg-gray-50 cursor-pointer"
                                   onclick="openIconPicker()">
                        </div>
                        <button type="button" onclick="openIconPicker()" 
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-semibold">
                            <i class="fas fa-icons mr-2"></i>Choose Icon
                        </button>
                        <button type="button" onclick="clearIcon()" 
                                class="px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all"
                                title="Clear icon">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="selectedIconPreview" class="mt-2 text-center"></div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-external-link-alt mr-2 text-gray-400"></i>Link Target
                    </label>
                    <select name="target" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="_self">Same Window (_self)</option>
                        <option value="_blank">New Window/Tab (_blank)</option>
                    </select>
                </div>
                
                <!-- Mega Menu Settings -->
                <div class="mb-4 border-t pt-4 mt-4">
                    <div class="flex items-center justify-between mb-4">
                        <label class="block text-sm font-semibold text-gray-700">
                            <i class="fas fa-th-large mr-2 text-purple-600"></i>Enable Mega Menu
                        </label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="mega_menu_enabled" id="mega_menu_enabled" value="1" class="sr-only peer" onchange="toggleMegaMenuSettings()">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                    
                    <div id="megaMenuSettings" class="hidden space-y-4 bg-purple-50 p-4 rounded-lg border border-purple-200">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-columns mr-2 text-purple-600"></i>Layout
                                </label>
                                <select name="mega_menu_layout" id="mega_menu_layout" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    <option value="columns">Columns</option>
                                    <option value="grid">Grid</option>
                                    <option value="fullwidth">Full Width</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-arrows-alt-h mr-2 text-purple-600"></i>Width
                                </label>
                                <select name="mega_menu_width" id="mega_menu_width" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    <option value="auto">Auto</option>
                                    <option value="600px">600px</option>
                                    <option value="800px">800px</option>
                                    <option value="1000px">1000px</option>
                                    <option value="1200px">1200px</option>
                                    <option value="full">Full Width</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-th mr-2 text-purple-600"></i>Number of Columns
                            </label>
                            <input type="number" name="mega_menu_columns" id="mega_menu_columns" min="1" max="6" value="3" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-palette mr-2 text-purple-600"></i>Background Color/Image
                            </label>
                            <input type="text" name="mega_menu_background" id="mega_menu_background" placeholder="#ffffff or url(...)" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-code mr-2 text-purple-600"></i>Custom CSS
                            </label>
                            <textarea name="mega_menu_custom_css" id="mega_menu_custom_css" rows="3" placeholder="Custom CSS styles..." class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 font-mono text-sm"></textarea>
                        </div>
                        <div class="pt-2 border-t">
                            <a href="mega-menu-manager.php?menu_item_id=<?= $menuItemId ?? '' ?>" id="megaMenuManagerLink" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all">
                                <i class="fas fa-cog mr-2"></i>Manage Mega Menu Content
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t mt-4">
                <button type="button" onclick="closeAddItemModal()" class="btn-secondary">
                    <i class="fas fa-times"></i>Cancel
                </button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-plus"></i>Add Item
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.item-type-btn {
    cursor: pointer;
    transition: all 0.2s;
}

.item-type-btn.active {
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    border-color: #3b82f6;
    color: white;
}

.item-type-btn.active i,
.item-type-btn.active .font-semibold,
.item-type-btn.active .text-xs {
    color: white !important;
}

.edit-item-type-btn {
    cursor: pointer;
    transition: all 0.2s;
}

.edit-item-type-btn.active {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-color: #10b981;
    color: white;
}

.edit-item-type-btn.active i,
.edit-item-type-btn.active .font-semibold,
.edit-item-type-btn.active .text-xs {
    color: white !important;
}

.item-type-fields {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Icon Picker Modal */
#iconPickerModal {
    max-height: 90vh;
    overflow-y: auto;
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 12px;
    max-height: 500px;
    overflow-y: auto;
    padding: 16px;
}

.icon-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px 8px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
}

.icon-item:hover {
    border-color: #3b82f6;
    background: #eff6ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.icon-item.selected {
    border-color: #3b82f6;
    background: #dbeafe;
}

.icon-item i {
    font-size: 24px;
    color: #4b5563;
    margin-bottom: 8px;
}

.icon-item.selected i {
    color: #1e40af;
}

.icon-item span {
    font-size: 10px;
    color: #6b7280;
    text-align: center;
    word-break: break-word;
}

.icon-item.selected span {
    color: #1e40af;
    font-weight: 600;
}
</style>

<!-- Icon Picker Modal -->
<div id="iconPickerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-6 rounded-t-xl flex items-center justify-between">
            <h3 class="text-xl font-bold">
                <i class="fas fa-icons mr-2"></i>Choose an Icon
            </h3>
            <button onclick="closeIconPicker()" class="text-white hover:text-gray-200 transition-colors text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 flex-1 overflow-y-auto">
            <div class="mb-4">
                <input type="text" id="iconSearch" placeholder="Search icons..." 
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                       onkeyup="filterIcons()">
            </div>
            <div id="iconGrid" class="icon-grid"></div>
        </div>
        <div class="border-t p-4 flex justify-end gap-3">
            <button onclick="closeIconPicker()" class="btn-secondary">
                Cancel
            </button>
            <button onclick="confirmIconSelection()" class="btn-primary">
                Select Icon
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" integrity="sha512-Eezs+g9Lq4TCCq0wae01s9PuNWzHYoCMkE97e2qdkYthpI0pzC3UGB03lgEHn2XM85hDOUF6qgqqszs+iXU4UA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
let sortableInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    const list = document.getElementById('menuItemsList');
    if (list && typeof Sortable !== 'undefined') {
        sortableInstance = new Sortable(list, {
            handle: '.fa-grip-vertical',
            animation: 150,
            ghostClass: 'sortable-ghost',
            group: 'menu-items',
            draggable: '.menu-item',
            onEnd: function(evt) {
                // Visual feedback
                console.log('Menu item moved');
            }
        });
    } else {
        console.error('SortableJS not loaded or menuItemsList not found');
    }
});

function showAddItemModal(type = null) {
    const form = document.getElementById('addItemForm');
    if (!form) return;
    
    // Reset form
    form.reset();
    
    // Reset all item type buttons
    document.querySelectorAll('.item-type-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Hide all type-specific fields
    document.querySelectorAll('.item-type-fields').forEach(field => {
        field.classList.add('hidden');
    });
    
    // Remove required attributes from all inputs
    form.querySelectorAll('input[required], select[required]').forEach(input => {
        input.removeAttribute('required');
    });
    
    // If type is provided, select it; otherwise default to custom
    const selectedType = type || 'custom';
    selectItemType(selectedType);
    
    document.getElementById('addItemModal').classList.remove('hidden');
}

function selectItemType(type) {
    const form = document.getElementById('addItemForm');
    if (!form) return;
    
    // Set the item type
    document.getElementById('item_type').value = type;
    
    // Update button states
    document.querySelectorAll('.item-type-btn').forEach(btn => {
        if (btn.getAttribute('data-type') === type) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    
    // Hide all fields
    document.querySelectorAll('.item-type-fields').forEach(field => {
        field.classList.add('hidden');
    });
    
    // Remove required from all inputs
    form.querySelectorAll('input, select').forEach(input => {
        input.removeAttribute('required');
    });
    
    // Show relevant fields based on type
    let fieldsToShow = [];
    let requiredFields = [];
    
    switch(type) {
        case 'custom':
            fieldsToShow = ['customLinkFields'];
            requiredFields = ['custom_title'];
            break;
        case 'category':
            fieldsToShow = ['categoryFields'];
            requiredFields = ['category_object_id'];
            break;
        case 'product':
            fieldsToShow = ['productFields'];
            requiredFields = ['product_object_id'];
            break;
        case 'page':
            fieldsToShow = ['pageFields'];
            requiredFields = ['page_object_id'];
            break;
        case 'post':
            fieldsToShow = ['postFields'];
            requiredFields = ['post_object_id'];
            break;
        case 'post_category':
            fieldsToShow = ['postCategoryFields'];
            requiredFields = ['post_category_name'];
            break;
        case 'services':
            fieldsToShow = ['servicesFields'];
            requiredFields = [];
            break;
        case 'ceo_message':
            fieldsToShow = ['ceoMessageFields'];
            requiredFields = [];
            break;
        case 'partners':
            fieldsToShow = ['partnersFields'];
            requiredFields = [];
            break;
        case 'clients':
            fieldsToShow = ['clientsFields'];
            requiredFields = [];
            break;
        case 'quality_certifications':
            fieldsToShow = ['qualityCertificationsFields'];
            requiredFields = [];
            break;
    }
    
    // Show the relevant fields
    fieldsToShow.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.remove('hidden');
        }
    });
    
    // Set required attributes
    requiredFields.forEach(fieldName => {
        const field = document.getElementById(fieldName) || form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.setAttribute('required', 'required');
        }
    });
    
    // For custom type, require title input by ID
    if (type === 'custom') {
        const titleInput = document.getElementById('custom_title');
        if (titleInput) {
            titleInput.setAttribute('required', 'required');
        }
    }
}

function closeAddItemModal() {
    document.getElementById('addItemModal').classList.add('hidden');
    document.getElementById('addItemForm').reset();
}

const addItemForm = document.getElementById('addItemForm');
if (addItemForm) {
    addItemForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validate form based on item type
        const itemType = document.getElementById('item_type').value;
        let validationError = '';
        
        if (itemType === 'custom') {
            const title = document.getElementById('custom_title')?.value.trim();
            if (!title) {
                validationError = 'Title is required for custom links';
            }
        } else if (itemType === 'category') {
            const objectId = document.getElementById('category_object_id')?.value;
            if (!objectId) {
                validationError = 'Please select a category';
            }
        } else if (itemType === 'product') {
            const objectId = document.getElementById('product_object_id')?.value;
            if (!objectId) {
                validationError = 'Please select a product';
            }
        } else if (itemType === 'page') {
            const objectId = document.getElementById('page_object_id')?.value;
            if (!objectId) {
                validationError = 'Please select a page';
            }
        } else if (itemType === 'post') {
            const objectId = document.getElementById('post_object_id')?.value;
            if (!objectId) {
                validationError = 'Please select a blog post';
            }
        } else if (itemType === 'post_category') {
            const categoryName = document.getElementById('post_category_name')?.value;
            if (!categoryName) {
                validationError = 'Please select a blog category';
            }
        } else if (itemType === 'services' || itemType === 'ceo_message' || itemType === 'partners' || itemType === 'quality_certifications') {
            // These types don't require validation - they have default values
        }
        
        if (validationError) {
            customAlert(validationError, 'Validation Error', 'error').then(() => {});
            return;
        }
        
        const formData = new FormData(this);
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
        
        try {
            const response = await fetch('<?= url("admin/menu-edit.php?id={$menuId}") ?>', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const result = await response.json();
            if (result.success) {
                closeAddItemModal();
                location.reload();
            } else {
                customAlert('Error adding item: ' + (result.error || 'Unknown error'), 'Error', 'error').then(() => {});
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            customAlert('Error: ' + error.message, 'Error', 'error').then(() => {});
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

function updateMenuStructure() {
    // Update parent relationships based on DOM structure
    const list = document.getElementById('menuItemsList');
    if (!list) return {};
    
    const orders = {};
    let orderIndex = 0;
    
    // Get all menu items in DOM order
    const allItems = list.querySelectorAll('.menu-item');
    
    allItems.forEach((item) => {
        const itemId = item.getAttribute('data-item-id');
        if (!itemId) return;
        
        // Find parent by checking DOM hierarchy
        let parentId = null;
        let parent = item.parentElement;
        
        // Look for parent menu item
        while (parent && parent !== list) {
            const parentItem = parent.querySelector('.menu-item[data-item-id]');
            if (parentItem && parentItem !== item) {
                // Check if this item is nested under parentItem
                const childContainer = parentItem.nextElementSibling;
                if (childContainer && childContainer.contains(item)) {
                    parentId = parentItem.getAttribute('data-item-id');
                    break;
                }
            }
            parent = parent.parentElement;
        }
        
        orderIndex++;
        orders[itemId] = {
            order: orderIndex,
            parent_id: parentId || null
        };
    });
    
    return orders;
}

function saveMenuOrder() {
    const orders = updateMenuStructure();
    
    if (Object.keys(orders).length === 0) {
        customAlert('No items to save', 'Information', 'info').then(() => {});
        return;
    }
    
    const saveBtn = document.querySelector('button[onclick="saveMenuOrder()"]');
    if (saveBtn) {
        saveBtn.disabled = true;
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
        
        fetch('<?= url("admin/menu-edit.php?id={$menuId}") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'save_order',
                menu_id: <?= $menuId ?>,
                orders: orders
            })
        }).then(res => {
            if (!res.ok) {
                throw new Error('Network response was not ok');
            }
            return res.json();
        }).then(data => {
            if (data.success) {
                customAlert('Menu order saved successfully!', 'Success', 'success').then(() => {
                    location.reload();
                });
            } else {
                customAlert('Error saving order: ' + (data.error || 'Unknown error'), 'Error', 'error').then(() => {});
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        }).catch(error => {
            customAlert('Error: ' + error.message, 'Error', 'error').then(() => {});
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        });
    }
}

// Sync Categories to Products Menu
async function syncCategoriesToProducts(evt) {
    const menuId = <?= $menuId ?>;
    if (!menuId || menuId <= 0) {
        customAlert('Invalid menu ID', 'Error', 'error').then(() => {});
        return;
    }
    
    // Find Products menu item
    const response = await fetch('<?= url("admin/menu-edit.php?id={$menuId}") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'find_products_item',
            menu_id: menuId
        })
    });
    
    const result = await response.json();
    if (!result.success || !result.item_id) {
        customAlert('Please create a "Products" menu item first, then use this feature to sync categories under it.', 'Info', 'info').then(() => {});
        return;
    }
    
    if (!confirm('This will sync all categories as sub-items under the Products menu. Existing category items will be updated. Continue?')) {
        return;
    }
    
    const btn = (evt && evt.target) ? evt.target : document.querySelector('button[onclick*="syncCategoriesToProducts"]');
    const originalText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Syncing...';
    }
    
    try {
        const syncResponse = await fetch('<?= url("admin/menu-edit.php?id={$menuId}") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'sync_categories',
                menu_id: menuId,
                products_item_id: result.item_id
            })
        });
        
        const syncResult = await syncResponse.json();
        if (syncResult.success) {
            customAlert(
                `Categories synced successfully!\n\nAdded: ${syncResult.added}\nUpdated: ${syncResult.updated}\nRemoved: ${syncResult.removed}\nTotal: ${syncResult.total}`,
                'Success',
                'success'
            ).then(() => {
                location.reload();
            });
        } else {
            customAlert('Error syncing categories: ' + (syncResult.error || 'Unknown error'), 'Error', 'error').then(() => {});
            if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
        }
    } catch (error) {
        customAlert('Error: ' + error.message, 'Error', 'error').then(() => {});
        if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
    }
}

// Quick Add CEO Message function
async function quickAddCeoMessage() {
    const menuId = <?= $menuId ?>;
    if (!menuId || menuId <= 0) {
        customAlert('Invalid menu ID', 'Error', 'error').then(() => {});
        return;
    }
    
    // Show loading state
    const btn = document.querySelector('button[onclick="quickAddCeoMessage()"]');
    if (!btn) {
        customAlert('Button not found', 'Error', 'error').then(() => {});
        return;
    }
    
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
    
    try {
        const formData = new FormData();
        formData.append('action', 'add_item');
        formData.append('menu_id', menuId);
        formData.append('item_type', 'ceo_message');
        formData.append('title', 'CEO Message');
        formData.append('url', '/ceo-message.php');
        formData.append('target', '_self');
        formData.append('icon', 'fas fa-user-tie');
        
        const response = await fetch('<?= url("admin/menu-edit.php?id={$menuId}") ?>', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const result = await response.json();
        if (result.success) {
            customAlert('CEO Message added to menu successfully!', 'Success', 'success').then(() => {
                location.reload();
            });
        } else {
            customAlert('Error adding CEO Message: ' + (result.error || 'Unknown error'), 'Error', 'error').then(() => {});
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (error) {
        customAlert('Error: ' + error.message, 'Error', 'error').then(() => {});
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function editItem(id) {
    try {
        // Fetch item data
        const response = await fetch('<?= url("admin/menu-edit.php?id={$menuId}") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_item',
                item_id: id
            })
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const result = await response.json();
        if (!result.success || !result.item) {
            customAlert('Error loading item: ' + (result.error || 'Unknown error'), 'Error', 'error').then(() => {});
            return;
        }
        
        const item = result.item;
        
        // Populate edit form
        document.getElementById('edit_item_id').value = item.id;
        
        // Select the item type
        selectEditItemType(item.type);
        
        // Populate mega menu fields
        if (document.getElementById('mega_menu_enabled')) {
            document.getElementById('mega_menu_enabled').checked = item.mega_menu_enabled == 1;
            toggleMegaMenuSettings();
        }
        
        // Update mega menu manager link
        const managerLink = document.getElementById('megaMenuManagerLink');
        if (managerLink) {
            managerLink.href = 'mega-menu-manager.php?menu_item_id=' + item.id;
        }
        if (document.getElementById('mega_menu_layout')) {
            document.getElementById('mega_menu_layout').value = item.mega_menu_layout || 'columns';
        }
        if (document.getElementById('mega_menu_width')) {
            document.getElementById('mega_menu_width').value = item.mega_menu_width || 'auto';
        }
        if (document.getElementById('mega_menu_columns')) {
            document.getElementById('mega_menu_columns').value = item.mega_menu_columns || 3;
        }
        if (document.getElementById('mega_menu_background')) {
            document.getElementById('mega_menu_background').value = item.mega_menu_background || '';
        }
        if (document.getElementById('mega_menu_custom_css')) {
            document.getElementById('mega_menu_custom_css').value = item.mega_menu_custom_css || '';
        }
        
        // Populate fields based on current type
        switch(item.type) {
            case 'custom':
                document.getElementById('edit_custom_title').value = item.title || '';
                document.getElementById('edit_custom_url').value = item.url || '';
                break;
            case 'category':
                document.getElementById('edit_category_object_id').value = item.object_id || '';
                break;
            case 'product':
                document.getElementById('edit_product_object_id').value = item.object_id || '';
                break;
            case 'page':
                document.getElementById('edit_page_object_id').value = item.object_id || '';
                break;
            case 'post':
                document.getElementById('edit_post_object_id').value = item.object_id || '';
                break;
            case 'post_category':
                document.getElementById('edit_post_category_name').value = item.title || '';
                break;
            case 'services':
                if (document.getElementById('edit_services_title')) {
                    document.getElementById('edit_services_title').value = item.title || '';
                }
                if (document.getElementById('edit_services_url')) {
                    document.getElementById('edit_services_url').value = item.url || '';
                }
                break;
            case 'ceo_message':
                if (document.getElementById('edit_ceo_message_title')) {
                    document.getElementById('edit_ceo_message_title').value = item.title || '';
                }
                if (document.getElementById('edit_ceo_message_url')) {
                    document.getElementById('edit_ceo_message_url').value = item.url || '';
                }
                break;
            case 'partners':
                if (document.getElementById('edit_partners_title')) {
                    document.getElementById('edit_partners_title').value = item.title || '';
                }
                if (document.getElementById('edit_partners_url')) {
                    document.getElementById('edit_partners_url').value = item.url || '';
                }
                break;
            case 'clients':
                if (document.getElementById('edit_clients_title')) {
                    document.getElementById('edit_clients_title').value = item.title || '';
                }
                if (document.getElementById('edit_clients_url')) {
                    document.getElementById('edit_clients_url').value = item.url || '';
                }
                break;
            case 'quality_certifications':
                if (document.getElementById('edit_quality_certifications_title')) {
                    document.getElementById('edit_quality_certifications_title').value = item.title || '';
                }
                if (document.getElementById('edit_quality_certifications_url')) {
                    document.getElementById('edit_quality_certifications_url').value = item.url || '';
                }
                break;
        }
        
        // For non-custom types, also populate title and URL fields if they exist (for custom link override)
        if (item.type !== 'custom') {
            // Store original values for reference
            if (document.getElementById('edit_custom_title')) {
                // Keep the title field available for editing
                document.getElementById('edit_custom_title').value = item.title || '';
            }
            if (document.getElementById('edit_custom_url')) {
                // Allow custom URL override
                document.getElementById('edit_custom_url').value = item.url || '';
            }
        }
        
        // Set common fields
        document.getElementById('edit_iconInput').value = item.icon || '';
        document.getElementById('edit_target').value = item.target || '_self';
        updateEditIconPreview();
        
        // Show modal
        document.getElementById('editItemModal').classList.remove('hidden');
    } catch (error) {
        customAlert('Error: ' + error.message, 'Error', 'error').then(() => {});
    }
}

function selectEditItemType(type) {
    const form = document.getElementById('editItemForm');
    if (!form) return;
    
    // Set the item type
    document.getElementById('edit_item_type').value = type;
    
    // Update button states
    document.querySelectorAll('.edit-item-type-btn').forEach(btn => {
        if (btn.getAttribute('data-type') === type) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    
    // Hide all fields
    document.querySelectorAll('#editItemTypeFields .item-type-fields').forEach(field => {
        field.classList.add('hidden');
    });
    
    // Remove required from all inputs
    form.querySelectorAll('input, select').forEach(input => {
        input.removeAttribute('required');
    });
    
    // Show relevant fields based on type
    let fieldsToShow = [];
    let requiredFields = [];
    
    switch(type) {
        case 'custom':
            fieldsToShow = ['editCustomLinkFields'];
            requiredFields = ['edit_custom_title'];
            break;
        case 'category':
            fieldsToShow = ['editCategoryFields'];
            requiredFields = ['edit_category_object_id'];
            break;
        case 'product':
            fieldsToShow = ['editProductFields'];
            requiredFields = ['edit_product_object_id'];
            break;
        case 'page':
            fieldsToShow = ['editPageFields'];
            requiredFields = ['edit_page_object_id'];
            break;
        case 'post':
            fieldsToShow = ['editPostFields'];
            requiredFields = ['edit_post_object_id'];
            break;
        case 'post_category':
            fieldsToShow = ['editPostCategoryFields'];
            requiredFields = ['edit_post_category_name'];
            break;
        case 'services':
            fieldsToShow = ['editServicesFields'];
            requiredFields = [];
            break;
        case 'ceo_message':
            fieldsToShow = ['editCeoMessageFields'];
            requiredFields = [];
            break;
        case 'partners':
            fieldsToShow = ['editPartnersFields'];
            requiredFields = [];
            break;
        case 'clients':
            fieldsToShow = ['editClientsFields'];
            requiredFields = [];
            break;
        case 'quality_certifications':
            fieldsToShow = ['editQualityCertificationsFields'];
            requiredFields = [];
            break;
    }
    
    // Show the relevant fields
    fieldsToShow.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.remove('hidden');
        }
    });
    
    // Set required attributes
    requiredFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field) {
            field.setAttribute('required', 'required');
        }
    });
    
    // Always show custom link fields as an option (for URL override)
    // But make them optional when not in custom mode
    if (type !== 'custom' && type !== 'services' && type !== 'ceo_message' && type !== 'partners' && type !== 'clients' && type !== 'quality_certifications') {
        const customFields = document.getElementById('editCustomLinkFields');
        if (customFields) {
            customFields.classList.remove('hidden');
            // Make title optional when not custom type
            const titleField = document.getElementById('edit_custom_title');
            if (titleField) {
                titleField.removeAttribute('required');
                titleField.placeholder = 'Title (optional - will use default if empty)';
            }
            const urlField = document.getElementById('edit_custom_url');
            if (urlField) {
                urlField.placeholder = 'Custom URL (optional - will use default if empty)';
            }
        }
    }
}

function closeEditItemModal() {
    document.getElementById('editItemModal').classList.add('hidden');
    document.getElementById('editItemForm').reset();
}

// Handle edit form submission
const editItemForm = document.getElementById('editItemForm');
if (editItemForm) {
    editItemForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validate form based on item type
        const itemType = document.getElementById('edit_item_type').value;
        let validationError = '';
        
        if (itemType === 'custom') {
            const title = document.getElementById('edit_custom_title')?.value.trim();
            if (!title) {
                validationError = 'Title is required for custom links';
            }
        } else if (itemType === 'category') {
            const objectId = document.getElementById('edit_category_object_id')?.value;
            if (!objectId) {
                validationError = 'Please select a category';
            }
        } else if (itemType === 'product') {
            const objectId = document.getElementById('edit_product_object_id')?.value;
            if (!objectId) {
                validationError = 'Please select a product';
            }
        } else if (itemType === 'page') {
            const objectId = document.getElementById('edit_page_object_id')?.value;
            if (!objectId) {
                validationError = 'Please select a page';
            }
        } else if (itemType === 'post') {
            const objectId = document.getElementById('edit_post_object_id')?.value;
            if (!objectId) {
                validationError = 'Please select a blog post';
            }
        } else if (itemType === 'post_category') {
            const categoryName = document.getElementById('edit_post_category_name')?.value;
            if (!categoryName) {
                validationError = 'Please select a blog category';
            }
        } else if (itemType === 'services' || itemType === 'ceo_message' || itemType === 'partners' || itemType === 'quality_certifications') {
            // These types don't require validation - they have default values
        }
        
        if (validationError) {
            customAlert(validationError, 'Validation Error', 'error').then(() => {});
            return;
        }
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
        
        try {
            const response = await fetch('<?= url("admin/menu-edit.php?id={$menuId}") ?>', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const result = await response.json();
            if (result.success) {
                closeEditItemModal();
                customAlert('Menu item updated successfully!', 'Success', 'success').then(() => {
                    location.reload();
                });
            } else {
                customAlert('Error updating item: ' + (result.error || 'Unknown error'), 'Error', 'error').then(() => {});
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            customAlert('Error: ' + error.message, 'Error', 'error').then(() => {});
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

// Icon picker functions for edit modal
function openEditIconPicker() {
    const modal = document.getElementById('iconPickerModal');
    const currentIcon = document.getElementById('edit_iconInput').value;
    selectedIconClass = currentIcon;
    
    renderIcons();
    
    if (currentIcon) {
        setTimeout(() => {
            const selectedItem = document.querySelector(`.icon-item[data-icon="${currentIcon}"]`);
            if (selectedItem) {
                selectedItem.classList.add('selected');
                selectedItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 100);
    }
    
    modal.classList.remove('hidden');
    document.getElementById('iconSearch').focus();
    
    // Store that we're editing (not adding)
    window.iconPickerMode = 'edit';
}

function clearEditIcon() {
    document.getElementById('edit_iconInput').value = '';
    selectedIconClass = '';
    updateEditIconPreview();
}

function updateEditIconPreview() {
    const preview = document.getElementById('editSelectedIconPreview');
    const iconValue = document.getElementById('edit_iconInput').value;
    
    if (iconValue) {
        preview.innerHTML = `
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-50 rounded-lg border border-green-200">
                <i class="${iconValue} text-green-600 text-xl"></i>
                <span class="text-sm text-gray-700 font-medium">${iconValue}</span>
            </div>
        `;
    } else {
        preview.innerHTML = '<span class="text-sm text-gray-400 italic">No icon selected</span>';
    }
}

// Icon picker will use window.iconPickerMode to determine which input to update

// Update preview when edit icon input changes
document.addEventListener('DOMContentLoaded', function() {
    const editIconInput = document.getElementById('edit_iconInput');
    if (editIconInput) {
        editIconInput.addEventListener('input', updateEditIconPreview);
    }
});

async function deleteItem(id) {
    const confirmed = await customConfirm('Delete this menu item? This will also delete all sub-items.', 'Delete Menu Item');
    if (!confirmed) {
        return;
    }
    
    fetch('<?= url("admin/menu-edit.php?id={$menuId}") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'delete_item',
            item_id: id
        })
    }).then(res => {
        if (!res.ok) {
            throw new Error('Network response was not ok');
        }
        return res.json();
    }).then(data => {
        if (data.success) {
            customAlert('Menu item deleted successfully!', 'Success', 'success').then(() => {
                location.reload();
            });
        } else {
            customAlert('Error deleting item: ' + (data.error || 'Unknown error'), 'Error', 'error').then(() => {});
        }
    }).catch(error => {
        customAlert('Error: ' + error.message, 'Error', 'error').then(() => {});
    });
}
</script>

<script>
// Comprehensive Font Awesome icons for menu items
const commonIcons = [
    // Navigation & Basic
    { class: 'fas fa-home', name: 'Home' },
    { class: 'fas fa-arrow-right', name: 'Arrow Right' },
    { class: 'fas fa-arrow-left', name: 'Arrow Left' },
    { class: 'fas fa-arrow-up', name: 'Arrow Up' },
    { class: 'fas fa-arrow-down', name: 'Arrow Down' },
    { class: 'fas fa-chevron-right', name: 'Chevron Right' },
    { class: 'fas fa-chevron-left', name: 'Chevron Left' },
    { class: 'fas fa-chevron-up', name: 'Chevron Up' },
    { class: 'fas fa-chevron-down', name: 'Chevron Down' },
    { class: 'fas fa-angle-right', name: 'Angle Right' },
    { class: 'fas fa-angle-left', name: 'Angle Left' },
    { class: 'fas fa-angle-up', name: 'Angle Up' },
    { class: 'fas fa-angle-down', name: 'Angle Down' },
    { class: 'fas fa-caret-right', name: 'Caret Right' },
    { class: 'fas fa-caret-left', name: 'Caret Left' },
    { class: 'fas fa-long-arrow-alt-right', name: 'Long Arrow Right' },
    { class: 'fas fa-long-arrow-alt-left', name: 'Long Arrow Left' },
    { class: 'fas fa-bars', name: 'Menu Bars' },
    { class: 'fas fa-ellipsis-h', name: 'Ellipsis Horizontal' },
    { class: 'fas fa-ellipsis-v', name: 'Ellipsis Vertical' },
    
    // User & People
    { class: 'fas fa-user', name: 'User' },
    { class: 'fas fa-user-circle', name: 'User Circle' },
    { class: 'fas fa-user-friends', name: 'User Friends' },
    { class: 'fas fa-users', name: 'Users' },
    { class: 'fas fa-users-cog', name: 'Users Settings' },
    { class: 'fas fa-user-tie', name: 'User Tie' },
    { class: 'fas fa-user-shield', name: 'User Shield' },
    { class: 'fas fa-user-check', name: 'User Check' },
    { class: 'fas fa-user-plus', name: 'User Plus' },
    { class: 'fas fa-user-minus', name: 'User Minus' },
    { class: 'fas fa-user-times', name: 'User Times' },
    { class: 'fas fa-id-card', name: 'ID Card' },
    { class: 'fas fa-id-badge', name: 'ID Badge' },
    { class: 'fas fa-address-card', name: 'Address Card' },
    { class: 'fas fa-child', name: 'Child' },
    { class: 'fas fa-baby', name: 'Baby' },
    
    // Shopping & E-commerce
    { class: 'fas fa-shopping-cart', name: 'Shopping Cart' },
    { class: 'fas fa-shopping-bag', name: 'Shopping Bag' },
    { class: 'fas fa-shopping-basket', name: 'Shopping Basket' },
    { class: 'fas fa-cash-register', name: 'Cash Register' },
    { class: 'fas fa-credit-card', name: 'Credit Card' },
    { class: 'fas fa-money-bill', name: 'Money Bill' },
    { class: 'fas fa-money-bill-wave', name: 'Money Bill Wave' },
    { class: 'fas fa-money-check', name: 'Money Check' },
    { class: 'fas fa-coins', name: 'Coins' },
    { class: 'fas fa-dollar-sign', name: 'Dollar Sign' },
    { class: 'fas fa-euro-sign', name: 'Euro Sign' },
    { class: 'fas fa-pound-sign', name: 'Pound Sign' },
    { class: 'fas fa-yen-sign', name: 'Yen Sign' },
    { class: 'fas fa-won-sign', name: 'Won Sign' },
    { class: 'fas fa-receipt', name: 'Receipt' },
    { class: 'fas fa-tags', name: 'Tags' },
    { class: 'fas fa-tag', name: 'Tag' },
    { class: 'fas fa-percent', name: 'Percent' },
    { class: 'fas fa-gift', name: 'Gift' },
    { class: 'fas fa-gift-card', name: 'Gift Card' },
    { class: 'fas fa-box', name: 'Box' },
    { class: 'fas fa-boxes', name: 'Boxes' },
    { class: 'fas fa-pallet', name: 'Pallet' },
    { class: 'fas fa-warehouse', name: 'Warehouse' },
    { class: 'fas fa-store', name: 'Store' },
    { class: 'fas fa-store-alt', name: 'Store Alt' },
    { class: 'fas fa-shopping-cart', name: 'Cart' },
    
    // Files & Documents
    { class: 'fas fa-file', name: 'File' },
    { class: 'fas fa-file-alt', name: 'File Alt' },
    { class: 'fas fa-file-pdf', name: 'File PDF' },
    { class: 'fas fa-file-word', name: 'File Word' },
    { class: 'fas fa-file-excel', name: 'File Excel' },
    { class: 'fas fa-file-powerpoint', name: 'File PowerPoint' },
    { class: 'fas fa-file-image', name: 'File Image' },
    { class: 'fas fa-file-video', name: 'File Video' },
    { class: 'fas fa-file-audio', name: 'File Audio' },
    { class: 'fas fa-file-archive', name: 'File Archive' },
    { class: 'fas fa-file-code', name: 'File Code' },
    { class: 'fas fa-file-contract', name: 'File Contract' },
    { class: 'fas fa-file-invoice', name: 'File Invoice' },
    { class: 'fas fa-folder', name: 'Folder' },
    { class: 'fas fa-folder-open', name: 'Folder Open' },
    { class: 'fas fa-folder-plus', name: 'Folder Plus' },
    { class: 'fas fa-archive', name: 'Archive' },
    { class: 'fas fa-book', name: 'Book' },
    { class: 'fas fa-book-open', name: 'Book Open' },
    { class: 'fas fa-bookmark', name: 'Bookmark' },
    { class: 'fas fa-book-reader', name: 'Book Reader' },
    { class: 'fas fa-scroll', name: 'Scroll' },
    { class: 'fas fa-certificate', name: 'Certificate' },
    { class: 'fas fa-diploma', name: 'Diploma' },
    
    // Communication
    { class: 'fas fa-envelope', name: 'Email' },
    { class: 'fas fa-envelope-open', name: 'Email Open' },
    { class: 'fas fa-envelope-square', name: 'Email Square' },
    { class: 'fas fa-mail-bulk', name: 'Mail Bulk' },
    { class: 'fas fa-phone', name: 'Phone' },
    { class: 'fas fa-phone-alt', name: 'Phone Alt' },
    { class: 'fas fa-phone-square', name: 'Phone Square' },
    { class: 'fas fa-mobile-alt', name: 'Mobile' },
    { class: 'fas fa-sms', name: 'SMS' },
    { class: 'fas fa-comments', name: 'Comments' },
    { class: 'fas fa-comment', name: 'Comment' },
    { class: 'fas fa-comment-dots', name: 'Comment Dots' },
    { class: 'fas fa-comment-alt', name: 'Comment Alt' },
    { class: 'fas fa-comments-dollar', name: 'Comments Dollar' },
    { class: 'fas fa-bullhorn', name: 'Bullhorn' },
    { class: 'fas fa-megaphone', name: 'Megaphone' },
    { class: 'fas fa-bell', name: 'Bell' },
    { class: 'fas fa-bell-slash', name: 'Bell Slash' },
    { class: 'fas fa-broadcast-tower', name: 'Broadcast Tower' },
    { class: 'fas fa-walkie-talkie', name: 'Walkie Talkie' },
    
    // Location & Maps
    { class: 'fas fa-map-marker-alt', name: 'Location Marker' },
    { class: 'fas fa-map', name: 'Map' },
    { class: 'fas fa-map-marked-alt', name: 'Map Marked' },
    { class: 'fas fa-globe', name: 'Globe' },
    { class: 'fas fa-globe-americas', name: 'Globe Americas' },
    { class: 'fas fa-globe-asia', name: 'Globe Asia' },
    { class: 'fas fa-globe-europe', name: 'Globe Europe' },
    { class: 'fas fa-compass', name: 'Compass' },
    { class: 'fas fa-street-view', name: 'Street View' },
    { class: 'fas fa-route', name: 'Route' },
    { class: 'fas fa-directions', name: 'Directions' },
    { class: 'fas fa-location-arrow', name: 'Location Arrow' },
    { class: 'fas fa-crosshairs', name: 'Crosshairs' },
    
    // Business & Office
    { class: 'fas fa-briefcase', name: 'Briefcase' },
    { class: 'fas fa-building', name: 'Building' },
    { class: 'fas fa-building', name: 'Office Building' },
    { class: 'fas fa-landmark', name: 'Landmark' },
    { class: 'fas fa-handshake', name: 'Handshake' },
    { class: 'fas fa-hand-holding-usd', name: 'Hand Holding Money' },
    { class: 'fas fa-chart-line', name: 'Chart Line' },
    { class: 'fas fa-chart-bar', name: 'Chart Bar' },
    { class: 'fas fa-chart-pie', name: 'Chart Pie' },
    { class: 'fas fa-chart-area', name: 'Chart Area' },
    { class: 'fas fa-project-diagram', name: 'Project Diagram' },
    { class: 'fas fa-sitemap', name: 'Sitemap' },
    { class: 'fas fa-network-wired', name: 'Network' },
    { class: 'fas fa-bullseye', name: 'Bullseye' },
    { class: 'fas fa-target', name: 'Target' },
    { class: 'fas fa-clipboard-list', name: 'Clipboard List' },
    { class: 'fas fa-clipboard-check', name: 'Clipboard Check' },
    { class: 'fas fa-calendar', name: 'Calendar' },
    { class: 'fas fa-calendar-alt', name: 'Calendar Alt' },
    { class: 'fas fa-calendar-check', name: 'Calendar Check' },
    { class: 'fas fa-calendar-day', name: 'Calendar Day' },
    { class: 'fas fa-calendar-week', name: 'Calendar Week' },
    { class: 'fas fa-clock', name: 'Clock' },
    { class: 'fas fa-stopwatch', name: 'Stopwatch' },
    { class: 'fas fa-hourglass', name: 'Hourglass' },
    { class: 'fas fa-hourglass-half', name: 'Hourglass Half' },
    
    // Technology & Devices
    { class: 'fas fa-laptop', name: 'Laptop' },
    { class: 'fas fa-desktop', name: 'Desktop' },
    { class: 'fas fa-tablet-alt', name: 'Tablet' },
    { class: 'fas fa-mobile-alt', name: 'Mobile Phone' },
    { class: 'fas fa-server', name: 'Server' },
    { class: 'fas fa-database', name: 'Database' },
    { class: 'fas fa-hdd', name: 'Hard Drive' },
    { class: 'fas fa-memory', name: 'Memory' },
    { class: 'fas fa-microchip', name: 'Microchip' },
    { class: 'fas fa-keyboard', name: 'Keyboard' },
    { class: 'fas fa-mouse', name: 'Mouse' },
    { class: 'fas fa-headphones', name: 'Headphones' },
    { class: 'fas fa-print', name: 'Print' },
    { class: 'fas fa-scanner', name: 'Scanner' },
    { class: 'fas fa-camera', name: 'Camera' },
    { class: 'fas fa-camera-retro', name: 'Camera Retro' },
    { class: 'fas fa-video', name: 'Video' },
    { class: 'fas fa-tv', name: 'TV' },
    { class: 'fas fa-radio', name: 'Radio' },
    { class: 'fas fa-satellite', name: 'Satellite' },
    { class: 'fas fa-satellite-dish', name: 'Satellite Dish' },
    { class: 'fas fa-wifi', name: 'WiFi' },
    { class: 'fas fa-bluetooth', name: 'Bluetooth' },
    { class: 'fas fa-usb', name: 'USB' },
    { class: 'fas fa-plug', name: 'Plug' },
    { class: 'fas fa-power-off', name: 'Power' },
    { class: 'fas fa-battery-full', name: 'Battery Full' },
    { class: 'fas fa-battery-half', name: 'Battery Half' },
    { class: 'fas fa-battery-empty', name: 'Battery Empty' },
    
    // Tools & Equipment
    { class: 'fas fa-tools', name: 'Tools' },
    { class: 'fas fa-wrench', name: 'Wrench' },
    { class: 'fas fa-screwdriver', name: 'Screwdriver' },
    { class: 'fas fa-hammer', name: 'Hammer' },
    { class: 'fas fa-hammer', name: 'Hammer' },
    { class: 'fas fa-cog', name: 'Settings Cog' },
    { class: 'fas fa-cogs', name: 'Settings Cogs' },
    { class: 'fas fa-sliders-h', name: 'Sliders' },
    { class: 'fas fa-filter', name: 'Filter' },
    { class: 'fas fa-wrench', name: 'Wrench' },
    { class: 'fas fa-screwdriver', name: 'Screwdriver' },
    { class: 'fas fa-hammer', name: 'Hammer' },
    { class: 'fas fa-toolbox', name: 'Toolbox' },
    { class: 'fas fa-drafting-compass', name: 'Compass' },
    { class: 'fas fa-ruler', name: 'Ruler' },
    { class: 'fas fa-ruler-combined', name: 'Ruler Combined' },
    { class: 'fas fa-cut', name: 'Cut' },
    { class: 'fas fa-paste', name: 'Paste' },
    
    // Transportation & Logistics
    { class: 'fas fa-truck', name: 'Truck' },
    { class: 'fas fa-truck-moving', name: 'Truck Moving' },
    { class: 'fas fa-truck-loading', name: 'Truck Loading' },
    { class: 'fas fa-shipping-fast', name: 'Shipping Fast' },
    { class: 'fas fa-dolly', name: 'Dolly' },
    { class: 'fas fa-dolly-flatbed', name: 'Dolly Flatbed' },
    { class: 'fas fa-forklift', name: 'Forklift' },
    { class: 'fas fa-pallet', name: 'Pallet' },
    { class: 'fas fa-warehouse', name: 'Warehouse' },
    { class: 'fas fa-boxes', name: 'Boxes' },
    { class: 'fas fa-car', name: 'Car' },
    { class: 'fas fa-car-side', name: 'Car Side' },
    { class: 'fas fa-motorcycle', name: 'Motorcycle' },
    { class: 'fas fa-bicycle', name: 'Bicycle' },
    { class: 'fas fa-bus', name: 'Bus' },
    { class: 'fas fa-train', name: 'Train' },
    { class: 'fas fa-subway', name: 'Subway' },
    { class: 'fas fa-plane', name: 'Plane' },
    { class: 'fas fa-helicopter', name: 'Helicopter' },
    { class: 'fas fa-ship', name: 'Ship' },
    { class: 'fas fa-anchor', name: 'Anchor' },
    { class: 'fas fa-gas-pump', name: 'Gas Pump' },
    { class: 'fas fa-road', name: 'Road' },
    { class: 'fas fa-route', name: 'Route' },
    
    // Industrial & Manufacturing
    { class: 'fas fa-industry', name: 'Industry' },
    { class: 'fas fa-industry', name: 'Factory' },
    { class: 'fas fa-hard-hat', name: 'Hard Hat' },
    { class: 'fas fa-helmet-safety', name: 'Helmet Safety' },
    { class: 'fas fa-cog', name: 'Gear' },
    { class: 'fas fa-cogs', name: 'Gears' },
    { class: 'fas fa-microchip', name: 'Microchip' },
    { class: 'fas fa-microchip', name: 'Processor' },
    { class: 'fas fa-robot', name: 'Robot' },
    { class: 'fas fa-conveyor-belt', name: 'Conveyor Belt' },
    { class: 'fas fa-compress-arrows-alt', name: 'Compress' },
    { class: 'fas fa-expand-arrows-alt', name: 'Expand' },
    { class: 'fas fa-compress', name: 'Compress' },
    { class: 'fas fa-expand', name: 'Expand' },
    
    // Media & Entertainment
    { class: 'fas fa-image', name: 'Image' },
    { class: 'fas fa-images', name: 'Images' },
    { class: 'fas fa-photo-video', name: 'Photo Video' },
    { class: 'fas fa-film', name: 'Film' },
    { class: 'fas fa-video', name: 'Video' },
    { class: 'fas fa-video-slash', name: 'Video Slash' },
    { class: 'fas fa-play', name: 'Play' },
    { class: 'fas fa-pause', name: 'Pause' },
    { class: 'fas fa-stop', name: 'Stop' },
    { class: 'fas fa-forward', name: 'Forward' },
    { class: 'fas fa-backward', name: 'Backward' },
    { class: 'fas fa-step-forward', name: 'Step Forward' },
    { class: 'fas fa-step-backward', name: 'Step Backward' },
    { class: 'fas fa-music', name: 'Music' },
    { class: 'fas fa-headphones', name: 'Headphones' },
    { class: 'fas fa-microphone', name: 'Microphone' },
    { class: 'fas fa-microphone-slash', name: 'Microphone Slash' },
    { class: 'fas fa-volume-up', name: 'Volume Up' },
    { class: 'fas fa-volume-down', name: 'Volume Down' },
    { class: 'fas fa-volume-mute', name: 'Volume Mute' },
    { class: 'fas fa-volume-off', name: 'Volume Off' },
    { class: 'fas fa-gamepad', name: 'Gamepad' },
    { class: 'fas fa-dice', name: 'Dice' },
    { class: 'fas fa-chess', name: 'Chess' },
    { class: 'fas fa-puzzle-piece', name: 'Puzzle Piece' },
    
    // Food & Beverage
    { class: 'fas fa-utensils', name: 'Utensils' },
    { class: 'fas fa-utensil-spoon', name: 'Spoon' },
    { class: 'fas fa-fish', name: 'Fish' },
    { class: 'fas fa-drumstick-bite', name: 'Drumstick' },
    { class: 'fas fa-hamburger', name: 'Hamburger' },
    { class: 'fas fa-pizza-slice', name: 'Pizza' },
    { class: 'fas fa-bread-slice', name: 'Bread' },
    { class: 'fas fa-cheese', name: 'Cheese' },
    { class: 'fas fa-wine-glass', name: 'Wine Glass' },
    { class: 'fas fa-wine-bottle', name: 'Wine Bottle' },
    { class: 'fas fa-beer', name: 'Beer' },
    { class: 'fas fa-coffee', name: 'Coffee' },
    { class: 'fas fa-mug-hot', name: 'Mug Hot' },
    { class: 'fas fa-cocktail', name: 'Cocktail' },
    { class: 'fas fa-ice-cream', name: 'Ice Cream' },
    { class: 'fas fa-candy-cane', name: 'Candy Cane' },
    { class: 'fas fa-cookie', name: 'Cookie' },
    { class: 'fas fa-birthday-cake', name: 'Birthday Cake' },
    { class: 'fas fa-seedling', name: 'Seedling' },
    { class: 'fas fa-apple-alt', name: 'Apple' },
    
    // Health & Medical
    { class: 'fas fa-heartbeat', name: 'Heartbeat' },
    { class: 'fas fa-heart', name: 'Heart' },
    { class: 'fas fa-lungs', name: 'Lungs' },
    { class: 'fas fa-stethoscope', name: 'Stethoscope' },
    { class: 'fas fa-user-md', name: 'Doctor' },
    { class: 'fas fa-user-nurse', name: 'Nurse' },
    { class: 'fas fa-ambulance', name: 'Ambulance' },
    { class: 'fas fa-hospital', name: 'Hospital' },
    { class: 'fas fa-clinic-medical', name: 'Clinic' },
    { class: 'fas fa-pills', name: 'Pills' },
    { class: 'fas fa-prescription-bottle', name: 'Prescription Bottle' },
    { class: 'fas fa-syringe', name: 'Syringe' },
    { class: 'fas fa-band-aid', name: 'Band Aid' },
    { class: 'fas fa-thermometer-half', name: 'Thermometer' },
    { class: 'fas fa-weight', name: 'Weight' },
    { class: 'fas fa-dumbbell', name: 'Dumbbell' },
    { class: 'fas fa-running', name: 'Running' },
    { class: 'fas fa-biking', name: 'Biking' },
    { class: 'fas fa-swimmer', name: 'Swimmer' },
    { class: 'fas fa-walking', name: 'Walking' },
    
    // Education & Learning
    { class: 'fas fa-graduation-cap', name: 'Graduation Cap' },
    { class: 'fas fa-university', name: 'University' },
    { class: 'fas fa-school', name: 'School' },
    { class: 'fas fa-chalkboard', name: 'Chalkboard' },
    { class: 'fas fa-chalkboard-teacher', name: 'Chalkboard Teacher' },
    { class: 'fas fa-user-graduate', name: 'User Graduate' },
    { class: 'fas fa-book-reader', name: 'Book Reader' },
    { class: 'fas fa-book-open', name: 'Book Open' },
    { class: 'fas fa-book-medical', name: 'Book Medical' },
    { class: 'fas fa-pencil-alt', name: 'Pencil' },
    { class: 'fas fa-pen', name: 'Pen' },
    { class: 'fas fa-pen-fancy', name: 'Pen Fancy' },
    { class: 'fas fa-highlighter', name: 'Highlighter' },
    { class: 'fas fa-marker', name: 'Marker' },
    { class: 'fas fa-eraser', name: 'Eraser' },
    { class: 'fas fa-calculator', name: 'Calculator' },
    { class: 'fas fa-ruler', name: 'Ruler' },
    { class: 'fas fa-compass', name: 'Compass' },
    { class: 'fas fa-microscope', name: 'Microscope' },
    { class: 'fas fa-flask', name: 'Flask' },
    { class: 'fas fa-atom', name: 'Atom' },
    { class: 'fas fa-dna', name: 'DNA' },
    
    // Sports & Recreation
    { class: 'fas fa-football-ball', name: 'Football' },
    { class: 'fas fa-basketball-ball', name: 'Basketball' },
    { class: 'fas fa-baseball-ball', name: 'Baseball' },
    { class: 'fas fa-volleyball-ball', name: 'Volleyball' },
    { class: 'fas fa-table-tennis', name: 'Table Tennis' },
    { class: 'fas fa-golf-ball', name: 'Golf Ball' },
    { class: 'fas fa-hockey-puck', name: 'Hockey Puck' },
    { class: 'fas fa-bowling-ball', name: 'Bowling Ball' },
    { class: 'fas fa-swimming-pool', name: 'Swimming Pool' },
    { class: 'fas fa-skiing', name: 'Skiing' },
    { class: 'fas fa-skiing-nordic', name: 'Skiing Nordic' },
    { class: 'fas fa-snowboarding', name: 'Snowboarding' },
    { class: 'fas fa-skating', name: 'Skating' },
    { class: 'fas fa-hiking', name: 'Hiking' },
    { class: 'fas fa-mountain', name: 'Mountain' },
    { class: 'fas fa-campground', name: 'Campground' },
    { class: 'fas fa-tent', name: 'Tent' },
    { class: 'fas fa-fish', name: 'Fishing' },
    { class: 'fas fa-horse', name: 'Horse' },
    { class: 'fas fa-horse-head', name: 'Horse Head' },
    
    // Weather & Nature
    { class: 'fas fa-sun', name: 'Sun' },
    { class: 'fas fa-moon', name: 'Moon' },
    { class: 'fas fa-cloud', name: 'Cloud' },
    { class: 'fas fa-cloud-sun', name: 'Cloud Sun' },
    { class: 'fas fa-cloud-moon', name: 'Cloud Moon' },
    { class: 'fas fa-cloud-rain', name: 'Cloud Rain' },
    { class: 'fas fa-cloud-showers-heavy', name: 'Cloud Showers' },
    { class: 'fas fa-snowflake', name: 'Snowflake' },
    { class: 'fas fa-wind', name: 'Wind' },
    { class: 'fas fa-tornado', name: 'Tornado' },
    { class: 'fas fa-umbrella', name: 'Umbrella' },
    { class: 'fas fa-thermometer-half', name: 'Thermometer' },
    { class: 'fas fa-tree', name: 'Tree' },
    { class: 'fas fa-leaf', name: 'Leaf' },
    { class: 'fas fa-seedling', name: 'Seedling' },
    { class: 'fas fa-flower', name: 'Flower' },
    { class: 'fas fa-mountain', name: 'Mountain' },
    { class: 'fas fa-water', name: 'Water' },
    { class: 'fas fa-fire', name: 'Fire' },
    { class: 'fas fa-volcano', name: 'Volcano' },
    
    // Security & Safety
    { class: 'fas fa-shield-alt', name: 'Shield' },
    { class: 'fas fa-shield-virus', name: 'Shield Virus' },
    { class: 'fas fa-lock', name: 'Lock' },
    { class: 'fas fa-unlock', name: 'Unlock' },
    { class: 'fas fa-lock-open', name: 'Lock Open' },
    { class: 'fas fa-key', name: 'Key' },
    { class: 'fas fa-fingerprint', name: 'Fingerprint' },
    { class: 'fas fa-user-shield', name: 'User Shield' },
    { class: 'fas fa-user-lock', name: 'User Lock' },
    { class: 'fas fa-user-secret', name: 'User Secret' },
    { class: 'fas fa-eye', name: 'Eye' },
    { class: 'fas fa-eye-slash', name: 'Eye Slash' },
    { class: 'fas fa-video-slash', name: 'Video Slash' },
    { class: 'fas fa-camera', name: 'Camera' },
    { class: 'fas fa-camera-retro', name: 'Camera Retro' },
    { class: 'fas fa-video', name: 'Video Camera' },
    { class: 'fas fa-bug', name: 'Bug' },
    { class: 'fas fa-virus', name: 'Virus' },
    { class: 'fas fa-virus-slash', name: 'Virus Slash' },
    { class: 'fas fa-head-side-mask', name: 'Face Mask' },
    { class: 'fas fa-hand-sparkles', name: 'Hand Sanitizer' },
    { class: 'fas fa-pump-medical', name: 'Pump Medical' },
    { class: 'fas fa-pump-soap', name: 'Pump Soap' },
    
    // Actions & Controls
    { class: 'fas fa-check', name: 'Check' },
    { class: 'fas fa-check-circle', name: 'Check Circle' },
    { class: 'fas fa-check-square', name: 'Check Square' },
    { class: 'fas fa-times', name: 'Times' },
    { class: 'fas fa-times-circle', name: 'Times Circle' },
    { class: 'fas fa-ban', name: 'Ban' },
    { class: 'fas fa-exclamation', name: 'Exclamation' },
    { class: 'fas fa-exclamation-triangle', name: 'Exclamation Triangle' },
    { class: 'fas fa-exclamation-circle', name: 'Exclamation Circle' },
    { class: 'fas fa-question', name: 'Question' },
    { class: 'fas fa-question-circle', name: 'Question Circle' },
    { class: 'fas fa-info', name: 'Info' },
    { class: 'fas fa-info-circle', name: 'Info Circle' },
    { class: 'fas fa-plus', name: 'Plus' },
    { class: 'fas fa-plus-circle', name: 'Plus Circle' },
    { class: 'fas fa-plus-square', name: 'Plus Square' },
    { class: 'fas fa-minus', name: 'Minus' },
    { class: 'fas fa-minus-circle', name: 'Minus Circle' },
    { class: 'fas fa-minus-square', name: 'Minus Square' },
    { class: 'fas fa-edit', name: 'Edit' },
    { class: 'fas fa-pencil-alt', name: 'Pencil Alt' },
    { class: 'fas fa-trash', name: 'Trash' },
    { class: 'fas fa-trash-alt', name: 'Trash Alt' },
    { class: 'fas fa-save', name: 'Save' },
    { class: 'fas fa-download', name: 'Download' },
    { class: 'fas fa-upload', name: 'Upload' },
    { class: 'fas fa-share', name: 'Share' },
    { class: 'fas fa-share-alt', name: 'Share Alt' },
    { class: 'fas fa-share-square', name: 'Share Square' },
    { class: 'fas fa-link', name: 'Link' },
    { class: 'fas fa-unlink', name: 'Unlink' },
    { class: 'fas fa-external-link-alt', name: 'External Link' },
    { class: 'fas fa-copy', name: 'Copy' },
    { class: 'fas fa-cut', name: 'Cut' },
    { class: 'fas fa-paste', name: 'Paste' },
    { class: 'fas fa-redo', name: 'Redo' },
    { class: 'fas fa-undo', name: 'Undo' },
    { class: 'fas fa-sync', name: 'Sync' },
    { class: 'fas fa-sync-alt', name: 'Sync Alt' },
    { class: 'fas fa-refresh', name: 'Refresh' },
    { class: 'fas fa-search', name: 'Search' },
    { class: 'fas fa-search-plus', name: 'Search Plus' },
    { class: 'fas fa-search-minus', name: 'Search Minus' },
    { class: 'fas fa-filter', name: 'Filter' },
    { class: 'fas fa-sort', name: 'Sort' },
    { class: 'fas fa-sort-up', name: 'Sort Up' },
    { class: 'fas fa-sort-down', name: 'Sort Down' },
    { class: 'fas fa-sort-alpha-down', name: 'Sort Alpha Down' },
    { class: 'fas fa-sort-alpha-up', name: 'Sort Alpha Up' },
    { class: 'fas fa-sort-numeric-down', name: 'Sort Numeric Down' },
    { class: 'fas fa-sort-numeric-up', name: 'Sort Numeric Up' },
    { class: 'fas fa-thumbs-up', name: 'Thumbs Up' },
    { class: 'fas fa-thumbs-down', name: 'Thumbs Down' },
    { class: 'fas fa-heart', name: 'Heart' },
    { class: 'fas fa-star', name: 'Star' },
    { class: 'fas fa-star-half', name: 'Star Half' },
    { class: 'fas fa-star-half-alt', name: 'Star Half Alt' },
    { class: 'fas fa-bookmark', name: 'Bookmark' },
    { class: 'fas fa-flag', name: 'Flag' },
    { class: 'fas fa-flag-checkered', name: 'Flag Checkered' },
    { class: 'fas fa-print', name: 'Print' },
    { class: 'fas fa-print', name: 'Printer' },
    { class: 'fas fa-file-download', name: 'File Download' },
    { class: 'fas fa-file-upload', name: 'File Upload' },
    { class: 'fas fa-file-export', name: 'File Export' },
    { class: 'fas fa-file-import', name: 'File Import' },
    { class: 'fas fa-file-invoice', name: 'File Invoice' },
    { class: 'fas fa-file-invoice-dollar', name: 'File Invoice Dollar' },
    { class: 'fas fa-file-contract', name: 'File Contract' },
    { class: 'fas fa-file-signature', name: 'File Signature' },
    { class: 'fas fa-file-check', name: 'File Check' },
    { class: 'fas fa-file-times', name: 'File Times' },
    { class: 'fas fa-file-plus', name: 'File Plus' },
    { class: 'fas fa-file-minus', name: 'File Minus' },
    { class: 'fas fa-file-edit', name: 'File Edit' },
    { class: 'fas fa-file-alt', name: 'File Alt' },
    { class: 'fas fa-file', name: 'File' },
    { class: 'fas fa-folder', name: 'Folder' },
    { class: 'fas fa-folder-open', name: 'Folder Open' },
    { class: 'fas fa-folder-plus', name: 'Folder Plus' },
    { class: 'fas fa-folder-minus', name: 'Folder Minus' },
    { class: 'fas fa-folder-times', name: 'Folder Times' },
    { class: 'fas fa-folder-check', name: 'Folder Check' },
    
    // Social Media & Brands
    { class: 'fab fa-facebook', name: 'Facebook' },
    { class: 'fab fa-facebook-f', name: 'Facebook F' },
    { class: 'fab fa-facebook-square', name: 'Facebook Square' },
    { class: 'fab fa-twitter', name: 'Twitter' },
    { class: 'fab fa-twitter-square', name: 'Twitter Square' },
    { class: 'fab fa-instagram', name: 'Instagram' },
    { class: 'fab fa-youtube', name: 'YouTube' },
    { class: 'fab fa-youtube-square', name: 'YouTube Square' },
    { class: 'fab fa-linkedin', name: 'LinkedIn' },
    { class: 'fab fa-linkedin-in', name: 'LinkedIn In' },
    { class: 'fab fa-whatsapp', name: 'WhatsApp' },
    { class: 'fab fa-whatsapp-square', name: 'WhatsApp Square' },
    { class: 'fab fa-telegram', name: 'Telegram' },
    { class: 'fab fa-telegram-plane', name: 'Telegram Plane' },
    { class: 'fab fa-viber', name: 'Viber' },
    { class: 'fab fa-line', name: 'Line' },
    { class: 'fab fa-wechat', name: 'WeChat' },
    { class: 'fab fa-weibo', name: 'Weibo' },
    { class: 'fab fa-qq', name: 'QQ' },
    { class: 'fab fa-tiktok', name: 'TikTok' },
    { class: 'fab fa-snapchat', name: 'Snapchat' },
    { class: 'fab fa-snapchat-ghost', name: 'Snapchat Ghost' },
    { class: 'fab fa-snapchat-square', name: 'Snapchat Square' },
    { class: 'fab fa-pinterest', name: 'Pinterest' },
    { class: 'fab fa-pinterest-p', name: 'Pinterest P' },
    { class: 'fab fa-pinterest-square', name: 'Pinterest Square' },
    { class: 'fab fa-reddit', name: 'Reddit' },
    { class: 'fab fa-reddit-alien', name: 'Reddit Alien' },
    { class: 'fab fa-reddit-square', name: 'Reddit Square' },
    { class: 'fab fa-discord', name: 'Discord' },
    { class: 'fab fa-skype', name: 'Skype' },
    { class: 'fab fa-viber', name: 'Viber' },
    { class: 'fab fa-google', name: 'Google' },
    { class: 'fab fa-google-plus', name: 'Google Plus' },
    { class: 'fab fa-google-plus-g', name: 'Google Plus G' },
    { class: 'fab fa-google-plus-square', name: 'Google Plus Square' },
    { class: 'fab fa-google-drive', name: 'Google Drive' },
    { class: 'fab fa-google-play', name: 'Google Play' },
    { class: 'fab fa-chrome', name: 'Chrome' },
    { class: 'fab fa-firefox', name: 'Firefox' },
    { class: 'fab fa-safari', name: 'Safari' },
    { class: 'fab fa-edge', name: 'Edge' },
    { class: 'fab fa-opera', name: 'Opera' },
    { class: 'fab fa-internet-explorer', name: 'Internet Explorer' },
    { class: 'fab fa-apple', name: 'Apple' },
    { class: 'fab fa-windows', name: 'Windows' },
    { class: 'fab fa-linux', name: 'Linux' },
    { class: 'fab fa-android', name: 'Android' },
    { class: 'fab fa-amazon', name: 'Amazon' },
    { class: 'fab fa-ebay', name: 'eBay' },
    { class: 'fab fa-paypal', name: 'PayPal' },
    { class: 'fab fa-stripe', name: 'Stripe' },
    { class: 'fab fa-cc-visa', name: 'Visa' },
    { class: 'fas fa-cc-mastercard', name: 'Mastercard' },
    { class: 'fab fa-cc-amex', name: 'American Express' },
    { class: 'fab fa-cc-paypal', name: 'PayPal Card' },
    { class: 'fab fa-cc-stripe', name: 'Stripe Card' },
    
    // News & Media
    { class: 'fas fa-newspaper', name: 'Newspaper' },
    { class: 'fas fa-blog', name: 'Blog' },
    { class: 'fas fa-rss', name: 'RSS' },
    { class: 'fas fa-rss-square', name: 'RSS Square' },
    { class: 'fas fa-podcast', name: 'Podcast' },
    { class: 'fas fa-microphone', name: 'Microphone' },
    { class: 'fas fa-microphone-alt', name: 'Microphone Alt' },
    { class: 'fas fa-microphone-slash', name: 'Microphone Slash' },
    { class: 'fas fa-broadcast-tower', name: 'Broadcast Tower' },
    { class: 'fas fa-tv', name: 'TV' },
    { class: 'fas fa-radio', name: 'Radio' },
    { class: 'fas fa-satellite', name: 'Satellite' },
    { class: 'fas fa-satellite-dish', name: 'Satellite Dish' },
    
    // Awards & Recognition
    { class: 'fas fa-trophy', name: 'Trophy' },
    { class: 'fas fa-medal', name: 'Medal' },
    { class: 'fas fa-award', name: 'Award' },
    { class: 'fas fa-ribbon', name: 'Ribbon' },
    { class: 'fas fa-certificate', name: 'Certificate' },
    { class: 'fas fa-badge', name: 'Badge' },
    { class: 'fas fa-crown', name: 'Crown' },
    { class: 'fas fa-gem', name: 'Gem' },
    { class: 'fas fa-star', name: 'Star' },
    { class: 'fas fa-star-of-life', name: 'Star of Life' },
    { class: 'fas fa-star-and-crescent', name: 'Star and Crescent' },
    { class: 'fas fa-star-of-david', name: 'Star of David' },
    
    // Miscellaneous
    { class: 'fas fa-lightbulb', name: 'Lightbulb' },
    { class: 'fas fa-magic', name: 'Magic' },
    { class: 'fas fa-gem', name: 'Gem' },
    { class: 'fas fa-ring', name: 'Ring' },
    { class: 'fas fa-infinity', name: 'Infinity' },
    { class: 'fas fa-puzzle-piece', name: 'Puzzle Piece' },
    { class: 'fas fa-cube', name: 'Cube' },
    { class: 'fas fa-cubes', name: 'Cubes' },
    { class: 'fas fa-dice', name: 'Dice' },
    { class: 'fas fa-dice-one', name: 'Dice One' },
    { class: 'fas fa-dice-two', name: 'Dice Two' },
    { class: 'fas fa-dice-three', name: 'Dice Three' },
    { class: 'fas fa-dice-four', name: 'Dice Four' },
    { class: 'fas fa-dice-five', name: 'Dice Five' },
    { class: 'fas fa-dice-six', name: 'Dice Six' },
    { class: 'fas fa-chess', name: 'Chess' },
    { class: 'fas fa-chess-king', name: 'Chess King' },
    { class: 'fas fa-chess-queen', name: 'Chess Queen' },
    { class: 'fas fa-chess-rook', name: 'Chess Rook' },
    { class: 'fas fa-chess-bishop', name: 'Chess Bishop' },
    { class: 'fas fa-chess-knight', name: 'Chess Knight' },
    { class: 'fas fa-chess-pawn', name: 'Chess Pawn' },
    { class: 'fas fa-chess-board', name: 'Chess Board' },
    { class: 'fas fa-gamepad', name: 'Gamepad' },
    { class: 'fas fa-ghost', name: 'Ghost' },
    { class: 'fas fa-mask', name: 'Mask' },
    { class: 'fas fa-theater-masks', name: 'Theater Masks' },
    { class: 'fas fa-hat-wizard', name: 'Wizard Hat' },
    { class: 'fas fa-hat-cowboy', name: 'Cowboy Hat' },
    { class: 'fas fa-hat-cowboy-side', name: 'Cowboy Hat Side' },
    { class: 'fas fa-user-astronaut', name: 'Astronaut' },
    { class: 'fas fa-rocket', name: 'Rocket' },
    { class: 'fas fa-satellite', name: 'Satellite' },
    { class: 'fas fa-satellite-dish', name: 'Satellite Dish' },
    { class: 'fas fa-space-shuttle', name: 'Space Shuttle' },
    { class: 'fas fa-meteor', name: 'Meteor' },
    { class: 'fas fa-moon', name: 'Moon' },
    { class: 'fas fa-sun', name: 'Sun' },
    { class: 'fas fa-star', name: 'Star' },
    { class: 'fas fa-star-and-crescent', name: 'Star and Crescent' },
    { class: 'fas fa-star-of-david', name: 'Star of David' },
    { class: 'fas fa-star-of-life', name: 'Star of Life' },
    { class: 'fas fa-allergies', name: 'Allergies' },
    { class: 'fas fa-band-aid', name: 'Band Aid' },
    { class: 'fas fa-bone', name: 'Bone' },
    { class: 'fas fa-bong', name: 'Bong' },
    { class: 'fas fa-book-medical', name: 'Book Medical' },
    { class: 'fas fa-brain', name: 'Brain' },
    { class: 'fas fa-briefcase-medical', name: 'Briefcase Medical' },
    { class: 'fas fa-burn', name: 'Burn' },
    { class: 'fas fa-cannabis', name: 'Cannabis' },
    { class: 'fas fa-capsules', name: 'Capsules' },
    { class: 'fas fa-clipboard-check', name: 'Clipboard Check' },
    { class: 'fas fa-clipboard-list', name: 'Clipboard List' },
    { class: 'fas fa-diagnoses', name: 'Diagnoses' },
    { class: 'fas fa-dna', name: 'DNA' },
    { class: 'fas fa-file-medical', name: 'File Medical' },
    { class: 'fas fa-file-medical-alt', name: 'File Medical Alt' },
    { class: 'fas fa-file-prescription', name: 'File Prescription' },
    { class: 'fas fa-first-aid', name: 'First Aid' },
    { class: 'fas fa-heartbeat', name: 'Heartbeat' },
    { class: 'fas fa-hospital-alt', name: 'Hospital Alt' },
    { class: 'fas fa-hospital-symbol', name: 'Hospital Symbol' },
    { class: 'fas fa-id-card-alt', name: 'ID Card Alt' },
    { class: 'fas fa-laptop-medical', name: 'Laptop Medical' },
    { class: 'fas fa-mortar-pestle', name: 'Mortar Pestle' },
    { class: 'fas fa-notes-medical', name: 'Notes Medical' },
    { class: 'fas fa-pills', name: 'Pills' },
    { class: 'fas fa-plus-square', name: 'Plus Square' },
    { class: 'fas fa-prescription', name: 'Prescription' },
    { class: 'fas fa-prescription-bottle', name: 'Prescription Bottle' },
    { class: 'fas fa-prescription-bottle-alt', name: 'Prescription Bottle Alt' },
    { class: 'fas fa-procedures', name: 'Procedures' },
    { class: 'fas fa-shield-virus', name: 'Shield Virus' },
    { class: 'fas fa-smoking', name: 'Smoking' },
    { class: 'fas fa-smoking-ban', name: 'Smoking Ban' },
    { class: 'fas fa-star-of-life', name: 'Star of Life' },
    { class: 'fas fa-stethoscope', name: 'Stethoscope' },
    { class: 'fas fa-syringe', name: 'Syringe' },
    { class: 'fas fa-tablets', name: 'Tablets' },
    { class: 'fas fa-thermometer', name: 'Thermometer' },
    { class: 'fas fa-thermometer-empty', name: 'Thermometer Empty' },
    { class: 'fas fa-thermometer-full', name: 'Thermometer Full' },
    { class: 'fas fa-thermometer-half', name: 'Thermometer Half' },
    { class: 'fas fa-thermometer-quarter', name: 'Thermometer Quarter' },
    { class: 'fas fa-thermometer-three-quarters', name: 'Thermometer Three Quarters' },
    { class: 'fas fa-user-md', name: 'User MD' },
    { class: 'fas fa-user-nurse', name: 'User Nurse' },
    { class: 'fas fa-vial', name: 'Vial' },
    { class: 'fas fa-vials', name: 'Vials' },
    { class: 'fas fa-weight', name: 'Weight' },
    { class: 'fas fa-x-ray', name: 'X-Ray' }
];

let selectedIconClass = '';
let filteredIcons = [...commonIcons];

function openIconPicker() {
    const modal = document.getElementById('iconPickerModal');
    const currentIcon = document.getElementById('iconInput').value;
    selectedIconClass = currentIcon;
    window.iconPickerMode = 'add';
    
    // Render icons
    renderIcons();
    
    // Highlight current selection
    if (currentIcon) {
        setTimeout(() => {
            const selectedItem = document.querySelector(`.icon-item[data-icon="${currentIcon}"]`);
            if (selectedItem) {
                selectedItem.classList.add('selected');
                selectedItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 100);
    }
    
    modal.classList.remove('hidden');
    document.getElementById('iconSearch').focus();
}

function closeIconPicker() {
    document.getElementById('iconPickerModal').classList.add('hidden');
    document.getElementById('iconSearch').value = '';
    filteredIcons = [...commonIcons];
    renderIcons();
}

function toggleMegaMenuSettings() {
    const enabled = document.getElementById('mega_menu_enabled');
    const settings = document.getElementById('megaMenuSettings');
    if (enabled && settings) {
        if (enabled.checked) {
            settings.classList.remove('hidden');
        } else {
            settings.classList.add('hidden');
        }
    }
}

function renderIcons() {
    const grid = document.getElementById('iconGrid');
    grid.innerHTML = '';
    
    filteredIcons.forEach(icon => {
        const item = document.createElement('div');
        item.className = 'icon-item' + (selectedIconClass === icon.class ? ' selected' : '');
        item.setAttribute('data-icon', icon.class);
        item.onclick = () => selectIcon(icon.class);
        
        item.innerHTML = `
            <i class="${icon.class}"></i>
            <span>${icon.name}</span>
        `;
        
        grid.appendChild(item);
    });
}

function selectIcon(iconClass) {
    // Remove previous selection
    document.querySelectorAll('.icon-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    // Add selection to clicked item
    const item = document.querySelector(`.icon-item[data-icon="${iconClass}"]`);
    if (item) {
        item.classList.add('selected');
        selectedIconClass = iconClass;
    }
}

function confirmIconSelection() {
    if (window.iconPickerMode === 'edit') {
        document.getElementById('edit_iconInput').value = selectedIconClass;
        updateEditIconPreview();
    } else {
        document.getElementById('iconInput').value = selectedIconClass;
        updateIconPreview();
    }
    closeIconPicker();
    window.iconPickerMode = null;
}

function clearIcon() {
    document.getElementById('iconInput').value = '';
    selectedIconClass = '';
    updateIconPreview();
}

function filterIcons() {
    const search = document.getElementById('iconSearch').value.toLowerCase();
    filteredIcons = commonIcons.filter(icon => 
        icon.name.toLowerCase().includes(search) || 
        icon.class.toLowerCase().includes(search)
    );
    renderIcons();
}

function updateIconPreview() {
    const preview = document.getElementById('selectedIconPreview');
    const iconValue = document.getElementById('iconInput').value;
    
    if (iconValue) {
        preview.innerHTML = `
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 rounded-lg border border-blue-200">
                <i class="${iconValue} text-blue-600 text-xl"></i>
                <span class="text-sm text-gray-700 font-medium">${iconValue}</span>
            </div>
        `;
    } else {
        preview.innerHTML = '<span class="text-sm text-gray-400 italic">No icon selected</span>';
    }
}

// Update preview when icon input changes
document.addEventListener('DOMContentLoaded', function() {
    const iconInput = document.getElementById('iconInput');
    if (iconInput) {
        iconInput.addEventListener('input', updateIconPreview);
        updateIconPreview();
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
