<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\QualityCertification;

// Check if table exists, create if not
try {
    db()->fetchOne("SELECT 1 FROM quality_certifications LIMIT 1");
} catch (Exception $e) {
    // Table doesn't exist, try to create it
    $sql = file_get_contents(__DIR__ . '/../database/quality-certifications.sql');
    try {
        db()->execute($sql);
    } catch (Exception $ex) {
        $error = 'Please run the SQL file: database/quality-certifications.sql';
    }
}

$certModel = new QualityCertification();
$message = '';
$error = '';

// Handle delete
if (!empty($_GET['delete'])) {
    try {
        $certId = (int)$_GET['delete'];
        if ($certId <= 0) {
            $error = 'Invalid certification ID.';
        } else {
            $cert = $certModel->getById($certId);
            if ($cert && !empty($cert['logo'])) {
                $logoPath = __DIR__ . '/../' . $cert['logo'];
                if (file_exists($logoPath)) {
                    @unlink($logoPath);
                }
            }
            $certModel->delete($certId);
            $message = 'Certification deleted successfully.';
        }
    } catch (\Exception $e) {
        $error = 'Error deleting certification: ' . $e->getMessage();
    }
}

// Handle toggle active
if (!empty($_GET['toggle'])) {
    try {
        $certId = (int)$_GET['toggle'];
        if ($certId > 0) {
            $cert = $certModel->getById($certId);
            if ($cert) {
                $certModel->update($certId, ['is_active' => $cert['is_active'] ? 0 : 1]);
                $message = 'Certification status updated.';
            }
        }
    } catch (\Exception $e) {
        $error = 'Error updating certification: ' . $e->getMessage();
    }
}

