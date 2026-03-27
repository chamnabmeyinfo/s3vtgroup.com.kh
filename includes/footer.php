    <!-- Footer -->
    <?php
    use App\Models\Setting;
    use App\Models\Footer;
    use App\Models\Category;
    
    $settingModel = new Setting();
    $footerModel = new Footer();
    $categoryModel = new Category();
    
    $siteName = $settingModel->get('site_name', 'ForkliftPro');
    $siteEmail = $settingModel->get('site_email', 'info@example.com');
    $sitePhone = $settingModel->get('site_phone', '+1 (555) 123-4567');
    $siteAddress = $settingModel->get('site_address', '123 Industrial Way');
    
    // Get footer content from database
    $companyInfo = $footerModel->getCompanyInfo();
    $companyDescription = $companyInfo['content'] ?? 'Premium industrial equipment for your business needs. Quality, reliability, and expert support.';
    
    $quickLinks = $footerModel->getQuickLinks();
    $socialMedia = $footerModel->getSocialMedia();
    $bottomLinks = $footerModel->getBottomContent();
    
    // Get categories for footer
    $footerCategories = $categoryModel->getAll(true);
    $footerCategories = array_slice($footerCategories, 0, 4); // Show first 4 categories
    
    // Get footer text
    $footerText = $settingModel->get('footer_text', '© ' . date('Y') . ' ' . $siteName . '. All rights reserved.');
    ?>
    <footer class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white py-12 md:py-16 relative overflow-hidden">
        <!-- Decorative Elements -->
        <div class="absolute top-0 left-0 w-full h-full opacity-5">
            <div class="absolute top-20 left-10 w-72 h-72 bg-blue-500 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500 rounded-full blur-3xl"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 md:gap-12 mb-12">
                <!-- Company Info -->
                <div class="lg:col-span-1">
                    <h3 class="text-2xl font-bold mb-4 text-white">
                        <?= escape($siteName) ?>
                    </h3>
                    <p class="text-gray-400 mb-6 leading-relaxed">
                        <?= nl2br(escape($companyDescription)) ?>
                    </p>
                    <!-- Social Links -->
                    <?php if (!empty($socialMedia)): ?>
                    <div class="flex gap-3">
                        <?php foreach ($socialMedia as $social): ?>
                        <a href="<?= escape($social['link_url']) ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="w-10 h-10 bg-white/10 hover:bg-blue-600 rounded-xl flex items-center justify-center transition-all duration-300 transform hover:scale-110 hover:rotate-3"
                           title="<?= escape($social['link_text']) ?>">
                            <?php if ($social['icon']): ?>
                                <i class="<?= escape($social['icon']) ?>"></i>
                            <?php else: ?>
                                <i class="fas fa-share-alt"></i>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h4 class="font-bold text-lg mb-6 flex items-center">
                        <i class="fas fa-link mr-2 text-blue-400"></i>Quick Links
                    </h4>
                    <ul class="space-y-3">
                        <?php if (!empty($quickLinks)): ?>
                            <?php foreach ($quickLinks as $link): ?>
                            <li>
                                <a href="<?= escape($link['link_url']) ?>" 
                                   class="text-gray-400 hover:text-white transition-colors flex items-center group">
                                    <?php if ($link['icon']): ?>
                                        <i class="<?= escape($link['icon']) ?> text-xs mr-2 group-hover:translate-x-1 transition-transform"></i>
                                    <?php else: ?>
                                        <i class="fas fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform"></i>
                                    <?php endif; ?>
                                    <?= escape($link['link_text']) ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback default links if none configured -->
                            <li><a href="<?= url() ?>" class="text-gray-400 hover:text-white transition-colors flex items-center group">
                                <i class="fas fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform"></i>Home
                            </a></li>
                            <li><a href="<?= url('products.php') ?>" class="text-gray-400 hover:text-white transition-colors flex items-center group">
                                <i class="fas fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform"></i>Products
                            </a></li>
                            <li><a href="<?= url('contact.php') ?>" class="text-gray-400 hover:text-white transition-colors flex items-center group">
                                <i class="fas fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform"></i>Contact
                            </a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Categories -->
                <div>
                    <h4 class="font-bold text-lg mb-6 flex items-center">
                        <i class="fas fa-th-large mr-2 text-blue-400"></i>Categories
                    </h4>
                    <ul class="space-y-3">
                        <?php if (!empty($footerCategories)): ?>
                            <?php foreach ($footerCategories as $category): ?>
                            <li>
                                <a href="<?= url('products.php?category=' . escape($category['slug'])) ?>" 
                                   class="text-gray-400 hover:text-white transition-colors flex items-center group">
                                    <i class="fas fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform"></i>
                                    <?= escape($category['name']) ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="text-gray-400 text-sm">No categories available</li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Contact & Newsletter -->
                <div>
                    <h4 class="font-bold text-lg mb-6 flex items-center">
                        <i class="fas fa-envelope mr-2 text-blue-400"></i>Stay Connected
                    </h4>
                    <div class="space-y-4 mb-6">
                        <div class="flex items-start group">
                            <i class="fas fa-phone text-blue-400 mr-3 mt-1"></i>
                            <a href="tel:<?= escape($sitePhone) ?>" class="text-gray-400 hover:text-white transition-colors">
                                <?= escape($sitePhone) ?>
                            </a>
                        </div>
                        <div class="flex items-start group">
                            <i class="fas fa-envelope text-blue-400 mr-3 mt-1"></i>
                            <a href="mailto:<?= escape($siteEmail) ?>" class="text-gray-400 hover:text-white transition-colors break-all">
                                <?= escape($siteEmail) ?>
                            </a>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-map-marker-alt text-blue-400 mr-3 mt-1"></i>
                            <span class="text-gray-400"><?= escape($siteAddress) ?></span>
                        </div>
                    </div>
                    
                    <!-- Newsletter -->
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <h5 class="font-semibold mb-3 text-sm">Newsletter</h5>
                        <form id="newsletter-form" class="space-y-2">
                            <input type="email" 
                                   id="newsletter-email" 
                                   placeholder="Your email" 
                                   required
                                   class="w-full px-3 py-2 rounded-lg text-gray-900 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-4 py-2 rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 text-sm font-semibold transform hover:scale-105">
                                <i class="fas fa-paper-plane mr-2"></i>Subscribe
                            </button>
                        </form>
                        <p id="newsletter-message" class="text-xs mt-2 hidden"></p>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Bar -->
            <div class="border-t border-gray-700/50 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div>
                        <p class="text-gray-400 text-sm"><?= nl2br(escape($footerText)) ?></p>
                    </div>
                    <div class="flex gap-6 text-sm text-gray-400 flex-wrap">
                        <?php if (!empty($bottomLinks)): ?>
                            <?php foreach ($bottomLinks as $link): ?>
                            <a href="<?= escape($link['link_url']) ?>" 
                               class="hover:text-white transition-colors">
                                <?= escape($link['link_text']) ?>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback default links -->
                            <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                            <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                            <a href="#" class="hover:text-white transition-colors">Cookie Policy</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <script>
    document.getElementById('newsletter-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('newsletter-email').value;
        const messageEl = document.getElementById('newsletter-message');
        
        fetch('<?= url('api/newsletter.php') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=subscribe&email=' + encodeURIComponent(email)
        })
        .then(response => response.json())
        .then(data => {
            messageEl.classList.remove('hidden');
            messageEl.className = 'text-sm mt-2 ' + (data.success ? 'text-green-400' : 'text-red-400');
            messageEl.textContent = data.message;
            if (data.success) {
                document.getElementById('newsletter-email').value = '';
            }
        });
    });
    </script>

    <!-- Quick View Modal -->
    <?php include __DIR__ . '/quick-view-modal.php'; ?>
    
    <!-- Live Chat Widget -->
    <?php include __DIR__ . '/live-chat.php'; ?>
    
    <!-- Image Zoom -->
    <?php include __DIR__ . '/image-zoom.php'; ?>

    
    <!-- Global API URLs Config -->
    <script>
    // Global configuration for API URLs
    window.APP_CONFIG = {
        apiUrl: '<?= url("api") ?>',
        baseUrl: '<?= url() ?>',
        urls: {
            search: '<?= url("api/search.php") ?>',
            smartSearch: '<?= url("api/smart-search.php") ?>',
            cart: '<?= url("api/cart.php") ?>',
            wishlist: '<?= url("api/wishlist.php") ?>',
            compare: '<?= url("api/compare.php") ?>',
            loadMore: '<?= url("api/load-more-products.php") ?>',
            products: '<?= url("products.php") ?>'
        }
    };
    </script>
    
    <!-- Modern Navigation & Slider Scripts -->
    <script>
    // Slider is initialized in slider.php
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile Bottom Navigation - More Menu Toggle
        window.toggleMobileMoreMenu = function() {
            const moreMenu = document.getElementById('mobile-more-menu');
            const moreBtn = document.getElementById('mobile-more-btn');
            
            if (moreMenu && moreBtn) {
                const isOpen = !moreMenu.classList.contains('hidden');
                
                if (isOpen) {
                    moreMenu.classList.add('hidden');
                    moreBtn.classList.remove('active');
                } else {
                    moreMenu.classList.remove('hidden');
                    moreBtn.classList.add('active');
                }
            }
        };
        
        // Close More Menu when clicking overlay
        document.addEventListener('click', function(event) {
            const moreMenu = document.getElementById('mobile-more-menu');
            const moreBtn = document.getElementById('mobile-more-btn');
            const moreContent = moreMenu?.querySelector('.mobile-more-menu-content');
            
            if (moreMenu && !moreMenu.classList.contains('hidden')) {
                // If clicking outside the content area
                if (moreContent && !moreContent.contains(event.target) && 
                    event.target !== moreBtn && !moreBtn?.contains(event.target)) {
                    moreMenu.classList.add('hidden');
                    if (moreBtn) moreBtn.classList.remove('active');
                }
            }
        });
        
        // Update badge counts for bottom nav
        function updateMobileNavBadges() {
            // Cart count
            const cartCount = document.getElementById('cart-count')?.textContent || '0';
            const cartBadgeMobile = document.getElementById('cart-count-mobile');
            if (cartBadgeMobile) {
                cartBadgeMobile.textContent = cartCount;
                cartBadgeMobile.style.display = cartCount > 0 ? 'flex' : 'none';
            }
            const cartBadgeMore = document.getElementById('cart-count-more');
            if (cartBadgeMore) {
                cartBadgeMore.textContent = cartCount;
                cartBadgeMore.style.display = cartCount > 0 ? 'flex' : 'none';
            }
            
            // Compare count
            const compareCount = document.getElementById('compare-count')?.textContent || '0';
            const compareBadgeMore = document.getElementById('compare-count-more');
            if (compareBadgeMore) {
                compareBadgeMore.textContent = compareCount;
                compareBadgeMore.style.display = compareCount > 0 ? 'flex' : 'none';
            }
        }
        
        // Update badges on load
        updateMobileNavBadges();
        
        // Initialize compare and wishlist counts on page load
        if (typeof updateCompareCount === 'function') {
            updateCompareCount();
        }
        if (typeof updateWishlistCount === 'function') {
            updateWishlistCount();
        }
        
        // Update badges when cart/compare changes (listen for custom events)
        document.addEventListener('cartUpdated', updateMobileNavBadges);
        document.addEventListener('compareUpdated', updateMobileNavBadges);
        
        // Navbar scroll effect
        const nav = document.getElementById('main-nav');
        let lastScroll = 0;
        
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
            
            lastScroll = currentScroll;
        });
    });
    
    // Mobile Accordion Toggle
    function toggleMobileAccordion(button) {
        const content = button.nextElementSibling;
        const icon = button.querySelector('.fa-chevron-down');
        
        if (content) {
            content.classList.toggle('hidden');
            if (icon) {
                icon.classList.toggle('rotate-180');
            }
        }
    }
    </script>
    
    <!-- JavaScript -->
    <script src="<?= asset('assets/js/lazy-load.js') ?>"></script>
    <script src="<?= asset('assets/js/main.js') ?>"></script>
    <script src="<?= asset('assets/js/advanced-ux.js') ?>"></script>
    <script src="<?= asset('assets/js/advanced-search.js') ?>"></script>
    <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
    <script src="<?= asset('assets/js/hero-slider.js') ?>"></script>
    <script src="<?= asset('assets/js/hero-slider-advanced.js') ?>"></script>
    <script src="<?= asset('assets/js/partners-slider.js') ?>"></script>
    <script src="<?= asset('assets/js/quality-certifications-slider.js') ?>"></script>
    <?php endif; ?>
</body>
</html>

