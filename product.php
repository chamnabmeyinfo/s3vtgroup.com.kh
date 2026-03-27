<?php
require_once __DIR__ . '/bootstrap/app.php';

// Check under construction mode
use App\Helpers\UnderConstruction;
UnderConstruction::show();

use App\Models\Product;
use App\Models\Category;

if (empty($_GET['slug'])) {
    header('Location: ' . url('products.php'));
    exit;
}

$productModel = new Product();
$categoryModel = new Category();

$product = $productModel->getBySlug($_GET['slug']);

if (!$product) {
    header('Location: ' . url('products.php'));
    exit;
}

// Track recently viewed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Track in session
if (!isset($_SESSION['recently_viewed'])) {
    $_SESSION['recently_viewed'] = [];
}

// Remove if already exists
$_SESSION['recently_viewed'] = array_filter($_SESSION['recently_viewed'], fn($id) => $id != $product['id']);

// Add to beginning
array_unshift($_SESSION['recently_viewed'], $product['id']);

// Keep only last 10
$_SESSION['recently_viewed'] = array_slice($_SESSION['recently_viewed'], 0, 10);

$relatedProducts = $productModel->getAll([
    'category_id' => $product['category_id'],
    'limit' => 4
]);

// Remove current product from related
$relatedProducts = array_filter($relatedProducts, fn($p) => $p['id'] != $product['id']);
$relatedProducts = array_slice($relatedProducts, 0, 3);

$gallery = [];
if (!empty($product['gallery'])) {
    $gallery = json_decode($product['gallery'], true) ?? [];
}
if (!empty($product['image'])) {
    array_unshift($gallery, $product['image']);
}

$specifications = [];
if (!empty($product['specifications'])) {
    $specifications = json_decode($product['specifications'], true) ?? [];
}

// Get product variants
$variants = [];
$variantAttributes = [];
try {
    $variants = db()->fetchAll(
        "SELECT * FROM product_variants WHERE product_id = :product_id AND is_active = 1 ORDER BY sort_order, id",
        ['product_id' => $product['id']]
    );
    
    if (!empty($variants)) {
        $variantIds = array_column($variants, 'id');
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $attributes = db()->fetchAll(
            "SELECT * FROM product_variant_attributes WHERE variant_id IN ($placeholders)",
            $variantIds
        );
        
        foreach ($attributes as $attr) {
            if (!isset($variantAttributes[$attr['variant_id']])) {
                $variantAttributes[$attr['variant_id']] = [];
            }
            $variantAttributes[$attr['variant_id']][$attr['attribute_name']] = $attr['attribute_value'];
        }
        
        // Add attributes to variants
        foreach ($variants as &$variant) {
            $variant['attributes'] = $variantAttributes[$variant['id']] ?? [];
        }
    }
} catch (Exception $e) {
    // Variants table might not exist
    $variants = [];
}

$siteName = get_site_name();
$pageTitle = !empty(trim($product['meta_title'] ?? '')) ? escape($product['meta_title']) : (escape($product['name']) . ' - ' . $siteName);
$metaDescription = !empty(trim($product['meta_description'] ?? '')) ? escape($product['meta_description']) : (escape($product['short_description'] ?? $product['description'] ?? ''));
$canonicalUrl = url('product.php?slug=' . urlencode($product['slug']));
$ogImage = !empty($product['image']) ? image_url($product['image']) : (get_seo_defaults()['og_image'] ?? '');
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'description' => strip_tags($product['short_description'] ?? $product['description'] ?? ''),
    'url' => $canonicalUrl,
];
if (!empty($ogImage)) {
    $jsonLd['image'] = $ogImage;
}

include __DIR__ . '/includes/header.php';
?>

