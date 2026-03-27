<?php
/**
 * Menu Renderer
 * Renders WordPress-style menu HTML
 */

if (!isset($menu) || !isset($items)) {
    return '';
}

// Load Category model if needed
if (!class_exists('App\Models\Category')) {
    require_once __DIR__ . '/../app/Models/Category.php';
}

// Helper function to process menu item URLs
function processMenuUrl($url) {
    if (empty($url) || $url === '#') {
        return '#';
    }
    
    // If it's already a full URL (http:// or https://), return as is
    if (preg_match('/^https?:\/\//', $url)) {
        return $url;
    }
    
    // If it starts with /, it's an absolute path from root
    // Keep the leading slash and build the full URL properly
    if (strpos($url, '/') === 0) {
        // Get base URL without trailing slash
        $baseUrl = rtrim(url(), '/');
        // Return base URL + absolute path
        return $baseUrl . $url;
    }
    
    // Otherwise, treat as relative path and process through url() function
    return url($url);
}

// Process all menu item URLs before building tree
foreach ($items as &$item) {
    if (isset($item['url'])) {
        $item['url'] = processMenuUrl($item['url']);
    }
}
unset($item); // Unset reference to avoid issues

// Build hierarchical structure
function buildMenuTree($items, $parentId = null) {
    $tree = [];
    foreach ($items as $item) {
        if (($item['parent_id'] ?? null) == $parentId) {
            $item['children'] = buildMenuTree($items, $item['id']);
            
            // Auto-inject categories for Products menu item
            if (empty($item['parent_id']) && 
                (strtolower(trim($item['title'] ?? '')) === 'products' || 
                strtolower(trim($item['title'] ?? '')) === 'product' ||
                strpos(strtolower($item['url'] ?? ''), 'products.php') !== false)) {
                
                // Check if we should auto-inject categories
                $autoInjectCategories = true;
                try {
                    $setting = db()->fetchOne(
                        "SELECT value FROM settings WHERE `key` = 'products_menu_auto_categories'"
                    );
                    $autoInjectCategories = empty($setting) || $setting['value'] == '1';
                } catch (\Exception $e) {
                    $autoInjectCategories = true;
                }
                
                if ($autoInjectCategories) {
                    // Get categories
                    try {
                        $categoryModel = new \App\Models\Category();
                        $categories = $categoryModel->getAll(true);
                        
                        // Check if we should use selected categories
                        $useSelectedCategories = false;
                        $selectedCategoryIds = [];
                        try {
                            $tableExists = false;
                            try {
                                db()->fetchOne("SELECT 1 FROM menu_category_selections LIMIT 1");
                                $tableExists = true;
                            } catch (\Exception $e) {
                                $tableExists = false;
                            }
                            
                            if ($tableExists) {
                                $setting = db()->fetchOne(
                                    "SELECT value FROM settings WHERE `key` = 'products_menu_use_selected_categories'"
                                );
                                $useSelectedCategories = !empty($setting) && $setting['value'] == '1';
                                
                                if ($useSelectedCategories) {
                                    $selected = db()->fetchAll(
                                        "SELECT category_id, display_order FROM menu_category_selections 
                                         WHERE menu_item_id IS NULL AND is_active = 1 
                                         ORDER BY display_order ASC"
                                    );
                                    $selectedCategoryIds = array_column($selected, 'category_id');
                                    $categoryOrders = [];
                                    foreach ($selected as $sel) {
                                        $categoryOrders[$sel['category_id']] = $sel['display_order'];
                                    }
                                    
                                    // Filter and reorder
                                    if (!empty($selectedCategoryIds)) {
                                        $categories = array_filter($categories, function($cat) use ($selectedCategoryIds) {
                                            return in_array($cat['id'], $selectedCategoryIds);
                                        });
                                        usort($categories, function($a, $b) use ($selectedCategoryIds, $categoryOrders) {
                                            $posA = array_search($a['id'], $selectedCategoryIds);
                                            $posB = array_search($b['id'], $selectedCategoryIds);
                                            if ($posA === false) return 1;
                                            if ($posB === false) return -1;
                                            $orderA = $categoryOrders[$a['id']] ?? $posA;
                                            $orderB = $categoryOrders[$b['id']] ?? $posB;
                                            return $orderA <=> $orderB;
                                        });
                                    } else {
                                        $categories = [];
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            // Fallback to all categories
                        }
                        
                        // Convert categories to menu item format
                        $categoryChildren = [];
                        foreach ($categories as $index => $category) {
                            $categoryChildren[] = [
                                'id' => 'cat_' . $category['id'],
                                'title' => $category['name'],
                                'url' => url('products.php?category=' . $category['slug']),
                                'icon' => 'fa-folder',
                                'type' => 'category',
                                'object_id' => $category['id'],
                                'target' => '_self',
                                'children' => [],
                                'is_auto_injected' => true
                            ];
                        }
                        
                        // Merge with existing children (existing children first, then auto-injected)
                        $item['children'] = array_merge($item['children'], $categoryChildren);
                    } catch (\Exception $e) {
                        // If category model fails, just use existing children
                    }
                }
            }
            
            $tree[] = $item;
        }
    }
    return $tree;
}

$menuTree = buildMenuTree($items);
$location = $options['location'] ?? 'header';
$menuClass = $options['menu_class'] ?? 'menu';
$containerClass = $options['container_class'] ?? '';

// Determine if this is a dropdown menu (has children)
$hasDropdown = false;
foreach ($items as $item) {
    if (!empty($item['parent_id'])) {
        $hasDropdown = true;
        break;
    }
}

if ($location === 'header'): ?>
    <!-- Header Menu -->
    <div class="hidden xl:flex items-center space-x-2 <?= $containerClass ?>">
        <?php foreach ($menuTree as $item): 
            $hasChildren = !empty($item['children']);
            $itemUrl = $item['url'] ?? '#';
            $itemTitle = escape($item['title']);
            $itemIcon = $item['icon'] ?? '';
            $itemClasses = $item['css_classes'] ?? '';
            $itemTarget = $item['target'] ?? '_self';
            
            // Check if current page
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
            $itemPath = parse_url($itemUrl, PHP_URL_PATH);
            if ($itemPath === '/' || $itemPath === '') {
                $isActive = ($currentUrl === '/' || $currentUrl === '/index.php' || basename($currentUrl) === 'index.php');
            } else {
                // Remove leading slash for comparison
                $itemPath = ltrim($itemPath, '/');
                $currentPath = ltrim(parse_url($currentUrl, PHP_URL_PATH), '/');
                $isActive = ($currentPath === $itemPath || strpos($currentPath, $itemPath) === 0);
            }
        ?>
            <?php if ($hasChildren): ?>
                <?php
                // Check if mega menu is enabled
                $megaMenuEnabled = !empty($item['mega_menu_enabled']);
                
                // Load mega menu helper if enabled
                if ($megaMenuEnabled) {
                    require_once __DIR__ . '/../app/Helpers/MegaMenuHelper.php';
                }
                ?>
                <div class="relative group mega-menu-wrapper" id="menu-<?= $item['id'] ?>" data-mega-menu="<?= $megaMenuEnabled ? 'true' : 'false' ?>">
                    <button class="nav-link-ultra px-4 py-2.5 rounded-xl transition-all duration-300 group relative flex items-center <?= $itemClasses ?> <?= $isActive ? 'active' : '' ?>" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?php if ($itemIcon): 
                            // Ensure icon has fas/far prefix if not already present
                            $iconClass = $itemIcon;
                            if (strpos($iconClass, 'fa-') !== false && strpos($iconClass, 'fas ') === false && strpos($iconClass, 'far ') === false && strpos($iconClass, 'fal ') === false && strpos($iconClass, 'fab ') === false) {
                                $iconClass = 'fas ' . $iconClass;
                            }
                        ?>
                            <i class="<?= escape($iconClass) ?> mr-2 flex-shrink-0" style="line-height: 1; vertical-align: middle;"></i>
                        <?php endif; ?>
                        <span class="flex-1" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; max-width: 100%; line-height: 1.5;"><?= $itemTitle ?></span>
                        <i class="fas fa-chevron-down ml-2 text-xs transform group-hover:rotate-180 transition-transform duration-300 flex-shrink-0" style="line-height: 1;"></i>
                        <span class="nav-link-indicator"></span>
                    </button>
                    <?php if ($megaMenuEnabled): ?>
                        <?= \App\Helpers\render_mega_menu($item, $item['children']) ?>
                    <?php else: ?>
                        <div class="absolute top-full left-0 mt-4 w-80 rounded-2xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-500 transform translate-y-4 group-hover:translate-y-0 border border-white/50 overflow-hidden" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(24px) saturate(180%); -webkit-backdrop-filter: blur(24px) saturate(180%);">
                            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>
                            <div class="p-3 max-h-96 overflow-y-auto">
                                <?php foreach ($item['children'] as $child): 
                                    $childUrl = $child['url'] ?? '#';
                                    $childTitle = escape($child['title']);
                                    $childIcon = $child['icon'] ?? '';
                                ?>
                                    <a href="<?= escape($childUrl) ?>" 
                                       target="<?= escape($child['target'] ?? '_self') ?>"
                                       class="group/item block px-4 py-3.5 rounded-xl hover:bg-gradient-to-r hover:from-blue-50/80 hover:via-indigo-50/80 hover:to-purple-50/80 transition-all duration-300 border-l-4 border-transparent hover:border-blue-500 hover:shadow-lg mb-1.5 relative overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/0 via-indigo-500/0 to-purple-500/0 group-hover/item:from-blue-500/5 group-hover/item:via-indigo-500/5 group-hover/item:to-purple-500/5 transition-all duration-300"></div>
                                        <div class="flex items-center justify-between relative z-10">
                                            <span class="font-semibold text-gray-800 group-hover/item:text-blue-600 transition-colors flex items-center">
                                                <?php if ($childIcon): 
                                                    // Ensure icon has fas/far prefix if not already present
                                                    $childIconClass = $childIcon;
                                                    if (strpos($childIconClass, 'fa-') !== false && strpos($childIconClass, 'fas ') === false && strpos($childIconClass, 'far ') === false && strpos($childIconClass, 'fal ') === false && strpos($childIconClass, 'fab ') === false) {
                                                        $childIconClass = 'fas ' . $childIconClass;
                                                    }
                                                ?>
                                                    <i class="<?= escape($childIconClass) ?> mr-2.5 text-blue-500 group-hover/item:scale-110 transition-transform duration-300 flex-shrink-0" style="line-height: 1; vertical-align: middle;"></i>
                                                <?php endif; ?>
                                                <span style="line-height: 1.5;"><?= $childTitle ?></span>
                                            </span>
                                            <i class="fas fa-arrow-right text-gray-400 group-hover/item:text-blue-600 transform group-hover/item:translate-x-2 transition-all duration-300"></i>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <a href="<?= escape($itemUrl) ?>" 
                   target="<?= escape($itemTarget) ?>"
                   class="nav-link-ultra px-4 py-2.5 rounded-xl transition-all duration-300 group relative flex items-center <?= $itemClasses ?> <?= $isActive ? 'active' : '' ?>"
                   style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?php if ($itemIcon): 
                        // Ensure icon has fas/far prefix if not already present
                        $iconClass = $itemIcon;
                        if (strpos($iconClass, 'fa-') !== false && strpos($iconClass, 'fas ') === false && strpos($iconClass, 'far ') === false && strpos($iconClass, 'fal ') === false && strpos($iconClass, 'fab ') === false) {
                            $iconClass = 'fas ' . $iconClass;
                        }
                    ?>
                        <i class="<?= escape($iconClass) ?> mr-2 flex-shrink-0" style="line-height: 1; vertical-align: middle;"></i>
                    <?php endif; ?>
                    <span class="flex-1" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; max-width: 100%; line-height: 1.5;"><?= $itemTitle ?></span>
                    <span class="nav-link-indicator"></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php elseif ($location === 'mobile'): ?>
    <!-- Mobile Menu -->
    <div class="xl:hidden <?= $containerClass ?>">
        <!-- Mobile menu implementation -->
        <div class="space-y-2">
            <?php foreach ($menuTree as $item): 
                $hasChildren = !empty($item['children']);
                $itemUrl = $item['url'] ?? '#';
                $itemTitle = escape($item['title']);
                $itemIcon = $item['icon'] ?? '';
            ?>
                <?php if ($hasChildren): ?>
                    <div class="border-b border-gray-200 pb-2">
                        <div class="font-semibold text-gray-800 px-4 py-2 flex items-center">
                            <?php if ($itemIcon): 
                                // Ensure icon has fas/far prefix if not already present
                                $iconClass = $itemIcon;
                                if (strpos($iconClass, 'fa-') !== false && strpos($iconClass, 'fas ') === false && strpos($iconClass, 'far ') === false && strpos($iconClass, 'fal ') === false && strpos($iconClass, 'fab ') === false) {
                                    $iconClass = 'fas ' . $iconClass;
                                }
                            ?>
                                <i class="<?= escape($iconClass) ?> mr-2 flex-shrink-0" style="line-height: 1; vertical-align: middle;"></i>
                            <?php endif; ?>
                            <span style="line-height: 1.5;"><?= $itemTitle ?></span>
                        </div>
                        <div class="pl-6 space-y-1 mt-2">
                            <?php foreach ($item['children'] as $child): 
                                $childIcon = $child['icon'] ?? '';
                            ?>
                                <a href="<?= escape($child['url'] ?? '#') ?>" 
                                   class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg flex items-center">
                                    <?php if ($childIcon): 
                                        // Ensure icon has fas/far prefix if not already present
                                        $childIconClass = $childIcon;
                                        if (strpos($childIconClass, 'fa-') !== false && strpos($childIconClass, 'fas ') === false && strpos($childIconClass, 'far ') === false && strpos($childIconClass, 'fal ') === false && strpos($childIconClass, 'fab ') === false) {
                                            $childIconClass = 'fas ' . $childIconClass;
                                        }
                                    ?>
                                        <i class="<?= escape($childIconClass) ?> mr-2 flex-shrink-0" style="line-height: 1; vertical-align: middle;"></i>
                                    <?php endif; ?>
                                    <span style="line-height: 1.5;"><?= escape($child['title']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= escape($itemUrl) ?>" 
                       class="block px-4 py-2 text-gray-800 hover:bg-gray-50 rounded-lg flex items-center">
                        <?php if ($itemIcon): 
                            // Ensure icon has fas/far prefix if not already present
                            $iconClass = $itemIcon;
                            if (strpos($iconClass, 'fa-') !== false && strpos($iconClass, 'fas ') === false && strpos($iconClass, 'far ') === false && strpos($iconClass, 'fal ') === false && strpos($iconClass, 'fab ') === false) {
                                $iconClass = 'fas ' . $iconClass;
                            }
                        ?>
                            <i class="<?= escape($iconClass) ?> mr-2 flex-shrink-0" style="line-height: 1; vertical-align: middle;"></i>
                        <?php endif; ?>
                        <span style="line-height: 1.5;"><?= $itemTitle ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Generic Menu (Footer, Sidebar, etc.) -->
    <ul class="<?= $menuClass ?> <?= $containerClass ?>">
        <?php foreach ($menuTree as $item): 
            $hasChildren = !empty($item['children']);
            $itemUrl = $item['url'] ?? '#';
            $itemTitle = escape($item['title']);
            $itemIcon = $item['icon'] ?? '';
        ?>
            <li>
                <a href="<?= escape($itemUrl) ?>" 
                   target="<?= escape($item['target'] ?? '_self') ?>"
                   class="flex items-center <?= $item['css_classes'] ?? '' ?>">
                    <?php if ($itemIcon): 
                        // Ensure icon has fas/far prefix if not already present
                        $iconClass = $itemIcon;
                        if (strpos($iconClass, 'fa-') !== false && strpos($iconClass, 'fas ') === false && strpos($iconClass, 'far ') === false && strpos($iconClass, 'fal ') === false && strpos($iconClass, 'fab ') === false) {
                            $iconClass = 'fas ' . $iconClass;
                        }
                    ?>
                        <i class="<?= escape($iconClass) ?> mr-2 flex-shrink-0" style="line-height: 1; vertical-align: middle;"></i>
                    <?php endif; ?>
                    <span style="line-height: 1.5;"><?= $itemTitle ?></span>
                </a>
                <?php if ($hasChildren): ?>
                    <ul>
                        <?php foreach ($item['children'] as $child): ?>
                            <li>
                                <a href="<?= escape($child['url'] ?? '#') ?>" 
                                   target="<?= escape($child['target'] ?? '_self') ?>">
                                    <?= escape($child['title']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
