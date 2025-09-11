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

// Grid Carousel functionality for Clientes Contemplados
class ContempladosGridCarousel {
    constructor() {
        this.currentPosition = 0;
        this.itemWidth = 300; // 280px + 20px gap
        this.visibleItems = this.getVisibleItems();
        this.totalItems = 10;
        this.autoScrollInterval = null;
        this.autoScrollDelay = 3000; // 3 segundos
        
        this.track = document.getElementById('contempladosTrack');
        this.prevBtn = document.getElementById('gridPrevBtn');
        this.nextBtn = document.getElementById('gridNextBtn');
        this.items = document.querySelectorAll('.cliente-item');
        
        this.init();
    }
    
    init() {
        if (!this.track) return;
        
        // Update item width based on screen size
        this.updateDimensions();
        
        // Event listeners
        this.prevBtn?.addEventListener('click', () => this.movePrev());
        this.nextBtn?.addEventListener('click', () => this.moveNext());
        
        // Touch/swipe support
        this.addTouchSupport();
        
        // Auto scroll
        this.startAutoScroll();
        
        // Pause on hover
        this.track.addEventListener('mouseenter', () => this.pauseAutoScroll());
        this.track.addEventListener('mouseleave', () => this.startAutoScroll());
        
        // Window resize
        window.addEventListener('resize', () => this.updateDimensions());
    }
    
    getVisibleItems() {
        if (window.innerWidth <= 480) return 2.2;
        if (window.innerWidth <= 768) return 3.5;
        if (window.innerWidth <= 1024) return 4.5;
        return 5;
    }
    
    updateDimensions() {
        this.visibleItems = this.getVisibleItems();
        if (window.innerWidth <= 480) {
            this.itemWidth = 172; // 160px + 12px gap
        } else if (window.innerWidth <= 768) {
            this.itemWidth = 215; // 200px + 15px gap
        } else if (window.innerWidth <= 1024) {
            this.itemWidth = 260; // 240px + 20px gap
        } else {
            this.itemWidth = 300; // 280px + 20px gap
        }
    }
    
    moveNext() {
        const maxPosition = (this.totalItems - this.visibleItems) * this.itemWidth;
        
        if (this.currentPosition < maxPosition) {
            this.currentPosition += this.itemWidth * 2; // Move 2 items
            if (this.currentPosition > maxPosition) {
                this.currentPosition = maxPosition;
            }
        } else {
            this.currentPosition = 0; // Loop back to start
        }
        
        this.updatePosition();
    }
    
    movePrev() {
        if (this.currentPosition > 0) {
            this.currentPosition -= this.itemWidth * 2; // Move 2 items
            if (this.currentPosition < 0) {
                this.currentPosition = 0;
            }
        } else {
            // Go to end
            this.currentPosition = (this.totalItems - this.visibleItems) * this.itemWidth;
        }
        
        this.updatePosition();
    }
    
    updatePosition() {
        this.track.style.transform = `translateX(-${this.currentPosition}px)`;
    }
    
    startAutoScroll() {
        this.pauseAutoScroll();
        this.autoScrollInterval = setInterval(() => {
            this.moveNext();
        }, this.autoScrollDelay);
    }
    
    pauseAutoScroll() {
        if (this.autoScrollInterval) {
            clearInterval(this.autoScrollInterval);
            this.autoScrollInterval = null;
        }
    }
    
    addTouchSupport() {
        let startX = 0;
        let endX = 0;
        let isDragging = false;
        let startPosition = 0;
        
        this.track.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startPosition = this.currentPosition;
            this.pauseAutoScroll();
        });
        
        this.track.addEventListener('touchmove', (e) => {
            if (!startX) return;
            const currentX = e.touches[0].clientX;
            const diff = startX - currentX;
            const newPosition = startPosition + diff;
            
            // Apply resistance at boundaries
            if (newPosition < 0 || newPosition > (this.totalItems - this.visibleItems) * this.itemWidth) {
                this.track.style.transform = `translateX(-${startPosition + diff * 0.3}px)`;
            } else {
                this.track.style.transform = `translateX(-${newPosition}px)`;
            }
        });
        
        this.track.addEventListener('touchend', (e) => {
            if (!startX) return;
            endX = e.changedTouches[0].clientX;
            const diff = startX - endX;
            
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    this.moveNext();
                } else {
                    this.movePrev();
                }
            } else {
                this.updatePosition(); // Snap back
            }
            
            startX = 0;
            this.startAutoScroll();
        });
        
        // Mouse drag support
        this.track.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX;
            startPosition = this.currentPosition;
            this.track.style.cursor = 'grabbing';
            this.pauseAutoScroll();
        });
        
        this.track.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            const diff = startX - e.clientX;
            const newPosition = startPosition + diff;
            
            if (newPosition >= 0 && newPosition <= (this.totalItems - this.visibleItems) * this.itemWidth) {
                this.track.style.transform = `translateX(-${newPosition}px)`;
            }
        });
        
        this.track.addEventListener('mouseup', (e) => {
            if (!isDragging) return;
            isDragging = false;
            endX = e.clientX;
            const diff = startX - endX;
            
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    this.moveNext();
                } else {
                    this.movePrev();
                }
            } else {
                this.updatePosition();
            }
            
            this.track.style.cursor = 'grab';
            this.startAutoScroll();
        });
        
        this.track.addEventListener('mouseleave', () => {
            if (isDragging) {
                isDragging = false;
                this.track.style.cursor = 'grab';
                this.updatePosition();
                this.startAutoScroll();
            }
        });
    }
}

// Initialize grid carousel when DOM loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize existing functionality
    initializeEventListeners();
    initializeAnimations();
    initializeFormMasks();
    handleScrollEffects();
    
    // Initialize grid carousel
    new ContempladosGridCarousel();
});