<main class="py-8">
    <div class="container mx-auto px-4">
        <!-- Admin Bar (if logged in) -->
        <?php if (session('admin_logged_in')): ?>
        <div class="mb-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-yellow-800">
                <i class="fas fa-user-shield"></i>
                <span>Admin Mode: You're viewing as administrator</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= url('admin/product-edit.php?id=' . $product['id']) ?>" 
                   class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm flex items-center gap-1">
                    <i class="fas fa-edit"></i>
                    <span>Edit Product</span>
                </a>
                <a href="<?= url('admin/products.php') ?>" 
                   class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm flex items-center gap-1">
                    <i class="fas fa-list"></i>
                    <span>All Products</span>
                </a>
                <a href="<?= url('admin/index.php') ?>" 
                   class="px-3 py-1 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="hidden sm:inline ml-1">Dashboard</span>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Breadcrumb -->
        <nav class="text-sm text-gray-600 mb-6">
            <a href="<?= url() ?>" class="hover:text-blue-600">Home</a>
            <span class="mx-2">/</span>
            <a href="<?= url('products.php') ?>" class="hover:text-blue-600">Products</a>
            <?php if (!empty($product['category_slug'])): ?>
                <span class="mx-2">/</span>
                <a href="<?= url('products.php?category=' . escape($product['category_slug'])) ?>" class="hover:text-blue-600">
                    <?= escape($product['category_name']) ?>
                </a>
            <?php endif; ?>
            <span class="mx-2">/</span>
            <span class="text-gray-900"><?= escape($product['name']) ?></span>
        </nav>
        
        <div class="grid md:grid-cols-2 gap-8 mb-12">
            <!-- Product Images -->
            <div>
                <div class="mb-4 relative overflow-hidden rounded-lg">
                    <?php if (!empty($gallery[0])): ?>
                        <img id="main-image" 
                             src="<?= asset('storage/uploads/' . escape($gallery[0])) ?>" 
                             alt="<?= escape($product['name']) ?>" 
                             class="product-zoom-image w-full max-w-[480px] max-h-[450px] aspect-[4/3] object-cover rounded-lg cursor-zoom-in mx-auto"
                             loading="eager">
                        <button onclick="openImageLightbox(this.previousElementSibling.src, this.previousElementSibling.alt)" 
                                class="absolute top-4 right-4 bg-white bg-opacity-80 hover:bg-opacity-100 rounded-full p-2 transition-all z-10">
                            <i class="fas fa-expand text-gray-700"></i>
                        </button>
                    <?php else: ?>
                        <div class="product-image-placeholder w-full h-96 rounded-lg">
                            <div class="text-center">
                                <i class="fas fa-image text-6xl mb-4"></i>
                                <p class="text-lg">No Image Available</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (count($gallery) > 1): ?>
                <div class="grid grid-cols-4 gap-2">
                    <?php foreach ($gallery as $index => $image): ?>
                    <img src="<?= asset('storage/uploads/' . escape($image)) ?>" 
                         alt="Gallery image <?= $index + 1 ?>"
                         onclick="changeMainImage('<?= asset('storage/uploads/' . escape($image)) ?>', this)"
                         class="gallery-thumbnail w-full h-24 object-cover rounded cursor-pointer border-2 border-transparent hover:border-blue-500 <?= $index === 0 ? 'active border-blue-500' : '' ?>"
                         loading="lazy">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div>
                <?php if ($product['is_featured']): ?>
                    <span class="inline-block bg-yellow-400 text-yellow-900 px-3 py-1 rounded text-sm font-bold mb-4">
                        Featured Product
                    </span>
                <?php endif; ?>
                
                <div class="flex justify-between items-start mb-4">
                    <h1 class="text-3xl font-bold"><?= escape($product['name']) ?></h1>
                    <?php if (session('admin_logged_in')): ?>
                        <a href="<?= url('admin/product-edit.php?id=' . $product['id']) ?>" 
                           class="ml-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-sm"
                           title="Edit Product">
                            <i class="fas fa-edit"></i>
                            <span class="hidden sm:inline">Edit</span>
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($product['category_name'])): ?>
                    <p class="text-gray-600 mb-4">Category: 
                        <a href="<?= url('products.php?category=' . escape($product['category_slug'])) ?>" 
                           class="text-blue-600 hover:underline">
                            <?= escape($product['category_name']) ?>
                        </a>
                    </p>
                <?php endif; ?>
                
                <div class="mb-6">
                    <?php 
                    $price = !empty($product['price']) && $product['price'] > 0 ? (float)$product['price'] : null;
                    $salePrice = !empty($product['sale_price']) && $product['sale_price'] > 0 ? (float)$product['sale_price'] : null;
                    ?>
                    <?php if ($salePrice && $price): ?>
                        <div class="flex items-center gap-4">
                            <span class="text-4xl font-bold text-blue-600">$<?= number_format((float)$salePrice, 2) ?></span>
                            <span class="text-2xl text-gray-400 line-through">$<?= number_format((float)$price, 2) ?></span>
                            <span class="bg-red-500 text-white px-2 py-1 rounded text-sm">
                                Save <?= number_format(((float)$price - (float)$salePrice) / (float)$price * 100, 0) ?>%
                            </span>
                        </div>
                    <?php elseif ($price): ?>
                        <span class="text-4xl font-bold text-blue-600">$<?= number_format((float)$price, 2) ?></span>
                    <?php else: ?>
                        <span class="text-4xl font-bold text-gray-500">Price on Request</span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($product['short_description'])): ?>
                    <p class="text-gray-700 mb-6"><?= nl2br(escape($product['short_description'])) ?></p>
                <?php endif; ?>
                
                <!-- Product Variants -->
                <?php if (!empty($variants)): ?>
                <div class="mb-6 p-4 bg-gray-50 rounded-lg border">
                    <h3 class="font-semibold mb-4 flex items-center">
                        <i class="fas fa-layer-group mr-2 text-purple-600"></i>
                        Available Variants
                    </h3>
                    
                    <?php
                    // Group variants by attributes
                    $attributeGroups = [];
                    foreach ($variants as $variant) {
                        foreach ($variant['attributes'] as $attrName => $attrValue) {
                            if (!isset($attributeGroups[$attrName])) {
                                $attributeGroups[$attrName] = [];
                            }
                            if (!in_array($attrValue, $attributeGroups[$attrName])) {
                                $attributeGroups[$attrName][] = $attrValue;
                            }
                        }
                    }
                    ?>
                    
                    <div id="variant-selector" class="space-y-4">
                        <?php foreach ($attributeGroups as $attrName => $attrValues): ?>
                        <div>
                            <label class="block text-sm font-medium mb-2"><?= escape($attrName) ?></label>
                            <select class="variant-attribute-select w-full px-4 py-2 border rounded-lg" 
                                    data-attribute="<?= escape($attrName) ?>"
                                    onchange="updateVariantSelection()">
                                <option value="">Select <?= escape($attrName) ?></option>
                                <?php foreach ($attrValues as $value): ?>
                                    <option value="<?= escape($value) ?>"><?= escape($value) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endforeach; ?>
                        
                        <div id="selected-variant-info" class="hidden p-3 bg-blue-50 border border-blue-200 rounded">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-semibold text-blue-900">Selected Variant:</span>
                                <span id="selected-variant-name" class="text-blue-700"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    <span id="selected-variant-price" class="text-2xl font-bold text-blue-600"></span>
                                    <span id="selected-variant-sale-price" class="text-lg text-gray-400 line-through ml-2"></span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Stock:</span>
                                    <span id="selected-variant-stock" class="ml-2 font-semibold"></span>
                                </div>
                            </div>
                            <div id="selected-variant-sku" class="text-xs text-gray-600 mt-2"></div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="selected-variant-id" value="">
                </div>
                <?php endif; ?>
                
                <div class="flex gap-4 mb-6 flex-wrap">
                    <button onclick="addToCart(<?= $product['id'] ?>)" id="add-to-cart-btn" class="btn-primary flex-1 text-center min-w-[140px]">
                        <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                    </button>
                    <a href="<?= url('quote.php?product_id=' . $product['id']) ?>" class="btn-secondary flex-1 text-center min-w-[140px]">
                        <i class="fas fa-calculator mr-2"></i> Request Quote
                    </a>
                    <a href="<?= url('contact.php?product=' . escape($product['name'])) ?>" class="btn-secondary flex-1 text-center min-w-[140px]">
                        <i class="fas fa-envelope mr-2"></i> Contact Us
                    </a>
                    <button onclick="addToWishlist(<?= $product['id'] ?>)" 
                            id="wishlist-btn-<?= $product['id'] ?>"
                            class="px-4 py-2 border-2 border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-heart"></i>
                    </button>
                    <button onclick="addToCompare(<?= $product['id'] ?>)" 
                            id="compare-btn-<?= $product['id'] ?>"
                            class="px-4 py-2 border-2 border-blue-300 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-balance-scale"></i>
                    </button>
                </div>
                
                <div class="border-t pt-6 space-y-3">
                    <?php if (!empty($product['sku'])): ?>
                        <p><strong>SKU:</strong> <?= escape($product['sku']) ?></p>
                    <?php endif; ?>
                    <p><strong>Stock Status:</strong> 
                        <span class="text-green-600 font-semibold"><?= ucwords(str_replace('_', ' ', $product['stock_status'])) ?></span>
                    </p>
                    <?php if (!empty($product['weight'])): ?>
                        <?php if (!empty($product['weight']) && $product['weight'] > 0): ?>
                            <p><strong>Weight:</strong> <?= number_format((float)$product['weight'], 2) ?> lbs</p>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($product['dimensions'])): ?>
                        <p><strong>Dimensions:</strong> <?= escape($product['dimensions']) ?></p>
                    <?php endif; ?>
                    
                    <!-- Social Sharing -->
                    <div class="pt-4">
                        <?php 
                        $metaDescription = $product['short_description'] ?? $product['name'];
                        include __DIR__ . '/includes/social-share.php'; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="mb-12">
            <div class="border-b flex items-center justify-between">
                <div>
                    <button onclick="showTab('description')" class="tab-btn active px-6 py-3 font-semibold">Description</button>
                    <button onclick="showTab('specifications')" class="tab-btn px-6 py-3 font-semibold">Specifications</button>
                    <button onclick="showTab('features')" class="tab-btn px-6 py-3 font-semibold">Features</button>
                </div>
                <a href="<?= url('product-reviews.php?product_id=' . $product['id']) ?>" class="text-blue-600 hover:underline font-semibold">
                    <i class="fas fa-star mr-1"></i> View Reviews
                </a>
            </div>
            
            <div id="description" class="tab-content py-6">
                <div class="prose max-w-none">
                    <?= nl2br(escape($product['description'] ?? 'No description available.')) ?>
                </div>
            </div>
            
            <div id="specifications" class="tab-content py-6 hidden">
                <?php if (!empty($specifications)): ?>
                    <table class="w-full">
                        <tbody>
                            <?php foreach ($specifications as $key => $value): ?>
                            <tr class="border-b">
                                <td class="py-2 font-semibold"><?= escape($key) ?></td>
                                <td class="py-2"><?= escape($value) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-gray-600">No specifications available.</p>
                <?php endif; ?>
            </div>
            
            <div id="features" class="tab-content py-6 hidden">
                <?php if (!empty($product['features'])): ?>
                    <div class="prose max-w-none">
                        <?= nl2br(escape($product['features'])) ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">No features listed.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
        <div>
            <h2 class="text-2xl font-bold mb-6">Related Products</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($relatedProducts as $related): ?>
                <div class="product-card bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <a href="<?= url('product.php?slug=' . escape($related['slug'])) ?>">
                        <div class="w-full aspect-[10/7] bg-gray-200 flex items-center justify-center overflow-hidden relative">
                            <?php if (!empty($related['image'])): ?>
                                <img src="<?= asset('storage/uploads/' . escape($related['image'])) ?>" 
                                     alt="<?= escape($related['name']) ?>" 
                                     class="w-full h-full object-cover transition-transform duration-300 hover:scale-110"
                                     loading="lazy"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="image-fallback" style="display: none;">
                                    <i class="fas fa-image text-4xl text-white"></i>
                                </div>
                            <?php else: ?>
                                <div class="product-image-placeholder w-full h-full">
                                    <i class="fas fa-image text-4xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-lg mb-2"><?= escape($related['name']) ?></h3>
                            <?php if (!empty($related['price']) && $related['price'] > 0): ?>
                                <p class="text-lg font-bold text-blue-600">$<?= number_format((float)($related['price'] ?? 0), 2) ?></p>
                            <?php else: ?>
                                <p class="text-lg font-bold text-gray-500">Price on Request</p>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
