<?php
require_once __DIR__ . '/bootstrap/app.php';

// Check under construction mode
use App\Helpers\UnderConstruction;
UnderConstruction::show();

use App\Models\Setting;

// Get Mission & Vision settings
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
    'vision_title' => 'Our Vision',
    'vision_content' => 'To become the most trusted partner in the industrial equipment industry, recognized for excellence, innovation, and customer satisfaction. We envision a future where every business has access to the best equipment solutions tailored to their unique needs.',
    'vision_icon' => 'fa-eye',
    'mission_vision_bg_color1' => '#ffffff',
    'mission_vision_bg_color2' => '#f0f7ff',
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

$siteName = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'site_name'")['value'] ?? 'Forklift & Equipment Pro';
$pageTitle = 'Our Mission & Vision - ' . $siteName;
$metaDescription = 'Discover our mission, vision, and core values. Learn how we deliver exceptional industrial equipment solutions with quality, reliability, and expert support.';

include __DIR__ . '/includes/header.php';
?>

<main>
    <!-- Hero Section -->
    <section class="relative overflow-hidden py-20 md:py-32" style="
        <?php if (!empty($settings['mission_vision_hero_bg_image'])): ?>
            background-image: url('<?= escape($settings['mission_vision_hero_bg_image']) ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        <?php else: ?>
            background: linear-gradient(to bottom right, #f9fafb, #ffffff, #eff6ff);
        <?php endif; ?>
    ">
        <!-- Background Overlay -->
        <?php if (!empty($settings['mission_vision_hero_bg_image'])): ?>
        <div class="absolute inset-0 bg-black" style="opacity: <?= escape($settings['mission_vision_hero_bg_overlay_opacity'] ?? 0.3) ?>;"></div>
        <?php endif; ?>
        
        <!-- Decorative Background Pattern -->
        <div class="absolute inset-0 opacity-5" style="background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%233b82f6" fill-opacity="1"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-0 right-0 w-96 h-96 bg-blue-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-black mb-6 leading-tight" style="color: <?= !empty($settings['mission_vision_hero_bg_image']) ? '#ffffff' : escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">
                    Our Mission & Vision
                </h1>
                <p class="text-xl md:text-2xl mb-8 leading-relaxed" style="color: <?= !empty($settings['mission_vision_hero_bg_image']) ? '#ffffff' : escape($settings['mission_vision_text_color'] ?? '#475569') ?>;">
                    Empowering businesses with exceptional industrial equipment solutions, reliability, and unwavering support.
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="<?= url('contact.php') ?>" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-4 rounded-xl font-bold text-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                        <i class="fas fa-envelope mr-2"></i>Contact Us
                    </a>
                    <a href="<?= url('products.php') ?>" class="bg-white text-blue-600 px-8 py-4 rounded-xl font-bold text-lg border-2 border-blue-600 hover:bg-blue-50 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                        <i class="fas fa-box mr-2"></i>Explore Products
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision Split Section -->
    <section class="py-16 md:py-24 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-2 gap-8 md:gap-12 max-w-6xl mx-auto">
                <!-- Mission Card -->
                <div class="relative bg-gradient-to-br from-white to-blue-50/30 rounded-3xl shadow-xl p-8 md:p-10 border border-gray-100 transform hover:-translate-y-2 transition-all duration-500 hover:shadow-2xl overflow-hidden" style="
                    <?php if (!empty($settings['mission_card_bg_image'])): ?>
                        background-image: url('<?= escape($settings['mission_card_bg_image']) ?>');
                        background-size: cover;
                        background-position: center;
                    <?php endif; ?>
                ">
                    <?php if (!empty($settings['mission_card_bg_image'])): ?>
                    <div class="absolute inset-0 bg-white/80"></div>
                    <?php endif; ?>
                    <div class="relative z-10">
                    <div class="mb-6">
                        <div class="w-20 h-20 bg-gradient-to-br rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg" style="background: linear-gradient(135deg, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>);">
                            <i class="fas <?= escape($settings['mission_icon'] ?? 'fa-bullseye') ?> text-white text-3xl"></i>
                        </div>
                        <h2 class="text-3xl md:text-4xl font-bold text-center mb-4" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">
                            Mission
                        </h2>
                    </div>
                    
                    <p class="text-lg leading-relaxed mb-6 text-center" style="color: <?= escape($settings['mission_vision_text_color'] ?? '#475569') ?>;">
                        <?= escape($settings['mission_content'] ?? '') ?>
                    </p>
                    
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mr-3 mt-1 flex-shrink-0"></i>
                            <span style="color: <?= escape($settings['mission_vision_text_color'] ?? '#475569') ?>;">Deliver exceptional quality in every product and service</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mr-3 mt-1 flex-shrink-0"></i>
                            <span style="color: <?= escape($settings['mission_vision_text_color'] ?? '#475569') ?>;">Build lasting partnerships based on trust and reliability</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mr-3 mt-1 flex-shrink-0"></i>
                            <span style="color: <?= escape($settings['mission_vision_text_color'] ?? '#475569') ?>;">Drive innovation to meet evolving industry needs</span>
                        </li>
                    </ul>
                    </div>
                </div>

                <!-- Vision Card -->
                <div class="relative bg-gradient-to-br from-white to-purple-50/30 rounded-3xl shadow-xl p-8 md:p-10 border border-gray-100 transform hover:-translate-y-2 transition-all duration-500 hover:shadow-2xl overflow-hidden" style="
                    <?php if (!empty($settings['vision_card_bg_image'])): ?>
                        background-image: url('<?= escape($settings['vision_card_bg_image']) ?>');
                        background-size: cover;
                        background-position: center;
                    <?php endif; ?>
                ">
                    <?php if (!empty($settings['vision_card_bg_image'])): ?>
                    <div class="absolute inset-0 bg-white/80"></div>
                    <?php endif; ?>
                    <div class="relative z-10">
                    <div class="mb-6">
                        <div class="w-20 h-20 bg-gradient-to-br rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>);">
                            <i class="fas <?= escape($settings['vision_icon'] ?? 'fa-eye') ?> text-white text-3xl"></i>
                        </div>
                        <h2 class="text-3xl md:text-4xl font-bold text-center mb-4" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">
                            Vision
                        </h2>
                    </div>
                    
                    <p class="text-lg leading-relaxed mb-6 text-center" style="color: <?= escape($settings['mission_vision_text_color'] ?? '#475569') ?>;">
                        <?= escape($settings['vision_content'] ?? '') ?>
                    </p>
                    
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-purple-600 mr-3 mt-1 flex-shrink-0"></i>
                            <span style="color: <?= escape($settings['mission_vision_text_color'] ?? '#475569') ?>;">Become the industry leader in equipment solutions</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-purple-600 mr-3 mt-1 flex-shrink-0"></i>
                            <span style="color: <?= escape($settings['mission_vision_text_color'] ?? '#475569') ?>;">Expand our global reach while maintaining quality standards</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-purple-600 mr-3 mt-1 flex-shrink-0"></i>
                            <span style="color: <?= escape($settings['mission_vision_text_color'] ?? '#475569') ?>;">Shape the future of industrial equipment innovation</span>
                        </li>
                    </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Values Section -->
    <section class="py-16 md:py-24 bg-gradient-to-b from-white via-gray-50 to-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-bold mb-4" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">
                    Our Core Values
                </h2>
                <div class="h-1 w-24 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full mx-auto mb-4"></div>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    The fundamental principles that guide everything we do
                </p>
            </div>
            
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
                <!-- Value Card 1: Quality Assured -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 transform hover:-translate-y-2 transition-all duration-300 hover:shadow-xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">Quality Assured</h3>
                    <p class="text-gray-600">Every product meets the highest industry standards for reliability and performance.</p>
                </div>
                
                <!-- Value Card 2: Fast Delivery -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 transform hover:-translate-y-2 transition-all duration-300 hover:shadow-xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-shipping-fast text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">Fast Delivery</h3>
                    <p class="text-gray-600">Quick shipping and reliable delivery service to get your equipment when you need it.</p>
                </div>
                
                <!-- Value Card 3: Expert Support -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 transform hover:-translate-y-2 transition-all duration-300 hover:shadow-xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-headset text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">Expert Support</h3>
                    <p class="text-gray-600">24/7 customer support and maintenance services from our experienced team.</p>
                </div>
                
                <!-- Value Card 4: Safety First -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 transform hover:-translate-y-2 transition-all duration-300 hover:shadow-xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-hard-hat text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">Safety First</h3>
                    <p class="text-gray-600">Prioritizing safety in all operations and ensuring equipment meets safety regulations.</p>
                </div>
                
                <!-- Value Card 5: Precision -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 transform hover:-translate-y-2 transition-all duration-300 hover:shadow-xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-bullseye text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">Precision</h3>
                    <p class="text-gray-600">Attention to detail in every aspect of our service and product selection.</p>
                </div>
                
                <!-- Value Card 6: Customer Commitment -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 transform hover:-translate-y-2 transition-all duration-300 hover:shadow-xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-heart text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">Customer Commitment</h3>
                    <p class="text-gray-600">Your success is our success—we're dedicated to your long-term satisfaction.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How We Deliver / Proof Section -->
    <section class="py-16 md:py-24 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-bold mb-4" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">
                    How We Deliver
                </h2>
                <div class="h-1 w-24 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full mx-auto mb-4"></div>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Three pillars that ensure your success
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <!-- Proof Block 1: Quality & Certification -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-3xl p-8 shadow-xl border border-blue-100 text-center transform hover:-translate-y-3 transition-all duration-500 hover:shadow-2xl">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <i class="fas fa-certificate text-white text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">
                        Quality & Certification
                    </h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        All equipment is thoroughly inspected and certified to meet the highest industry standards. We partner with trusted manufacturers to ensure quality.
                    </p>
                    <ul class="text-left space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-blue-600 mr-2"></i>
                            ISO certified products
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-blue-600 mr-2"></i>
                            Rigorous quality testing
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-blue-600 mr-2"></i>
                            Manufacturer warranties
                        </li>
                    </ul>
                </div>
                
                <!-- Proof Block 2: Fast Delivery & Installation -->
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-3xl p-8 shadow-xl border border-green-100 text-center transform hover:-translate-y-3 transition-all duration-500 hover:shadow-2xl">
                    <div class="w-20 h-20 bg-gradient-to-br from-green-600 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <i class="fas fa-truck-fast text-white text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">
                        Fast Delivery & Installation
                    </h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Quick shipping and reliable delivery service to get your equipment when you need it. Professional installation available.
                    </p>
                    <ul class="text-left space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-600 mr-2"></i>
                            Express shipping options
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-600 mr-2"></i>
                            Professional installation
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-600 mr-2"></i>
                            On-time delivery guarantee
                        </li>
                    </ul>
                </div>
                
                <!-- Proof Block 3: 24/7 Support & Maintenance -->
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-3xl p-8 shadow-xl border border-purple-100 text-center transform hover:-translate-y-3 transition-all duration-500 hover:shadow-2xl">
                    <div class="w-20 h-20 bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <i class="fas fa-tools text-white text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">
                        24/7 Support & Maintenance
                    </h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        24/7 customer support and maintenance services from our experienced team. We're here when you need us.
                    </p>
                    <ul class="text-left space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-purple-600 mr-2"></i>
                            Round-the-clock support
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-purple-600 mr-2"></i>
                            Preventive maintenance
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-purple-600 mr-2"></i>
                            Expert technical assistance
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Clients / Trust Strip (Optional) -->
    <?php
    // Get clients/partners for trust strip
    use App\Models\Partner;
    $partnerModel = new Partner();
    $clients = $partnerModel->getAll(true);
    if (!empty($clients)):
    ?>
    <section class="py-12 bg-gray-50 border-t border-gray-200">
        <div class="container mx-auto px-4">
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Trusted By Industry Leaders</h3>
                <p class="text-gray-600">We're proud to serve businesses across various industries</p>
            </div>
            <div class="flex flex-wrap justify-center items-center gap-8 md:gap-12 opacity-60">
                <?php foreach (array_slice($clients, 0, 6) as $client): ?>
                    <?php if (!empty($client['logo'])): ?>
                    <div class="flex items-center justify-center h-16 w-32 grayscale hover:grayscale-0 transition-all duration-300">
                        <img src="<?= escape($client['logo']) ?>" alt="<?= escape($client['name']) ?>" class="max-h-full max-w-full object-contain">
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Final CTA Band -->
    <section class="py-16 md:py-20 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%23ffffff" fill-opacity="0.4"/%3E%3C/svg%3E');"></div>
        
        <div class="container mx-auto px-4 text-center relative z-10">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to Elevate Your Operations?</h2>
            <p class="text-xl mb-8 text-blue-100 max-w-2xl mx-auto">
                Let's discuss how our industrial equipment solutions can drive your business forward.
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="<?= url('quote.php') ?>" class="bg-white text-blue-600 px-8 py-4 rounded-xl font-bold text-lg hover:bg-blue-50 transition-all duration-300 transform hover:scale-105 shadow-xl">
                    <i class="fas fa-calculator mr-2"></i>Request a Quote
                </a>
                <a href="<?= url('contact.php') ?>" class="bg-blue-500 text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-blue-400 transition-all duration-300 transform hover:scale-105 shadow-xl border-2 border-white/20">
                    <i class="fas fa-comments mr-2"></i>Talk to an Expert
                </a>
            </div>
        </div>
    </section>
</main>

<style>
@keyframes blob {
    0%, 100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
}
.animate-blob {
    animation: blob 7s infinite;
}
.animation-delay-2000 {
    animation-delay: 2s;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
