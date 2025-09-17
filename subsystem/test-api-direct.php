<?php
/**
 * Teste direto da API de configurações
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste Direto da API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #e8f5e8; }
        .error { background: #ffe8e8; }
    </style>
</head>
<body>";

echo "<h1>🧪 Teste Direto da API</h1>";

// Simular POST para a API
$_POST['action'] = 'save';
$_POST['section'] = 'hero';
$_POST['hero_title_main'] = 'Teste';

// Capturar output da API
ob_start();
include 'api/site-config.php';
$apiOutput = ob_get_clean();

echo "<div class='result'>";
echo "<h2>Resultado da API:</h2>";
echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";
echo "</div>";

// Testar apenas autenticação
echo "<div class='result'>";
echo "<h2>Teste de Autenticação:</h2>";

session_start();
$authenticated = false;
$userRole = null;

if (isset($_COOKIE['crm_session'])) {
    require_once 'classes/Auth.php';
    $auth = new Auth();
    $sessionResult = $auth->validateSession($_COOKIE['crm_session']);

    if ($sessionResult['success']) {
        $authenticated = true;
        $userRole = $sessionResult['user']['role'] ?? 'viewer';
    }
} elseif (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $authenticated = true;
    $userRole = $_SESSION['user_role'] ?? 'viewer';
}

echo "<p>Autenticado: " . ($authenticated ? 'Sim' : 'Não') . "</p>";
echo "<p>Role: " . ($userRole ?: 'N/A') . "</p>";
echo "<p>É Admin: " . ($userRole === 'admin' ? 'Sim' : 'Não') . "</p>";
echo "</div>";

// Informações de requisição
echo "<div class='result'>";
echo "<h2>Informações da Requisição:</h2>";
echo "<p><strong>Method:</strong> " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "</p>";
echo "<p><strong>POST keys:</strong> " . implode(', ', array_keys($_POST)) . "</p>";
echo "<p><strong>FILES keys:</strong> " . implode(', ', array_keys($_FILES)) . "</p>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "</div>";

echo "<hr>";
echo "<p><a href='site-config.php'>← Voltar para configurações</a></p>";

echo "</body></html>";
?>