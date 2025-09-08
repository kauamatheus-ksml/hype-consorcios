<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hype Consórcios - Você tem sonhos nós temos a chave</title>
    <meta name="description" content="Realize o sonho do carro novo com o Consórcio Volkswagen. Parceiro autorizado com as melhores condições e 40+ anos de tradição. Simule agora!">
    
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
                        <div class="company-name">Hype Consórcios</div>
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
                <a href="#inicio">Início</a>
                <a href="#carros">Veículos</a>
                <a href="#consorcio">Consórcio</a>
                <a href="#simulacao">Simulação</a>
                <a href="#contato">Contato</a>
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
    <section id="inicio" class="hero-section">
        <div class="hero-background">
            <img src="assets/images/hero-cars.jpg" alt="Carros Volkswagen">
            <div class="hero-overlay"></div>
        </div>
        
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <img src="assets/images/vw-logo.png" alt="Volkswagen" style="height: 24px; margin-right: 8px;">
                    <span>Volkswagen</span>
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

                <!-- Features -->
                <div class="hero-features">
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>40+ anos de tradição</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-award"></i>
                        <span>Parceiro autorizado VW</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-car"></i>
                        <span>Toda linha Volkswagen</span>
                    </div>
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
                        'name' => 'Leves',
                        'model' => 'Conquiste seu veículo novo em até 10 anos de uso',
                        'description' => 'De qualquer marca e modelo da sua preferência. Aqui, seu sonho sai do papel.',
                        'image' => 'assets/images/polo-blue.jpg',
                        'features' => ['Entrada sem juros', 'Aceita carro usado como lance', 'Lance embutido até 25% da própria carta'],
                        'link' => 'leves.php'
                    ],
                    [
                        'name' => 'Prêmio',
                        'model' => 'Faça a aquisição do seu carro prêmio de forma inteligente sem descapitalizar',
                        'description' => 'O prêmio que você merece a passos de você.',
                        'image' => 'assets/images/mercedes.jpg',
                        'features' => ['Planos personalizados', 'Lance troca de chaves', 'Contemplação programada'],
                        'link' => 'premio.php'
                    ],
                    [
                        'name' => 'Pesados',
                        'model' => 'Adquira seu caminhão/carreta novo ou renove sua frota',
                        'description' => 'Com o consórcio você acelera muito mais. Descubra como carta de crédito para pesados pode alavancar seu negócio.',
                        'image' => 'assets/images/caminhao.jpg',
                        'features' => ['Pesados até 8 anos de uso', 'Aceita seu caminhão usado na troca', 'Lance de 25% embutido da própria carta'],
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
                        <h3 class="car-name">Consórcio <?= $car['name'] ?></h3>
                        <h4 class="car-model"><?= $car['model'] ?></h4>
                        <p class="car-description"><?= $car['description'] ?></p>
                        <ul class="car-features">
                            <?php foreach($car['features'] as $feature): ?>
                                <li><i class="fas fa-check"></i> <?= $feature ?></li>
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
                    <span>Parceiro da Embracon - Mais de 700 mil carros entregues</span>
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
                        'icon' => 'fas fa-users',
                        'number' => '700 mil+',
                        'label' => 'Carros Entregues pela Embracon',
                        'description' => 'Mais de 700 mil famílias realizaram o sonho através da Embracon'
                    ],
                    [
                        'icon' => 'fas fa-calendar',
                        'number' => '40+ anos',
                        'label' => 'No Mercado',
                        'description' => 'Mais de quatro décadas de experiência e confiança'
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

            <div class="about-cta">
                <div class="cta-badge">
                    <i class="fas fa-award"></i>
                    <span>Conheça nossa história →</span>
                </div>
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
                <div class="faq-buttons">
                    <a href="https://api.whatsapp.com/send/?phone=5547996862997" target="_blank" class="btn btn-success">
                        <i class="fab fa-whatsapp"></i>
                        Falar via WhatsApp
                    </a>
                    <a href="https://instagram.com/hype.consorcios" target="_blank" class="btn btn-outline">
                        <i class="fab fa-instagram"></i>
                        Seguir no Instagram
                    </a>
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
                        <span class="badge">CNPJ: 00.000.000/0001-00</span>
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
                
                <form class="simulation-form" id="simulationForm">
                    <div class="form-group">
                        <label for="name">Nome completo *</label>
                        <input type="text" id="name" name="name" placeholder="Seu nome completo" required>
                    </div>

                    <div class="form-group">
                        <label for="vehicle">Qual carro é do seu interesse? *</label>
                        <select id="vehicle" name="vehicle" required>
                            <option value="">Selecione o veículo</option>
                            <option value="Polo">Volkswagen Polo</option>
                            <option value="Nivus">Volkswagen Nivus</option>
                            <option value="Virtus">Volkswagen Virtus</option>
                            <option value="T-Cross">Volkswagen T-Cross</option>
                            <option value="Jetta">Volkswagen Jetta</option>
                            <option value="Tiguan">Volkswagen Tiguan</option>
                            <option value="Amarok">Volkswagen Amarok</option>
                        </select>
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

                    <button type="submit" class="btn btn-hero btn-full">
                        <i class="fas fa-calculator"></i>
                        Simular Agora
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/script.js"></script>
</body>
</html>