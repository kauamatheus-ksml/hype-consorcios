<?php
/**
 * Script para criar tabela de configurações do site
 * Hype Consórcios
 */

require_once 'config/database.php';

// Conectar ao banco
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Erro na conexão com o banco de dados.");
}

try {
    // Criar tabela de configurações do site
    $sql = "
    CREATE TABLE IF NOT EXISTS site_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        config_key VARCHAR(100) NOT NULL UNIQUE,
        config_value TEXT,
        config_type ENUM('text', 'textarea', 'image', 'number', 'boolean') DEFAULT 'text',
        section VARCHAR(50) NOT NULL,
        display_name VARCHAR(200) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        INDEX idx_section (section),
        INDEX idx_config_key (config_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $conn->exec($sql);
    echo "✅ Tabela 'site_config' criada com sucesso!\n\n";

    // Inserir configurações padrão baseadas no index.php atual
    $defaultConfigs = [
        // Hero Section
        ['hero_title_main', 'Você tem sonhos', 'text', 'hero', 'Título Principal do Hero', 'Primeira parte do título principal da página'],
        ['hero_title_highlight', 'nós temos a chave', 'text', 'hero', 'Título Destacado do Hero', 'Segunda parte do título (com gradiente)'],
        ['hero_subtitle', 'Com parcelas que você nunca imaginou. Seu carro novo há clique de você.', 'textarea', 'hero', 'Subtítulo do Hero', 'Texto descritivo abaixo do título'],
        ['hero_video', 'assets/videos/test-drive-hero.mp4', 'image', 'hero', 'Vídeo de Fundo', 'Upload do vídeo de fundo'],
        ['hero_logo', 'assets/images/logo.png', 'image', 'hero', 'Logo no Hero', 'Logo flutuante no hero'],

        // Meta Tags
        ['site_title', 'Hype Consórcios - Você tem sonhos nós temos a chave', 'text', 'meta', 'Título do Site', 'Título principal da página (meta title)'],
        ['site_description', 'Realize o sonho do carro novo com o Consórcio Volkswagen. Parceiro autorizado com as melhores condições. Simule agora!', 'textarea', 'meta', 'Descrição do Site', 'Descrição meta para SEO'],
        ['site_keywords', 'consórcio de veículos, consórcio volkswagen, consórcio carros, consórcio sem juros, embracon, carta contemplada, consórcio leves premium pesados', 'textarea', 'meta', 'Palavras-chave', 'Keywords para SEO'],
        ['og_image', 'https://hypeconsorcios.com.br/assets/images/consorcio-jaragua-do-sul-og.jpg', 'text', 'meta', 'Imagem Open Graph', 'Imagem para compartilhamento em redes sociais'],

        // Company Info
        ['company_name', 'Hype Consórcios E Investimentos Ltda', 'text', 'company', 'Nome da Empresa', 'Razão social da empresa'],
        ['company_phone', '(47) 99686-2997', 'text', 'company', 'Telefone', 'Telefone principal da empresa'],
        ['company_whatsapp', '5547996862997', 'text', 'company', 'WhatsApp', 'Número do WhatsApp (formato internacional)'],
        ['company_instagram', 'hype.consorcios', 'text', 'company', 'Instagram', 'Usuario do Instagram'],
        ['company_address', 'Rua José Narloch, 1953', 'text', 'company', 'Endereço', 'Endereço da empresa'],
        ['company_neighborhood', 'Bairro Tifa Martins', 'text', 'company', 'Bairro', 'Bairro da empresa'],
        ['company_city', 'Jaraguá do Sul', 'text', 'company', 'Cidade', 'Cidade da empresa'],
        ['company_state', 'SC', 'text', 'company', 'Estado', 'Estado da empresa'],
        ['company_cnpj', '53.170.406/0001-89', 'text', 'company', 'CNPJ', 'CNPJ da empresa'],

        // About Section
        ['about_title', 'Por que escolher a', 'text', 'about', 'Título da Seção Sobre', 'Primeira parte do título da seção'],
        ['about_title_highlight', 'Hype Consórcios?', 'text', 'about', 'Título Destacado Sobre', 'Segunda parte do título (com gradiente)'],
        ['about_subtitle', 'Na Hype Consórcios, oferecemos uma assessoria exclusiva em todo o processo de contemplação — desde a assinatura do contrato até a entrega do seu veículo.', 'textarea', 'about', 'Subtítulo Sobre', 'Primeiro parágrafo da seção sobre'],
        ['about_text1', 'Somos representantes de uma marca consolidada nacionalmente: o Consórcio Volkswagen, administrado pela Embracon, especialista em consórcios e responsável pela entrega de mais de 700 mil bens desde 1960.', 'textarea', 'about', 'Texto Sobre 1', 'Segundo parágrafo'],
        ['about_text2', 'Nosso diferencial está no atendimento ágil, transparente e personalizado, sempre focado nas necessidades de cada cliente. Afinal, nosso propósito vai muito além de comercializar consórcios: queremos realizar o seu sonho.', 'textarea', 'about', 'Texto Sobre 2', 'Terceiro parágrafo'],

        // Cars Section
        ['cars_title', 'Descubra nossa', 'text', 'cars', 'Título da Seção Veículos', 'Primeira parte do título'],
        ['cars_title_highlight', 'linha completa de crédito veicular', 'text', 'cars', 'Título Destacado Veículos', 'Segunda parte do título (com gradiente)'],

        // Veículos Leves
        ['leves_price', 'Parcelas a partir de 811,25', 'text', 'cars', 'Preço Veículos Leves', 'Preço dos veículos leves'],
        ['leves_description', 'Realize o sonho do seu carro novo ou seminovo (até 10 anos de uso), da marca e modelo que você escolher. Aqui, seu plano cabe no bolso e seu sonho sai do papel!', 'textarea', 'cars', 'Descrição Veículos Leves', 'Descrição dos veículos leves'],
        ['leves_image', 'assets/images/polo-blue.jpg', 'image', 'cars', 'Imagem Veículos Leves', 'Imagem dos veículos leves'],

        // Veículos Premium
        ['premium_price', 'Parcelas a partir de 1.480,00', 'text', 'cars', 'Preço Veículos Premium', 'Preço dos veículos premium'],
        ['premium_description', 'Adquira seu carro premium de forma inteligente, sem comprometer seu patrimônio. O veículo dos seus sonhos está mais próximo do que você imagina!', 'textarea', 'cars', 'Descrição Veículos Premium', 'Descrição dos veículos premium'],
        ['premium_image', 'assets/images/mercedes.jpg', 'image', 'cars', 'Imagem Veículos Premium', 'Imagem dos veículos premium'],

        // Veículos Pesados
        ['pesados_price', 'Parcelas a partir de 2.530,00', 'text', 'cars', 'Preço Veículos Pesados', 'Preço dos veículos pesados'],
        ['pesados_description', 'Invista no crescimento do seu negócio com a aquisição de caminhões e carretas novos ou seminovos. Com a carta de crédito para pesados, sua frota ganha mais força para acelerar resultados.', 'textarea', 'cars', 'Descrição Veículos Pesados', 'Descrição dos veículos pesados'],
        ['pesados_image', 'assets/images/caminhao.jpg', 'image', 'cars', 'Imagem Veículos Pesados', 'Imagem dos veículos pesados'],

        // FAQ
        ['faq_title', 'Dúvidas Frequentes', 'text', 'faq', 'Título FAQ', 'Título da seção de FAQ'],
        ['faq_subtitle', 'Esclarecemos as principais dúvidas sobre consórcio', 'text', 'faq', 'Subtítulo FAQ', 'Subtítulo da seção de FAQ'],

        // Location
        ['location_title', 'Nossa', 'text', 'location', 'Título Localização', 'Primeira parte do título'],
        ['location_title_highlight', 'Localização', 'text', 'location', 'Título Destacado Localização', 'Segunda parte do título (com gradiente)'],
        ['location_subtitle', 'Visite nossa sede em Jaraguá do Sul e conheça nossos especialistas pessoalmente! 📍', 'text', 'location', 'Subtítulo Localização', 'Subtítulo da seção'],

        // Clients
        ['clients_title', 'Clientes', 'text', 'clients', 'Título Clientes', 'Primeira parte do título'],
        ['clients_title_highlight', 'Contemplados', 'text', 'clients', 'Título Destacado Clientes', 'Segunda parte do título (com gradiente)'],
        ['clients_subtitle', 'Veja alguns dos nossos clientes que realizaram o sonho do carro novo! 🚗✨', 'text', 'clients', 'Subtítulo Clientes', 'Subtítulo da seção'],

        // Career
        ['career_title', 'Trabalhe com a', 'text', 'career', 'Título Carreira', 'Primeira parte do título'],
        ['career_title_highlight', 'Hype Consórcios', 'text', 'career', 'Título Destacado Carreira', 'Segunda parte do título (com gradiente)'],
        ['career_subtitle', 'A Hype Consórcios está em constante crescimento e buscamos profissionais que queiram crescer junto com a gente! 🚀', 'text', 'career', 'Subtítulo Carreira', 'Subtítulo da seção'],
        ['career_image', 'assets/images/contarte.png', 'image', 'career', 'Imagem Carreira', 'Imagem da seção de trabalhe conosco'],
    ];

    // Preparar statement para inserção
    $stmt = $conn->prepare("
        INSERT IGNORE INTO site_config
        (config_key, config_value, config_type, section, display_name, description)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $insertedCount = 0;
    foreach ($defaultConfigs as $config) {
        $stmt->execute($config);
        if ($stmt->rowCount() > 0) {
            $insertedCount++;
        }
    }

    echo "✅ $insertedCount configurações padrão inseridas!\n\n";

    // Mostrar resumo
    $countStmt = $conn->query("SELECT COUNT(*) as total FROM site_config");
    $totalConfigs = $countStmt->fetch()['total'];

    echo "📊 Total de configurações na tabela: $totalConfigs\n\n";

    // Mostrar configurações por seção
    $sectionsStmt = $conn->query("
        SELECT section, COUNT(*) as count
        FROM site_config
        GROUP BY section
        ORDER BY section
    ");

    echo "📋 Configurações por seção:\n";
    while ($row = $sectionsStmt->fetch()) {
        echo "   • {$row['section']}: {$row['count']} configurações\n";
    }

    echo "\n✅ Instalação concluída com sucesso!\n";
    echo "👉 Acesse o painel de administração para editar as configurações.\n";

} catch (PDOException $e) {
    echo "❌ Erro ao criar tabela: " . $e->getMessage() . "\n";
}

$database->closeConnection();
?>