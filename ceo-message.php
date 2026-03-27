<?php
require_once __DIR__ . '/bootstrap/app.php';

// Check under construction mode
use App\Helpers\UnderConstruction;
UnderConstruction::show();

use App\Models\Setting;
use App\Models\CeoMessage;
use App\Helpers\QrCodeHelper;

$settingModel = new Setting();
$ceoModel = new CeoMessage();
$siteName = $settingModel->get('site_name', 'Forklift & Equipment Pro');

// Get CEO message from database
$ceoData = $ceoModel->getActive();
if (!$ceoData) {
    // Fallback to default if no CEO message exists
    $ceoData = [
        'ceo_name' => 'CEO',
        'ceo_title' => 'Chief Executive Officer',
        'ceo_photo' => null,
        'greeting' => 'Dear Valued Customers and Partners,',
        'message_content' => '<p>It is with great pleasure and pride that I welcome you to ' . escape($siteName) . '. As the Chief Executive Officer, I am honored to lead a team of dedicated professionals who are committed to delivering excellence in every aspect of our business.</p><p>Our company was founded on the principles of quality, integrity, and customer satisfaction. These core values have guided us through years of growth and have established us as a trusted leader in the forklift and industrial equipment industry.</p><p>We understand that your business success depends on reliable, efficient equipment. That\'s why we go beyond simply selling products – we partner with you to understand your unique needs and provide solutions that drive your operational excellence.</p><p>Our commitment extends to every interaction: from the initial consultation through installation, training, and ongoing support. We believe in building long-term relationships, not just making transactions.</p><p>As we look to the future, we remain dedicated to innovation, continuous improvement, and exceeding your expectations. We invest in our team, our technology, and our processes to ensure we can serve you better every day.</p><p>Thank you for choosing ' . escape($siteName) . '. We are here to support your success, and we look forward to being your trusted partner for years to come.</p>',
        'signature_name' => 'CEO',
        'signature_title' => 'Chief Executive Officer'
    ];
}

// Generate QR code for website
$qrCodeUrl = QrCodeHelper::generateWebsiteQr('', 200, 'url');

