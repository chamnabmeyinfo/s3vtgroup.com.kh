<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Setting;

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $settingsToUpdate = [
        'mission_title' => trim($_POST['mission_title'] ?? 'Our Mission'),
        'mission_content' => trim($_POST['mission_content'] ?? ''),
        'mission_icon' => trim($_POST['mission_icon'] ?? 'fa-bullseye'),
        'mission_enabled' => isset($_POST['mission_enabled']) ? 1 : 0,
        'vision_title' => trim($_POST['vision_title'] ?? 'Our Vision'),
        'vision_content' => trim($_POST['vision_content'] ?? ''),
        'vision_icon' => trim($_POST['vision_icon'] ?? 'fa-eye'),
        'vision_enabled' => isset($_POST['vision_enabled']) ? 1 : 0,
        'mission_vision_section_enabled' => isset($_POST['mission_vision_section_enabled']) ? 1 : 0,
        'mission_vision_bg_color1' => trim($_POST['mission_vision_bg_color1'] ?? '#ffffff'),
        'mission_vision_bg_color2' => trim($_POST['mission_vision_bg_color2'] ?? '#f0f7ff'),
        'mission_vision_padding' => (int)($_POST['mission_vision_padding'] ?? 80),
        'mission_vision_title_color' => trim($_POST['mission_vision_title_color'] ?? '#1a1a1a'),
        'mission_vision_text_color' => trim($_POST['mission_vision_text_color'] ?? '#475569'),
        'mission_vision_icon_bg_color1' => trim($_POST['mission_vision_icon_bg_color1'] ?? '#3b82f6'),
        'mission_vision_icon_bg_color2' => trim($_POST['mission_vision_icon_bg_color2'] ?? '#2563eb'),
        'vision_icon_bg_color1' => trim($_POST['vision_icon_bg_color1'] ?? '#8b5cf6'),
        'vision_icon_bg_color2' => trim($_POST['vision_icon_bg_color2'] ?? '#7c3aed'),
        'mission_vision_hero_bg_image' => trim($_POST['mission_vision_hero_bg_image'] ?? ''),
        'mission_vision_hero_bg_overlay_opacity' => (float)($_POST['mission_vision_hero_bg_overlay_opacity'] ?? 0.3),
        'mission_card_bg_image' => trim($_POST['mission_card_bg_image'] ?? ''),
        'vision_card_bg_image' => trim($_POST['vision_card_bg_image'] ?? ''),
        'mission_vision_decorative_image' => trim($_POST['mission_vision_decorative_image'] ?? ''),
    ];
    
    // Handle image uploads
    $uploadDir = __DIR__ . '/../storage/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Helper function to handle image upload
    function handleImageUpload($fileKey, $prefix, $uploadDir) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $file = $_FILES[$fileKey];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes) || $file['size'] > $maxSize) {
            return null;
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return 'storage/uploads/' . $filename;
        }
        
        return null;
    }
    
    // Upload hero background image
    if (isset($_FILES['mission_vision_hero_bg_image_upload']) && $_FILES['mission_vision_hero_bg_image_upload']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = handleImageUpload('mission_vision_hero_bg_image_upload', 'mv_hero_bg', $uploadDir);
        if ($uploadedPath) {
            $settingsToUpdate['mission_vision_hero_bg_image'] = $uploadedPath;
        }
    }
    
    // Upload mission card background image
    if (isset($_FILES['mission_card_bg_image_upload']) && $_FILES['mission_card_bg_image_upload']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = handleImageUpload('mission_card_bg_image_upload', 'mission_card_bg', $uploadDir);
        if ($uploadedPath) {
            $settingsToUpdate['mission_card_bg_image'] = $uploadedPath;
        }
    }
    
    // Upload vision card background image
    if (isset($_FILES['vision_card_bg_image_upload']) && $_FILES['vision_card_bg_image_upload']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = handleImageUpload('vision_card_bg_image_upload', 'vision_card_bg', $uploadDir);
        if ($uploadedPath) {
            $settingsToUpdate['vision_card_bg_image'] = $uploadedPath;
        }
    }
    
    // Upload decorative image
    if (isset($_FILES['mission_vision_decorative_image_upload']) && $_FILES['mission_vision_decorative_image_upload']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = handleImageUpload('mission_vision_decorative_image_upload', 'mv_decorative', $uploadDir);
        if ($uploadedPath) {
            $settingsToUpdate['mission_vision_decorative_image'] = $uploadedPath;
        }
    }
    
    try {
        foreach ($settingsToUpdate as $key => $value) {
            $existing = db()->fetchOne("SELECT id FROM settings WHERE `key` = :key", ['key' => $key]);
            
            if ($existing) {
                db()->update('settings', ['value' => $value], '`key` = :key', ['key' => $key]);
            } else {
                db()->insert('settings', [
                    'key' => $key,
                    'value' => $value,
                    'type' => 'text'
                ]);
            }
        }
        
        $message = 'Mission & Vision updated successfully.';
    } catch (Exception $e) {
        $error = 'Error updating Mission & Vision: ' . $e->getMessage();
    }
}

