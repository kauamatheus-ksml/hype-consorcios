<?php
// Carregar configurações do site
require_once 'includes/site-config-functions.php';

// Carregar todas as configurações
$configs = getAllSiteConfigs();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapeConfig(getSiteConfig('site_title', 'Hype Consórcios - Você tem sonhos nós temos a chave')) ?></title>
    <meta name="description" content="<?= escapeConfig(getSiteConfig('site_description', 'Realize o sonho do carro novo com o Consórcio Volkswagen. Parceiro autorizado com as melhores condições. Simule agora!')) ?>">
    <meta name="keywords" content="<?= escapeConfig(getSiteConfig('site_keywords', 'consórcio de veículos, consórcio volkswagen, consórcio carros, consórcio sem juros, embracon, carta contemplada, consórcio leves premium pesados')) ?>">
    <link rel="canonical" href="https://hypeconsorcios.com.br/">
    <link rel="icon" type="image/x-icon" href="assets/images/logo.ico">

    <!-- SEO Local Jaraguá do Sul -->
    <meta name="geo.region" content="BR-SC">
    <meta name="geo.placename" content="Jaraguá do Sul">
    <meta name="geo.position" content="-26.4819;-49.0737">
    <meta name="ICBM" content="-26.4819, -49.0737">
    <meta name="author" content="Hype Consórcios E Investimentos Ltda">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">

    <!-- Open Graph - SEO Social -->
    <meta property="og:title" content="<?= escapeConfig(getSiteConfig('site_title', 'Hype Consórcios - Você tem sonhos nós temos a chave')) ?>">
    <meta property="og:description" content="<?= escapeConfig(getSiteConfig('site_description', 'Realize o sonho do carro novo com o Consórcio Volkswagen. Parceiro autorizado com as melhores condições e 40+ anos de tradição.')) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://hypeconsorcios.com.br/">
    <meta property="og:image" content="<?= escapeConfig(getSiteConfig('og_image', 'https://hypeconsorcios.com.br/assets/images/consorcio-jaragua-do-sul-og.jpg')) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Consórcio de Veículos Jaraguá do Sul - Hype Consórcios">
    <meta property="og:site_name" content="<?= escapeConfig(getSiteConfig('company_name', 'Hype Consórcios')) ?>">
    <meta property="og:locale" content="pt_BR">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= escapeConfig(getSiteConfig('site_title', 'Hype Consórcios - Você tem sonhos nós temos a chave')) ?>">
    <meta name="twitter:description" content="<?= escapeConfig(getSiteConfig('site_description', 'Realize o sonho do carro novo com o Consórcio Volkswagen. Parceiro autorizado com as melhores condições e 40+ anos de tradição.')) ?>">
    <meta name="twitter:image" content="<?= escapeConfig(getSiteConfig('og_image', 'https://hypeconsorcios.com.br/assets/images/consorcio-jaragua-do-sul-og.jpg')) ?>">

    <!-- Schema.org LocalBusiness - Dados Estruturados -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "@id": "https://hypeconsorcios.com.br/#organization",
        "name": "Hype Consórcios E Investimentos Ltda",
        "alternateName": "Hype Consórcios",
        "description": "Hype Consórcios - Parceiro autorizado Volkswagen e Embracon. Sem juros, sem entrada. Carros leves, premium e pesados. Realizando sonhos há mais de 15 anos.",
        "url": "https://hypeconsorcios.com.br/",
        "telephone": "+55 47 99686-2997",
        "priceRange": "$$",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "Jaraguá do Sul",
            "addressRegion": "SC",
            "addressCountry": "BR"
        },
        "geo": {
            "@type": "GeoCoordinates",
            "latitude": -26.4819,
            "longitude": -49.0737
        },
        "areaServed": [
            {
                "@type": "City",
                "name": "Jaraguá do Sul",
                "sameAs": "https://pt.wikipedia.org/wiki/Jaragua_do_Sul"
            },
            {
                "@type": "State", 
                "name": "Santa Catarina"
            }
        ],
        "serviceArea": {
            "@type": "GeoCircle",
            "geoMidpoint": {
                "@type": "GeoCoordinates",
                "latitude": -26.4819,
                "longitude": -49.0737
            },
            "geoRadius": "50000"
        },
        "hasOfferCatalog": {
            "@type": "OfferCatalog",
            "name": "Consórcio de Veículos",
            "itemListElement": [
                {
                    "@type": "Offer",
                    "itemOffered": {
                        "@type": "Service",
                        "name": "Consórcio Veículos Leves",
                        "description": "Consórcio para carros novos e seminovos até 10 anos"
                    }
                },
                {
                    "@type": "Offer",
                    "itemOffered": {
                        "@type": "Service",
                        "name": "Consórcio Veículos Premium",
                        "description": "Consórcio para carros de luxo e importados"
                    }
                },
                {
                    "@type": "Offer",
                    "itemOffered": {
                        "@type": "Service",
                        "name": "Consórcio Veículos Pesados",
                        "description": "Consórcio para caminhões e carretas"
                    }
                }
            ]
        },
        "sameAs": [
            "https://instagram.com/hype.consorcios"
        ],
        "logo": "https://hypeconsorcios.com.br/assets/images/logo.png",
        "image": "https://hypeconsorcios.com.br/assets/images/consorcio-jaragua-do-sul-og.jpg",
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "5.0",
            "reviewCount": "47",
            "bestRating": "5",
            "worstRating": "1"
        }
    }
    </script>

    <!-- FAQ Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
            <?php
            try {
                require_once 'subsystem/config/database.php';
                $database = new Database();
                $conn = $database->getConnection();

                if ($conn) {
                    $stmt = $conn->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY display_order, id LIMIT 10");
                    $schemaFaqs = $stmt->fetchAll();
                } else {
                    $schemaFaqs = [];
                }
            } catch (Exception $e) {
                $schemaFaqs = [
                    [
                        'question' => 'Como funciona o consórcio de veículos?',
                        'answer' => 'O consórcio é um sistema de autofinanciamento onde um grupo de pessoas se une para adquirir bens. Mensalmente, cada participante paga uma parcela e alguns são contemplados por sorteio ou lance.'
                    ]
                ];
            }

            if (!empty($schemaFaqs)) {
                $schemaItems = [];
                foreach ($schemaFaqs as $faq) {
                    $schemaItems[] = [
                        "@type" => "Question",
                        "name" => $faq['question'],
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => $faq['answer']
                        ]
                    ];
                }
                echo json_encode($schemaItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                echo '[]';
            }
            ?>
        ]
    }
    </script>

    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '752388217802160');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=752388217802160&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->

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
        <div class="hero-video-container">
            <?php
            $heroVideo = getSiteConfig('hero_video', 'assets/videos/test-drive-hero.mp4');
            $videoExtension = pathinfo($heroVideo, PATHINFO_EXTENSION);
            ?>
            <video class="hero-video" autoplay muted loop playsinline>
                <source src="<?= escapeConfig($heroVideo) ?>" type="video/<?= $videoExtension === 'webm' ? 'webm' : 'mp4' ?>">
                <?php if ($videoExtension !== 'webm'): ?>
                <source src="<?= escapeConfig(str_replace('.mp4', '.webm', $heroVideo)) ?>" type="video/webm">
                <?php endif; ?>
            </video>
            <div class="hero-overlay"></div>

            <!-- Hero Logo Floating -->
            <div class="hero-logo-float">
                <?php
                $heroLogo = getConfigImageUrl('hero_logo', 'assets/images/logo.png');
                ?>
                <img src="<?= escapeConfig($heroLogo) ?>" alt="Hype Consórcios" class="hero-logo">
            </div>
        </div>
        
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <img src="assets/images/vw-logo.png" alt="Volkswagen" style="height: 24px; margin-right: 8px;">
                    <span>Parceiro Autorizado Volkswagen</span>
                </div>
                
                <h1 class="hero-title">
                    <?= escapeConfig(getSiteConfig('hero_title_main', 'Você tem sonhos')) ?>
                    <span class="gradient-text"><?= escapeConfig(getSiteConfig('hero_title_highlight', 'nós temos a chave')) ?></span>
                </h1>

                <p class="hero-subtitle">
                    <?= escapeConfig(getSiteConfig('hero_subtitle', 'Com parcelas que você nunca imaginou. Seu carro novo há clique de você.')) ?>
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
                    <?= escapeConfig(getSiteConfig('cars_title', 'Descubra nossa')) ?> <span class="gradient-text"><?= escapeConfig(getSiteConfig('cars_title_highlight', 'linha completa de crédito veicular')) ?></span>
                </h2>
            </div>

            <div class="cars-grid">
                <?php
                $cars = [
                    [
                        'name' => 'Veículos Leves',
                        'price' => getSiteConfig('leves_price', 'Parcelas a partir de 811,25'),
                        'description' => getSiteConfig('leves_description', 'Realize o sonho do seu carro novo ou seminovo (até 10 anos de uso), da marca e modelo que você escolher. Aqui, seu plano cabe no bolso e seu sonho sai do papel!'),
                        'image' => getConfigImageUrl('leves_image', 'assets/images/polo-blue.jpg'),
                        'features' => ['Sem entrada', 'Sem juros', 'Usar seu carro usado como lance', 'Lance embutido de até 25% da própria carta'],
                        'link' => 'leves.php'
                    ],
                    [
                        'name' => 'Veículos Premium',
                        'price' => getSiteConfig('premium_price', 'Parcelas a partir de 1.480,00'),
                        'description' => getSiteConfig('premium_description', 'Adquira seu carro premium de forma inteligente, sem comprometer seu patrimônio. O veículo dos seus sonhos está mais próximo do que você imagina!'),
                        'image' => getConfigImageUrl('premium_image', 'assets/images/mercedes.jpg'),
                        'features' => ['Planos personalizados', 'Opção de lance "troca de chaves"', 'Contemplação programada'],
                        'link' => 'premio.php'
                    ],
                    [
                        'name' => 'Veículos Pesados',
                        'price' => getSiteConfig('pesados_price', 'Parcelas a partir de 2.530,00'),
                        'description' => getSiteConfig('pesados_description', 'Invista no crescimento do seu negócio com a aquisição de caminhões e carretas novos ou seminovos. Com a carta de crédito para pesados, sua frota ganha mais força para acelerar resultados.'),
                        'image' => getConfigImageUrl('pesados_image', 'assets/images/caminhao.jpg'),
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
                        
                        <a href="<?= $car['link'] ?>" class="btn btn-primary">
                            Saiba Mais.
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
                        <?= escapeConfig(getSiteConfig('about_title', 'Por que escolher a')) ?>
                        <span class="gradient-text"><?= escapeConfig(getSiteConfig('about_title_highlight', 'Hype Consórcios?')) ?></span>
                    </h2>
                    <p class="section-subtitle">
                        <?= escapeConfig(getSiteConfig('about_subtitle', 'Na Hype Consórcios, oferecemos uma assessoria exclusiva em todo o processo de contemplação — desde a assinatura do contrato até a entrega do seu veículo.')) ?>
                    </p>
                    <p class="section-text">
                        <?= escapeConfig(getSiteConfig('about_text1', 'Somos representantes de uma marca consolidada nacionalmente: o Consórcio Volkswagen, administrado pela Embracon, especialista em consórcios e responsável pela entrega de mais de 700 mil bens desde 1960.')) ?>
                    </p>
                    <p class="section-text">
                        <?= escapeConfig(getSiteConfig('about_text2', 'Nosso diferencial está no atendimento ágil, transparente e personalizado, sempre focado nas necessidades de cada cliente. Afinal, nosso propósito vai muito além de comercializar consórcios: queremos realizar o seu sonho.')) ?>
                    </p>
                </div>
            </div>

            

            
        </div>
    </section>

    <!-- Clientes Contemplados Section -->
    <section class="clientes-contemplados-section">
        <div class="container">
            <div class="contemplados-header">
                <h2 class="section-title">
                    <?= escapeConfig(getSiteConfig('clients_title', 'Clientes')) ?> <span class="gradient-text"><?= escapeConfig(getSiteConfig('clients_title_highlight', 'Contemplados')) ?></span>
                </h2>
                <p class="section-subtitle">
                    <?= escapeConfig(getSiteConfig('clients_subtitle', 'Veja alguns dos nossos clientes que realizaram o sonho do carro novo! 🚗✨')) ?>
                </p>
            </div>

            <div class="contemplados-grid-container">
                <div class="contemplados-track" id="contempladosTrack">
                    <?php
                    // Gerar imagens dos clientes dinamicamente usando configurações
                    for ($i = 1; $i <= 10; $i++) {
                        $imageKey = "client_image_$i";
                        $imagePath = getConfigImageUrl($imageKey, "assets/images/clientes/cliente-$i.jpg");
                        ?>
                        <div class="cliente-item">
                            <img src="<?= escapeConfig($imagePath) ?>" alt="Cliente contemplado <?= $i ?>" class="cliente-image">
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <!-- Navigation Controls -->
                <div class="grid-controls">
                    <button class="grid-btn prev" id="gridPrevBtn">❮</button>
                    <button class="grid-btn next" id="gridNextBtn">❯</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Career Section -->
    <section class="career-section">
        <div class="container">
            <div class="career-content">
                <div class="career-header">
                    <h2 class="section-title">
                        Trabalhe com a <span class="gradient-text">Hype Consórcios</span>
                    </h2>
                    <p class="section-subtitle">
                        A Hype Consórcios está em constante crescimento e buscamos profissionais que queiram crescer junto com a gente! 🚀
                    </p>
                </div>

                <div class="career-grid">
                    <div class="career-image">
                        <img src="<?= escapeConfig(getConfigImageUrl('career_image', 'assets/images/contarte.png')) ?>" alt="Trabalhe conosco">
                    </div>
                    <div class="career-description">
                        <p>Se você é comunicativo, tem espírito empreendedor e gosta de ajudar pessoas a realizarem sonhos, venha fazer parte do nosso time de consultores de consórcio.</p>
                        
                        <div class="career-benefits">
                            <h3>O que oferecemos:</h3>
                            <ul>
                                <li>• Treinamento completo e suporte constante.</li>
                                <li>• Comissões atrativas e possibilidade de altos ganhos.</li>
                                <li>• Reconhecimento e plano de crescimento na empresa.</li>
                            </ul>
                        </div>

                        <div class="career-requirements">
                            <h3>O que buscamos em você:</h3>
                            <ul>
                                <li>• Vontade de aprender e se desenvolver.</li>
                                <li>• Perfil comercial e boa comunicação.</li>
                                <li>• Determinação e foco em resultados.</li>
                                <li>• Experiência em vendas (desejável, mas não obrigatória).</li>
                            </ul>
                        </div>

                        <div class="career-cta">
                            <p class="career-highlight">👉 Faça parte da equipe que está transformando sonhos em realidade.</p>
                            <a href="https://api.whatsapp.com/send/?phone=5547996862997&text=Oi!%20Quero%20enviar%20meu%20curr%C3%ADculo%20para%20trabalhar%20na%20Hype%20Cons%C3%B3rcios!" target="_blank" class="btn btn-primary btn-career">
                                <i class="fab fa-whatsapp"></i>
                                Envie seu Currículo
                            </a>
                            <p class="career-note">Envie sua mensagem e depois mande seu currículo!</p>
                        </div>
                    </div>
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
                try {
                    require_once 'subsystem/config/database.php';
                    $database = new Database();
                    $conn = $database->getConnection();

                    if ($conn) {
                        $stmt = $conn->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY display_order, id");
                        $faqs = $stmt->fetchAll();
                    } else {
                        $faqs = [];
                    }
                } catch (Exception $e) {
                    // Fallback para FAQs estáticas em caso de erro
                    $faqs = [
                        [
                            'question' => 'Como funciona o consórcio de veículos?',
                            'answer' => 'O consórcio é um sistema de autofinanciamento onde um grupo de pessoas se une para adquirir bens. Mensalmente, cada participante paga uma parcela e alguns são contemplados por sorteio ou lance.'
                        ],
                        [
                            'question' => 'Quais são as vantagens do consórcio?',
                            'answer' => 'As principais vantagens são: sem juros, parcelas menores, sem consulta ao SPC/Serasa, possibilidade de usar FGTS, e você pode ser contemplado a qualquer momento.'
                        ]
                    ];
                }

                if (empty($faqs)): ?>
                    <div class="faq-item">
                        <div class="faq-answer" style="display: block; text-align: center; padding: 2rem;">
                            <p>Nenhuma pergunta frequente encontrada no momento.</p>
                        </div>
                    </div>
                <?php else:
                    foreach($faqs as $index => $faq): ?>
                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFAQ(<?= $index ?>)">
                            <span><?= htmlspecialchars($faq['question']) ?></span>
                            <i class="fas fa-chevron-down faq-icon" id="faq-icon-<?= $index ?>"></i>
                        </button>
                        <div class="faq-answer" id="faq-answer-<?= $index ?>">
                            <p><?= htmlspecialchars($faq['answer']) ?></p>
                        </div>
                    </div>
                    <?php endforeach;
                endif; ?>
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

    <!-- Localização Section -->
    <section class="location-section">
        <div class="container">
            <div class="location-header">
                <h2 class="section-title">
                    Nossa <span class="gradient-text">Localização</span>
                </h2>
                <p class="section-subtitle">
                    Visite nossa sede em Jaraguá do Sul e conheça nossos especialistas pessoalmente! 📍
                </p>
            </div>

            <div class="location-content">
                <div class="location-info-card">
                    <div class="info-header">
                        <div class="info-icon">
                            
                        </div>
                        <h3 class="info-title">Hype Consórcios</h3>
                        <p class="info-subtitle">Seu parceiro em realizações</p>
                    </div>

                    <div class="address-details">
                        <div class="address-item">
                            <i class="fas fa-map-pin"></i>
                            <div class="address-text">
                                <strong>Endereço:</strong><br>
                                Rua José Narloch, 1953<br>
                                Bairro Tifa Martins<br>
                                Jaraguá do Sul - SC
                            </div>
                        </div>

                        <div class="address-item">
                            <i class="fas fa-clock"></i>
                            <div class="address-text">
                                <strong>Horário de Atendimento:</strong><br>
                                Segunda a Sexta: 8h às 18h<br>
                                Sábado: 8h às 12h
                            </div>
                        </div>

                        
                    </div>

                    <div class="location-actions">
                        <a href="https://maps.google.com/?q=Rua+José+Narloch,+1953,+Tifa+Martins,+Jaraguá+do+Sul,+SC" 
                           target="_blank" 
                           class="btn btn-outline-primary">
                            <i class="fas fa-directions"></i>
                            Como Chegar
                        </a>
                        
                    </div>
                </div>

                <div class="google-maps-container">
                    <div class="map-overlay">
                        <div class="map-placeholder" id="mapPlaceholder">
                            <i class="fas fa-map-marked-alt"></i>
                            <p>Clique para carregar o mapa</p>
                        </div>
                    </div>
                    <iframe 
                        id="googleMap"
                        class="google-map"
                        src="" 
                        style="display: none; border: 0; opacity: 0; transition: opacity 0.3s ease;"
                        width="100%" 
                        height="400" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
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
                        <div class="logo">
                            <img src="assets/images/logo.png" alt="Hype Consórcios Logo">
                            <div class="logo-text">
                                
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
                        <li><a onclick="openSimulationModal()" >Simulação</a></li>
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
                        <a href="/login" class="badge badge-link" title="Acesso ao Sistema">
                            <i class="fas fa-cog"></i> Sistema
                        </a>
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
        function getLeadCaptureEndpoint() {
            return window.HYPE_CRM_CAPTURE_ENDPOINT || 'subsystem/api/capture_lead.php';
        }

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
                const response = await fetch(getLeadCaptureEndpoint(), {
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
