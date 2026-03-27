<?php
require_once __DIR__ . '/bootstrap/app.php';

// Check under construction mode
use App\Helpers\UnderConstruction;
UnderConstruction::show();

use App\Models\Setting;

// Get Vision settings
$settingsData = db()->fetchAll("SELECT `key`, value FROM settings WHERE `key` LIKE 'vision_%' OR `key` LIKE 'mission_vision_%'");
$settings = [];
foreach ($settingsData as $setting) {
    $settings[$setting['key']] = $setting['value'];
}

// Default values
$defaults = [
    'vision_title' => 'Our Vision',
    'vision_content' => 'To become the most trusted partner in the industrial equipment industry, recognized for excellence, innovation, and customer satisfaction. We envision a future where every business has access to the best equipment solutions tailored to their unique needs.',
    'vision_icon' => 'fa-eye',
    'mission_vision_bg_color1' => '#ffffff',
    'mission_vision_bg_color2' => '#faf5ff',
    'mission_vision_title_color' => '#1a1a1a',
    'mission_vision_text_color' => '#475569',
    'vision_icon_bg_color1' => '#8b5cf6',
    'vision_icon_bg_color2' => '#7c3aed',
];

foreach ($defaults as $key => $default) {
    if (!isset($settings[$key])) {
        $settings[$key] = $default;
    }
}

$pageTitle = escape($settings['vision_title'] ?? 'Our Vision') . ' - ' . (db()->fetchOne("SELECT value FROM settings WHERE `key` = 'site_name'")['value'] ?? 'Forklift & Equipment Pro');
$metaDescription = 'Discover our vision for the future of industrial equipment solutions and customer excellence.';

include __DIR__ . '/includes/header.php';
?>

