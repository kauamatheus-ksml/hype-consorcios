<?php
/**
 * Teste Rápido de Autenticação
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'classes/Auth.php';

try {
    $auth = new Auth();
    
    echo "🔧 Testando autenticação...\n";
    
    // Teste de login
    $result = $auth->login('admin', 'password');
    
    if ($result['success']) {
        echo "✅ Login funcionando!\n";
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Testar validação de sessão
        $sessionId = $result['session_id'];
        echo "\n\n🔍 Testando validação de sessão...\n";
        
        $validation = $auth->validateSession($sessionId);
        echo json_encode($validation, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
    } else {
        echo "❌ Login falhou!\n";
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste Auth - Hype CRM</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; }
        pre { background: #333; padding: 20px; border-radius: 10px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h2>🧪 Teste de Autenticação</h2>
    <pre><?php ob_flush(); ?></pre>
    
    <p><a href="test_backend.php" style="color: #00ffff;">← Voltar para Teste Backend</a></p>
</body>
</html>