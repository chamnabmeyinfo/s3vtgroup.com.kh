// Parallax Section JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const parallaxSection = document.getElementById('parallaxSection');
    if (!parallaxSection) return;
    
    const parallaxBg = parallaxSection.querySelector('.parallax-bg');
    const parallaxContent = parallaxSection.querySelector('.parallax-content');
    const particlesCanvas = document.getElementById('parallaxParticles');
    
    // Get parallax speed from data attribute or default
    const speed = parseFloat(parallaxSection.dataset.speed || 0.5);
    
    // Parallax scroll effect
    function handleParallax() {
        if (!parallaxBg) return;
        
        const rect = parallaxSection.getBoundingClientRect();
        const scrolled = window.pageYOffset;
        const rate = scrolled * speed;
        
        // Only apply parallax when section is in view
        if (rect.bottom >= 0 && rect.top <= window.innerHeight) {
            parallaxBg.style.transform = `translateY(${rate}px)`;
        }
    }
    
    // Throttle scroll events
    let ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                handleParallax();
                ticking = false;
            });
            ticking = true;
        }
    });
    
    // Content animation on scroll
    function animateContent() {
        if (!parallaxContent) return;
        
        const rect = parallaxSection.getBoundingClientRect();
        const windowHeight = window.innerHeight;
        const elementTop = rect.top;
        const elementVisible = 150;
        
        if (elementTop < windowHeight - elementVisible) {
            const animationType = parallaxContent.dataset.animation || 'fade-in';
            
            parallaxContent.style.opacity = '1';
            parallaxContent.style.transform = 'translateY(0)';
            
            if (animationType === 'slide-up') {
                parallaxContent.style.transform = 'translateY(0)';
            } else if (animationType === 'zoom-in') {
                parallaxContent.style.transform = 'scale(1)';
            }
        }
    }
    
    // Initialize content animation
    const animationType = parallaxContent?.dataset.animation || 'fade-in';
    if (parallaxContent) {
        parallaxContent.style.transition = 'opacity 1s ease-out, transform 1s ease-out';
        
        if (animationType === 'fade-in') {
            parallaxContent.style.opacity = '0';
        } else if (animationType === 'slide-up') {
            parallaxContent.style.opacity = '0';
            parallaxContent.style.transform = 'translateY(50px)';
        } else if (animationType === 'zoom-in') {
            parallaxContent.style.opacity = '0';
            parallaxContent.style.transform = 'scale(0.8)';
        }
    }
    
    window.addEventListener('scroll', animateContent);
    animateContent(); // Check on load
    
    // Particles effect - handle all canvas elements
    const particlesCanvases = parallaxSection.querySelectorAll('.parallax-particles');
    particlesCanvases.forEach(function(particlesCanvas) {
        const ctx = particlesCanvas.getContext('2d');
        const particles = [];
        const particleCount = 50;
        
        // Set canvas size
        function resizeCanvas() {
            particlesCanvas.width = parallaxSection.offsetWidth;
            particlesCanvas.height = parallaxSection.offsetHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        
        // Get particle color from CSS or default
        const particleColor = getComputedStyle(particlesCanvas).color || '#ffffff';
        
        // Create particles
        for (let i = 0; i < particleCount; i++) {
            particles.push({
                x: Math.random() * particlesCanvas.width,
                y: Math.random() * particlesCanvas.height,
                radius: Math.random() * 2 + 1,
                speedX: (Math.random() - 0.5) * 0.5,
                speedY: (Math.random() - 0.5) * 0.5,
                opacity: Math.random() * 0.5 + 0.2
            });
        }
        
        // Animate particles
        function animateParticles() {
            ctx.clearRect(0, 0, particlesCanvas.width, particlesCanvas.height);
            
            particles.forEach(particle => {
                particle.x += particle.speedX;
                particle.y += particle.speedY;
                
                // Wrap around edges
                if (particle.x < 0) particle.x = particlesCanvas.width;
                if (particle.x > particlesCanvas.width) particle.x = 0;
                if (particle.y < 0) particle.y = particlesCanvas.height;
                if (particle.y > particlesCanvas.height) particle.y = 0;
                
                // Draw particle
                ctx.beginPath();
                ctx.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
                ctx.fillStyle = particleColor;
                ctx.globalAlpha = particle.opacity;
                ctx.fill();
            });
            
            requestAnimationFrame(animateParticles);
        }
        
        animateParticles();
    });
    
    // Mobile: Disable fixed background attachment for better performance
    if (window.innerWidth < 768) {
        if (parallaxBg) {
            parallaxBg.style.backgroundAttachment = 'scroll';
        }
    }
});
