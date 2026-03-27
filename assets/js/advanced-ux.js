// Advanced User Experience Enhancements

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== ONE-CLICK ADD TO CART =====
    initOneClickAddToCart();
    
    // ===== INFINITE SCROLL =====
    initInfiniteScroll();
    
    // ===== QUICK VIEW ENHANCEMENTS =====
    initQuickViewEnhancements();
    
    // ===== SMOOTH ANIMATIONS =====
    initSmoothAnimations();
    
    // ===== PRODUCT HOVER EFFECTS =====
    initProductHoverEffects();
    
    // ===== SMART TOAST NOTIFICATIONS =====
    initSmartNotifications();
    
    // ===== AUTO-SAVE CART =====
    initAutoSaveCart();
    
    // ===== QUICK FILTERS =====
    initQuickFilters();
    
    // ===== BACK TO TOP BUTTON =====
    initBackToTop();
    
    // ===== LOADING STATES =====
    initLoadingStates();
});

// One-click add to cart from listings
function initOneClickAddToCart() {
    document.querySelectorAll('[data-quick-add-cart]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.quickAddCart;
            const button = this;
            
            // Show loading state
            const originalHTML = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Add to cart
            const cartUrl = window.APP_CONFIG?.urls?.cart || 'api/cart.php';
            fetch(`${cartUrl}?action=add&product_id=${productId}`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Success animation
                    button.innerHTML = '<i class="fas fa-check"></i> Added!';
                    button.classList.add('bg-green-500');
                    
                    // Show notification
                    if (typeof showNotification === 'function') {
                        showNotification('Product added to cart!', 'success');
                    }
                    
                    // Update cart count
                    if (typeof updateCartCount === 'function') {
                        updateCartCount();
                    }
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.classList.remove('bg-green-500');
                        button.disabled = false;
                    }, 2000);
                } else {
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                    if (typeof showNotification === 'function') {
                        showNotification(data.message || 'Error adding to cart', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                button.innerHTML = originalHTML;
                button.disabled = false;
                if (typeof showNotification === 'function') {
                    showNotification('Error adding to cart', 'error');
                }
            });
        });
    });
}

// Infinite scroll for products
function initInfiniteScroll() {
    const productsContainer = document.querySelector('.products-infinite-container');
    if (!productsContainer) return;
    
    let loading = false;
    let page = 1;
    let hasMore = true;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !loading && hasMore) {
                loadMoreProducts();
            }
        });
    }, {
        rootMargin: '200px'
    });
    
    const sentinel = document.createElement('div');
    sentinel.className = 'infinite-scroll-sentinel';
    productsContainer.appendChild(sentinel);
    observer.observe(sentinel);
    
    function loadMoreProducts() {
        loading = true;
        page++;
        
        const loader = document.createElement('div');
        loader.className = 'infinite-scroll-loader text-center py-8';
        loader.innerHTML = '<i class="fas fa-spinner fa-spin text-2xl text-blue-500"></i>';
        sentinel.parentNode.insertBefore(loader, sentinel);
        
        // Build URL with current filters
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        
        fetch(url)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newProducts = doc.querySelectorAll('.product-card');
                
                if (newProducts.length === 0) {
                    hasMore = false;
                    sentinel.style.display = 'none';
                } else {
                    newProducts.forEach(product => {
                        productsContainer.insertBefore(product, sentinel);
                    });
                }
                
                loader.remove();
                loading = false;
            })
            .catch(error => {
                console.error('Error loading more products:', error);
                loader.remove();
                loading = false;
            });
    }
}

// Quick view enhancements
function initQuickViewEnhancements() {
    // Add keyboard navigation for quick view
    document.addEventListener('keydown', function(e) {
        const quickViewModal = document.getElementById('quick-view-modal');
        if (quickViewModal && !quickViewModal.classList.contains('hidden')) {
            if (e.key === 'ArrowLeft') {
                // Previous product
            } else if (e.key === 'ArrowRight') {
                // Next product
            }
        }
    });
}

// Smooth animations
function initSmoothAnimations() {
    // Fade in products on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                entry.target.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);
                
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.product-card').forEach(card => {
        observer.observe(card);
    });
}

// Product hover effects
function initProductHoverEffects() {
    document.querySelectorAll('.product-card').forEach(card => {
        const img = card.querySelector('img');
        if (!img) return;
        
        card.addEventListener('mouseenter', function() {
            img.style.transform = 'scale(1.05)';
            img.style.transition = 'transform 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            img.style.transform = 'scale(1)';
        });
    });
}

