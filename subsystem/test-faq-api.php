<?php
/**
 * Teste da API de FAQs
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste - API de FAQs</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-box { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; background: #e8f5e8; }
        .error { color: red; background: #ffe8e8; }
    </style>
</head>
<body>";

echo "<h1>🔍 Teste da API de FAQs</h1>";

// Teste 1: Verificar se tabela faqs existe
echo "<div class='test-box'>";
echo "<h2>Teste 1: Verificar tabela faqs</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();

    if ($conn) {
        // Verificar se tabela existe
        $stmt = $conn->query("SHOW TABLES LIKE 'faqs'");
        $tableExists = $stmt->rowCount() > 0;

        if ($tableExists) {
            echo "<div class='success'>✅ Tabela 'faqs' existe</div>";

            // Verificar quantas FAQs existem
            $stmt = $conn->query("SELECT COUNT(*) as total FROM faqs");
            $total = $stmt->fetch()['total'];
            echo "<p>Total de FAQs: $total</p>";

        } else {
            echo "<div class='error'>❌ Tabela 'faqs' não existe</div>";
            echo "<p><a href='create_faq_table.php'>Clique aqui para criar a tabela</a></p>";
        }
    } else {
        echo "<div class='error'>❌ Erro na conexão com banco</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Teste 2: Verificar autenticação
echo "<div class='test-box'>";
echo "<h2>Teste 2: Status da Sessão</h2>";

session_start();
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'Ativa' : 'Inativa') . "</p>";
echo "<p><strong>Logged In:</strong> " . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'Sim' : 'Não') : 'Não definido') . "</p>";
echo "<p><strong>User Role:</strong> " . ($_SESSION['user_role'] ?? 'Não definido') . "</p>";
echo "<p><strong>Cookie crm_session:</strong> " . (isset($_COOKIE['crm_session']) ? 'Existe' : 'Não existe') . "</p>";

echo "</div>";

// Teste 3: Simular chamada API
echo "<div class='test-box'>";
echo "<h2>Teste 3: Teste Manual da API</h2>";
echo "<p>Use as ferramentas de desenvolvedor do navegador para testar:</p>";
echo "<code>fetch('api/faq.php?action=list').then(r => r.text()).then(console.log)</code>";

echo "<script>
function testAPI() {
    fetch('api/faq.php?action=list')
        .then(response => response.text())
        .then(data => {
            console.log('Resposta da API:', data);
            document.getElementById('apiResult').innerHTML = '<pre>' + data + '</pre>';
        })
        .catch(error => {
            console.error('Erro:', error);
            document.getElementById('apiResult').innerHTML = '<div class=\"error\">Erro: ' + error + '</div>';
        });
}
</script>";

echo "<button onclick='testAPI()'>Testar API</button>";
echo "<div id='apiResult'></div>";

echo "</div>";

echo "<hr>";
echo "<h2>🔗 Links Úteis:</h2>";
echo "<ul>";
echo "<li><a href='create_faq_table.php'>Criar tabela de FAQs</a></li>";
echo "<li><a href='site-config.php'>Painel de configurações</a></li>";
echo "<li><a href='login.php'>Login</a></li>";
echo "</ul>";

echo "</body></html>";
?>