// Get current settings
$settingsData = db()->fetchAll("SELECT `key`, value FROM settings WHERE `key` LIKE 'mission_%' OR `key` LIKE 'vision_%'");
$settings = [];
foreach ($settingsData as $setting) {
    $settings[$setting['key']] = $setting['value'];
}

// Default values
$defaults = [
    'mission_title' => 'Our Mission',
    'mission_content' => 'To provide exceptional forklift and industrial equipment solutions that empower businesses to achieve their operational goals. We are committed to delivering quality products, outstanding service, and innovative solutions that drive productivity and success.',
    'mission_icon' => 'fa-bullseye',
    'mission_enabled' => 1,
    'vision_title' => 'Our Vision',
    'vision_content' => 'To become the most trusted partner in the industrial equipment industry, recognized for excellence, innovation, and customer satisfaction. We envision a future where every business has access to the best equipment solutions tailored to their unique needs.',
    'vision_icon' => 'fa-eye',
    'vision_enabled' => 1,
    'mission_vision_section_enabled' => 1,
    'mission_vision_bg_color1' => '#ffffff',
    'mission_vision_bg_color2' => '#f0f7ff',
    'mission_vision_padding' => 80,
    'mission_vision_title_color' => '#1a1a1a',
    'mission_vision_text_color' => '#475569',
    'mission_vision_icon_bg_color1' => '#3b82f6',
    'mission_vision_icon_bg_color2' => '#2563eb',
    'vision_icon_bg_color1' => '#8b5cf6',
    'vision_icon_bg_color2' => '#7c3aed',
];

foreach ($defaults as $key => $default) {
    if (!isset($settings[$key])) {
        $settings[$key] = $default;
    }
}