// Smart toast notifications
function initSmartNotifications() {
    // Notification container
    if (!document.getElementById('notification-container')) {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
    }
}

function showNotification(message, type = 'success', duration = 3000) {
    const container = document.getElementById('notification-container') || document.body;
    
    const notification = document.createElement('div');
    notification.className = `notification bg-white rounded-lg shadow-xl p-4 flex items-center gap-3 min-w-[300px] transform transition-all duration-300 translate-x-full`;
    
    const icon = type === 'success' ? 'fa-check-circle text-green-500' : 
                 type === 'error' ? 'fa-exclamation-circle text-red-500' : 
                 'fa-info-circle text-blue-500';
    
    notification.innerHTML = `
        <i class="fas ${icon} text-xl"></i>
        <span class="flex-1">${message}</span>
        <button onclick="this.closest('.notification').remove()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 10);
    
    // Auto remove
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

// Auto-save cart to localStorage
function initAutoSaveCart() {
    // Save cart state
    window.addEventListener('beforeunload', function() {
        const cartData = {
            items: getCartItems(),
            timestamp: Date.now()
        };
        localStorage.setItem('cart_backup', JSON.stringify(cartData));
    });
    
    // Restore cart on load (if session cart is empty)
    if (document.getElementById('cart-count') && document.getElementById('cart-count').textContent === '0') {
        const saved = localStorage.getItem('cart_backup');
        if (saved) {
            const cartData = JSON.parse(saved);
            // Only restore if less than 24 hours old
            if (Date.now() - cartData.timestamp < 86400000) {
                // Could restore cart here if needed
            }
        }
    }
}

function getCartItems() {
    // Get current cart items
    return {};
}

// Quick filters
function initQuickFilters() {
    document.querySelectorAll('[data-quick-filter]').forEach(filter => {
        filter.addEventListener('click', function() {
            const filterType = this.dataset.quickFilter;
            const filterValue = this.dataset.filterValue;
            
            // Apply filter instantly
            applyQuickFilter(filterType, filterValue);
        });
    });
}

function applyQuickFilter(type, value) {
    // Update URL and reload
    const url = new URL(window.location.href);
    url.searchParams.set(type, value);
    window.location.href = url.toString();
}

// Back to top button
function initBackToTop() {
    // Create button
    const button = document.createElement('button');
    button.id = 'back-to-top';
    button.className = 'fixed bottom-8 right-8 bg-blue-600 text-white rounded-full w-12 h-12 shadow-lg hover:bg-blue-700 transition-all opacity-0 pointer-events-none z-40';
    button.innerHTML = '<i class="fas fa-arrow-up"></i>';
    button.onclick = () => window.scrollTo({ top: 0, behavior: 'smooth' });
    document.body.appendChild(button);
    
    // Show/hide on scroll
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            button.classList.remove('opacity-0', 'pointer-events-none');
        } else {
            button.classList.add('opacity-0', 'pointer-events-none');
        }
    });
}

// Loading states
function initLoadingStates() {
    // Add loading state to all buttons on click
    document.querySelectorAll('button[type="submit"], .btn-primary, .btn-secondary').forEach(button => {
        button.addEventListener('click', function() {
            if (!this.disabled && !this.dataset.loading) {
                this.dataset.loading = 'true';
                const originalHTML = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
                
                // Reset after 5 seconds if not reset manually
                setTimeout(() => {
                    if (this.dataset.loading === 'true') {
                        this.innerHTML = originalHTML;
                        this.disabled = false;
                        delete this.dataset.loading;
                    }
                }, 5000);
            }
        });
    });
}

// Update cart count
function updateCartCount() {
    const cartUrl = window.APP_CONFIG?.urls?.cart || 'api/cart.php';
    fetch(`${cartUrl}?action=count`, {
        credentials: 'include'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                if (data.count > 0) {
                    cartCount.textContent = data.count;
                    cartCount.classList.remove('hidden');
                } else {
                    cartCount.classList.add('hidden');
                }
            }
            
            // Update mobile cart count
            const cartCountMobile = document.getElementById('cart-count-mobile');
            if (cartCountMobile) {
                if (data.count > 0) {
                    cartCountMobile.textContent = data.count;
                    cartCountMobile.classList.remove('hidden');
                } else {
                    cartCountMobile.classList.add('hidden');
                }
            }
        })
        .catch(error => {
            console.error('Error updating cart count:', error);
        });
}

