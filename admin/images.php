<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Product;
use App\Services\ImageOptimizer;

$imageOptimizer = new ImageOptimizer();
$message = '';
$error = '';
$optimizationResults = [];
$orphanedImages = [];

// Handle bulk optimization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_optimize'])) {
    if (!empty($_POST['selected_images'])) {
        $optimized = 0;
        $failed = 0;
        $totalSavings = 0;
        
        $quality = (int)($_POST['quality'] ?? 85);
        $maxWidth = !empty($_POST['max_width']) ? (int)$_POST['max_width'] : null;
        $maxHeight = !empty($_POST['max_height']) ? (int)$_POST['max_height'] : null;
        
        foreach ($_POST['selected_images'] as $filename) {
            try {
                $result = $imageOptimizer->compress($filename, $quality, $maxWidth, $maxHeight);
                $optimized++;
                $totalSavings += $result['savings'];
                $optimizationResults[] = $result;
            } catch (Exception $e) {
                $failed++;
                $optimizationResults[] = [
                    'success' => false,
                    'filename' => $filename,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $message = "Optimized {$optimized} image(s). ";
        if ($totalSavings > 0) {
            $message .= "Saved " . number_format($totalSavings / 1024, 2) . " KB total.";
        }
        if ($failed > 0) {
            $message .= " {$failed} failed.";
        }
    }
}

// Handle bulk conversion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_convert'])) {
    if (!empty($_POST['selected_images'])) {
        $converted = 0;
        $failed = 0;
        $targetFormat = $_POST['target_format'] ?? 'webp';
        $quality = (int)($_POST['convert_quality'] ?? 85);
        
        foreach ($_POST['selected_images'] as $filename) {
            try {
                $result = $imageOptimizer->convert($filename, $targetFormat, $quality);
                $converted++;
                // Delete original if conversion successful
                $originalPath = __DIR__ . '/../storage/uploads/' . basename($filename);
                if (file_exists($originalPath) && $result['success']) {
                    @unlink($originalPath);
                }
            } catch (Exception $e) {
                $failed++;
            }
        }
        
        $message = "Converted {$converted} image(s) to {$targetFormat}.";
        if ($failed > 0) {
            $message .= " {$failed} failed.";
        }
    }
}

// Handle cleanup orphaned images
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cleanup_orphaned'])) {
    // Get all used images from database
    $usedImages = [];
    $productModel = new Product();
    $allProducts = $productModel->getAll(['include_inactive' => true]);
    
    foreach ($allProducts as $product) {
        if (!empty($product['image'])) {
            $usedImages[] = $product['image'];
        }
        if (!empty($product['gallery'])) {
            $gallery = json_decode($product['gallery'], true) ?? [];
            $usedImages = array_merge($usedImages, $gallery);
        }
    }
    
    
    $orphaned = $imageOptimizer->findOrphanedImages($usedImages);
    
    if (!empty($_POST['delete_orphaned'])) {
        $deleted = 0;
        $failed = 0;
        
        // Get files to delete from POST data or use the found orphaned list
        $filesToDelete = [];
        if (!empty($_POST['orphaned_files'])) {
            $filesToDelete = $_POST['orphaned_files'];
        } elseif (!empty($orphaned)) {
            foreach ($orphaned as $img) {
                $filesToDelete[] = $img['filename'];
            }
        }
        
        foreach ($filesToDelete as $filename) {
            $filepath = __DIR__ . '/../storage/uploads/' . basename($filename);
            if (file_exists($filepath) && unlink($filepath)) {
                $deleted++;
            } else {
                $failed++;
            }
        }
        $message = "Cleaned up {$deleted} orphaned image(s).";
        if ($failed > 0) {
            $message .= " {$failed} failed.";
        }
        $orphanedImages = []; // Clear after deletion
    } else {
        $orphanedImages = $orphaned; // Store for display
        if (count($orphaned) > 0) {
            $message = "Found " . count($orphaned) . " orphaned image(s). Use the delete button below to remove them.";
        } else {
            $message = "No orphaned images found. All images are in use.";
        }
    }
}

