<?php
require_once __DIR__ . '/bootstrap/app.php';

// Check under construction mode
use App\Helpers\UnderConstruction;
UnderConstruction::show();

use App\Models\Setting;

// Get Mission settings
$settingsData = db()->fetchAll("SELECT `key`, value FROM settings WHERE `key` LIKE 'mission_%' OR `key` LIKE 'mission_vision_%'");
$settings = [];
foreach ($settingsData as $setting) {
    $settings[$setting['key']] = $setting['value'];
}

// Default values
$defaults = [
    'mission_title' => 'Our Mission',
    'mission_content' => 'To provide exceptional forklift and industrial equipment solutions that empower businesses to achieve their operational goals. We are committed to delivering quality products, outstanding service, and innovative solutions that drive productivity and success.',
    'mission_icon' => 'fa-bullseye',
    'mission_vision_bg_color1' => '#ffffff',
    'mission_vision_bg_color2' => '#f0f7ff',
    'mission_vision_title_color' => '#1a1a1a',
    'mission_vision_text_color' => '#475569',
    'mission_vision_icon_bg_color1' => '#3b82f6',
    'mission_vision_icon_bg_color2' => '#2563eb',
];

foreach ($defaults as $key => $default) {
    if (!isset($settings[$key])) {
        $settings[$key] = $default;
    }
}

$pageTitle = escape($settings['mission_title'] ?? 'Our Mission') . ' - ' . (db()->fetchOne("SELECT value FROM settings WHERE `key` = 'site_name'")['value'] ?? 'Forklift & Equipment Pro');
$metaDescription = 'Learn about our mission to provide exceptional forklift and industrial equipment solutions.';

include __DIR__ . '/includes/header.php';
?>

