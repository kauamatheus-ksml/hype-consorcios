// Global variables
let isModalOpen = false;
let isMobileMenuOpen = false;

// DOM Elements
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const menuIcon = document.getElementById('menuIcon');
const mobileNav = document.getElementById('mobileNav');
const simulationModal = document.getElementById('simulationModal');
const simulationForm = document.getElementById('simulationForm');
const floatingBtn = document.getElementById('floatingBtn');

// Initialize when DOM loads
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    initializeAnimations();
    initializeFormMasks();
    handleScrollEffects();
});

// Event Listeners
function initializeEventListeners() {
    // Mobile menu toggle
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    }

    // Modal events
    if (simulationModal) {
        simulationModal.addEventListener('click', function(e) {
            if (e.target === simulationModal) {
                closeSimulationModal();
            }
        });
    }

    // Form submission
    if (simulationForm) {
        simulationForm.addEventListener('submit', handleFormSubmission);
    }

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const headerHeight = document.querySelector('.header').offsetHeight;
                const targetPosition = target.offsetTop - headerHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });

                // Close mobile menu if open
                if (isMobileMenuOpen) {
                    toggleMobileMenu();
                }
            }
        });
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isModalOpen) {
            closeSimulationModal();
        }
    });

    // Scroll event for floating button
    window.addEventListener('scroll', handleScrollEffects);
}

// Mobile Menu Functions
function toggleMobileMenu() {
    isMobileMenuOpen = !isMobileMenuOpen;
    
    if (isMobileMenuOpen) {
        mobileNav.classList.add('active');
        menuIcon.classList.remove('fa-bars');
        menuIcon.classList.add('fa-times');
    } else {
        mobileNav.classList.remove('active');
        menuIcon.classList.remove('fa-times');
        menuIcon.classList.add('fa-bars');
    }
}

// Modal Functions
function openSimulationModal(selectedVehicle = '') {
    isModalOpen = true;
    simulationModal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Pre-select vehicle if provided
    if (selectedVehicle) {
        const vehicleSelect = document.getElementById('vehicle');
        if (vehicleSelect) {
            vehicleSelect.value = selectedVehicle;
        }
    }
}

function closeSimulationModal() {
    isModalOpen = false;
    simulationModal.classList.remove('active');
    document.body.style.overflow = '';
}

// FAQ Functions
function toggleFAQ(index) {
    const answer = document.getElementById(`faq-answer-${index}`);
    const icon = document.getElementById(`faq-icon-${index}`);
    
    if (answer.classList.contains('active')) {
        answer.classList.remove('active');
        icon.classList.remove('rotate');
    } else {
        // Close all other FAQs
        document.querySelectorAll('.faq-answer').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelectorAll('.faq-icon').forEach(item => {
            item.classList.remove('rotate');
        });
        
        // Open current FAQ
        answer.classList.add('active');
        icon.classList.add('rotate');
    }
}

// Form Functions
function toggleDownPayment(hasDownPayment) {
    const downPaymentGroup = document.getElementById('downPaymentGroup');
    const downPaymentInput = document.getElementById('downPayment');
    
    if (hasDownPayment) {
        downPaymentGroup.style.display = 'block';
        downPaymentInput.required = true;
    } else {
        downPaymentGroup.style.display = 'none';
        downPaymentInput.required = false;
        downPaymentInput.value = '';
    }
}

function handleFormSubmission(e) {
    e.preventDefault();
    
    const formData = new FormData(simulationForm);
    const data = Object.fromEntries(formData);
    
    // Basic validation
    if (!data.name || !data.vehicle || !data.phone) {
        showAlert('Por favor, preencha todos os campos obrigatórios.', 'error');
        return;
    }

    // Show loading state
    const submitButton = simulationForm.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    submitButton.disabled = true;

    // Simulate API call (replace with actual endpoint)
    setTimeout(() => {
        // Create WhatsApp message
        const whatsappMessage = createWhatsAppMessage(data);
        const whatsappURL = `https://api.whatsapp.com/send/?phone=5547996862997&text=${encodeURIComponent(whatsappMessage)}`;
        
        // Open WhatsApp
        window.open(whatsappURL, '_blank');
        
        // Reset form and close modal
        simulationForm.reset();
        closeSimulationModal();
        
        // Show success message
        showAlert('Simulação enviada! Você será redirecionado para o WhatsApp.', 'success');
        
        // Reset button
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }, 1500);
}

function createWhatsAppMessage(data) {
    let message = `🚗 *SIMULAÇÃO DE CONSÓRCIO*\n\n`;
    message += `👤 *Nome:* ${data.name}\n`;
    message += `🚙 *Veículo:* ${data.vehicle}\n`;
    message += `📱 *Telefone:* ${data.phone}\n`;
    
    if (data.email) {
        message += `📧 *E-mail:* ${data.email}\n`;
    }
    
    if (data.hasDownPayment === 'yes' && data.downPayment) {
        message += `💰 *Entrada:* ${data.downPayment}\n`;
    } else {
        message += `💰 *Entrada:* Não possui\n`;
    }
    
    message += `\nGostaria de receber uma simulação personalizada! 😊`;
    
    return message;
}

