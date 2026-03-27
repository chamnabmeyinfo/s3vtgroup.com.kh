<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\HeroSlider;

// Check if table exists, redirect to setup if not
try {
    db()->fetchOne("SELECT 1 FROM hero_slides LIMIT 1");
} catch (Exception $e) {
    header('Location: ' . url('admin/setup-hero-slider.php'));
    exit;
}

$heroSliderModel = new HeroSlider();
$message = '';
$error = '';

// Default values (defined early for use in update handler)
$defaultSettings = [
    'hero_slider_autoplay_delay' => 5000,
    'hero_slider_default_transparency' => 0.02,
    'hero_slider_show_arrows' => 1,
    'hero_slider_show_dots' => 1,
    'hero_slider_show_progress' => 1,
    'hero_slider_pause_on_hover' => 1,
    'hero_slider_transition_speed' => 800,
    'hero_slider_enable_keyboard' => 1,
    'hero_slider_enable_touch' => 1,
];

// Handle delete
if (!empty($_GET['delete'])) {
    try {
        $slideId = (int)$_GET['delete'];
        if ($slideId <= 0) {
            $error = 'Invalid slide ID.';
        } else {
            $heroSliderModel->delete($slideId);
            $message = 'Slide deleted successfully.';
        }
    } catch (\Exception $e) {
        $error = 'Error deleting slide: ' . $e->getMessage();
    }
}

// Handle toggle active
if (!empty($_GET['toggle'])) {
    try {
        $slideId = (int)$_GET['toggle'];
        if ($slideId > 0) {
            $heroSliderModel->toggleActive($slideId);
            $message = 'Slide status updated.';
        }
    } catch (\Exception $e) {
        $error = 'Error updating slide: ' . $e->getMessage();
    }
}

// Handle order update
if (!empty($_POST['update_order'])) {
    try {
        $orders = $_POST['order'] ?? [];
        foreach ($orders as $id => $order) {
            $heroSliderModel->updateOrder((int)$id, (int)$order);
        }
        $message = 'Display order updated successfully.';
    } catch (\Exception $e) {
        $error = 'Error updating order: ' . $e->getMessage();
    }
}

// Handle bulk operations
if (!empty($_POST['bulk_action']) && !empty($_POST['selected_slides'])) {
    try {
        $action = $_POST['bulk_action'];
        $selectedIds = array_map('intval', $_POST['selected_slides']);
        
        if ($action === 'activate') {
            $heroSliderModel->bulkUpdate($selectedIds, ['is_active' => 1]);
            $message = count($selectedIds) . ' slide(s) activated.';
        } elseif ($action === 'deactivate') {
            $heroSliderModel->bulkUpdate($selectedIds, ['is_active' => 0]);
            $message = count($selectedIds) . ' slide(s) deactivated.';
        } elseif ($action === 'delete') {
            foreach ($selectedIds as $id) {
                $heroSliderModel->delete($id);
            }
            $message = count($selectedIds) . ' slide(s) deleted.';
        }
    } catch (\Exception $e) {
        $error = 'Error performing bulk action: ' . $e->getMessage();
    }
}

