<!-- Product Quick View Modal -->
<div id="quick-view-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="relative">
            <button onclick="closeQuickView()" class="absolute top-4 right-4 z-10 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center hover:bg-gray-100 transition-colors">
                <i class="fas fa-times text-gray-600"></i>
            </button>
            
            <div id="quick-view-content" class="p-6">
                <div class="text-center py-12">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-500"></i>
                    <p class="mt-4 text-gray-600">Loading product...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openQuickView(productId) {
    const modal = document.getElementById('quick-view-modal');
    const content = document.getElementById('quick-view-content');
    
    modal.classList.remove('hidden');
    content.innerHTML = '<div class="text-center py-12"><i class="fas fa-spinner fa-spin text-4xl text-blue-500"></i><p class="mt-4 text-gray-600">Loading product...</p></div>';
    
    fetch('<?= url('api/quick-view.php') ?>?product_id=' + productId, {
        credentials: 'include'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const product = data.product;
                content.innerHTML = `
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <img src="${product.gallery[0] ? '<?= asset('storage/uploads/') ?>' + product.gallery[0] : '<?= asset('storage/uploads/placeholder.jpg') ?>'}" 
                                 alt="${product.name}" 
                                 class="w-full h-96 object-cover rounded-lg">
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold mb-4">${product.name}</h2>
                            <div class="mb-4">
                                ${product.sale_price ? `
                                    <div class="flex items-center gap-4">
                                        <span class="text-3xl font-bold text-blue-600">$${parseFloat(product.sale_price).toFixed(2)}</span>
                                        <span class="text-xl text-gray-400 line-through">$${parseFloat(product.price).toFixed(2)}</span>
                                    </div>
                                ` : `
                                    <span class="text-3xl font-bold text-blue-600">$${parseFloat(product.price).toFixed(2)}</span>
                                `}
                            </div>
                            <p class="text-gray-600 mb-4">${product.short_description || ''}</p>
                            <div class="flex gap-4 mb-4">
                                <button onclick="addToCart(${product.id}); closeQuickView();" class="btn-primary flex-1">
                                    <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                                </button>
                                <a href="${product.url}" class="btn-secondary flex-1 text-center">
                                    View Full Details
                                </a>
                            </div>
                            <div class="border-t pt-4">
                                <p class="text-sm"><strong>SKU:</strong> ${product.sku || 'N/A'}</p>
                                <p class="text-sm"><strong>Stock:</strong> <span class="text-green-600">${product.stock_status.replace('_', ' ')}</span></p>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = '<div class="text-center py-12"><p class="text-red-600">Error loading product</p></div>';
            }
        })
        .catch(error => {
            content.innerHTML = '<div class="text-center py-12"><p class="text-red-600">Error loading product</p></div>';
        });
}

function closeQuickView() {
    document.getElementById('quick-view-modal').classList.add('hidden');
}

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeQuickView();
    }
});

// Close on background click
document.getElementById('quick-view-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeQuickView();
    }
});
</script>