<main class="overflow-hidden">
    <!-- Futuristic Hero Section with Diagonal Design -->
    <section class="relative min-h-screen flex items-center overflow-hidden" style="
        <?php if (!empty($settings['mission_vision_hero_bg_image'])): ?>
            background-image: url('<?= escape($settings['mission_vision_hero_bg_image']) ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        <?php else: ?>
            background: linear-gradient(135deg, <?= escape($settings['mission_vision_bg_color1'] ?? '#ffffff') ?> 0%, <?= escape($settings['mission_vision_bg_color2'] ?? '#faf5ff') ?> 100%);
        <?php endif; ?>
    ">
        <?php if (!empty($settings['mission_vision_hero_bg_image'])): ?>
        <div class="absolute inset-0 bg-black" style="opacity: <?= escape($settings['mission_vision_hero_bg_overlay_opacity'] ?? 0.3) ?>;"></div>
        <?php endif; ?>
        <!-- Animated Futuristic Background -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <!-- Gradient Orbs -->
            <div class="absolute top-10 right-20 w-80 h-80 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-orb-float"></div>
            <div class="absolute bottom-20 left-20 w-96 h-96 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-orb-float-delay"></div>
            <div class="absolute top-1/2 right-1/3 w-72 h-72 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-orb-float-delay-2"></div>
            
            <!-- Geometric Grid Pattern -->
            <div class="absolute inset-0 opacity-5" style="background-image: 
                linear-gradient(45deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?> 25%, transparent 25%),
                linear-gradient(-45deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?> 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?> 75%),
                linear-gradient(-45deg, transparent 75%, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?> 75%);
                background-size: 60px 60px;
                background-position: 0 0, 0 30px, 30px -30px, -30px 0px;
            "></div>
            
            <!-- Floating Shapes -->
            <div class="absolute top-1/4 left-1/4 w-20 h-20 border-2 rounded-lg transform rotate-45 animate-rotate-3d" style="border-color: <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>; opacity: 0.15;"></div>
            <div class="absolute bottom-1/4 right-1/4 w-16 h-16 border-2 rounded-full animate-pulse-slow" style="border-color: <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>; opacity: 0.2;"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10 py-20">
            <div class="max-w-6xl mx-auto">
                <!-- Center Content with Futuristic Layout -->
                <div class="text-center mb-16">
                    <!-- Icon with 3D Effect -->
                    <div class="inline-block mb-12 relative">
                        <div class="relative perspective-1000">
                            <div class="w-40 h-40 md:w-48 md:h-48 bg-gradient-to-br rounded-3xl flex items-center justify-center shadow-2xl transform hover:scale-110 hover:rotate-y-12 transition-all duration-700 animate-3d-float" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>);">
                                <i class="fas <?= escape($settings['vision_icon'] ?? 'fa-eye') ?> text-white text-6xl md:text-7xl"></i>
                            </div>
                            <!-- Glowing Rings -->
                            <div class="absolute inset-0 border-4 rounded-3xl animate-glow-pulse" style="border-color: <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>; opacity: 0.4; transform: scale(1.3);"></div>
                            <div class="absolute inset-0 border-4 rounded-3xl animate-glow-pulse-delay" style="border-color: <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>; opacity: 0.3; transform: scale(1.6);"></div>
                            <!-- Sparkle Effects -->
                            <div class="absolute -top-4 -right-4 w-4 h-4 bg-purple-400 rounded-full animate-sparkle"></div>
                            <div class="absolute -bottom-4 -left-4 w-3 h-3 bg-pink-400 rounded-full animate-sparkle-delay"></div>
                        </div>
                    </div>
                    
                    <!-- Title with Gradient Text -->
                    <h1 class="text-6xl md:text-8xl lg:text-9xl font-black mb-8 leading-tight animate-fade-in-scale">
                        <span class="block <?= !empty($settings['mission_vision_hero_bg_image']) ? 'text-white' : 'bg-gradient-to-r bg-clip-text text-transparent' ?>" <?= empty($settings['mission_vision_hero_bg_image']) ? 'style="background-image: linear-gradient(135deg, ' . escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') . ', ' . escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') . ', ' . escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') . ');"' : '' ?>>Our</span>
                        <span class="block <?= !empty($settings['mission_vision_hero_bg_image']) ? 'text-white mt-2' : 'bg-gradient-to-r bg-clip-text text-transparent mt-2' ?>" <?= empty($settings['mission_vision_hero_bg_image']) ? 'style="background-image: linear-gradient(135deg, ' . escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') . ', ' . escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') . ', ' . escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') . ');"' : '' ?>>Vision</span>
                    </h1>
                    
                    <!-- Decorative Lines -->
                    <div class="flex items-center justify-center gap-4 mb-12">
                        <div class="h-1 w-24 bg-gradient-to-r rounded-full" style="background: linear-gradient(90deg, transparent, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>);"></div>
                        <div class="w-3 h-3 rounded-full animate-pulse" style="background: <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>;"></div>
                        <div class="h-1 w-24 bg-gradient-to-l rounded-full" style="background: linear-gradient(90deg, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>, transparent);"></div>
                    </div>
                </div>
                
                <!-- Content Card with Diagonal Design -->
                <div class="relative max-w-4xl mx-auto">
                    <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl p-10 md:p-16 border border-white/50 relative overflow-hidden transform hover:scale-105 transition-all duration-500" style="
                        <?php if (!empty($settings['vision_card_bg_image'])): ?>
                            background-image: url('<?= escape($settings['vision_card_bg_image']) ?>');
                            background-size: cover;
                            background-position: center;
                        <?php endif; ?>
                    ">
                        <!-- Diagonal Background -->
                        <div class="absolute inset-0 opacity-10" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?> 0%, transparent 50%, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?> 100%);"></div>
                        
                        <!-- Corner Accents -->
                        <div class="absolute top-0 left-0 w-32 h-32 border-t-4 border-l-4 rounded-tl-3xl" style="border-color: <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>; opacity: 0.3;"></div>
                        <div class="absolute bottom-0 right-0 w-32 h-32 border-b-4 border-r-4 rounded-br-3xl" style="border-color: <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>; opacity: 0.3;"></div>
                        
                        <div class="relative z-10">
                            <p class="text-xl md:text-3xl leading-relaxed font-medium text-center" style="color: <?= escape($settings['mission_vision_text_color'] ?? '#475569') ?>;">
                                <?= nl2br(escape($settings['vision_content'] ?? '')) ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Floating Decorative Elements -->
                    <div class="absolute -top-8 -left-8 w-32 h-32 bg-purple-300 rounded-2xl transform rotate-12 opacity-20 animate-float-diagonal"></div>
                    <div class="absolute -bottom-8 -right-8 w-24 h-24 bg-pink-300 rounded-full opacity-20 animate-float-diagonal-delay"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Vision Pillars with Creative Grid Layout -->
    <section class="py-20 md:py-32 bg-gradient-to-b from-white via-purple-50/20 to-white relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 opacity-5" style="background-image: radial-gradient(circle at 2px 2px, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?> 1px, transparent 0); background-size: 40px 40px;"></div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-black mb-4 bg-gradient-to-r bg-clip-text text-transparent" style="background-image: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>);">
                    Our Vision Pillars
                </h2>
                <div class="h-1 w-32 bg-gradient-to-r rounded-full mx-auto" style="background: linear-gradient(90deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>);"></div>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8 max-w-6xl mx-auto">
                <!-- Pillar 1 - Industry Leadership -->
                <div class="group relative">
                    <div class="bg-white rounded-3xl p-10 shadow-2xl border-2 border-transparent hover:border-purple-300 transform hover:-translate-y-6 hover:scale-105 transition-all duration-700 hover:shadow-3xl relative overflow-hidden">
                        <!-- Animated Gradient Background -->
                        <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-100 transition-opacity duration-700" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>);"></div>
                        
                        <!-- Icon with 3D Effect -->
                        <div class="relative z-10 mb-6">
                            <div class="w-24 h-24 bg-gradient-to-br rounded-2xl flex items-center justify-center transform group-hover:scale-125 group-hover:rotate-12 transition-all duration-700 shadow-xl" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>);">
                                <i class="fas fa-trophy text-white text-4xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-3xl font-black mb-4 group-hover:text-white transition-colors duration-300 relative z-10" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">Industry Leadership</h3>
                        <p class="text-gray-600 group-hover:text-white/90 transition-colors duration-300 relative z-10 text-lg">Setting new standards and leading innovation in the industrial equipment sector.</p>
                        
                        <!-- Corner Accent -->
                        <div class="absolute top-4 right-4 w-16 h-16 border-t-2 border-r-2 rounded-tr-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="border-color: white;"></div>
                    </div>
                </div>
                
                <!-- Pillar 2 - Global Reach -->
                <div class="group relative">
                    <div class="bg-white rounded-3xl p-10 shadow-2xl border-2 border-transparent hover:border-indigo-300 transform hover:-translate-y-6 hover:scale-105 transition-all duration-700 hover:shadow-3xl relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-100 transition-opacity duration-700" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>);"></div>
                        
                        <div class="relative z-10 mb-6">
                            <div class="w-24 h-24 bg-gradient-to-br rounded-2xl flex items-center justify-center transform group-hover:scale-125 group-hover:rotate-12 transition-all duration-700 shadow-xl" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>);">
                                <i class="fas fa-globe text-white text-4xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-3xl font-black mb-4 group-hover:text-white transition-colors duration-300 relative z-10" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">Global Reach</h3>
                        <p class="text-gray-600 group-hover:text-white/90 transition-colors duration-300 relative z-10 text-lg">Expanding our services worldwide to serve businesses across all continents.</p>
                        
                        <div class="absolute top-4 right-4 w-16 h-16 border-t-2 border-r-2 rounded-tr-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="border-color: white;"></div>
                    </div>
                </div>
                
                <!-- Pillar 3 - Future Innovation -->
                <div class="group relative">
                    <div class="bg-white rounded-3xl p-10 shadow-2xl border-2 border-transparent hover:border-pink-300 transform hover:-translate-y-6 hover:scale-105 transition-all duration-700 hover:shadow-3xl relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-100 transition-opacity duration-700" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>);"></div>
                        
                        <div class="relative z-10 mb-6">
                            <div class="w-24 h-24 bg-gradient-to-br rounded-2xl flex items-center justify-center transform group-hover:scale-125 group-hover:rotate-12 transition-all duration-700 shadow-xl" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>);">
                                <i class="fas fa-rocket text-white text-4xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-3xl font-black mb-4 group-hover:text-white transition-colors duration-300 relative z-10" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">Future Innovation</h3>
                        <p class="text-gray-600 group-hover:text-white/90 transition-colors duration-300 relative z-10 text-lg">Embracing cutting-edge technology to shape the future of industrial equipment.</p>
                        
                        <div class="absolute top-4 right-4 w-16 h-16 border-t-2 border-r-2 rounded-tr-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="border-color: white;"></div>
                    </div>
                </div>
                
                <!-- Pillar 4 - Customer Success -->
                <div class="group relative">
                    <div class="bg-white rounded-3xl p-10 shadow-2xl border-2 border-transparent hover:border-violet-300 transform hover:-translate-y-6 hover:scale-105 transition-all duration-700 hover:shadow-3xl relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-100 transition-opacity duration-700" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>);"></div>
                        
                        <div class="relative z-10 mb-6">
                            <div class="w-24 h-24 bg-gradient-to-br rounded-2xl flex items-center justify-center transform group-hover:scale-125 group-hover:rotate-12 transition-all duration-700 shadow-xl" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>);">
                                <i class="fas fa-heart text-white text-4xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="text-3xl font-black mb-4 group-hover:text-white transition-colors duration-300 relative z-10" style="color: <?= escape($settings['mission_vision_title_color'] ?? '#1a1a1a') ?>;">Customer Success</h3>
                        <p class="text-gray-600 group-hover:text-white/90 transition-colors duration-300 relative z-10 text-lg">Dedicated to ensuring every customer achieves their operational excellence goals.</p>
                        
                        <div class="absolute top-4 right-4 w-16 h-16 border-t-2 border-r-2 rounded-tr-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="border-color: white;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Futuristic Call to Action -->
    <section class="relative py-20 md:py-32 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>);"></div>
        
        <!-- Animated Grid Overlay -->
        <div class="absolute inset-0 opacity-10" style="background-image: 
            linear-gradient(<?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?> 1px, transparent 1px),
            linear-gradient(90deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?> 1px, transparent 1px);
            background-size: 50px 50px;
        "></div>
        
        <!-- Floating Orbs -->
        <div class="absolute top-0 left-1/4 w-32 h-32 bg-white/10 rounded-full blur-2xl animate-orb-float"></div>
        <div class="absolute bottom-0 right-1/4 w-40 h-40 bg-white/10 rounded-full blur-2xl animate-orb-float-delay"></div>
        
        <div class="container mx-auto px-4 text-center relative z-10">
            <h2 class="text-4xl md:text-6xl font-black mb-6 text-white">Join Us on This Journey</h2>
            <p class="text-xl md:text-2xl mb-12 text-white/90 max-w-3xl mx-auto">Together, we'll shape the future of industrial excellence and create something extraordinary.</p>
            <div class="flex flex-wrap justify-center gap-6">
                <a href="<?= url('contact.php') ?>" class="group bg-white text-purple-600 px-12 py-5 rounded-2xl font-black text-lg hover:bg-purple-50 transition-all duration-300 transform hover:scale-110 hover:rotate-1 shadow-2xl hover:shadow-3xl relative overflow-hidden">
                    <span class="relative z-10 flex items-center">
                        <i class="fas fa-envelope mr-3 text-xl"></i>Get in Touch
                    </span>
                    <div class="absolute inset-0 bg-gradient-to-r opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: linear-gradient(135deg, <?= escape($settings['vision_icon_bg_color1'] ?? '#8b5cf6') ?>, <?= escape($settings['vision_icon_bg_color2'] ?? '#7c3aed') ?>);"></div>
                </a>
                <a href="<?= url('products.php') ?>" class="bg-white/15 backdrop-blur-xl text-white px-12 py-5 rounded-2xl font-black text-lg hover:bg-white/25 transition-all duration-300 transform hover:scale-110 hover:-rotate-1 shadow-2xl border-3 border-white/40">
                    <i class="fas fa-box mr-3 text-xl"></i>Explore Products
                </a>
            </div>
        </div>
    </section>