<main class="overflow-hidden">
    <!-- Creative Hero Section with Split Design -->
    <section class="relative min-h-screen flex items-center overflow-hidden" style="
        <?php if (!empty($settings['mission_vision_hero_bg_image'])): ?>
            background-image: url('<?= escape($settings['mission_vision_hero_bg_image']) ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        <?php else: ?>
            background: linear-gradient(135deg, <?= escape($settings['mission_vision_bg_color1'] ?? '#ffffff') ?> 0%, <?= escape($settings['mission_vision_bg_color2'] ?? '#f0f7ff') ?> 100%);
        <?php endif; ?>
    ">
        <?php if (!empty($settings['mission_vision_hero_bg_image'])): ?>
        <div class="absolute inset-0 bg-black" style="opacity: <?= escape($settings['mission_vision_hero_bg_overlay_opacity'] ?? 0.3) ?>;"></div>
        <?php endif; ?>
        <!-- Animated Geometric Shapes -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-20 left-10 w-72 h-72 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float-delay"></div>
            <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-cyan-300 rounded-full mix-blend-multiply filter blur-3xl opacity-15 animate-float-delay-2"></div>
            
            <!-- Geometric Shapes -->
            <div class="absolute top-32 right-32 w-32 h-32 border-4 rounded-lg transform rotate-45 animate-spin-slow" style="border-color: <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>; opacity: 0.1;"></div>
            <div class="absolute bottom-32 left-32 w-24 h-24 border-4 rounded-full animate-pulse" style="border-color: <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>; opacity: 0.15;"></div>
            <div class="absolute top-1/2 right-1/4 w-16 h-16 bg-blue-400 transform rotate-12 animate-bounce-slow" style="opacity: 0.1;"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10 py-20">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Side - Icon & Title -->
                <div class="text-center lg:text-left">
                    <div class="inline-block mb-8 lg:mb-12">
                        <div class="relative">
                            <div class="w-32 h-32 lg:w-40 lg:h-40 bg-gradient-to-br rounded-3xl flex items-center justify-center shadow-2xl transform hover:scale-110 hover:rotate-6 transition-all duration-500 animate-icon-float" style="background: linear-gradient(135deg, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>);">
                                <i class="fas <?= escape($settings['mission_icon'] ?? 'fa-bullseye') ?> text-white text-5xl lg:text-6xl"></i>
                            </div>
                            <!-- Rotating Rings -->
                            <div class="absolute inset-0 border-4 rounded-3xl animate-spin-slow" style="border-color: <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>; opacity: 0.3; transform: scale(1.2);"></div>
                            <div class="absolute inset-0 border-4 rounded-3xl animate-spin-reverse" style="border-color: <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>; opacity: 0.2; transform: scale(1.4);"></div>
                        </div>
                    </div>
                    
                    <h1 class="text-6xl md:text-7xl lg:text-8xl font-black mb-6 leading-tight animate-slide-in-left" style="color: <?= !empty($settings['mission_vision_hero_bg_image']) ? '#ffffff' : escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">
                        <span class="block">Our</span>
                        <span class="block <?= !empty($settings['mission_vision_hero_bg_image']) ? 'text-white' : 'bg-gradient-to-r bg-clip-text text-transparent' ?>" <?= empty($settings['mission_vision_hero_bg_image']) ? 'style="background-image: linear-gradient(135deg, ' . escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') . ', ' . escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') . ');"' : '' ?>>Mission</span>
                    </h1>
                    
                    <div class="flex items-center gap-4 justify-center lg:justify-start mb-8">
                        <div class="h-1 w-20 bg-gradient-to-r rounded-full" style="background: linear-gradient(90deg, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>);"></div>
                        <div class="h-1 w-12 bg-gradient-to-r rounded-full opacity-50" style="background: linear-gradient(90deg, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>);"></div>
                    </div>
                </div>
                
                <!-- Right Side - Content Card with Creative Design -->
                <div class="relative">
                    <div class="bg-white/80 backdrop-blur-lg rounded-3xl shadow-2xl p-8 md:p-12 border border-white/50 relative overflow-hidden transform hover:scale-105 transition-all duration-500" style="
                        <?php if (!empty($settings['mission_card_bg_image'])): ?>
                            background-image: url('<?= escape($settings['mission_card_bg_image']) ?>');
                            background-size: cover;
                            background-position: center;
                        <?php endif; ?>
                    ">
                        <!-- Decorative Elements -->
                        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br rounded-full -mr-32 -mt-32 opacity-20" style="background: linear-gradient(135deg, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>);"></div>
                        <div class="absolute bottom-0 left-0 w-48 h-48 bg-gradient-to-tr rounded-full -ml-24 -mb-24 opacity-15" style="background: linear-gradient(135deg, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>);"></div>
                        
                        <div class="relative z-10">
                            <p class="text-xl md:text-2xl leading-relaxed font-medium" style="color: <?= escape($settings['mission_vision_text_color'] ?? '#475569') ?>;">
                                <?= nl2br(escape($settings['mission_content'] ?? '')) ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Floating Elements -->
                    <div class="absolute -top-6 -right-6 w-24 h-24 bg-blue-400 rounded-2xl transform rotate-12 opacity-20 animate-float"></div>
                    <div class="absolute -bottom-6 -left-6 w-16 h-16 bg-indigo-400 rounded-full opacity-20 animate-float-delay"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Creative Mission Pillars Section -->
    <section class="py-20 md:py-32 bg-white relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5" style="background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%233b82f6" fill-opacity="1"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-black mb-4" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">
                    Our Core Values
                </h2>
                <div class="h-1 w-32 bg-gradient-to-r rounded-full mx-auto" style="background: linear-gradient(90deg, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>);"></div>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <!-- Value Card 1 - Excellence -->
                <div class="group relative">
                    <div class="bg-gradient-to-br from-white to-blue-50/50 rounded-3xl p-8 shadow-xl border border-gray-100 transform hover:-translate-y-4 hover:scale-105 transition-all duration-500 hover:shadow-2xl relative overflow-hidden">
                        <!-- Animated Background -->
                        <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-100 transition-opacity duration-500" style="background: linear-gradient(135deg, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>);"></div>
                        
                        <div class="relative z-10">
                            <div class="w-20 h-20 bg-gradient-to-br rounded-2xl flex items-center justify-center mb-6 transform group-hover:scale-110 group-hover:rotate-6 transition-all duration-500 shadow-lg" style="background: linear-gradient(135deg, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>);">
                                <i class="fas fa-star text-white text-3xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold mb-3 group-hover:text-white transition-colors duration-300" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">Excellence</h3>
                            <p class="text-gray-600 group-hover:text-white/90 transition-colors duration-300">We strive for excellence in every product and service we deliver.</p>
                        </div>
                        
                        <!-- Corner Decoration -->
                        <div class="absolute top-0 right-0 w-24 h-24 border-t-4 border-r-4 rounded-tr-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="border-color: white;"></div>
                    </div>
                </div>
                
                <!-- Value Card 2 - Partnership -->
                <div class="group relative">
                    <div class="bg-gradient-to-br from-white to-indigo-50/50 rounded-3xl p-8 shadow-xl border border-gray-100 transform hover:-translate-y-4 hover:scale-105 transition-all duration-500 hover:shadow-2xl relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-100 transition-opacity duration-500" style="background: linear-gradient(135deg, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>);"></div>
                        
                        <div class="relative z-10">
                            <div class="w-20 h-20 bg-gradient-to-br rounded-2xl flex items-center justify-center mb-6 transform group-hover:scale-110 group-hover:rotate-6 transition-all duration-500 shadow-lg" style="background: linear-gradient(135deg, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>);">
                                <i class="fas fa-users text-white text-3xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold mb-3 group-hover:text-white transition-colors duration-300" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">Partnership</h3>
                            <p class="text-gray-600 group-hover:text-white/90 transition-colors duration-300">Building long-term relationships with our clients and partners.</p>
                        </div>
                        
                        <div class="absolute top-0 right-0 w-24 h-24 border-t-4 border-r-4 rounded-tr-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="border-color: white;"></div>
                    </div>
                </div>
                
                <!-- Value Card 3 - Innovation -->
                <div class="group relative">
                    <div class="bg-gradient-to-br from-white to-purple-50/50 rounded-3xl p-8 shadow-xl border border-gray-100 transform hover:-translate-y-4 hover:scale-105 transition-all duration-500 hover:shadow-2xl relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-100 transition-opacity duration-500" style="background: linear-gradient(135deg, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>);"></div>
                        
                        <div class="relative z-10">
                            <div class="w-20 h-20 bg-gradient-to-br rounded-2xl flex items-center justify-center mb-6 transform group-hover:scale-110 group-hover:rotate-6 transition-all duration-500 shadow-lg" style="background: linear-gradient(135deg, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>);">
                                <i class="fas fa-lightbulb text-white text-3xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold mb-3 group-hover:text-white transition-colors duration-300" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">Innovation</h3>
                            <p class="text-gray-600 group-hover:text-white/90 transition-colors duration-300">Continuously innovating to meet evolving industry needs.</p>
                        </div>
                        
                        <div class="absolute top-0 right-0 w-24 h-24 border-t-4 border-r-4 rounded-tr-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="border-color: white;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Creative Call to Action -->
    <section class="relative py-20 md:py-32 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r" style="background: linear-gradient(135deg, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>);"></div>
        
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%23ffffff" fill-opacity="0.4"/%3E%3C/svg%3E');"></div>
        </div>
        
        <div class="container mx-auto px-4 text-center relative z-10">
            <h2 class="text-4xl md:text-5xl font-black mb-6 text-white">Ready to Work With Us?</h2>
            <p class="text-xl md:text-2xl mb-10 text-white/90 max-w-2xl mx-auto">Let's achieve your operational goals together and build something extraordinary.</p>
            <div class="flex flex-wrap justify-center gap-6">
                <a href="<?= url('contact.php') ?>" class="group bg-white text-blue-600 px-10 py-4 rounded-2xl font-bold text-lg hover:bg-blue-50 transition-all duration-300 transform hover:scale-110 shadow-2xl hover:shadow-3xl relative overflow-hidden">
                    <span class="relative z-10 flex items-center">
                        <i class="fas fa-envelope mr-3"></i>Contact Us
                    </span>
                    <div class="absolute inset-0 bg-gradient-to-r opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: linear-gradient(135deg, <?= escape($settings['mission_vision_icon_bg_color1'] ?? '#3b82f6') ?>, <?= escape($settings['mission_vision_icon_bg_color2'] ?? '#2563eb') ?>);"></div>
                </a>
                <a href="<?= url('products.php') ?>" class="bg-white/10 backdrop-blur-lg text-white px-10 py-4 rounded-2xl font-bold text-lg hover:bg-white/20 transition-all duration-300 transform hover:scale-110 shadow-2xl border-2 border-white/30">
                    <i class="fas fa-box mr-3"></i>View Products
                </a>
            </div>
        </div>
    </section>
