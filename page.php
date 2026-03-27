<?php
require_once __DIR__ . '/bootstrap/app.php';

// Check under construction mode
use App\Helpers\UnderConstruction;
UnderConstruction::show();

use App\Models\Page;

if (empty($_GET['slug'])) {
    header('Location: ' . url('index.php'));
    exit;
}

$pageModel = new Page();
$page = $pageModel->getBySlug($_GET['slug']);

if (!$page) {
    http_response_code(404);
    $pageTitle = 'Page Not Found';
    include __DIR__ . '/includes/header.php';
    ?>
    <main class="py-12">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold mb-4">Page Not Found</h1>
            <p class="text-gray-600 mb-6">The page you're looking for doesn't exist.</p>
            <a href="<?= url('index.php') ?>" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-all inline-block">
                <i class="fas fa-home mr-2"></i>Go to Homepage
            </a>
        </div>
    </main>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Set page title and meta
$pageTitle = !empty($page['meta_title']) ? $page['meta_title'] : $page['title'] . ' - ' . get_site_name();
$metaDescription = $page['meta_description'] ?? '';
$canonicalUrl = url('page.php?slug=' . urlencode($page['slug']));
$ogImage = (function_exists('get_seo_defaults') ? (get_seo_defaults()['og_image'] ?? '') : '');

include __DIR__ . '/includes/header.php';
?>

<main class="py-8 md:py-12">
    <div class="container mx-auto px-4">
        <!-- Breadcrumbs -->
        <nav class="mb-6 text-sm text-gray-600">
            <ol class="flex items-center space-x-2">
                <li><a href="<?= url('index.php') ?>" class="hover:text-blue-600 transition-colors">Home</a></li>
                <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
                <li class="text-gray-900 font-semibold"><?= escape($page['title']) ?></li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-lg p-6 md:p-8 mb-8 text-white">
            <h1 class="text-3xl md:text-4xl font-bold mb-2"><?= escape($page['title']) ?></h1>
            <?php if (!empty($page['meta_description'])): ?>
            <p class="text-blue-100 text-lg"><?= escape($page['meta_description']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Page Content -->
        <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 mb-8">
            <div class="prose prose-lg max-w-none">
                <?= $page['content'] ?>
            </div>
        </div>

        <!-- Page Info -->
        <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <span>
                        <i class="fas fa-calendar mr-2"></i>
                        Created: <?= date('F d, Y', strtotime($page['created_at'])) ?>
                    </span>
                    <?php if ($page['updated_at'] != $page['created_at']): ?>
                    <span>
                        <i class="fas fa-edit mr-2"></i>
                        Updated: <?= date('F d, Y', strtotime($page['updated_at'])) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="<?= url('index.php') ?>" class="text-blue-600 hover:text-blue-800 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Home
                    </a>
                </div>
            </div>
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
    color: #111827;
}

.prose h1 { font-size: 2.25em; }
.prose h2 { font-size: 1.875em; }
.prose h3 { font-size: 1.5em; }
.prose h4 { font-size: 1.25em; }

.prose p {
    margin-bottom: 1.25em;
}

.prose ul, .prose ol {
    margin-bottom: 1.25em;
    padding-left: 1.625em;
}

.prose li {
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}

.prose a {
    color: #2563eb;
    text-decoration: underline;
}

.prose a:hover {
    color: #1d4ed8;
}

.prose img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1.5em 0;
}

.prose blockquote {
    border-left: 4px solid #3b82f6;
    padding-left: 1em;
    margin: 1.5em 0;
    font-style: italic;
    color: #6b7280;
}

.prose code {
    background-color: #f3f4f6;
    padding: 0.125em 0.375em;
    border-radius: 0.25rem;
    font-size: 0.875em;
    font-family: 'Courier New', monospace;
}

.prose pre {
    background-color: #1f2937;
    color: #f9fafb;
    padding: 1em;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin: 1.5em 0;
}

.prose pre code {
    background-color: transparent;
    padding: 0;
    color: inherit;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
