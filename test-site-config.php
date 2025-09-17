<?php
/**
 * Script de teste para configurações do site
 */

// Testar se as funções estão funcionando
require_once 'includes/site-config-functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste de Configurações do Site</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .config-item {
            background: #f5f5f5;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .section {
            margin: 20px 0;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>";

echo "<h1>Teste de Configurações do Site - Hype Consórcios</h1>";

try {
    // Testar conexão com banco
    require_once 'subsystem/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();

    if ($conn) {
        echo "<p class='success'>✅ Conexão com banco estabelecida</p>";

        // Verificar se tabela existe
        $stmt = $conn->query("SHOW TABLES LIKE 'site_config'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Tabela site_config existe</p>";

            // Contar configurações
            $countStmt = $conn->query("SELECT COUNT(*) as total FROM site_config");
            $total = $countStmt->fetch()['total'];
            echo "<p class='success'>✅ Total de configurações: $total</p>";

            if ($total == 0) {
                echo "<p class='error'>⚠️ Nenhuma configuração encontrada. Execute o script create_site_config_table.php</p>";
                echo "<p><a href='subsystem/create_site_config_table.php'>👉 Executar instalação</a></p>";
            } else {
                // Testar algumas configurações
                echo "<div class='section'>";
                echo "<h2>Configurações Carregadas:</h2>";

                $testConfigs = [
                    'site_title' => 'Título do Site',
                    'hero_title_main' => 'Título Principal do Hero',
                    'hero_title_highlight' => 'Título Destacado do Hero',
                    'hero_subtitle' => 'Subtítulo do Hero',
                    'about_title' => 'Título da Seção Sobre',
                    'company_name' => 'Nome da Empresa',
                    'company_phone' => 'Telefone da Empresa'
                ];

                foreach ($testConfigs as $key => $label) {
                    $value = getSiteConfig($key, '[Não configurado]');
                    echo "<div class='config-item'>";
                    echo "<strong>$label ($key):</strong> " . htmlspecialchars($value);
                    echo "</div>";
                }

                echo "</div>";

                // Testar carregamento de todas as configurações
                echo "<div class='section'>";
                echo "<h2>Teste de Performance:</h2>";
                $start = microtime(true);
                $allConfigs = getAllSiteConfigs();
                $end = microtime(true);
                $time = ($end - $start) * 1000;

                echo "<p class='success'>✅ Carregadas " . count($allConfigs) . " configurações em " . number_format($time, 2) . "ms</p>";
                echo "</div>";

                // Seções disponíveis
                echo "<div class='section'>";
                echo "<h2>Seções Configuradas:</h2>";
                $sectionsStmt = $conn->query("
                    SELECT section, COUNT(*) as count
                    FROM site_config
                    GROUP BY section
                    ORDER BY section
                ");

                while ($row = $sectionsStmt->fetch()) {
                    echo "<div class='config-item'>";
                    echo "<strong>{$row['section']}:</strong> {$row['count']} configurações";
                    echo "</div>";
                }
                echo "</div>";
            }

        } else {
            echo "<p class='error'>❌ Tabela site_config não existe</p>";
            echo "<p><a href='subsystem/create_site_config_table.php'>👉 Criar tabela</a></p>";
        }

    } else {
        echo "<p class='error'>❌ Erro na conexão com banco</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Links de Teste:</h2>";
echo "<ul>";
echo "<li><a href='index.php'>🏠 Ver site principal</a></li>";
echo "<li><a href='subsystem/login.php'>🔐 Acesso ao sistema</a></li>";
echo "<li><a href='subsystem/site-config.php'>⚙️ Configurações (Admin)</a></li>";
echo "<li><a href='subsystem/create_site_config_table.php'>🛠️ Instalar configurações</a></li>";
echo "</ul>";

echo "</body></html>";
?>