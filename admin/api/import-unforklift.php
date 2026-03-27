<?php
// Start output buffering to catch any errors
ob_start();

// Suppress error display but log them
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../bootstrap/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Increase execution time for import
set_time_limit(300); // 5 minutes
ini_set('max_execution_time', 300);

// Clear any previous output
ob_clean();

header('Content-Type: application/json');

use App\Services\UnForkliftScraper;
use App\Models\Product;
use App\Models\Category;
use App\Database\Connection;

// Generate session ID for progress tracking
$sessionId = uniqid('import_', true);
$progressFile = __DIR__ . '/../../storage/cache/import_progress_' . $sessionId . '.json';

// Ensure cache directory exists
$cacheDir = dirname($progressFile);
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Initialize progress
$progress = [
    'session_id' => $sessionId,
    'status' => 'starting',
    'message' => 'Initializing import...',
    'total' => 0,
    'current' => 0,
    'imported' => 0,
    'errors' => 0,
    'skipped' => 0,
    'current_product' => '',
    'results' => []
];

file_put_contents($progressFile, json_encode($progress));

// Function to update progress
function updateProgress($file, $data) {
    $current = json_decode(file_get_contents($file), true);
    $current = array_merge($current, $data);
    file_put_contents($file, json_encode($current));
}

// Function to generate slug
function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

// Output buffering is already started at the top