// Utility Functions
function showAlert(message, type = 'info') {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add styles
    alert.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        z-index: 70;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        animation: slideInRight 0.3s ease-out;
    `;
    
    // Add to page
    document.body.appendChild(alert);
    
    // Remove after 5 seconds
    setTimeout(() => {
        alert.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            document.body.removeChild(alert);
        }, 300);
    }, 5000);
}

// Initialize form masks
function initializeFormMasks() {
    // Phone mask
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length <= 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                if (value.length < 14) {
                    value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                }
            }
            
            e.target.value = value;
        });
    }

    // Currency mask for down payment
    const currencyInput = document.getElementById('downPayment');
    if (currencyInput) {
        currencyInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (value / 100).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
            e.target.value = value;
        });
    }
}

// Scroll effects
function handleScrollEffects() {
    const scrollY = window.scrollY;
    const windowHeight = window.innerHeight;
    
    // Show/hide floating button
    if (floatingBtn) {
        if (scrollY > windowHeight / 2) {
            floatingBtn.style.opacity = '1';
            floatingBtn.style.visibility = 'visible';
        } else {
            floatingBtn.style.opacity = '0';
            floatingBtn.style.visibility = 'hidden';
        }
    }
    
    // Animate elements on scroll
    const elements = document.querySelectorAll('.animate-on-scroll');
    elements.forEach(element => {
        const elementTop = element.offsetTop;
        const elementHeight = element.offsetHeight;
        
        if (scrollY > elementTop - windowHeight + elementHeight / 4) {
            element.classList.add('animate-fade-in');
        }
    });
}

// Initialize animations
function initializeAnimations() {
    // Add animation classes to elements
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    document.querySelectorAll('.car-card, .feature-card, .faq-item').forEach(el => {
        observer.observe(el);
    });
}

// Additional CSS for animations and alerts
const additionalStyles = `
@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

.alert-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
`;

// Add additional styles to document
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);

// Make functions global for onclick handlers
window.openSimulationModal = openSimulationModal;
window.closeSimulationModal = closeSimulationModal;
window.toggleFAQ = toggleFAQ;
window.toggleDownPayment = toggleDownPayment;

// Carousel functionality for Clientes Contemplados
class ContempladosCarousel {
    constructor() {
        this.currentSlide = 0;
        this.totalSlides = 10;
        this.autoplayInterval = null;
        this.autoplayDelay = 4000; // 4 segundos
        
        this.carousel = document.getElementById('contempladosCarousel');
        this.slides = document.querySelectorAll('.carousel-slide');
        this.dots = document.querySelectorAll('.dot');
        this.prevBtn = document.getElementById('prevBtn');
        this.nextBtn = document.getElementById('nextBtn');
        
        this.init();
    }
    
    init() {
        if (!this.carousel) return;
        
        // Event listeners
        this.prevBtn?.addEventListener('click', () => this.prevSlide());
        this.nextBtn?.addEventListener('click', () => this.nextSlide());
        
        // Dots navigation
        this.dots.forEach((dot, index) => {
            dot.addEventListener('click', () => this.goToSlide(index));
        });
        
        // Touch/swipe support
        this.addTouchSupport();
        
        // Start autoplay
        this.startAutoplay();
        
        // Pause on hover
        this.carousel.addEventListener('mouseenter', () => this.pauseAutoplay());
        this.carousel.addEventListener('mouseleave', () => this.startAutoplay());
    }
    
    goToSlide(slideIndex) {
        // Remove active classes
        this.slides[this.currentSlide]?.classList.remove('active');
        this.dots[this.currentSlide]?.classList.remove('active');
        
        // Update current slide
        this.currentSlide = slideIndex;
        
        // Add active classes
        this.slides[this.currentSlide]?.classList.add('active');
        this.dots[this.currentSlide]?.classList.add('active');
        
        // Transform carousel
        const translateX = -this.currentSlide * 100;
        this.carousel.style.transform = `translateX(${translateX}%)`;
    }
    
    nextSlide() {
        const nextIndex = (this.currentSlide + 1) % this.totalSlides;
        this.goToSlide(nextIndex);
    }
    
    prevSlide() {
        const prevIndex = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
        this.goToSlide(prevIndex);
    }
    
    startAutoplay() {
        this.pauseAutoplay(); // Clear existing interval
        this.autoplayInterval = setInterval(() => {
            this.nextSlide();
        }, this.autoplayDelay);
    }
    
    pauseAutoplay() {
        if (this.autoplayInterval) {
            clearInterval(this.autoplayInterval);
            this.autoplayInterval = null;
        }
    }
    
    addTouchSupport() {
        let startX = 0;
        let endX = 0;
        
        this.carousel.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });
        
        this.carousel.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            this.handleSwipe(startX, endX);
        });
        
        // Mouse drag support
        let isDragging = false;
        
        this.carousel.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX;
            this.carousel.style.cursor = 'grabbing';
        });
        
        this.carousel.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
        });
        
        this.carousel.addEventListener('mouseup', (e) => {
            if (!isDragging) return;
            isDragging = false;
            endX = e.clientX;
            this.carousel.style.cursor = 'grab';
            this.handleSwipe(startX, endX);
        });
        
        this.carousel.addEventListener('mouseleave', () => {
            if (isDragging) {
                isDragging = false;
                this.carousel.style.cursor = 'grab';
            }
        });
    }
    
    handleSwipe(startX, endX) {
        const threshold = 50;
        const diff = startX - endX;
        
        if (Math.abs(diff) > threshold) {
            if (diff > 0) {
                this.nextSlide();
            } else {
                this.prevSlide();
            }
        }
    }
}

// Initialize carousel when DOM loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize existing functionality
    initializeEventListeners();
    initializeAnimations();
    initializeFormMasks();
    handleScrollEffects();
    
    // Initialize carousel
    new ContempladosCarousel();
});