<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Helpers\PdfCatalogHelper;
use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;

$productModel = new Product();
$categoryModel = new Category();
$settingModel = new Setting();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toEmail = trim($_POST['email'] ?? '');
    $customerName = trim($_POST['name'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $featuredOnly = isset($_POST['featured_only']) ? true : false;
    
    if (empty($toEmail)) {
        $error = 'Please enter a valid email address.';
    } elseif (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Build filters
        $filters = [
            'is_active' => 1
        ];
        
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
            $filters['include_subcategories'] = true;
        }
        
        if ($featuredOnly) {
            $filters['featured'] = true;
        }
        
        try {
            // Generate and send catalog
            $result = PdfCatalogHelper::sendCatalog($toEmail, $customerName, $filters);
            
            if ($result) {
                $message = 'Catalog has been sent successfully to ' . escape($toEmail) . '.';
            } else {
                $error = 'Failed to send catalog. Please try again.';
            }
        } catch (\Exception $e) {
            $error = 'Error sending catalog: ' . $e->getMessage();
        }
    }
}

// Get categories for filter
$categories = $categoryModel->getAll(true);

$pageTitle = 'Send Product Catalog';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-file-pdf mr-2 md:mr-3"></i>
                    Send Product Catalog
                </h1>
                <p class="text-blue-100 text-sm md:text-lg">Generate and email PDF catalog to customers</p>
            </div>
            <a href="<?= url('admin/products.php') ?>" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Products
            </a>
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

    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 lg:p-8">
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Customer Email -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-envelope text-gray-400 mr-2"></i> Customer Email *
                    </label>
                    <input type="email" name="email" required
                           value="<?= escape($_POST['email'] ?? '') ?>"
                           placeholder="customer@example.com"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <p class="text-xs text-gray-500 mt-1">The catalog will be sent to this email address</p>
                </div>

                <!-- Customer Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user text-gray-400 mr-2"></i> Customer Name (Optional)
                    </label>
                    <input type="text" name="name"
                           value="<?= escape($_POST['name'] ?? '') ?>"
                           placeholder="John Doe"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>

                <!-- Category Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-tags text-gray-400 mr-2"></i> Category Filter (Optional)
                    </label>
                    <select name="category_id"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= (!empty($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                            <?= escape($category['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Send catalog for specific category only</p>
                </div>

                <!-- Featured Only -->
                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="featured_only" value="1" <?= isset($_POST['featured_only']) ? 'checked' : '' ?>
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Send only featured products</span>
                    </label>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 text-xl mr-3 mt-1"></i>
                    <div class="text-sm text-gray-700">
                        <p class="font-semibold mb-2">About the Catalog:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>The catalog will be generated as a PDF file</li>
                            <li>It includes product images, descriptions, prices, and specifications</li>
                            <li>The PDF will be attached to the email</li>
                            <li>If PDF generation fails, an HTML version will be sent</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-4 pt-4">
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-3 rounded-lg font-bold text-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Send Catalog
                </button>
                <a href="<?= url('admin/products.php') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-8 py-3 rounded-lg font-bold text-lg transition-all duration-300">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
