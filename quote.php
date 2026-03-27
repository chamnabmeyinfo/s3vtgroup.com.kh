<?php
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Product;

$message = '';
$error = '';
$productModel = new Product();
$selectedProduct = null;

if (!empty($_GET['product_id'])) {
    $selectedProduct = $productModel->getById($_GET['product_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security: CSRF protection
    require_csrf();
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $productId = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $messageText = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            db()->insert('quote_requests', [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'company' => $company,
                'product_id' => $productId,
                'message' => $messageText,
                'status' => 'pending'
            ]);
            
            $message = 'Thank you for your quote request! We will contact you shortly with pricing information.';
            $_POST = []; // Clear form
            $selectedProduct = null;
        } catch (Exception $e) {
            $error = 'Sorry, there was an error submitting your request. Please try again.';
        }
    }
}

$pageTitle = 'Request a Quote - ' . get_site_name();
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/message.php';
?>

<main class="py-12">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-4xl font-bold text-center mb-4">Request a Quote</h1>
            <p class="text-center text-gray-600 mb-12">Get competitive pricing for our equipment</p>
            
            <?= displayMessage($message, $error) ?>
            
            <?php if ($selectedProduct): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="font-semibold mb-2">Product:</p>
                    <p><?= escape($selectedProduct['name']) ?></p>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="bg-white rounded-lg shadow-md p-8 space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= $selectedProduct['id'] ?? '' ?>">
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium mb-2">Full Name *</label>
                        <input type="text" id="name" name="name" required
                               value="<?= escape($_POST['name'] ?? '') ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium mb-2">Email *</label>
                        <input type="email" id="email" name="email" required
                               value="<?= escape($_POST['email'] ?? '') ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="phone" class="block text-sm font-medium mb-2">Phone</label>
                        <input type="tel" id="phone" name="phone"
                               value="<?= escape($_POST['phone'] ?? '') ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="company" class="block text-sm font-medium mb-2">Company</label>
                        <input type="text" id="company" name="company"
                               value="<?= escape($_POST['company'] ?? '') ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div>
                    <label for="message" class="block text-sm font-medium mb-2">Additional Information</label>
                    <textarea id="message" name="message" rows="6"
                              placeholder="Tell us about your requirements, quantity needed, delivery timeline, etc."
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= escape($_POST['message'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn-primary w-full">
                    <i class="fas fa-paper-plane mr-2"></i> Submit Quote Request
                </button>
                
                <p class="text-sm text-gray-600 text-center">
                    We typically respond within 24 hours during business days.
                </p>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

