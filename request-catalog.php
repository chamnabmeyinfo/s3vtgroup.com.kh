<?php
require_once __DIR__ . '/bootstrap/app.php';

// Check under construction mode
use App\Helpers\UnderConstruction;
UnderConstruction::show();

use App\Helpers\PdfCatalogHelper;
use App\Models\Category;

$categoryModel = new Category();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toEmail = trim($_POST['email'] ?? '');
    $customerName = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    
    if (empty($toEmail) || empty($customerName)) {
        $error = 'Please fill in all required fields.';
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
        
        try {
            // Generate and send catalog
            $result = PdfCatalogHelper::sendCatalog($toEmail, $customerName, $filters);
            
            if ($result) {
                $message = 'Thank you! Your catalog request has been received. We will send the PDF catalog to your email shortly.';
                $_POST = []; // Clear form
            } else {
                $error = 'Sorry, there was an error processing your request. Please try again later.';
            }
        } catch (\Exception $e) {
            $error = 'Sorry, there was an error processing your request. Please try again later.';
        }
    }
}

// Get categories for filter
$categories = $categoryModel->getAll(true);

$pageTitle = 'Request Product Catalog - ' . get_site_name();
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/message.php';
?>

<main class="py-12">
    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <div class="inline-block p-4 bg-blue-100 rounded-full mb-4">
                    <i class="fas fa-file-pdf text-blue-600 text-4xl"></i>
                </div>
                <h1 class="text-4xl font-bold mb-4">Request Product Catalog</h1>
                <p class="text-gray-600 text-lg">Get our complete product catalog delivered to your inbox</p>
            </div>
            
            <?= displayMessage($message, $error) ?>
            
            <div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
                <form method="POST" class="space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-semibold mb-2">
                                <i class="fas fa-user text-gray-400 mr-2"></i>Full Name *
                            </label>
                            <input type="text" id="name" name="name" required
                                   value="<?= escape($_POST['name'] ?? '') ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-semibold mb-2">
                                <i class="fas fa-envelope text-gray-400 mr-2"></i>Email Address *
                            </label>
                            <input type="email" id="email" name="email" required
                                   value="<?= escape($_POST['email'] ?? '') ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="phone" class="block text-sm font-semibold mb-2">
                                <i class="fas fa-phone text-gray-400 mr-2"></i>Phone Number
                            </label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?= escape($_POST['phone'] ?? '') ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                        
                        <div>
                            <label for="company" class="block text-sm font-semibold mb-2">
                                <i class="fas fa-building text-gray-400 mr-2"></i>Company
                            </label>
                            <input type="text" id="company" name="company"
                                   value="<?= escape($_POST['company'] ?? '') ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                    </div>
                    
                    <div>
                        <label for="category_id" class="block text-sm font-semibold mb-2">
                            <i class="fas fa-tags text-gray-400 mr-2"></i>Category (Optional)
                        </label>
                        <select id="category_id" name="category_id"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="">All Products</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= (!empty($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                <?= escape($category['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Select a specific category or leave blank for complete catalog</p>
                    </div>
                    
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-600 text-xl mr-3 mt-1"></i>
                            <div class="text-sm text-gray-700">
                                <p class="font-semibold mb-2">What you'll receive:</p>
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Complete PDF catalog with all products</li>
                                    <li>Product images, descriptions, and specifications</li>
                                    <li>Pricing information</li>
                                    <li>Contact information for inquiries</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-4 rounded-lg font-bold text-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Request Catalog
                    </button>
                </form>
            </div>
            
            <div class="mt-6 text-center">
                <a href="<?= url('products.php') ?>" class="text-blue-600 hover:text-blue-800 font-semibold">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Products
                </a>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
