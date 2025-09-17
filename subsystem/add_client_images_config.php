<?php
/**
 * Script para adicionar configurações das imagens dos clientes
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception('Erro na conexão com o banco de dados');
    }

    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Adicionar Configurações de Imagens dos Clientes</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
            .error { color: red; background: #ffe8e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
            .info { background: #e8f0ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>";

    echo "<h1>📸 Adicionar Configurações de Imagens dos Clientes</h1>";

    // Configurações das imagens dos clientes
    $clientImageConfigs = [
        ['client_image_1', 'assets/images/clientes/cliente-1.jpg', 'image', 'clients', 'Imagem Cliente 1', 'Foto do primeiro cliente contemplado'],
        ['client_image_2', 'assets/images/clientes/cliente-2.jpg', 'image', 'clients', 'Imagem Cliente 2', 'Foto do segundo cliente contemplado'],
        ['client_image_3', 'assets/images/clientes/cliente-3.jpg', 'image', 'clients', 'Imagem Cliente 3', 'Foto do terceiro cliente contemplado'],
        ['client_image_4', 'assets/images/clientes/cliente-4.jpg', 'image', 'clients', 'Imagem Cliente 4', 'Foto do quarto cliente contemplado'],
        ['client_image_5', 'assets/images/clientes/cliente-5.jpg', 'image', 'clients', 'Imagem Cliente 5', 'Foto do quinto cliente contemplado'],
        ['client_image_6', 'assets/images/clientes/cliente-6.jpg', 'image', 'clients', 'Imagem Cliente 6', 'Foto do sexto cliente contemplado'],
        ['client_image_7', 'assets/images/clientes/cliente-7.jpg', 'image', 'clients', 'Imagem Cliente 7', 'Foto do sétimo cliente contemplado'],
        ['client_image_8', 'assets/images/clientes/cliente-8.jpg', 'image', 'clients', 'Imagem Cliente 8', 'Foto do oitavo cliente contemplado'],
        ['client_image_9', 'assets/images/clientes/cliente-9.jpg', 'image', 'clients', 'Imagem Cliente 9', 'Foto do nono cliente contemplado'],
        ['client_image_10', 'assets/images/clientes/cliente-10.jpg', 'image', 'clients', 'Imagem Cliente 10', 'Foto do décimo cliente contemplado'],
    ];

    // Verificar se as configurações já existem
    $stmt = $conn->prepare("SELECT config_key FROM site_config WHERE config_key LIKE 'client_image_%'");
    $stmt->execute();
    $existingConfigs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<div class='info'>";
    echo "<h2>Status Atual:</h2>";
    echo "<p>Configurações de imagens já existentes: " . count($existingConfigs) . "</p>";
    if (count($existingConfigs) > 0) {
        echo "<p>Existem: " . implode(', ', $existingConfigs) . "</p>";
    }
    echo "</div>";

    // Preparar statement para inserção
    $insertStmt = $conn->prepare("
        INSERT IGNORE INTO site_config
        (config_key, config_value, config_type, section, display_name, description)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $insertedCount = 0;
    $skippedCount = 0;

    echo "<h2>Adicionando configurações:</h2>";

    foreach ($clientImageConfigs as $config) {
        list($key, $value, $type, $section, $name, $description) = $config;

        $insertStmt->execute([$key, $value, $type, $section, $name, $description]);

        if ($insertStmt->rowCount() > 0) {
            echo "<div class='success'>✅ Adicionado: $name ($key)</div>";
            $insertedCount++;
        } else {
            echo "<div class='info'>⏭️ Já existe: $name ($key)</div>";
            $skippedCount++;
        }
    }

    echo "<div class='info'>";
    echo "<h2>Resumo:</h2>";
    echo "<p>✅ Configurações adicionadas: $insertedCount</p>";
    echo "<p>⏭️ Configurações já existentes: $skippedCount</p>";
    echo "<p>📊 Total de configurações de clientes: " . count($clientImageConfigs) . "</p>";
    echo "</div>";

    // Verificar se as imagens existem
    echo "<h2>Verificação das Imagens:</h2>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;'>";

    foreach ($clientImageConfigs as $config) {
        list($key, $imagePath) = $config;
        $fullPath = '../' . $imagePath;
        $exists = file_exists($fullPath);

        echo "<div style='border: 1px solid #ddd; padding: 10px; border-radius: 5px; text-align: center;'>";
        echo "<h4>Cliente " . substr($key, -1) . "</h4>";

        if ($exists) {
            $size = filesize($fullPath);
            echo "<img src='$fullPath' style='max-width: 100%; height: 100px; object-fit: cover; border-radius: 4px;'>";
            echo "<p style='color: green; font-size: 0.8em; margin: 5px 0 0 0;'>✅ Existe (" . number_format($size/1024, 1) . " KB)</p>";
        } else {
            echo "<div style='width: 100%; height: 100px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666;'>Imagem não encontrada</div>";
            echo "<p style='color: red; font-size: 0.8em; margin: 5px 0 0 0;'>❌ Não encontrada</p>";
        }

        echo "<p style='font-size: 0.7em; color: #666; margin: 5px 0 0 0;'>$imagePath</p>";
        echo "</div>";
    }

    echo "</div>";

    echo "<hr>";
    echo "<h2>🔗 Próximos Passos:</h2>";
    echo "<ul>";
    echo "<li><a href='site-config.php'>⚙️ Ir para configurações do site</a></li>";
    echo "<li><a href='../index.php' target='_blank'>🏠 Ver site principal</a></li>";
    echo "<li><a href='test-api-js.html' target='_blank'>🧪 Testar API</a></li>";
    echo "</ul>";

    echo "</body></html>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
}
?>