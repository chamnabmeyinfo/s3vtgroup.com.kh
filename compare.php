<?php
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Product;

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$productModel = new Product();

// Get compare IDs from session (primary) or URL params (fallback for compatibility)
$ids = [];
if (!empty($_SESSION['compare']) && is_array($_SESSION['compare'])) {
    $ids = array_filter(array_map('intval', $_SESSION['compare']));
} else {
    // Fallback to URL params for backward compatibility
    $compareIds = $_GET['ids'] ?? '';
    $ids = array_filter(array_map('intval', explode(',', $compareIds)));
}

$products = [];
foreach ($ids as $id) {
    $product = $productModel->getById($id);
    if ($product && $product['is_active']) {
        $products[] = $product;
    }
}

$pageTitle = 'Compare Products - ' . get_site_name();
include __DIR__ . '/includes/header.php';
?>

<main class="py-8">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold mb-6">Compare Products</h1>
        
        <?php if (empty($products)): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-8 text-center">
                <i class="fas fa-balance-scale text-6xl text-blue-400 mb-4"></i>
                <h2 class="text-2xl font-bold mb-2">No Products to Compare</h2>
                <p class="text-gray-600 mb-6">Add products to compare by clicking the compare button on product pages.</p>
                <a href="<?= url('products.php') ?>" class="btn-primary inline-block">
                    Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-500 uppercase">Feature</th>
                            <?php foreach ($products as $product): ?>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-500 uppercase min-w-[250px]">
                                <div class="text-center">
                                    <?php if (!empty($product['image'])): ?>
                                        <?php
                                        // Ensure image path is correct
                                        $imagePath = $product['image'];
                                        // If image doesn't start with storage/uploads/, add it
                                        if (strpos($imagePath, 'storage/uploads/') !== 0 && strpos($imagePath, '/') !== 0 && !preg_match('/^https?:\/\//', $imagePath)) {
                                            $imagePath = 'storage/uploads/' . ltrim($imagePath, '/');
                                        }
                                        $imageUrl = image_url($imagePath);
                                        ?>
                                        <img src="<?= escape($imageUrl) ?>" 
                                             alt="<?= escape($product['name']) ?>" 
                                             class="h-32 w-32 object-cover mx-auto mb-2 rounded">
                                    <?php endif; ?>
                                    <h3 class="font-bold text-lg"><?= escape($product['name']) ?></h3>
                                    <p class="text-2xl font-bold text-blue-600 mt-2">$<?= number_format((float)($product['price'] ?? 0), 2) ?></p>
                                    <a href="<?= url('product.php?slug=' . escape($product['slug'])) ?>" 
                                       class="btn-primary-sm mt-2 inline-block">View Details</a>
                                </div>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 font-semibold">Name</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4"><?= escape($product['name']) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 font-semibold">Price</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4">
                                <span class="text-lg font-bold">$<?= number_format((float)($product['price'] ?? 0), 2) ?></span>
                                <?php if (!empty($product['sale_price']) && $product['sale_price'] > 0): ?>
                                    <div class="text-sm text-green-600">Sale: $<?= number_format((float)($product['sale_price'] ?? 0), 2) ?></div>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 font-semibold">SKU</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4"><?= escape($product['sku'] ?? 'N/A') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 font-semibold">Category</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4"><?= escape($product['category_name'] ?? 'N/A') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 font-semibold">Stock Status</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded <?= $product['stock_status'] === 'in_stock' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= ucwords(str_replace('_', ' ', $product['stock_status'])) ?>
                                </span>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php if (!empty(array_filter(array_column($products, 'weight')))): ?>
                        <tr>
                            <td class="px-6 py-4 font-semibold">Weight</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4"><?= !empty($product['weight']) && $product['weight'] > 0 ? number_format((float)($product['weight'] ?? 0), 2) . ' lbs' : 'N/A' ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty(array_filter(array_column($products, 'dimensions')))): ?>
                        <tr>
                            <td class="px-6 py-4 font-semibold">Dimensions</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4"><?= escape($product['dimensions'] ?? 'N/A') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="px-6 py-4 font-semibold">Description</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4 text-sm"><?= escape(substr($product['short_description'] ?? $product['description'] ?? '', 0, 150)) ?>...</td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 font-semibold">Actions</td>
                            <?php foreach ($products as $product): ?>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-2">
                                    <a href="<?= url('quote.php?product_id=' . $product['id']) ?>" class="btn-primary-sm text-center">
                                        Get Quote
                                    </a>
                                    <a href="<?= url('product.php?slug=' . escape($product['slug'])) ?>" class="btn-secondary text-center text-sm">
                                        View Details
                                    </a>
                                    <button onclick="removeFromCompare(<?= $product['id'] ?>)" class="btn-secondary text-center text-sm text-red-600 hover:bg-red-50">
                                        <i class="fas fa-times mr-1"></i>Remove
                                    </button>
                                </div>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 text-center">
                <button onclick="clearComparison()" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">
                    Clear Comparison
                </button>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
function clearComparison() {
    const compareUrl = window.APP_CONFIG?.urls?.compare || 'api/compare.php';
    fetch(`${compareUrl}?action=clear`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Dispatch event for other components
            document.dispatchEvent(new CustomEvent('compareUpdated', { detail: { count: 0 } }));
            window.location.href = '<?= url('compare.php') ?>';
        }
    })
    .catch(error => {
        console.error('Error clearing comparison:', error);
        window.location.href = '<?= url('compare.php') ?>';
    });
}

function removeFromCompare(productId) {
    const compareUrl = window.APP_CONFIG?.urls?.compare || 'api/compare.php';
    fetch(`${compareUrl}?action=remove&id=${productId}`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Dispatch event for other components
            document.dispatchEvent(new CustomEvent('compareUpdated', { detail: { count: data.count || 0 } }));
            // Reload page to update the comparison table
            window.location.reload();
        } else {
            alert(data.message || 'Error removing product from comparison');
        }
    })
    .catch(error => {
        console.error('Error removing from comparison:', error);
        alert('Error removing product from comparison');
    });
}

// Update compare count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCompareCount();
});

function updateCompareCount() {
    const compareUrl = window.APP_CONFIG?.urls?.compare || 'api/compare.php';
    fetch(`${compareUrl}?action=get`, {
        credentials: 'include'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const countEl = document.getElementById('compare-count');
            if (countEl && data.compare) {
                const count = Array.isArray(data.compare) ? data.compare.length : 0;
                if (count > 0) {
                    countEl.textContent = count;
                    countEl.classList.remove('hidden');
                } else {
                    countEl.classList.add('hidden');
                }
            }
        })
        .catch(error => {
            console.error('Error updating compare count:', error);
        });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

