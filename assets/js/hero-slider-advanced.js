// Advanced Hero Slider JavaScript - Extends Basic Functionality
// This adds advanced features on top of the basic hero-slider.js

(function() {
    'use strict';
    
    // Wait for DOM and basic slider to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdvancedFeatures);
    } else {
        initAdvancedFeatures();
    }
    
    function initAdvancedFeatures() {
        const slider = document.querySelector('.hero-slider');
        if (!slider) return;
        
        const slides = slider.querySelectorAll('.hero-slide');
        if (slides.length === 0) return;
        
        let parallaxScrollHandler = null;
        
        // Initialize video backgrounds
        function initVideoBackgrounds() {
            slides.forEach(slide => {
                const video = slide.querySelector('video.hero-video-background');
                if (video) {
                    video.muted = true;
                    video.loop = true;
                    video.play().catch(e => {
                        // Video autoplay prevented - browser policy
                        console.log('Video autoplay prevented');
                    });
                }
            });
        }
        
        // Initialize parallax
        function initParallax() {
            if (parallaxScrollHandler) return;
            
            parallaxScrollHandler = function() {
                slides.forEach(slide => {
                    if (slide.classList.contains('active') && slide.dataset.parallax === 'true') {
                        const scrolled = window.pageYOffset;
                        const rate = scrolled * 0.5;
                        const bg = slide.querySelector('.hero-slide-bg');
                        if (bg) {
                            bg.style.transform = `translateY(${rate}px)`;
                        }
                    }
                });
            };
            
            window.addEventListener('scroll', parallaxScrollHandler, { passive: true });
        }
        
        // Apply text animations when slide becomes active
        function applyTextAnimation(slide) {
            const content = slide.querySelector('.hero-slide-content');
            if (!content) return;
            
            const animation = slide.dataset.textAnimation || 'fadeInUp';
            if (animation === 'none') return;
            
            // Remove previous animation classes
            content.classList.remove('animate-fadeInUp', 'animate-fadeInDown', 'animate-fadeInLeft', 
                                     'animate-fadeInRight', 'animate-slideInUp', 'animate-slideInDown',
                                     'animate-zoomIn', 'animate-bounceIn', 'animate-typewriter');
            
            // Add new animation
            if (animation === 'typewriter') {
                typewriterEffect(content);
            } else {
                content.classList.add(`animate-${animation}`);
            }
        }
        
        // Typewriter effect
        function typewriterEffect(element) {
            const h1 = element.querySelector('h1');
            if (!h1) return;
            
            const text = h1.textContent;
            h1.textContent = '';
            h1.style.borderRight = '2px solid white';
            
            let i = 0;
            const typeInterval = setInterval(() => {
                if (i < text.length) {
                    h1.textContent += text.charAt(i);
                    i++;
                } else {
                    clearInterval(typeInterval);
                    h1.style.borderRight = 'none';
                }
            }, 50);
        }
        
        // Apply transition effects
        function applyTransition(slide, effect) {
            if (!effect || effect === 'fade') return; // Fade is default
            
            // Remove all transition classes
            slide.classList.remove('transition-slideLeft', 'transition-slideRight',
                                  'transition-slideUp', 'transition-slideDown', 'transition-zoom',
                                  'transition-cube', 'transition-flip', 'transition-coverflow');
            
            // Add specific transition class
            if (effect !== 'fade') {
                slide.classList.add(`transition-${effect}`);
            }
        }
        
        // Track slide view
        function trackSlideView(slideId) {
            if (!slideId) return;
            fetch(`api/hero-slider-track.php?slide_id=${slideId}`, {
                method: 'POST',
                credentials: 'include'
            }).catch(e => {
                // Silently fail if tracking is not available
            });
        }
        
        // Observe slide changes to apply animations
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const slide = mutation.target;
                    if (slide.classList.contains('active')) {
                        const transition = slide.dataset.transition || 'fade';
                        applyTransition(slide, transition);
                        applyTextAnimation(slide);
                        
                        // Track view
                        if (slide.dataset.slideId) {
                            trackSlideView(slide.dataset.slideId);
                        }
                    }
                }
            });
        });
        
        // Observe all slides
        slides.forEach(slide => {
            observer.observe(slide, { attributes: true, attributeFilter: ['class'] });
        });
        
        // Initialize countdown timers
        function initCountdowns() {
            slides.forEach(slide => {
                const countdownEl = slide.querySelector('.hero-countdown');
                if (countdownEl && countdownEl.dataset.countdownTo) {
                    const targetDate = new Date(countdownEl.dataset.countdownTo).getTime();
                    
                    function updateCountdown() {
                        const now = new Date().getTime();
                        const distance = targetDate - now;
                        
                        if (distance < 0) {
                            countdownEl.innerHTML = '<div class="text-2xl font-bold text-white">Expired</div>';
                            return;
                        }
                        
                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        
                        countdownEl.innerHTML = `
                            <div class="flex gap-4 justify-center flex-wrap">
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-white">${days}</div>
                                    <div class="text-sm text-white/80">Days</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-white">${hours}</div>
                                    <div class="text-sm text-white/80">Hours</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-white">${minutes}</div>
                                    <div class="text-sm text-white/80">Minutes</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-white">${seconds}</div>
                                    <div class="text-sm text-white/80">Seconds</div>
                                </div>
                            </div>
                        `;
                    }
                    
                    updateCountdown();
                    setInterval(updateCountdown, 1000);
                }
            });
        }
        
        // Initialize all advanced features
        initVideoBackgrounds();
        initCountdowns();
        
        // Initialize parallax for slides that have it enabled
        const hasParallax = Array.from(slides).some(s => s.dataset.parallax === 'true');
        if (hasParallax) {
            initParallax();
        }
        
        // Apply initial animations to active slide
        const activeSlide = slider.querySelector('.hero-slide.active');
        if (activeSlide) {
            applyTextAnimation(activeSlide);
        }
    }
})();

