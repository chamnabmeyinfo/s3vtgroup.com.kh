// Main JavaScript for Forklift & Equipment Website

document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('show');
            mobileMenu.classList.toggle('hidden');
            
            const icon = mobileMenuBtn.querySelector('i');
            if (icon) {
                if (mobileMenu.classList.contains('show')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (mobileMenu && mobileMenuBtn && 
            !mobileMenu.contains(event.target) && 
            !mobileMenuBtn.contains(event.target)) {
            mobileMenu.classList.remove('show');
            mobileMenu.classList.add('hidden');
            const icon = mobileMenuBtn.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.length > 1) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // Form Validation Enhancement
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
    
    // Lazy Loading Images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // Search Form Enhancement
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            // Could add live search functionality here
        });
    }
    
    // Add loading state to buttons on form submit
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            }
        });
    });
    
    // Load cart count
    updateCartCount();
});

// Mobile Search Toggle Function
function toggleMobileSearch() {
    const overlay = document.getElementById('mobile-search-overlay');
    const searchInput = document.getElementById('mobile-advanced-search');
    
    if (overlay) {
        overlay.classList.toggle('hidden');
        
        // Focus on input when overlay opens
        if (!overlay.classList.contains('hidden') && searchInput) {
            setTimeout(() => {
                searchInput.focus();
            }, 100);
        }
    }
}

// Close mobile search when clicking outside
document.addEventListener('click', function(event) {
    const overlay = document.getElementById('mobile-search-overlay');
    const toggleBtn = document.getElementById('mobile-search-toggle');
    
    if (overlay && !overlay.classList.contains('hidden')) {
        if (!overlay.contains(event.target) && !toggleBtn?.contains(event.target)) {
            overlay.classList.add('hidden');
        }
    }
});

// Add to cart function
function addToCart(productId, quantity = 1) {
    if (!productId) {
        console.error('Product ID is required');
        if (typeof showNotification === 'function') {
            showNotification('Error: Product ID is required', 'error');
        }
        return;
    }
    
    const cartUrl = window.APP_CONFIG?.urls?.cart || 'api/cart.php';
    const button = document.getElementById('add-to-cart-btn');
    const originalHTML = button ? button.innerHTML : '';
    
    // Show loading state
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';
    }
    
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
                // Success state
                if (button) {
                    button.innerHTML = '<i class="fas fa-check mr-2"></i> Added!';
                    button.classList.add('bg-green-500');
                    button.classList.remove('bg-blue-600');
                }
                
                // Show notification
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Product added to cart!', 'success');
                }
                
                // Update cart count
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }
                
                // Reset button after 2 seconds
                if (button) {
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.classList.remove('bg-green-500');
                        button.classList.add('bg-blue-600');
                        button.disabled = false;
                    }, 2000);
                }
            } else {
                // Error state
                if (button) {
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                }
                
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Error adding to cart', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error adding to cart:', error);
            if (button) {
                button.innerHTML = originalHTML;
                button.disabled = false;
            }
            if (typeof showNotification === 'function') {
                showNotification('Error adding to cart. Please try again.', 'error');
            }
        });
}

// Update cart count function (if not already defined)
if (typeof updateCartCount === 'undefined') {
    function updateCartCount() {
        const cartUrl = window.APP_CONFIG?.urls?.cart || 'api/cart.php';
        fetch(cartUrl + '?action=count', {
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
}

// Utility Functions
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

