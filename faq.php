<?php
require_once __DIR__ . '/bootstrap/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get all FAQs
$faqs = db()->fetchAll("SELECT * FROM faqs WHERE is_active = 1 ORDER BY display_order, id");
$categories = array_unique(array_column($faqs, 'category'));
$category = $_GET['category'] ?? '';

if ($category) {
    $faqs = array_filter($faqs, fn($f) => $f['category'] === $category);
}

$pageTitle = 'Frequently Asked Questions - ' . get_site_name();
include __DIR__ . '/includes/header.php';
?>

<main class="py-12">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold mb-4">Frequently Asked Questions</h1>
            <p class="text-gray-600 text-lg">Find answers to common questions about our products and services</p>
        </div>
        
        <!-- Category Filter -->
        <?php if (!empty($categories)): ?>
        <div class="flex flex-wrap gap-2 mb-8 justify-center">
            <a href="<?= url('faq.php') ?>" 
               class="px-4 py-2 rounded-lg <?= !$category ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                All
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= url('faq.php?category=' . urlencode($cat)) ?>" 
                   class="px-4 py-2 rounded-lg <?= $category === $cat ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                    <?= escape($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- FAQ Accordion -->
        <div class="space-y-4">
            <?php if (empty($faqs)): ?>
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <i class="fas fa-question-circle text-6xl text-gray-300 mb-4"></i>
                    <h2 class="text-2xl font-bold mb-2">No FAQs Found</h2>
                    <p class="text-gray-600">Check back soon for frequently asked questions.</p>
                </div>
            <?php else: ?>
                <?php foreach ($faqs as $faq): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <button onclick="toggleFAQ(<?= $faq['id'] ?>)" 
                            class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                        <span class="font-semibold text-lg"><?= escape($faq['question']) ?></span>
                        <i id="faq-icon-<?= $faq['id'] ?>" class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                    </button>
                    <div id="faq-answer-<?= $faq['id'] ?>" class="hidden px-6 pb-4 text-gray-700">
                        <div class="prose max-w-none">
                            <?= nl2br(escape($faq['answer'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Contact CTA -->
        <div class="mt-12 bg-blue-50 border border-blue-200 rounded-lg p-8 text-center">
            <h2 class="text-2xl font-bold mb-4">Still have questions?</h2>
            <p class="text-gray-600 mb-6">Our team is here to help you find the perfect equipment for your needs.</p>
            <a href="<?= url('contact.php') ?>" class="btn-primary inline-block">
                Contact Us
            </a>
        </div>
    </div>
</main>

<script>
function toggleFAQ(id) {
    const answer = document.getElementById('faq-answer-' + id);
    const icon = document.getElementById('faq-icon-' + id);
    
    answer.classList.toggle('hidden');
    icon.classList.toggle('fa-chevron-down');
    icon.classList.toggle('fa-chevron-up');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

