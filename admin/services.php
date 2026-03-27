<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Service;

// Check if table exists, create if not
try {
    db()->fetchOne("SELECT 1 FROM services LIMIT 1");
} catch (Exception $e) {
    // Table doesn't exist, try to create it
    $sql = file_get_contents(__DIR__ . '/../database/services.sql');
    try {
        db()->execute($sql);
    } catch (Exception $ex) {
        $error = 'Please run the SQL file: database/services.sql';
    }
}

$serviceModel = new Service();
$message = '';
$error = '';

// Handle delete
if (!empty($_GET['delete'])) {
    try {
        $serviceId = (int)$_GET['delete'];
        if ($serviceId <= 0) {
            $error = 'Invalid service ID.';
        } else {
            $service = $serviceModel->getById($serviceId);
            if ($service && !empty($service['image'])) {
                $imagePath = __DIR__ . '/../' . $service['image'];
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            $serviceModel->delete($serviceId);
            $message = 'Service deleted successfully.';
        }
    } catch (\Exception $e) {
        $error = 'Error deleting service: ' . $e->getMessage();
    }
}

// Handle toggle active
if (!empty($_GET['toggle'])) {
    try {
        $serviceId = (int)$_GET['toggle'];
        if ($serviceId > 0) {
            $service = $serviceModel->getById($serviceId);
            if ($service) {
                $serviceModel->update($serviceId, ['is_active' => $service['is_active'] ? 0 : 1]);
                $message = 'Service status updated.';
            }
        }
    } catch (\Exception $e) {
        $error = 'Error updating service: ' . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['service_id'])) {
        // Update existing service
        $serviceId = (int)$_POST['service_id'];
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'icon' => trim($_POST['icon'] ?? ''),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? '')
        ];

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../storage/uploads/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    $error = 'Failed to create upload directory. Please check permissions.';
                }
            }
            
            if (empty($error) && !is_writable($uploadDir)) {
                @chmod($uploadDir, 0755);
                if (!is_writable($uploadDir)) {
                    $error = 'Upload directory is not writable. Please check permissions.';
                }
            }
            
            if (empty($error)) {
                $file = $_FILES['image'];
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                $maxSize = 5 * 1024 * 1024; // 5MB

                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($extension, $allowedExtensions)) {
                    $error = 'Invalid file type. Please upload JPG, PNG, GIF, WebP, or SVG.';
                } elseif (!in_array($file['type'], $allowedTypes)) {
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);
                        if (!in_array($mimeType, $allowedTypes)) {
                            $error = 'Invalid file type. Please upload JPG, PNG, GIF, WebP, or SVG.';
                        }
                    }
                } elseif ($file['size'] > $maxSize) {
                    $error = 'File size exceeds 5MB limit.';
                } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive.',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
                    ];
                    $error = 'Upload error: ' . ($uploadErrors[$file['error']] ?? 'Unknown error');
                } elseif ($extension !== 'svg' && !@getimagesize($file['tmp_name'])) {
                    $error = 'File is not a valid image.';
                } else {
                    // Delete old image
                    $oldService = $serviceModel->getById($serviceId);
                    if ($oldService && !empty($oldService['image']) && file_exists(__DIR__ . '/../' . $oldService['image'])) {
                        @unlink(__DIR__ . '/../' . $oldService['image']);
                    }

                    $filename = 'service_' . time() . '_' . uniqid() . '.' . $extension;
                    $filepath = $uploadDir . $filename;

                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        if (file_exists($filepath) && filesize($filepath) > 0) {
                            $data['image'] = 'storage/uploads/' . $filename;
                        } else {
                            $error = 'File upload failed. File may be corrupted.';
                            @unlink($filepath);
                        }
                    } else {
                        $error = 'Failed to upload image. Please check directory permissions.';
                    }
                }
            }
        }

        if (empty($error)) {
            try {
                $serviceModel->update($serviceId, $data);
                $message = 'Service updated successfully.';
            } catch (\Exception $e) {
                $error = 'Error updating service: ' . $e->getMessage();
            }
        }
    } else {
        // Create new service
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'icon' => trim($_POST['icon'] ?? ''),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? '')
        ];

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../storage/uploads/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    $error = 'Failed to create upload directory. Please check permissions.';
                }
            }
            
            if (empty($error) && !is_writable($uploadDir)) {
                @chmod($uploadDir, 0755);
                if (!is_writable($uploadDir)) {
                    $error = 'Upload directory is not writable. Please check permissions.';
                }
            }
            
            if (empty($error)) {
                $file = $_FILES['image'];
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                $maxSize = 5 * 1024 * 1024; // 5MB

                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($extension, $allowedExtensions)) {
                    $error = 'Invalid file type. Please upload JPG, PNG, GIF, WebP, or SVG.';
                } elseif (!in_array($file['type'], $allowedTypes)) {
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);
                        if (!in_array($mimeType, $allowedTypes)) {
                            $error = 'Invalid file type. Please upload JPG, PNG, GIF, WebP, or SVG.';
                        }
                    }
                } elseif ($file['size'] > $maxSize) {
                    $error = 'File size exceeds 5MB limit.';
                } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive.',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
                    ];
                    $error = 'Upload error: ' . ($uploadErrors[$file['error']] ?? 'Unknown error');
                } elseif ($extension !== 'svg' && !@getimagesize($file['tmp_name'])) {
                    $error = 'File is not a valid image.';
                } else {
                    $filename = 'service_' . time() . '_' . uniqid() . '.' . $extension;
                    $filepath = $uploadDir . $filename;

                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        if (file_exists($filepath) && filesize($filepath) > 0) {
                            $data['image'] = 'storage/uploads/' . $filename;
                        } else {
                            $error = 'File upload failed. File may be corrupted.';
                            @unlink($filepath);
                        }
                    } else {
                        $error = 'Failed to upload image. Please check directory permissions.';
                    }
                }
            }
        }

        if (empty($error)) {
            try {
                $serviceModel->create($data);
                $message = 'Service added successfully.';
            } catch (\Exception $e) {
                $error = 'Error adding service: ' . $e->getMessage();
            }
        }
    }
}