</main>

<style>
@keyframes float {
    0%, 100% {
        transform: translateY(0px) translateX(0px);
    }
    50% {
        transform: translateY(-20px) translateX(10px);
    }
}
@keyframes float-delay {
    0%, 100% {
        transform: translateY(0px) translateX(0px);
    }
    50% {
        transform: translateY(20px) translateX(-10px);
    }
}
@keyframes float-delay-2 {
    0%, 100% {
        transform: translateY(0px) translateX(0px);
    }
    50% {
        transform: translateY(-15px) translateX(-15px);
    }
}
@keyframes spin-slow {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
@keyframes spin-reverse {
    from {
        transform: rotate(360deg);
    }
    to {
        transform: rotate(0deg);
    }
}
@keyframes bounce-slow {
    0%, 100% {
        transform: translateY(0) rotate(12deg);
    }
    50% {
        transform: translateY(-20px) rotate(12deg);
    }
}
@keyframes slide-in-left {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
@keyframes icon-float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-10px) rotate(3deg);
    }
}
.animate-float {
    animation: float 6s ease-in-out infinite;
}
.animate-float-delay {
    animation: float-delay 8s ease-in-out infinite;
}
.animate-float-delay-2 {
    animation: float-delay-2 7s ease-in-out infinite;
}
.animate-spin-slow {
    animation: spin-slow 20s linear infinite;
}
.animate-spin-reverse {
    animation: spin-reverse 15s linear infinite;
}
.animate-bounce-slow {
    animation: bounce-slow 3s ease-in-out infinite;
}
.animate-slide-in-left {
    animation: slide-in-left 1s ease-out;
}
.animate-icon-float {
    animation: icon-float 4s ease-in-out infinite;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