// Handle bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    $deleted = 0;
    $failed = 0;
    
    if (!empty($_POST['selected_images'])) {
        foreach ($_POST['selected_images'] as $filename) {
            $filename = basename($filename);
            $filepath = __DIR__ . '/../storage/uploads/' . $filename;
            
            if (file_exists($filepath) && is_file($filepath)) {
                if (unlink($filepath)) {
                    $deleted++;
                } else {
                    $failed++;
                }
            }
        }
    }
    
    if ($deleted > 0) {
        $message = "Successfully deleted {$deleted} image(s).";
        if ($failed > 0) {
            $message .= " {$failed} failed.";
        }
    } else {
        $error = "Failed to delete images.";
    }
}

// Handle single delete
if (!empty($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filepath = __DIR__ . '/../storage/uploads/' . $filename;
    
    if (file_exists($filepath) && is_file($filepath)) {
        if (unlink($filepath)) {
            $message = 'Image deleted successfully.';
        } else {
            $error = 'Failed to delete image.';
        }
    } else {
        $error = 'Image not found.';
    }
}

// Get all images with metadata
$uploadDir = __DIR__ . '/../storage/uploads/';
$images = [];

if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($uploadDir . $file)) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                $filepath = $uploadDir . $file;
                $size = filesize($filepath);
                $date = filemtime($filepath);
                
                // Get image dimensions if possible
                $dimensions = null;
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $imageInfo = @getimagesize($filepath);
                    if ($imageInfo) {
                        $dimensions = $imageInfo[0] . ' × ' . $imageInfo[1];
                    }
                }
                
                $images[] = [
                    'filename' => $file,
                    'size' => $size,
                    'date' => $date,
                    'extension' => $extension,
                    'dimensions' => $dimensions,
                    'url' => asset('storage/uploads/' . $file)
                ];
            }
        }
    }
    
    // Apply filters
    $search = trim($_GET['search'] ?? '');
    $typeFilter = $_GET['type'] ?? 'all';
    $sizeFilter = $_GET['size'] ?? 'all';
    $optimizationFilter = $_GET['optimization_status'] ?? 'all';
    $usageFilter = $_GET['usage_status'] ?? 'all';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $sort = $_GET['sort'] ?? 'date_desc';
    
    // Filter by search
    if ($search) {
        $images = array_filter($images, function($img) use ($search) {
            return stripos($img['filename'], $search) !== false;
        });
    }
    
    // Filter by type
    if ($typeFilter !== 'all') {
        $images = array_filter($images, function($img) use ($typeFilter) {
            return $img['extension'] === $typeFilter;
        });
    }
    
    // Filter by size
    if ($sizeFilter !== 'all') {
        $sizeRanges = [
            'small' => [0, 100 * 1024], // 0-100KB
            'medium' => [100 * 1024, 500 * 1024], // 100KB-500KB
            'large' => [500 * 1024, 2 * 1024 * 1024], // 500KB-2MB
            'xlarge' => [2 * 1024 * 1024, PHP_INT_MAX] // 2MB+
        ];
        
        if (isset($sizeRanges[$sizeFilter])) {
            $range = $sizeRanges[$sizeFilter];
            $images = array_filter($images, function($img) use ($range) {
                return $img['size'] >= $range[0] && $img['size'] < $range[1];
            });
        }
    }
    
    // Filter by optimization status
    if ($optimizationFilter === 'needs_optimization') {
        $images = array_filter($images, function($img) {
            return !empty($img['needs_optimization']);
        });
    } elseif ($optimizationFilter === 'optimized') {
        $images = array_filter($images, function($img) {
            return empty($img['needs_optimization']);
        });
    }
    
    // Filter by usage status
    if ($usageFilter === 'used') {
        $images = array_filter($images, function($img) {
            return !empty($img['used_in']);
        });
    } elseif ($usageFilter === 'orphaned') {
        $images = array_filter($images, function($img) {
            return empty($img['used_in']);
        });
    }
    
    // Filter by date
    if ($dateFrom) {
        $dateFromTs = strtotime($dateFrom . ' 00:00:00');
        $images = array_filter($images, function($img) use ($dateFromTs) {
            return $img['date'] >= $dateFromTs;
        });
    }
    
    if ($dateTo) {
        $dateToTs = strtotime($dateTo . ' 23:59:59');
        $images = array_filter($images, function($img) use ($dateToTs) {
            return $img['date'] <= $dateToTs;
        });
    }
    
    // Sort
    switch ($sort) {
        case 'name_asc':
            usort($images, fn($a, $b) => strcmp($a['filename'], $b['filename']));
            break;
        case 'name_desc':
            usort($images, fn($a, $b) => strcmp($b['filename'], $a['filename']));
            break;
        case 'size_asc':
            usort($images, fn($a, $b) => $a['size'] <=> $b['size']);
            break;
        case 'size_desc':
            usort($images, fn($a, $b) => $b['size'] <=> $a['size']);
            break;
        case 'date_asc':
            usort($images, fn($a, $b) => $a['date'] <=> $b['date']);
            break;
        case 'date_desc':
        default:
            usort($images, fn($a, $b) => $b['date'] <=> $a['date']);
            break;
    }
}

