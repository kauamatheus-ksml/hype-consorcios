<?php
/**
 * Teste específico da imagem de carreira
 */

require_once 'includes/site-config-functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste - Imagem de Carreira</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe8e8; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #e8f0ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .test-box { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h1>🖼️ Teste da Imagem de Carreira</h1>";

try {
    // Teste 1: Verificar função getSiteConfig
    echo "<div class='test-box'>";
    echo "<h2>🔍 Teste 1: Função getSiteConfig</h2>";
    $careerImage = getSiteConfig('career_image', 'FALLBACK_NOT_FOUND');
    echo "<p><strong>Resultado:</strong> " . htmlspecialchars($careerImage) . "</p>";

    if ($careerImage === 'FALLBACK_NOT_FOUND') {
        echo "<div class='error'>❌ Configuração 'career_image' não encontrada no banco!</div>";
    } else {
        echo "<div class='success'>✅ Configuração encontrada!</div>";
    }
    echo "</div>";

    // Teste 2: Verificar função getConfigImageUrl
    echo "<div class='test-box'>";
    echo "<h2>🔍 Teste 2: Função getConfigImageUrl</h2>";
    $imageUrl = getConfigImageUrl('career_image', 'assets/images/contarte.png');
    echo "<p><strong>URL retornada:</strong> " . htmlspecialchars($imageUrl) . "</p>";

    $fullPath = __DIR__ . '/' . $imageUrl;
    echo "<p><strong>Caminho completo:</strong> " . htmlspecialchars($fullPath) . "</p>";
    echo "<p><strong>Arquivo existe:</strong> " . (file_exists($fullPath) ? '✅ Sim' : '❌ Não') . "</p>";

    if (file_exists($fullPath)) {
        echo "<div class='success'>✅ Arquivo de imagem encontrado!</div>";
        echo "<img src='$imageUrl' style='max-width: 300px; border: 1px solid #ddd; border-radius: 5px; margin: 10px 0;'>";
    } else {
        echo "<div class='error'>❌ Arquivo de imagem não encontrado!</div>";
    }
    echo "</div>";

    // Teste 3: Verificar banco diretamente
    echo "<div class='test-box'>";
    echo "<h2>🔍 Teste 3: Verificação do Banco</h2>";
    require_once 'subsystem/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();

    if ($conn) {
        $stmt = $conn->prepare("SELECT * FROM site_config WHERE config_key = 'career_image'");
        $stmt->execute();
        $dbResult = $stmt->fetch();

        if ($dbResult) {
            echo "<div class='success'>✅ Encontrado no banco de dados!</div>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>Campo</th><th>Valor</th>";
            echo "</tr>";
            foreach ($dbResult as $key => $value) {
                if (!is_numeric($key)) {
                    echo "<tr>";
                    echo "<td><strong>$key</strong></td>";
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                    echo "</tr>";
                }
            }
            echo "</table>";
        } else {
            echo "<div class='error'>❌ NÃO encontrado no banco de dados!</div>";
            echo "<p><a href='subsystem/fix_career_image.php'>🔧 Clique aqui para corrigir</a></p>";
        }
    } else {
        echo "<div class='error'>❌ Erro na conexão com banco!</div>";
    }
    echo "</div>";

    // Teste 4: Como aparece no index.php
    echo "<div class='test-box'>";
    echo "<h2>🔍 Teste 4: Como aparece no index.php</h2>";
    $finalImagePath = escapeConfig(getConfigImageUrl('career_image', 'assets/images/contarte.png'));
    echo "<p><strong>Caminho final usado no HTML:</strong> " . $finalImagePath . "</p>";
    echo "<p><strong>HTML gerado:</strong></p>";
    echo "<code>&lt;img src=\"$finalImagePath\" alt=\"Trabalhe conosco\"&gt;</code>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<h2>🔗 Ações:</h2>";
echo "<ul>";
echo "<li><a href='subsystem/fix_career_image.php'>🔧 Corrigir configuração</a></li>";
echo "<li><a href='subsystem/site-config.php'>⚙️ Painel de configurações</a></li>";
echo "<li><a href='index.php' target='_blank'>🏠 Ver site</a></li>";
echo "</ul>";

echo "</body></html>";
?>