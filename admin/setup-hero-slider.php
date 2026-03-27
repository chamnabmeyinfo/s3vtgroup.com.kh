<?php
/**
 * Hero Slider Setup Script
 * Run this once to create the hero_slides table
 */

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\HeroSlider;

$pageTitle = 'Setup Hero Slider';
include __DIR__ . '/includes/header.php';

$message = '';
$error = '';
$success = false;

// Check if table already exists
$tableExists = false;
try {
    db()->fetchOne("SELECT 1 FROM hero_slides LIMIT 1");
    $tableExists = true;
} catch (Exception $e) {
    $tableExists = false;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_table'])) {
    try {
        $sql = "
        CREATE TABLE IF NOT EXISTS `hero_slides` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `button1_text` VARCHAR(100) DEFAULT NULL,
            `button1_url` VARCHAR(255) DEFAULT NULL,
            `button2_text` VARCHAR(100) DEFAULT NULL,
            `button2_url` VARCHAR(255) DEFAULT NULL,
            `background_image` VARCHAR(255) DEFAULT NULL,
            `background_gradient_start` VARCHAR(50) DEFAULT NULL,
            `background_gradient_end` VARCHAR(50) DEFAULT NULL,
            `content_transparency` DECIMAL(3,2) DEFAULT 0.10,
            `is_active` TINYINT(1) DEFAULT 1,
            `display_order` INT(11) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_active` (`is_active`),
            INDEX `idx_display_order` (`display_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        db()->query($sql);
        
        // Add transparency column if it doesn't exist (for existing tables)
        try {
            db()->fetchOne("SELECT content_transparency FROM hero_slides LIMIT 1");
        } catch (Exception $e) {
            // Column doesn't exist, add it
            try {
                db()->query("ALTER TABLE `hero_slides` ADD COLUMN `content_transparency` DECIMAL(3,2) DEFAULT 0.10 AFTER `background_gradient_end`");
                db()->query("UPDATE `hero_slides` SET `content_transparency` = 0.10 WHERE `content_transparency` IS NULL");
            } catch (Exception $e2) {
                // Ignore if column already exists or other error
            }
        }
        
        // Insert default slides if table was just created
        if (!$tableExists) {
            $defaultSlides = [
                [
                    'title' => 'Premium Forklifts & Industrial Equipment',
                    'description' => 'Discover our extensive range of high-quality forklifts, material handling equipment, and industrial solutions designed to power your business.',
                    'button1_text' => 'Shop Now',
                    'button1_url' => 'products.php',
                    'button2_text' => 'Get Quote',
                    'button2_url' => 'quote.php',
                    'background_gradient_start' => 'rgba(37, 99, 235, 0.9)',
                    'background_gradient_end' => 'rgba(79, 70, 229, 0.9)',
                    'content_transparency' => 0.10,
                    'display_order' => 1
                ],
                [
                    'title' => 'Expert Support & Maintenance',
                    'description' => '24/7 customer support and professional maintenance services to keep your equipment running at peak performance.',
                    'button1_text' => 'Contact Us',
                    'button1_url' => 'contact.php',
                    'button2_text' => 'Browse Products',
                    'button2_url' => 'products.php',
                    'background_gradient_start' => 'rgba(16, 185, 129, 0.9)',
                    'background_gradient_end' => 'rgba(5, 150, 105, 0.9)',
                    'content_transparency' => 0.10,
                    'display_order' => 2
                ],
                [
                    'title' => 'Quality You Can Trust',
                    'description' => 'All our equipment is thoroughly inspected and certified to meet the highest industry standards for safety and performance.',
                    'button1_text' => 'Explore Quality',
                    'button1_url' => 'products.php',
                    'button2_text' => 'Read Reviews',
                    'button2_url' => 'testimonials.php',
                    'background_gradient_start' => 'rgba(139, 92, 246, 0.9)',
                    'background_gradient_end' => 'rgba(124, 58, 237, 0.9)',
                    'content_transparency' => 0.10,
                    'display_order' => 3
                ],
                [
                    'title' => 'Fast Delivery & Installation',
                    'description' => 'Quick shipping and professional installation services to get your equipment up and running when you need it most.',
                    'button1_text' => 'Shop Now',
                    'button1_url' => 'products.php',
                    'button2_text' => 'Learn More',
                    'button2_url' => 'contact.php',
                    'background_gradient_start' => 'rgba(236, 72, 153, 0.9)',
                    'background_gradient_end' => 'rgba(219, 39, 119, 0.9)',
                    'content_transparency' => 0.10,
                    'display_order' => 4
                ]
            ];
            
            $heroSliderModel = new HeroSlider();
            
            foreach ($defaultSlides as $slide) {
                $heroSliderModel->create($slide);
            }
        }
        
        $message = 'Hero slider table created successfully! Default slides have been added.';
        $success = true;
        $tableExists = true;
    } catch (Exception $e) {
        $error = 'Error creating table: ' . $e->getMessage();
    }
}
?>

<div class="p-6">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Hero Slider Setup</h1>
        
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
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                Hero slider table already exists! You can now <a href="hero-slider.php" class="underline font-semibold">manage your slides</a>.
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Create Hero Slider Table</h2>
                <p class="text-gray-600 mb-6">
                    The hero slider table doesn't exist yet. Click the button below to create it along with default slides.
                </p>
                
                <form method="POST">
                    <button type="submit" name="create_table" class="btn-primary">
                        <i class="fas fa-database mr-2"></i> Create Hero Slider Table
                    </button>
                </form>
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

