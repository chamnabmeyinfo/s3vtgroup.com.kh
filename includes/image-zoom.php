<!-- Image Zoom Functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add zoom functionality to product images
    const productImages = document.querySelectorAll('.product-zoom-image');
    
    productImages.forEach(img => {
        img.addEventListener('mouseenter', function() {
            if (window.innerWidth > 768) { // Only on desktop
                this.style.cursor = 'zoom-in';
            }
        });
        
        img.addEventListener('mousemove', function(e) {
            if (window.innerWidth > 768) {
                const rect = this.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width) * 100;
                const y = ((e.clientY - rect.top) / rect.height) * 100;
                
                this.style.transformOrigin = `${x}% ${y}%`;
            }
        });
        
        img.addEventListener('click', function() {
            openImageLightbox(this.src, this.alt);
        });
    });
});

function openImageLightbox(src, alt) {
    const lightbox = document.createElement('div');
    lightbox.className = 'fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4';
    lightbox.innerHTML = `
        <div class="relative max-w-6xl max-h-full">
            <button onclick="this.closest('.fixed').remove()" 
                    class="absolute -top-12 right-0 text-white text-2xl hover:text-gray-300">
                <i class="fas fa-times"></i>
            </button>
            <img src="${src}" alt="${alt}" class="max-w-full max-h-[90vh] object-contain">
        </div>
    `;
    
    lightbox.addEventListener('click', function(e) {
        if (e.target === this) {
            this.remove();
        }
    });
    
    document.body.appendChild(lightbox);
    
    // Close on escape
    const escapeHandler = function(e) {
        if (e.key === 'Escape') {
            lightbox.remove();
            document.removeEventListener('keydown', escapeHandler);
        }
    };
    document.addEventListener('keydown', escapeHandler);
}
</script>

<style>
.product-zoom-image {
    transition: transform 0.3s ease;
}

.product-zoom-image:hover {
    transform: scale(1.5);
}

@media (max-width: 768px) {
    .product-zoom-image:hover {
        transform: none;
    }
}
</style>

