<?php
/**
 * Mega Menu Helper
 * Renders advanced mega menu layouts with custom content
 */

namespace App\Helpers;

use App\Models\MegaMenuWidget;
use App\Models\Product;
use App\Models\Category;

if (!function_exists('App\Helpers\render_mega_menu')) {
/**
 * Render mega menu dropdown
 */
function render_mega_menu($menuItem, $children = [])
{
    if (empty($menuItem['mega_menu_enabled'])) {
        return render_standard_dropdown($menuItem, $children);
    }
    
    $layout = $menuItem['mega_menu_layout'] ?? 'columns';
    $width = $menuItem['mega_menu_width'] ?? 'auto';
    $columns = (int)($menuItem['mega_menu_columns'] ?? 3);
    $background = !empty($menuItem['mega_menu_background']) ? $menuItem['mega_menu_background'] : null;
    $customCss = !empty($menuItem['mega_menu_custom_css']) ? $menuItem['mega_menu_custom_css'] : null;
    
    // Get widgets
    $widgetModel = new MegaMenuWidget();
    $widgets = $widgetModel->getByMenuItemId($menuItem['id']);
    
    // Organize widgets by column
    $widgetsByColumn = [];
    foreach ($widgets as $widget) {
        $col = (int)($widget['widget_column'] ?? 1);
        if (!isset($widgetsByColumn[$col])) {
            $widgetsByColumn[$col] = [];
        }
        $widgetsByColumn[$col][] = $widget;
    }
    
    // Get products if any
    $products = get_mega_menu_products($menuItem['id']);
    
    // Get categories if any
    $categories = get_mega_menu_categories($menuItem['id']);
    
    // Calculate actual column count
    $actualColumns = max($columns, count($widgetsByColumn), !empty($children) ? 1 : 0);
    
    ob_start();
    ?>
    <div class="mega-menu-dropdown" 
         data-layout="<?= escape($layout) ?>"
         data-width="<?= escape($width) ?>"
         style="<?= $background ? 'background: ' . escape($background) . ';' : '' ?> <?= $customCss ? escape($customCss) : '' ?>">
        
        <div class="mega-menu-container" style="width: <?= $width === 'auto' ? 'auto' : escape($width) ?>;">
            <div class="mega-menu-grid" style="grid-template-columns: repeat(<?= $actualColumns ?>, 1fr);">
                
                <?php if (!empty($children)): ?>
                <!-- Standard Menu Items Column -->
                <div class="mega-menu-column mega-menu-children">
                    <?php foreach ($children as $child): ?>
                    <a href="<?= escape($child['url'] ?? '#') ?>" 
                       class="mega-menu-item"
                       target="<?= escape($child['target'] ?? '_self') ?>">
                        <?php if (!empty($child['icon'])): ?>
                        <i class="<?= escape($child['icon']) ?>"></i>
                        <?php endif; ?>
                        <span><?= escape($child['title']) ?></span>
                        <?php if (!empty($child['description'])): ?>
                        <small><?= escape($child['description']) ?></small>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php for ($col = 1; $col <= $columns; $col++): ?>
                <div class="mega-menu-column" data-column="<?= $col ?>">
                    <?php if (isset($widgetsByColumn[$col])): ?>
                        <?php foreach ($widgetsByColumn[$col] as $widget): ?>
                            <?= render_mega_widget($widget) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
                
                <?php if (!empty($products)): ?>
                <!-- Products Column -->
                <div class="mega-menu-column mega-menu-products">
                    <h4 class="mega-menu-section-title">Featured Products</h4>
                    <div class="mega-menu-products-grid">
                        <?php foreach ($products as $product): ?>
                        <a href="<?= url('product.php?slug=' . escape($product['slug'])) ?>" 
                           class="mega-menu-product-card">
                            <?php if (!empty($product['image'])): ?>
                            <div class="mega-menu-product-image">
                                <img src="<?= escape(image_url($product['image'])) ?>" alt="<?= escape($product['name']) ?>">
                            </div>
                            <?php endif; ?>
                            <div class="mega-menu-product-info">
                                <h5><?= escape($product['name']) ?></h5>
                                <?php if (!empty($product['price']) || !empty($product['sale_price'])): ?>
                                <div class="mega-menu-product-price">
                                    <?php if (!empty($product['sale_price'])): ?>
                                    <span class="sale-price">$<?= number_format($product['sale_price'], 2) ?></span>
                                    <?php if (!empty($product['price']) && $product['price'] > $product['sale_price']): ?>
                                    <span class="original-price">$<?= number_format($product['price'], 2) ?></span>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span class="price">$<?= number_format($product['price'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($categories)): ?>
                <!-- Categories Column -->
                <div class="mega-menu-column mega-menu-categories">
                    <h4 class="mega-menu-section-title">Categories</h4>
                    <div class="mega-menu-categories-list">
                        <?php foreach ($categories as $category): ?>
                        <a href="<?= url('products.php?category=' . escape($category['slug'])) ?>" 
                           class="mega-menu-category-item">
                            <?php if (!empty($category['image']) && !empty($category['show_image'])): ?>
                            <div class="mega-menu-category-image">
                                <img src="<?= escape(image_url($category['image'])) ?>" alt="<?= escape($category['name']) ?>">
                            </div>
                            <?php endif; ?>
                            <div class="mega-menu-category-info">
                                <h5><?= escape($category['name']) ?></h5>
                                <?php if (!empty($category['description']) && !empty($category['show_description'])): ?>
                                <p><?= escape($category['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render a mega menu widget
 */
function render_mega_widget($widget)
{
    $type = $widget['widget_type'] ?? 'text';
    $width = $widget['widget_width'] ?? 'full';
    $style = !empty($widget['widget_style']) ? $widget['widget_style'] : '';
    
    ob_start();
    ?>
    <div class="mega-menu-widget widget-<?= escape($type) ?> widget-width-<?= escape($width) ?>" style="<?= escape($style) ?>">
        <?php if (!empty($widget['widget_title'])): ?>
        <h4 class="mega-menu-widget-title"><?= escape($widget['widget_title']) ?></h4>
        <?php endif; ?>
        
        <?php if ($type === 'text'): ?>
            <div class="mega-menu-widget-content">
                <?= nl2br(escape($widget['widget_content'] ?? '')) ?>
            </div>
        
        <?php elseif ($type === 'image'): ?>
            <?php if (!empty($widget['widget_image'])): ?>
            <div class="mega-menu-widget-image">
                <?php if (!empty($widget['widget_url'])): ?>
                <a href="<?= escape($widget['widget_url']) ?>">
                <?php endif; ?>
                <img src="<?= escape(image_url($widget['widget_image'])) ?>" alt="<?= escape($widget['widget_title'] ?? '') ?>">
                <?php if (!empty($widget['widget_url'])): ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        
        <?php elseif ($type === 'html'): ?>
            <div class="mega-menu-widget-html">
                <?= $widget['widget_content'] ?? '' ?>
            </div>
        
        <?php elseif ($type === 'button'): ?>
            <?php if (!empty($widget['widget_url'])): ?>
            <a href="<?= escape($widget['widget_url']) ?>" class="mega-menu-widget-button">
                <?= escape($widget['widget_content'] ?? $widget['widget_title'] ?? 'Click Here') ?>
            </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Get products for mega menu
 */
function get_mega_menu_products($menuItemId)
{
    try {
        $db = \App\Database\Connection::getInstance();
        $products = $db->fetchAll(
            "SELECT p.* FROM products p
             INNER JOIN mega_menu_products mmp ON p.id = mmp.product_id
             WHERE mmp.menu_item_id = :menu_item_id AND p.is_active = 1
             ORDER BY mmp.display_order ASC
             LIMIT 6",
            ['menu_item_id' => $menuItemId]
        );
        return $products ?: [];
    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Get categories for mega menu
 */
function get_mega_menu_categories($menuItemId)
{
    try {
        $db = \App\Database\Connection::getInstance();
        $categories = $db->fetchAll(
            "SELECT c.*, mmc.show_image, mmc.show_description 
             FROM categories c
             INNER JOIN mega_menu_categories mmc ON c.id = mmc.category_id
             WHERE mmc.menu_item_id = :menu_item_id AND c.is_active = 1
             ORDER BY mmc.display_order ASC
             LIMIT 8",
            ['menu_item_id' => $menuItemId]
        );
        return $categories ?: [];
    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Render standard dropdown (fallback)
 */
function render_standard_dropdown($menuItem, $children = [])
{
    if (empty($children)) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="standard-menu-dropdown">
        <?php foreach ($children as $child): ?>
        <a href="<?= escape($child['url'] ?? '#') ?>" 
           class="standard-menu-item"
           target="<?= escape($child['target'] ?? '_self') ?>">
            <?php if (!empty($child['icon'])): ?>
            <i class="<?= escape($child['icon']) ?>"></i>
            <?php endif; ?>
            <span><?= escape($child['title']) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
}