// Handle styling settings form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_styling'])) {
    require_csrf();
    
    // Handle styling settings
    foreach ($_POST as $key => $value) {
        if ($key !== 'save_styling' && $key !== 'csrf_token' && strpos($key, 'certs_') === 0) {
            $existing = db()->fetchOne("SELECT id FROM settings WHERE `key` = :key", ['key' => $key]);
            
            if ($existing) {
                db()->update('settings', ['value' => trim($value)], '`key` = :key', ['key' => $key]);
            } else {
                db()->insert('settings', [
                    'key' => $key,
                    'value' => trim($value),
                    'type' => 'text'
                ]);
            }
        }
    }
    
    $message = 'Quality Certifications styling settings updated successfully.';
    header('Location: ' . url('admin/quality-certifications.php') . '?styling_saved=1');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['save_styling'])) {
    if (!empty($_POST['cert_id'])) {
        // Update existing certification
        $certId = (int)$_POST['cert_id'];
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'reference_url' => trim($_POST['reference_url'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Handle logo upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../storage/uploads/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    $error = 'Failed to create upload directory. Please check permissions.';
                }
            }
            
            // Ensure directory is writable
            if (empty($error) && !is_writable($uploadDir)) {
                @chmod($uploadDir, 0755);
                if (!is_writable($uploadDir)) {
                    $error = 'Upload directory is not writable. Please check permissions.';
                }
            }
            
            if (empty($error)) {
                $file = $_FILES['logo'];
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                $maxSize = 2 * 1024 * 1024; // 2MB

                // Get file extension
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // Validate extension
                if (!in_array($extension, $allowedExtensions)) {
                    $error = 'Invalid file type. Please upload JPG, PNG, GIF, WebP, or SVG.';
                } 
                // Validate MIME type
                elseif (!in_array($file['type'], $allowedTypes)) {
                    // Double-check with file info for better security
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);
                        
                        if (!in_array($mimeType, $allowedTypes)) {
                            $error = 'Invalid file type. Please upload JPG, PNG, GIF, WebP, or SVG.';
                        }
                    } else {
                        // If finfo is not available, just check extension (less secure but works)
                        $error = 'Invalid file type. Please upload JPG, PNG, GIF, WebP, or SVG.';
                    }
                }
                // Validate file size
                elseif ($file['size'] > $maxSize) {
                    $error = 'File size exceeds 2MB limit.';
                }
                // Check for upload errors
                elseif ($file['error'] !== UPLOAD_ERR_OK) {
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
                }
                // Validate file is actually an image (for non-SVG)
                elseif ($extension !== 'svg' && !@getimagesize($file['tmp_name'])) {
                    $error = 'File is not a valid image.';
                }
                else {
                    // Delete old logo first (only for updates)
                    if (!empty($certId)) {
                        $oldCert = $certModel->getById($certId);
                        if ($oldCert && !empty($oldCert['logo'])) {
                            $oldLogoPath = __DIR__ . '/../' . $oldCert['logo'];
                            if (file_exists($oldLogoPath)) {
                                @unlink($oldLogoPath);
                            }
                        }
                    }

                    // Generate unique filename
                    $filename = 'cert_' . time() . '_' . uniqid() . '.' . $extension;
                    $filepath = $uploadDir . $filename;

                    // Resize and save image (max 800x800 for logos)
                    if (resize_and_save_image($file['tmp_name'], $filepath, 800, 800, 85)) {
                        // Verify file was actually saved
                        if (file_exists($filepath) && filesize($filepath) > 0) {
                            $data['logo'] = 'storage/uploads/' . $filename;
                        } else {
                            $error = 'File upload failed. File may be corrupted.';
                            @unlink($filepath); // Clean up
                        }
                    } else {
                        $error = 'Failed to process logo. Please check directory permissions and ensure GD extension is enabled.';
                    }
                }
            }
        }

        if (empty($error)) {
            try {
                $certModel->update($certId, $data);
                $message = 'Certification updated successfully.';
            } catch (\Exception $e) {
                $error = 'Error updating certification: ' . $e->getMessage();
            }
        }
    } else {
        // Create new certification
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'reference_url' => trim($_POST['reference_url'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Handle logo upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../storage/uploads/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    $error = 'Failed to create upload directory. Please check permissions.';
                }
            }
            
            // Ensure directory is writable
            if (empty($error) && !is_writable($uploadDir)) {
                @chmod($uploadDir, 0755);
                if (!is_writable($uploadDir)) {
                    $error = 'Upload directory is not writable. Please check permissions.';
                }
            }
            
            if (empty($error)) {
                $file = $_FILES['logo'];
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                $maxSize = 2 * 1024 * 1024; // 2MB

                // Get file extension
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // Validate extension
                if (!in_array($extension, $allowedExtensions)) {
                    $error = 'Invalid file type. Please upload JPG, PNG, GIF, WebP, or SVG.';
                } 
                // Validate MIME type
                elseif (!in_array($file['type'], $allowedTypes)) {
                    // Double-check with file info for better security
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);
                        
                        if (!in_array($mimeType, $allowedTypes)) {
                            $error = 'Invalid file type. Please upload JPG, PNG, GIF, WebP, or SVG.';
                        }
                    } else {
                        // If finfo is not available, just check extension (less secure but works)
                        $error = 'Invalid file type. Please upload JPG, PNG, GIF, WebP, or SVG.';
                    }
                }
                // Validate file size
                elseif ($file['size'] > $maxSize) {
                    $error = 'File size exceeds 2MB limit.';
                }
                // Check for upload errors
                elseif ($file['error'] !== UPLOAD_ERR_OK) {
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
                }
                // Validate file is actually an image (for non-SVG)
                elseif ($extension !== 'svg' && !@getimagesize($file['tmp_name'])) {
                    $error = 'File is not a valid image.';
                }
                else {
                    // Generate unique filename
                    $filename = 'cert_' . time() . '_' . uniqid() . '.' . $extension;
                    $filepath = $uploadDir . $filename;

                    // Ensure directory is writable
                    if (!is_writable($uploadDir)) {
                        $error = 'Upload directory is not writable. Please check permissions.';
                    }
                    // Resize and save image (max 800x800 for logos)
                    elseif (resize_and_save_image($file['tmp_name'], $filepath, 800, 800, 85)) {
                        // Verify file was actually saved
                        if (file_exists($filepath) && filesize($filepath) > 0) {
                            $data['logo'] = 'storage/uploads/' . $filename;
                        } else {
                            $error = 'File upload failed. File may be corrupted.';
                            @unlink($filepath); // Clean up
                        }
                    } else {
                        $error = 'Failed to process logo. Please check directory permissions and ensure GD extension is enabled.';
                    }
                }
            }
        } else {
            // No file uploaded - logo is required for new certifications only
            if (empty($certId)) {
                $error = 'Logo is required.';
            }
        }

        if (empty($error)) {
            try {
                $certModel->create($data);
                $message = 'Certification added successfully.';
            } catch (\Exception $e) {
                $error = 'Error adding certification: ' . $e->getMessage();
            }
        }
    }
}

// Get all certifications
$certifications = $certModel->getAll();
$editingCert = null;
if (!empty($_GET['edit'])) {
    $editingCert = $certModel->getById((int)$_GET['edit']);
}