// Get image usage (which products use which images)
$productModel = new Product();
$allProducts = $productModel->getAll(['include_inactive' => true]);
$imageUsage = [];

foreach ($allProducts as $product) {
    if (!empty($product['image'])) {
        $imageUsage[$product['image']][] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'slug' => $product['slug']
        ];
    }
    
    // Check gallery images
    if (!empty($product['gallery'])) {
        $gallery = json_decode($product['gallery'], true) ?? [];
        foreach ($gallery as $galleryImg) {
            $imageUsage[$galleryImg][] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'type' => 'gallery'
            ];
        }
    }
}

// Add usage info and optimization analysis to images
foreach ($images as &$image) {
    $image['usage'] = $imageUsage[$image['filename']] ?? [];
    $image['usage_count'] = count($image['usage']);
    
    // Get optimization analysis
    try {
        $analysis = $imageOptimizer->analyze($image['filename']);
        $image['needs_optimization'] = $analysis['needs_optimization'];
        $image['optimization_reasons'] = $analysis['optimization_reasons'];
        $image['size_category'] = $analysis['size_category'];
    } catch (Exception $e) {
        $image['needs_optimization'] = false;
        $image['optimization_reasons'] = [];
        $image['size_category'] = 'unknown';
    }
}

// Column visibility
$selectedColumns = $_GET['columns'] ?? ['checkbox', 'preview', 'filename', 'size', 'dimensions', 'date', 'usage', 'actions'];
$availableColumns = [
    'checkbox' => 'Checkbox',
    'preview' => 'Preview',
    'filename' => 'Filename',
    'size' => 'File Size',
    'dimensions' => 'Dimensions',
    'type' => 'Type',
    'date' => 'Upload Date',
    'usage' => 'Usage',
    'actions' => 'Actions'
];

$pageTitle = 'Image Management';
include __DIR__ . '/includes/header.php';

// Setup filter component
$filterId = 'images-filter';
$defaultColumns = $selectedColumns;
$filters = [
    'search' => true,
    'date_range' => true,
    'type' => [
        'options' => [
            'all' => 'All Types',
            'jpg' => 'JPG',
            'jpeg' => 'JPEG',
            'png' => 'PNG',
            'gif' => 'GIF',
            'webp' => 'WebP',
            'svg' => 'SVG'
        ]
    ],
    'size' => [
        'options' => [
            'all' => 'All Sizes',
            'small' => 'Small (< 100KB)',
            'medium' => 'Medium (100KB - 500KB)',
            'large' => 'Large (500KB - 2MB)',
            'xlarge' => 'Extra Large (> 2MB)'
        ]
    ],
    'optimization' => [
        'options' => [
            'all' => 'All Images',
            'needs_optimization' => 'Needs Optimization',
            'optimized' => 'Already Optimized'
        ]
    ]
];

