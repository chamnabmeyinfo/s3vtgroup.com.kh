<?php
require_once __DIR__ . '/bootstrap/app.php';

// Check under construction mode
use App\Helpers\UnderConstruction;
UnderConstruction::show();

use App\Helpers\QrCodeHelper;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $messageText = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($messageText)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            db()->insert('contact_messages', [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'subject' => $subject,
                'message' => $messageText
            ]);
            
            $message = 'Thank you for contacting us! We will get back to you soon.';
            $_POST = []; // Clear form
        } catch (Exception $e) {
            $error = 'Sorry, there was an error sending your message. Please try again.';
        }
    }
}

$productName = $_GET['product'] ?? '';

// Generate QR code for website
$qrCodeUrl = QrCodeHelper::generateWebsiteQr('', 200, 'url');

$pageTitle = 'Contact Us - ' . get_site_name();
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/message.php';
?>

<main class="py-12 md:py-16 bg-gradient-to-br from-gray-50 via-white to-blue-50">
    <div class="container mx-auto px-4">
        <!-- Hero Section -->
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-3xl shadow-2xl mb-12 md:mb-16">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="absolute inset-0 opacity-20" style="background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            <div class="relative px-6 md:px-12 lg:px-16 py-12 md:py-20 text-white">
                <div class="max-w-4xl mx-auto text-center">
                    <div class="inline-block p-4 bg-white/20 backdrop-blur-sm rounded-2xl mb-6">
                        <i class="fas fa-envelope-open-text text-5xl md:text-6xl"></i>
                    </div>
                    <h1 class="text-4xl md:text-6xl font-bold mb-6">Contact Us</h1>
                    <p class="text-xl md:text-2xl text-blue-100 max-w-2xl mx-auto leading-relaxed">
                        We're here to help! Get in touch with our team and let's discuss how we can assist you.
                    </p>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto">
            
            <?= displayMessage($message, $error) ?>
            
            <div class="grid lg:grid-cols-2 gap-8 md:gap-12">
                <!-- Contact Form - Modern Design -->
                <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 border border-gray-100">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold mb-2">Send us a Message</h2>
                        <p class="text-gray-600">Fill out the form below and we'll get back to you as soon as possible.</p>
                    </div>
                    
                    <form method="POST" class="space-y-5">
                        <div class="form-group">
                            <label for="name" class="block text-sm font-semibold mb-2 text-gray-700">
                                <i class="fas fa-user mr-2 text-blue-600"></i>Full Name *
                            </label>
                            <input type="text" id="name" name="name" required
                                   value="<?= escape($_POST['name'] ?? '') ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300 bg-gray-50 focus:bg-white">
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="block text-sm font-semibold mb-2 text-gray-700">
                                <i class="fas fa-envelope mr-2 text-blue-600"></i>Email Address *
                            </label>
                            <input type="email" id="email" name="email" required
                                   value="<?= escape($_POST['email'] ?? '') ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300 bg-gray-50 focus:bg-white">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone" class="block text-sm font-semibold mb-2 text-gray-700">
                                <i class="fas fa-phone mr-2 text-blue-600"></i>Phone Number
                            </label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?= escape($_POST['phone'] ?? '') ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300 bg-gray-50 focus:bg-white">
                        </div>
                        
                        <div class="form-group">
                            <label for="subject" class="block text-sm font-semibold mb-2 text-gray-700">
                                <i class="fas fa-tag mr-2 text-blue-600"></i>Subject
                            </label>
                            <input type="text" id="subject" name="subject"
                                   value="<?= escape($_POST['subject'] ?? $productName) ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300 bg-gray-50 focus:bg-white">
                        </div>
                        
                        <div class="form-group">
                            <label for="message" class="block text-sm font-semibold mb-2 text-gray-700">
                                <i class="fas fa-comment-alt mr-2 text-blue-600"></i>Message *
                            </label>
                            <textarea id="message" name="message" rows="6" required
                                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300 bg-gray-50 focus:bg-white resize-none"><?= escape($_POST['message'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white px-6 py-4 rounded-xl font-bold hover:from-blue-700 hover:via-indigo-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl">
                            <i class="fas fa-paper-plane mr-2"></i>Send Message
                        </button>
                    </form>
                </div>
                
                <!-- Contact Information - Modern Cards -->
                <div class="space-y-6">
                    <?php
                    use App\Models\Setting;
                    $settingModel = new Setting();
                    $sitePhone = $settingModel->get('site_phone', '+1 (555) 123-4567');
                    $siteEmail = $settingModel->get('site_email', 'info@example.com');
                    $siteAddress = $settingModel->get('site_address', '123 Industrial Way, City, State 12345');
                    ?>
                    
                    <!-- Contact Info Card -->
                    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-xl p-6 md:p-8 text-white">
                        <h3 class="text-2xl font-bold mb-6 flex items-center">
                            <i class="fas fa-address-card mr-3"></i>Get in Touch
                        </h3>
                        <div class="space-y-5">
                            <div class="flex items-start group">
                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 group-hover:bg-white/30 transition-all duration-300 flex-shrink-0">
                                    <i class="fas fa-phone text-xl"></i>
                                </div>
                                <div>
                                    <p class="font-semibold mb-1">Phone</p>
                                    <a href="tel:<?= escape($sitePhone) ?>" class="text-blue-100 hover:text-white transition-colors">
                                        <?= escape($sitePhone) ?>
                                    </a>
                                </div>
                            </div>
                            <div class="flex items-start group">
                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 group-hover:bg-white/30 transition-all duration-300 flex-shrink-0">
                                    <i class="fas fa-envelope text-xl"></i>
                                </div>
                                <div>
                                    <p class="font-semibold mb-1">Email</p>
                                    <a href="mailto:<?= escape($siteEmail) ?>" class="text-blue-100 hover:text-white transition-colors break-all">
                                        <?= escape($siteEmail) ?>
                                    </a>
                                </div>
                            </div>
                            <div class="flex items-start group">
                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 group-hover:bg-white/30 transition-all duration-300 flex-shrink-0">
                                    <i class="fas fa-map-marker-alt text-xl"></i>
                                </div>
                                <div>
                                    <p class="font-semibold mb-1">Address</p>
                                    <p class="text-blue-100"><?= nl2br(escape($siteAddress)) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Business Hours Card -->
                    <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 border border-gray-100">
                        <h3 class="text-2xl font-bold mb-6 flex items-center text-gray-800">
                            <i class="fas fa-clock mr-3 text-blue-600"></i>Business Hours
                        </h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <span class="font-semibold text-gray-700">Monday - Friday</span>
                                <span class="text-blue-600 font-bold">8:00 AM - 6:00 PM</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <span class="font-semibold text-gray-700">Saturday</span>
                                <span class="text-blue-600 font-bold">9:00 AM - 4:00 PM</span>
                            </div>
                            <div class="flex justify-between items-center py-3">
                                <span class="font-semibold text-gray-700">Sunday</span>
                                <span class="text-red-500 font-bold">Closed</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- QR Code Card -->
                    <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 border border-gray-100">
                        <h3 class="text-2xl font-bold mb-4 text-gray-800 flex items-center">
                            <i class="fas fa-qrcode text-blue-600 mr-3"></i>Scan to Visit Website
                        </h3>
                        <p class="text-gray-600 mb-6">Scan this QR code with your mobile device to visit our website</p>
                        <div class="flex justify-center mb-6">
                            <div class="bg-white p-4 rounded-xl shadow-lg border-2 border-gray-200">
                                <img src="<?= escape($qrCodeUrl) ?>" alt="QR Code" class="w-48 h-48">
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 text-center">Point your camera at the QR code to open our website</p>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-2xl shadow-lg p-6 border border-indigo-100">
                        <h3 class="text-xl font-bold mb-4 text-gray-800">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="<?= url('quote.php') ?>" class="block w-full bg-white hover:bg-blue-600 text-gray-800 hover:text-white px-4 py-3 rounded-xl font-semibold transition-all duration-300 shadow-md hover:shadow-xl transform hover:scale-105 text-center">
                                <i class="fas fa-calculator mr-2"></i>Request a Quote
                            </a>
                            <a href="<?= url('products.php') ?>" class="block w-full bg-white hover:bg-indigo-600 text-gray-800 hover:text-white px-4 py-3 rounded-xl font-semibold transition-all duration-300 shadow-md hover:shadow-xl transform hover:scale-105 text-center">
                                <i class="fas fa-box mr-2"></i>Browse Products
                            </a>
                            <a href="<?= url('about-us.php') ?>" class="block w-full bg-white hover:bg-purple-600 text-gray-800 hover:text-white px-4 py-3 rounded-xl font-semibold transition-all duration-300 shadow-md hover:shadow-xl transform hover:scale-105 text-center">
                                <i class="fas fa-building mr-2"></i>About Us
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

