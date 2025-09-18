<?php
require_once __DIR__ . '/classes/Auth.php';

echo "<h2>🔍 Debug do Sistema de Autenticação por Cookie</h2>";
echo "<style>body { font-family: Arial; padding: 20px; background: #f5f5f5; }</style>";

echo "<div style='background: white; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
echo "<h3>Cookies Disponíveis:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";
echo "</div>";

// Verificar cookie de sessão
$sessionId = $_COOKIE['crm_session'] ?? '';

echo "<div style='background: white; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
echo "<h3>Verificação de Sessão:</h3>";
echo "Session ID do Cookie: " . ($sessionId ? $sessionId : 'NÃO ENCONTRADO') . "<br>";

if ($sessionId) {
    try {
        $auth = new Auth();
        $sessionResult = $auth->validateSession($sessionId);

        echo "<h4>Resultado da Validação:</h4>";
        echo "<pre>";
        print_r($sessionResult);
        echo "</pre>";

        if ($sessionResult['success']) {
            $user = $sessionResult['user'];
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
            echo "✅ <strong>SESSÃO VÁLIDA</strong><br>";
            echo "Usuário: " . htmlspecialchars($user['full_name']) . "<br>";
            echo "Role: " . htmlspecialchars($user['role']) . "<br>";
            echo "É Admin: " . ($user['role'] === 'admin' ? 'SIM' : 'NÃO');
            echo "</div>";

            if ($user['role'] === 'admin') {
                echo "<br><a href='commission_settings.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Acessar Commission Settings</a>";
            } else {
                echo "<div style='background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
                echo "⚠️ Usuário logado mas não é admin";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
            echo "❌ <strong>SESSÃO INVÁLIDA</strong><br>";
            echo "Motivo: " . htmlspecialchars($sessionResult['message'] ?? 'Sessão expirada ou inválida');
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
        echo "❌ <strong>ERRO</strong><br>";
        echo "Erro: " . htmlspecialchars($e->getMessage());
        echo "</div>";
    }
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
    echo "❌ <strong>COOKIE NÃO ENCONTRADO</strong><br>";
    echo "Você precisa fazer login primeiro";
    echo "</div>";
    echo "<br><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Fazer Login</a>";
}
echo "</div>";

echo "<div style='background: white; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
echo "<h3>Instruções:</h3>";
echo "1. Se não há cookie, faça login através de <a href='login.php'>login.php</a><br>";
echo "2. Após login bem-sucedido, volte aqui para testar<br>";
echo "3. Se tudo estiver OK, acesse commission_settings.php";
echo "</div>";
?>