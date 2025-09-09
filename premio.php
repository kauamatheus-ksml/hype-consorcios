<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consórcio para Veículos Premium - Hype Consórcios</title>
    <meta name="description" content="Conquiste seu carro premium com parcelas a partir de R$ 1.480,00. Planos personalizados e contemplação programada.">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <!-- Logo -->
                <div class="logo">
                    <img src="assets/images/logo.png" alt="Hype Consórcios Logo">
                    <div class="logo-text">
                        <div class="company-name">Hype Consórcios</div>
                    </div>
                </div>

                <!-- Desktop Navigation -->
                <nav class="nav-desktop">
                    <a href="index.php">Início</a>
                    <a href="index.php#carros">Veículos</a>
                    <a href="index.php#consorcio">Consórcio</a>
                    <a href="index.php#simulacao">Simulação</a>
                    <a href="index.php#contato">Contato</a>
                </nav>

                <!-- Contact Buttons -->
                <div class="header-buttons">
                    <a href="https://instagram.com/hype.consorcios" target="_blank" class="btn btn-outline">
                        <i class="fab fa-instagram"></i>
                        Instagram
                    </a>
                    <a href="https://api.whatsapp.com/send/?phone=5547996862997" target="_blank" class="btn btn-cta">
                        <i class="fab fa-whatsapp"></i>
                        WhatsApp
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars" id="menuIcon"></i>
                </button>
            </div>

            <!-- Mobile Navigation -->
            <nav class="nav-mobile" id="mobileNav">
                <a href="index.php">Início</a>
                <a href="index.php#carros">Veículos</a>
                <a href="index.php#consorcio">Consórcio</a>
                <a href="index.php#simulacao">Simulação</a>
                <a href="index.php#contato">Contato</a>
                <div class="mobile-buttons">
                    <a href="https://instagram.com/hype.consorcios" target="_blank" class="btn btn-outline">
                        <i class="fab fa-instagram"></i>
                        Instagram
                    </a>
                    <a href="https://api.whatsapp.com/send/?phone=5547996862997" target="_blank" class="btn btn-cta">
                        <i class="fab fa-whatsapp"></i>
                        WhatsApp
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-background">
            <img src="assets/images/mercedes.jpg" alt="Consórcio Veículos Premium">
            <div class="hero-overlay"></div>
        </div>
        
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-crown"></i>
                    <span>Veículos Premium</span>
                </div>
                
                <h1 class="hero-title">
                    🔹 Consórcio para 
                    <span class="gradient-text">Veículos Premium</span>
                </h1>
                
                <p class="hero-subtitle">
                    Parcelas a partir de <strong>R$ 1.480,00</strong> - Planos personalizados para você
                </p>

                <div class="hero-buttons">
                    <button class="btn btn-hero" onclick="openSimulationModal()">
                        Simular agora
                    </button>
                    <a href="https://api.whatsapp.com/send/?phone=5547996862997" target="_blank" class="btn btn-outline-white">
                        Falar com especialista
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2 class="section-title">
                        O carro premium que <span class="gradient-text">combina com seu estilo</span>
                    </h2>
                    
                    <div class="content-block">
                        <p class="section-text">
                            Quem busca um carro premium sabe que não se trata apenas de um meio de transporte, mas de conforto, status e experiência de dirigir.
                        </p>
                        
                        <p class="section-text">
                            Com o consórcio premium, você pode conquistar o carro dos seus sonhos sem se descapitalizar. Oferecemos planos personalizados para se adaptar ao seu perfil, opções de lance "troca de chaves" e contemplação programada, garantindo previsibilidade no seu planejamento.
                        </p>
                        
                        <div class="highlight-box">
                            <i class="fas fa-hand-point-right"></i>
                            <p><strong>O carro que combina com o seu estilo de vida pode estar mais próximo do que você imagina.</strong></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="feature-label">Planos Personalizados</div>
                    <p class="feature-description">Criamos um plano que se adapta perfeitamente ao seu perfil e necessidades específicas.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="feature-label">Lance "Troca de Chaves"</div>
                    <p class="feature-description">Opção exclusiva que permite acelerar sua contemplação de forma inteligente.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="feature-label">Contemplação Programada</div>
                    <p class="feature-description">Garantia de previsibilidade no seu planejamento financeiro.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="feature-label">Sem Descapitalização</div>
                    <p class="feature-description">Conquiste seu carro premium preservando seu patrimônio e investimentos.</p>
                </div>
            </div>

            <!-- Success Story Section -->
            <div class="success-story">
                <div class="success-content">
                    <div class="success-image">
                        <img src="assets/images/cliente-premium.jpg" alt="Cliente conquistando veículo premium">
                    </div>
                    <div class="success-text">
                        <h3>Histórias de sucesso</h3>
                        <p>
                            "Sempre sonhei com um carro premium, mas achava que seria impossível sem comprometer meu patrimônio. 
                            Com o consórcio da Hype, consegui meu BMW sem me descapitalizar. O atendimento foi excepcional!"
                        </p>
                        <div class="success-author">
                            <strong>Carlos Eduardo</strong>
                            <span>Contemplado em 12 meses</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="about-cta">
                <h3>Pronto para conquistar seu veículo premium?</h3>
                <p>Faça uma simulação personalizada e descubra como é possível realizar esse sonho!</p>
                <div class="hero-buttons">
                    <button class="btn btn-hero" onclick="openSimulationModal()">
                        <i class="fas fa-calculator"></i>
                        Simular Consórcio
                    </button>
                    <a href="https://api.whatsapp.com/send/?phone=5547996862997&text=Olá! Tenho interesse no consórcio de veículos premium. Gostaria de mais informações." target="_blank" class="btn btn-success">
                        <i class="fab fa-whatsapp"></i>
                        Falar no WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <!-- Company Info -->
                <div class="footer-section">
                    <div class="footer-logo">
                        <div class="logo-text">
                            <div class="company-name gradient-text">Hype</div>
                            <div class="subtitle">Consórcios</div>
                        </div>
                    </div>
                    <p class="footer-description">
                        Hype Consórcios E Investimentos Ltda oferece as melhores condições 
                        para você realizar o sonho do veículo novo.
                    </p>
                    <div class="social-links">
                        <a href="https://instagram.com/hype.consorcios" target="_blank" class="social-link">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://api.whatsapp.com/send/?phone=5547996862997" target="_blank" class="social-link">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-section">
                    <h3 class="footer-title">Links Rápidos</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Início</a></li>
                        <li><a href="index.php#carros">Veículos</a></li>
                        <li><a href="index.php#consorcio">Sobre o Consórcio</a></li>
                        <li><a href="index.php#simulacao">Simulação</a></li>
                    </ul>
                </div>

                <!-- Vehicle Types -->
                <div class="footer-section">
                    <h3 class="footer-title">Tipos de Consórcio</h3>
                    <ul class="footer-links">
                        <li><a href="leves.php">Veículos Leves</a></li>
                        <li><a href="premio.php">Veículos Premium</a></li>
                        <li><a href="pesados.php">Veículos Pesados</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="footer-section">
                    <h3 class="footer-title">Contato</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fab fa-whatsapp"></i>
                            <a href="https://api.whatsapp.com/send/?phone=5547996862997" target="_blank">
                                (47) 99686-2997
                            </a>
                        </div>
                        <div class="contact-item">
                            <i class="fab fa-instagram"></i>
                            <a href="https://instagram.com/hype.consorcios" target="_blank">
                                @hype.consorcios
                            </a>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Atendimento em todo o Brasil</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p class="copyright">
                        © 2024 Hype Consórcios E Investimentos Ltda. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating Simulation Button -->
    <button class="floating-simulation-btn" onclick="openSimulationModal()" id="floatingBtn">
        <i class="fas fa-calculator"></i>
        <span>Simular</span>
    </button>

    <!-- Simulation Modal -->
    <div class="modal-overlay" id="simulationModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Simule seu Consórcio de Veículos Premium</h2>
                <button class="modal-close" onclick="closeSimulationModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="modal-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <h3>Preencha os dados e receba uma simulação personalizada para veículos premium</h3>
                
                <form class="simulation-form" id="simulationForm" onsubmit="submitLeadForm(event)">
                    <div class="form-group">
                        <label for="name">Nome completo *</label>
                        <input type="text" id="name" name="name" placeholder="Seu nome completo" required>
                    </div>

                    <div class="form-group">
                        <label for="vehicle">Qual carro premium é do seu interesse? *</label>
                        <input type="text" id="vehicle" name="vehicle" placeholder="Ex: BMW X3, Mercedes C-Class, Audi A4..." required>
                    </div>

                    <div class="form-group">
                        <label>Você possui entrada? *</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="hasDownPayment" value="yes" onclick="toggleDownPayment(true)">
                                <span>Sim</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="hasDownPayment" value="no" onclick="toggleDownPayment(false)">
                                <span>Não</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" id="downPaymentGroup" style="display: none;">
                        <label for="downPayment">Qual valor da entrada?</label>
                        <input type="text" id="downPayment" name="downPayment" placeholder="R$ 0,00" class="currency-input">
                    </div>

                    <div class="form-group">
                        <label for="phone">Telefone com DDD *</label>
                        <input type="tel" id="phone" name="phone" placeholder="(00) 00000-0000" required>
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" placeholder="seu@email.com">
                    </div>

                    <button type="submit" class="btn btn-hero1 btn-full" id="submitBtn">
                        <i class="fas fa-calculator"></i>
                        <span id="btnText">Simular Agora</span>
                        <i class="fas fa-spinner fa-spin" id="btnSpinner" style="display: none;"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/script.js"></script>
    
    <script>
        // Função para enviar dados do lead
        async function submitLeadForm(event) {
            event.preventDefault();
            
            const form = event.target;
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            
            // Desabilitar botão e mostrar loading
            submitBtn.disabled = true;
            btnText.textContent = 'Enviando...';
            btnSpinner.style.display = 'inline-block';
            
            try {
                // Obter dados do formulário
                const formData = new FormData(form);
                const data = {
                    name: formData.get('name'),
                    phone: formData.get('phone'),
                    email: formData.get('email'),
                    vehicle: formData.get('vehicle'),
                    hasDownPayment: formData.get('hasDownPayment'),
                    downPayment: formData.get('downPayment'),
                    source: 'premium'
                };
                
                // Enviar para API
                const response = await fetch('subsystem/api/capture_lead.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Fechar modal
                    closeSimulationModal();
                    
                    // Mostrar mensagem de sucesso
                    alert('✅ Dados enviados com sucesso! Redirecionando para WhatsApp...');
                    
                    // Redirecionar para WhatsApp
                    if (result.redirect_whatsapp) {
                        window.open(result.redirect_whatsapp, '_blank');
                    }
                    
                    // Limpar formulário
                    form.reset();
                    
                } else {
                    alert('❌ Erro: ' + result.message);
                }
                
            } catch (error) {
                console.error('Erro ao enviar lead:', error);
                alert('❌ Erro ao enviar dados. Tente novamente.');
            } finally {
                // Reabilitar botão
                submitBtn.disabled = false;
                btnText.textContent = 'Simular Agora';
                btnSpinner.style.display = 'none';
            }
        }
    </script>
</body>
</html>