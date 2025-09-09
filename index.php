<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hype Consórcios - Você tem sonhos nós temos a chave</title>
    <meta name="description" content="Realize o sonho do carro novo com o Consórcio Volkswagen. Parceiro autorizado com as melhores condições e 40+ anos de tradição. Simule agora!">
    <link rel="icon" type="image/x-icon" href="assets/images/logo.ico">

    <!-- Open Graph -->
    <meta property="og:title" content="Hype Consórcios - Você tem sonhos nós temos a chave">
    <meta property="og:description" content="Realize o sonho do carro novo com o Consórcio Volkswagen. Parceiro autorizado com as melhores condições e 40+ anos de tradição.">
    <meta property="og:type" content="website">
    
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
                        
                    </div>
                </div>

                <!-- Desktop Navigation -->
                <nav class="nav-desktop">
                    <a href="#inicio">Início</a>
                    <a href="#carros">Veículos</a>
                    <a href="#consorcio">Consórcio</a>
                    <a href="#simulacao">Simulação</a>
                    <a href="#contato">Contato</a>
                </nav>

                

                <!-- Mobile Menu Button -->
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars" id="menuIcon"></i>
                </button>
            </div>

            <!-- Mobile Navigation -->
            <nav class="nav-mobile" id="mobileNav">
                <a href="#inicio">Início</a>
                <a href="#carros">Veículos</a>
                <a href="#consorcio">Consórcio</a>
                <a href="#simulacao">Simulação</a>
                <a href="#contato">Contato</a>
                
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="inicio" class="hero-section">
        <div class="hero-background">
            <img src="assets/images/hero-cars.jpg" alt="Carros Volkswagen">
            <div class="hero-overlay"></div>
        </div>
        
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <img src="assets/images/vw-logo.png" alt="Volkswagen" style="height: 24px; margin-right: 8px;">
                    <span>Parceiro Autorizado Volkswagen</span>
                </div>
                
                <h1 class="hero-title">
                    Você tem sonhos 
                    <span class="gradient-text">nós temos a chave</span>
                </h1>
                
                <p class="hero-subtitle">
                    Com parcelas que você nunca imaginou. Seu carro novo há clique de você.
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

        <!-- Scroll Indicator -->
        <div class="scroll-indicator">
            <div class="scroll-mouse">
                <div class="scroll-wheel"></div>
            </div>
        </div>
    </section>

    <!-- Cars Section -->
    <section id="carros" class="cars-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    Descubra nossa <span class="gradient-text">linha completa de crédito veicular</span>
                </h2>
            </div>

            <div class="cars-grid">
                <?php
                $cars = [
                    [
                        'name' => 'Veículos Leves',
                        'price' => 'Parcelas a partir de 811,25',
                        'description' => 'Realize o sonho do seu carro novo ou seminovo (até 10 anos de uso), da marca e modelo que você escolher. Aqui, seu plano cabe no bolso e seu sonho sai do papel!',
                        'image' => 'assets/images/polo-blue.jpg',
                        'features' => ['Sem entrada', 'Sem juros', 'Usar seu carro usado como lance', 'Lance embutido de até 25% da própria carta'],
                        'link' => 'leves.php'
                    ],
                    [
                        'name' => 'Veículos Premium',
                        'price' => 'Parcelas a partir de 1.960,00',
                        'description' => 'Adquira seu carro premium de forma inteligente, sem comprometer seu patrimônio. O veículo dos seus sonhos está mais próximo do que você imagina!',
                        'image' => 'assets/images/mercedes.jpg',
                        'features' => ['Planos personalizados', 'Opção de lance "troca de chaves"', 'Contemplação programada'],
                        'link' => 'premio.php'
                    ],
                    [
                        'name' => 'Veículos Pesados',
                        'price' => 'Parcelas a partir de 2.530,00',
                        'description' => 'Invista no crescimento do seu negócio com a aquisição de caminhões e carretas novos ou seminovos. Com a carta de crédito para pesados, sua frota ganha mais força para acelerar resultados.',
                        'image' => 'assets/images/caminhao.jpg',
                        'features' => ['Veículos pesados com até 8 anos de uso', 'Aceitamos seu caminhão usado como parte de pagamento', 'Lance embutido de até 25% da própria carta'],
                        'link' => 'pesados.php'
                    ]
                ];

                foreach($cars as $index => $car):
                ?>
                <div class="car-card" style="animation-delay: <?= $index * 0.1 ?>s">
                    <div class="car-image">
                        <img src="<?= $car['image'] ?>" alt="<?= $car['name'] ?>">
                    </div>
                    <div class="car-content">
                        <h3 class="car-name">Consórcio para <?= $car['name'] ?></h3>
                        <div class="car-price"><?= $car['price'] ?></div>
                        <p class="car-description"><?= $car['description'] ?></p>
                        <ul class="car-features">
                            <?php foreach($car['features'] as $feature): ?>
                                <li>• <?= $feature ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?= $car['link'] ?>" class="btn btn-primary">
                            Saiba Mais
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cars-footer">
                <p class="disclaimer">*Consulte condições</p>
                <div class="stats-badge">
                    <i class="fas fa-check"></i>
                    <span>Parceiro da Embracon</span>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="consorcio" class="about-section">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2 class="section-title">
                        Por que escolher a 
                        <span class="gradient-text">Hype Consórcios?</span>
                    </h2>
                    <p class="section-subtitle">
                        Nossa empresa tem uma assessoria exclusiva em contemplação desde o ato do contato até a entrega do veículo, 
                        representamos uma marca forte nacionalmente, sócio Volkswagen com a EMBRACON.
                    </p>
                    <p class="section-text">
                        Especialista em entrega de veículos com mais de 700 mil bens entregues desde 1960. 
                        A Hyper se destaca pelo ótimo desempenho no atendimento ágil e personalizado, 
                        focando na necessidade de cada cliente. Nosso propósito não é apenas de comercializar consórcio, 
                        e sim de realizar sonho.
                    </p>
                </div>
            </div>

            <div class="features-grid">
                <?php
                $features = [
                    [
                        'icon' => 'fas fa-calendar',
                        'number' => 'Muitos sonhos realizados',
                        'label' => '',
                        'description' => 'Mais clientes, mais experiência e confiança'
                    ],
                    [
                        'icon' => 'fas fa-shield-alt',
                        'number' => '100%',
                        'label' => 'Seguro',
                        'description' => 'Parceiro autorizado com total segurança jurídica'
                    ],
                    [
                        'icon' => 'fas fa-star',
                        'number' => '#1',
                        'label' => 'Melhor Escolha',
                        'description' => 'Referência em consórcios automotivos no Brasil'
                    ]
                ];

                foreach($features as $feature):
                ?>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="<?= $feature['icon'] ?>"></i>
                    </div>
                    <div class="feature-number"><?= $feature['number'] ?></div>
                    <div class="feature-label"><?= $feature['label'] ?></div>
                    <p class="feature-description"><?= $feature['description'] ?></p>
                </div>
                <?php endforeach; ?>
            </div>

            
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Dúvidas Frequentes</h2>
                <p class="section-subtitle">
                    Esclarecemos as principais dúvidas sobre consórcio
                </p>
            </div>

            <div class="faq-container">
                <?php
                $faqs = [
                    [
                        'question' => 'Como funciona o consórcio de veículos?',
                        'answer' => 'O consórcio é um sistema de autofinanciamento onde um grupo de pessoas se une para adquirir bens. Mensalmente, cada participante paga uma parcela e alguns são contemplados por sorteio ou lance.'
                    ],
                    [
                        'question' => 'Quais são as vantagens do consórcio?',
                        'answer' => 'As principais vantagens são: sem juros, parcelas menores, sem consulta ao SPC/Serasa, possibilidade de usar FGTS, e você pode ser contemplado a qualquer momento.'
                    ],
                    [
                        'question' => 'Posso usar o FGTS para pagamento?',
                        'answer' => 'Sim! Você pode usar o FGTS tanto para dar lance quanto para amortizar parcelas do seu consórcio, seguindo as regras da Caixa Econômica Federal.'
                    ],
                    [
                        'question' => 'Como funciona a contemplação?',
                        'answer' => 'A contemplação pode acontecer por sorteio mensal (gratuito) ou por lance (oferta de valor). Quanto maior o lance, maiores as chances de contemplação.'
                    ]
                ];

                foreach($faqs as $index => $faq):
                ?>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(<?= $index ?>)">
                        <span><?= $faq['question'] ?></span>
                        <i class="fas fa-chevron-down faq-icon" id="faq-icon-<?= $index ?>"></i>
                    </button>
                    <div class="faq-answer" id="faq-answer-<?= $index ?>">
                        <p><?= $faq['answer'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="faq-cta">
                <h3>Ainda tem dúvidas?</h3>
                <p>Nossa equipe está pronta para te ajudar!</p>
                <div class="hero-buttons-dwn">
                    <button class="btn btn-hero" onclick="openSimulationModal()">
                        Simular agora
                    </button>
                    
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contato" class="footer">
        <div class="container">
            <div class="footer-content">
                <!-- Company Info -->
                <div class="footer-section">
                    <div class="footer-logo">
                        <div class="logo-text">
                            <div class="company-name gradient-text">Hype</div>
                            <div class="subtitle">
                                Consórcios<br>
                                <span>Parceiro Autorizado VW</span>
                            </div>
                        </div>
                    </div>
                    <p class="footer-description">
                        Hype Consórcios E Investimentos Ltda é parceiro autorizado do Consórcio Volkswagen, 
                        oferecendo as melhores condições para você realizar o sonho do carro novo.
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
                        <li><a href="#inicio">Início</a></li>
                        <li><a href="#carros">Veículos</a></li>
                        <li><a href="#consorcio">Sobre o Consórcio</a></li>
                        <li><a href="#simulacao">Simulação</a></li>
                    </ul>
                </div>

                <!-- Vehicles -->
                <div class="footer-section">
                    <h3 class="footer-title">Veículos Disponíveis</h3>
                    <ul class="footer-links">
                        <li>Volkswagen Polo</li>
                        <li>Volkswagen Nivus</li>
                        <li>Volkswagen Virtus</li>
                        <li>Volkswagen T-Cross</li>
                        <li>Volkswagen Jetta</li>
                        <li>Volkswagen Tiguan</li>
                        <li>Volkswagen Amarok</li>
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
                            <span>
                                Atendimento em todo o Brasil<br>
                                Parceiro Autorizado VW
                            </span>
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
                    <div class="footer-badges">
                        <span class="badge">Parceiro Autorizado VW</span>
                        <span class="badge">CNPJ: 53.170.406/0001-89</span>
                    </div>
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
                <h2>Simule seu Consórcio</h2>
                <button class="modal-close" onclick="closeSimulationModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="modal-icon">
                    <i class="fas fa-calculator"></i>
                </div>
                <h3>Preencha os dados e receba uma simulação personalizada</h3>
                
                <form class="simulation-form" id="simulationForm" onsubmit="submitLeadForm(event)">
                    <div class="form-group">
                        <label for="name">Nome completo *</label>
                        <input type="text" id="name" name="name" placeholder="Seu nome completo" required>
                    </div>

                    <div class="form-group">
                        <label for="vehicle">Qual carro é do seu interesse? *</label>
                        <input type="text" id="vehicle" name="vehicle" placeholder="Ex: Volkswagen Polo, Honda Civic, Toyota Corolla..." required>
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
                    source: 'index'
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
        
        // Máscara para telefone
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length >= 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 7) {
                value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            }
            
            e.target.value = value;
        });
        
        // Máscara para valor da entrada
        document.getElementById('downPayment').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (value / 100).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
            e.target.value = value;
        });
    </script>
</body>
</html>