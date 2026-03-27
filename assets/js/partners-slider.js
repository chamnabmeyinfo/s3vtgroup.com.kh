// Partners & Clients Logo Slider
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Partners Slider
    const partnersSlider = document.querySelector('.partners-slider-track');
    if (partnersSlider) {
        const partnersItems = partnersSlider.querySelectorAll('.partners-slider-item');
        if (partnersItems.length > 0) {
            // Duplicate items to create seamless loop
            partnersItems.forEach(item => {
                const clone = item.cloneNode(true);
                clone.setAttribute('aria-hidden', 'true');
                partnersSlider.appendChild(clone);
            });

            // Pause animation on hover
            const partnersWrapper = document.querySelector('.partners-slider-wrapper');
            if (partnersWrapper) {
                partnersWrapper.addEventListener('mouseenter', function() {
                    partnersSlider.style.animationPlayState = 'paused';
                });

                partnersWrapper.addEventListener('mouseleave', function() {
                    partnersSlider.style.animationPlayState = 'running';
                });
            }
        }
    }

    // Initialize Clients Slider
    const clientsSlider = document.querySelector('.clients-slider-track');
    if (clientsSlider) {
        const clientsItems = clientsSlider.querySelectorAll('.clients-slider-item');
        if (clientsItems.length > 0) {
            // Duplicate items to create seamless loop
            clientsItems.forEach(item => {
                const clone = item.cloneNode(true);
                clone.setAttribute('aria-hidden', 'true');
                clientsSlider.appendChild(clone);
            });

            // Pause animation on hover
            const clientsWrapper = document.querySelector('.clients-slider-wrapper');
            if (clientsWrapper) {
                clientsWrapper.addEventListener('mouseenter', function() {
                    clientsSlider.style.animationPlayState = 'paused';
                });

                clientsWrapper.addEventListener('mouseleave', function() {
                    clientsSlider.style.animationPlayState = 'running';
                });
            }
        }
    }
});
