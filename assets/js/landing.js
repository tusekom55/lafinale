// GlobalBorsa Landing Page JavaScript - XM.com Style Animations

document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS (Animate On Scroll)
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true,
        offset: 100
    });
    
    // Initialize all animations and effects
    initParticleAnimation();
    initCounterAnimations();
    initScrollEffects();
    initHoverEffects();
    initCryptoTicker();
    initSmoothScrolling();
});

// Particle Animation Effect
function initParticleAnimation() {
    const particlesContainer = document.querySelector('.particles');
    if (!particlesContainer) return;
    
    // Create additional floating particles
    for (let i = 0; i < 15; i++) {
        createParticle(particlesContainer, i);
    }
}

function createParticle(container, index) {
    const particle = document.createElement('div');
    particle.className = 'floating-particle';
    particle.style.cssText = `
        position: absolute;
        width: ${Math.random() * 4 + 2}px;
        height: ${Math.random() * 4 + 2}px;
        background: rgba(255, 255, 255, ${Math.random() * 0.6 + 0.2});
        border-radius: 50%;
        top: ${Math.random() * 100}%;
        left: ${Math.random() * 100}%;
        animation: float-particle ${Math.random() * 10 + 10}s linear infinite;
        animation-delay: ${index * 0.5}s;
    `;
    
    // Add floating animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes float-particle {
            0% {
                transform: translateY(100vh) translateX(0px);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) translateX(${Math.random() * 200 - 100}px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    container.appendChild(particle);
}

// Counter Animations for Stats Section
function initCounterAnimations() {
    const counters = document.querySelectorAll('[data-counter]');
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    counters.forEach(counter => observer.observe(counter));
}

function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-counter'));
    const duration = 2000; // 2 seconds
    const increment = target / (duration / 16); // 60fps
    let current = 0;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = formatNumber(target);
            clearInterval(timer);
        } else {
            element.textContent = formatNumber(Math.floor(current));
        }
    }, 16);
}

function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toLocaleString();
}

// Scroll Effects
function initScrollEffects() {
    window.addEventListener('scroll', handleScroll);
    
    // Parallax effect for hero background
    const heroBackground = document.querySelector('.hero-background');
    if (heroBackground) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallax = scrolled * 0.3;
            heroBackground.style.transform = `translateY(${parallax}px)`;
        });
    }
    
    // Navigation background change on scroll
    const navbar = document.querySelector('.navbar-dark');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(26, 54, 93, 0.95)';
                navbar.style.backdropFilter = 'blur(10px)';
            } else {
                navbar.style.background = 'transparent';
                navbar.style.backdropFilter = 'none';
            }
        });
    }
}

function handleScroll() {
    const scrollProgress = window.pageYOffset / (document.documentElement.scrollHeight - window.innerHeight);
    
    // Add floating animation to feature cards on scroll
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach((card, index) => {
        const rect = card.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
            card.style.transform = `translateY(${Math.sin(Date.now() * 0.001 + index) * 2}px)`;
        }
    });
}

// Hover Effects
function initHoverEffects() {
    // Feature card hover effects
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-15px) scale(1.02)';
            this.style.boxShadow = '0 30px 60px rgba(0, 0, 0, 0.2)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.1)';
        });
    });
    
    // Crypto card hover effects
    const cryptoCards = document.querySelectorAll('.crypto-card');
    cryptoCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) rotate(2deg)';
            this.style.background = 'rgba(255, 255, 255, 0.2)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) rotate(0deg)';
            this.style.background = 'rgba(255, 255, 255, 0.1)';
        });
    });
    
    // Button hover effects with ripple
    const buttons = document.querySelectorAll('.btn-primary, .feature-btn');
    buttons.forEach(button => {
        button.addEventListener('click', createRipple);
    });
}

