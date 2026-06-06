/**
 * Enhanced Smooth Scrolling with Parallax Effects
 * Provides buttery smooth scrolling and parallax animations
 */

(function() {
    'use strict';

    let ticking = false;
    let lastScrollY = 0;

    // Enhanced smooth scrolling for anchor links
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scroll for all anchor links
        const anchorLinks = document.querySelectorAll('a[href^="#"]');
        
        anchorLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                // Skip if it's just "#"
                if (href === '#' || href === '') {
                    e.preventDefault();
                    return;
                }
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    
                    // Smooth scroll with offset for fixed headers
                    const offsetTop = target.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Create scroll-to-top button
        const scrollButton = document.createElement('button');
        scrollButton.className = 'scroll-to-top';
        scrollButton.innerHTML = `
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
            </svg>
        `;
        scrollButton.setAttribute('aria-label', 'Scroll to top');
        document.body.appendChild(scrollButton);

        // Show/hide scroll-to-top button with parallax
        window.addEventListener('scroll', function() {
            lastScrollY = window.pageYOffset;
            
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    updateScrollEffects();
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });

        // Scroll to top on button click
        scrollButton.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Initial setup
        updateScrollEffects();
    });

    // Update scroll effects (parallax + button visibility)
    function updateScrollEffects() {
        const scrollButton = document.querySelector('.scroll-to-top');
        
        // Show/hide scroll button
        if (scrollButton) {
            if (lastScrollY > 300) {
                scrollButton.classList.add('visible');
            } else {
                scrollButton.classList.remove('visible');
            }
        }
        
        // Apply parallax effect
        applyParallax();
    }

    // Parallax effect function
    function applyParallax() {
        const parallaxElements = document.querySelectorAll('.theme-card, .card-shadow');
        const viewportHeight = window.innerHeight;
        
        parallaxElements.forEach((element, index) => {
            const rect = element.getBoundingClientRect();
            
            // Only apply parallax if element is near or in viewport
            if (rect.top < viewportHeight + 200 && rect.bottom > -200) {
                // Calculate parallax offset (very subtle movement)
                const speed = 0.03 + (index % 3) * 0.01; // Stagger speeds for depth
                const centerY = rect.top + rect.height / 2;
                const distanceFromCenter = (viewportHeight / 2) - centerY;
                const yPos = distanceFromCenter * speed;
                
                // Apply transform with GPU acceleration
                element.style.transform = `translate3d(0, ${yPos}px, 0)`;
                element.style.transition = 'transform 0.1s ease-out';
                
                // Subtle scale effect for depth
                const scale = 1 + (Math.abs(distanceFromCenter) / viewportHeight) * 0.01;
                element.style.transform = `translate3d(0, ${yPos}px, 0) scale(${Math.min(scale, 1.02)})`;
                
                // Fade in effect as elements enter viewport
                if (rect.top < viewportHeight && rect.top > viewportHeight - 200) {
                    const opacity = 1 - ((viewportHeight - rect.top) / 200);
                    element.style.opacity = Math.max(0.3, Math.min(opacity, 1));
                } else if (rect.top <= viewportHeight - 200) {
                    element.style.opacity = 1;
                }
            }
        });
    }

    // Smooth scroll to element by ID (utility function)
    window.smoothScrollTo = function(elementId, offset = 80) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const elementPosition = element.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - offset;
        
        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    };

})();
