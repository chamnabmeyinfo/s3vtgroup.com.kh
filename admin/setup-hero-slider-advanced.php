<?php
/**
 * Advanced Hero Slider Features Setup
 * Run this to add all advanced features to existing hero_slides table
 */

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Setup Advanced Hero Slider Features';
include __DIR__ . '/includes/header.php';

$message = '';
$error = '';
$success = false;

// Check for success message from redirect
if (isset($_GET['success'])) {
    $addedCount = isset($_GET['added']) ? (int)$_GET['added'] : 0;
    if ($addedCount > 0) {
        $message = "Advanced features added successfully! $addedCount new column(s) added.";
    } else {
        $message = 'All advanced features are already installed!';
    }
    $success = true;
}

// Check if table exists
$tableExists = false;
try {
    db()->fetchOne("SELECT 1 FROM hero_slides LIMIT 1");
    $tableExists = true;
} catch (Exception $e) {
    $tableExists = false;
}

if (!$tableExists) {
    $error = 'Hero slides table does not exist. Please run the basic setup first.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_features'])) {
    try {
        // Check which columns already exist
        $columns = db()->fetchAll("SHOW COLUMNS FROM hero_slides");
        $existingColumns = array_column($columns, 'Field');
        
        // Define all columns to add with their definitions
        $columnsToAdd = [
            'transition_effect' => "VARCHAR(50) DEFAULT 'fade'",
            'text_animation' => "VARCHAR(50) DEFAULT 'fadeInUp'",
            'video_background' => "VARCHAR(255) DEFAULT NULL",
            'parallax_effect' => "TINYINT(1) DEFAULT 0",
            'content_layout' => "VARCHAR(50) DEFAULT 'center'",
            'mobile_image' => "VARCHAR(255) DEFAULT NULL",
            'tablet_image' => "VARCHAR(255) DEFAULT NULL",
            'start_date' => "DATETIME DEFAULT NULL",
            'end_date' => "DATETIME DEFAULT NULL",
            'overlay_pattern' => "VARCHAR(50) DEFAULT NULL",
            'button1_style' => "VARCHAR(50) DEFAULT 'primary'",
            'button2_style' => "VARCHAR(50) DEFAULT 'secondary'",
            'social_share_buttons' => "TINYINT(1) DEFAULT 0",
            'countdown_timer' => "DATETIME DEFAULT NULL",
            'badge_text' => "VARCHAR(100) DEFAULT NULL",
            'badge_color' => "VARCHAR(50) DEFAULT NULL",
            'mobile_content' => "TEXT DEFAULT NULL",
            'lazy_load' => "TINYINT(1) DEFAULT 1",
            'auto_height' => "TINYINT(1) DEFAULT 0",
            'views' => "INT(11) DEFAULT 0",
            'clicks' => "INT(11) DEFAULT 0",
        ];
        
        $addedCount = 0;
        $skippedCount = 0;
        $errors = [];
        
        // Define column order and positioning
        $columnOrder = [
            'transition_effect' => 'content_transparency',
            'text_animation' => 'transition_effect',
            'video_background' => 'background_image',
            'parallax_effect' => 'video_background',
            'content_layout' => 'parallax_effect',
            'mobile_image' => 'background_image',
            'tablet_image' => 'mobile_image',
            'start_date' => 'display_order',
            'end_date' => 'start_date',
            'overlay_pattern' => 'end_date',
            'button1_style' => 'button1_url',
            'button2_style' => 'button1_style',
            'social_share_buttons' => 'button2_style',
            'countdown_timer' => 'social_share_buttons',
            'badge_text' => 'countdown_timer',
            'badge_color' => 'badge_text',
            'mobile_content' => 'description',
            'lazy_load' => 'badge_color',
            'auto_height' => 'lazy_load',
            'views' => 'auto_height',
            'clicks' => 'views',
        ];
        
        // Add each column if it doesn't exist
        foreach ($columnsToAdd as $columnName => $columnDef) {
            if (!in_array($columnName, $existingColumns)) {
                try {
                    // Determine position - use the order defined above, or fallback to a safe position
                    $afterColumn = $columnOrder[$columnName] ?? 'display_order';
                    
                    // Make sure the after column exists, otherwise use a safe fallback
                    if (!in_array($afterColumn, $existingColumns)) {
                        // Try common fallback columns
                        if (in_array('display_order', $existingColumns)) {
                            $afterColumn = 'display_order';
                        } elseif (in_array('updated_at', $existingColumns)) {
                            $afterColumn = 'updated_at';
                        } else {
                            // Last resort: add at the end
                            $afterColumn = null;
                        }
                    }
                    
                    if ($afterColumn) {
                        $sql = "ALTER TABLE `hero_slides` ADD COLUMN `{$columnName}` {$columnDef} AFTER `{$afterColumn}`";
                    } else {
                        $sql = "ALTER TABLE `hero_slides` ADD COLUMN `{$columnName}` {$columnDef}";
                    }
                    
                    db()->query($sql);
                    $addedCount++;
                    error_log("Added column: $columnName after $afterColumn");
                    
                    // Update existing columns list for next iteration
                    $existingColumns[] = $columnName;
                } catch (Exception $e) {
                    $errorMsg = $e->getMessage();
                    // Check if it's a duplicate column error (might have been added between checks)
                    if (strpos($errorMsg, 'Duplicate column') !== false || 
                        strpos($errorMsg, 'already exists') !== false ||
                        strpos($errorMsg, 'Duplicate column name') !== false) {
                        $skippedCount++;
                        error_log("Column $columnName already exists, skipping.");
                        $existingColumns[] = $columnName; // Add to list to prevent retry
                    } else {
                        $errors[] = "$columnName: " . $errorMsg;
                        error_log("Error adding column $columnName: " . $errorMsg);
                        error_log("SQL was: $sql");
                    }
                }
            } else {
                $skippedCount++;
            }
        }
        
        if (count($errors) > 0) {
            $error = 'Some columns could not be added: ' . implode(', ', $errors);
            if ($addedCount > 0) {
                $error .= " ($addedCount column(s) were added successfully)";
            }
        } elseif ($addedCount > 0) {
            $message = "Advanced features added successfully! $addedCount new column(s) added.";
            $success = true;
            // Redirect to prevent form resubmission
            header('Location: ' . url('admin/setup-hero-slider-advanced.php') . '?success=1&added=' . $addedCount);
            exit;
        } elseif ($skippedCount > 0) {
            $message = 'All advanced features are already installed!';
            $success = true;
        } else {
            $error = 'No columns were added. Please check the database connection.';
        }
    } catch (Exception $e) {
        error_log("Setup Advanced Features Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $error = 'Error adding features: ' . $e->getMessage();
    }
}

// Check which columns exist
$existingColumns = [];
if ($tableExists) {
    try {
        $columns = db()->fetchAll("SHOW COLUMNS FROM hero_slides");
        $existingColumns = array_column($columns, 'Field');
    } catch (Exception $e) {
        // Ignore
    }
}

// Define all advanced columns that should exist
$newColumns = [
    'transition_effect', 'text_animation', 'video_background', 'parallax_effect',
    'content_layout', 'mobile_image', 'tablet_image', 'start_date', 'end_date',
    'overlay_pattern', 'button1_style', 'button2_style', 'social_share_buttons',
    'countdown_timer', 'badge_text', 'badge_color', 'mobile_content',
    'lazy_load', 'auto_height', 'views', 'clicks'
];

$missingColumns = array_diff($newColumns, $existingColumns);
?>

<div class="p-6">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Advanced Hero Slider Features Setup</h1>
        
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
        
        <?php if ($tableExists): ?>
            <?php if (empty($missingColumns)): ?>
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle mr-2"></i>
                    All advanced features are already installed! You can now use all the new options.
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Add Advanced Features</h2>
                    <p class="text-gray-600 mb-4">
                        The following features will be added to your hero slider:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 mb-6 space-y-2">
                        <li>Multiple transition effects (fade, slide, zoom, cube, flip, etc.)</li>
                        <li>Video background support</li>
                        <li>Slide templates</li>
                        <li>Responsive images (mobile/tablet/desktop)</li>
                        <li>Scheduled slides (date/time)</li>
                        <li>Text animations</li>
                        <li>Parallax scrolling</li>
                        <li>Multiple content layouts</li>
                        <li>Overlay patterns</li>
                        <li>Button style options</li>
                        <li>Social sharing</li>
                        <li>Countdown timers</li>
                        <li>Badges/labels</li>
                        <li>Mobile-specific content</li>
                        <li>Custom fonts</li>
                        <li>Slide groups</li>
                        <li>A/B testing</li>
                        <li>Auto-height adjustment</li>
                        <li>Dark mode support</li>
                    </ul>
                    
                    <p class="text-sm text-gray-500 mb-6">
                        Missing columns: <?= count($missingColumns) ?>
                    </p>
                    
                    <form method="POST">
                        <button type="submit" name="add_features" class="btn-primary">
                            <i class="fas fa-magic mr-2"></i> Add Advanced Features
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                Please run the basic hero slider setup first.
            </div>
        <?php endif; ?>
        
        <div class="mt-6">
            <a href="hero-slider.php" class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i> Back to Hero Slider
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