try {
    $scraper = new UnForkliftScraper();
    $productModel = new Product();
    $categoryModel = new Category();
    $db = Connection::getInstance();
    
    $importType = $_POST['import_type'] ?? 'category';
    
    if ($importType === 'url') {
        // Import from URL
        $productUrl = trim($_POST['product_url'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $downloadImages = isset($_POST['download_images']);
        
        if (empty($productUrl)) {
            throw new Exception('Product URL is required');
        }
        
        updateProgress($progressFile, [
            'status' => 'extracting',
            'message' => 'Extracting product information...',
            'current_product' => $productUrl
        ]);
        
        $productData = $scraper->extractProductDetails($productUrl);
        
        if (!$productData || empty($productData['name'])) {
            throw new Exception('Could not extract product information from the URL');
        }
        
        updateProgress($progressFile, [
            'status' => 'processing',
            'message' => 'Processing product: ' . $productData['name'],
            'current_product' => $productData['name']
        ]);
        
        $slug = generateSlug($productData['name']);
        
        // Check if product already exists
        $existing = $db->fetchOne(
            "SELECT id FROM products WHERE name = :name OR slug = :slug",
            ['name' => $productData['name'], 'slug' => $slug]
        );
        
        if ($existing) {
            updateProgress($progressFile, [
                'status' => 'completed',
                'message' => 'Product already exists',
                'skipped' => 1,
                'completed_at' => time()
            ]);
            echo json_encode(['success' => true, 'session_id' => $sessionId, 'message' => 'Product already exists']);
            exit;
        }
        
        // Get or use provided category
        if (!$categoryId) {
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
        updateProgress($progressFile, [
            'status' => 'downloading_images',
            'message' => 'Downloading images...'
        ]);
        
        $imagePath = '';
        $gallery = [];
        
        if ($downloadImages && !empty($productData['images'])) {
            $uploadDir = __DIR__ . '/../../storage/uploads/';
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
        updateProgress($progressFile, [
            'status' => 'saving',
            'message' => 'Saving product to database...'
        ]);
        
        // Check which columns exist in database first
        $existingColumns = $db->fetchAll("SHOW COLUMNS FROM products");
        $columnNames = array_column($existingColumns, 'Field');
        
        // Build product insert - only use fields that exist in database
        $productInsert = [];
        
        // Required fields (must exist)
        $requiredFields = [
            'name' => $productData['name'],
            'slug' => $slug,
            'sku' => 'UN-' . strtoupper(substr($slug, 0, 8)) . '-' . time(),
            'category_id' => $categoryId,
            'stock_status' => 'on_order',
            'is_active' => 1,
            'is_featured' => 0,
        ];
        
        foreach ($requiredFields as $field => $value) {
            if (in_array($field, $columnNames)) {
                $productInsert[$field] = $value;
            }
        }
        
        // Optional fields - only add if column exists
        $optionalFields = [
            'description' => $productData['description'] ?: $productData['short_description'],
            'short_description' => $productData['short_description'] ?: mb_substr(strip_tags($productData['description'] ?? ''), 0, 200),
            'image' => $imagePath,
            'gallery' => !empty($gallery) ? json_encode($gallery) : null,
            'specifications' => !empty($productData['specifications']) ? json_encode($productData['specifications']) : null,
            'features' => !empty($productData['features']) ? implode("\n", $productData['features']) : null,
            'meta_title' => $productData['name'],
            'meta_description' => $productData['short_description'],
            'price' => null, // UN Forklift doesn't provide prices, leave as null
            'sale_price' => null,
        ];
        
        foreach ($optionalFields as $field => $value) {
            if (in_array($field, $columnNames)) {
                $productInsert[$field] = $value;
            }
        }
        
        // Add supplier_url if column exists
        if (in_array('supplier_url', $columnNames)) {
            $productInsert['supplier_url'] = $productUrl;
        }
        
        // Add forklift-specific fields if they exist in database
        $forkliftFields = [
            'capacity', 'lifting_height', 'mast_type', 'power_type', 'engine_power',
            'battery_capacity', 'fuel_consumption', 'max_speed', 'turning_radius',
            'overall_length', 'overall_width', 'overall_height', 'wheelbase',
            'tire_type', 'manufacturer_model', 'year_manufactured', 'warranty_period',
            'country_of_origin'
        ];
        
        foreach ($forkliftFields as $field) {
            if (in_array($field, $columnNames) && isset($productData[$field]) && !empty($productData[$field])) {
                $productInsert[$field] = $productData[$field];
            }
        }
        
        $productId = $db->insert('products', $productInsert);
        
        updateProgress($progressFile, [
            'status' => 'completed',
            'message' => 'Product imported successfully!',
            'imported' => 1,
            'current' => 1,
            'total' => 1,
            'results' => [[
                'status' => 'success',
                'name' => $productData['name'],
                'id' => $productId
            ]],
            'completed_at' => time()
        ]);
        
        ob_clean();
        echo json_encode(['success' => true, 'session_id' => $sessionId, 'product_id' => $productId]);
        exit;
        
    } else {
        // Import from category
        $categorySlug = $_POST['category'] ?? '';
        $downloadImages = isset($_POST['download_images']);
        
        if (empty($categorySlug)) {
            throw new Exception('Category is required');
        }
        
        $categories = $scraper->getProductCategories();
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
        
        updateProgress($progressFile, [
            'status' => 'extracting',
            'message' => 'Extracting products from category page...',
            'current_product' => 'Scanning category: ' . $selectedCategory['name']
        ]);
        
        // Extract products
        $products = $scraper->extractProductsFromCategory($selectedCategory['url']);
        
        if (empty($products)) {
            throw new Exception('No products found on the category page');
        }
        
        $totalProducts = count($products);
        updateProgress($progressFile, [
            'status' => 'importing',
            'message' => "Found $totalProducts products. Starting import...",
            'total' => $totalProducts,
            'current' => 0
        ]);
        
        $importCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        $results = [];
        
        foreach ($products as $index => $productData) {
            $currentNum = $index + 1;
            updateProgress($progressFile, [
                'status' => 'importing',
                'message' => "Importing product $currentNum of $totalProducts: " . ($productData['name'] ?? 'Unknown'),
                'current' => $currentNum,
                'current_product' => $productData['name'] ?? 'Unknown'
            ]);
            
            try {
                $slug = generateSlug($productData['name']);
                
                // Check if product already exists
                $existing = $db->fetchOne(
                    "SELECT id FROM products WHERE name = :name OR slug = :slug",
                    ['name' => $productData['name'], 'slug' => $slug]
                );
                
                if ($existing) {
                    $skippedCount++;
                    $results[] = [
                        'status' => 'skipped',
                        'name' => $productData['name'],
                        'message' => 'Already exists'
                    ];
                    continue;
                }
                
                // Handle images
                $imagePath = '';
                $gallery = [];
                
                if ($downloadImages && !empty($productData['images'])) {
                    $uploadDir = __DIR__ . '/../../storage/uploads/';
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
                
                // Check which columns exist in database first (do this once per batch)
                if (!isset($columnNames)) {
                    $existingColumns = $db->fetchAll("SHOW COLUMNS FROM products");
                    $columnNames = array_column($existingColumns, 'Field');
                }
                
                // Build product insert - only use fields that exist in database
                $productInsert = [];
                
                // Required fields (must exist)
                $requiredFields = [
                    'name' => $productData['name'],
                    'slug' => $slug,
                    'sku' => 'UN-' . strtoupper(substr($slug, 0, 8)) . '-' . time(),
                    'category_id' => $dbCategory['id'],
                    'stock_status' => 'on_order',
                    'is_active' => 1,
                    'is_featured' => 0,
                ];
                
                foreach ($requiredFields as $field => $value) {
                    if (in_array($field, $columnNames)) {
                        $productInsert[$field] = $value;
                    }
                }
                
                // Optional fields - only add if column exists
                $optionalFields = [
                    'description' => $productData['description'] ?: $productData['short_description'],
                    'short_description' => $productData['short_description'] ?: mb_substr(strip_tags($productData['description'] ?? ''), 0, 200),
                    'image' => $imagePath,
                    'gallery' => !empty($gallery) ? json_encode($gallery) : null,
                    'specifications' => !empty($productData['specifications']) ? json_encode($productData['specifications']) : null,
                    'features' => !empty($productData['features']) ? implode("\n", $productData['features']) : null,
                    'meta_title' => $productData['name'],
                    'meta_description' => $productData['short_description'],
                    'price' => null, // UN Forklift doesn't provide prices, leave as null
                    'sale_price' => null,
                ];
                
                foreach ($optionalFields as $field => $value) {
                    if (in_array($field, $columnNames)) {
                        $productInsert[$field] = $value;
                    }
                }
                
                // Add supplier_url if column exists
                if (in_array('supplier_url', $columnNames) && isset($productData['url'])) {
                    $productInsert['supplier_url'] = $productData['url'];
                }
                
                // Add forklift-specific fields if they exist in database
                $forkliftFields = [
                    'capacity', 'lifting_height', 'mast_type', 'power_type', 'engine_power',
                    'battery_capacity', 'fuel_consumption', 'max_speed', 'turning_radius',
                    'overall_length', 'overall_width', 'overall_height', 'wheelbase',
                    'tire_type', 'manufacturer_model', 'year_manufactured', 'warranty_period',
                    'country_of_origin'
                ];
                
                foreach ($forkliftFields as $field) {
                    if (in_array($field, $columnNames) && isset($productData[$field]) && !empty($productData[$field])) {
                        $productInsert[$field] = $productData[$field];
                    }
                }
                
                $productId = $db->insert('products', $productInsert);
                $importCount++;
                
                $results[] = [
                    'status' => 'success',
                    'name' => $productData['name'],
                    'id' => $productId
                ];
                
            } catch (Exception $e) {
                $errorCount++;
                $results[] = [
                    'status' => 'error',
                    'name' => $productData['name'] ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
            }
            
            // Update progress after each product
            updateProgress($progressFile, [
                'imported' => $importCount,
                'errors' => $errorCount,
                'skipped' => $skippedCount,
                'results' => $results
            ]);
        }
        
        updateProgress($progressFile, [
            'status' => 'completed',
            'message' => "Import completed! Imported: $importCount, Errors: $errorCount, Skipped: $skippedCount",
            'completed_at' => time()
        ]);
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'session_id' => $sessionId,
            'imported' => $importCount,
            'errors' => $errorCount,
            'skipped' => $skippedCount
        ]);
        exit;
    }
    
} catch (Exception $e) {
    // Clear any output before sending JSON
    ob_clean();
    
    if (file_exists($progressFile)) {
        updateProgress($progressFile, [
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage(),
            'completed_at' => time()
        ]);
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'session_id' => $sessionId ?? ''
    ]);
    exit;
} catch (Error $e) {
    // Handle fatal errors
    ob_clean();
    
    if (file_exists($progressFile)) {
        updateProgress($progressFile, [
            'status' => 'error',
            'message' => 'Fatal Error: ' . $e->getMessage(),
            'completed_at' => time()
        ]);
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Fatal Error: ' . $e->getMessage(),
        'session_id' => $sessionId ?? ''
    ]);
    exit;
}

// Clean output buffer and send response
ob_end_flush();

