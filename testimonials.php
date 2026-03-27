<?php
require_once __DIR__ . '/bootstrap/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get testimonials
$testimonials = db()->fetchAll("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY display_order, created_at DESC");

$pageTitle = 'Customer Testimonials - ' . get_site_name();
include __DIR__ . '/includes/header.php';
?>

<main class="py-12 md:py-16 bg-gradient-to-br from-gray-50 to-white">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-12 md:mb-16">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 text-gray-800">
                What Our Customers Say
            </h1>
            <p class="text-gray-600 text-lg md:text-xl max-w-2xl mx-auto">
                Read reviews from satisfied customers who trust us for their equipment needs
            </p>
        </div>
        
        <?php if (empty($testimonials)): ?>
            <div class="bg-white rounded-2xl shadow-xl p-12 md:p-16 text-center border border-gray-100">
                <div class="w-32 h-32 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-comments text-6xl text-blue-500"></i>
                </div>
                <p class="text-gray-600 text-lg">No testimonials available yet.</p>
            </div>
        <?php else: ?>
            <!-- Testimonials Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8 mb-12">
                <?php foreach ($testimonials as $testimonial): ?>
                <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 p-6 md:p-8 border border-gray-100 transform hover:-translate-y-2 group">
                    <!-- Quote Icon -->
                    <div class="mb-4">
                        <i class="fas fa-quote-left text-4xl text-blue-200 group-hover:text-blue-400 transition-colors"></i>
                    </div>
                    
                    <!-- Rating Stars -->
                    <div class="flex items-center mb-4">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?= $i <= $testimonial['rating'] ? 'text-yellow-400' : 'text-gray-300' ?> text-lg"></i>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- Testimonial Text -->
                    <p class="text-gray-700 mb-6 italic leading-relaxed text-base">"<?= escape($testimonial['testimonial']) ?>"</p>
                    
                    <!-- Customer Info -->
                    <div class="flex items-center border-t border-gray-100 pt-4">
                        <?php if (!empty($testimonial['image'])): ?>
                            <img src="<?= asset('storage/uploads/' . escape($testimonial['image'])) ?>" 
                                 alt="<?= escape($testimonial['customer_name']) ?>"
                                 class="w-14 h-14 rounded-full object-cover mr-4 ring-2 ring-blue-100 group-hover:ring-blue-400 transition-all">
                        <?php else: ?>
                            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold mr-4 text-lg ring-2 ring-blue-100 group-hover:ring-blue-400 transition-all">
                                <?= strtoupper(substr($testimonial['customer_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="flex-1">
                            <p class="font-bold text-gray-800"><?= escape($testimonial['customer_name']) ?></p>
                            <?php if (!empty($testimonial['company'])): ?>
                                <p class="text-sm text-gray-600"><?= escape($testimonial['company']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- CTA Section -->
        <div class="bg-gradient-to-br from-blue-600 via-indigo-700 to-purple-700 text-white rounded-2xl shadow-2xl p-12 md:p-16 text-center relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-white rounded-full blur-3xl"></div>
            </div>
            <div class="relative z-10">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to Join Our Satisfied Customers?</h2>
                <p class="text-xl mb-8 text-blue-100">Get the equipment you need, when you need it.</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?= url('products.php') ?>" class="bg-white text-blue-600 px-8 py-4 rounded-xl font-bold hover:bg-gray-100 transform hover:scale-105 transition-all duration-300 shadow-xl hover:shadow-2xl inline-flex items-center justify-center">
                        <i class="fas fa-box mr-2"></i>Browse Products
                    </a>
                    <a href="<?= url('contact.php') ?>" class="bg-blue-500/20 backdrop-blur-sm border-2 border-white/30 text-white px-8 py-4 rounded-xl font-bold hover:bg-blue-500/30 transform hover:scale-105 transition-all duration-300 shadow-xl hover:shadow-2xl inline-flex items-center justify-center">
                        <i class="fas fa-envelope mr-2"></i>Contact Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

