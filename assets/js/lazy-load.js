/**
 * Lazy Loading for Images
 * Uses Intersection Observer API for efficient image loading
 */

// Function to load a lazy image with fade-in effect
function loadLazyImage(img) {
    // Check if image has data-src and isn't already loaded
    if (!img.dataset.src) {
        return false;
    }
    
    // If already loaded, skip
    if (img.classList.contains('loaded')) {
        return false;
    }
    
    // Set up fade-in when image loads
    const handleImageLoad = function() {
        // Use requestAnimationFrame for smooth animation
        requestAnimationFrame(() => {
            this.classList.add('loaded');
        });
    };
    
    // Handle image load event
    img.addEventListener('load', handleImageLoad, { once: true });
    
    // Handle error case
    img.addEventListener('error', function() {
        this.style.opacity = '0.3';
        // Still mark as loaded to prevent retries
        this.classList.add('loaded');
    }, { once: true });
    
    // Check if image is already cached/loaded
    if (img.complete && img.naturalHeight !== 0) {
        // Image already loaded (cached), fade in immediately
        requestAnimationFrame(() => {
            img.classList.add('loaded');
        });
    } else {
        // Start loading the image by setting src from data-src
        img.src = img.dataset.src;
    }
    
    return true;
}

// Function to check if image is in viewport or near viewport
function isInViewport(element, margin = 300) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top < (window.innerHeight || document.documentElement.clientHeight) + margin &&
        rect.bottom > -margin &&
        rect.left < (window.innerWidth || document.documentElement.clientWidth) + margin &&
        rect.right > -margin
    );
}

