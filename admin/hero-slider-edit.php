<?php
/**
 * Advanced Hero Slider Edit Page
 * This is a comprehensive edit page with all advanced features
 * We'll integrate this into the main edit page
 */

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\HeroSlider;
use App\Helpers\HeroSliderHelper;

$heroSliderModel = new HeroSlider();
$message = '';
$error = '';
$slide = null;
$slideId = $_GET['id'] ?? null;

if ($slideId) {
    $slide = $heroSliderModel->getById($slideId);
    if (!$slide) {
        header('Location: ' . url('admin/hero-slider.php'));
        exit;
    }
}

// Helper functions for file uploads
function handleFileUpload($fileKey, $allowedTypes, $maxSize, $prefix, $oldFile = null) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $uploadDir = __DIR__ . '/../storage/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES[$fileKey];
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['error' => "Invalid file type for $fileKey"];
    }
    
    if ($file['size'] > $maxSize) {
        return ['error' => "File size exceeds limit for $fileKey"];
    }
    
    // Delete old file if exists
    if ($oldFile && file_exists(__DIR__ . '/../storage/uploads/' . basename($oldFile))) {
        @unlink(__DIR__ . '/../storage/uploads/' . basename($oldFile));
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'storage/uploads/' . $filename;
    }
    
    return ['error' => "Failed to upload $fileKey"];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle all file uploads
    $imageResult = handleFileUpload('background_image', 
        ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'], 
        10 * 1024 * 1024, 'hero_slide', $slide['background_image'] ?? null);
    
    $mobileImageResult = handleFileUpload('image_mobile',
        ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        10 * 1024 * 1024, 'hero_mobile', $slide['image_mobile'] ?? null);
    
    $tabletImageResult = handleFileUpload('image_tablet',
        ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        10 * 1024 * 1024, 'hero_tablet', $slide['image_tablet'] ?? null);
    
    $videoResult = handleFileUpload('video_background',
        ['video/mp4', 'video/webm', 'video/ogg'],
        50 * 1024 * 1024, 'hero_video', $slide['video_background'] ?? null);
    
    $videoPosterResult = handleFileUpload('video_poster',
        ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        5 * 1024 * 1024, 'hero_video_poster', $slide['video_poster'] ?? null);
    
    // Check for upload errors
    if (is_array($imageResult) && isset($imageResult['error'])) {
        $error = $imageResult['error'];
    } elseif (is_array($mobileImageResult) && isset($mobileImageResult['error'])) {
        $error = $mobileImageResult['error'];
    } elseif (is_array($tabletImageResult) && isset($tabletImageResult['error'])) {
        $error = $tabletImageResult['error'];
    } elseif (is_array($videoResult) && isset($videoResult['error'])) {
        $error = $videoResult['error'];
    } elseif (is_array($videoPosterResult) && isset($videoPosterResult['error'])) {
        $error = $videoPosterResult['error'];
    } else {
        // Prepare data array with all fields
        $data = [
            // Basic fields
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'mobile_title' => trim($_POST['mobile_title'] ?? '') ?: null,
            'mobile_description' => trim($_POST['mobile_description'] ?? '') ?: null,
            
            // Buttons
            'button1_text' => trim($_POST['button1_text'] ?? '') ?: null,
            'button1_url' => trim($_POST['button1_url'] ?? '') ?: null,
            'button1_style' => trim($_POST['button1_style'] ?? 'primary'),
            'button2_text' => trim($_POST['button2_text'] ?? '') ?: null,
            'button2_url' => trim($_POST['button2_url'] ?? '') ?: null,
            'button2_style' => trim($_POST['button2_style'] ?? 'secondary'),
            
            // Backgrounds
            'background_image' => is_string($imageResult) ? $imageResult : ($_POST['background_image'] ?? ($slide['background_image'] ?? null)),
            'image_mobile' => is_string($mobileImageResult) ? $mobileImageResult : ($_POST['image_mobile'] ?? ($slide['image_mobile'] ?? null)),
            'image_tablet' => is_string($tabletImageResult) ? $tabletImageResult : ($_POST['image_tablet'] ?? ($slide['image_tablet'] ?? null)),
            'background_gradient_start' => trim($_POST['background_gradient_start'] ?? '') ?: null,
            'background_gradient_end' => trim($_POST['background_gradient_end'] ?? '') ?: null,
            
            // Video
            'video_background' => is_string($videoResult) ? $videoResult : ($_POST['video_background'] ?? ($slide['video_background'] ?? null)),
            'video_poster' => is_string($videoPosterResult) ? $videoPosterResult : ($_POST['video_poster'] ?? ($slide['video_poster'] ?? null)),
            
            // Effects & Animations
            'transition_effect' => trim($_POST['transition_effect'] ?? 'fade'),
            'text_animation' => trim($_POST['text_animation'] ?? 'fadeInUp'),
            'parallax_enabled' => isset($_POST['parallax_enabled']) ? 1 : 0,
            
            // Layout & Design
            'template' => trim($_POST['template'] ?? 'default'),
            'content_layout' => trim($_POST['content_layout'] ?? 'center'),
            'overlay_pattern' => trim($_POST['overlay_pattern'] ?? '') ?: null,
            'content_transparency' => isset($_POST['content_transparency']) ? (float)$_POST['content_transparency'] : 0.02,
            
            // Badges & Labels
            'badge_text' => trim($_POST['badge_text'] ?? '') ?: null,
            'badge_color' => trim($_POST['badge_color'] ?? 'blue'),
            
            // Social & Countdown
            'social_sharing' => isset($_POST['social_sharing']) ? 1 : 0,
            'countdown_enabled' => isset($_POST['countdown_enabled']) ? 1 : 0,
            'countdown_date' => !empty($_POST['countdown_date']) ? $_POST['countdown_date'] : null,
            
            // Scheduling
            'scheduled_start' => !empty($_POST['scheduled_start']) ? $_POST['scheduled_start'] : null,
            'scheduled_end' => !empty($_POST['scheduled_end']) ? $_POST['scheduled_end'] : null,
            
            // Advanced
            'slide_group' => trim($_POST['slide_group'] ?? '') ?: null,
            'ab_test_variant' => trim($_POST['ab_test_variant'] ?? '') ?: null,
            'custom_font' => trim($_POST['custom_font'] ?? '') ?: null,
            'auto_height' => isset($_POST['auto_height']) ? 1 : 0,
            'dark_mode' => isset($_POST['dark_mode']) ? 1 : 0,
            
            // Status
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'display_order' => (int)($_POST['display_order'] ?? 0),
        ];
        
        if (empty($data['title'])) {
            $error = 'Title is required.';
        } else {
            try {
                if ($slideId) {
                    $updated = $heroSliderModel->update($slideId, $data);
                    if ($updated) {
                        $message = 'Slide updated successfully.';
                        $slide = $heroSliderModel->getById($slideId);
                    } else {
                        $error = 'Failed to update slide.';
                    }
                } else {
                    $newId = $heroSliderModel->create($data);
                    if ($newId) {
                        $message = 'Slide created successfully.';
                        header('Location: ' . url('admin/hero-slider.php'));
                        exit;
                    } else {
                        $error = 'Failed to create slide.';
                    }
                }
            } catch (Exception $e) {
                $error = 'Error saving slide: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = $slideId ? 'Edit Hero Slide' : 'Add Hero Slide';
include __DIR__ . '/includes/header.php';

// Get options from helper
$transitions = HeroSliderHelper::getTransitionEffects();
$textAnimations = HeroSliderHelper::getTextAnimations();
$layouts = HeroSliderHelper::getContentLayouts();
$templates = HeroSliderHelper::getTemplates();
$buttonStyles = HeroSliderHelper::getButtonStyles();
$overlayPatterns = HeroSliderHelper::getOverlayPatterns();
$badgeColors = HeroSliderHelper::getBadgeColors();
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold"><?= $slideId ? 'Edit Hero Slide' : 'Add New Hero Slide' ?></h1>
        <a href="hero-slider.php" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Slides
        </a>
    </div>
    
    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= escape($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= escape($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-md p-6">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="flex space-x-8" aria-label="Tabs">
                <button type="button" onclick="showTab('basic')" id="tab-basic" class="tab-button active border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600">
                    <i class="fas fa-info-circle mr-2"></i> Basic Info
                </button>
                <button type="button" onclick="showTab('background')" id="tab-background" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-image mr-2"></i> Background
                </button>
                <button type="button" onclick="showTab('animation')" id="tab-animation" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-magic mr-2"></i> Animation
                </button>
                <button type="button" onclick="showTab('buttons')" id="tab-buttons" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-mouse-pointer mr-2"></i> Buttons
                </button>
                <button type="button" onclick="showTab('advanced')" id="tab-advanced" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-cog mr-2"></i> Advanced
                </button>
            </nav>
        </div>
        
        <!-- Tab Content -->
        <!-- Basic Info Tab -->
        <div id="tab-content-basic" class="tab-content">
            <div class="grid md:grid-cols-2 gap-6">
                <div class="space-y-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" value="<?= escape($slide['title'] ?? '') ?>" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="description" name="description" rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= escape($slide['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div>
                        <label for="template" class="block text-sm font-medium text-gray-700 mb-2">Template</label>
                        <select id="template" name="template" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <?php foreach ($templates as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($slide['template'] ?? 'default') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="content_layout" class="block text-sm font-medium text-gray-700 mb-2">Content Layout</label>
                        <select id="content_layout" name="content_layout" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <?php foreach ($layouts as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($slide['content_layout'] ?? 'center') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <label for="display_order" class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="<?= escape($slide['display_order'] ?? 0) ?>" min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="slide_group" class="block text-sm font-medium text-gray-700 mb-2">Slide Group</label>
                        <input type="text" id="slide_group" name="slide_group" value="<?= escape($slide['slide_group'] ?? '') ?>" placeholder="e.g., homepage, promotions"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Group slides together for filtering</p>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" <?= (!isset($slide) || $slide['is_active']) ? 'checked' : '' ?> class="mr-2">
                            <span class="text-sm font-medium text-gray-700">Active</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="auto_height" value="1" <?= ($slide['auto_height'] ?? 0) ? 'checked' : '' ?> class="mr-2">
                            <span class="text-sm font-medium text-gray-700">Auto Height</span>
                        </label>
                        <p class="text-xs text-gray-500 ml-6">Adjust height based on content</p>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="dark_mode" value="1" <?= ($slide['dark_mode'] ?? 0) ? 'checked' : '' ?> class="mr-2">
                            <span class="text-sm font-medium text-gray-700">Dark Mode</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Background Tab -->
        <div id="tab-content-background" class="tab-content hidden">
            <div class="space-y-6">
                <div class="grid md:grid-cols-3 gap-6">
                    <div>
                        <label for="background_image" class="block text-sm font-medium text-gray-700 mb-2">Desktop Image</label>
                        <input type="file" id="background_image" name="background_image" accept="image/*"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <?php if (!empty($slide['background_image'])): ?>
                            <img src="<?= url($slide['background_image']) ?>" alt="Current" class="mt-2 max-w-full h-24 object-cover rounded">
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="image_tablet" class="block text-sm font-medium text-gray-700 mb-2">Tablet Image</label>
                        <input type="file" id="image_tablet" name="image_tablet" accept="image/*"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <?php if (!empty($slide['image_tablet'])): ?>
                            <img src="<?= url($slide['image_tablet']) ?>" alt="Current" class="mt-2 max-w-full h-24 object-cover rounded">
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="image_mobile" class="block text-sm font-medium text-gray-700 mb-2">Mobile Image</label>
                        <input type="file" id="image_mobile" name="image_mobile" accept="image/*"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <?php if (!empty($slide['image_mobile'])): ?>
                            <img src="<?= url($slide['image_mobile']) ?>" alt="Current" class="mt-2 max-w-full h-24 object-cover rounded">
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="background_gradient_start" class="block text-sm font-medium text-gray-700 mb-2">Gradient Start</label>
                        <input type="text" id="background_gradient_start" name="background_gradient_start"
                               value="<?= escape($slide['background_gradient_start'] ?? 'rgba(37, 99, 235, 0.9)') ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label for="background_gradient_end" class="block text-sm font-medium text-gray-700 mb-2">Gradient End</label>
                        <input type="text" id="background_gradient_end" name="background_gradient_end"
                               value="<?= escape($slide['background_gradient_end'] ?? 'rgba(79, 70, 229, 0.9)') ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                
                <div class="border-t pt-6">
                    <h3 class="font-semibold mb-4">Video Background</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="video_background" class="block text-sm font-medium text-gray-700 mb-2">Video File (MP4/WebM)</label>
                            <input type="file" id="video_background" name="video_background" accept="video/*"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <?php if (!empty($slide['video_background'])): ?>
                                <p class="text-xs text-gray-500 mt-1">Current: <?= basename($slide['video_background']) ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <label for="video_poster" class="block text-sm font-medium text-gray-700 mb-2">Video Poster Image</label>
                            <input type="file" id="video_poster" name="video_poster" accept="image/*"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <?php if (!empty($slide['video_poster'])): ?>
                                <img src="<?= url($slide['video_poster']) ?>" alt="Poster" class="mt-2 max-w-full h-24 object-cover rounded">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label for="overlay_pattern" class="block text-sm font-medium text-gray-700 mb-2">Overlay Pattern</label>
                    <select id="overlay_pattern" name="overlay_pattern" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <?php foreach ($overlayPatterns as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($slide['overlay_pattern'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Animation Tab -->
        <div id="tab-content-animation" class="tab-content hidden">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label for="transition_effect" class="block text-sm font-medium text-gray-700 mb-2">Transition Effect</label>
                    <select id="transition_effect" name="transition_effect" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <?php foreach ($transitions as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($slide['transition_effect'] ?? 'fade') === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="text_animation" class="block text-sm font-medium text-gray-700 mb-2">Text Animation</label>
                    <select id="text_animation" name="text_animation" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <?php foreach ($textAnimations as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($slide['text_animation'] ?? 'fadeInUp') === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="parallax_enabled" value="1" <?= ($slide['parallax_enabled'] ?? 0) ? 'checked' : '' ?> class="mr-2">
                        <span class="text-sm font-medium text-gray-700">Enable Parallax Effect</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Buttons Tab -->
        <div id="tab-content-buttons" class="tab-content hidden">
            <div class="grid md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h3 class="font-semibold">Button 1 (Primary)</h3>
                    <div>
                        <label for="button1_text" class="block text-sm font-medium text-gray-700 mb-2">Text</label>
                        <input type="text" id="button1_text" name="button1_text" value="<?= escape($slide['button1_text'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="button1_url" class="block text-sm font-medium text-gray-700 mb-2">URL</label>
                        <input type="text" id="button1_url" name="button1_url" value="<?= escape($slide['button1_url'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="button1_style" class="block text-sm font-medium text-gray-700 mb-2">Style</label>
                        <select id="button1_style" name="button1_style" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <?php foreach ($buttonStyles as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($slide['button1_style'] ?? 'primary') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <h3 class="font-semibold">Button 2 (Secondary)</h3>
                    <div>
                        <label for="button2_text" class="block text-sm font-medium text-gray-700 mb-2">Text</label>
                        <input type="text" id="button2_text" name="button2_text" value="<?= escape($slide['button2_text'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="button2_url" class="block text-sm font-medium text-gray-700 mb-2">URL</label>
                        <input type="text" id="button2_url" name="button2_url" value="<?= escape($slide['button2_url'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="button2_style" class="block text-sm font-medium text-gray-700 mb-2">Style</label>
                        <select id="button2_style" name="button2_style" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <?php foreach ($buttonStyles as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($slide['button2_style'] ?? 'secondary') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Advanced Tab -->
        <div id="tab-content-advanced" class="tab-content hidden">
            <div class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="mobile_title" class="block text-sm font-medium text-gray-700 mb-2">Mobile Title (Optional)</label>
                        <input type="text" id="mobile_title" name="mobile_title" value="<?= escape($slide['mobile_title'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">Different title for mobile devices</p>
                    </div>
                    
                    <div>
                        <label for="mobile_description" class="block text-sm font-medium text-gray-700 mb-2">Mobile Description</label>
                        <textarea id="mobile_description" name="mobile_description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg"><?= escape($slide['mobile_description'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="border-t pt-6">
                    <h3 class="font-semibold mb-4">Badge/Label</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="badge_text" class="block text-sm font-medium text-gray-700 mb-2">Badge Text</label>
                            <input type="text" id="badge_text" name="badge_text" value="<?= escape($slide['badge_text'] ?? '') ?>" placeholder="New, Sale, Featured"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label for="badge_color" class="block text-sm font-medium text-gray-700 mb-2">Badge Color</label>
                            <select id="badge_color" name="badge_color" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <?php foreach ($badgeColors as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= ($slide['badge_color'] ?? 'blue') === $value ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-6">
                    <h3 class="font-semibold mb-4">Scheduling</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="scheduled_start" class="block text-sm font-medium text-gray-700 mb-2">Start Date/Time</label>
                            <input type="datetime-local" id="scheduled_start" name="scheduled_start"
                                   value="<?= !empty($slide['scheduled_start']) ? date('Y-m-d\TH:i', strtotime($slide['scheduled_start'])) : '' ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label for="scheduled_end" class="block text-sm font-medium text-gray-700 mb-2">End Date/Time</label>
                            <input type="datetime-local" id="scheduled_end" name="scheduled_end"
                                   value="<?= !empty($slide['scheduled_end']) ? date('Y-m-d\TH:i', strtotime($slide['scheduled_end'])) : '' ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-6">
                    <h3 class="font-semibold mb-4">Countdown Timer</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="countdown_enabled" value="1" <?= ($slide['countdown_enabled'] ?? 0) ? 'checked' : '' ?> class="mr-2">
                                <span class="text-sm font-medium text-gray-700">Enable Countdown</span>
                            </label>
                        </div>
                        <div>
                            <label for="countdown_date" class="block text-sm font-medium text-gray-700 mb-2">Countdown To</label>
                            <input type="datetime-local" id="countdown_date" name="countdown_date"
                                   value="<?= !empty($slide['countdown_date']) ? date('Y-m-d\TH:i', strtotime($slide['countdown_date'])) : '' ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-6">
                    <h3 class="font-semibold mb-4">Additional Options</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="social_sharing" value="1" <?= ($slide['social_sharing'] ?? 0) ? 'checked' : '' ?> class="mr-2">
                                <span class="text-sm font-medium text-gray-700">Enable Social Sharing</span>
                            </label>
                        </div>
                        <div>
                            <label for="custom_font" class="block text-sm font-medium text-gray-700 mb-2">Custom Font</label>
                            <input type="text" id="custom_font" name="custom_font" value="<?= escape($slide['custom_font'] ?? '') ?>" placeholder="e.g., 'Roboto', sans-serif"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label for="ab_test_variant" class="block text-sm font-medium text-gray-700 mb-2">A/B Test Variant</label>
                            <input type="text" id="ab_test_variant" name="ab_test_variant" value="<?= escape($slide['ab_test_variant'] ?? '') ?>" placeholder="e.g., variant-a"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label for="content_transparency" class="block text-sm font-medium text-gray-700 mb-2">Content Transparency</label>
                            <input type="range" id="content_transparency" name="content_transparency" min="0" max="1" step="0.01"
                                   value="<?= escape($slide['content_transparency'] ?? 0.02) ?>" oninput="updateTransparency(this.value)"
                                   class="w-full">
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>0%</span>
                                <span id="transparency-display"><?= number_format((float)($slide['content_transparency'] ?? 0.02) * 100, 0) ?>%</span>
                                <span>100%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-8 flex justify-end gap-4">
            <a href="hero-slider.php" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i> <?= $slideId ? 'Update Slide' : 'Create Slide' ?>
            </button>
        </div>
    </form>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab
    document.getElementById('tab-content-' + tabName).classList.remove('hidden');
    const btn = document.getElementById('tab-' + tabName);
    btn.classList.add('active', 'border-blue-500', 'text-blue-600');
    btn.classList.remove('border-transparent', 'text-gray-500');
}

function updateTransparency(value) {
    document.getElementById('transparency-display').textContent = Math.round(value * 100) + '%';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

