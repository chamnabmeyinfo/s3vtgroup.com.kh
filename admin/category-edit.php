<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Category;

$categoryModel = new Category();
$message = '';
$error = '';
$category = null;
$categoryId = $_GET['id'] ?? null;

if ($categoryId) {
    $category = $categoryModel->getById($categoryId);
    if (!$category) {
        header('Location: ' . url('admin/categories.php'));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle image upload
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../storage/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = $_FILES['category_image'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'Invalid file type. Please upload JPG, PNG, GIF, WebP, or SVG.';
        } elseif ($file['size'] > $maxSize) {
            $error = 'File size exceeds 5MB limit.';
        } else {
            // Delete old image if exists
            if ($categoryId && !empty($category['image']) && file_exists(__DIR__ . '/../' . $category['image'])) {
                @unlink(__DIR__ . '/../' . $category['image']);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'category_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $_POST['image'] = 'storage/uploads/' . $filename;
            } else {
                $error = 'Failed to upload image.';
            }
        }
    }
    
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'slug' => strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', $_POST['slug'] ?? ''), '-')),
        'description' => trim($_POST['description'] ?? ''),
        'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    
    // Check if short_description column exists before adding it
    try {
        db()->fetchOne("SELECT short_description FROM categories LIMIT 1");
        $data['short_description'] = trim($_POST['short_description'] ?? '');
    } catch (\Exception $e) {
        // Column doesn't exist yet - skip it
        // User needs to run database/category-homepage-update.sql
    }
    
    // Handle image (either uploaded or existing)
    if (!empty($_POST['image'])) {
        $data['image'] = trim($_POST['image']);
    } elseif ($categoryId && !empty($category['image'])) {
        // Keep existing image if no new upload and no new file was uploaded
        if (!isset($_FILES['category_image']) || $_FILES['category_image']['error'] !== UPLOAD_ERR_OK) {
            $data['image'] = $category['image'];
        }
    }
    
    if (empty($data['name'])) {
        $error = 'Category name is required.';
    } else {
        try {
            if ($categoryId) {
                // Prevent circular reference (basic check)
                if (!empty($data['parent_id']) && $data['parent_id'] == $categoryId) {
                    $error = 'Category cannot be its own parent.';
                } else {
                    // Check for session errors/warnings first
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    $updated = $categoryModel->update($categoryId, $data);
                    if ($updated) {
                        // Check if there was a validation warning
                        if (isset($_SESSION['category_update_warning'])) {
                            $warning = $_SESSION['category_update_warning'];
                            unset($_SESSION['category_update_warning']);
                            $message = 'Category updated successfully, but: ' . $warning;
                        } else {
                            $message = 'Category updated successfully.';
                        }
                        $category = $categoryModel->getById($categoryId);
                    } else {
                        // Check for stored error message
                        if (isset($_SESSION['category_update_error'])) {
                            $error = $_SESSION['category_update_error'];
                            unset($_SESSION['category_update_error']);
                        } else {
                            // Get more specific error from logs or provide detailed message
                            $error = 'Failed to update category. ';
                            
                            // Check for common issues
                            if (!empty($data['parent_id'])) {
                                $parentId = (int)$data['parent_id'];
                                $parent = $categoryModel->getById($parentId);
                                if (!$parent) {
                                    $error .= 'The selected parent category does not exist.';
                                } else {
                                    // Check for circular reference
                                    $descendants = $categoryModel->getDescendants($categoryId, false);
                                    if (in_array($parentId, $descendants)) {
                                        $error .= 'Cannot set parent: This would create a circular reference (the selected parent is a child of this category).';
                                    } else {
                                        $error .= 'Database update failed. Please check error logs for details.';
                                    }
                                }
                            } else {
                                $error .= 'Database update failed. Please check error logs for details.';
                            }
                        }
                    }
                }
            } else {
                $newId = $categoryModel->create($data);
                if ($newId) {
                    $message = 'Category created successfully.';
                    header('Location: ' . url('admin/categories.php'));
                    exit;
                } else {
                    $error = 'Failed to create category.';
                }
            }
        } catch (Exception $e) {
            $error = 'Error saving category: ' . $e->getMessage();
        }
    }
}

// Get categories for parent selection (exclude current category to prevent self-reference)
$allCategories = $categoryModel->getAll(false);
$categoriesForParent = $categoryId 
    ? $categoryModel->getFlatTree(null, false, 0, $categoryId)
    : $categoryModel->getFlatTree(null, false);

