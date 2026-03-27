<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\CeoMessage;
use App\Models\Setting;

// Check if table exists, create if not
try {
    db()->fetchOne("SELECT 1 FROM ceo_message LIMIT 1");
} catch (Exception $e) {
    // Table doesn't exist, try to create it
    $sql = file_get_contents(__DIR__ . '/../database/ceo-message.sql');
    try {
        db()->execute($sql);
    } catch (Exception $ex) {
        $error = 'Please run the SQL file: database/ceo-message.sql';
    }
}

$ceoModel = new CeoMessage();
$settingModel = new Setting();
$message = '';
$error = '';

// Get current CEO message (or create default)
$ceoData = $ceoModel->getActive();
if (!$ceoData) {
    // Create default if none exists
    try {
        $defaultId = $ceoModel->create([
            'ceo_name' => 'CEO',
            'ceo_title' => 'Chief Executive Officer',
            'greeting' => 'Dear Valued Customers and Partners,',
            'message_content' => '<p>It is with great pleasure and pride that I welcome you to our company. As the Chief Executive Officer, I am honored to lead a team of dedicated professionals who are committed to delivering excellence in every aspect of our business.</p><p>Our company was founded on the principles of quality, integrity, and customer satisfaction. These core values have guided us through years of growth and have established us as a trusted leader in the forklift and industrial equipment industry.</p><p>We understand that your business success depends on reliable, efficient equipment. That\'s why we go beyond simply selling products – we partner with you to understand your unique needs and provide solutions that drive your operational excellence.</p><p>Our commitment extends to every interaction: from the initial consultation through installation, training, and ongoing support. We believe in building long-term relationships, not just making transactions.</p><p>As we look to the future, we remain dedicated to innovation, continuous improvement, and exceeding your expectations. We invest in our team, our technology, and our processes to ensure we can serve you better every day.</p><p>Thank you for choosing us. We are here to support your success, and we look forward to being your trusted partner for years to come.</p>',
            'signature_name' => 'CEO',
            'signature_title' => 'Chief Executive Officer',
            'is_active' => 1
        ]);
        $ceoData = $ceoModel->getById($defaultId);
    } catch (Exception $e) {
        $error = 'Error creating default CEO message: ' . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'ceo_name' => trim($_POST['ceo_name'] ?? ''),
        'ceo_title' => trim($_POST['ceo_title'] ?? ''),
        'greeting' => trim($_POST['greeting'] ?? ''),
        'message_content' => $_POST['message_content'] ?? '',
        'signature_name' => trim($_POST['signature_name'] ?? ''),
        'signature_title' => trim($_POST['signature_title'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];

    // Handle photo upload
    if (isset($_FILES['ceo_photo']) && $_FILES['ceo_photo']['error'] === UPLOAD_ERR_OK) {
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
            $file = $_FILES['ceo_photo'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedExtensions)) {
                $error = 'Invalid file type. Please upload JPG, PNG, GIF, or WebP.';
            } elseif (!in_array($file['type'], $allowedTypes)) {
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    if (!in_array($mimeType, $allowedTypes)) {
                        $error = 'Invalid file type. Please upload JPG, PNG, GIF, or WebP.';
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
            } elseif (!@getimagesize($file['tmp_name'])) {
                $error = 'File is not a valid image.';
            } else {
                // Delete old photo if exists
                if ($ceoData && !empty($ceoData['ceo_photo'])) {
                    $oldPhotoPath = __DIR__ . '/../' . $ceoData['ceo_photo'];
                    if (file_exists($oldPhotoPath)) {
                        @unlink($oldPhotoPath);
                    }
                }

                $filename = 'ceo_' . time() . '_' . uniqid() . '.' . $extension;
                $filepath = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    if (file_exists($filepath) && filesize($filepath) > 0) {
                        $data['ceo_photo'] = 'storage/uploads/' . $filename;
                    } else {
                        $error = 'File upload failed. File may be corrupted.';
                        @unlink($filepath);
                    }
                } else {
                    $error = 'Failed to upload photo. Please check directory permissions.';
                }
            }
        }
    }

    if (empty($error)) {
        try {
            if ($ceoData) {
                $ceoModel->update($ceoData['id'], $data);
                $message = 'CEO Message updated successfully.';
            } else {
                $ceoModel->create($data);
                $message = 'CEO Message created successfully.';
            }
            $ceoData = $ceoModel->getActive();
        } catch (\Exception $e) {
            $error = 'Error saving CEO message: ' . $e->getMessage();
        }
    }
}

