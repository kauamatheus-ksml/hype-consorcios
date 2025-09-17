<?php
/**
 * Funções para gerenciar configurações do site
 * Hype Consórcios
 */

require_once __DIR__ . '/../subsystem/config/database.php';

/**
 * Cache estático para as configurações
 */
$GLOBALS['site_configs_cache'] = null;

/**
 * Carrega todas as configurações do banco uma única vez
 */
function loadSiteConfigs() {
    if ($GLOBALS['site_configs_cache'] === null) {
        try {
            $database = new Database();
            $conn = $database->getConnection();

            if ($conn) {
                $stmt = $conn->prepare("SELECT config_key, config_value FROM site_config");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                $GLOBALS['site_configs_cache'] = $results;
            } else {
                $GLOBALS['site_configs_cache'] = [];
            }
        } catch (Exception $e) {
            // Em caso de erro, usar cache vazio para evitar erros
            $GLOBALS['site_configs_cache'] = [];
        }
    }

    return $GLOBALS['site_configs_cache'];
}

/**
 * Obtém uma configuração específica
 */
function getSiteConfig($configKey, $defaultValue = '') {
    $configs = loadSiteConfigs();
    return $configs[$configKey] ?? $defaultValue;
}

/**
 * Obtém múltiplas configurações de uma vez
 */
function getSiteConfigs($keys) {
    $configs = loadSiteConfigs();
    $result = [];

    foreach ($keys as $key) {
        $result[$key] = $configs[$key] ?? '';
    }

    return $result;
}

/**
 * Obtém todas as configurações
 */
function getAllSiteConfigs() {
    return loadSiteConfigs();
}

/**
 * Escape HTML para exibição segura
 */
function escapeConfig($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Formata configurações de texto para exibição
 */
function formatConfigText($value) {
    return nl2br(escapeConfig($value));
}

/**
 * Verifica se uma configuração de imagem existe
 */
function configImageExists($configKey) {
    $imagePath = getSiteConfig($configKey);
    return !empty($imagePath) && file_exists(__DIR__ . '/../' . $imagePath);
}

/**
 * Obtém URL completa da imagem/vídeo ou fallback
 */
function getConfigImageUrl($configKey, $fallback = '') {
    $filePath = getSiteConfig($configKey);

    if (!empty($filePath) && file_exists(__DIR__ . '/../' . $filePath)) {
        return $filePath;
    }

    return $fallback;
}

/**
 * Alias para manter compatibilidade
 */
function getConfigMediaUrl($configKey, $fallback = '') {
    return getConfigImageUrl($configKey, $fallback);
}

/**
 * Gera meta tags baseadas nas configurações
 */
function generateMetaTags() {
    $title = escapeConfig(getSiteConfig('site_title', 'Hype Consórcios'));
    $description = escapeConfig(getSiteConfig('site_description'));
    $keywords = escapeConfig(getSiteConfig('site_keywords'));
    $ogImage = escapeConfig(getSiteConfig('og_image'));

    $html = '';

    if ($title) {
        $html .= "<title>$title</title>\n";
        $html .= "    <meta property=\"og:title\" content=\"$title\">\n";
        $html .= "    <meta name=\"twitter:title\" content=\"$title\">\n";
    }

    if ($description) {
        $html .= "    <meta name=\"description\" content=\"$description\">\n";
        $html .= "    <meta property=\"og:description\" content=\"$description\">\n";
        $html .= "    <meta name=\"twitter:description\" content=\"$description\">\n";
    }

    if ($keywords) {
        $html .= "    <meta name=\"keywords\" content=\"$keywords\">\n";
    }

    if ($ogImage) {
        $html .= "    <meta property=\"og:image\" content=\"$ogImage\">\n";
        $html .= "    <meta name=\"twitter:image\" content=\"$ogImage\">\n";
    }

    return $html;
}

/**
 * Gera dados estruturados da empresa
 */
function generateCompanyStructuredData() {
    $companyData = getSiteConfigs([
        'company_name',
        'company_phone',
        'company_address',
        'company_neighborhood',
        'company_city',
        'company_state'
    ]);

    $name = escapeConfig($companyData['company_name'] ?: 'Hype Consórcios E Investimentos Ltda');
    $phone = escapeConfig($companyData['company_phone'] ?: '(47) 99686-2997');
    $address = escapeConfig($companyData['company_address'] ?: 'Rua José Narloch, 1953');
    $neighborhood = escapeConfig($companyData['company_neighborhood'] ?: 'Bairro Tifa Martins');
    $city = escapeConfig($companyData['company_city'] ?: 'Jaraguá do Sul');
    $state = escapeConfig($companyData['company_state'] ?: 'SC');

    return [
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
        'neighborhood' => $neighborhood,
        'city' => $city,
        'state' => $state
    ];
}
?>