$pageTitle = $category ? 'Edit Category' : 'Add New Category';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-tags mr-2 md:mr-3"></i>
                    <?= $category ? 'Edit Category' : 'Add New Category' ?>
                </h1>
                <p class="text-gray-200 text-sm md:text-lg">Manage category information for homepage display</p>
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

    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-lg p-4 md:p-6 lg:p-8 space-y-6">
        <!-- Category Image Upload -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-2 border-blue-200">
            <label class="block text-sm font-semibold text-gray-700 mb-4">
                <i class="fas fa-image text-blue-600 mr-2"></i> Category Image
            </label>
            
            <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                <div class="flex-shrink-0">
                    <?php 
                    $imagePath = $category['image'] ?? null;
                    $imageUrl = $imagePath ? image_url($imagePath) : null;
                    ?>
                    <div class="w-32 h-32 bg-white rounded-lg border-2 border-gray-200 flex items-center justify-center overflow-hidden shadow-md">
                        <?php if ($imageUrl): ?>
                            <img src="<?= escape($imageUrl) ?>" alt="Category Image" class="max-w-full max-h-full object-cover" id="category-image-preview">
                        <?php else: ?>
                            <div class="text-center text-gray-400">
                                <i class="fas fa-image text-4xl mb-2"></i>
                                <p class="text-xs">No Image</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex-1">
                    <input type="file" 
                           name="category_image" 
                           id="category_image" 
                           accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/svg+xml"
                           onchange="previewCategoryImage(this)"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white">
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Recommended: Square image (400x400px or larger). Max size: 5MB. This image appears on the homepage "Shop by Category" section. If no image is uploaded, an automatic icon will be used based on the category name.
                    </p>
                    <?php if ($imagePath): ?>
                        <input type="hidden" name="image" value="<?= escape($imagePath) ?>">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Basic Information -->
        <div class="grid md:grid-cols-2 gap-6">
    <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-tag text-gray-400 mr-2"></i> Category Name *
                </label>
        <input type="text" name="name" required value="<?= escape($category['name'] ?? '') ?>"
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
    </div>
    
    <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-link text-gray-400 mr-2"></i> Slug
                </label>
        <input type="text" name="slug" value="<?= escape($category['slug'] ?? '') ?>"
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                       placeholder="auto-generated-from-name">
                <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate from name</p>
            </div>
    </div>
    
        <!-- Short Description for Homepage -->
        <?php
        // Check if short_description column exists
        $hasShortDescription = false;
        try {
            db()->fetchOne("SELECT short_description FROM categories LIMIT 1");
            $hasShortDescription = true;
        } catch (\Exception $e) {
            $hasShortDescription = false;
        }
        ?>
        
        <?php if ($hasShortDescription): ?>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-align-left text-gray-400 mr-2"></i> Short Description (for Homepage)
            </label>
            <textarea name="short_description" rows="2" maxlength="255"
                      class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                      placeholder="Brief description that appears on the homepage category card (max 255 characters)"><?= escape($category['short_description'] ?? '') ?></textarea>
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-info-circle mr-1"></i>
                This short text appears on the homepage "Shop by Category" section. Keep it concise (2-3 sentences max).
            </p>
        </div>
        <?php else: ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
                <div>
                    <p class="font-semibold text-yellow-800 mb-1">Short Description Feature Not Available</p>
                    <p class="text-yellow-700 text-sm">The <code class="bg-yellow-100 px-2 py-1 rounded">short_description</code> column doesn't exist yet. Please run <code class="bg-yellow-100 px-2 py-1 rounded">database/category-homepage-update.sql</code> to enable this feature.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Full Description -->
    <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-file-alt text-gray-400 mr-2"></i> Full Description
            </label>
            <textarea name="description" rows="4" 
                      class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                      placeholder="Full category description (optional)"><?= escape($category['description'] ?? '') ?></textarea>
            <p class="text-xs text-gray-500 mt-1">Full description for category pages (optional)</p>
    </div>
    
        <!-- Additional Settings -->
    <div class="grid md:grid-cols-2 gap-6">
        <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-sitemap text-gray-400 mr-2"></i> Parent Category
                </label>
                <select name="parent_id" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                <option value="">None (Top Level Category)</option>
                <?php foreach ($categoriesForParent as $cat): 
                    $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $cat['level'] ?? 0);
                    $prefix = ($cat['level'] ?? 0) > 0 ? '└─ ' : '';
                ?>
                    <option value="<?= $cat['id'] ?>" <?= ($category['parent_id'] ?? null) == $cat['id'] ? 'selected' : '' ?>>
                        <?= $indent . $prefix . escape($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-info-circle mr-1"></i>
                Select a parent category to create a sub-category. Leave empty for top-level category.
                <?php if ($categoryId && !empty($category['parent_id'])): 
                    $parent = $categoryModel->getById($category['parent_id']);
                    if ($parent):
                ?>
                    <br><span class="text-blue-600">Current Parent: <?= escape($parent['name']) ?></span>
                <?php endif; endif; ?>
            </p>
        </div>
        
        <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-sort-numeric-down text-gray-400 mr-2"></i> Sort Order
                </label>
            <input type="number" name="sort_order" value="<?= escape($category['sort_order'] ?? 0) ?>"
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                <p class="text-xs text-gray-500 mt-1">Lower numbers appear first</p>
        </div>
    </div>
    
        <div class="bg-gray-50 rounded-lg p-4">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" name="is_active" <?= ($category['is_active'] ?? 1) ? 'checked' : '' ?>
                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="ml-3 text-sm font-semibold text-gray-700">Active (Show on website)</span>
        </label>
    </div>
    
        <!-- Action Buttons -->
        <div class="flex gap-4 pt-4 border-t border-gray-200">
            <button type="submit" class="btn-primary btn-lg">
                <i class="fas fa-save"></i>
                <?= $category ? 'Update Category' : 'Create Category' ?>
            </button>
            <a href="<?= url('admin/categories.php') ?>" class="btn-secondary btn-lg">
                <i class="fas fa-times"></i>Cancel
            </a>
        </div>
</form>
</div>

<script>
function previewCategoryImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('category-image-preview');
            const container = preview ? preview.parentElement : input.closest('.bg-gradient-to-br').querySelector('.w-32');
            
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            } else {
                container.innerHTML = '<img src="' + e.target.result + '" alt="Category Image Preview" class="max-w-full max-h-full object-cover" id="category-image-preview">';
            }
            
            // Hide "No Image" text if exists
            const noImageDiv = container.querySelector('.text-center.text-gray-400');
            if (noImageDiv) {
                noImageDiv.style.display = 'none';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Auto-generate slug from name
document.querySelector('input[name="name"]')?.addEventListener('blur', function() {
    const slugInput = document.querySelector('input[name="slug"]');
    if (slugInput && !slugInput.value) {
        const slug = this.value.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        slugInput.value = slug;
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

