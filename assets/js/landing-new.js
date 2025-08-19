// ===== DOM CONTENT LOADED =====
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initNavbar();
    initHeroSlider();
    initMobileMenu();
    initSmoothScroll();
    initContactForm();
    initLiveSupport();
    initScrollAnimations();
    initMarketIndicators();
    initCardAnimations();
    
    // Initialize enhanced classes
    new EnhancedHeroSlider();
    new EnhancedScrollAnimations();
});

// ===== NAVBAR FUNCTIONALITY =====
function initNavbar() {
    const navbar = document.getElementById('navbar');
    
    // Add scroll effect to navbar
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar?.classList.add('scrolled');
        } else {
            navbar?.classList.remove('scrolled');
        }
    });
}

// ===== HERO SLIDER =====
function initHeroSlider() {
    const slides = document.querySelectorAll('.slide');
    let currentSlide = 0;
    let slideInterval;
    let touchStartX = 0;
    let touchEndX = 0;

    if (slides.length === 0) return;

    // Function to show specific slide
    function showSlide(index) {
        // Remove active class from all slides
        slides.forEach(slide => slide.classList.remove('active'));
        
        // Add active class to current slide
        slides[index].classList.add('active');
        
        currentSlide = index;
    }

    // Auto-slide functionality
    function startAutoSlide() {
        slideInterval = setInterval(() => {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }, 5000); // Change slide every 5 seconds
    }

    // Stop auto-slide
    function stopAutoSlide() {
        clearInterval(slideInterval);
    }

    // Navigation functions
    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
        stopAutoSlide();
        setTimeout(startAutoSlide, 10000);
    }

    function prevSlide() {
        currentSlide = currentSlide === 0 ? slides.length - 1 : currentSlide - 1;
        showSlide(currentSlide);
        stopAutoSlide();
        setTimeout(startAutoSlide, 10000);
    }

    // Handle touch gestures
    function handleTouchStart(e) {
        touchStartX = e.touches[0].clientX;
    }

    function handleTouchMove(e) {
        touchEndX = e.touches[0].clientX;
    }

    function handleTouchEnd(e) {
        if (!touchStartX || !touchEndX) return;
        
        const touchDiff = touchStartX - touchEndX;
        const minSwipeDistance = 50;
        
        if (Math.abs(touchDiff) > minSwipeDistance) {
            if (touchDiff > 0) {
                // Swipe left - next slide
                nextSlide();
            } else {
                // Swipe right - previous slide
                prevSlide();
            }
        }
        
        // Reset touch values
        touchStartX = 0;
        touchEndX = 0;
    }

    // Pause on hover
    const sliderContainer = document.querySelector('.slider-container');
    if (sliderContainer) {
        sliderContainer.addEventListener('mouseenter', stopAutoSlide);
        sliderContainer.addEventListener('mouseleave', startAutoSlide);

        // Touch events for mobile swiping
        sliderContainer.addEventListener('touchstart', handleTouchStart, { passive: true });
        sliderContainer.addEventListener('touchmove', handleTouchMove, { passive: true });
        sliderContainer.addEventListener('touchend', handleTouchEnd, { passive: true });
    }

    // Initialize auto-slide
    startAutoSlide();

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            prevSlide();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
        }
    });
}

// ===== MOBILE MENU =====
function initMobileMenu() {
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');

    if (!hamburger || !navMenu) return;

    // Toggle mobile menu
    hamburger.addEventListener('click', () => {
        navMenu.classList.toggle('active');
        hamburger.classList.toggle('active');
        
        // Animate hamburger
        const spans = hamburger.querySelectorAll('span');
        spans.forEach((span, index) => {
            if (hamburger.classList.contains('active')) {
                if (index === 0) span.style.transform = 'rotate(45deg) translate(5px, 5px)';
                if (index === 1) span.style.opacity = '0';
                if (index === 2) span.style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                span.style.transform = 'none';
                span.style.opacity = '1';
            }
        });
    });

    // Close menu on link click
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
            hamburger.classList.remove('active');
            
            const spans = hamburger.querySelectorAll('span');
            spans.forEach(span => {
                span.style.transform = 'none';
                span.style.opacity = '1';
            });
        });
    });

    // Close menu on outside click
    document.addEventListener('click', (e) => {
        if (!hamburger.contains(e.target) && !navMenu.contains(e.target)) {
            navMenu.classList.remove('active');
            hamburger.classList.remove('active');
            
            const spans = hamburger.querySelectorAll('span');
            spans.forEach(span => {
                span.style.transform = 'none';
                span.style.opacity = '1';
            });
        }
    });
}

