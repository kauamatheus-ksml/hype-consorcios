<?php
/**
 * Script para verificar e corrigir a configuração da imagem de carreira
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
        <title>Verificar Imagem de Carreira</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .success { color: green; background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .error { color: red; background: #ffe8e8; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .info { background: #e8f0ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>";

    echo "<h1>🖼️ Verificar Configuração de Imagem de Carreira</h1>";

    // Verificar se a configuração career_image existe
    $stmt = $conn->prepare("SELECT * FROM site_config WHERE config_key = 'career_image'");
    $stmt->execute();
    $careerConfig = $stmt->fetch();

    if ($careerConfig) {
        echo "<div class='success'>";
        echo "<h2>✅ Configuração encontrada!</h2>";
        echo "<p><strong>Chave:</strong> " . htmlspecialchars($careerConfig['config_key']) . "</p>";
        echo "<p><strong>Valor atual:</strong> " . htmlspecialchars($careerConfig['config_value']) . "</p>";
        echo "<p><strong>Tipo:</strong> " . htmlspecialchars($careerConfig['config_type']) . "</p>";
        echo "<p><strong>Seção:</strong> " . htmlspecialchars($careerConfig['section']) . "</p>";
        echo "</div>";

        // Verificar se o arquivo existe
        $imagePath = '../' . $careerConfig['config_value'];
        if (file_exists($imagePath)) {
            echo "<div class='success'>✅ Arquivo de imagem existe: " . htmlspecialchars($careerConfig['config_value']) . "</div>";
            echo "<img src='$imagePath' style='max-width: 300px; border: 1px solid #ddd; border-radius: 5px;'>";
        } else {
            echo "<div class='error'>❌ Arquivo de imagem não encontrado: " . htmlspecialchars($careerConfig['config_value']) . "</div>";
        }

    } else {
        echo "<div class='error'>❌ Configuração 'career_image' não encontrada!</div>";
        echo "<div class='info'>";
        echo "<h3>🔧 Adicionando configuração...</h3>";

        // Adicionar a configuração
        $insertStmt = $conn->prepare("
            INSERT INTO site_config (config_key, config_value, config_type, section, display_name, description)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $result = $insertStmt->execute([
            'career_image',
            'assets/images/contarte.png',
            'image',
            'career',
            'Imagem Carreira',
            'Imagem da seção trabalhe conosco'
        ]);

        if ($result) {
            echo "<p>✅ Configuração adicionada com sucesso!</p>";
        } else {
            echo "<p>❌ Erro ao adicionar configuração</p>";
        }
        echo "</div>";
    }

    // Verificar todas as configurações da seção career
    echo "<h2>📋 Todas as configurações de Carreira:</h2>";
    $stmt = $conn->prepare("SELECT * FROM site_config WHERE section = 'career' ORDER BY config_key");
    $stmt->execute();
    $careerConfigs = $stmt->fetchAll();

    if (empty($careerConfigs)) {
        echo "<div class='error'>❌ Nenhuma configuração de carreira encontrada!</div>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Chave</th><th>Valor</th><th>Tipo</th><th>Nome</th>";
        echo "</tr>";

        foreach ($careerConfigs as $config) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($config['config_key']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($config['config_value'], 0, 50)) . (strlen($config['config_value']) > 50 ? '...' : '') . "</td>";
            echo "<td>" . htmlspecialchars($config['config_type']) . "</td>";
            echo "<td>" . htmlspecialchars($config['display_name']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<hr>";
    echo "<h2>🔗 Links:</h2>";
    echo "<ul>";
    echo "<li><a href='site-config.php'>⚙️ Ir para configurações</a></li>";
    echo "<li><a href='../index.php' target='_blank'>🏠 Ver site</a></li>";
    echo "</ul>";

    echo "</body></html>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
}
?>