<?php
// Teste da API do dashboard
require_once __DIR__ . '/classes/Auth.php';

echo "<h2>🔍 Teste da API Dashboard</h2>";
echo "<style>body { font-family: Arial; padding: 20px; background: #f5f5f5; }</style>";

// Verificar cookie de sessão
$sessionId = $_COOKIE['crm_session'] ?? '';

echo "<div style='background: white; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
echo "<h3>Cookie de Sessão:</h3>";
echo "Session ID: " . ($sessionId ? 'Presente' : 'NÃO ENCONTRADO') . "<br>";

if ($sessionId) {
    try {
        $auth = new Auth();
        $sessionResult = $auth->validateSession($sessionId);

        echo "<h4>Validação da Sessão:</h4>";
        echo "<pre>";
        print_r($sessionResult);
        echo "</pre>";

        if ($sessionResult['success']) {
            $user = $sessionResult['user'];
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
            echo "✅ <strong>SESSÃO VÁLIDA</strong><br>";
            echo "Usuário: " . htmlspecialchars($user['full_name']) . "<br>";
            echo "Role: " . htmlspecialchars($user['role']) . "<br>";
            echo "ID: " . htmlspecialchars($user['id']);
            echo "</div>";

            // Agora testar a API diretamente
            echo "<h3>Teste da API Dashboard:</h3>";

            // Simular chamada da API
            $userRole = $user['role'];
            $userId = $user['id'];
            $isAdmin = ($userRole === 'admin');

            require_once __DIR__ . '/config/database.php';
            $database = new Database();
            $conn = $database->getConnection();

            $sellerFilter = $isAdmin ? "" : "AND seller_id = ?";

            echo "<h4>Consultas SQL:</h4>";

            // Total de vendas
            $sql = "SELECT COUNT(*) as total FROM sales WHERE status != 'cancelled' $sellerFilter";
            echo "<strong>Total de vendas:</strong> " . $sql . "<br>";
            $stmt = $conn->prepare($sql);
            if (!$isAdmin) {
                $stmt->execute([$userId]);
            } else {
                $stmt->execute();
            }
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Resultado: " . ($result['total'] ?? 0) . " vendas<br><br>";

            // Receita total
            $sql = "SELECT COALESCE(SUM(sale_value), 0) as total FROM sales WHERE status = 'confirmed' $sellerFilter";
            echo "<strong>Receita total:</strong> " . $sql . "<br>";
            $stmt = $conn->prepare($sql);
            if (!$isAdmin) {
                $stmt->execute([$userId]);
            } else {
                $stmt->execute();
            }
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Resultado: R$ " . number_format($result['total'] ?? 0, 2, ',', '.') . "<br><br>";

            // Total de comissões
            $sql = "SELECT COALESCE(SUM(commission_value), 0) as total FROM sales WHERE status = 'confirmed' $sellerFilter";
            echo "<strong>Total de comissões:</strong> " . $sql . "<br>";
            $stmt = $conn->prepare($sql);
            if (!$isAdmin) {
                $stmt->execute([$userId]);
            } else {
                $stmt->execute();
            }
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Resultado: R$ " . number_format($result['total'] ?? 0, 2, ',', '.') . "<br><br>";

            // Vendas pendentes
            $sql = "SELECT COUNT(*) as total FROM sales WHERE status = 'pending' $sellerFilter";
            echo "<strong>Vendas pendentes:</strong> " . $sql . "<br>";
            $stmt = $conn->prepare($sql);
            if (!$isAdmin) {
                $stmt->execute([$userId]);
            } else {
                $stmt->execute();
            }
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Resultado: " . ($result['total'] ?? 0) . " vendas<br><br>";

            echo "<a href='dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Voltar ao Dashboard</a>";

        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
            echo "❌ <strong>SESSÃO INVÁLIDA</strong>";
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
}
echo "</div>";
?>