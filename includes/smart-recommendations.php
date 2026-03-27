<!-- Smart Recommendations Widget -->
<div id="smart-recommendations" class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold mb-6">
            <i class="fas fa-sparkles text-yellow-500 mr-2"></i>
            Recommended For You
        </h2>
        <div id="recommendations-loading" class="text-center py-12">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-500"></i>
            <p class="mt-4 text-gray-600">Finding perfect products for you...</p>
        </div>
        <div id="recommendations-grid" class="hidden grid sm:grid-cols-2 lg:grid-cols-4 gap-6"></div>
    </div>
</div>

<script>
function loadSmartRecommendations() {
    const container = document.getElementById('smart-recommendations');
    if (!container) return;
    
    fetch('<?= url('api/smart-recommendations.php') ?>')
        .then(response => response.json())
        .then(data => {
            const loading = document.getElementById('recommendations-loading');
            const grid = document.getElementById('recommendations-grid');
            
            if (data.success && data.products.length > 0) {
                loading.classList.add('hidden');
                grid.classList.remove('hidden');
                
                grid.innerHTML = data.products.map(product => `
                    <div class="product-card bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                        <a href="${product.url}">
                            <div class="w-full aspect-[10/7] bg-gray-200 flex items-center justify-center overflow-hidden relative">
                                ${product.image ? 
                                    `<img src="<?= asset('storage/uploads/') ?>${product.image}" alt="${product.name}" class="w-full h-full object-cover">` :
                                    `<span class="text-gray-400">No Image</span>`
                                }
                                <span class="absolute top-2 right-2 bg-yellow-400 text-yellow-900 px-2 py-1 rounded text-xs font-bold">
                                    <i class="fas fa-star mr-1"></i>Recommended
                                </span>
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-lg mb-2 line-clamp-2">${product.name}</h3>
                                <p class="text-sm text-gray-600 mb-2 line-clamp-2">${product.short_description || ''}</p>
                                ${product.sale_price ? 
                                    `<div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-blue-600">$${parseFloat(product.sale_price).toFixed(2)}</span>
                                        <span class="text-sm text-gray-400 line-through">$${parseFloat(product.price).toFixed(2)}</span>
                                    </div>` :
                                    `<p class="text-lg font-bold text-blue-600">$${parseFloat(product.price).toFixed(2)}</p>`
                                }
                            </div>
                        </a>
                    </div>
                `).join('');
            } else {
                container.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading recommendations:', error);
            document.getElementById('recommendations-loading').innerHTML = '<p class="text-gray-600">Recommendations will appear soon...</p>';
        });
}

// Load recommendations when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadSmartRecommendations();
});
</script>

