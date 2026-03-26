<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consórcio para Veículos Pesados - Hype Consórcios</title>
    <meta name="description" content="Renove sua frota com parcelas a partir de R$ 2.530,00. Caminhões e carretas até 8 anos de uso.">
    
    <!-- Styles -->

    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/logo.ico">
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
                    
                </div>

                <!-- Desktop Navigation -->
                <nav class="nav-desktop">
                    <a href="index.php">Início</a>
                    <a href="index.php#carros">Veículos</a>
                    <a href="index.php#consorcio">Consórcio</a>
                    <a href="index.php#simulacao">Simulação</a>
                    <a href="index.php#contato">Contato</a>
                </nav>

                

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
                
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-background">
            <img src="assets/images/caminhao.jpg" alt="Consórcio Veículos Pesados">
            <div class="hero-overlay"></div>
        </div>
        
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-truck"></i>
                    <span>Veículos Pesados</span>
                </div>
                
                <h1 class="hero-title">
                    🔹 Consórcio para 
                    <span class="gradient-text">Veículos Pesados</span>
                </h1>
                
                <p class="hero-subtitle">
                    Parcelas a partir de <strong>R$ 2.530,00</strong> - Para fazer seu negócio crescer
                </p>

                <div class="hero-buttons">
                    <button class="btn btn-hero" onclick="openSimulationModal()">
                        Simular agora
                    </button>
                    
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
                        Acelere o crescimento <span class="gradient-text">do seu negócio</span>
                    </h2>
                    
                    <div class="content-block">
                        <p class="section-text">
                            Seja para renovar a frota ou investir em um novo caminhão, o consórcio de veículos pesados é a solução certa para quem deseja crescer com segurança e planejamento.
                        </p>
                        
                        <p class="section-text">
                            Aqui você encontra cartas de crédito para caminhões e carretas de até 8 anos de uso, com possibilidade de utilizar seu veículo atual como parte de pagamento. Também oferecemos lance embutido de até 25% da própria carta, tornando a contemplação ainda mais acessível.
                        </p>
                        
                        <div class="highlight-box">
                            <i class="fas fa-hand-point-right"></i>
                            <p><strong>Uma oportunidade para acelerar o crescimento do seu negócio sem comprometer o caixa.</strong></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="feature-label">Veículos até 8 anos de uso</div>
                    <p class="feature-description">Ampla gama de caminhões e carretas novos e seminovos para escolher.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="feature-label">Seu caminhão como parte do pagamento</div>
                    <p class="feature-description">Aceitamos seu veículo atual como parte de pagamento, facilitando a troca.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="feature-label">Lance embutido até 25%</div>
                    <p class="feature-description">Conte com lance embutido de até 25% da própria carta para acelerar a contemplação.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="feature-label">Crescimento sem risco</div>
                    <p class="feature-description">Expanda sua frota com planejamento financeiro seguro e previsível.</p>
                </div>
            </div>

            

            <!-- CTA Section -->
            <div class="about-cta">
                <h3>Pronto para expandir seu negócio?</h3>
                <p>Faça uma simulação personalizada e descubra como crescer com segurança!</p>
                <div class="hero-buttons">
                    
                    
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
                <h2>Simule seu Consórcio de Veículos Pesados</h2>
                <button class="modal-close" onclick="closeSimulationModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="modal-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3>Preencha os dados e receba uma simulação personalizada para veículos pesados</h3>
                
                <form class="simulation-form" id="simulationForm" onsubmit="submitLeadForm(event)">
                    <div class="form-group">
                        <label for="name">Nome completo *</label>
                        <input type="text" id="name" name="name" placeholder="Seu nome completo" required>
                    </div>

                    <div class="form-group">
                        <label for="vehicle">Qual veículo pesado é do seu interesse? *</label>
                        <input type="text" id="vehicle" name="vehicle" placeholder="Ex: Mercedes-Benz Atego, Volvo FH, Scania R-Series..." required>
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
                    source: 'pesados'
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