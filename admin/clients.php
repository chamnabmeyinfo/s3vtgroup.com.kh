<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Partner;

// Check if table exists, create if not
try {
    db()->fetchOne("SELECT 1 FROM partners LIMIT 1");
} catch (Exception $e) {
    // Table doesn't exist, try to create it
    $sql = file_get_contents(__DIR__ . '/../database/partners-clients.sql');
    try {
        db()->execute($sql);
    } catch (Exception $ex) {
        $error = 'Please run the SQL file: database/partners-clients.sql';
    }
}

$partnerModel = new Partner();
$message = '';
$error = '';

// Handle delete
if (!empty($_GET['delete'])) {
    try {
        $clientId = (int)$_GET['delete'];
        if ($clientId <= 0) {
            $error = 'Invalid client ID.';
        } else {
            $client = $partnerModel->getById($clientId);
            // Only allow deleting clients
            if ($client && $client['type'] === 'client') {
                if (!empty($client['logo'])) {
                    $logoPath = __DIR__ . '/../' . $client['logo'];
                    if (file_exists($logoPath)) {
                        @unlink($logoPath);
                    }
                }
                $partnerModel->delete($clientId);
                $message = 'Client deleted successfully.';
            } else {
                $error = 'Invalid client or not a client record.';
            }
        }
    } catch (\Exception $e) {
        $error = 'Error deleting client: ' . $e->getMessage();
    }
}

// Handle toggle active
if (!empty($_GET['toggle'])) {
    try {
        $clientId = (int)$_GET['toggle'];
        if ($clientId > 0) {
            $client = $partnerModel->getById($clientId);
            if ($client && $client['type'] === 'client') {
                $partnerModel->update($clientId, ['is_active' => $client['is_active'] ? 0 : 1]);
                $message = 'Client status updated.';
            }
        }
    } catch (\Exception $e) {
        $error = 'Error updating client: ' . $e->getMessage();
    }
}

// Handle styling settings form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_styling'])) {
    require_csrf();
    
    // Handle styling settings
    foreach ($_POST as $key => $value) {
        if ($key !== 'save_styling' && $key !== 'csrf_token' && strpos($key, 'clients_') === 0) {
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
    
    $message = 'Clients styling settings updated successfully.';
    header('Location: ' . url('admin/clients.php') . '?styling_saved=1');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['save_styling'])) {
    if (!empty($_POST['client_id'])) {
        // Update existing client
        $clientId = (int)$_POST['client_id'];
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'website_url' => trim($_POST['website_url'] ?? ''),
            'type' => 'client', // Always set to client
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
                    if (!empty($clientId)) {
                        $oldClient = $partnerModel->getById($clientId);
                        if ($oldClient && !empty($oldClient['logo'])) {
                            $oldLogoPath = __DIR__ . '/../' . $oldClient['logo'];
                            if (file_exists($oldLogoPath)) {
                                @unlink($oldLogoPath);
                            }
                        }
                    }

                    // Generate unique filename
                    $filename = 'client_' . time() . '_' . uniqid() . '.' . $extension;
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
                $partnerModel->update($clientId, $data);
                $message = 'Client updated successfully.';
            } catch (\Exception $e) {
                $error = 'Error updating client: ' . $e->getMessage();
            }
        }
    } else {
        // Create new client
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'website_url' => trim($_POST['website_url'] ?? ''),
            'type' => 'client', // Always set to client
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
                    $filename = 'client_' . time() . '_' . uniqid() . '.' . $extension;
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
            // No file uploaded - logo is required for new clients only
            if (empty($clientId)) {
                $error = 'Logo is required.';
            }
        }

        if (empty($error)) {
            try {
                $partnerModel->create($data);
                $message = 'Client added successfully.';
            } catch (\Exception $e) {
                $error = 'Error adding client: ' . $e->getMessage();
            }
        }
    }
}

// Get all clients (filter by type='client')
$clients = $partnerModel->getByType('client', false);
$editingClient = null;
if (!empty($_GET['edit'])) {
    $editingClient = $partnerModel->getById((int)$_GET['edit']);
    // Verify it's actually a client
    if ($editingClient && $editingClient['type'] !== 'client') {
        $editingClient = null;
        $error = 'Invalid client record.';
    }
}