// Get all services
$services = $serviceModel->getAll();
$editingService = null;
if (!empty($_GET['edit'])) {
    $editingService = $serviceModel->getById((int)$_GET['edit']);
}

$pageTitle = 'Services';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-700 to-gray-900 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-concierge-bell mr-2 md:mr-3"></i>
                    Services
                </h1>
                <p class="text-gray-300 text-sm md:text-lg">Manage services offered by your company</p>
            </div>
            <div class="flex gap-2">
                <a href="<?= url('admin/services-add-demo.php') ?>" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-database mr-2"></i>Add Demo Data
                </a>
                <a href="<?= url('admin/services.php') ?>" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New
                </a>
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

    <!-- Add/Edit Form -->
    <?php if ($editingService || empty($services) || (isset($_GET['add']) && $_GET['add'] == '1')): ?>
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 lg:p-8 mb-6">
        <h2 class="text-xl font-bold mb-4">
            <?= $editingService ? 'Edit Service' : 'Add New Service' ?>
        </h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="service_id" value="<?= $editingService ? $editingService['id'] : '' ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-heading text-gray-400 mr-2"></i> Title *
                    </label>
                    <input type="text" name="title" value="<?= escape($editingService['title'] ?? '') ?>" required
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-link text-gray-400 mr-2"></i> Slug
                    </label>
                    <input type="text" name="slug" value="<?= escape($editingService['slug'] ?? '') ?>"
                           placeholder="auto-generated-from-title"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all">
                    <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate from title</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-icons text-gray-400 mr-2"></i> Icon (Font Awesome class)
                    </label>
                    <input type="text" name="icon" value="<?= escape($editingService['icon'] ?? '') ?>"
                           placeholder="fas fa-tools"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all">
                    <p class="text-xs text-gray-500 mt-1">e.g., fas fa-tools, fas fa-wrench</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-align-left text-gray-400 mr-2"></i> Short Description
                    </label>
                    <textarea name="description" rows="2" 
                              placeholder="Brief description of the service"
                              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all"><?= escape($editingService['description'] ?? '') ?></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-file-alt text-gray-400 mr-2"></i> Full Content
                    </label>
                    <textarea name="content" rows="6" 
                              placeholder="Detailed description of the service"
                              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all"><?= escape($editingService['content'] ?? '') ?></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-image text-gray-400 mr-2"></i> Service Image
                    </label>
                    <?php if ($editingService && !empty($editingService['image'])): ?>
                    <div class="mb-2">
                        <img src="<?= escape(image_url($editingService['image'])) ?>" alt="Current Image" class="h-32 w-auto object-contain border-2 border-gray-200 rounded p-2">
                        <p class="text-xs text-gray-500 mt-1">Current image. Upload a new one to replace it.</p>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/svg+xml"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all">
                    <p class="text-xs text-gray-500 mt-1">Recommended: PNG or JPG. Max size: 5MB.</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-sort-numeric-down text-gray-400 mr-2"></i> Sort Order
                    </label>
                    <input type="number" name="sort_order" value="<?= escape($editingService['sort_order'] ?? 0) ?>"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all">
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" <?= ($editingService['is_active'] ?? 1) ? 'checked' : '' ?>
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Active (show on services page)</span>
                    </label>
                </div>

                <div class="md:col-span-2 border-t border-gray-200 pt-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">SEO Settings (Optional)</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-tag text-gray-400 mr-2"></i> Meta Title
                            </label>
                            <input type="text" name="meta_title" value="<?= escape($editingService['meta_title'] ?? '') ?>"
                                   placeholder="SEO title for search engines"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-align-left text-gray-400 mr-2"></i> Meta Description
                            </label>
                            <textarea name="meta_description" rows="2" 
                                      placeholder="SEO description for search engines"
                                      class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all"><?= escape($editingService['meta_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="bg-gradient-to-r from-gray-700 to-gray-900 text-white px-8 py-3 rounded-lg font-bold text-lg hover:from-gray-800 hover:to-gray-950 transition-all duration-300 shadow-lg hover:shadow-xl">
                    <i class="fas fa-save mr-2"></i>
                    <?= $editingService ? 'Update Service' : 'Add Service' ?>
                </button>
                <a href="<?= url('admin/services.php') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-8 py-3 rounded-lg font-bold text-lg transition-all duration-300">
                    Cancel
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Services List -->
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 lg:p-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold">All Services</h2>
            <div class="flex gap-2">
                <a href="<?= url('admin/services-add-demo.php') ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-database mr-2"></i>Add Demo
                </a>
                <a href="<?= url('admin/services.php?add=1') ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New
                </a>
            </div>
        </div>

        <?php if (empty($services)): ?>
        <div class="text-center py-12">
            <i class="fas fa-concierge-bell text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">No services yet.</p>
            <a href="<?= url('admin/services.php?add=1') ?>" class="inline-block mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Add First Service
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Image</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Title</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Icon</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Order</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <?php if (!empty($service['image'])): ?>
                            <img src="<?= escape(image_url($service['image'])) ?>" alt="<?= escape($service['title']) ?>" class="h-12 w-auto object-contain">
                            <?php elseif (!empty($service['icon'])): ?>
                            <i class="<?= escape($service['icon']) ?> text-2xl text-gray-400"></i>
                            <?php else: ?>
                            <span class="text-gray-400">No image</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-medium"><?= escape($service['title']) ?></div>
                            <?php if (!empty($service['description'])): ?>
                            <div class="text-xs text-gray-500 mt-1"><?= escape(substr($service['description'], 0, 60)) ?><?= strlen($service['description']) > 60 ? '...' : '' ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <?php if (!empty($service['icon'])): ?>
                            <i class="<?= escape($service['icon']) ?> text-xl text-gray-600"></i>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4"><?= escape($service['sort_order']) ?></td>
                        <td class="py-3 px-4">
                            <a href="?toggle=<?= $service['id'] ?>" class="px-2 py-1 rounded text-xs <?= $service['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $service['is_active'] ? 'Active' : 'Inactive' ?>
                            </a>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <a href="?edit=<?= $service['id'] ?>" class="text-blue-600 hover:text-blue-800" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?= $service['id'] ?>" class="text-red-600 hover:text-red-800" title="Delete" onclick="return confirm('Are you sure you want to delete this service?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
