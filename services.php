<?php
require_once __DIR__ . '/bootstrap/app.php';

// Check under construction mode
use App\Helpers\UnderConstruction;
UnderConstruction::show();

use App\Models\Service;

$serviceModel = new Service();
$services = $serviceModel->getAll(true); // Get only active services

$pageTitle = 'Our Services - ' . get_site_name();
$metaDescription = 'Comprehensive services for all your forklift and industrial equipment needs. Professional maintenance, repairs, rentals, and more.';

include __DIR__ . '/includes/header.php';
?>

<main class="py-12 md:py-16 bg-gradient-to-br from-gray-50 to-white">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-12 md:mb-16">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 text-gray-800">
                Our Services
            </h1>
            <p class="text-gray-600 text-lg md:text-xl max-w-2xl mx-auto">
                Comprehensive solutions for all your forklift and industrial equipment needs
            </p>
        </div>
        
        <?php if (empty($services)): ?>
            <div class="bg-white rounded-2xl shadow-xl p-12 md:p-16 text-center border border-gray-100">
                <div class="w-32 h-32 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-concierge-bell text-6xl text-blue-500"></i>
                </div>
                <p class="text-gray-600 text-lg">No services available yet.</p>
            </div>
        <?php else: ?>
            <!-- Services Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8 mb-12">
                <?php foreach ($services as $service): ?>
                <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 p-6 md:p-8 border border-gray-100 transform hover:-translate-y-2 group">
                    <!-- Icon or Image -->
                    <div class="mb-6">
                        <?php if (!empty($service['image'])): ?>
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                            <img src="<?= escape(image_url($service['image'])) ?>" alt="<?= escape($service['title']) ?>" class="max-w-full max-h-full object-contain">
                        </div>
                        <?php elseif (!empty($service['icon'])): ?>
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-lg">
                            <i class="<?= escape($service['icon']) ?> text-white text-3xl"></i>
                        </div>
                        <?php else: ?>
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-lg">
                            <i class="fas fa-concierge-bell text-white text-3xl"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Title -->
                    <h3 class="text-xl md:text-2xl font-bold mb-3 text-gray-800 group-hover:text-blue-600 transition-colors">
                        <?= escape($service['title']) ?>
                    </h3>
                    
                    <!-- Description -->
                    <?php if (!empty($service['description'])): ?>
                    <p class="text-gray-600 mb-4 leading-relaxed">
                        <?= escape($service['description']) ?>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Content Preview -->
                    <?php if (!empty($service['content'])): ?>
                    <div class="text-gray-500 text-sm mb-4 line-clamp-3">
                        <?= escape(strip_tags(substr($service['content'], 0, 150))) ?><?= strlen(strip_tags($service['content'])) > 150 ? '...' : '' ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- View Details Link -->
                    <a href="<?= url('service.php?slug=' . escape($service['slug'])) ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold group-hover:translate-x-2 transition-all duration-300">
                        <span>Learn More</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