// ===== SMOOTH SCROLL =====
function initSmoothScroll() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = link.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                const offsetTop = targetSection.offsetTop - 80; // Account for navbar height
                
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Scroll-triggered animations
function initScrollAnimations() {
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    animatedElements.forEach(element => {
        observer.observe(element);
    });
    
    // Parallax effect for hero section
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const heroSlider = document.querySelector('.hero-slider');
        
        if (heroSlider) {
            const parallax = scrolled * 0.5;
            heroSlider.style.transform = `translateY(${parallax}px)`;
        }
    });
}

// Contact form handling
function initContactForm() {
    const form = document.getElementById('callbackForm');
    
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        // Validate form
        if (!validateForm(data)) {
            return;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('.submit-btn');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = getCurrentLang() === 'tr' ? 'Gönderiliyor...' : 'Sending...';
        submitBtn.disabled = true;
        
        // Simulate form submission (replace with actual API call)
        setTimeout(() => {
            showNotification(
                getCurrentLang() === 'tr' ? 'Başarılı!' : 'Success!',
                getCurrentLang() === 'tr' ? 
                    'Talebiniz alındı. En kısa sürede size dönüş yapacağız.' : 
                    'Your request has been received. We will get back to you soon.',
                'success'
            );
            
            form.reset();
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }, 2000);
    });
}

// Form validation
function validateForm(data) {
    const errors = [];
    
    if (!data.name || data.name.trim().length < 2) {
        errors.push(getCurrentLang() === 'tr' ? 'Geçerli bir isim giriniz' : 'Please enter a valid name');
    }
    
    if (!data.phone || data.phone.trim().length < 10) {
        errors.push(getCurrentLang() === 'tr' ? 'Geçerli bir telefon numarası giriniz' : 'Please enter a valid phone number');
    }
    
    if (!data.email || !isValidEmail(data.email)) {
        errors.push(getCurrentLang() === 'tr' ? 'Geçerli bir e-posta adresi giriniz' : 'Please enter a valid email address');
    }
    
    if (!data.experience) {
        errors.push(getCurrentLang() === 'tr' ? 'Yatırım deneyiminizi seçiniz' : 'Please select your investment experience');
    }
    
    if (errors.length > 0) {
        showNotification(
            getCurrentLang() === 'tr' ? 'Hata!' : 'Error!',
            errors.join('\n'),
            'error'
        );
        return false;
    }
    
    return true;
}

// Email validation
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Live support functionality
function initLiveSupport() {
    const supportBtn = document.querySelector('.support-btn');
    
    if (!supportBtn) return;
    
    supportBtn.addEventListener('click', function() {
        // Show support options or open chat
        showNotification(
            getCurrentLang() === 'tr' ? 'Canlı Destek' : 'Live Support',
            getCurrentLang() === 'tr' ? 
                'Destek ekibimiz size yardımcı olmak için hazır!' : 
                'Our support team is ready to help you!',
            'info'
        );
        
        // In a real implementation, this would open a chat widget
        // or redirect to a support page
    });
}

// Smooth scrolling for navigation links
function initSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const headerHeight = document.querySelector('.navbar')?.offsetHeight || 80;
                const targetPosition = target.offsetTop - headerHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Responsive features
function initResponsiveFeatures() {
    // Mobile menu toggle (if needed)
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
    }
    
    // Close mobile menu when clicking on a link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function() {
            if (hamburger && navMenu) {
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
            }
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        // Reset mobile menu on desktop
        if (window.innerWidth > 768) {
            if (hamburger && navMenu) {
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
            }
        }
    });
}

