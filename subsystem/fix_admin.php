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
    
    // Verificar se usuário admin já existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin' OR email = 'admin@hypeconsorcios.com.br'");
    $stmt->execute();
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "ℹ️ Usuário admin já existe. Atualizando senha...\n";
        // Não deletar - apenas atualizar para evitar problemas de foreign key
        $updateExisting = true;
    } else {
        echo "ℹ️ Criando novo usuário admin...\n";
        $updateExisting = false;
    }
    
    // Criar/atualizar usuário admin com senha correta
    $passwordHash = password_hash('password', PASSWORD_DEFAULT);
    
    // Validar se o hash foi criado corretamente
    if (!$passwordHash) {
        throw new Exception("Erro ao gerar hash da senha");
    }
    
    echo "🔐 Hash gerado: " . substr($passwordHash, 0, 20) . "...\n";
    echo "🧪 Validação do hash: " . (password_verify('password', $passwordHash) ? "OK" : "FALHOU") . "\n";
    
    if ($updateExisting) {
        // Atualizar usuário existente
        $stmt = $conn->prepare("
            UPDATE users 
            SET password_hash = ?, full_name = ?, role = ?, status = 'active'
            WHERE username = 'admin' OR email = 'admin@hypeconsorcios.com.br'
        ");
        
        $result = $stmt->execute([
            $passwordHash,
            'Administrador Sistema',
            'admin'
        ]);
    } else {
        // Criar novo usuário
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
    }
    
    if ($result) {
        $action = $updateExisting ? "atualizado" : "criado";
        echo "✅ Usuário admin {$action} com sucesso!\n";
        echo "📧 Email: admin@hypeconsorcios.com.br\n";
        echo "🔑 Senha: password\n";
        echo "👤 Nome: Administrador Sistema\n";
        echo "🛡️ Função: admin\n\n";
        
        // Testar login - buscar usuário recém criado/atualizado
        $stmt = $conn->prepare("
            SELECT id, username, email, password_hash, full_name, role, status 
            FROM users 
            WHERE (username = 'admin' OR email = 'admin@hypeconsorcios.com.br') AND status = 'active'
        ");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user && password_verify('password', $user['password_hash'])) {
            echo "🎉 Teste de login: SUCESSO!\n";
            echo "📊 Hash da senha: " . substr($user['password_hash'], 0, 20) . "...\n";
            echo "📊 Dados do usuário:\n";
            echo json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            echo "❌ Teste de login: FALHOU!\n";
            if ($user) {
                echo "👤 Usuário encontrado mas senha não confere\n";
                echo "🔍 Hash atual: " . substr($user['password_hash'], 0, 20) . "...\n";
                echo "🔍 Hash esperado: " . substr($passwordHash, 0, 20) . "...\n";
                echo "🧪 Teste manual: " . (password_verify('password', $passwordHash) ? "OK" : "FALHOU") . "\n";
            } else {
                echo "👤 Usuário não encontrado ou inativo\n";
            }
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