$siteName = $settingModel->get('site_name', 'Forklift & Equipment Pro');

$pageTitle = 'CEO Message Template';
include __DIR__ . '/includes/header.php';
?>

<style>
    .admin-section-card {
        background: linear-gradient(to bottom, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }
    
    .admin-section-card:hover {
        border-color: rgba(99, 102, 241, 0.2);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
    }
    
    .form-input {
        transition: all 0.3s ease;
    }
    
    .form-input:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
    }
    
    .photo-preview {
        border: 3px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    
    .photo-preview:hover {
        border-color: #6366f1;
        transform: scale(1.05);
    }
</style>

<div class="w-full">
    <!-- Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-6 md:p-8 mb-6 text-white">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute inset-0 opacity-20" style="background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center mb-2">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-user-tie text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold">
                            CEO Message Template
                        </h1>
                        <p class="text-indigo-100 text-sm mt-1">Edit the CEO message displayed on the website</p>
                    </div>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="<?= url('admin/ceo-message-add-demo.php') ?>" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm px-4 py-2 rounded-lg font-semibold transition-all duration-300">
                    <i class="fas fa-database mr-2"></i>Add Demo Data
                </a>
                <a href="<?= url('ceo-message.php') ?>" target="_blank" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm px-6 py-3 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105">
                    <i class="fas fa-eye mr-2"></i>Preview Page
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-800 p-5 rounded-xl mb-6 shadow-lg">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-check-circle text-white text-lg"></i>
            </div>
            <span class="font-semibold text-lg"><?= escape($message) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 text-red-800 p-5 rounded-xl mb-6 shadow-lg">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-exclamation-circle text-white text-lg"></i>
            </div>
            <span class="font-semibold text-lg"><?= escape($error) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($ceoData): ?>
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <!-- CEO Profile Section -->
        <div class="admin-section-card rounded-2xl p-6 md:p-8">
            <div class="flex items-center mb-6 pb-4 border-b-2 border-gray-100">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center mr-4">
                    <i class="fas fa-user-tie text-white"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">CEO Profile</h2>
            </div>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-user text-indigo-500 mr-2"></i>CEO Name *
                    </label>
                    <input type="text" name="ceo_name" value="<?= escape($ceoData['ceo_name'] ?? 'CEO') ?>" required
                           class="form-input w-full px-4 py-3.5 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-briefcase text-indigo-500 mr-2"></i>CEO Title *
                    </label>
                    <input type="text" name="ceo_title" value="<?= escape($ceoData['ceo_title'] ?? 'Chief Executive Officer') ?>" required
                           class="form-input w-full px-4 py-3.5 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-image text-indigo-500 mr-2"></i>CEO Photo
                    </label>
                    <?php if (!empty($ceoData['ceo_photo'])): ?>
                    <div class="mb-4 inline-block">
                        <div class="photo-preview rounded-2xl p-2 bg-gradient-to-br from-indigo-50 to-purple-50">
                            <img src="<?= escape(image_url($ceoData['ceo_photo'])) ?>" alt="CEO Photo" class="h-40 w-40 object-cover rounded-xl">
                        </div>
                        <p class="text-xs text-gray-500 mt-2 font-medium">Current photo. Upload a new one to replace it.</p>
                    </div>
                    <?php endif; ?>
                    <div class="mt-4">
                        <input type="file" name="ceo_photo" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                               class="form-input w-full px-4 py-3.5 border-2 border-dashed border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50 hover:bg-white transition-colors cursor-pointer">
                        <p class="text-xs text-gray-500 mt-2 font-medium">
                            <i class="fas fa-info-circle mr-1 text-indigo-500"></i>
                            Recommended: Square image (300x300px or larger). Max size: 5MB.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Content Section -->
        <div class="admin-section-card rounded-2xl p-6 md:p-8">
            <div class="flex items-center mb-6 pb-4 border-b-2 border-gray-100">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center mr-4">
                    <i class="fas fa-envelope-open-text text-white"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Message Content</h2>
            </div>
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-hand-paper text-indigo-500 mr-2"></i>Greeting *
                    </label>
                    <input type="text" name="greeting" value="<?= escape($ceoData['greeting'] ?? 'Dear Valued Customers and Partners,') ?>" required
                           placeholder="Dear Valued Customers and Partners,"
                           class="form-input w-full px-4 py-3.5 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-align-left text-indigo-500 mr-2"></i>Message Content *
                    </label>
                    <textarea name="message_content" rows="14" required
                              class="form-input w-full px-4 py-3.5 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white font-mono text-sm leading-relaxed"
                              placeholder="Enter the CEO message content here. You can use HTML tags for formatting."><?= escape($ceoData['message_content'] ?? '') ?></textarea>
                    <div class="mt-3 bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <p class="text-xs text-blue-800 font-medium">
                            <i class="fas fa-info-circle mr-2"></i>
                            You can use HTML tags like &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;br&gt;, etc. for formatting.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="admin-section-card rounded-2xl p-6 md:p-8">
            <div class="flex items-center mb-6 pb-4 border-b-2 border-gray-100">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center mr-4">
                    <i class="fas fa-pen-fancy text-white"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Signature</h2>
            </div>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-signature text-indigo-500 mr-2"></i>Signature Name *
                    </label>
                    <input type="text" name="signature_name" value="<?= escape($ceoData['signature_name'] ?? 'CEO') ?>" required
                           class="form-input w-full px-4 py-3.5 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-briefcase text-indigo-500 mr-2"></i>Signature Title
                    </label>
                    <input type="text" name="signature_title" value="<?= escape($ceoData['signature_title'] ?? '') ?>"
                           placeholder="Chief Executive Officer"
                           class="form-input w-full px-4 py-3.5 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="admin-section-card rounded-2xl p-6 md:p-8">
            <div class="bg-gradient-to-r from-gray-50 to-indigo-50 rounded-xl p-5 border-2 border-gray-200">
                <label class="flex items-center cursor-pointer group">
                    <input type="checkbox" name="is_active" value="1" <?= ($ceoData['is_active'] ?? 1) ? 'checked' : '' ?>
                           class="w-6 h-6 text-indigo-600 border-gray-300 rounded-lg focus:ring-indigo-500 focus:ring-2 transition-all">
                    <span class="ml-4 text-base font-bold text-gray-700 group-hover:text-indigo-600 transition-colors">Active (Show on website)</span>
                </label>
                <p class="text-xs text-gray-600 mt-3 ml-10 font-medium">
                    <i class="fas fa-info-circle mr-1 text-indigo-500"></i>
                    Only active CEO messages will be displayed on the frontend.
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-4 pt-4">
            <button type="submit" class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white px-10 py-4 rounded-xl font-bold text-lg hover:from-indigo-700 hover:via-purple-700 hover:to-pink-700 transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:scale-105">
                <i class="fas fa-save mr-2"></i>Save CEO Message
            </button>
            <a href="<?= url('ceo-message.php') ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-10 py-4 rounded-xl font-bold text-lg transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="fas fa-eye mr-2"></i>Preview Page
            </a>
        </div>
    </form>
    <?php else: ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
            <div>
                <h3 class="font-semibold text-yellow-800 mb-2">CEO Message Table Not Set Up</h3>
                <p class="text-yellow-700 text-sm mb-4">Please run the SQL file: database/ceo-message.sql</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