$pageTitle = 'Mission & Vision';
include __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-bullseye text-blue-600 mr-3"></i>
                Mission & Vision
            </h1>
            <p class="text-gray-600 mt-2">Manage your company's Mission and Vision pages and homepage section.</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <a href="<?= url('mission-vision.php') ?>" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                <i class="fas fa-external-link-alt mr-2"></i>View Combined Page
            </a>
            <a href="<?= url('mission.php') ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                <i class="fas fa-external-link-alt mr-2"></i>View Mission Page
            </a>
            <a href="<?= url('vision.php') ?>" target="_blank" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                <i class="fas fa-external-link-alt mr-2"></i>View Vision Page
            </a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-check-circle mr-2"></i><?= escape($message) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i><?= escape($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="missionVisionForm" class="bg-white rounded-xl shadow-lg p-4 md:p-6 lg:p-8">
        <?= csrf_field() ?>
        
        <!-- Section Enable/Disable -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Show Mission & Vision Section on Homepage</label>
                    <p class="text-xs text-gray-500">Enable or disable the Mission & Vision section on the homepage</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="mission_vision_section_enabled" value="1" <?= ($settings['mission_vision_section_enabled'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>
        </div>

        <!-- Live Preview Section -->
        <div class="mb-6 bg-white rounded-xl shadow-lg p-4 md:p-6 lg:p-8 border-2 border-blue-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-eye text-blue-600 mr-2"></i>
                Live Preview
            </h3>
            
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Mission Preview -->
                <div class="border-2 border-gray-200 rounded-lg p-4 bg-gray-50">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Mission Page Preview</h4>
                    <div id="missionPreview" class="relative overflow-hidden rounded-lg" style="
                        background: linear-gradient(135deg, <?= escape($settings['mission_vision_bg_color1'] ?? '#ffffff') ?>, <?= escape($settings['mission_vision_bg_color2'] ?? '#f0f7ff') ?>);
                        min-height: 200px;
                        padding: 20px;
                    ">
                        <div class="text-center">
                            <div class="inline-block mb-4">
                                <div class="w-16 h-16 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>);">
                                    <i class="fas <?= escape($settings['mission_icon'] ?? 'fa-bullseye') ?> text-white text-2xl"></i>
                                </div>
                            </div>
                            <h2 class="text-2xl font-bold mb-2" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">
                                <?= escape($settings['mission_title'] ?? 'Our Mission') ?>
                            </h2>
                            <p class="text-sm line-clamp-2" style="color: <?= escape($settings['mission_vision_text_color'] ?? '#475569') ?>;">
                                <?= escape(substr($settings['mission_content'] ?? '', 0, 100)) ?>...
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Vision Preview -->
                <div class="border-2 border-gray-200 rounded-lg p-4 bg-gray-50">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Vision Page Preview</h4>
                    <div id="visionPreview" class="relative overflow-hidden rounded-lg" style="
                        background: linear-gradient(135deg, <?= escape($settings['mission_vision_bg_color1'] ?? '#ffffff') ?>, <?= escape($settings['mission_vision_bg_color2'] ?? '#faf5ff') ?>);
                        min-height: 200px;
                        padding: 20px;
                    ">
                        <div class="text-center">
                            <div class="inline-block mb-4">
                                <div class="w-16 h-16 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>);">
                                    <i class="fas <?= escape($settings['vision_icon'] ?? 'fa-eye') ?> text-white text-2xl"></i>
                                </div>
                            </div>
                            <h2 class="text-2xl font-bold mb-2" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">
                                <?= escape($settings['vision_title'] ?? 'Our Vision') ?>
                            </h2>
                            <p class="text-sm line-clamp-2" style="color: <?= escape($settings['mission_vision_text_color'] ?? '#475569') ?>;">
                                <?= escape(substr($settings['vision_content'] ?? '', 0, 100)) ?>...
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Settings -->
        <div class="mb-6">
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <button type="button" class="w-full px-6 py-4 bg-gray-50 hover:bg-gray-100 flex items-center justify-between transition-colors" onclick="toggleAccordion('contentSettings')">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-edit text-blue-600 mr-2"></i>
                        Content Settings
                    </h3>
                    <i class="fas fa-chevron-down transform transition-transform" id="contentSettingsIcon"></i>
                </button>
                <div id="contentSettings" class="hidden p-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Mission Card -->
                        <div class="border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-800 flex items-center">
                                    <i class="fas fa-bullseye text-blue-600 mr-2"></i>
                                    Mission
                                </h4>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="mission_enabled" value="1" <?= ($settings['mission_enabled'] ?? 1) ? 'checked' : '' ?> class="sr-only peer" onchange="updatePreview()">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Mission Title</label>
                                    <input type="text" name="mission_title" value="<?= escape($settings['mission_title'] ?? 'Our Mission') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="updatePreview()">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Mission Content</label>
                                    <textarea name="mission_content" rows="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="updatePreview()"><?= escape($settings['mission_content'] ?? '') ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Mission Icon (Font Awesome class)</label>
                                    <input type="text" name="mission_icon" value="<?= escape($settings['mission_icon'] ?? 'fa-bullseye') ?>" placeholder="fa-bullseye" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="updatePreview()">
                                    <p class="text-xs text-gray-500 mt-1">Example: fa-bullseye, fa-target, fa-flag</p>
                                </div>
                            </div>
                        </div>

                        <!-- Vision Card -->
                        <div class="border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-800 flex items-center">
                                    <i class="fas fa-eye text-purple-600 mr-2"></i>
                                    Vision
                                </h4>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="vision_enabled" value="1" <?= ($settings['vision_enabled'] ?? 1) ? 'checked' : '' ?> class="sr-only peer" onchange="updatePreview()">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Vision Title</label>
                                    <input type="text" name="vision_title" value="<?= escape($settings['vision_title'] ?? 'Our Vision') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="updatePreview()">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Vision Content</label>
                                    <textarea name="vision_content" rows="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="updatePreview()"><?= escape($settings['vision_content'] ?? '') ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Vision Icon (Font Awesome class)</label>
                                    <input type="text" name="vision_icon" value="<?= escape($settings['vision_icon'] ?? 'fa-eye') ?>" placeholder="fa-eye" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" oninput="updatePreview()">
                                    <p class="text-xs text-gray-500 mt-1">Example: fa-eye, fa-lightbulb, fa-rocket</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Upload Settings -->
        <div class="mb-6">
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <button type="button" class="w-full px-6 py-4 bg-gray-50 hover:bg-gray-100 flex items-center justify-between transition-colors" onclick="toggleAccordion('imageSettings')">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-images text-blue-600 mr-2"></i>
                        Image Upload Settings
                    </h3>
                    <i class="fas fa-chevron-down transform transition-transform" id="imageSettingsIcon"></i>
                </button>
                <div id="imageSettings" class="hidden p-6">
                    <div class="space-y-6">
                        <!-- Hero Background Image -->
                        <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                            <h4 class="text-md font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-image text-blue-600 mr-2"></i>
                                Hero Section Background Image
                            </h4>
                            <p class="text-sm text-gray-600 mb-4">Upload a background image for the Mission & Vision page hero section</p>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Hero Background Image</label>
                                <input type="file" name="mission_vision_hero_bg_image_upload" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Max 5MB. JPG, PNG, GIF, or WebP</p>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Or Enter Image URL</label>
                                <input type="text" name="mission_vision_hero_bg_image" value="<?= escape($settings['mission_vision_hero_bg_image'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://example.com/image.jpg or storage/uploads/image.jpg">
                            </div>
                            
                            <?php if (!empty($settings['mission_vision_hero_bg_image'])): ?>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Hero Background Image</label>
                                <img src="<?= escape($settings['mission_vision_hero_bg_image']) ?>" alt="Hero Background" class="max-w-full h-48 object-cover rounded-lg border border-gray-300">
                            </div>
                            <?php endif; ?>
                            
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Background Overlay Opacity (0-1)</label>
                                <input type="number" name="mission_vision_hero_bg_overlay_opacity" value="<?= escape($settings['mission_vision_hero_bg_overlay_opacity'] ?? 0.3) ?>" min="0" max="1" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <p class="text-xs text-gray-500 mt-1">Controls the darkness of overlay on background image (0 = transparent, 1 = fully opaque)</p>
                            </div>
                        </div>
                        
                        <!-- Mission Card Background Image -->
                        <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                            <h4 class="text-md font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-image text-blue-600 mr-2"></i>
                                Mission Card Background Image
                            </h4>
                            <p class="text-sm text-gray-600 mb-4">Upload a background image for the Mission card</p>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Mission Card Background</label>
                                <input type="file" name="mission_card_bg_image_upload" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Max 5MB. JPG, PNG, GIF, or WebP</p>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Or Enter Image URL</label>
                                <input type="text" name="mission_card_bg_image" value="<?= escape($settings['mission_card_bg_image'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://example.com/image.jpg or storage/uploads/image.jpg">
                            </div>
                            
                            <?php if (!empty($settings['mission_card_bg_image'])): ?>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Mission Card Background</label>
                                <img src="<?= escape($settings['mission_card_bg_image']) ?>" alt="Mission Card Background" class="max-w-full h-32 object-cover rounded-lg border border-gray-300">
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Vision Card Background Image -->
                        <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                            <h4 class="text-md font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-image text-purple-600 mr-2"></i>
                                Vision Card Background Image
                            </h4>
                            <p class="text-sm text-gray-600 mb-4">Upload a background image for the Vision card</p>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Vision Card Background</label>
                                <input type="file" name="vision_card_bg_image_upload" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Max 5MB. JPG, PNG, GIF, or WebP</p>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Or Enter Image URL</label>
                                <input type="text" name="vision_card_bg_image" value="<?= escape($settings['vision_card_bg_image'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://example.com/image.jpg or storage/uploads/image.jpg">
                            </div>
                            
                            <?php if (!empty($settings['vision_card_bg_image'])): ?>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Vision Card Background</label>
                                <img src="<?= escape($settings['vision_card_bg_image']) ?>" alt="Vision Card Background" class="max-w-full h-32 object-cover rounded-lg border border-gray-300">
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Decorative Image -->
                        <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                            <h4 class="text-md font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-image text-indigo-600 mr-2"></i>
                                Decorative Image
                            </h4>
                            <p class="text-sm text-gray-600 mb-4">Upload a decorative image for use in the Mission & Vision section</p>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Decorative Image</label>
                                <input type="file" name="mission_vision_decorative_image_upload" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Max 5MB. JPG, PNG, GIF, or WebP</p>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Or Enter Image URL</label>
                                <input type="text" name="mission_vision_decorative_image" value="<?= escape($settings['mission_vision_decorative_image'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://example.com/image.jpg or storage/uploads/image.jpg">
                            </div>
                            
                            <?php if (!empty($settings['mission_vision_decorative_image'])): ?>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Decorative Image</label>
                                <img src="<?= escape($settings['mission_vision_decorative_image']) ?>" alt="Decorative Image" class="max-w-full h-32 object-cover rounded-lg border border-gray-300">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Styling Settings -->
        <div class="mb-6">
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <button type="button" class="w-full px-6 py-4 bg-gray-50 hover:bg-gray-100 flex items-center justify-between transition-colors" onclick="toggleAccordion('stylingSettings')">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-palette text-blue-600 mr-2"></i>
                        Styling Settings
                    </h3>
                    <i class="fas fa-chevron-down transform transition-transform" id="stylingSettingsIcon"></i>
                </button>
                <div id="stylingSettings" class="hidden p-6">
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Background Color 1</label>
                            <input type="color" name="mission_vision_bg_color1" value="<?= escape($settings['mission_vision_bg_color1'] ?? '#ffffff') ?>" class="w-full h-10 border border-gray-300 rounded cursor-pointer" onchange="updatePreview()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Background Color 2</label>
                            <input type="color" name="mission_vision_bg_color2" value="<?= escape($settings['mission_vision_bg_color2'] ?? '#f0f7ff') ?>" class="w-full h-10 border border-gray-300 rounded cursor-pointer" onchange="updatePreview()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Section Padding</label>
                            <input type="number" name="mission_vision_padding" value="<?= escape($settings['mission_vision_padding'] ?? 80) ?>" min="20" max="200" step="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title Color</label>
                            <input type="color" name="mission_vision_title_color" value="<?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>" class="w-full h-10 border border-gray-300 rounded cursor-pointer" onchange="updatePreview()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Text Color</label>
                            <input type="color" name="mission_vision_text_color" value="<?= escape($settings['mission_vision_text_color'] ?? '#475569') ?>" class="w-full h-10 border border-gray-300 rounded cursor-pointer" onchange="updatePreview()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mission Icon BG Color 1</label>
                            <input type="color" name="mission_vision_icon_bg_color1" value="<?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>" class="w-full h-10 border border-gray-300 rounded cursor-pointer" onchange="updatePreview()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mission Icon BG Color 2</label>
                            <input type="color" name="mission_vision_icon_bg_color2" value="<?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>" class="w-full h-10 border border-gray-300 rounded cursor-pointer" onchange="updatePreview()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Vision Icon BG Color 1</label>
                            <input type="color" name="vision_icon_bg_color1" value="<?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>" class="w-full h-10 border border-gray-300 rounded cursor-pointer" onchange="updatePreview()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Vision Icon BG Color 2</label>
                            <input type="color" name="vision_icon_bg_color2" value="<?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>" class="w-full h-10 border border-gray-300 rounded cursor-pointer" onchange="updatePreview()">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="<?= url('index.php') ?>" target="_blank" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                <i class="fas fa-home mr-2"></i>View Homepage
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                <i class="fas fa-save mr-2"></i>Save Mission & Vision
            </button>
        </div>
    </form>
</div>

<script>
function toggleAccordion(id) {
    const element = document.getElementById(id);
    const icon = document.getElementById(id + 'Icon');
    
    if (element.classList.contains('hidden')) {
        element.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        element.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}

function hexToRgba(hex, alpha = 1) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function updatePreview() {
    const form = document.getElementById('missionVisionForm');
    const formData = new FormData(form);
    
    // Mission Preview
    const missionPreview = document.getElementById('missionPreview');
    const missionTitle = form.querySelector('[name="mission_title"]').value;
    const missionContent = form.querySelector('[name="mission_content"]').value;
    const missionIcon = form.querySelector('[name="mission_icon"]').value;
    const bgColor1 = form.querySelector('[name="mission_vision_bg_color1"]').value;
    const bgColor2 = form.querySelector('[name="mission_vision_bg_color2"]').value;
    const titleColor = form.querySelector('[name="mission_vision_title_color"]').value;
    const textColor = form.querySelector('[name="mission_vision_text_color"]').value;
    const missionIconBg1 = form.querySelector('[name="mission_vision_icon_bg_color1"]').value;
    const missionIconBg2 = form.querySelector('[name="mission_vision_icon_bg_color2"]').value;
    
    missionPreview.style.background = `linear-gradient(135deg, ${bgColor1}, ${bgColor2})`;
    missionPreview.innerHTML = `
        <div class="text-center">
            <div class="inline-block mb-4">
                <div class="w-16 h-16 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, ${missionIconBg1}, ${missionIconBg2});">
                    <i class="fas ${missionIcon} text-white text-2xl"></i>
                </div>
            </div>
            <h2 class="text-2xl font-bold mb-2" style="color: ${titleColor};">
                ${missionTitle}
            </h2>
            <p class="text-sm line-clamp-2" style="color: ${textColor};">
                ${missionContent.substring(0, 100)}...
            </p>
        </div>
    `;
    
    // Vision Preview
    const visionPreview = document.getElementById('visionPreview');
    const visionTitle = form.querySelector('[name="vision_title"]').value;
    const visionContent = form.querySelector('[name="vision_content"]').value;
    const visionIcon = form.querySelector('[name="vision_icon"]').value;
    const visionIconBg1 = form.querySelector('[name="vision_icon_bg_color1"]').value;
    const visionIconBg2 = form.querySelector('[name="vision_icon_bg_color2"]').value;
    
    visionPreview.style.background = `linear-gradient(135deg, ${bgColor1}, ${bgColor2})`;
    visionPreview.innerHTML = `
        <div class="text-center">
            <div class="inline-block mb-4">
                <div class="w-16 h-16 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, ${visionIconBg1}, ${visionIconBg2});">
                    <i class="fas ${visionIcon} text-white text-2xl"></i>
                </div>
            </div>
            <h2 class="text-2xl font-bold mb-2" style="color: ${titleColor};">
                ${visionTitle}
            </h2>
            <p class="text-sm line-clamp-2" style="color: ${textColor};">
                ${visionContent.substring(0, 100)}...
            </p>
        </div>
    `;
}

// Initialize accordions
document.addEventListener('DOMContentLoaded', function() {
    // Open first accordion by default
    toggleAccordion('contentSettings');
    
    // Preview image uploads
    const imageInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Show preview near the input
                    const preview = document.createElement('img');
                    preview.src = e.target.result;
                    preview.className = 'mt-2 max-w-full h-32 object-cover rounded-lg border border-gray-300';
                    preview.alt = 'Preview';
                    
                    // Remove existing preview if any
                    const existingPreview = input.parentElement.querySelector('img.preview-image');
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    
                    preview.classList.add('preview-image');
                    input.parentElement.appendChild(preview);
                };
                reader.readAsDataURL(file);
            }
        });
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