function changeMainImage(imageSrc, thumbnail) {
    const mainImage = document.getElementById('main-image');
    if (mainImage) {
        mainImage.src = imageSrc;
        
        // Update active thumbnail
        document.querySelectorAll('.gallery-thumbnail').forEach(thumb => {
            thumb.classList.remove('active', 'border-blue-500');
        });
        if (thumbnail) {
            thumbnail.classList.add('active', 'border-blue-500');
        }
    }
}

// Variant Management
const variants = <?= json_encode($variants ?? []) ?>;
let selectedVariant = null;

// Debug: Log variants
console.log('Product Variants:', variants);

function updateVariantSelection() {
    console.log('updateVariantSelection called');
    const selects = document.querySelectorAll('.variant-attribute-select');
    const selectedAttributes = {};
    
    selects.forEach(select => {
        const attrName = select.dataset.attribute;
        const attrValue = select.value;
        if (attrValue) {
            selectedAttributes[attrName] = attrValue;
        }
    });
    
    console.log('Selected attributes:', selectedAttributes);
    console.log('Available variants:', variants);
    
    // Find matching variant
    selectedVariant = variants.find(variant => {
        if (Object.keys(selectedAttributes).length === 0) return false;
        
        // Must have attributes
        if (!variant.attributes || Object.keys(variant.attributes).length === 0) {
            return false;
        }
        
        // Check if all selected attributes match this variant
        let allMatch = true;
        for (const [attrName, attrValue] of Object.entries(selectedAttributes)) {
            if (variant.attributes[attrName] !== attrValue) {
                allMatch = false;
                break;
            }
        }
        
        if (!allMatch) return false;
        
        // Check if variant has exactly the same number of attributes as selected
        // (to ensure we're matching the complete variant, not a partial match)
        const variantAttrCount = Object.keys(variant.attributes).length;
        const selectedAttrCount = Object.keys(selectedAttributes).length;
        
        return variantAttrCount === selectedAttrCount;
    });
    
    // Update UI
    const infoDiv = document.getElementById('selected-variant-info');
    const variantIdInput = document.getElementById('selected-variant-id');
    
    if (selectedVariant) {
        console.log('Found matching variant:', selectedVariant);
        infoDiv.classList.remove('hidden');
        document.getElementById('selected-variant-name').textContent = selectedVariant.name || 'Selected Variant';
        
        // Update price
        if (selectedVariant.sale_price) {
            document.getElementById('selected-variant-price').textContent = '$' + parseFloat(selectedVariant.sale_price).toFixed(2);
            document.getElementById('selected-variant-sale-price').textContent = '$' + parseFloat(selectedVariant.price).toFixed(2);
            document.getElementById('selected-variant-sale-price').classList.remove('hidden');
        } else {
            document.getElementById('selected-variant-price').textContent = '$' + parseFloat(selectedVariant.price).toFixed(2);
            document.getElementById('selected-variant-sale-price').classList.add('hidden');
        }
        
        // Update stock
        const stockStatus = selectedVariant.stock_status === 'in_stock' ? 
            '<span class="text-green-600">In Stock</span>' : 
            (selectedVariant.stock_status === 'out_of_stock' ? 
                '<span class="text-red-600">Out of Stock</span>' : 
                '<span class="text-yellow-600">On Order</span>');
        document.getElementById('selected-variant-stock').innerHTML = stockStatus;
        
        if (selectedVariant.stock_quantity > 0) {
            document.getElementById('selected-variant-stock').innerHTML += ' (' + selectedVariant.stock_quantity + ' available)';
        }
        
        // Update SKU
        if (selectedVariant.sku) {
            document.getElementById('selected-variant-sku').textContent = 'SKU: ' + selectedVariant.sku;
        }
        
        variantIdInput.value = selectedVariant.id;
        
        // Update main image if variant has image
        if (selectedVariant.image) {
            const mainImage = document.getElementById('main-image');
            if (mainImage) {
                mainImage.src = '<?= asset('storage/uploads/') ?>' + selectedVariant.image;
            }
        }
        
        // Update add to cart button
        const addToCartBtn = document.getElementById('add-to-cart-btn');
        if (addToCartBtn) {
            if (selectedVariant.stock_status === 'out_of_stock') {
                addToCartBtn.disabled = true;
                addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed');
                addToCartBtn.innerHTML = '<i class="fas fa-ban mr-2"></i> Out of Stock';
            } else {
                addToCartBtn.disabled = false;
                addToCartBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart mr-2"></i> Add to Cart';
            }
        }
    } else {
        console.log('No matching variant found');
        infoDiv.classList.add('hidden');
        variantIdInput.value = '';
        
        // Reset add to cart button
        const addToCartBtn = document.getElementById('add-to-cart-btn');
        if (addToCartBtn && variants.length > 0) {
            addToCartBtn.disabled = true;
            addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed');
            addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart mr-2"></i> Select Variant';
        }
    }
}