$sortOptions = [
    'date_desc' => 'Date (Newest)',
    'date_asc' => 'Date (Oldest)',
    'name_asc' => 'Name (A-Z)',
    'name_desc' => 'Name (Z-A)',
    'size_asc' => 'Size (Smallest)',
    'size_desc' => 'Size (Largest)'
];
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-images mr-2 md:mr-3"></i>
                    Advanced Image Management
                </h1>
                <p class="text-indigo-100 text-sm md:text-lg">Manage, optimize, and compress your images</p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="toggleViewMode()" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all" id="viewModeBtn">
                    <i class="fas fa-th-large mr-2" id="viewIcon"></i> <span id="viewText" class="hidden sm:inline">Grid View</span>
                </button>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2 text-xl"></i>
            <span class="font-semibold"><?= escape($message) ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2 text-xl"></i>
            <span class="font-semibold"><?= escape($error) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Advanced Tools Section -->
    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl shadow-lg p-4 md:p-6 mb-6 border border-purple-200">
        <h2 class="text-xl md:text-2xl font-bold mb-4 text-gray-800">
            <i class="fas fa-magic mr-2 text-purple-600"></i>
            Advanced Image Tools
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Bulk Optimize -->
            <div class="bg-white rounded-lg p-4 shadow-md">
                <h3 class="font-semibold mb-3 text-gray-700">
                    <i class="fas fa-compress mr-2 text-blue-600"></i> Bulk Optimize
                </h3>
                <p class="text-sm text-gray-600 mb-3">Compress and resize selected images</p>
                <button onclick="showOptimizeModal()" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all text-sm font-medium">
                    <i class="fas fa-compress mr-2"></i> Optimize Images
                </button>
            </div>
            
            <!-- Format Conversion -->
            <div class="bg-white rounded-lg p-4 shadow-md">
                <h3 class="font-semibold mb-3 text-gray-700">
                    <i class="fas fa-exchange-alt mr-2 text-green-600"></i> Convert Format
                </h3>
                <p class="text-sm text-gray-600 mb-3">Convert images to WebP, JPG, PNG</p>
                <button onclick="showConvertModal()" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-700 transition-all text-sm font-medium">
                    <i class="fas fa-exchange-alt mr-2"></i> Convert Images
                </button>
            </div>
            
            <!-- Cleanup Orphaned -->
            <div class="bg-white rounded-lg p-4 shadow-md">
                <h3 class="font-semibold mb-3 text-gray-700">
                    <i class="fas fa-broom mr-2 text-red-600"></i> Cleanup
                </h3>
                <p class="text-sm text-gray-600 mb-3">Find and remove unused images</p>
                <form method="POST" onsubmit="return confirm('This will scan for orphaned images. Continue?')">
                    <input type="hidden" name="cleanup_orphaned" value="1">
                    <button type="submit" class="w-full bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-lg hover:from-red-600 hover:to-red-700 transition-all text-sm font-medium mb-2">
                        <i class="fas fa-search mr-2"></i> Find Orphaned
                    </button>
                </form>
                <?php if (!empty($orphanedImages)): ?>
                    <form method="POST" onsubmit="return confirm('This will permanently delete <?= count($orphanedImages) ?> orphaned image(s). This action cannot be undone. Continue?')">
                        <input type="hidden" name="cleanup_orphaned" value="1">
                        <input type="hidden" name="delete_orphaned" value="1">
                        <?php foreach ($orphanedImages as $img): ?>
                            <input type="hidden" name="orphaned_files[]" value="<?= escape($img['filename']) ?>">
                        <?php endforeach; ?>
                        <button type="submit" class="w-full bg-gradient-to-r from-red-600 to-red-700 text-white px-4 py-2 rounded-lg hover:from-red-700 hover:to-red-800 transition-all text-sm font-medium shadow-lg">
                            <i class="fas fa-trash-alt mr-2"></i> Delete <?= count($orphanedImages) ?> Orphaned Image(s)
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Upload Area -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">
            <i class="fas fa-upload mr-2"></i>
            Upload Images
        </h2>
        <div id="dropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition-colors">
            <form id="uploadForm" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-600 mb-2">Drag and drop images here, or click to select</p>
                    <input type="file" id="fileInput" name="file[]" accept="image/*" multiple
                           class="hidden">
                    <button type="button" onclick="document.getElementById('fileInput').click()" 
                            class="btn-primary">
                        <i class="fas fa-folder-open mr-2"></i> Select Images
                    </button>
                    <p class="text-sm text-gray-500 mt-2">Maximum file size: 5MB per image. Allowed: JPG, PNG, GIF, WebP, SVG</p>
                </div>
            </form>
            <div id="uploadProgress" class="mt-4 hidden">
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p id="progressText" class="text-sm text-gray-600 mt-2">Uploading...</p>
            </div>
            <div id="uploadResult" class="mt-4"></div>
        </div>
    </div>
    
    <!-- Advanced Filters -->
    <?php include __DIR__ . '/includes/advanced-filters.php'; ?>
    
    <!-- Bulk Actions -->
    <form method="POST" id="bulkForm" class="mb-4 hidden">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center justify-between">
            <span class="text-blue-800 font-semibold">
                <span id="selectedCount">0</span> image(s) selected
            </span>
            <div class="flex flex-wrap gap-2">
                <button type="submit" name="bulk_delete" 
                        onclick="return confirm('Are you sure you want to delete selected images?')"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-all">
                    <i class="fas fa-trash mr-2"></i> Delete Selected
                </button>
                <button type="button" onclick="showOptimizeModal()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-all">
                    <i class="fas fa-compress mr-2"></i> Optimize
                </button>
                <button type="button" onclick="showConvertModal()" 
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-all">
                    <i class="fas fa-exchange-alt mr-2"></i> Convert
                </button>
                <button type="button" onclick="clearSelection()" 
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-all">
                    Clear Selection
                </button>
            </div>
        </div>
    </form>
    
    <!-- Images Display -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold">Images (<?= count($images) ?>)</h2>
            <div class="text-sm text-gray-600">
                Total: <?= number_format(array_sum(array_column($images, 'size')) / 1024 / 1024, 2) ?> MB
            </div>
        </div>
        
        <?php if (empty($images)): ?>
            <div class="p-12 text-center text-gray-500">
                <i class="fas fa-images text-6xl mb-4 text-gray-300"></i>
                <p class="text-lg">No images found.</p>
                <p class="text-sm mt-2">Upload your first image to get started!</p>
            </div>
        <?php else: ?>
            <!-- Grid View -->
            <div id="gridView" class="p-6 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach ($images as $image): ?>
                <div class="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow image-card" data-filename="<?= escape($image['filename']) ?>">
                    <div class="relative aspect-square bg-gray-100 overflow-hidden group cursor-pointer" onclick="showImageModal('<?= escape($image['filename']) ?>')">
                        <img src="<?= escape($image['url']) ?>" 
                             alt="<?= escape($image['filename']) ?>" 
                             class="w-full h-full object-cover"
                             loading="lazy"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27200%27 height=%27200%27%3E%3Crect fill=%27%23ddd%27 width=%27200%27 height=%27200%27/%3E%3Ctext fill=%27%23999%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27%3EBroken%3C/text%3E%3C/svg%3E'">
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all flex items-center justify-center">
                            <div class="text-white opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-search-plus text-2xl"></i>
                            </div>
                        </div>
                        <div class="absolute top-2 left-2">
                            <input type="checkbox" 
                                   name="selected_images[]" 
                                   value="<?= escape($image['filename']) ?>"
                                   class="image-checkbox rounded border-gray-300"
                                   onchange="updateBulkActions()">
                        </div>
                        <?php if ($image['usage_count'] > 0): ?>
                        <div class="absolute top-2 right-2 bg-blue-500 text-white text-xs px-2 py-1 rounded">
                            <?= $image['usage_count'] ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-3">
                        <p class="text-xs text-gray-600 truncate mb-1" title="<?= escape($image['filename']) ?>">
                            <?= escape($image['filename']) ?>
                        </p>
                        <div class="flex justify-between text-xs text-gray-500 mb-2">
                            <span><?= number_format($image['size'] / 1024, 1) ?> KB</span>
                            <?php if ($image['dimensions']): ?>
                            <span><?= escape($image['dimensions']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex gap-1">
                            <button onclick="copyImageUrl('<?= escape($image['filename']) ?>')" 
                                    class="flex-1 text-xs px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
                                    title="Copy URL">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button onclick="copyImageFilename('<?= escape($image['filename']) ?>')" 
                                    class="flex-1 text-xs px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600"
                                    title="Copy Filename">
                                <i class="fas fa-file"></i>
                            </button>
                            <button onclick="deleteImage('<?= escape($image['filename']) ?>')" 
                                    class="flex-1 text-xs px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600"
                                    title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- List View (Hidden by default) -->
            <div id="listView" class="hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <?php if (in_array('checkbox', $selectedColumns)): ?>
                                <th class="px-4 py-3 text-left">
                                    <input type="checkbox" onchange="toggleAllImages(this)" class="rounded">
                                </th>
                                <?php endif; ?>
                                <?php if (in_array('preview', $selectedColumns)): ?>
                                <th class="px-4 py-3 text-left">Preview</th>
                                <?php endif; ?>
                                <?php if (in_array('filename', $selectedColumns)): ?>
                                <th class="px-4 py-3 text-left">Filename</th>
                                <?php endif; ?>
                                <?php if (in_array('size', $selectedColumns)): ?>
                                <th class="px-4 py-3 text-left">Size</th>
                                <?php endif; ?>
                                <?php if (in_array('dimensions', $selectedColumns)): ?>
                                <th class="px-4 py-3 text-left">Dimensions</th>
                                <?php endif; ?>
                                <?php if (in_array('type', $selectedColumns)): ?>
                                <th class="px-4 py-3 text-left">Type</th>
                                <?php endif; ?>
                                <?php if (in_array('date', $selectedColumns)): ?>
                                <th class="px-4 py-3 text-left">Upload Date</th>
                                <?php endif; ?>
                                <?php if (in_array('usage', $selectedColumns)): ?>
                                <th class="px-4 py-3 text-left">Usage</th>
                                <?php endif; ?>
                                <?php if (in_array('actions', $selectedColumns)): ?>
                                <th class="px-4 py-3 text-center">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($images as $image): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <?php if (in_array('checkbox', $selectedColumns)): ?>
                                <td class="px-4 py-3">
                                    <input type="checkbox" 
                                           name="selected_images[]" 
                                           value="<?= escape($image['filename']) ?>"
                                           class="image-checkbox rounded"
                                           onchange="updateBulkActions()">
                                </td>
                                <?php endif; ?>
                                <?php if (in_array('preview', $selectedColumns)): ?>
                                <td class="px-4 py-3">
                                    <img src="<?= escape($image['url']) ?>" 
                                         alt="" 
                                         class="w-16 h-16 object-cover rounded cursor-pointer"
                                         onclick="showImageModal('<?= escape($image['filename']) ?>')">
                                </td>
                                <?php endif; ?>
                                <?php if (in_array('filename', $selectedColumns)): ?>
                                <td class="px-4 py-3">
                                    <div class="font-medium"><?= escape($image['filename']) ?></div>
                                    <div class="text-xs text-gray-500"><?= escape($image['url']) ?></div>
                                </td>
                                <?php endif; ?>
                                <?php if (in_array('size', $selectedColumns)): ?>
                                <td class="px-4 py-3"><?= number_format($image['size'] / 1024, 2) ?> KB</td>
                                <?php endif; ?>
                                <?php if (in_array('dimensions', $selectedColumns)): ?>
                                <td class="px-4 py-3"><?= escape($image['dimensions'] ?? 'N/A') ?></td>
                                <?php endif; ?>
                                <?php if (in_array('type', $selectedColumns)): ?>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 bg-gray-100 rounded text-xs"><?= strtoupper($image['extension']) ?></span>
                                </td>
                                <?php endif; ?>
                                <?php if (in_array('date', $selectedColumns)): ?>
                                <td class="px-4 py-3 text-sm">
                                    <?= date('M d, Y', $image['date']) ?><br>
                                    <span class="text-xs text-gray-500"><?= date('H:i', $image['date']) ?></span>
                                </td>
                                <?php endif; ?>
                                <?php if (in_array('usage', $selectedColumns)): ?>
                                <td class="px-4 py-3">
                                    <?php if ($image['usage_count'] > 0): ?>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                            Used in <?= $image['usage_count'] ?> product(s)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">Not used</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <?php if (in_array('actions', $selectedColumns)): ?>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2 justify-center">
                                        <button onclick="copyImageUrl('<?= escape($image['filename']) ?>')" 
                                                class="text-blue-600 hover:text-blue-800" title="Copy URL">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button onclick="copyImageFilename('<?= escape($image['filename']) ?>')" 
                                                class="text-green-600 hover:text-green-800" title="Copy Filename">
                                            <i class="fas fa-file"></i>
                                        </button>
                                        <button onclick="deleteImage('<?= escape($image['filename']) ?>')" 
                                                class="text-red-600 hover:text-red-800" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Image Preview Modal -->
<div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4" onclick="closeImageModal()">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-auto" onclick="event.stopPropagation()">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-xl font-bold" id="modalTitle">Image Preview</h3>
            <button onclick="closeImageModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="text-center mb-4">
                <img id="modalImage" src="" alt="" class="max-w-full max-h-96 mx-auto rounded-lg shadow-lg">
            </div>
            <div class="grid md:grid-cols-2 gap-4" id="modalDetails">
                <!-- Details will be populated by JavaScript -->
            </div>
            <div id="modalUsage" class="mt-4">
                <!-- Usage info will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
// View mode (grid/list)
let viewMode = localStorage.getItem('imageViewMode') || 'grid';

function toggleViewMode() {
    viewMode = viewMode === 'grid' ? 'list' : 'grid';
    localStorage.setItem('imageViewMode', viewMode);
    updateViewMode();
}

function updateViewMode() {
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');
    const viewIcon = document.getElementById('viewIcon');
    const viewText = document.getElementById('viewText');
    
    if (viewMode === 'grid') {
        gridView.classList.remove('hidden');
        listView.classList.add('hidden');
        viewIcon.className = 'fas fa-list mr-2';
        viewText.textContent = 'List View';
    } else {
        gridView.classList.add('hidden');
        listView.classList.remove('hidden');
        viewIcon.className = 'fas fa-th-large mr-2';
        viewText.textContent = 'Grid View';
    }
}

updateViewMode();

// Drag and drop upload
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-blue-500', 'bg-blue-50');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('border-blue-500', 'bg-blue-50');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    
    if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        handleUpload();
    }
});

