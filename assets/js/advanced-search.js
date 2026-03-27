// Advanced Search with Autocomplete

document.addEventListener('DOMContentLoaded', function() {
    // Initialize search for both desktop and mobile inputs
    const desktopInput = document.getElementById('advanced-search');
    const desktopResults = document.getElementById('search-results');
    const mobileInput = document.getElementById('mobile-advanced-search');
    const mobileResults = document.getElementById('mobile-search-results');
    
    if (desktopInput && desktopResults) {
        initSearch(desktopInput, desktopResults);
    }
    
    if (mobileInput && mobileResults) {
        initSearch(mobileInput, mobileResults);
    }
    
    function initSearch(searchInput, searchResults) {
    
    let searchTimeout;
    let currentQuery = '';
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        currentQuery = query;
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            performSearch(this.value.trim());
        }
    });
    
    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('hidden');
        }
    });
    
    function performSearch(query) {
        if (query !== currentQuery) return;
        
        const searchUrl = window.APP_CONFIG?.urls?.search || 'api/search.php';
        fetch(`${searchUrl}?q=${encodeURIComponent(query)}&type=all`)
            .then(response => response.json())
            .then(data => {
                if (query !== currentQuery) return;
                
                if (data.results && data.results.length > 0) {
                    displayResults(data.results);
                } else {
                    searchResults.innerHTML = '<div class="p-4 text-gray-500 text-center">No results found</div>';
                    searchResults.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Search error:', error);
            });
    }
    
    function displayResults(results) {
        let html = '<div class="p-2">';
        
        results.forEach(result => {
            const icon = result.type === 'product' ? 'fa-box' : 'fa-tags';
            const badge = result.type === 'product' 
                ? `<span class="text-blue-600 font-semibold">${result.price || ''}</span>` 
                : '';
            
            html += `
                <a href="${result.url}" class="flex items-center gap-3 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                    <i class="fas ${icon} text-gray-400"></i>
                    <div class="flex-1">
                        <div class="font-semibold">${result.title}</div>
                        ${result.description ? `<div class="text-sm text-gray-600">${result.description}</div>` : ''}
                    </div>
                    ${badge}
                </a>
            `;
        });
        
        html += '</div>';
        searchResults.innerHTML = html;
        searchResults.classList.remove('hidden');
    }
    
    // Allow Enter key to go to first result or search page
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const firstResult = searchResults.querySelector('a');
            if (firstResult) {
                window.location.href = firstResult.href;
            } else if (this.value.trim()) {
                const productsUrl = window.APP_CONFIG?.urls?.products || 'products.php';
                window.location.href = `${productsUrl}?search=${encodeURIComponent(this.value.trim())}`;
            }
            // Close mobile overlay if on mobile
            if (searchInput.id === 'mobile-advanced-search') {
                const overlay = document.getElementById('mobile-search-overlay');
                if (overlay) {
                    overlay.classList.add('hidden');
                }
            }
        }
    });
    }
});

// Wishlist Functions
function addToWishlist(productId) {
    const wishlistUrl = window.APP_CONFIG?.urls?.wishlist || 'api/wishlist.php';
    fetch(`${wishlistUrl}?action=add&id=${productId}`, {
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
            const btn = document.getElementById(`wishlist-btn-${productId}`);
            if (btn) {
                btn.classList.add('bg-red-500', 'text-white');
                btn.classList.remove('border-red-300', 'text-red-600');
            }
            if (typeof updateWishlistCount === 'function') {
                updateWishlistCount();
            }
            // Dispatch event for other components
            document.dispatchEvent(new CustomEvent('wishlistUpdated', { detail: { count: data.count || 0 } }));
            if (typeof showNotification === 'function') {
                showNotification('Added to wishlist!', 'success');
            }
        } else {
            if (typeof showNotification === 'function') {
                showNotification(data.message || 'Error adding to wishlist', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error adding to wishlist:', error);
        if (typeof showNotification === 'function') {
            showNotification('Error adding to wishlist', 'error');
        }
    });
}

function addToCompare(productId) {
    const compareUrl = window.APP_CONFIG?.urls?.compare || 'api/compare.php';
    fetch(`${compareUrl}?action=add&id=${productId}`, {
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
            if (typeof showNotification === 'function') {
                showNotification(`Added to comparison (${data.count || 0}/4)`, 'success');
            }
            const btn = document.getElementById(`compare-btn-${productId}`);
            if (btn) {
                btn.classList.add('bg-blue-500', 'text-white');
                btn.classList.remove('border-blue-300', 'text-blue-600');
            }
            // Update compare count in header
            if (typeof updateCompareCount === 'function') {
                updateCompareCount();
            }
            // Dispatch event for other components
            document.dispatchEvent(new CustomEvent('compareUpdated', { detail: { count: data.count || 0 } }));
        } else {
            if (typeof showNotification === 'function') {
                showNotification(data.message || 'Error adding to comparison', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error adding to compare:', error);
        if (typeof showNotification === 'function') {
            showNotification('Error adding to comparison', 'error');
        }
    });
}

function updateCompareCount() {
    const compareUrl = window.APP_CONFIG?.urls?.compare || 'api/compare.php';
    fetch(`${compareUrl}?action=get`, {
        credentials: 'include'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const count = Array.isArray(data.compare) ? data.compare.length : 0;
            
            // Update desktop compare count
            const countEl = document.getElementById('compare-count');
            if (countEl) {
                if (count > 0) {
                    countEl.textContent = count;
                    countEl.classList.remove('hidden');
                } else {
                    countEl.classList.add('hidden');
                }
            }
            
            // Update mobile compare count
            const countElMobile = document.getElementById('compare-count-mobile');
            if (countElMobile) {
                if (count > 0) {
                    countElMobile.textContent = count;
                    countElMobile.classList.remove('hidden');
                } else {
                    countElMobile.classList.add('hidden');
                }
            }
        })
        .catch(error => {
            console.error('Error updating compare count:', error);
        });
}

function updateWishlistCount() {
    const wishlistUrl = window.APP_CONFIG?.urls?.wishlist || 'api/wishlist.php';
    fetch(`${wishlistUrl}?action=count`, {
        credentials: 'include'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const countEl = document.getElementById('wishlist-count');
            if (countEl) {
                if (data.count > 0) {
                    countEl.textContent = data.count;
                    countEl.classList.remove('hidden');
                } else {
                    countEl.classList.add('hidden');
                }
            }
            
            // Update mobile wishlist count
            const countElMobile = document.getElementById('wishlist-count-mobile');
            if (countElMobile) {
                if (data.count > 0) {
                    countElMobile.textContent = data.count;
                    countElMobile.classList.remove('hidden');
                } else {
                    countElMobile.classList.add('hidden');
                }
            }
        })
        .catch(error => {
            console.error('Error updating wishlist count:', error);
        });
}

// Initialize wishlist count on page load
if (typeof updateWishlistCount === 'function') {
    document.addEventListener('DOMContentLoaded', updateWishlistCount);
}

// Initialize compare count on page load
if (typeof updateCompareCount === 'function') {
    document.addEventListener('DOMContentLoaded', updateCompareCount);
}