// Notification system
function showNotification(title, message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-title">${title}</div>
            <div class="notification-message">${message}</div>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    // Add notification styles
    const notificationStyles = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            z-index: 10000;
            min-width: 300px;
            max-width: 400px;
            animation: slideInRight 0.3s ease-out;
        }
        
        .notification-success {
            border-left: 4px solid #48bb78;
        }
        
        .notification-error {
            border-left: 4px solid #f56565;
        }
        
        .notification-info {
            border-left: 4px solid #667eea;
        }
        
        .notification-content {
            padding: 1rem;
            position: relative;
        }
        
        .notification-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .notification-message {
            color: #666;
            line-height: 1.4;
            white-space: pre-line;
        }
        
        .notification-close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #999;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .notification-close:hover {
            color: #333;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    
    // Add styles if not already added
    if (!document.querySelector('#notification-styles')) {
        const styleElement = document.createElement('style');
        styleElement.id = 'notification-styles';
        styleElement.textContent = notificationStyles;
        document.head.appendChild(styleElement);
    }
    
    // Add to DOM
    document.body.appendChild(notification);
    
    // Handle close button
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', function() {
        notification.style.animation = 'slideInRight 0.3s ease-out reverse';
        setTimeout(() => notification.remove(), 300);
    });
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideInRight 0.3s ease-out reverse';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Utility function to get current language
function getCurrentLang() {
    return document.documentElement.lang || 'tr';
}

// Enhanced hover effects for cards
document.addEventListener('DOMContentLoaded', function() {
    // Add enhanced hover effects to promo cards
    const promoCards = document.querySelectorAll('.promo-card');
    promoCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Add hover effects to service cards
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            const icon = this.querySelector('.service-icon');
            if (icon) {
                icon.style.transform = 'scale(1.1) rotate(5deg)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            const icon = this.querySelector('.service-icon');
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
            }
        });
    });
});

// Coin ticker pause on hover
document.addEventListener('DOMContentLoaded', function() {
    const tickerTrack = document.querySelector('.ticker-track');
    
    if (tickerTrack) {
        tickerTrack.addEventListener('mouseenter', function() {
            this.style.animationPlayState = 'paused';
        });
        
        tickerTrack.addEventListener('mouseleave', function() {
            this.style.animationPlayState = 'running';
        });
    }
});

// Loading screen
function showLoadingScreen() {
    const loadingHTML = `
        <div id="loading-screen" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            transition: opacity 0.5s ease;
        ">
            <div style="text-align: center; color: white;">
                <div style="
                    width: 50px;
                    height: 50px;
                    border: 3px solid rgba(255, 255, 255, 0.3);
                    border-top: 3px solid #ffd700;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 1rem;
                "></div>
                <div style="font-size: 1.2rem; font-weight: 600;">
                    <i class="fas fa-chart-line" style="margin-right: 0.5rem;"></i>
                    GlobalBorsa
                </div>
            </div>
        </div>
        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    `;
    
    document.body.insertAdjacentHTML('beforeend', loadingHTML);
    
    // Remove loading screen after page load
    window.addEventListener('load', function() {
        setTimeout(() => {
            const loadingScreen = document.getElementById('loading-screen');
            if (loadingScreen) {
                loadingScreen.style.opacity = '0';
                setTimeout(() => loadingScreen.remove(), 500);
            }
        }, 1000);
    });
}

// Initialize loading screen
showLoadingScreen();

// Performance monitoring
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 GlobalBorsa Landing Page loaded successfully');
    console.log('📊 Performance metrics:');
    console.log('- Hero Slider:', document.querySelectorAll('.slide').length, 'slides');
    console.log('- Promo Cards:', document.querySelectorAll('.promo-card').length, 'cards');
    console.log('- Service Cards:', document.querySelectorAll('.service-card').length, 'services');
    console.log('- Screen size:', window.innerWidth + 'x' + window.innerHeight);
});

// Error handling
window.addEventListener('error', function(e) {
    console.warn('Landing page error:', e.error);
    // Gracefully handle errors without breaking the user experience
});

// ===== MARKET INDICATORS ANIMATION =====
function initMarketIndicators() {
    const indicators = document.querySelectorAll('.indicator-item');
    
    // Animate price changes
    function animatePriceChange() {
        indicators.forEach(indicator => {
            const priceElement = indicator.querySelector('.price');
            const changeElement = indicator.querySelector('.change');
            
            if (!priceElement || !changeElement) return;
            
            // Random price simulation
            if (Math.random() > 0.7) { // 30% chance of change
                const currentPrice = parseFloat(priceElement.textContent.replace(/[^0-9.,]/g, '').replace(',', '.'));
                const changePercent = (Math.random() - 0.5) * 0.02; // ±1% change
                const newPrice = currentPrice * (1 + changePercent);
                
                // Update price with animation
                priceElement.style.transform = 'scale(1.05)';
                priceElement.style.color = changePercent > 0 ? '#22c55e' : '#ef4444';
                
                setTimeout(() => {
                    priceElement.textContent = newPrice.toFixed(2) + ' TL';
                    
                    setTimeout(() => {
                        priceElement.style.transform = 'scale(1)';
                        priceElement.style.color = '#fff';
                    }, 300);
                }, 150);
                
                // Update change indicator
                const changeValue = (changePercent * 100);
                changeElement.textContent = (changePercent > 0 ? '+' : '') + changeValue.toFixed(2) + '%';
                changeElement.className = 'change ' + (changePercent > 0 ? 'positive' : 'negative');
            }
        });
    }
    
    // Run price animation every 3-8 seconds
    setInterval(animatePriceChange, 3000 + Math.random() * 5000);
}