fileInput.addEventListener('change', handleUpload);

function handleUpload() {
    const files = fileInput.files;
    if (files.length === 0) return;
    
    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }
    
    const progressDiv = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const resultDiv = document.getElementById('uploadResult');
    
    progressDiv.classList.remove('hidden');
    resultDiv.innerHTML = '';
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const percent = (e.loaded / e.total) * 100;
            progressBar.style.width = percent + '%';
            progressText.textContent = `Uploading... ${Math.round(percent)}%`;
        }
    });
    
    xhr.addEventListener('load', () => {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                resultDiv.innerHTML = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">Images uploaded successfully!</div>';
                progressDiv.classList.add('hidden');
                setTimeout(() => location.reload(), 1500);
            } else {
                resultDiv.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">' + response.message + '</div>';
                progressDiv.classList.add('hidden');
            }
        }
    });
    
    xhr.addEventListener('error', () => {
        resultDiv.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Upload failed. Please try again.</div>';
        progressDiv.classList.add('hidden');
    });
    
    xhr.open('POST', '<?= url('admin/upload.php') ?>');
    xhr.send(formData);
}

// Bulk actions
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.image-checkbox:checked');
    const bulkForm = document.getElementById('bulkForm');
    const selectedCount = document.getElementById('selectedCount');
    
    selectedCount.textContent = checkboxes.length;
    
    if (checkboxes.length > 0) {
        bulkForm.classList.remove('hidden');
    } else {
        bulkForm.classList.add('hidden');
    }
}