// Get all settings for styling
$settingsData = db()->fetchAll("SELECT `key`, value FROM settings");
$settings = [];
foreach ($settingsData as $setting) {
    $settings[$setting['key']] = $setting['value'];
}

// Show success message for styling save
if (isset($_GET['styling_saved'])) {
    $message = 'Quality Certifications styling settings updated successfully.';
}

$pageTitle = 'Quality Certifications';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-700 to-gray-900 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-certificate mr-2 md:mr-3"></i>
                    Quality Certifications
                </h1>
                <p class="text-gray-300 text-sm md:text-lg">Manage quality certification logos (ISO, CE, RIML, etc.)</p>
            </div>
            <div class="flex gap-2">
                <a href="<?= url('admin/quality-certifications-add-demo.php') ?>" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-database mr-2"></i>Add Demo Data
                </a>
                <a href="<?= url('admin/quality-certifications.php') ?>" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg transition-colors">
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
    <?php if ($editingCert || empty($certifications) || (isset($_GET['add']) && $_GET['add'] == '1')): ?>
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 lg:p-8 mb-6">
        <h2 class="text-xl font-bold mb-4">
            <?= $editingCert ? 'Edit Certification' : 'Add New Certification' ?>
        </h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="cert_id" value="<?= $editingCert ? $editingCert['id'] : '' ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-certificate text-gray-400 mr-2"></i> Certification Name *
                    </label>
                    <input type="text" name="name" value="<?= escape($editingCert['name'] ?? '') ?>" required
                           placeholder="e.g., ISO 9001:2015"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-link text-gray-400 mr-2"></i> Reference URL
                    </label>
                    <input type="url" name="reference_url" value="<?= escape($editingCert['reference_url'] ?? '') ?>"
                           placeholder="https://example.com/certification"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all">
                    <p class="text-xs text-gray-500 mt-1">Link to certification details or official source</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-sort-numeric-down text-gray-400 mr-2"></i> Sort Order
                    </label>
                    <input type="number" name="sort_order" value="<?= escape($editingCert['sort_order'] ?? 0) ?>"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-align-left text-gray-400 mr-2"></i> Description
                    </label>
                    <textarea name="description" rows="2" 
                              placeholder="Brief description of the certification (optional)"
                              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all"><?= escape($editingCert['description'] ?? '') ?></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-image text-gray-400 mr-2"></i> Certification Logo <?= $editingCert ? '' : '*' ?>
                    </label>
                    <?php if ($editingCert && !empty($editingCert['logo'])): ?>
                    <div class="mb-2">
                        <img src="<?= escape(image_url($editingCert['logo'])) ?>" alt="Current Logo" class="h-20 w-auto object-contain border-2 border-gray-200 rounded p-2">
                        <p class="text-xs text-gray-500 mt-1">Current logo. Upload a new one to replace it.</p>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="logo" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/svg+xml" <?= $editingCert ? '' : 'required' ?>
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-all">
                    <p class="text-xs text-gray-500 mt-1">Recommended: PNG or SVG with transparent background. Max size: 2MB.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" <?= ($editingCert['is_active'] ?? 1) ? 'checked' : '' ?>
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Active (show in slider)</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="bg-gradient-to-r from-gray-700 to-gray-900 text-white px-8 py-3 rounded-lg font-bold text-lg hover:from-gray-800 hover:to-gray-950 transition-all duration-300 shadow-lg hover:shadow-xl">
                    <i class="fas fa-save mr-2"></i>
                    <?= $editingCert ? 'Update Certification' : 'Add Certification' ?>
                </button>
                <a href="<?= url('admin/quality-certifications.php') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-8 py-3 rounded-lg font-bold text-lg transition-all duration-300">
                    Cancel
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Certifications List -->
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 lg:p-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold">All Quality Certifications</h2>
            <div class="flex gap-2">
                <a href="<?= url('admin/quality-certifications-add-demo.php') ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-database mr-2"></i>Add Demo
                </a>
                <a href="<?= url('admin/quality-certifications.php?add=1') ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New
                </a>
            </div>
        </div>

        <?php if (empty($certifications)): ?>
        <div class="text-center py-12">
            <i class="fas fa-certificate text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">No certifications yet.</p>
            <a href="<?= url('admin/quality-certifications.php?add=1') ?>" class="inline-block mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Add First Certification
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Logo</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Name</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Description</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Reference URL</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Order</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certifications as $cert): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <?php if (!empty($cert['logo'])): ?>
                            <img src="<?= escape(image_url($cert['logo'])) ?>" alt="<?= escape($cert['name']) ?>" class="h-12 w-auto object-contain">
                            <?php else: ?>
                            <span class="text-gray-400">No logo</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 font-medium"><?= escape($cert['name']) ?></td>
                        <td class="py-3 px-4 text-sm text-gray-600"><?= escape($cert['description'] ?? '-') ?></td>
                        <td class="py-3 px-4">
                            <?php if (!empty($cert['reference_url'])): ?>
                            <a href="<?= escape($cert['reference_url']) ?>" target="_blank" class="text-blue-600 hover:underline">
                                <i class="fas fa-external-link-alt mr-1"></i>View
                            </a>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4"><?= escape($cert['sort_order']) ?></td>
                        <td class="py-3 px-4">
                            <a href="?toggle=<?= $cert['id'] ?>" class="px-2 py-1 rounded text-xs <?= $cert['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $cert['is_active'] ? 'Active' : 'Inactive' ?>
                            </a>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <a href="?edit=<?= $cert['id'] ?>" class="text-blue-600 hover:text-blue-800" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?= $cert['id'] ?>" class="text-red-600 hover:text-red-800" title="Delete" onclick="return confirm('Are you sure you want to delete this certification?')">
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

    <!-- Quality Certifications Styling Settings -->
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 lg:p-8 mt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-800 flex items-center">
                <i class="fas fa-palette text-gray-600 mr-2 text-xs"></i>
                Logo Slider Styling
            </h3>
            <a href="<?= url('index.php#quality-certifications') ?>" target="_blank" class="text-xs text-gray-600 hover:text-gray-800">
                <i class="fas fa-external-link-alt mr-1"></i>View Full Page
            </a>
        </div>
        
        <!-- Live Preview -->
        <div class="mb-4 p-4 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <div class="text-xs font-semibold text-gray-600 mb-2 flex items-center">
                <i class="fas fa-eye mr-2"></i>Live Preview
            </div>
            <div id="certs-preview-slider" class="quality-certifications-slider-preview" style="border-radius: 8px; overflow: hidden;">
                <div class="quality-certifications-slider-container-preview" style="max-width: 100%; padding: 0 15px;">
                    <div class="quality-certifications-slider-header-preview" style="text-align: center; margin-bottom: 20px;">
                        <h2 style="
                            font-size: 1.5rem;
                            font-weight: 700;
                            margin-bottom: 5px;
                        ">Quality Certifications</h2>
                        <p style="
                            font-size: 0.875rem;
                            font-weight: 500;
                        ">Certified quality standards and compliance</p>
                    </div>
                    <div class="quality-certifications-slider-wrapper-preview" style="overflow: hidden;">
                        <div class="quality-certifications-slider-track-preview" style="display: flex; flex-shrink: 0;">
                            <?php 
                            $previewCerts = array_slice($certifications, 0, 6);
                            if (empty($previewCerts)) {
                                for ($i = 0; $i < 12; $i++): ?>
                                <div class="quality-certifications-slider-item-preview" style="flex-shrink: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer;">
                                    <div style="width: 60%; height: 60%; background: #e5e7eb; border-radius: 4px; margin-bottom: 8px;"></div>
                                    <span class="cert-name-preview" style="font-size: 0.75rem; color: #6b7280;">Cert Name</span>
                                </div>
                                <?php endfor;
                            } else {
                                foreach (array_merge($previewCerts, $previewCerts) as $cert): ?>
                                <div class="quality-certifications-slider-item-preview" style="flex-shrink: 0; display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                                    <?php if (!empty($cert['logo'])): ?>
                                    <img src="<?= escape(image_url($cert['logo'])) ?>" alt="<?= escape($cert['name']) ?>" style="width: 100%; height: auto; object-fit: contain; margin-bottom: 8px;">
                                    <?php else: ?>
                                    <div style="width: 100%; height: 60%; background: #e5e7eb; border-radius: 4px; margin-bottom: 8px; display: flex; align-items: center; justify-content: center;">
                                        <span style="color: #9ca3af; font-size: 0.75rem;">No Logo</span>
                                    </div>
                                    <?php endif; ?>
                                    <span class="cert-name-preview" style="font-size: 0.75rem;"><?= escape($cert['name']) ?></span>
                                </div>
                                <?php endforeach;
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <form method="POST" id="certs-styling-form">
            <input type="hidden" name="save_styling" value="1">
            <?= csrf_field() ?>
            
            <div class="space-y-2">
                <!-- Accordion: Section & Header -->
                <div class="border border-gray-200 rounded">
                    <button type="button" onclick="toggleAccordion('certs-section')" class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 flex items-center justify-between">
                        <span><i class="fas fa-layer-group mr-2"></i>Section & Header</span>
                        <i class="fas fa-chevron-down text-xs transform transition-transform" id="certs-section-icon"></i>
                    </button>
                    <div id="certs-section" class="hidden p-3 bg-white border-t border-gray-200">
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">BG Color 1</label>
                                <input type="color" name="certs_section_bg_color1" value="<?= escape($settings['certs_section_bg_color1'] ?? '#ffffff') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">BG Color 2</label>
                                <input type="color" name="certs_section_bg_color2" value="<?= escape($settings['certs_section_bg_color2'] ?? '#f8f9fa') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Padding</label>
                                <input type="number" name="certs_section_padding" value="<?= escape($settings['certs_section_padding'] ?? '60') ?>" min="20" max="200" step="10" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Title Color</label>
                                <input type="color" name="certs_title_color" value="<?= escape($settings['certs_title_color'] ?? '#1a1a1a') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Desc Color</label>
                                <input type="color" name="certs_desc_color" value="<?= escape($settings['certs_desc_color'] ?? '#666666') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Accordion: Logo Item -->
                <div class="border border-gray-200 rounded">
                    <button type="button" onclick="toggleAccordion('certs-item')" class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 flex items-center justify-between">
                        <span><i class="fas fa-image mr-2"></i>Logo Item</span>
                        <i class="fas fa-chevron-down text-xs transform transition-transform" id="certs-item-icon"></i>
                    </button>
                    <div id="certs-item" class="hidden p-3 bg-white border-t border-gray-200">
                        <div class="grid grid-cols-4 gap-2">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Width</label>
                                <input type="number" name="certs_logo_item_width" value="<?= escape($settings['certs_logo_item_width'] ?? '160') ?>" min="100" max="400" step="10" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Height</label>
                                <input type="number" name="certs_logo_item_height" value="<?= escape($settings['certs_logo_item_height'] ?? '120') ?>" min="60" max="300" step="10" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Gap</label>
                                <input type="number" name="certs_logo_gap" value="<?= escape($settings['certs_logo_gap'] ?? '30') ?>" min="10" max="100" step="5" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Padding</label>
                                <input type="number" name="certs_logo_padding" value="<?= escape($settings['certs_logo_padding'] ?? '20') ?>" min="0" max="50" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Border W</label>
                                <input type="number" name="certs_logo_border_width" value="<?= escape($settings['certs_logo_border_width'] ?? '1') ?>" min="0" max="10" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Border Style</label>
                                <select name="certs_logo_border_style" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                                    <option value="solid" <?= ($settings['certs_logo_border_style'] ?? 'solid') === 'solid' ? 'selected' : '' ?>>Solid</option>
                                    <option value="dashed" <?= ($settings['certs_logo_border_style'] ?? '') === 'dashed' ? 'selected' : '' ?>>Dashed</option>
                                    <option value="dotted" <?= ($settings['certs_logo_border_style'] ?? '') === 'dotted' ? 'selected' : '' ?>>Dotted</option>
                                    <option value="none" <?= ($settings['certs_logo_border_style'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Border Color</label>
                                <input type="color" name="certs_logo_border_color" value="<?= escape($settings['certs_logo_border_color'] ?? '#e5e7eb') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Radius</label>
                                <input type="number" name="certs_logo_border_radius" value="<?= escape($settings['certs_logo_border_radius'] ?? '12') ?>" min="0" max="50" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">BG Color</label>
                                <input type="color" name="certs_logo_bg_color" value="<?= escape($settings['certs_logo_bg_color'] ?? '#ffffff') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Accordion: Shadow & Hover -->
                <div class="border border-gray-200 rounded">
                    <button type="button" onclick="toggleAccordion('certs-shadow')" class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 flex items-center justify-between">
                        <span><i class="fas fa-magic mr-2"></i>Shadow & Hover</span>
                        <i class="fas fa-chevron-down text-xs transform transition-transform" id="certs-shadow-icon"></i>
                    </button>
                    <div id="certs-shadow" class="hidden p-3 bg-white border-t border-gray-200">
                        <div class="grid grid-cols-4 gap-2 mb-2">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Shadow X</label>
                                <input type="number" name="certs_logo_shadow_x" value="<?= escape($settings['certs_logo_shadow_x'] ?? '0') ?>" min="-20" max="20" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Shadow Y</label>
                                <input type="number" name="certs_logo_shadow_y" value="<?= escape($settings['certs_logo_shadow_y'] ?? '2') ?>" min="-20" max="20" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Blur</label>
                                <input type="number" name="certs_logo_shadow_blur" value="<?= escape($settings['certs_logo_shadow_blur'] ?? '12') ?>" min="0" max="50" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Shadow Color</label>
                                <input type="color" name="certs_logo_shadow_color" value="<?= escape($settings['certs_logo_shadow_color'] ?? '#000000') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Opacity %</label>
                                <input type="number" name="certs_logo_shadow_opacity" value="<?= escape($settings['certs_logo_shadow_opacity'] ?? '8') ?>" min="0" max="100" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Y</label>
                                <input type="number" name="certs_logo_hover_y" value="<?= escape($settings['certs_logo_hover_y'] ?? '-8') ?>" min="-50" max="50" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Scale</label>
                                <input type="number" name="certs_logo_hover_scale" value="<?= escape($settings['certs_logo_hover_scale'] ?? '1.05') ?>" min="0.5" max="2" step="0.01" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Border</label>
                                <input type="color" name="certs_logo_hover_border_color" value="<?= escape($settings['certs_logo_hover_border_color'] ?? '#3b82f6') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Shadow Y</label>
                                <input type="number" name="certs_logo_hover_shadow_y" value="<?= escape($settings['certs_logo_hover_shadow_y'] ?? '8') ?>" min="-20" max="50" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Blur</label>
                                <input type="number" name="certs_logo_hover_shadow_blur" value="<?= escape($settings['certs_logo_hover_shadow_blur'] ?? '24') ?>" min="0" max="100" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Opacity</label>
                                <input type="number" name="certs_logo_hover_shadow_opacity" value="<?= escape($settings['certs_logo_hover_shadow_opacity'] ?? '15') ?>" min="0" max="100" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Transition</label>
                                <input type="number" name="certs_logo_transition" value="<?= escape($settings['certs_logo_transition'] ?? '300') ?>" min="0" max="2000" step="50" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Accordion: Image & Text Effects -->
                <div class="border border-gray-200 rounded">
                    <button type="button" onclick="toggleAccordion('certs-image')" class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 flex items-center justify-between">
                        <span><i class="fas fa-adjust mr-2"></i>Image & Text Effects</span>
                        <i class="fas fa-chevron-down text-xs transform transition-transform" id="certs-image-icon"></i>
                    </button>
                    <div id="certs-image" class="hidden p-3 bg-white border-t border-gray-200">
                        <div class="grid grid-cols-4 gap-2">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Object Fit</label>
                                <select name="certs_logo_object_fit" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                                    <option value="contain" <?= ($settings['certs_logo_object_fit'] ?? 'contain') === 'contain' ? 'selected' : '' ?>>Contain</option>
                                    <option value="cover" <?= ($settings['certs_logo_object_fit'] ?? '') === 'cover' ? 'selected' : '' ?>>Cover</option>
                                    <option value="fill" <?= ($settings['certs_logo_object_fit'] ?? '') === 'fill' ? 'selected' : '' ?>>Fill</option>
                                    <option value="scale-down" <?= ($settings['certs_logo_object_fit'] ?? '') === 'scale-down' ? 'selected' : '' ?>>Scale Down</option>
                                    <option value="none" <?= ($settings['certs_logo_object_fit'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Max Height</label>
                                <input type="number" name="certs_logo_max_image_height" value="<?= escape($settings['certs_logo_max_image_height'] ?? '80') ?>" min="40" max="200" step="5" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Scale</label>
                                <input type="number" name="certs_logo_hover_image_scale" value="<?= escape($settings['certs_logo_hover_image_scale'] ?? '1.1') ?>" min="0.5" max="2" step="0.01" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Text Color</label>
                                <input type="color" name="certs_text_color" value="<?= escape($settings['certs_text_color'] ?? '#6b7280') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Text Size</label>
                                <input type="number" name="certs_text_font_size" value="<?= escape($settings['certs_text_font_size'] ?? '12') ?>" min="8" max="24" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Text</label>
                                <input type="color" name="certs_text_hover_color" value="<?= escape($settings['certs_text_hover_color'] ?? '#3b82f6') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Accordion: Animation Settings -->
                <div class="border border-gray-200 rounded">
                    <button type="button" onclick="toggleAccordion('certs-animation')" class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 flex items-center justify-between">
                        <span><i class="fas fa-tachometer-alt mr-2"></i>Animation Settings</span>
                        <i class="fas fa-chevron-down text-xs transform transition-transform" id="certs-animation-icon"></i>
                    </button>
                    <div id="certs-animation" class="hidden p-3 bg-white border-t border-gray-200">
                        <div class="grid grid-cols-1 gap-2">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Slide Speed (seconds)</label>
                                <input type="number" name="certs_logo_slide_speed" value="<?= escape($settings['certs_logo_slide_speed'] ?? '25') ?>" min="5" max="120" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                                <p class="text-xs text-gray-500 mt-1">Time for one complete slide cycle. Lower = faster animation.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end mt-3 pt-3 border-t border-gray-200">
                <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                    <i class="fas fa-save mr-1"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
    
    <script>
    function toggleAccordion(id) {
        const content = document.getElementById(id);
        const icon = document.getElementById(id + '-icon');
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            content.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
    }
    
    // Live preview update function
    function hexToRgba(hex, opacity) {
        hex = hex.replace('#', '');
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        return `rgba(${r}, ${g}, ${b}, ${opacity / 100})`;
    }
    
    function updateCertsPreview() {
        const form = document.getElementById('certs-styling-form');
        if (!form) return;
        
        const preview = document.getElementById('certs-preview-slider');
        if (!preview) return;
        
        const previewHeader = preview.querySelector('.quality-certifications-slider-header-preview');
        const previewTitle = previewHeader?.querySelector('h2');
        const previewDesc = previewHeader?.querySelector('p');
        const previewTrack = preview.querySelector('.quality-certifications-slider-track-preview');
        const previewItems = preview.querySelectorAll('.quality-certifications-slider-item-preview');
        const previewTexts = preview.querySelectorAll('.cert-name-preview');
        
        // Get form values
        const bgColor1 = (form.querySelector('[name="certs_section_bg_color1"]')?.value) || '#ffffff';
        const bgColor2 = (form.querySelector('[name="certs_section_bg_color2"]')?.value) || '#f8f9fa';
        const padding = parseInt(form.querySelector('[name="certs_section_padding"]')?.value) || 60;
        const titleColor = (form.querySelector('[name="certs_title_color"]')?.value) || '#1a1a1a';
        const descColor = (form.querySelector('[name="certs_desc_color"]')?.value) || '#666666';
        const itemWidth = parseInt(form.querySelector('[name="certs_logo_item_width"]')?.value) || 160;
        const itemHeight = parseInt(form.querySelector('[name="certs_logo_item_height"]')?.value) || 120;
        const gap = parseInt(form.querySelector('[name="certs_logo_gap"]')?.value) || 30;
        const itemPadding = parseInt(form.querySelector('[name="certs_logo_padding"]')?.value) || 20;
        const borderWidth = parseInt(form.querySelector('[name="certs_logo_border_width"]')?.value) || 1;
        const borderStyle = (form.querySelector('[name="certs_logo_border_style"]')?.value) || 'solid';
        const borderColor = (form.querySelector('[name="certs_logo_border_color"]')?.value) || '#e5e7eb';
        const borderRadius = parseInt(form.querySelector('[name="certs_logo_border_radius"]')?.value) || 12;
        const bgColor = (form.querySelector('[name="certs_logo_bg_color"]')?.value) || '#ffffff';
        const shadowX = parseInt(form.querySelector('[name="certs_logo_shadow_x"]')?.value) || 0;
        const shadowY = parseInt(form.querySelector('[name="certs_logo_shadow_y"]')?.value) || 2;
        const shadowBlur = parseInt(form.querySelector('[name="certs_logo_shadow_blur"]')?.value) || 12;
        const shadowColor = (form.querySelector('[name="certs_logo_shadow_color"]')?.value) || '#000000';
        const shadowOpacity = parseInt(form.querySelector('[name="certs_logo_shadow_opacity"]')?.value) || 8;
        const objectFit = (form.querySelector('[name="certs_logo_object_fit"]')?.value) || 'contain';
        const maxHeight = parseInt(form.querySelector('[name="certs_logo_max_image_height"]')?.value) || 80;
        const hoverImageScale = parseFloat(form.querySelector('[name="certs_logo_hover_image_scale"]')?.value) || 1.1;
        const textColor = (form.querySelector('[name="certs_text_color"]')?.value) || '#6b7280';
        const textSize = parseInt(form.querySelector('[name="certs_text_font_size"]')?.value) || 12;
        const textHoverColor = (form.querySelector('[name="certs_text_hover_color"]')?.value) || '#3b82f6';
        const hoverY = parseInt(form.querySelector('[name="certs_logo_hover_y"]')?.value) || -8;
        const hoverScale = parseFloat(form.querySelector('[name="certs_logo_hover_scale"]')?.value) || 1.05;
        const hoverBorderColor = (form.querySelector('[name="certs_logo_hover_border_color"]')?.value) || '#3b82f6';
        const hoverShadowY = parseInt(form.querySelector('[name="certs_logo_hover_shadow_y"]')?.value) || 8;
        const hoverShadowBlur = parseInt(form.querySelector('[name="certs_logo_hover_shadow_blur"]')?.value) || 24;
        const hoverShadowOpacity = parseInt(form.querySelector('[name="certs_logo_hover_shadow_opacity"]')?.value) || 15;
        const transition = parseInt(form.querySelector('[name="certs_logo_transition"]')?.value) || 300;
        const slideSpeed = parseInt(form.querySelector('[name="certs_logo_slide_speed"]')?.value) || 25;
        
        // Update preview section background
        preview.style.background = `linear-gradient(to bottom, ${bgColor1}, ${bgColor2})`;
        preview.style.padding = `${Math.max(15, padding / 4)}px 0`;
        
        // Update header
        if (previewTitle) {
            previewTitle.style.color = titleColor;
        }
        if (previewDesc) {
            previewDesc.style.color = descColor;
        }
        
        // Update track gap and animation speed
        if (previewTrack) {
            previewTrack.style.gap = `${gap}px`;
            previewTrack.style.animation = `slideCerts ${slideSpeed}s linear infinite`;
        }
        
        // Update items
        previewItems.forEach(item => {
            item.style.width = `${itemWidth}px`;
            item.style.minHeight = `${itemHeight}px`;
            item.style.padding = `${itemPadding}px`;
            item.style.border = `${borderWidth}px ${borderStyle} ${borderColor}`;
            item.style.borderRadius = `${borderRadius}px`;
            item.style.backgroundColor = bgColor;
            item.style.boxShadow = `${shadowX}px ${shadowY}px ${shadowBlur}px ${hexToRgba(shadowColor, shadowOpacity)}`;
            item.style.transition = `all ${transition}ms ease`;
            
            const img = item.querySelector('img');
            if (img) {
                img.style.objectFit = objectFit;
                img.style.maxHeight = `${maxHeight}px`;
                img.style.transition = `all ${transition}ms ease`;
            }
        });
        
        // Update text styles
        previewTexts.forEach(text => {
            text.style.color = textColor;
            text.style.fontSize = `${textSize}px`;
            text.style.transition = `color ${transition}ms ease`;
        });
        
        // Update hover styles
        const style = document.getElementById('certs-preview-hover-styles');
        if (style) {
            style.textContent = `
                .quality-certifications-slider-item-preview:hover {
                    transform: translateY(${hoverY}px) scale(${hoverScale}) !important;
                    border-color: ${hoverBorderColor} !important;
                    box-shadow: ${shadowX}px ${hoverShadowY}px ${hoverShadowBlur}px ${hexToRgba(shadowColor, hoverShadowOpacity)} !important;
                }
                .quality-certifications-slider-item-preview:hover img {
                    transform: scale(${hoverImageScale}) !important;
                }
                .quality-certifications-slider-item-preview:hover .cert-name-preview {
                    color: ${textHoverColor} !important;
                }
            `;
        }
    }
    
    // Attach event listeners
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('certs-styling-form');
        if (form) {
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('input', updateCertsPreview);
                input.addEventListener('change', updateCertsPreview);
            });
            updateCertsPreview();
        }
    });
    </script>
    
    <style>
    @keyframes slideCerts {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    .quality-certifications-slider-track-preview {
        animation: slideCerts <?= (int)($settings['certs_logo_slide_speed'] ?? 25) ?>s linear infinite;
    }
    .quality-certifications-slider-wrapper-preview:hover .quality-certifications-slider-track-preview {
        animation-play-state: paused;
    }
    </style>
    <style id="certs-preview-hover-styles"></style>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
