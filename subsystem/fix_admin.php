<?php
/**
 * Script para corrigir/criar usuário Gerente de Vendas
 * Hype Consórcios CRM
 */

// Inicia o buffer de saída para garantir que o conteúdo seja exibido no <pre>
ob_start();

require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception("Não foi possível conectar ao banco de dados");
    }
    
    // --- 1. Definir os dados do usuário Gerente de Vendas ---
    $username     = 'gerentevendas';
    $email        = 'gerentevendas@hypeconsorcios.com.br';
    $newPassword  = 'HypeVendas@2025'; // Defina a nova senha aqui
    $fullName     = 'Gerente de Vendas';
    $role         = 'gerente_vendas';
    $status       = 'active';
    
    // --- 2. Deletar usuário existente para evitar duplicatas ---
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    echo "ℹ️ Usuário '{$username}' anterior (se existente) removido.\n";
    
    // --- 3. Criar o hash da nova senha ---
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // --- 4. Inserir o novo usuário com a senha hasheada ---
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password_hash, full_name, role, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $username,
        $email,
        $passwordHash,
        $fullName,
        $role,
        $status
    ]);
    
    if ($result) {
        echo "✅ Usuário 'Gerente de Vendas' criado/corrigido com sucesso!\n";
        echo "--------------------------------------------------------\n";
        echo "📧 Email: {$email}\n";
        echo "🔑 Senha: {$newPassword}\n";
        echo "👤 Nome: {$fullName}\n";
        echo "🛡️ Função: {$role}\n\n";
        
        // --- 5. Testar o login para verificar se a senha funciona ---
        $stmt = $conn->prepare("
            SELECT id, username, email, password_hash, full_name, role, status 
            FROM users 
            WHERE username = ? AND status = ?
        ");
        $stmt->execute([$username, $status]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($newPassword, $user['password_hash'])) {
            echo "🎉 Teste de login: SUCESSO!\n";
            echo "📊 Dados do usuário recuperado do banco:\n";
            // Usando print_r para uma saída mais limpa no terminal/pre formatado
            print_r($user);
        } else {
            echo "❌ Teste de login: FALHOU! Verifique o hash da senha e a consulta.\n";
        }
        
    } else {
        echo "❌ Erro ao criar o usuário 'Gerente de Vendas'.\n";
        // Imprimir informações de erro do PDO
        print_r($stmt->errorInfo());
    }
    
} catch (Exception $e) {
    echo "❌ Erro Crítico: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}

// Captura o conteúdo do buffer
$output = ob_get_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Correção Gerente Vendas - Hype CRM</title>
    <style>
        body { font-family: monospace, sans-serif; background: #1a1a1a; color: #00ff00; padding: 20px; font-size: 14px; }
        h2, h3 { color: #ffffff; border-bottom: 1px solid #00ff00; padding-bottom: 5px;}
        pre { background: #2b2b2b; padding: 20px; border-radius: 10px; border-left: 3px solid #00ff00; white-space: pre-wrap; word-wrap: break-word; }
        a { color: #00ffff; }
        ul { list-style: none; padding-left: 0;}
        li { margin-bottom: 8px;}
        strong { color: #ffffff; }
    </style>
</head>
<body>
    <h2>🔧 Script de Correção - Usuário Gerente de Vendas</h2>
    <pre><?php echo htmlspecialchars($output); // Exibe a saída do script PHP de forma segura ?></pre>
    
    <h3>📋 Próximos Passos:</h3>
    <ol>
        <li>Volte para a página de login do CRM.</li>
        <li>Use as novas credenciais para acessar:</li>
        <ul>
            <li><strong>Usuário:</strong> gerentevendas</li>
            <li><strong>Senha:</strong> HypeVendas@2025</li>
        </ul>
        <li>Após o login, por segurança, é recomendável alterar a senha através do perfil do usuário.</li>
        <li><strong>IMPORTANTE:</strong> Delete este script do servidor após o uso para evitar riscos de segurança.</li>
    </ol>
</body>
</html>