// Handle general settings update
if (!empty($_POST['update_settings'])) {
    try {
        // Debug: Log what we received
        error_log("Hero Slider Settings POST data: " . print_r($_POST, true));
        
        // Prepare settings to update with proper type casting and validation
        // Always use POST values if provided, otherwise use defaults
        $settingsToUpdate = [
            'hero_slider_autoplay_delay' => isset($_POST['autoplay_delay']) && $_POST['autoplay_delay'] !== '' 
                ? max(1000, min(30000, (int)$_POST['autoplay_delay'])) 
                : $defaultSettings['hero_slider_autoplay_delay'],
            'hero_slider_default_transparency' => isset($_POST['default_transparency']) && $_POST['default_transparency'] !== '' 
                ? max(0, min(1, (float)$_POST['default_transparency'])) 
                : $defaultSettings['hero_slider_default_transparency'],
            'hero_slider_show_arrows' => isset($_POST['show_arrows']) && $_POST['show_arrows'] == '1' ? 1 : 0,
            'hero_slider_show_dots' => isset($_POST['show_dots']) && $_POST['show_dots'] == '1' ? 1 : 0,
            'hero_slider_show_progress' => isset($_POST['show_progress']) && $_POST['show_progress'] == '1' ? 1 : 0,
            'hero_slider_pause_on_hover' => isset($_POST['pause_on_hover']) && $_POST['pause_on_hover'] == '1' ? 1 : 0,
            'hero_slider_transition_speed' => isset($_POST['transition_speed']) && $_POST['transition_speed'] !== '' 
                ? max(200, min(2000, (int)$_POST['transition_speed'])) 
                : $defaultSettings['hero_slider_transition_speed'],
            'hero_slider_enable_keyboard' => isset($_POST['enable_keyboard']) && $_POST['enable_keyboard'] == '1' ? 1 : 0,
            'hero_slider_enable_touch' => isset($_POST['enable_touch']) && $_POST['enable_touch'] == '1' ? 1 : 0,
        ];
        
        // Debug: Log what we're about to save
        error_log("Hero Slider Settings to save: " . print_r($settingsToUpdate, true));
        
        // Update or insert each setting
        $updateCount = 0;
        $errors = [];
        foreach ($settingsToUpdate as $key => $value) {
            try {
                $existing = db()->fetchOne("SELECT id, value FROM settings WHERE `key` = :key", ['key' => $key]);
                if ($existing) {
                    // Always update, even if value appears the same (handles type conversion)
                    $oldValue = $existing['value'];
                    $newValue = (string)$value;
                    
                    // Only update if value actually changed
                    if ((string)$oldValue !== $newValue) {
                        $result = db()->update('settings', ['value' => $newValue], '`key` = :key', ['key' => $key]);
                        error_log("Updated setting $key: '$oldValue' -> '$newValue' (rowCount: $result)");
                        $updateCount++;
                    } else {
                        error_log("Setting $key unchanged: '$oldValue'");
                        $updateCount++; // Count as processed
                    }
                } else {
                    // Insert new setting
                    $result = db()->insert('settings', [
                        'key' => $key,
                        'value' => (string)$value,
                        'type' => 'text'
                    ]);
                    if ($result) {
                        error_log("Inserted new setting $key: '$value'");
                        $updateCount++;
                    } else {
                        $errors[] = "Failed to insert setting: $key";
                        error_log("Failed to insert setting: $key = $value");
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Error saving $key: " . $e->getMessage();
                error_log("Error saving setting $key: " . $e->getMessage());
            }
        }
        
        if (count($errors) > 0) {
            $error = 'Some settings could not be saved: ' . implode(', ', $errors);
        } elseif ($updateCount === 0) {
            $error = 'No settings were processed. Please check your input values.';
        } else {
            // Reload settings to show updated values
            $settingsData = db()->fetchAll("SELECT `key`, value FROM settings WHERE `key` LIKE 'hero_slider_%'");
            $sliderSettings = [];
            foreach ($settingsData as $setting) {
                $sliderSettings[$setting['key']] = $setting['value'];
            }
            
            // Merge with defaults for any missing settings
            foreach ($defaultSettings as $key => $default) {
                if (!isset($sliderSettings[$key])) {
                    $sliderSettings[$key] = $default;
                }
            }
            
            // Ensure proper typing for display
            $sliderSettings['hero_slider_autoplay_delay'] = (int)($sliderSettings['hero_slider_autoplay_delay'] ?? $defaultSettings['hero_slider_autoplay_delay']);
            $sliderSettings['hero_slider_default_transparency'] = (float)($sliderSettings['hero_slider_default_transparency'] ?? $defaultSettings['hero_slider_default_transparency']);
            $sliderSettings['hero_slider_show_arrows'] = (int)($sliderSettings['hero_slider_show_arrows'] ?? $defaultSettings['hero_slider_show_arrows']);
            $sliderSettings['hero_slider_show_dots'] = (int)($sliderSettings['hero_slider_show_dots'] ?? $defaultSettings['hero_slider_show_dots']);
            $sliderSettings['hero_slider_show_progress'] = (int)($sliderSettings['hero_slider_show_progress'] ?? $defaultSettings['hero_slider_show_progress']);
            $sliderSettings['hero_slider_pause_on_hover'] = (int)($sliderSettings['hero_slider_pause_on_hover'] ?? $defaultSettings['hero_slider_pause_on_hover']);
            $sliderSettings['hero_slider_transition_speed'] = (int)($sliderSettings['hero_slider_transition_speed'] ?? $defaultSettings['hero_slider_transition_speed']);
            $sliderSettings['hero_slider_enable_keyboard'] = (int)($sliderSettings['hero_slider_enable_keyboard'] ?? $defaultSettings['hero_slider_enable_keyboard']);
            $sliderSettings['hero_slider_enable_touch'] = (int)($sliderSettings['hero_slider_enable_touch'] ?? $defaultSettings['hero_slider_enable_touch']);
            
            $message = 'General settings updated successfully. ' . $updateCount . ' setting(s) saved.';
            
            // Redirect to prevent form resubmission
            header('Location: ' . url('admin/hero-slider.php') . '?settings_saved=1');
            exit;
        }
    } catch (\Exception $e) {
        error_log("Hero Slider Settings Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $error = 'Error updating settings: ' . $e->getMessage();
    }
}

// Check for success message from redirect
if (isset($_GET['settings_saved'])) {
    $message = 'General settings updated successfully.';
}

// Get current settings (only if not already set by update handler)
if (!isset($sliderSettings) || empty($sliderSettings)) {
    $settingsData = db()->fetchAll("SELECT `key`, value FROM settings WHERE `key` LIKE 'hero_slider_%'");
    $sliderSettings = [];
    foreach ($settingsData as $setting) {
        $sliderSettings[$setting['key']] = $setting['value'];
    }
    
    // Merge with defaults for any missing settings
    foreach ($defaultSettings as $key => $default) {
        if (!isset($sliderSettings[$key])) {
            $sliderSettings[$key] = $default;
        }
    }
}

// Ensure all values are properly typed for display
$sliderSettings['hero_slider_autoplay_delay'] = (int)($sliderSettings['hero_slider_autoplay_delay'] ?? $defaultSettings['hero_slider_autoplay_delay']);
$sliderSettings['hero_slider_default_transparency'] = (float)($sliderSettings['hero_slider_default_transparency'] ?? $defaultSettings['hero_slider_default_transparency']);
$sliderSettings['hero_slider_show_arrows'] = (int)($sliderSettings['hero_slider_show_arrows'] ?? $defaultSettings['hero_slider_show_arrows']);
$sliderSettings['hero_slider_show_dots'] = (int)($sliderSettings['hero_slider_show_dots'] ?? $defaultSettings['hero_slider_show_dots']);
$sliderSettings['hero_slider_show_progress'] = (int)($sliderSettings['hero_slider_show_progress'] ?? $defaultSettings['hero_slider_show_progress']);
$sliderSettings['hero_slider_pause_on_hover'] = (int)($sliderSettings['hero_slider_pause_on_hover'] ?? $defaultSettings['hero_slider_pause_on_hover']);
$sliderSettings['hero_slider_transition_speed'] = (int)($sliderSettings['hero_slider_transition_speed'] ?? $defaultSettings['hero_slider_transition_speed']);
$sliderSettings['hero_slider_enable_keyboard'] = (int)($sliderSettings['hero_slider_enable_keyboard'] ?? $defaultSettings['hero_slider_enable_keyboard']);
$sliderSettings['hero_slider_enable_touch'] = (int)($sliderSettings['hero_slider_enable_touch'] ?? $defaultSettings['hero_slider_enable_touch']);

// Get all slides
$slides = $heroSliderModel->getAll();

$pageTitle = 'Manage Hero Slider';
include __DIR__ . '/includes/header.php';
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Hero Slider</h1>
            <p class="text-sm text-gray-500 mt-1">
                <a href="setup-hero-slider-advanced.php" class="text-blue-600 hover:underline">
                    <i class="fas fa-magic mr-1"></i> Setup Advanced Features
                </a>
            </p>
        </div>
        <a href="hero-slider-edit.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i> Add New Slide
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
    
    <!-- General Settings Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold flex items-center">
                <i class="fas fa-cog mr-2 text-blue-600"></i> General Settings
            </h2>
            <button type="button" onclick="toggleSettings()" class="text-blue-600 hover:text-blue-800 text-sm">
                <i class="fas fa-chevron-down" id="settings-toggle-icon"></i>
            </button>
        </div>
        
        <form method="POST" action="" id="settings-form" class="hidden">
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Auto-play Settings -->
                <div class="space-y-4">
                    <h3 class="font-semibold text-gray-700 border-b pb-2">Auto-play Settings</h3>
                    
                    <div>
                        <label for="autoplay_delay" class="block text-sm font-medium text-gray-700 mb-2">
                            Auto-play Delay (milliseconds)
                        </label>
                        <input type="number" 
                               id="autoplay_delay" 
                               name="autoplay_delay" 
                               value="<?= escape($sliderSettings['hero_slider_autoplay_delay']) ?>"
                               min="1000" 
                               max="30000" 
                               step="500"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Time between slides (1000-30000ms)</p>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="pause_on_hover" 
                                   value="1"
                                   <?= $sliderSettings['hero_slider_pause_on_hover'] ? 'checked' : '' ?>
                                   class="mr-2">
                            <span class="text-sm font-medium text-gray-700">Pause on Hover</span>
                        </label>
                    </div>
                </div>
                
                <!-- Display Settings -->
                <div class="space-y-4">
                    <h3 class="font-semibold text-gray-700 border-b pb-2">Display Settings</h3>
                    
                    <div>
                        <label for="default_transparency" class="block text-sm font-medium text-gray-700 mb-2">
                            Default Transparency
                        </label>
                        <div class="space-y-2">
                            <input type="range" 
                                   id="default_transparency" 
                                   name="default_transparency" 
                                   min="0" 
                                   max="1" 
                                   step="0.01"
                                   value="<?= escape($sliderSettings['hero_slider_default_transparency']) ?>"
                                   oninput="updateTransparencyDisplay(this.value)"
                                   class="w-full">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">0%</span>
                                <span id="transparency_display" class="text-sm font-semibold text-blue-600">
                                    <?= number_format((float)$sliderSettings['hero_slider_default_transparency'] * 100, 0) ?>%
                                </span>
                                <span class="text-xs text-gray-500">100%</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Default glass effect transparency for new slides</p>
                    </div>
                    
                    <div>
                        <label for="transition_speed" class="block text-sm font-medium text-gray-700 mb-2">
                            Transition Speed (milliseconds)
                        </label>
                        <input type="number" 
                               id="transition_speed" 
                               name="transition_speed" 
                               value="<?= escape($sliderSettings['hero_slider_transition_speed']) ?>"
                               min="200" 
                               max="2000" 
                               step="100"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Animation speed between slides</p>
                    </div>
                </div>
                
                <!-- Navigation Settings -->
                <div class="space-y-4">
                    <h3 class="font-semibold text-gray-700 border-b pb-2">Navigation Settings</h3>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="show_arrows" 
                                   value="1"
                                   <?= $sliderSettings['hero_slider_show_arrows'] ? 'checked' : '' ?>
                                   class="mr-2">
                            <span class="text-sm font-medium text-gray-700">Show Navigation Arrows</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="show_dots" 
                                   value="1"
                                   <?= $sliderSettings['hero_slider_show_dots'] ? 'checked' : '' ?>
                                   class="mr-2">
                            <span class="text-sm font-medium text-gray-700">Show Dots Navigation</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="show_progress" 
                                   value="1"
                                   <?= $sliderSettings['hero_slider_show_progress'] ? 'checked' : '' ?>
                                   class="mr-2">
                            <span class="text-sm font-medium text-gray-700">Show Progress Bar</span>
                        </label>
                    </div>
                </div>
                
                <!-- Interaction Settings -->
                <div class="space-y-4">
                    <h3 class="font-semibold text-gray-700 border-b pb-2">Interaction Settings</h3>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="enable_keyboard" 
                                   value="1"
                                   <?= $sliderSettings['hero_slider_enable_keyboard'] ? 'checked' : '' ?>
                                   class="mr-2">
                            <span class="text-sm font-medium text-gray-700">Enable Keyboard Navigation</span>
                        </label>
                        <p class="text-xs text-gray-500 ml-6">Arrow keys to navigate</p>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="enable_touch" 
                                   value="1"
                                   <?= $sliderSettings['hero_slider_enable_touch'] ? 'checked' : '' ?>
                                   class="mr-2">
                            <span class="text-sm font-medium text-gray-700">Enable Touch/Swipe</span>
                        </label>
                        <p class="text-xs text-gray-500 ml-6">Swipe on mobile devices</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="submit" name="update_settings" class="btn-primary">
                    <i class="fas fa-save mr-2"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
    
    <script>
    function toggleSettings() {
        const form = document.getElementById('settings-form');
        const icon = document.getElementById('settings-toggle-icon');
        form.classList.toggle('hidden');
        icon.classList.toggle('fa-chevron-down');
        icon.classList.toggle('fa-chevron-up');
    }
    
    function updateTransparencyDisplay(value) {
        document.getElementById('transparency_display').textContent = Math.round(value * 100) + '%';
    }
    
    // Ensure form is visible and validate before submitting
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('settings-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Make sure form is visible before submitting
                if (form.classList.contains('hidden')) {
                    form.classList.remove('hidden');
                }
                
                // Validate all required fields
                const autoplayDelay = document.getElementById('autoplay_delay');
                const transitionSpeed = document.getElementById('transition_speed');
                
                if (autoplayDelay && (autoplayDelay.value < 1000 || autoplayDelay.value > 30000)) {
                    e.preventDefault();
                    alert('Auto-play delay must be between 1000 and 30000 milliseconds.');
                    autoplayDelay.focus();
                    return false;
                }
                
                if (transitionSpeed && (transitionSpeed.value < 200 || transitionSpeed.value > 2000)) {
                    e.preventDefault();
                    alert('Transition speed must be between 200 and 2000 milliseconds.');
                    transitionSpeed.focus();
                    return false;
                }
                
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
                }
            });
        }
    });
    </script>
    
    <?php if (empty($slides)): ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <i class="fas fa-images text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No slides found</h3>
            <p class="text-gray-500 mb-4">Get started by adding your first hero slide.</p>
            <a href="hero-slider-edit.php" class="btn-primary inline-block">
                <i class="fas fa-plus mr-2"></i> Add First Slide
            </a>
        </div>
    <?php else: ?>
        <form method="POST" action="" id="slides-form">
            <!-- Bulk Actions Bar -->
            <div class="bg-gray-50 border border-gray-200 rounded-t-lg p-4 flex items-center justify-between mb-0" id="bulk-actions-bar" style="display: none;">
                <div class="flex items-center gap-4">
                    <span id="selected-count" class="text-sm font-medium text-gray-700">0 selected</span>
                    <select name="bulk_action" id="bulk_action" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Choose action...</option>
                        <option value="activate">Activate</option>
                        <option value="deactivate">Deactivate</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn-primary btn-sm">
                        <i class="fas fa-check mr-2"></i> Apply
                    </button>
                    <button type="button" onclick="clearSelection()" class="btn-secondary btn-sm">
                        <i class="fas fa-times mr-2"></i> Clear
                    </button>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox" id="select-all" onclick="toggleSelectAll(this)">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Template</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($slides as $slide): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="selected_slides[]" value="<?= $slide['id'] ?>" 
                                       class="slide-checkbox" onchange="updateBulkActions()">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="number" 
                                       name="order[<?= $slide['id'] ?>]" 
                                       value="<?= escape($slide['display_order']) ?>"
                                       class="w-20 px-2 py-1 border border-gray-300 rounded text-sm"
                                       min="0">
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900"><?= escape($slide['title']) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500 max-w-xs truncate">
                                    <?= escape($slide['description'] ?? '') ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs">
                                    <?= escape(ucfirst($slide['template'] ?? 'default')) ?>
                                </span>
                                <?php if (!empty($slide['transition_effect']) && $slide['transition_effect'] !== 'fade'): ?>
                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs ml-1">
                                        <?= escape(ucfirst($slide['transition_effect'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="?toggle=<?= $slide['id'] ?>" 
                                   class="px-2 py-1 text-xs rounded <?= $slide['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= $slide['is_active'] ? 'Active' : 'Inactive' ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                <a href="hero-slider-edit.php?id=<?= $slide['id'] ?>" 
                                   class="text-blue-600 hover:underline">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete=<?= $slide['id'] ?>" 
                                   onclick="return confirm('Are you sure you want to delete this slide?')" 
                                   class="text-red-600 hover:underline">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 flex justify-between">
                <button type="submit" name="update_order" class="btn-primary">
                    <i class="fas fa-save mr-2"></i> Update Order
                </button>
            </div>
        </form>
        
        <script>
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.slide-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateBulkActions();
        }
        
        function updateBulkActions() {
            const checked = document.querySelectorAll('.slide-checkbox:checked');
            const bulkBar = document.getElementById('bulk-actions-bar');
            const countEl = document.getElementById('selected-count');
            
            if (checked.length > 0) {
                bulkBar.style.display = 'flex';
                countEl.textContent = checked.length + ' selected';
            } else {
                bulkBar.style.display = 'none';
            }
        }
        
        function clearSelection() {
            document.querySelectorAll('.slide-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('select-all').checked = false;
            updateBulkActions();
        }
        
        // Confirm bulk delete
        document.getElementById('slides-form').addEventListener('submit', function(e) {
            if (document.getElementById('bulk_action').value === 'delete') {
                if (!confirm('Are you sure you want to delete the selected slides? This cannot be undone.')) {
                    e.preventDefault();
                }
            }
        });
        </script>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