function createRipple(event) {
    const button = event.currentTarget;
    const circle = document.createElement('span');
    const diameter = Math.max(button.clientWidth, button.clientHeight);
    const radius = diameter / 2;
    
    circle.style.width = circle.style.height = `${diameter}px`;
    circle.style.left = `${event.clientX - button.offsetLeft - radius}px`;
    circle.style.top = `${event.clientY - button.offsetTop - radius}px`;
    circle.classList.add('ripple');
    
    const rippleCSS = `
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    
    if (!document.querySelector('#ripple-style')) {
        const style = document.createElement('style');
        style.id = 'ripple-style';
        style.textContent = rippleCSS;
        document.head.appendChild(style);
    }
    
    const existingRipple = button.querySelector('.ripple');
    if (existingRipple) {
        existingRipple.remove();
    }
    
    button.appendChild(circle);
}

// Crypto Ticker Animation
function initCryptoTicker() {
    const cryptoCards = document.querySelectorAll('.crypto-card');
    
    // Simulate price updates
    setInterval(() => {
        cryptoCards.forEach(card => {
            const priceElement = card.querySelector('.price');
            const changeElement = card.querySelector('.change');
            
            if (priceElement && changeElement) {
                // Simulate small price changes
                const currentPrice = parseFloat(priceElement.textContent.replace(/[^\d.-]/g, ''));
                const change = (Math.random() - 0.5) * 0.01; // ±0.5% change
                const newPrice = currentPrice * (1 + change);
                
                // Update price with animation
                priceElement.style.transform = 'scale(1.1)';
                priceElement.style.color = change > 0 ? '#48bb78' : '#f56565';
                
                setTimeout(() => {
                    priceElement.textContent = formatPrice(newPrice) + ' TL';
                    priceElement.style.transform = 'scale(1)';
                    priceElement.style.color = 'white';
                }, 200);
                
                // Update change percentage
                const changePercent = change * 100;
                changeElement.textContent = (changePercent >= 0 ? '+' : '') + changePercent.toFixed(2) + '%';
                changeElement.className = 'change ' + (changePercent >= 0 ? 'positive' : 'negative');
            }
        });
    }, 10000); // Update every 10 seconds
}

function formatPrice(price) {
    if (price >= 1000) {
        return price.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    } else if (price >= 1) {
        return price.toFixed(4);
    } else {
        return price.toFixed(8);
    }
}

// Smooth Scrolling for anchor links
function initSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Intersection Observer for revealing elements
function initRevealAnimation() {
    const revealElements = document.querySelectorAll('.feature-card, .stat-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    revealElements.forEach(el => {
        el.classList.add('reveal');
        observer.observe(el);
    });
}

// Add reveal animation CSS
const revealCSS = `
    .reveal {
        opacity: 0;
        transform: translateY(50px);
        transition: all 0.8s ease;
    }
    
    .revealed {
        opacity: 1;
        transform: translateY(0);
    }
`;

const style = document.createElement('style');
style.textContent = revealCSS;
document.head.appendChild(style);

// Initialize reveal animations
document.addEventListener('DOMContentLoaded', initRevealAnimation);

// Background Mountain Animation Enhancement
function enhanceMountainAnimation() {
    const mountain = document.querySelector('.mountain-animation');
    if (mountain) {
        let time = 0;
        
        function animate() {
            time += 0.01;
            const wave1 = Math.sin(time) * 3;
            const wave2 = Math.cos(time * 1.5) * 2;
            
            mountain.style.transform = `translateY(${wave1}px) translateX(${wave2}px)`;
            requestAnimationFrame(animate);
        }
        
        animate();
    }
}

// Initialize enhanced mountain animation
document.addEventListener('DOMContentLoaded', enhanceMountainAnimation);

// Typewriter Effect for Hero Title
function initTypewriterEffect() {
    const heroTitle = document.querySelector('.hero-title');
    if (!heroTitle) return;
    
    const text = heroTitle.textContent;
    heroTitle.textContent = '';
    heroTitle.style.borderRight = '2px solid #ffd700';
    
    let i = 0;
    function typeWriter() {
        if (i < text.length) {
            heroTitle.textContent += text.charAt(i);
            i++;
            setTimeout(typeWriter, 50);
        } else {
            heroTitle.style.borderRight = 'none';
        }
    }
    
    // Start typewriter effect after a delay
    setTimeout(typeWriter, 1000);
}

// Mouse movement parallax effect
function initMouseParallax() {
    document.addEventListener('mousemove', (e) => {
        const mouseX = e.clientX / window.innerWidth;
        const mouseY = e.clientY / window.innerHeight;
        
        const cryptoCards = document.querySelectorAll('.crypto-card');
        cryptoCards.forEach((card, index) => {
            const speed = (index + 1) * 0.5;
            const x = (mouseX - 0.5) * speed;
            const y = (mouseY - 0.5) * speed;
            
            card.style.transform += ` translate(${x}px, ${y}px)`;
        });
    });
}

// Initialize mouse parallax
document.addEventListener('DOMContentLoaded', initMouseParallax);

// Performance optimization: Throttle scroll events
function throttle(func, wait) {
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

// Apply throttling to scroll events
window.addEventListener('scroll', throttle(handleScroll, 16)); // ~60fps

// Loading screen animation
function initLoadingScreen() {
    const loadingScreen = document.createElement('div');
    loadingScreen.className = 'loading-screen';
    loadingScreen.innerHTML = `
        <div class="loading-content">
            <div class="loading-logo">
                <i class="fas fa-chart-line"></i>
                GlobalBorsa
            </div>
            <div class="loading-spinner"></div>
        </div>
    `;
    
    const loadingCSS = `
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1a365d 0%, #2b5ce6 50%, #3182ce 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }
        
        .loading-content {
            text-align: center;
            color: white;
        }
        
        .loading-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid #ffd700;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    
    const style = document.createElement('style');
    style.textContent = loadingCSS;
    document.head.appendChild(style);
    document.body.appendChild(loadingScreen);
    
    // Remove loading screen after page load
    window.addEventListener('load', () => {
        setTimeout(() => {
            loadingScreen.style.opacity = '0';
            setTimeout(() => {
                loadingScreen.remove();
            }, 500);
        }, 1000);
    });
}

// Initialize loading screen
initLoadingScreen();

// Error handling for failed animations
window.addEventListener('error', (e) => {
    console.warn('Animation error:', e.error);
    // Gracefully degrade animations if there are errors
});

// Export functions for external use
window.GlobalBorsaLanding = {
    initCounterAnimations,
    initParticleAnimation,
    initScrollEffects,
    formatPrice,
    formatNumber
};