// ===== ENHANCED CARD ANIMATIONS =====
function initCardAnimations() {
    const promoCards = document.querySelectorAll('.promo-card');
    
    promoCards.forEach(card => {
        // Tilt effect on mouse move
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;
            
            card.style.transform = `
                translateY(-8px) 
                scale(1.02) 
                perspective(1000px) 
                rotateX(${rotateX}deg) 
                rotateY(${rotateY}deg)
            `;
        });
        
        // Reset on mouse leave
        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
        
        // Click ripple effect
        card.addEventListener('click', (e) => {
            const ripple = document.createElement('span');
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.3);
                transform: scale(0);
                animation: ripple 0.6s linear;
                left: ${x - 10}px;
                top: ${y - 10}px;
                width: 20px;
                height: 20px;
                pointer-events: none;
            `;
            
            card.style.position = 'relative';
            card.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });
    
    // Add ripple animation CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// ===== ENHANCED HERO SLIDER =====
class EnhancedHeroSlider {
    constructor() {
        this.slides = document.querySelectorAll('.slide');
        this.progressBar = document.getElementById('sliderProgress');
        this.currentSlide = 0;
        this.totalSlides = this.slides.length;
        this.autoPlayInterval = null;
        this.progressInterval = null;
        this.isPlaying = true;
        this.autoPlayDuration = 6000; // 6 seconds
        
        if (this.slides.length > 0) {
            this.init();
        }
    }
    
    init() {
        // Touch/swipe support
        this.addTouchSupport();
        
        // Pause on hover
        const slider = document.querySelector('.hero-slider');
        if (slider) {
            slider.addEventListener('mouseenter', () => this.pauseAutoPlay());
            slider.addEventListener('mouseleave', () => this.resumeAutoPlay());
        }
        
        // Start auto-play
        this.startAutoPlay();
    }
    
    goToSlide(slideIndex) {
        // Remove active class from current slide
        this.slides[this.currentSlide].classList.remove('active');
        
        // Set new current slide
        this.currentSlide = slideIndex;
        
        // Add active class to new slide
        this.slides[this.currentSlide].classList.add('active');
        
        // Restart progress bar
        this.resetProgress();
    }
    
    nextSlide() {
        const nextIndex = (this.currentSlide + 1) % this.totalSlides;
        this.goToSlide(nextIndex);
    }
    
    previousSlide() {
        const prevIndex = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
        this.goToSlide(prevIndex);
    }
    
    startAutoPlay() {
        this.autoPlayInterval = setInterval(() => {
            if (this.isPlaying) {
                this.nextSlide();
            }
        }, this.autoPlayDuration);
        
        this.startProgress();
    }
    
    startProgress() {
        if (!this.progressBar) return;
        
        let progress = 0;
        const increment = 100 / (this.autoPlayDuration / 100);
        
        this.progressInterval = setInterval(() => {
            if (this.isPlaying) {
                progress += increment;
                this.progressBar.style.width = `${progress}%`;
                
                if (progress >= 100) {
                    progress = 0;
                }
            }
        }, 100);
    }
    
    resetProgress() {
        clearInterval(this.progressInterval);
        if (this.progressBar) {
            this.progressBar.style.width = '0%';
        }
        this.startProgress();
    }
    
    pauseAutoPlay() {
        this.isPlaying = false;
    }
    
    resumeAutoPlay() {
        this.isPlaying = true;
    }
    
    addTouchSupport() {
        let startX = 0;
        let startY = 0;
        
        const slider = document.querySelector('.hero-slider');
        if (!slider) return;
        
        slider.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        });
        
        slider.addEventListener('touchend', (e) => {
            const endX = e.changedTouches[0].clientX;
            const endY = e.changedTouches[0].clientY;
            const distX = endX - startX;
            const distY = endY - startY;
            
            // Check if horizontal swipe and minimum distance
            if (Math.abs(distX) > Math.abs(distY) && Math.abs(distX) > 50) {
                if (distX > 0) {
                    this.previousSlide();
                } else {
                    this.nextSlide();
                }
            }
        });
    }
}

// ===== ENHANCED SCROLL ANIMATIONS =====
class EnhancedScrollAnimations {
    constructor() {
        this.animateElements = document.querySelectorAll('.animate-on-scroll');
        this.init();
    }
    
    init() {
        // Initial check
        this.checkElements();
        
        // Optimized scroll handler
        let ticking = false;
        const scrollHandler = () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    this.checkElements();
                    ticking = false;
                });
                ticking = true;
            }
        };
        
        window.addEventListener('scroll', scrollHandler);
        window.addEventListener('resize', scrollHandler);
    }
    
    checkElements() {
        this.animateElements.forEach((element) => {
            if (this.isElementInViewport(element) && !element.classList.contains('animate')) {
                // Get delay from data attribute
                const delay = element.getAttribute('data-delay') || '0';
                element.style.setProperty('--delay', `${delay}s`);
                
                // Add animate class with delay
                setTimeout(() => {
                    element.classList.add('animate');
                }, parseFloat(delay) * 1000);
            }
        });
    }
    
    isElementInViewport(element) {
        const rect = element.getBoundingClientRect();
        const windowHeight = window.innerHeight;
        
        return (
            rect.top < windowHeight * 0.8 &&
            rect.bottom > 0
        );
    }
}

// ===== UTILITY FUNCTIONS =====

// Smooth scroll to top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Add scroll to top functionality
window.addEventListener('scroll', () => {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    
    if (scrollTop > 300) {
        if (!document.querySelector('.scroll-to-top')) {
            const scrollBtn = document.createElement('button');
            scrollBtn.className = 'scroll-to-top';
            scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            scrollBtn.style.cssText = `
                position: fixed;
                bottom: 100px;
                right: 30px;
                background: #2563eb;
                color: #fff;
                border: none;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 1.2rem;
                z-index: 999;
                transition: all 0.3s ease;
                opacity: 0.8;
            `;
            
            scrollBtn.addEventListener('click', scrollToTop);
            scrollBtn.addEventListener('mouseenter', () => {
                scrollBtn.style.background = '#1d4ed8';
                scrollBtn.style.transform = 'translateY(-3px)';
            });
            scrollBtn.addEventListener('mouseleave', () => {
                scrollBtn.style.background = '#2563eb';
                scrollBtn.style.transform = 'translateY(0)';
            });
            
            document.body.appendChild(scrollBtn);
        }
    } else {
        const scrollBtn = document.querySelector('.scroll-to-top');
        if (scrollBtn) {
            document.body.removeChild(scrollBtn);
        }
    }
});

// ===== CTA BUTTON EFFECTS =====
document.addEventListener('DOMContentLoaded', () => {
    const ctaButtons = document.querySelectorAll('.btn-cta, .card-btn, .submit-btn');
    
    ctaButtons.forEach(button => {
        button.addEventListener('mouseenter', () => {
            const ripple = document.createElement('span');
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.3);
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            `;
            
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = '50%';
            ripple.style.top = '50%';
            ripple.style.marginLeft = -size / 2 + 'px';
            ripple.style.marginTop = -size / 2 + 'px';
            
            button.style.position = 'relative';
            button.style.overflow = 'hidden';
            button.appendChild(ripple);
            
            setTimeout(() => {
                try {
                    button.removeChild(ripple);
                } catch (e) {
                    // Ripple already removed
                }
            }, 600);
        });
    });
});

// ===== PERFORMANCE OPTIMIZATION =====
// Debounce function for scroll events
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Optimized scroll handler
const optimizedScrollHandler = debounce(() => {
    const navbar = document.getElementById('navbar');
    if (navbar) {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }
}, 10);

window.addEventListener('scroll', optimizedScrollHandler);

// Export functions for external use
window.GlobalBorsaLanding = {
    showNotification,
    getCurrentLang,
    initHeroSlider,
    initScrollAnimations,
    initMarketIndicators,
    initCardAnimations,
    EnhancedHeroSlider,
    EnhancedScrollAnimations
};