// Initialize - disable add to cart if variants exist but none selected
document.addEventListener('DOMContentLoaded', function() {
    if (variants.length > 0) {
        const addToCartBtn = document.getElementById('add-to-cart-btn');
        if (addToCartBtn) {
            addToCartBtn.disabled = true;
            addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed');
            addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart mr-2"></i> Select Variant';
        }
    }
});

function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active', 'border-b-2', 'border-blue-600');
    });
    
    // Show selected tab content
    document.getElementById(tabName).classList.remove('hidden');
    
    // Add active class to clicked button
    event.target.classList.add('active', 'border-b-2', 'border-blue-600');
}

function addToCart(productId, variantId) {
    variantId = variantId || document.getElementById('selected-variant-id')?.value;
    
    if (variants.length > 0 && !variantId) {
        alert('Please select a variant first.');
        return;
    }
    
    let url = '<?= url('api/cart.php') ?>?action=add&product_id=' + productId;
    if (variantId) {
        url += '&variant_id=' + variantId;
    }
    
    fetch(url, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product added to cart!', 'success');
            updateCartCount();
        } else {
            showNotification('Error adding to cart', 'error');
        }
    })
    .catch(error => {
        showNotification('Error adding to cart', 'error');
    });
}

function updateCartCount() {
    fetch('<?= url('api/cart.php') ?>?action=count')
        .then(response => response.json())
        .then(data => {
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                if (data.count > 0) {
                    cartCount.textContent = data.count;
                    cartCount.classList.remove('hidden');
                } else {
                    cartCount.classList.add('hidden');
                }
            }
        });
}

// Load cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
});
</script>

<?php include __DIR__ . '/includes/image-zoom.php'; ?>
<?php include __DIR__ . '/includes/quick-view-modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