// Initialize lazy loading - can be called multiple times
function initLazyLoading() {
    // Lazy load images - include app-product-image class
    const lazyImages = document.querySelectorAll('img.lazy-load[data-src]:not(.loaded), img[data-src]:not(.loaded), .app-product-image[data-src]:not(.loaded)');
    
    if (lazyImages.length === 0) return;
    
    // First, load images that are already in viewport or near viewport immediately
    lazyImages.forEach(img => {
        const rect = img.getBoundingClientRect();
        // Load immediately if visible on screen or within 200px
        const isVisible = rect.top < window.innerHeight + 200 && rect.bottom > -200;
        if (isVisible) {
            loadLazyImage(img);
        }
    });
    
    // Then set up Intersection Observer for remaining images
    const remainingImages = document.querySelectorAll('img.lazy-load[data-src]:not(.loaded), img[data-src]:not(.loaded)');
    
    if ('IntersectionObserver' in window && remainingImages.length > 0) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    // Load immediately when entering viewport
                    if (loadLazyImage(img)) {
                        observer.unobserve(img);
                    }
                }
            });
        }, {
            rootMargin: '100px' // Start loading 100px before image enters viewport
        });
        
        remainingImages.forEach(img => {
            // Only observe if not already loaded
            if (!img.classList.contains('loaded')) {
                imageObserver.observe(img);
            }
        });
    } else if (remainingImages.length > 0) {
        // Fallback for browsers without IntersectionObserver - load all immediately
        remainingImages.forEach(img => {
            loadLazyImage(img);
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize lazy loading immediately
    initLazyLoading();
    
    // Immediate check: Load all images that are currently visible (including app-product-image)
    setTimeout(function() {
        const visibleImages = document.querySelectorAll('img.lazy-load[data-src]:not(.loaded), .app-product-image[data-src]:not(.loaded), img[data-src]:not(.loaded)');
        visibleImages.forEach(img => {
            const rect = img.getBoundingClientRect();
            // Load if visible on screen or within viewport
            if (rect.top < window.innerHeight + 100 && rect.bottom > -100) {
                loadLazyImage(img);
            }
        });
    }, 50);
    
    // Also load images on window load - load all visible images immediately
    window.addEventListener('load', function() {
        const remainingLazyImages = document.querySelectorAll('img.lazy-load[data-src]:not(.loaded), .app-product-image[data-src]:not(.loaded), img[data-src]:not(.loaded)');
        remainingLazyImages.forEach(img => {
            const rect = img.getBoundingClientRect();
            // Load immediately if in viewport or near viewport
            if (rect.top < window.innerHeight + 300) {
                loadLazyImage(img);
            }
        });
    });
    
    // Load More Products Functionality
    const loadMoreBtn = document.getElementById('load-more-btn');
    let isLoading = false;
    let scrollTimeout = null;
    let isAtBottom = false;
    
    // Function to load more products
    function loadMoreProducts() {
        if (!loadMoreBtn || isLoading) return;
        
        const btn = loadMoreBtn;
        const spinner = document.getElementById('load-more-spinner');
        const text = document.getElementById('load-more-text');
        const currentPage = parseInt(btn.dataset.currentPage) || 1;
        const totalPages = parseInt(btn.dataset.totalPages) || 1;
        const nextPage = currentPage + 1;
        
        // Check if there are more pages
        if (nextPage > totalPages) {
            return;
        }
        
        isLoading = true;
        
        // Show loading state
        if (spinner) spinner.classList.remove('hidden');
        if (text) text.textContent = 'Loading...';
        if (btn) btn.disabled = true;
        
        // Build query params - get all current filters from URL
        const urlParams = new URLSearchParams(window.location.search);
        const params = new URLSearchParams();
        params.append('page', nextPage);
        
        // Add all filter parameters from URL to maintain filter state
        if (urlParams.get('category')) params.append('category', urlParams.get('category'));
        if (urlParams.get('search')) params.append('search', urlParams.get('search'));
        if (urlParams.get('featured')) params.append('featured', urlParams.get('featured'));
        if (urlParams.get('min_price')) params.append('min_price', urlParams.get('min_price'));
        if (urlParams.get('max_price')) params.append('max_price', urlParams.get('max_price'));
        if (urlParams.get('in_stock')) params.append('in_stock', urlParams.get('in_stock'));
        if (urlParams.get('sort')) params.append('sort', urlParams.get('sort'));
        
        // Also check button data attributes as fallback
        if (btn.dataset.category && !params.has('category')) {
            params.append('category', btn.dataset.category);
        }
        if (btn.dataset.search && !params.has('search')) {
            params.append('search', btn.dataset.search);
        }
        if (btn.dataset.featured && !params.has('featured')) {
            params.append('featured', btn.dataset.featured);
        }
        
        // Fetch more products
        const apiUrl = window.APP_CONFIG?.urls?.loadMore || 'api/load-more-products.php';
        fetch(`${apiUrl}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Append new products
                    const productsGrid = document.getElementById('products-grid');
                    if (productsGrid) {
                        // Create temporary container to parse HTML
                        const temp = document.createElement('div');
                        temp.innerHTML = data.html;
                        
                        // Collect images BEFORE appending (while they're still in temp)
                        const newImages = [];
                        Array.from(temp.children).forEach(product => {
                            const images = product.querySelectorAll('img[data-src]');
                            images.forEach(img => {
                                newImages.push(img);
                            });
                        });
                        
                        // Get all product cards (using .app-product-card class for consistency)
                        const newProducts = temp.querySelectorAll('.app-product-card');
                        
                        // Append each product with fade-in animation
                        newProducts.forEach((product, index) => {
                            product.style.opacity = '0';
                            product.style.transform = 'translateY(20px)';
                            productsGrid.appendChild(product);
                            
                            // Animate in
                            setTimeout(() => {
                                product.style.transition = 'all 0.5s ease';
                                product.style.opacity = '1';
                                product.style.transform = 'translateY(0)';
                            }, index * 50);
                        });
                        
                        // Reapply layout to newly loaded products
                        if (typeof setLayout === 'function') {
                            const savedLayout = localStorage.getItem('productLayout') || 'grid';
                            setTimeout(() => {
                                setLayout(savedLayout);
                            }, 100);
                        }
                        
                        // Load all new images immediately after DOM update
                        // Use multiple animation frames to ensure DOM is ready
                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                // Load all collected images
                                newImages.forEach((img, index) => {
                                    // Small delay to stagger loading slightly
                                    setTimeout(() => {
                                        // Ensure lazy-load class is present
                                        if (!img.classList.contains('lazy-load')) {
                                            img.classList.add('lazy-load');
                                        }
                                        
                                        // Skip if already loaded
                                        if (img.classList.contains('loaded')) {
                                            return;
                                        }
                                        
                                        // Directly set src from data-src to force immediate loading
                                        if (img.dataset.src) {
                                            // Always load - don't check placeholder, just load
                                            // Set up load handler
                                            const loadHandler = function() {
                                                requestAnimationFrame(() => {
                                                    this.classList.add('loaded');
                                                });
                                            };
                                            
                                            // Set up error handler
                                            const errorHandler = function() {
                                                this.style.opacity = '0.3';
                                                this.classList.add('loaded');
                                            };
                                            
                                            // Remove any existing handlers to avoid duplicates
                                            img.removeEventListener('load', loadHandler);
                                            img.removeEventListener('error', errorHandler);
                                            
                                            // Add handlers
                                            img.addEventListener('load', loadHandler, { once: true });
                                            img.addEventListener('error', errorHandler, { once: true });
                                            
                                            // Set src to trigger loading immediately
                                            img.src = img.dataset.src;
                                        }
                                    }, index * 10); // Stagger by 10ms per image
                                });
                                
                                // Double-check: Find any remaining unloaded images in the grid
                                setTimeout(() => {
                                    const remainingImages = productsGrid.querySelectorAll('img[data-src]:not(.loaded)');
                                    remainingImages.forEach(img => {
                                        if (!img.classList.contains('lazy-load')) {
                                            img.classList.add('lazy-load');
                                        }
                                        
                                        // Force load if still has placeholder or empty src
                                        if (img.dataset.src) {
                                            const needsLoad = img.src.includes('data:image/svg+xml') || 
                                                             img.src === '' || 
                                                             !img.src;
                                            
                                            if (needsLoad) {
                                                img.addEventListener('load', function() {
                                                    this.classList.add('loaded');
                                                }, { once: true });
                                                img.addEventListener('error', function() {
                                                    this.style.opacity = '0.3';
                                                    this.classList.add('loaded');
                                                }, { once: true });
                                                img.src = img.dataset.src;
                                            } else {
                                                loadLazyImage(img);
                                            }
                                        }
                                    });
                                    
                                // Initialize lazy loading for any future images
                                initLazyLoading();
                                
                                // Ensure overlay functionality works for newly loaded products
                                const newProductCards = productsGrid.querySelectorAll('.app-product-card:not([data-overlay-checked])');
                                newProductCards.forEach(card => {
                                    // Mark as checked
                                    card.setAttribute('data-overlay-checked', 'true');
                                    
                                    // Verify overlay exists and is properly positioned
                                    const imageWrapper = card.querySelector('.app-product-image-wrapper');
                                    const overlay = card.querySelector('.app-product-overlay');
                                    
                                    if (imageWrapper && overlay) {
                                        // Ensure image wrapper has position relative (should be from CSS)
                                        const computedStyle = window.getComputedStyle(imageWrapper);
                                        if (computedStyle.position === 'static') {
                                            imageWrapper.style.position = 'relative';
                                        }
                                        
                                        // Ensure overlay has position absolute (should be from CSS)
                                        const overlayStyle = window.getComputedStyle(overlay);
                                        if (overlayStyle.position !== 'absolute') {
                                            overlay.style.position = 'absolute';
                                            overlay.style.inset = '0';
                                        }
                                    }
                                });
                                
                                // Ensure feature buttons are properly initialized on newly loaded products
                                // The toggleFeatured function is defined globally in products.php
                                // All feature buttons should work automatically since they're in the HTML
                                // But we can verify they're present
                                const featureButtons = productsGrid.querySelectorAll('.app-overlay-btn-feature');
                                if (featureButtons.length > 0 && typeof toggleFeatured === 'function') {
                                    // Feature buttons are present and function is available
                                    // They will work automatically via onclick handlers
                                }
                            }, 300);
                        });
                        
                        // Reapply layout to newly loaded products after a short delay
                        setTimeout(() => {
                            // Check if setLayout function exists (from products.php)
                            if (typeof window.setLayout === 'function') {
                                const savedLayout = localStorage.getItem('productLayout') || 'grid';
                                window.setLayout(savedLayout);
                            } else {
                                // Fallback: manually apply layout classes
                                const savedLayout = localStorage.getItem('productLayout') || 'grid';
                                const container = document.getElementById('products-grid');
                                const allItems = container.querySelectorAll('.product-item');
                                
                                // Remove all layout classes
                                container.classList.remove('grid', 'list-view', 'compact-view', 'sm:grid-cols-2', 'md:grid-cols-3', 'lg:grid-cols-3', 'lg:grid-cols-4', 'gap-6', 'gap-4', 'space-y-4');
                                allItems.forEach(item => {
                                    item.classList.remove('grid-item', 'list-item', 'compact-item', 'flex');
                                });
                                
                                // Apply layout
                                if (savedLayout === 'grid') {
                                    container.classList.add('grid', 'sm:grid-cols-2', 'lg:grid-cols-3', 'gap-6');
                                    allItems.forEach(item => item.classList.add('grid-item'));
                                } else if (savedLayout === 'list') {
                                    container.classList.add('list-view', 'space-y-4');
                                    allItems.forEach(item => {
                                        item.classList.add('list-item', 'flex', 'gap-4');
                                    });
                                } else if (savedLayout === 'compact') {
                                    container.classList.add('grid', 'sm:grid-cols-2', 'md:grid-cols-3', 'lg:grid-cols-4', 'gap-4', 'compact-view');
                                    allItems.forEach(item => item.classList.add('compact-item'));
                                }
                            }
                        }, 350);
                    });
                    }
                    
                    // Update button state
                    btn.dataset.currentPage = nextPage;
                    
                    if (data.hasMore) {
                        // Update remaining count
                        const remaining = data.totalProducts - (nextPage * 12);
                        const countEl = document.getElementById('load-more-count');
                        if (countEl) {
                            countEl.textContent = `(${remaining > 0 ? remaining : 0} remaining)`;
                        }
                        
                        // Re-enable button
                        if (spinner) spinner.classList.add('hidden');
                        if (text) text.textContent = 'Load More Products';
                        isLoading = false;
                    } else {
                        // Hide button if no more products
                        const container = document.getElementById('load-more-container');
                        if (container) {
                            container.style.opacity = '0';
                            setTimeout(() => {
                                container.style.display = 'none';
                            }, 300);
                        }
                        isLoading = false;
                    }
                } else {
                    // Error handling
                    if (spinner) spinner.classList.add('hidden');
                    if (text) text.textContent = 'Load More Products';
                    isLoading = false;
                    alert('Failed to load more products. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error loading more products:', error);
                if (spinner) spinner.classList.add('hidden');
                if (text) text.textContent = 'Load More Products';
                isLoading = false;
                alert('Error loading products. Please try again.');
            });
    }
    
    // Auto-load products when user scrolls to 70% of page height or hits the bottom
    if (loadMoreBtn) {
        let lastScrollPercent = 0;
        let hasLoadedAt70 = false;
        
        // Function to check scroll position and auto-load
        function checkScrollAndLoad() {
            if (isLoading) return;
            
            const btn = loadMoreBtn;
            const currentPage = parseInt(btn.dataset.currentPage) || 1;
            const totalPages = parseInt(btn.dataset.totalPages) || 1;
            
            // Check if there are more pages to load
            if (currentPage >= totalPages) {
                return; // No more products to load
            }
            
            // Calculate scroll percentage
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollPercent = (scrollTop + windowHeight) / documentHeight * 100;
            
            // Check if user hit the bottom (within 50px of bottom)
            const isAtBottom = scrollTop + windowHeight >= documentHeight - 50;
            
            // Auto-load when user reaches 70% of page or hits the bottom
            if ((scrollPercent >= 70 && !hasLoadedAt70) || isAtBottom) {
                loadMoreProducts();
                hasLoadedAt70 = true;
            }
            
            // Reset flag if user scrolls back up significantly
            if (scrollPercent < 60) {
                hasLoadedAt70 = false;
            }
            
            lastScrollPercent = scrollPercent;
        }
        
        // Throttle scroll event for better performance
        let scrollTimeout = null;
        window.addEventListener('scroll', function() {
            if (scrollTimeout) {
                clearTimeout(scrollTimeout);
            }
            scrollTimeout = setTimeout(checkScrollAndLoad, 100); // Check every 100ms
        }, { passive: true });
        
        // Also check on initial load in case page is already scrolled
        checkScrollAndLoad();
        
        // Manual click handler (still works if user wants to click)
        loadMoreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!isLoading) {
                loadMoreProducts();
            }
        });
    }
});

