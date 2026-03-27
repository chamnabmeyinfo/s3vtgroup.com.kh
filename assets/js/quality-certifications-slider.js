// Quality Certifications Logo Slider
(function() {
    const SLIDER_INIT_KEY = 'qualityCertificationsSliderInitialized';
    
    function initQualityCertificationsSlider() {
        const slider = document.querySelector('.quality-certifications-slider-track');
        if (!slider) return;

        // Check if already initialized using data attribute
        if (slider.dataset.initialized === 'true') {
            return;
        }

        // Remove any existing clones first (in case script runs multiple times)
        const existingClones = slider.querySelectorAll('.quality-certifications-slider-item[aria-hidden="true"], .quality-certifications-slider-item.slider-clone');
        existingClones.forEach(clone => clone.remove());

        // Get only original items (not clones) - items without aria-hidden="true" and without slider-clone class
        const originalItems = Array.from(slider.querySelectorAll('.quality-certifications-slider-item')).filter(item => {
            return !item.classList.contains('slider-clone') && 
                   (!item.hasAttribute('aria-hidden') || item.getAttribute('aria-hidden') !== 'true');
        });
        
        if (originalItems.length === 0) return;

        // Only clone items if there are 2 or more items (for seamless infinite scroll)
        // If there's only 1 item, don't clone it to avoid duplicates
        if (originalItems.length >= 2) {
            // Clone items for seamless infinite scroll
            originalItems.forEach(item => {
                const clone = item.cloneNode(true);
                clone.setAttribute('aria-hidden', 'true');
                clone.classList.add('slider-clone'); // Add class to identify clones
                slider.appendChild(clone);
            });
        } else {
            // For single item, disable animation to prevent duplicate appearance
            slider.style.animation = 'none';
            // Center the single item
            slider.style.justifyContent = 'center';
            // Ensure wrapper doesn't hide overflow for single item
            const wrapper = slider.parentElement;
            if (wrapper) {
                wrapper.style.overflow = 'visible';
            }
        }

        // Mark as initialized
        slider.dataset.initialized = 'true';

        // Pause animation on hover
        const wrapper = document.querySelector('.quality-certifications-slider-wrapper');
        if (wrapper && !wrapper.dataset.hoverInitialized) {
            wrapper.addEventListener('mouseenter', function() {
                slider.style.animationPlayState = 'paused';
            });

            wrapper.addEventListener('mouseleave', function() {
                slider.style.animationPlayState = 'running';
            });
            
            wrapper.dataset.hoverInitialized = 'true';
        }
    }
    
    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initQualityCertificationsSlider);
    } else {
        // Small delay to ensure DOM is fully ready
        setTimeout(initQualityCertificationsSlider, 100);
    }
})();