function toggleAllImages(checkbox) {
    document.querySelectorAll('.image-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

function clearSelection() {
    document.querySelectorAll('.image-checkbox').forEach(cb => {
        cb.checked = false;
    });
    updateBulkActions();
}

// Image modal
const imageData = <?= json_encode($images) ?>;

function showImageModal(filename) {
    const image = imageData.find(img => img.filename === filename);
    if (!image) return;
    
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalTitle');
    const modalDetails = document.getElementById('modalDetails');
    const modalUsage = document.getElementById('modalUsage');
    
    modalImage.src = image.url;
    modalTitle.textContent = image.filename;
    
    modalDetails.innerHTML = `
        <div>
            <h4 class="font-semibold mb-2">File Information</h4>
            <div class="space-y-1 text-sm">
                <div><strong>Filename:</strong> ${image.filename}</div>
                <div><strong>Size:</strong> ${(image.size / 1024).toFixed(2)} KB</div>
                <div><strong>Type:</strong> ${image.extension.toUpperCase()}</div>
                ${image.dimensions ? `<div><strong>Dimensions:</strong> ${image.dimensions}</div>` : ''}
                <div><strong>Uploaded:</strong> ${new Date(image.date * 1000).toLocaleString()}</div>
            </div>
        </div>
        <div>
            <h4 class="font-semibold mb-2">Quick Actions</h4>
            <div class="space-y-2">
                <button onclick="copyImageUrl('${image.filename}'); closeImageModal();" class="w-full btn-secondary">
                    <i class="fas fa-copy mr-2"></i> Copy URL
                </button>
                <button onclick="copyImageFilename('${image.filename}'); closeImageModal();" class="w-full btn-secondary">
                    <i class="fas fa-file mr-2"></i> Copy Filename
                </button>
                <button onclick="deleteImage('${image.filename}'); closeImageModal();" class="w-full btn-secondary bg-red-600 hover:bg-red-700 text-white">
                    <i class="fas fa-trash mr-2"></i> Delete Image
                </button>
            </div>
        </div>
    `;
    
    if (image.usage_count > 0) {
        let usageHtml = '<h4 class="font-semibold mb-2">Used in Products:</h4><ul class="list-disc list-inside space-y-1 text-sm">';
        image.usage.forEach(usage => {
            usageHtml += `<li><a href="<?= url('admin/product-edit.php?id=') ?>${usage.id}" target="_blank" class="text-blue-600 hover:underline">${usage.name}</a> ${usage.type === 'gallery' ? '(Gallery)' : ''}</li>`;
        });
        usageHtml += '</ul>';
        modalUsage.innerHTML = usageHtml;
    } else {
        modalUsage.innerHTML = '<p class="text-gray-500 text-sm">This image is not used in any products.</p>';
    }
    
    modal.classList.remove('hidden');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
}

// Copy functions
function copyImageUrl(filename) {
    const url = '<?= asset('storage/uploads/') ?>' + filename;
    navigator.clipboard.writeText(url).then(() => {
        alert('Image URL copied to clipboard!');
    });
}

function copyImageFilename(filename) {
    navigator.clipboard.writeText(filename).then(() => {
        alert('Filename copied to clipboard!');
    });
}

function deleteImage(filename) {
    if (confirm('Are you sure you want to delete this image?')) {
        window.location.href = '?delete=' + encodeURIComponent(filename);
    }
}

// Close modal on ESC key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeImageModal();
        closeOptimizeModal();
        closeConvertModal();
    }
});

