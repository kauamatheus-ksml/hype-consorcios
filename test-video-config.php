<?php
/**
 * Teste específico para configuração de vídeo
 */

require_once 'includes/site-config-functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste - Configuração de Vídeo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe8e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #e8f0ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🎥 Teste de Configuração de Vídeo</h1>";

try {
    require_once 'subsystem/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();

    if ($conn) {
        echo "<div class='success'>✅ Conexão estabelecida</div>";

        // Verificar configuração do vídeo
        $stmt = $conn->prepare("SELECT * FROM site_config WHERE config_key = 'hero_video'");
        $stmt->execute();
        $videoConfig = $stmt->fetch();

        if ($videoConfig) {
            echo "<div class='info'>";
            echo "<h2>Configuração Atual do Vídeo:</h2>";
            echo "<p><strong>Chave:</strong> " . htmlspecialchars($videoConfig['config_key']) . "</p>";
            echo "<p><strong>Valor:</strong> " . htmlspecialchars($videoConfig['config_value']) . "</p>";
            echo "<p><strong>Tipo:</strong> " . htmlspecialchars($videoConfig['config_type']) . "</p>";
            echo "<p><strong>Nome:</strong> " . htmlspecialchars($videoConfig['display_name']) . "</p>";
            echo "<p><strong>Descrição:</strong> " . htmlspecialchars($videoConfig['description']) . "</p>";
            echo "</div>";

            // Verificar se o arquivo existe
            if ($videoConfig['config_value']) {
                $videoPath = __DIR__ . '/' . $videoConfig['config_value'];
                $exists = file_exists($videoPath);
                echo "<div class='" . ($exists ? 'success' : 'error') . "'>";
                echo ($exists ? '✅' : '❌') . " Arquivo: " . htmlspecialchars($videoConfig['config_value']);
                if ($exists) {
                    $size = filesize($videoPath);
                    echo " (" . number_format($size / 1024 / 1024, 2) . " MB)";
                }
                echo "</div>";
            }

            // Verificar tipo de campo
            if ($videoConfig['config_type'] === 'image') {
                echo "<div class='success'>✅ Configurado como campo de upload (tipo 'image')</div>";
            } else {
                echo "<div class='error'>❌ Ainda configurado como campo de texto</div>";
                echo "<p><a href='subsystem/update_video_config.php'>🔧 Corrigir configuração</a></p>";
            }

        } else {
            echo "<div class='error'>❌ Configuração 'hero_video' não encontrada</div>";
        }

        // Testar função helper
        $heroVideo = getSiteConfig('hero_video', 'assets/videos/test-drive-hero.mp4');
        echo "<div class='info'>";
        echo "<h2>Teste da Função Helper:</h2>";
        echo "<p><strong>getSiteConfig('hero_video'):</strong> " . htmlspecialchars($heroVideo) . "</p>";
        echo "</div>";

    } else {
        echo "<div class='error'>❌ Erro na conexão</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<h2>🔧 Ações:</h2>";
echo "<ul>";
echo "<li><a href='subsystem/update_video_config.php'>🔧 Atualizar configuração de vídeo</a></li>";
echo "<li><a href='subsystem/site-config.php'>⚙️ Ir para painel de configurações</a></li>";
echo "<li><a href='index.php'>🏠 Ver site principal</a></li>";
echo "</ul>";

echo "</body></html>";
?>