<?php
require_once __DIR__ . '/bootstrap/app.php';

// Check under construction mode
use App\Helpers\UnderConstruction;
UnderConstruction::show();

use App\Models\Service;

if (empty($_GET['slug'])) {
    header('Location: ' . url('services.php'));
    exit;
}

$serviceModel = new Service();
$service = $serviceModel->getBySlug($_GET['slug']);

if (!$service) {
    http_response_code(404);
    $pageTitle = 'Service Not Found';
    include __DIR__ . '/includes/header.php';
    ?>
    <main class="py-12">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold mb-4">Service Not Found</h1>
            <p class="text-gray-600 mb-6">The service you're looking for doesn't exist.</p>
            <a href="<?= url('services.php') ?>" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-all inline-block">
                <i class="fas fa-arrow-left mr-2"></i>Back to Services
            </a>
        </div>
    </main>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Set page title and meta
$pageTitle = !empty($service['meta_title']) ? $service['meta_title'] : $service['title'] . ' - ' . get_site_name();
$metaDescription = $service['meta_description'] ?? $service['description'] ?? '';
$canonicalUrl = url('service.php?slug=' . urlencode($service['slug']));
$ogImage = !empty($service['image']) ? image_url($service['image']) : (function_exists('get_seo_defaults') ? (get_seo_defaults()['og_image'] ?? '') : '');

include __DIR__ . '/includes/header.php';
?>

<main class="py-8 md:py-12">
    <div class="container mx-auto px-4">
        <!-- Breadcrumbs -->
        <nav class="mb-6 text-sm text-gray-600">
            <ol class="flex items-center space-x-2">
                <li><a href="<?= url('index.php') ?>" class="hover:text-blue-600 transition-colors">Home</a></li>
                <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
                <li><a href="<?= url('services.php') ?>" class="hover:text-blue-600 transition-colors">Services</a></li>
                <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
                <li class="text-gray-900 font-semibold"><?= escape($service['title']) ?></li>
            </ol>
        </nav>

        <!-- Service Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-lg p-6 md:p-8 mb-8 text-white">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                <?php if (!empty($service['image'])): ?>
                <div class="flex-shrink-0">
                    <img src="<?= escape(image_url($service['image'])) ?>" alt="<?= escape($service['title']) ?>" class="h-24 w-24 md:h-32 md:w-32 object-contain bg-white rounded-lg p-4">
                </div>
                <?php elseif (!empty($service['icon'])): ?>
                <div class="flex-shrink-0">
                    <div class="w-24 h-24 md:w-32 md:h-32 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <i class="<?= escape($service['icon']) ?> text-white text-4xl md:text-5xl"></i>
                    </div>
                </div>
                <?php endif; ?>
                <div class="flex-1">
                    <h1 class="text-3xl md:text-4xl font-bold mb-2"><?= escape($service['title']) ?></h1>
                    <?php if (!empty($service['description'])): ?>
                    <p class="text-blue-100 text-lg"><?= escape($service['description']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Service Content -->
        <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 mb-8">
            <div class="prose prose-lg max-w-none">
                <?php if (!empty($service['content'])): ?>
                    <?= $service['content'] ?>
                <?php else: ?>
                    <p class="text-gray-600"><?= escape($service['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Back to Services -->
        <div class="bg-gray-50 rounded-lg p-4">
            <a href="<?= url('services.php') ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to All Services
            </a>
        </div>
    </div>
</main>

<style>
.prose {
    color: #374151;
    line-height: 1.75;
}

.prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
    font-weight: 700;
    margin-top: 2em;
    margin-bottom: 1em;
    color: #1f2937;
}

.prose p {
    margin-bottom: 1.25em;
}

.prose ul, .prose ol {
    margin-bottom: 1.25em;
    padding-left: 1.625em;
}

.prose li {
    margin-bottom: 0.5em;
}

.prose a {
    color: #2563eb;
    text-decoration: underline;
}

.prose a:hover {
    color: #1d4ed8;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
