// Modern Hero Slider JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const slider = document.querySelector('.hero-slider');
    if (!slider) return;
    
    const container = slider.querySelector('.hero-slider-container');
    const slides = slider.querySelectorAll('.hero-slide');
    const prevBtn = slider.querySelector('.hero-slider-nav.prev');
    const nextBtn = slider.querySelector('.hero-slider-nav.next');
    const dots = slider.querySelectorAll('.hero-slider-dot');
    const progressBar = slider.querySelector('.hero-slider-progress-bar');
    
    if (slides.length === 0) return;
    
    let currentSlide = 0;
    let autoplayInterval = null;
    let progressInterval = null;
    // Get settings from window object or use defaults
    const settings = window.heroSliderSettings || {
        autoplayDelay: 5000,
        pauseOnHover: true,
        transitionSpeed: 800,
        enableKeyboard: true,
        enableTouch: true
    };
    const autoplayDelay = settings.autoplayDelay;
    let isPaused = false;
    let touchStartX = 0;
    let touchEndX = 0;
    
    // Initialize slider
    function initSlider() {
        showSlide(0);
        startAutoplay();
        if (settings.enableTouch) {
            setupTouchEvents();
        }
    }
    
    // Show specific slide
    function showSlide(index) {
        // Remove active class from all slides and dots
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        
        // Ensure index is within bounds
        if (index < 0) {
            currentSlide = slides.length - 1;
        } else if (index >= slides.length) {
            currentSlide = 0;
        } else {
            currentSlide = index;
        }
        
        // Add active class to current slide and dot
        slides[currentSlide].classList.add('active');
        if (dots[currentSlide]) {
            dots[currentSlide].classList.add('active');
        }
        
        // Reset progress bar
        resetProgress();
    }
    
    // Next slide
    function nextSlide() {
        showSlide(currentSlide + 1);
    }
    
    // Previous slide
    function prevSlide() {
        showSlide(currentSlide - 1);
    }
    
    // Start autoplay
    function startAutoplay() {
        stopAutoplay();
        if (isPaused) return;
        
        autoplayInterval = setInterval(() => {
            nextSlide();
        }, autoplayDelay);
        
        startProgress();
    }
    
    // Stop autoplay
    function stopAutoplay() {
        if (autoplayInterval) {
            clearInterval(autoplayInterval);
            autoplayInterval = null;
        }
        stopProgress();
    }
    
    // Start progress bar animation
    function startProgress() {
        stopProgress();
        if (isPaused) return;
        
        let progress = 0;
        const increment = 100 / (autoplayDelay / 100); // Update every 100ms
        
        progressInterval = setInterval(() => {
            progress += increment;
            if (progressBar) {
                progressBar.style.width = Math.min(progress, 100) + '%';
            }
            
            if (progress >= 100) {
                stopProgress();
            }
        }, 100);
    }
    
    // Stop progress bar
    function stopProgress() {
        if (progressInterval) {
            clearInterval(progressInterval);
            progressInterval = null;
        }
    }
    
    // Reset progress bar
    function resetProgress() {
        if (progressBar) {
            progressBar.style.width = '0%';
        }
        stopProgress();
        if (!isPaused) {
            startProgress();
        }
    }
    
    // Setup touch events for swipe
    function setupTouchEvents() {
        if (!settings.enableTouch) return;
        
        slider.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        slider.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });
    }
    
    // Handle swipe gesture
    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                // Swipe left - next slide
                nextSlide();
            } else {
                // Swipe right - previous slide
                prevSlide();
            }
        }
    }
    
    // Event listeners
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            nextSlide();
            stopAutoplay();
            setTimeout(() => startAutoplay(), 2000); // Resume after 2 seconds
        });
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            stopAutoplay();
            setTimeout(() => startAutoplay(), 2000); // Resume after 2 seconds
        });
    }
    
    // Dot navigation
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            showSlide(index);
            stopAutoplay();
            setTimeout(() => startAutoplay(), 2000); // Resume after 2 seconds
        });
    });
    
    // Pause on hover (if enabled)
    if (settings.pauseOnHover) {
        slider.addEventListener('mouseenter', () => {
            isPaused = true;
            stopAutoplay();
            stopProgress();
        });
        
        slider.addEventListener('mouseleave', () => {
            isPaused = false;
            startAutoplay();
        });
    }
    
    // Keyboard navigation (if enabled)
    if (settings.enableKeyboard) {
        document.addEventListener('keydown', (e) => {
            if (document.activeElement.tagName === 'INPUT' || 
                document.activeElement.tagName === 'TEXTAREA') {
                return; // Don't interfere with form inputs
            }
            
            if (e.key === 'ArrowLeft') {
                prevSlide();
                stopAutoplay();
                setTimeout(() => startAutoplay(), 2000);
            } else if (e.key === 'ArrowRight') {
                nextSlide();
                stopAutoplay();
                setTimeout(() => startAutoplay(), 2000);
            }
        });
    }
    
    // Pause when tab is not visible
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            isPaused = true;
            stopAutoplay();
            stopProgress();
        } else {
            isPaused = false;
            startAutoplay();
        }
    });
    
    // Initialize
    initSlider();
});

