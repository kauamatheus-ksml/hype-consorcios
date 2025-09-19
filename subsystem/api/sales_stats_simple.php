<?php
/**
 * API simplificada para estatísticas de vendas
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar autenticação
$authenticated = false;
$user = null;

if (isset($_COOKIE['crm_session'])) {
    require_once __DIR__ . '/../classes/Auth.php';
    $auth = new Auth();
    $sessionResult = $auth->validateSession($_COOKIE['crm_session']);

    if ($sessionResult['success']) {
        $authenticated = true;
        $user = $sessionResult['user'];
    }
}

if (!$authenticated) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Não autenticado'
    ]);
    exit();
}

try {
    require_once __DIR__ . '/../config/database.php';

    $database = new Database();
    $conn = $database->getConnection();

    $userRole = $user['role'] ?? 'viewer';
    $userId = $user['id'] ?? null;

    // Estatísticas básicas
    $stats = [
        'total' => 0,
        'revenue' => 0.00,
        'commission' => 0.00,
        'pending' => 0
    ];

    // Determinar filtro baseado no usuário
    $isAdmin = in_array($userRole, ['admin', 'manager']);

    if ($isAdmin) {
        // Admin vê tudo
        // Total de vendas
        $stmt = $conn->query("SELECT COUNT(*) as total FROM sales");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total'] = (int)($result['total'] ?? 0);

        // Receita total
        $stmt = $conn->query("SELECT COALESCE(SUM(sale_value), 0) as revenue FROM sales WHERE status = 'confirmed'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['revenue'] = (float)($result['revenue'] ?? 0);

        // Vendas pendentes
        $stmt = $conn->query("SELECT COUNT(*) as pending FROM sales WHERE status = 'pending'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['pending'] = (int)($result['pending'] ?? 0);

        // Comissões (calculadas dinamicamente)
        $stmt = $conn->query("
            SELECT
                s.sale_value,
                COALESCE(scs.commission_percentage, 1.50) as commission_percentage
            FROM sales s
            LEFT JOIN seller_commission_settings scs ON s.seller_id = scs.seller_id AND scs.is_active = 1
            WHERE s.status = 'confirmed'
        ");
        $commissionResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalCommissions = 0;
        foreach ($commissionResults as $commission) {
            $commissionValue = ($commission['sale_value'] * $commission['commission_percentage']) / 100;
            $totalCommissions += $commissionValue;
        }
        $stats['commission'] = $totalCommissions;

    } else {
        // Vendedor vê apenas suas vendas
        // Total de vendas
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM sales WHERE seller_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total'] = (int)($result['total'] ?? 0);

        // Receita total
        $stmt = $conn->prepare("SELECT COALESCE(SUM(sale_value), 0) as revenue FROM sales WHERE seller_id = ? AND status = 'confirmed'");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['revenue'] = (float)($result['revenue'] ?? 0);

        // Vendas pendentes
        $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM sales WHERE seller_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['pending'] = (int)($result['pending'] ?? 0);

        // Comissões pessoais
        $stmt = $conn->prepare("
            SELECT
                s.sale_value,
                COALESCE(scs.commission_percentage, 1.50) as commission_percentage
            FROM sales s
            LEFT JOIN seller_commission_settings scs ON s.seller_id = scs.seller_id AND scs.is_active = 1
            WHERE s.seller_id = ? AND s.status = 'confirmed'
        ");
        $stmt->execute([$userId]);
        $commissionResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalCommissions = 0;
        foreach ($commissionResults as $commission) {
            $commissionValue = ($commission['sale_value'] * $commission['commission_percentage']) / 100;
            $totalCommissions += $commissionValue;
        }
        $stats['commission'] = $totalCommissions;
    }

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'debug' => [
            'user_id' => $userId,
            'user_role' => $userRole,
            'is_admin' => $isAdmin,
            'current_time' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Erro nas estatísticas de vendas: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage(),
        'debug' => [
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'error_trace' => $e->getTraceAsString()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>