$pageTitle = 'CEO Message - ' . $siteName;
include __DIR__ . '/includes/header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap');
    
    .ceo-hero {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 25%, #6366f1 50%, #8b5cf6 75%, #a855f7 100%);
        position: relative;
        overflow: hidden;
    }
    
    .ceo-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.05"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
        opacity: 0.3;
    }
    
    .ceo-hero::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse 20s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.3; }
        50% { transform: scale(1.1) rotate(180deg); opacity: 0.5; }
    }
    
    .ceo-card {
        background: linear-gradient(to bottom, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .ceo-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
    }
    
    .ceo-photo-frame {
        position: relative;
        padding: 8px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }
    
    .ceo-photo-frame::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 20px;
        padding: 2px;
        background: linear-gradient(135deg, rgba(255,255,255,0.3), rgba(255,255,255,0.1));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
    }
    
    .ceo-signature {
        font-family: 'Playfair Display', serif;
        position: relative;
        padding-left: 20px;
    }
    
    .ceo-signature::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(to bottom, #667eea, #764ba2);
        border-radius: 2px;
    }
    
    .message-content {
        font-family: 'Inter', sans-serif;
        line-height: 1.9;
        color: #374151;
    }
    
    .message-content p {
        margin-bottom: 1.5rem;
        font-size: 1.125rem;
    }
    
    .qr-card {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }
    
    .qr-card:hover {
        border-color: rgba(102, 126, 234, 0.3);
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.1);
    }
    
    .quick-links-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        overflow: hidden;
    }
    
    .quick-links-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: rotate 30s linear infinite;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .link-button {
        position: relative;
        z-index: 1;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }
    
    .link-button:hover {
        transform: translateX(5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    }
    
    .decorative-line {
        height: 3px;
        background: linear-gradient(to right, transparent, #667eea, #764ba2, transparent);
        margin: 2rem 0;
    }
</style>

<main class="py-8 md:py-16 bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/50">
    <div class="container mx-auto px-4 max-w-6xl">
        <!-- Hero Section -->
        <div class="ceo-hero rounded-3xl shadow-2xl mb-12 md:mb-16 relative z-10">
            <div class="relative px-6 md:px-12 lg:px-16 py-16 md:py-24 text-white z-10">
                <div class="max-w-4xl mx-auto text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 md:w-24 md:h-24 bg-white/20 backdrop-blur-md rounded-2xl mb-6 shadow-xl">
                        <i class="fas fa-user-tie text-4xl md:text-5xl"></i>
                    </div>
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold mb-6 font-serif tracking-tight" style="font-family: 'Playfair Display', serif;">
                        A Message from Our CEO
                    </h1>
                    <div class="w-24 h-1 bg-white/50 mx-auto mb-6 rounded-full"></div>
                    <p class="text-lg md:text-xl text-blue-100 max-w-2xl mx-auto leading-relaxed font-light">
                        Leadership, Vision, and Commitment to Excellence
                    </p>
                </div>
            </div>
        </div>

        <div class="max-w-5xl mx-auto">
            <!-- CEO Message Card -->
            <div class="ceo-card rounded-3xl p-8 md:p-12 lg:p-16 mb-12">
                <!-- CEO Profile Section -->
                <div class="flex flex-col md:flex-row items-center md:items-start gap-8 mb-10 pb-10 border-b-2 border-gray-100">
                    <div class="flex-shrink-0">
                        <?php if (!empty($ceoData['ceo_photo'])): ?>
                        <div class="ceo-photo-frame">
                            <img src="<?= escape(image_url($ceoData['ceo_photo'])) ?>" 
                                 alt="<?= escape($ceoData['ceo_name']) ?>" 
                                 class="w-40 h-40 md:w-48 md:h-48 object-cover rounded-xl">
                        </div>
                        <?php else: ?>
                        <div class="ceo-photo-frame">
                            <div class="w-40 h-40 md:w-48 md:h-48 bg-gradient-to-br from-indigo-500 via-purple-600 to-pink-500 rounded-xl flex items-center justify-center">
                                <i class="fas fa-user-tie text-white text-6xl"></i>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 text-center md:text-left">
                        <div class="inline-block px-4 py-2 bg-gradient-to-r from-indigo-100 to-purple-100 rounded-full mb-4">
                            <span class="text-sm font-semibold text-indigo-700"><?= escape($ceoData['ceo_title']) ?></span>
                        </div>
                        <h2 class="text-3xl md:text-4xl font-bold mb-3 text-gray-900" style="font-family: 'Playfair Display', serif;">
                            <?= escape($ceoData['ceo_name']) ?>
                        </h2>
                        <p class="text-lg text-gray-600 mb-4 font-medium"><?= escape($siteName) ?></p>
                        <div class="flex flex-wrap justify-center md:justify-start gap-4 text-sm text-gray-500">
                            <span class="flex items-center bg-gray-50 px-3 py-1.5 rounded-lg">
                                <i class="fas fa-calendar-alt mr-2 text-indigo-500"></i>
                                <?= date('F Y') ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Decorative Line -->
                <div class="decorative-line"></div>

                <!-- Message Content -->
                <div class="message-content">
                    <div class="mb-8">
                        <p class="text-2xl font-semibold text-gray-900 mb-6" style="font-family: 'Playfair Display', serif;">
                            <?= escape($ceoData['greeting']) ?>
                        </p>
                        <div class="space-y-6">
                            <?= $ceoData['message_content'] ?>
                        </div>
                    </div>
                    
                    <!-- Signature Section -->
                    <div class="ceo-signature mt-12 pt-8 border-t-2 border-gray-100">
                        <p class="text-lg font-semibold text-gray-700 mb-4 uppercase tracking-wider text-sm">Sincerely,</p>
                        <p class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 mb-2" style="font-family: 'Playfair Display', serif;">
                            <?= escape($ceoData['signature_name']) ?>
                        </p>
                        <?php if (!empty($ceoData['signature_title'])): ?>
                        <p class="text-lg text-gray-600 font-medium mb-2"><?= escape($ceoData['signature_title']) ?></p>
                        <?php endif; ?>
                        <p class="text-base text-gray-500"><?= escape($siteName) ?></p>
                    </div>
                </div>
            </div>

            <!-- QR Code & Contact Section -->
            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <!-- QR Code Card -->
                <div class="qr-card rounded-3xl p-8 md:p-10">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                            <i class="fas fa-qrcode text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Scan to Visit</h3>
                            <p class="text-sm text-gray-500">Quick access to our website</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-6 text-sm leading-relaxed">Scan this QR code with your mobile device to instantly access our website and explore our services.</p>
                    <div class="flex justify-center mb-6">
                        <div class="bg-white p-6 rounded-2xl shadow-xl border-4 border-gray-100">
                            <img src="<?= escape($qrCodeUrl) ?>" alt="QR Code" class="w-40 h-40">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 text-center">Point your camera at the QR code</p>
                </div>

                <!-- Quick Links -->
                <div class="quick-links-card rounded-3xl p-8 md:p-10 text-white relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                                <i class="fas fa-link text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold">Quick Links</h3>
                                <p class="text-sm text-blue-100">Explore our website</p>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <a href="<?= url('about-us.php') ?>" class="link-button block bg-white/20 hover:bg-white/30 px-6 py-4 rounded-xl font-semibold transition-all duration-300">
                                <i class="fas fa-building mr-3"></i>About Us
                            </a>
                            <a href="<?= url('contact.php') ?>" class="link-button block bg-white/20 hover:bg-white/30 px-6 py-4 rounded-xl font-semibold transition-all duration-300">
                                <i class="fas fa-envelope mr-3"></i>Contact Us
                            </a>
                            <a href="<?= url('products.php') ?>" class="link-button block bg-white/20 hover:bg-white/30 px-6 py-4 rounded-xl font-semibold transition-all duration-300">
                                <i class="fas fa-box mr-3"></i>Our Products
                            </a>
                            <a href="<?= url('services.php') ?>" class="link-button block bg-white/20 hover:bg-white/30 px-6 py-4 rounded-xl font-semibold transition-all duration-300">
                                <i class="fas fa-concierge-bell mr-3"></i>Our Services
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
