<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Services\UnForkliftScraper;
use App\Models\Product;
use App\Models\Category;
use App\Database\Connection;

$scraper = new UnForkliftScraper();
$productModel = new Product();
$categoryModel = new Category();
$db = Connection::getInstance();

$message = '';
$error = '';
$importResults = [];

// Handle import from URL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_url'])) {
    try {
        $productUrl = trim($_POST['product_url'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $downloadImages = isset($_POST['download_images_url']);
        
        if (empty($productUrl)) {
            throw new Exception('Product URL is required');
        }
        
        if (!filter_var($productUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL format');
        }
        
        // Extract product details
        $productData = $scraper->extractProductDetails($productUrl);
        
        if (!$productData || empty($productData['name'])) {
            throw new Exception('Could not extract product information from the URL');
        }
        
        // Generate slug
        $slug = generateSlug($productData['name']);
        
        // Check if product already exists
        $existing = $db->fetchOne(
            "SELECT id FROM products WHERE name = :name OR slug = :slug",
            ['name' => $productData['name'], 'slug' => $slug]
        );
        
        if ($existing) {
            $error = 'Product already exists in database';
        } else {
            // Get or use provided category
            if (!$categoryId) {
                // Try to find a matching category or create a default one
                $dbCategory = $categoryModel->getBySlug('uncategorized');
                if (!$dbCategory) {
                    $categoryData = [
                        'name' => 'Uncategorized',
                        'slug' => 'uncategorized',
                        'description' => 'Imported products',
                        'is_active' => 1,
                        'sort_order' => 999,
                    ];
                    $categoryId = $db->insert('categories', $categoryData);
                } else {
                    $categoryId = $dbCategory['id'];
                }
            }
            
            // Handle images
            $imagePath = '';
            $gallery = [];
            
            if ($downloadImages && !empty($productData['images'])) {
                $uploadDir = __DIR__ . '/../storage/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $mainImage = $productData['images'][0];
                $imageExt = pathinfo(parse_url($mainImage, PHP_URL_PATH), PATHINFO_EXTENSION);
                $imageExt = $imageExt ?: 'jpg';
                $imageFileName = 'unforklift_' . time() . '_' . rand(1000, 9999) . '.' . $imageExt;
                $imageFullPath = $uploadDir . $imageFileName;
                
                if ($scraper->downloadImage($mainImage, $imageFullPath)) {
                    $imagePath = $imageFileName;
                }
                
                for ($i = 1; $i < min(5, count($productData['images'])); $i++) {
                    $galleryImage = $productData['images'][$i];
                    $galleryExt = pathinfo(parse_url($galleryImage, PHP_URL_PATH), PATHINFO_EXTENSION);
                    $galleryExt = $galleryExt ?: 'jpg';
                    $galleryFileName = 'unforklift_' . time() . '_' . rand(1000, 9999) . '_' . $i . '.' . $galleryExt;
                    $galleryFullPath = $uploadDir . $galleryFileName;
                    
                    if ($scraper->downloadImage($galleryImage, $galleryFullPath)) {
                        $gallery[] = $galleryFileName;
                    }
                }
            } else {
                if (!empty($productData['images'])) {
                    $imagePath = $productData['images'][0];
                    $gallery = array_slice($productData['images'], 1, 4);
                }
            }
            
            // Insert product
            $productInsert = [
                'name' => $productData['name'],
                'slug' => $slug,
                'sku' => 'UN-' . strtoupper(substr($slug, 0, 8)) . '-' . time(),
                'description' => $productData['description'] ?: $productData['short_description'],
                'short_description' => $productData['short_description'] ?: mb_substr(strip_tags($productData['description']), 0, 200),
                'category_id' => $categoryId,
                'image' => $imagePath,
                'gallery' => !empty($gallery) ? json_encode($gallery) : null,
                'specifications' => !empty($productData['specifications']) ? json_encode($productData['specifications']) : null,
                'features' => !empty($productData['features']) ? implode("\n", $productData['features']) : null,
                'stock_status' => 'on_order',
                'is_active' => 1,
                'is_featured' => 0,
                'meta_title' => $productData['name'],
                'meta_description' => $productData['short_description'],
            ];
            
            $productId = $db->insert('products', $productInsert);
            $message = "Product imported successfully! <a href='product-edit.php?id=$productId'>View/Edit Product</a>";
        }
        
    } catch (Exception $e) {
        $error = 'Import error: ' . $e->getMessage();
    }
}

// Handle import request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    try {
        $categorySlug = $_POST['category'] ?? '';
        $downloadImages = isset($_POST['download_images']);
        $importCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        
        // Get all categories from scraper
        $categories = $scraper->getProductCategories();
        
        // Find the selected category
        $selectedCategory = null;
        foreach ($categories as $cat) {
            if ($cat['slug'] === $categorySlug) {
                $selectedCategory = $cat;
                break;
            }
        }
        
        if (!$selectedCategory) {
            throw new Exception('Invalid category selected');
        }
        
        // Get or create category in database
        $dbCategory = $categoryModel->getBySlug($categorySlug);
        if (!$dbCategory) {
            // Create category
            $categoryData = [
                'name' => $selectedCategory['name'],
                'slug' => $categorySlug,
                'description' => 'Imported from UN Forklift',
                'is_active' => 1,
                'sort_order' => 0,
            ];
            $categoryId = $db->insert('categories', $categoryData);
            $dbCategory = $categoryModel->getById($categoryId);
        }
        
        // Extract products from category page
        try {
            $products = $scraper->extractProductsFromCategory($selectedCategory['url']);
        } catch (Exception $e) {
            $error = 'Failed to extract products: ' . $e->getMessage() . 
                     '<br><br><strong>Tip:</strong> Try using the "Import from Product URL" method on the right to import individual products. ' .
                     'You can find product URLs by visiting <a href="https://www.unforklift.com/product/" target="_blank">UN Forklift product page</a> and clicking on individual products.';
            $products = [];
        }
        
        if (empty($products)) {
            if (empty($error)) {
                $error = 'No products found on the category page. The website structure may have changed. ' .
                         '<br><br><strong>Tip:</strong> Use the "Import from Product URL" method to import individual products instead.';
            }
        } else {
            foreach ($products as $productData) {
                try {
                    // Generate slug
                    $slug = generateSlug($productData['name']);
                    
                    // Check if product already exists (by name or slug)
                    $existing = $db->fetchOne(
                        "SELECT id FROM products WHERE name = :name OR slug = :slug",
                        [
                            'name' => $productData['name'],
                            'slug' => $slug
                        ]
                    );
                    
                    if ($existing) {
                        $skippedCount++;
                        continue;
                    }
                    
                    // Handle images
                    $imagePath = '';
                    $gallery = [];
                    
                    if ($downloadImages && !empty($productData['images'])) {
                        // Ensure upload directory exists
                        $uploadDir = __DIR__ . '/../storage/uploads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        // Download main image
                        $mainImage = $productData['images'][0];
                        $imageExt = pathinfo(parse_url($mainImage, PHP_URL_PATH), PATHINFO_EXTENSION);
                        $imageExt = $imageExt ?: 'jpg';
                        $imageFileName = 'unforklift_' . time() . '_' . rand(1000, 9999) . '.' . $imageExt;
                        $imageFullPath = $uploadDir . $imageFileName;
                        
                        if ($scraper->downloadImage($mainImage, $imageFullPath)) {
                            $imagePath = $imageFileName; // Store just filename, relative to storage/uploads/
                        } else {
                            $imagePath = '';
                        }
                        
                        // Download gallery images
                        for ($i = 1; $i < min(5, count($productData['images'])); $i++) {
                            $galleryImage = $productData['images'][$i];
                            $galleryExt = pathinfo(parse_url($galleryImage, PHP_URL_PATH), PATHINFO_EXTENSION);
                            $galleryExt = $galleryExt ?: 'jpg';
                            $galleryFileName = 'unforklift_' . time() . '_' . rand(1000, 9999) . '_' . $i . '.' . $galleryExt;
                            $galleryFullPath = $uploadDir . $galleryFileName;
                            
                            if ($scraper->downloadImage($galleryImage, $galleryFullPath)) {
                                $gallery[] = $galleryFileName; // Store just filename
                            }
                        }
                    } else {
                        // Just store URLs
                        if (!empty($productData['images'])) {
                            $imagePath = $productData['images'][0];
                            $gallery = array_slice($productData['images'], 1, 4);
                        }
                    }
                    
                    // Prepare product data
                    $productInsert = [
                        'name' => $productData['name'],
                        'slug' => $slug,
                        'sku' => 'UN-' . strtoupper(substr($slug, 0, 8)) . '-' . time(),
                        'description' => $productData['description'] ?: $productData['short_description'],
                        'short_description' => $productData['short_description'] ?: mb_substr(strip_tags($productData['description']), 0, 200),
                        'category_id' => $dbCategory['id'],
                        'image' => $imagePath,
                        'gallery' => !empty($gallery) ? json_encode($gallery) : null,
                        'specifications' => !empty($productData['specifications']) ? json_encode($productData['specifications']) : null,
                        'features' => !empty($productData['features']) ? implode("\n", $productData['features']) : null,
                        'stock_status' => 'on_order', // Products from supplier are typically on order
                        'is_active' => 1,
                        'is_featured' => 0,
                        'meta_title' => $productData['name'],
                        'meta_description' => $productData['short_description'],
                    ];
                    
                    // Insert product
                    $productId = $db->insert('products', $productInsert);
                    $importCount++;
                    
                    $importResults[] = [
                        'status' => 'success',
                        'name' => $productData['name'],
                        'id' => $productId
                    ];
                    
                } catch (Exception $e) {
                    $errorCount++;
                    $importResults[] = [
                        'status' => 'error',
                        'name' => $productData['name'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $message = "Import completed! Imported: $importCount, Errors: $errorCount, Skipped: $skippedCount";
        }
        
    } catch (Exception $e) {
        $error = 'Import error: ' . $e->getMessage();
    }
}

// Helper function to generate slug
function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

// Get available categories
$availableCategories = $scraper->getProductCategories();

$pageTitle = 'Import Products from UN Forklift';
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Import Products from UN Forklift</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Import from Category</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Select Category to Import</label>
                                    <select name="category" id="category" class="form-select" required>
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($availableCategories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat['slug']); ?>" 
                                                    data-url="<?php echo htmlspecialchars($cat['url']); ?>">
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        This will import products from the selected UN Forklift category page.
                                        <br><strong>Note:</strong> If category import fails (404 error), use the "Import from Product URL" method instead.
                                    </small>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="download_images" name="download_images" checked>
                                    <label class="form-check-label" for="download_images">
                                        Download images to server
                                    </label>
                                    <small class="form-text text-muted d-block">
                                        If unchecked, product images will be linked directly from UN Forklift website.
                                    </small>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <strong>Important:</strong> Category import may not work if UN Forklift has changed their website structure. 
                                    If you get a 404 error, please use the <strong>"Import from Product URL"</strong> method on the right instead.
                                </div>
                                <div class="alert alert-info">
                                    <strong>Note:</strong> This import process may take several minutes depending on the number of products. 
                                    The script will automatically skip products that already exist in your database.
                                </div>
                                
                                <button type="submit" name="import" class="btn btn-primary" id="btnImportCategory">
                                    <i class="fas fa-download"></i> Start Import
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Import from Product URL</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="product_url" class="form-label">Product URL</label>
                                    <input type="url" class="form-control" id="product_url" name="product_url" 
                                           placeholder="https://www.unforklift.com/product/..." required>
                                    <small class="form-text text-muted">
                                        Paste the direct URL to a product page on UN Forklift website.
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category (Optional)</label>
                                    <?php 
                                    $allCategories = $categoryModel->getAll(false);
                                    ?>
                                    <select name="category_id" id="category_id" class="form-select">
                                        <option value="">-- Auto-detect or create --</option>
                                        <?php foreach ($allCategories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>">
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        Select a category or leave empty to auto-create.
                                    </small>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="download_images_url" name="download_images_url" checked>
                                    <label class="form-check-label" for="download_images_url">
                                        Download images to server
                                    </label>
                                </div>
                                
                                <button type="submit" name="import_url" class="btn btn-success" id="btnImportUrl">
                                    <i class="fas fa-link"></i> Import from URL
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($importResults)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Import Results</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Product Name</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($importResults as $result): ?>
                                        <tr>
                                            <td>
                                                <?php if ($result['status'] === 'success'): ?>
                                                    <span class="badge bg-success">Success</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Error</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($result['name']); ?></td>
                                            <td>
                                                <?php if ($result['status'] === 'success'): ?>
                                                    <a href="product-edit.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        View/Edit
                                                    </a>
                                                <?php else: ?>
                                                    <small class="text-danger"><?php echo htmlspecialchars($result['error'] ?? 'Unknown error'); ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Manual Import Instructions</h5>
                </div>
                <div class="card-body">
                    <p>If the automatic import doesn't work, you can manually import products:</p>
                    <ol>
                        <li>Visit the UN Forklift website: <a href="https://www.unforklift.com" target="_blank">https://www.unforklift.com</a></li>
                        <li>Navigate to the product category you want to import</li>
                        <li>Copy product information and images</li>
                        <li>Use the <a href="product-edit.php">Add Product</a> page to manually create products</li>
                    </ol>
                    <p class="text-muted">
                        <strong>Alternative:</strong> You can also use the API endpoint if UN Forklift provides one, 
                        or contact them for a product data export (CSV/JSON format).
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div id="progressModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden flex flex-col">
        <div class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center">
            <h5 class="text-lg font-semibold" id="progressModalLabel">
                <i class="fas fa-spinner fa-spin"></i> Importing Products
            </h5>
            <button type="button" id="btnCloseModal" class="text-white hover:text-gray-200" onclick="closeProgressModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <div class="mb-4">
                <div class="flex justify-between mb-2">
                    <span id="progressStatus" class="text-sm font-medium">Initializing...</span>
                    <span id="progressPercent" class="text-sm font-medium">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden">
                    <div class="bg-blue-600 h-6 rounded-full transition-all duration-300 flex items-center justify-center" 
                         id="progressBar" 
                         style="width: 0%">
                        <span class="text-xs text-white font-medium" id="progressBarText"></span>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <strong class="text-sm font-semibold">Current Product:</strong>
                <div id="currentProduct" class="text-gray-600 text-sm mt-1">-</div>
            </div>
            
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="border rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-green-600 mb-1" id="importedCount">0</div>
                    <small class="text-gray-600">Imported</small>
                </div>
                <div class="border rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-yellow-600 mb-1" id="skippedCount">0</div>
                    <small class="text-gray-600">Skipped</small>
                </div>
                <div class="border rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-red-600 mb-1" id="errorCount">0</div>
                    <small class="text-gray-600">Errors</small>
                </div>
            </div>
            
            <div>
                <strong class="text-sm font-semibold mb-2 block">Recent Activity:</strong>
                <div id="progressLog" class="border rounded p-3 bg-gray-50 max-h-48 overflow-y-auto">
                    <small class="text-gray-500">Waiting for import to start...</small>
                </div>
            </div>
        </div>
        <div class="bg-gray-100 px-6 py-4 flex justify-end">
            <button type="button" 
                    class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed" 
                    id="btnCloseProgress" 
                    disabled 
                    onclick="location.reload()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
let progressInterval = null;
let currentSessionId = null;

function showProgressModal() {
    document.getElementById('progressModal').classList.remove('hidden');
    document.getElementById('btnCloseProgress').disabled = true;
}

function closeProgressModal() {
    if (progressInterval) {
        clearInterval(progressInterval);
    }
    document.getElementById('progressModal').classList.add('hidden');
}

function updateProgress(sessionId) {
    if (!sessionId) return;
    
    fetch('<?= url("admin/api/import-progress.php") ?>?session_id=' + sessionId)
        .then(response => response.json())
        .then(data => {
            // Update status
            document.getElementById('progressStatus').textContent = data.message || 'Processing...';
            document.getElementById('currentProduct').textContent = data.current_product || '-';
            
            // Update counts
            document.getElementById('importedCount').textContent = data.imported || 0;
            document.getElementById('skippedCount').textContent = data.skipped || 0;
            document.getElementById('errorCount').textContent = data.errors || 0;
            
            // Update progress bar
            if (data.total > 0) {
                const percent = Math.round((data.current / data.total) * 100);
                document.getElementById('progressBar').style.width = percent + '%';
                document.getElementById('progressBarText').textContent = percent + '%';
                document.getElementById('progressPercent').textContent = percent + '%';
            } else if (data.status === 'completed' || data.status === 'error') {
                document.getElementById('progressBar').style.width = '100%';
                document.getElementById('progressBarText').textContent = '100%';
                document.getElementById('progressPercent').textContent = '100%';
            }
            
            // Update log
            if (data.results && data.results.length > 0) {
                const logDiv = document.getElementById('progressLog');
                let logHtml = '';
                data.results.slice(-10).forEach(result => {
                    const icon = result.status === 'success' ? '✓' : result.status === 'error' ? '✗' : '⊘';
                    const color = result.status === 'success' ? 'text-success' : result.status === 'error' ? 'text-danger' : 'text-warning';
                    logHtml += `<div class="${color}"><small>${icon} ${result.name}</small></div>`;
                });
                logDiv.innerHTML = logHtml;
                logDiv.scrollTop = logDiv.scrollHeight;
            }
            
            // Check if completed
            if (data.status === 'completed' || data.status === 'error') {
                clearInterval(progressInterval);
                document.getElementById('btnCloseProgress').disabled = false;
                const labelEl = document.getElementById('progressModalLabel');
                if (data.status === 'completed') {
                    labelEl.innerHTML = '<i class="fas fa-check-circle text-green-500"></i> Import Completed';
                    labelEl.classList.add('text-green-600');
                } else {
                    labelEl.innerHTML = '<i class="fas fa-exclamation-circle text-red-500"></i> Import Failed';
                    labelEl.classList.add('text-red-600');
                }
                
                // Show final message
                if (data.status === 'completed') {
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            }
        })
        .catch(error => {
            console.error('Error fetching progress:', error);
        });
}

// Handle category import form
document.getElementById('btnImportCategory')?.addEventListener('click', function(e) {
    e.preventDefault();
    
    const form = this.closest('form');
    const formData = new FormData(form);
    formData.append('import_type', 'category');
    
    showProgressModal();
    
    fetch('<?= url("admin/api/import-unforklift.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response. Check console for details.');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.session_id) {
            currentSessionId = data.session_id;
            progressInterval = setInterval(() => {
                updateProgress(currentSessionId);
            }, 1000);
            updateProgress(currentSessionId);
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
            closeProgressModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error starting import: ' + error.message + '\n\nCheck browser console for more details.');
        closeProgressModal();
    });
});

// Handle URL import form
document.getElementById('btnImportUrl')?.addEventListener('click', function(e) {
    e.preventDefault();
    
    const form = this.closest('form');
    const formData = new FormData(form);
    formData.append('import_type', 'url');
    
    showProgressModal();
    
    fetch('<?= url("admin/api/import-unforklift.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response. Check console for details.');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.session_id) {
            currentSessionId = data.session_id;
            progressInterval = setInterval(() => {
                updateProgress(currentSessionId);
            }, 1000);
            updateProgress(currentSessionId);
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
            closeProgressModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error starting import: ' + error.message + '\n\nCheck browser console for more details.');
        closeProgressModal();
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