</main>

<style>
@keyframes orb-float {
    0%, 100% {
        transform: translateY(0px) translateX(0px) scale(1);
    }
    33% {
        transform: translateY(-30px) translateX(20px) scale(1.1);
    }
    66% {
        transform: translateY(20px) translateX(-20px) scale(0.9);
    }
}
@keyframes orb-float-delay {
    0%, 100% {
        transform: translateY(0px) translateX(0px) scale(1);
    }
    33% {
        transform: translateY(30px) translateX(-15px) scale(1.15);
    }
    66% {
        transform: translateY(-20px) translateX(25px) scale(0.85);
    }
}
@keyframes orb-float-delay-2 {
    0%, 100% {
        transform: translateY(0px) translateX(0px) scale(1);
    }
    50% {
        transform: translateY(-25px) translateX(-25px) scale(1.05);
    }
}
@keyframes rotate-3d {
    0%, 100% {
        transform: rotateX(0deg) rotateY(0deg) rotateZ(45deg);
    }
    50% {
        transform: rotateX(180deg) rotateY(90deg) rotateZ(45deg);
    }
}
@keyframes pulse-slow {
    0%, 100% {
        opacity: 0.2;
        transform: scale(1);
    }
    50% {
        opacity: 0.4;
        transform: scale(1.1);
    }
}
@keyframes glow-pulse {
    0%, 100% {
        opacity: 0.4;
        transform: scale(1.3);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.4);
    }
}
@keyframes glow-pulse-delay {
    0%, 100% {
        opacity: 0.3;
        transform: scale(1.6);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.7);
    }
}
@keyframes sparkle {
    0%, 100% {
        opacity: 0;
        transform: scale(0);
    }
    50% {
        opacity: 1;
        transform: scale(1);
    }
}
@keyframes sparkle-delay {
    0%, 100% {
        opacity: 0;
        transform: scale(0);
    }
    50% {
        opacity: 1;
        transform: scale(1);
    }
}
@keyframes fade-in-scale {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
@keyframes float-diagonal {
    0%, 100% {
        transform: translate(0, 0) rotate(12deg);
    }
    50% {
        transform: translate(20px, -20px) rotate(12deg);
    }
}
@keyframes float-diagonal-delay {
    0%, 100% {
        transform: translate(0, 0) rotate(-12deg);
    }
    50% {
        transform: translate(-20px, 20px) rotate(-12deg);
    }
}
.animate-orb-float {
    animation: orb-float 8s ease-in-out infinite;
}
.animate-orb-float-delay {
    animation: orb-float-delay 10s ease-in-out infinite;
}
.animate-orb-float-delay-2 {
    animation: orb-float-delay-2 9s ease-in-out infinite;
}
.animate-rotate-3d {
    animation: rotate-3d 15s linear infinite;
}
.animate-pulse-slow {
    animation: pulse-slow 4s ease-in-out infinite;
}
.animate-glow-pulse {
    animation: glow-pulse 3s ease-in-out infinite;
}
.animate-glow-pulse-delay {
    animation: glow-pulse-delay 3.5s ease-in-out infinite;
}
.animate-sparkle {
    animation: sparkle 2s ease-in-out infinite;
}
.animate-sparkle-delay {
    animation: sparkle-delay 2.5s ease-in-out infinite;
}
.animate-fade-in-scale {
    animation: fade-in-scale 1.2s ease-out;
}
.animate-float-diagonal {
    animation: float-diagonal 6s ease-in-out infinite;
}
.animate-float-diagonal-delay {
    animation: float-diagonal-delay 7s ease-in-out infinite;
}
.animate-3d-float {
    animation: orb-float 5s ease-in-out infinite;
}
.perspective-1000 {
    perspective: 1000px;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