// Get all settings for styling
$settingsData = db()->fetchAll("SELECT `key`, value FROM settings");
$settings = [];
foreach ($settingsData as $setting) {
    $settings[$setting['key']] = $setting['value'];
}

// Show success message for styling save
if (isset($_GET['styling_saved'])) {
    $message = 'Clients styling settings updated successfully.';
}

$pageTitle = 'Clients';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-700 to-emerald-900 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-building mr-2 md:mr-3"></i>
                    Clients
                </h1>
                <p class="text-gray-300 text-sm md:text-lg">Manage client logos and information</p>
            </div>
            <div class="flex gap-2">
                <a href="<?= url('admin/clients.php?add=1') ?>" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New Client
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
    <?php if ($editingClient || empty($clients) || (isset($_GET['add']) && $_GET['add'] == '1')): ?>
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 lg:p-8 mb-6">
        <h2 class="text-xl font-bold mb-4">
            <?= $editingClient ? 'Edit Client' : 'Add New Client' ?>
        </h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="client_id" value="<?= $editingClient ? $editingClient['id'] : '' ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-building text-gray-400 mr-2"></i> Name *
                    </label>
                    <input type="text" name="name" value="<?= escape($editingClient['name'] ?? '') ?>" required
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-link text-gray-400 mr-2"></i> Website URL
                    </label>
                    <input type="url" name="website_url" value="<?= escape($editingClient['website_url'] ?? '') ?>"
                           placeholder="https://example.com"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-sort-numeric-down text-gray-400 mr-2"></i> Sort Order
                    </label>
                    <input type="number" name="sort_order" value="<?= escape($editingClient['sort_order'] ?? 0) ?>"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-image text-gray-400 mr-2"></i> Logo <?= $editingClient ? '' : '*' ?>
                    </label>
                    <?php if ($editingClient && !empty($editingClient['logo'])): ?>
                    <div class="mb-2">
                        <img src="<?= escape(image_url($editingClient['logo'])) ?>" alt="Current Logo" class="h-20 w-auto object-contain border-2 border-gray-200 rounded p-2">
                        <p class="text-xs text-gray-500 mt-1">Current logo. Upload a new one to replace it.</p>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="logo" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/svg+xml" <?= $editingClient ? '' : 'required' ?>
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    <p class="text-xs text-gray-500 mt-1">Recommended: PNG or SVG with transparent background. Max size: 2MB.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" <?= ($editingClient['is_active'] ?? 1) ? 'checked' : '' ?>
                               class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700">Active (show in slider)</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="bg-gradient-to-r from-green-700 to-emerald-900 text-white px-8 py-3 rounded-lg font-bold text-lg hover:from-green-800 hover:to-emerald-950 transition-all duration-300 shadow-lg hover:shadow-xl">
                    <i class="fas fa-save mr-2"></i>
                    <?= $editingClient ? 'Update Client' : 'Add Client' ?>
                </button>
                <a href="<?= url('admin/clients.php') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-8 py-3 rounded-lg font-bold text-lg transition-all duration-300">
                    Cancel
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Clients List -->
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 lg:p-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold">All Clients</h2>
            <div class="flex gap-2">
                <a href="<?= url('admin/clients.php?add=1') ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New
                </a>
            </div>
        </div>

        <?php if (empty($clients)): ?>
        <div class="text-center py-12">
            <i class="fas fa-building text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">No clients yet.</p>
            <a href="<?= url('admin/clients.php?add=1') ?>" class="inline-block mt-4 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Add First Client
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Logo</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Name</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Website</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Order</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <?php if (!empty($client['logo'])): ?>
                            <img src="<?= escape(image_url($client['logo'])) ?>" alt="<?= escape($client['name']) ?>" class="h-12 w-auto object-contain">
                            <?php else: ?>
                            <span class="text-gray-400">No logo</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 font-medium"><?= escape($client['name']) ?></td>
                        <td class="py-3 px-4">
                            <?php if (!empty($client['website_url'])): ?>
                            <a href="<?= escape($client['website_url']) ?>" target="_blank" class="text-green-600 hover:underline">
                                <i class="fas fa-external-link-alt mr-1"></i>Visit
                            </a>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4"><?= escape($client['sort_order']) ?></td>
                        <td class="py-3 px-4">
                            <a href="?toggle=<?= $client['id'] ?>" class="px-2 py-1 rounded text-xs <?= $client['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $client['is_active'] ? 'Active' : 'Inactive' ?>
                            </a>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <a href="?edit=<?= $client['id'] ?>" class="text-green-600 hover:text-green-800" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?= $client['id'] ?>" class="text-red-600 hover:text-red-800" title="Delete" onclick="return confirm('Are you sure you want to delete this client?')">
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

    <!-- Clients Styling Settings -->
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 lg:p-8 mt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-800 flex items-center">
                <i class="fas fa-palette text-green-600 mr-2 text-xs"></i>
                Logo Slider Styling
            </h3>
            <a href="<?= url('index.php#clients') ?>" target="_blank" class="text-xs text-green-600 hover:text-green-800">
                <i class="fas fa-external-link-alt mr-1"></i>View Full Page
            </a>
        </div>
        
        <!-- Live Preview -->
        <div class="mb-4 p-4 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <div class="text-xs font-semibold text-gray-600 mb-2 flex items-center">
                <i class="fas fa-eye mr-2"></i>Live Preview
            </div>
            <div id="clients-preview-slider" class="clients-slider-preview" style="border-radius: 8px; overflow: hidden;">
                <div class="clients-slider-container-preview" style="max-width: 100%; padding: 0 15px;">
                    <div class="clients-slider-header-preview" style="text-align: center; margin-bottom: 20px;">
                        <h2 style="
                            font-size: 1.5rem;
                            font-weight: 700;
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                            background-clip: text;
                            margin-bottom: 5px;
                        ">Our Clients</h2>
                        <p style="
                            font-size: 0.875rem;
                            font-weight: 500;
                        ">Proud to serve leading companies worldwide</p>
                    </div>
                    <div class="clients-slider-wrapper-preview" style="overflow: hidden;">
                        <div class="clients-slider-track-preview" style="display: flex; flex-shrink: 0;">
                            <?php 
                            $previewClients = array_slice($clients, 0, 6);
                            if (empty($previewClients)) {
                                // Show placeholder boxes if no clients
                                for ($i = 0; $i < 12; $i++): ?>
                                <div class="clients-slider-item-preview" style="flex-shrink: 0; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                    <div style="width: 60%; height: 60%; background: #e5e7eb; border-radius: 4px;"></div>
                                </div>
                                <?php endfor;
                            } else {
                                foreach (array_merge($previewClients, $previewClients) as $client): ?>
                                <div class="clients-slider-item-preview" style="flex-shrink: 0; cursor: pointer;">
                                    <?php if (!empty($client['logo'])): ?>
                                    <img src="<?= escape(image_url($client['logo'])) ?>" alt="<?= escape($client['name']) ?>" style="width: 100%; height: 100%; object-fit: contain;">
                                    <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                        <span style="color: #9ca3af; font-size: 0.75rem;">No Logo</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach;
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <form method="POST" id="clients-styling-form">
            <input type="hidden" name="save_styling" value="1">
            <?= csrf_field() ?>
            
            <div class="space-y-2">
                <!-- Accordion: Section & Header -->
                <div class="border border-gray-200 rounded">
                    <button type="button" onclick="toggleAccordion('clients-section')" class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 flex items-center justify-between">
                        <span><i class="fas fa-layer-group mr-2"></i>Section & Header</span>
                        <i class="fas fa-chevron-down text-xs transform transition-transform" id="clients-section-icon"></i>
                    </button>
                    <div id="clients-section" class="hidden p-3 bg-white border-t border-gray-200">
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">BG Color 1</label>
                                <input type="color" name="clients_section_bg_color1" value="<?= escape($settings['clients_section_bg_color1'] ?? '#f0fdf4') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">BG Color 2</label>
                                <input type="color" name="clients_section_bg_color2" value="<?= escape($settings['clients_section_bg_color2'] ?? '#dcfce7') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Padding</label>
                                <input type="number" name="clients_section_padding" value="<?= escape($settings['clients_section_padding'] ?? '80') ?>" min="20" max="200" step="10" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Title Color 1</label>
                                <input type="color" name="clients_title_color1" value="<?= escape($settings['clients_title_color1'] ?? '#059669') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Title Color 2</label>
                                <input type="color" name="clients_title_color2" value="<?= escape($settings['clients_title_color2'] ?? '#10b981') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Desc Color</label>
                                <input type="color" name="clients_desc_color" value="<?= escape($settings['clients_desc_color'] ?? '#475569') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Accordion: Logo Item -->
                <div class="border border-gray-200 rounded">
                    <button type="button" onclick="toggleAccordion('clients-item')" class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 flex items-center justify-between">
                        <span><i class="fas fa-image mr-2"></i>Logo Item</span>
                        <i class="fas fa-chevron-down text-xs transform transition-transform" id="clients-item-icon"></i>
                    </button>
                    <div id="clients-item" class="hidden p-3 bg-white border-t border-gray-200">
                        <div class="grid grid-cols-4 gap-2">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Width</label>
                                <input type="number" name="clients_logo_item_width" value="<?= escape($settings['clients_logo_item_width'] ?? '180') ?>" min="100" max="400" step="10" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Height</label>
                                <input type="number" name="clients_logo_item_height" value="<?= escape($settings['clients_logo_item_height'] ?? '100') ?>" min="60" max="300" step="10" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Gap</label>
                                <input type="number" name="clients_logo_gap" value="<?= escape($settings['clients_logo_gap'] ?? '40') ?>" min="10" max="100" step="5" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Padding</label>
                                <input type="number" name="clients_logo_padding" value="<?= escape($settings['clients_logo_padding'] ?? '20') ?>" min="0" max="50" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Border W</label>
                                <input type="number" name="clients_logo_border_width" value="<?= escape($settings['clients_logo_border_width'] ?? '2') ?>" min="0" max="10" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Border Style</label>
                                <select name="clients_logo_border_style" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                                    <option value="solid" <?= ($settings['clients_logo_border_style'] ?? 'solid') === 'solid' ? 'selected' : '' ?>>Solid</option>
                                    <option value="dashed" <?= ($settings['clients_logo_border_style'] ?? '') === 'dashed' ? 'selected' : '' ?>>Dashed</option>
                                    <option value="dotted" <?= ($settings['clients_logo_border_style'] ?? '') === 'dotted' ? 'selected' : '' ?>>Dotted</option>
                                    <option value="none" <?= ($settings['clients_logo_border_style'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Border Color</label>
                                <input type="color" name="clients_logo_border_color" value="<?= escape($settings['clients_logo_border_color'] ?? '#10b981') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Radius</label>
                                <input type="number" name="clients_logo_border_radius" value="<?= escape($settings['clients_logo_border_radius'] ?? '12') ?>" min="0" max="50" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">BG Color</label>
                                <input type="color" name="clients_logo_bg_color" value="<?= escape($settings['clients_logo_bg_color'] ?? '#ffffff') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Accordion: Shadow & Hover -->
                <div class="border border-gray-200 rounded">
                    <button type="button" onclick="toggleAccordion('clients-shadow')" class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 flex items-center justify-between">
                        <span><i class="fas fa-magic mr-2"></i>Shadow & Hover</span>
                        <i class="fas fa-chevron-down text-xs transform transition-transform" id="clients-shadow-icon"></i>
                    </button>
                    <div id="clients-shadow" class="hidden p-3 bg-white border-t border-gray-200">
                        <div class="grid grid-cols-4 gap-2 mb-2">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Shadow X</label>
                                <input type="number" name="clients_logo_shadow_x" value="<?= escape($settings['clients_logo_shadow_x'] ?? '0') ?>" min="-20" max="20" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Shadow Y</label>
                                <input type="number" name="clients_logo_shadow_y" value="<?= escape($settings['clients_logo_shadow_y'] ?? '2') ?>" min="-20" max="20" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Blur</label>
                                <input type="number" name="clients_logo_shadow_blur" value="<?= escape($settings['clients_logo_shadow_blur'] ?? '8') ?>" min="0" max="50" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Shadow Color</label>
                                <input type="color" name="clients_logo_shadow_color" value="<?= escape($settings['clients_logo_shadow_color'] ?? '#10b981') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Opacity %</label>
                                <input type="number" name="clients_logo_shadow_opacity" value="<?= escape($settings['clients_logo_shadow_opacity'] ?? '10') ?>" min="0" max="100" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Y</label>
                                <input type="number" name="clients_logo_hover_y" value="<?= escape($settings['clients_logo_hover_y'] ?? '-8') ?>" min="-50" max="50" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Scale</label>
                                <input type="number" name="clients_logo_hover_scale" value="<?= escape($settings['clients_logo_hover_scale'] ?? '1.02') ?>" min="0.5" max="2" step="0.01" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Border</label>
                                <input type="color" name="clients_logo_hover_border_color" value="<?= escape($settings['clients_logo_hover_border_color'] ?? '#10b981') ?>" class="w-full h-8 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Shadow Y</label>
                                <input type="number" name="clients_logo_hover_shadow_y" value="<?= escape($settings['clients_logo_hover_shadow_y'] ?? '8') ?>" min="-20" max="50" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Blur</label>
                                <input type="number" name="clients_logo_hover_shadow_blur" value="<?= escape($settings['clients_logo_hover_shadow_blur'] ?? '24') ?>" min="0" max="100" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Opacity</label>
                                <input type="number" name="clients_logo_hover_shadow_opacity" value="<?= escape($settings['clients_logo_hover_shadow_opacity'] ?? '20') ?>" min="0" max="100" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Transition</label>
                                <input type="number" name="clients_logo_transition" value="<?= escape($settings['clients_logo_transition'] ?? '300') ?>" min="0" max="2000" step="50" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Accordion: Image Effects -->
                <div class="border border-gray-200 rounded">
                    <button type="button" onclick="toggleAccordion('clients-image')" class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 flex items-center justify-between">
                        <span><i class="fas fa-adjust mr-2"></i>Image Effects</span>
                        <i class="fas fa-chevron-down text-xs transform transition-transform" id="clients-image-icon"></i>
                    </button>
                    <div id="clients-image" class="hidden p-3 bg-white border-t border-gray-200">
                        <div class="grid grid-cols-4 gap-2">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Object Fit</label>
                                <select name="clients_logo_object_fit" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                                    <option value="contain" <?= ($settings['clients_logo_object_fit'] ?? 'contain') === 'contain' ? 'selected' : '' ?>>Contain</option>
                                    <option value="cover" <?= ($settings['clients_logo_object_fit'] ?? '') === 'cover' ? 'selected' : '' ?>>Cover</option>
                                    <option value="fill" <?= ($settings['clients_logo_object_fit'] ?? '') === 'fill' ? 'selected' : '' ?>>Fill</option>
                                    <option value="scale-down" <?= ($settings['clients_logo_object_fit'] ?? '') === 'scale-down' ? 'selected' : '' ?>>Scale Down</option>
                                    <option value="none" <?= ($settings['clients_logo_object_fit'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Grayscale %</label>
                                <input type="number" name="clients_logo_grayscale" value="<?= escape($settings['clients_logo_grayscale'] ?? '80') ?>" min="0" max="100" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Opacity %</label>
                                <input type="number" name="clients_logo_image_opacity" value="<?= escape($settings['clients_logo_image_opacity'] ?? '80') ?>" min="0" max="100" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Hover Scale</label>
                                <input type="number" name="clients_logo_hover_image_scale" value="<?= escape($settings['clients_logo_hover_image_scale'] ?? '1.05') ?>" min="0.5" max="2" step="0.01" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Accordion: Animation Settings -->
                <div class="border border-gray-200 rounded">
                    <button type="button" onclick="toggleAccordion('clients-animation')" class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 flex items-center justify-between">
                        <span><i class="fas fa-tachometer-alt mr-2"></i>Animation Settings</span>
                        <i class="fas fa-chevron-down text-xs transform transition-transform" id="clients-animation-icon"></i>
                    </button>
                    <div id="clients-animation" class="hidden p-3 bg-white border-t border-gray-200">
                        <div class="grid grid-cols-1 gap-2">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Slide Speed (seconds)</label>
                                <input type="number" name="clients_logo_slide_speed" value="<?= escape($settings['clients_logo_slide_speed'] ?? '30') ?>" min="5" max="120" step="1" class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                                <p class="text-xs text-gray-500 mt-1">Time for one complete slide cycle. Lower = faster animation.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end mt-3 pt-3 border-t border-gray-200">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
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
    
    function updateClientsPreview() {
        const form = document.getElementById('clients-styling-form');
        if (!form) return;
        
        const preview = document.getElementById('clients-preview-slider');
        if (!preview) return;
        
        const previewHeader = preview.querySelector('.clients-slider-header-preview');
        const previewTitle = previewHeader?.querySelector('h2');
        const previewDesc = previewHeader?.querySelector('p');
        const previewTrack = preview.querySelector('.clients-slider-track-preview');
        const previewItems = preview.querySelectorAll('.clients-slider-item-preview');
        
        // Get form values with fallbacks
        const bgColor1 = (form.querySelector('[name="clients_section_bg_color1"]')?.value) || '#f0fdf4';
        const bgColor2 = (form.querySelector('[name="clients_section_bg_color2"]')?.value) || '#dcfce7';
        const padding = parseInt(form.querySelector('[name="clients_section_padding"]')?.value) || 80;
        const titleColor1 = (form.querySelector('[name="clients_title_color1"]')?.value) || '#059669';
        const titleColor2 = (form.querySelector('[name="clients_title_color2"]')?.value) || '#10b981';
        const descColor = (form.querySelector('[name="clients_desc_color"]')?.value) || '#475569';
        const itemWidth = parseInt(form.querySelector('[name="clients_logo_item_width"]')?.value) || 180;
        const itemHeight = parseInt(form.querySelector('[name="clients_logo_item_height"]')?.value) || 100;
        const gap = parseInt(form.querySelector('[name="clients_logo_gap"]')?.value) || 40;
        const itemPadding = parseInt(form.querySelector('[name="clients_logo_padding"]')?.value) || 20;
        const borderWidth = parseInt(form.querySelector('[name="clients_logo_border_width"]')?.value) || 2;
        const borderStyle = (form.querySelector('[name="clients_logo_border_style"]')?.value) || 'solid';
        const borderColor = (form.querySelector('[name="clients_logo_border_color"]')?.value) || '#10b981';
        const borderRadius = parseInt(form.querySelector('[name="clients_logo_border_radius"]')?.value) || 12;
        const bgColor = (form.querySelector('[name="clients_logo_bg_color"]')?.value) || '#ffffff';
        const shadowX = parseInt(form.querySelector('[name="clients_logo_shadow_x"]')?.value) || 0;
        const shadowY = parseInt(form.querySelector('[name="clients_logo_shadow_y"]')?.value) || 2;
        const shadowBlur = parseInt(form.querySelector('[name="clients_logo_shadow_blur"]')?.value) || 8;
        const shadowColor = (form.querySelector('[name="clients_logo_shadow_color"]')?.value) || '#10b981';
        const shadowOpacity = parseInt(form.querySelector('[name="clients_logo_shadow_opacity"]')?.value) || 10;
        const objectFit = (form.querySelector('[name="clients_logo_object_fit"]')?.value) || 'contain';
        const grayscale = parseInt(form.querySelector('[name="clients_logo_grayscale"]')?.value) || 80;
        const imageOpacity = parseInt(form.querySelector('[name="clients_logo_image_opacity"]')?.value) || 80;
        const hoverY = parseInt(form.querySelector('[name="clients_logo_hover_y"]')?.value) || -8;
        const hoverScale = parseFloat(form.querySelector('[name="clients_logo_hover_scale"]')?.value) || 1.02;
        const hoverBorderColor = (form.querySelector('[name="clients_logo_hover_border_color"]')?.value) || '#10b981';
        const hoverShadowY = parseInt(form.querySelector('[name="clients_logo_hover_shadow_y"]')?.value) || 8;
        const hoverShadowBlur = parseInt(form.querySelector('[name="clients_logo_hover_shadow_blur"]')?.value) || 24;
        const hoverShadowOpacity = parseInt(form.querySelector('[name="clients_logo_hover_shadow_opacity"]')?.value) || 20;
        const hoverImageScale = parseFloat(form.querySelector('[name="clients_logo_hover_image_scale"]')?.value) || 1.05;
        const transition = parseInt(form.querySelector('[name="clients_logo_transition"]')?.value) || 300;
        const slideSpeed = parseInt(form.querySelector('[name="clients_logo_slide_speed"]')?.value) || 30;
        
        // Update preview section background
        preview.style.background = `linear-gradient(135deg, ${bgColor1} 0%, ${bgColor2} 100%)`;
        preview.style.padding = `${Math.max(15, padding / 4)}px 0`;
        
        // Update header
        if (previewTitle) {
            previewTitle.style.background = `linear-gradient(135deg, ${titleColor1}, ${titleColor2})`;
        }
        if (previewDesc) {
            previewDesc.style.color = descColor;
        }
        
        // Update track gap and animation speed
        if (previewTrack) {
            previewTrack.style.gap = `${gap}px`;
            previewTrack.style.animation = `slideClients ${slideSpeed}s linear infinite`;
        }
        
        // Update items
        previewItems.forEach(item => {
            item.style.width = `${itemWidth}px`;
            item.style.height = `${itemHeight}px`;
            item.style.padding = `${itemPadding}px`;
            item.style.border = `${borderWidth}px ${borderStyle} ${borderColor}`;
            item.style.borderRadius = `${borderRadius}px`;
            item.style.backgroundColor = bgColor;
            item.style.boxShadow = `${shadowX}px ${shadowY}px ${shadowBlur}px ${hexToRgba(shadowColor, shadowOpacity)}`;
            item.style.transition = `all ${transition}ms ease`;
            
            // Store hover values as data attributes
            item.setAttribute('data-hover-y', hoverY);
            item.setAttribute('data-hover-scale', hoverScale);
            item.setAttribute('data-hover-border', hoverBorderColor);
            item.setAttribute('data-hover-shadow-y', hoverShadowY);
            item.setAttribute('data-hover-shadow-blur', hoverShadowBlur);
            item.setAttribute('data-hover-shadow-opacity', hoverShadowOpacity);
            item.setAttribute('data-hover-image-scale', hoverImageScale);
            item.setAttribute('data-shadow-color', shadowColor);
            
            const img = item.querySelector('img');
            if (img) {
                img.style.objectFit = objectFit;
                img.style.filter = `grayscale(${grayscale}%) opacity(${imageOpacity / 100})`;
                img.style.transition = `all ${transition}ms ease`;
            }
        });
        
        // Update hover styles dynamically
        const style = document.getElementById('clients-preview-hover-styles');
        if (style) {
            style.textContent = `
                .clients-slider-item-preview:hover {
                    transform: translateY(${hoverY}px) scale(${hoverScale}) !important;
                    border-color: ${hoverBorderColor} !important;
                    box-shadow: ${shadowX}px ${hoverShadowY}px ${hoverShadowBlur}px ${hexToRgba(shadowColor, hoverShadowOpacity)} !important;
                }
                .clients-slider-item-preview:hover img {
                    filter: grayscale(0%) opacity(1) !important;
                    transform: scale(${hoverImageScale}) !important;
                }
            `;
        }
    }
    
    // Attach event listeners to all form inputs
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('clients-styling-form');
        if (form) {
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('input', updateClientsPreview);
                input.addEventListener('change', updateClientsPreview);
            });
            // Initial update
            updateClientsPreview();
        }
    });
    </script>
    
    <style>
    @keyframes slideClients {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    .clients-slider-track-preview {
        animation: slideClients <?= (int)($settings['clients_logo_slide_speed'] ?? 30) ?>s linear infinite;
    }
    .clients-slider-wrapper-preview:hover .clients-slider-track-preview {
        animation-play-state: paused;
    }
    </style>
    <style id="clients-preview-hover-styles"></style>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
