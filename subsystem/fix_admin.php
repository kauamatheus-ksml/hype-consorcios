<?php
/**
 * Script para corrigir/criar usuário admin
 * Hype Consórcios CRM
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception("Não foi possível conectar ao banco");
    }
    
    // Deletar usuário admin se existir
    $stmt = $conn->prepare("DELETE FROM users WHERE username = 'admin' OR email = 'admin@hypeconsorcios.com.br'");
    $stmt->execute();
    
    // Criar novo usuário admin com senha correta
    $passwordHash = password_hash('password', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password_hash, full_name, role, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        'admin',
        'admin@hypeconsorcios.com.br',
        $passwordHash,
        'Administrador Sistema',
        'admin',
        'active'
    ]);
    
    if ($result) {
        echo "✅ Usuário admin criado com sucesso!\n";
        echo "📧 Email: admin@hypeconsorcios.com.br\n";
        echo "🔑 Senha: password\n";
        echo "👤 Nome: Administrador Sistema\n";
        echo "🛡️ Função: admin\n\n";
        
        // Testar login
        $stmt = $conn->prepare("
            SELECT id, username, email, password_hash, full_name, role, status 
            FROM users 
            WHERE username = 'admin' AND status = 'active'
        ");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user && password_verify('password', $user['password_hash'])) {
            echo "🎉 Teste de login: SUCESSO!\n";
            echo "📊 Dados do usuário:\n";
            echo json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            echo "❌ Teste de login: FALHOU!\n";
        }
        
    } else {
        echo "❌ Erro ao criar usuário admin\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Correção Admin - Hype CRM</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; }
        pre { background: #333; padding: 20px; border-radius: 10px; }
    </style>
</head>
<body>
    <h2>🔧 Script de Correção - Usuário Admin</h2>
    <pre><?php 
        // Se executado via web, mostrar resultado formatado
        if (isset($_SERVER['HTTP_HOST'])) {
            ob_flush();
        }
    ?></pre>
    
    <h3>📋 Próximos Passos:</h3>
    <ol>
        <li>Volte para a página de teste: <a href="test_backend.php" style="color: #00ffff;">test_backend.php</a></li>
        <li>Use as credenciais:</li>
        <ul>
            <li><strong>Usuário:</strong> admin</li>
            <li><strong>Senha:</strong> password</li>
        </ul>
        <li>Faça login e teste o sistema!</li>
    </ol>
</body>
</html>