// Optimization Modal
function showOptimizeModal() {
    const checkboxes = document.querySelectorAll('.image-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one image to optimize.');
        return;
    }
    
    document.getElementById('optimizeModal').classList.remove('hidden');
}

function closeOptimizeModal() {
    document.getElementById('optimizeModal').classList.add('hidden');
}

// Convert Modal
function showConvertModal() {
    const checkboxes = document.querySelectorAll('.image-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one image to convert.');
        return;
    }
    
    document.getElementById('convertModal').classList.remove('hidden');
}

function closeConvertModal() {
    document.getElementById('convertModal').classList.add('hidden');
}
</script>

<!-- Optimization Modal -->
<div id="optimizeModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4" onclick="closeOptimizeModal()">
    <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-auto" onclick="event.stopPropagation()">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-xl font-bold">Optimize Images</h3>
            <button onclick="closeOptimizeModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="bulk_optimize" value="1">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Quality (1-100)</label>
                    <input type="range" name="quality" min="50" max="100" value="85" class="w-full" oninput="document.getElementById('qualityValue').textContent = this.value">
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>Lower (Smaller)</span>
                        <span id="qualityValue" class="font-semibold">85</span>
                        <span>Higher (Better)</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Max Width (px)</label>
                        <input type="number" name="max_width" placeholder="Optional" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Max Height (px)</label>
                        <input type="number" name="max_height" placeholder="Optional" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Note:</strong> Original images will be backed up automatically. Optimization will reduce file size while maintaining visual quality.
                    </p>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-blue-700 transition-all">
                        <i class="fas fa-compress mr-2"></i> Optimize Selected
                    </button>
                    <button type="button" onclick="closeOptimizeModal()" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Convert Modal -->
<div id="convertModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4" onclick="closeConvertModal()">
    <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-auto" onclick="event.stopPropagation()">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-xl font-bold">Convert Image Format</h3>
            <button onclick="closeConvertModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="bulk_convert" value="1">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Target Format</label>
                    <select name="target_format" class="w-full px-3 py-2 border rounded-lg">
                        <option value="webp">WebP (Best compression)</option>
                        <option value="jpg">JPG (Universal)</option>
                        <option value="png">PNG (Transparency)</option>
                        <option value="gif">GIF (Animation)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Quality (1-100)</label>
                    <input type="range" name="convert_quality" min="50" max="100" value="85" class="w-full" oninput="document.getElementById('convertQualityValue').textContent = this.value">
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>Lower</span>
                        <span id="convertQualityValue" class="font-semibold">85</span>
                        <span>Higher</span>
                    </div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Warning:</strong> Original images will be deleted after successful conversion. Make sure you have backups if needed.
                    </p>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-green-600 hover:to-green-700 transition-all">
                        <i class="fas fa-exchange-alt mr-2"></i> Convert Selected
                    </button>
                    <button type="button" onclick="closeConvertModal()" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.image-card {
    transition: all 0.3s ease;
}
.image-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
