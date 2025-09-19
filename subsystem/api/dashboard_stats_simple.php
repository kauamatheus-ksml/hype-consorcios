<?php
/**
 * API simplificada para estatísticas do dashboard (para teste)
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
    
    // Definir filtros baseado no papel do usuário
    $isAdmin = ($userRole === 'admin');
    $sellerFilter = $isAdmin ? "" : "AND seller_id = ?";
    $leadFilter = $isAdmin ? "" : "AND assigned_to = ?";

    // Estatísticas básicas
    $stats = [
        'total_sales' => 0,
        'total_revenue' => 0,
        'total_commissions' => 0,
        'pending_sales' => 0,
        'total_leads' => 0,
        'leads_this_month' => 0,
        'sales_this_month' => 0,
        'conversion_rate' => 0,
        'user_role' => $userRole,
        'is_admin' => $isAdmin
    ];

    // Total de vendas
    $sql = "SELECT COUNT(*) as total FROM sales WHERE status != 'cancelled' $sellerFilter";
    $stmt = $conn->prepare($sql);
    if (!$isAdmin) {
        $stmt->execute([$userId]);
    } else {
        $stmt->execute();
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_sales'] = (int)($result['total'] ?? 0);

    // Receita total
    $sql = "SELECT COALESCE(SUM(sale_value), 0) as total FROM sales WHERE status = 'confirmed' $sellerFilter";
    $stmt = $conn->prepare($sql);
    if (!$isAdmin) {
        $stmt->execute([$userId]);
    } else {
        $stmt->execute();
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_revenue'] = (float)($result['total'] ?? 0);

    // Comissões do mês atual (recalculadas com base nas configurações atuais)
    if ($isAdmin) {
        // Para admin: soma de todas as comissões do mês
        $sql = "SELECT
                    s.seller_id,
                    s.sale_value,
                    COALESCE(cs.commission_percentage, 1.50) as commission_percentage
                FROM sales s
                LEFT JOIN commission_settings cs ON s.seller_id = cs.user_id
                WHERE s.status = 'confirmed'
                AND MONTH(s.created_at) = MONTH(CURRENT_DATE())
                AND YEAR(s.created_at) = YEAR(CURRENT_DATE())";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    } else {
        // Para vendedor: apenas suas próprias comissões do mês
        $sql = "SELECT
                    s.seller_id,
                    s.sale_value,
                    COALESCE(cs.commission_percentage, 1.50) as commission_percentage
                FROM sales s
                LEFT JOIN commission_settings cs ON s.seller_id = cs.user_id
                WHERE s.status = 'confirmed'
                AND s.seller_id = ?
                AND MONTH(s.created_at) = MONTH(CURRENT_DATE())
                AND YEAR(s.created_at) = YEAR(CURRENT_DATE())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
    }

    $commissionResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalCommissions = 0;

    foreach ($commissionResults as $commission) {
        $commissionValue = ($commission['sale_value'] * $commission['commission_percentage']) / 100;
        $totalCommissions += $commissionValue;
    }

    $stats['total_commissions'] = $totalCommissions;

    // Vendas pendentes
    $sql = "SELECT COUNT(*) as total FROM sales WHERE status = 'pending' $sellerFilter";
    $stmt = $conn->prepare($sql);
    if (!$isAdmin) {
        $stmt->execute([$userId]);
    } else {
        $stmt->execute();
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['pending_sales'] = (int)($result['total'] ?? 0);

    // Total de leads
    $sql = "SELECT COUNT(*) as total FROM leads $leadFilter";
    $stmt = $conn->prepare($sql);
    if (!$isAdmin) {
        $stmt->execute([$userId]);
    } else {
        $stmt->execute();
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_leads'] = (int)($result['total'] ?? 0);

    // Leads este mês
    $sql = "SELECT COUNT(*) as total FROM leads WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) $leadFilter";
    $stmt = $conn->prepare($sql);
    if (!$isAdmin) {
        $stmt->execute([$userId]);
    } else {
        $stmt->execute();
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['leads_this_month'] = (int)($result['total'] ?? 0);

    // Vendas este mês
    $sql = "SELECT COUNT(*) as total FROM sales WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND status != 'cancelled' $sellerFilter";
    $stmt = $conn->prepare($sql);
    if (!$isAdmin) {
        $stmt->execute([$userId]);
    } else {
        $stmt->execute();
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['sales_this_month'] = (int)($result['total'] ?? 0);

    // Taxa de conversão
    if ($stats['total_leads'] > 0) {
        $stats['conversion_rate'] = round(($stats['total_sales'] / $stats['total_leads']) * 100, 2);
    }

    // Estatísticas adicionais
    // Top vendedores do mês
    $stmt = $conn->prepare("
        SELECT 
            u.full_name,
            COUNT(s.id) as sales_count,
            COALESCE(SUM(s.commission_value), 0) as total_commission
        FROM users u
        LEFT JOIN sales s ON u.id = s.seller_id 
            AND MONTH(s.created_at) = MONTH(CURRENT_DATE())
            AND YEAR(s.created_at) = YEAR(CURRENT_DATE())
        WHERE u.role IN ('seller', 'manager', 'admin')
        AND u.status = 'active'
        GROUP BY u.id, u.full_name
        ORDER BY sales_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $stats['top_sellers'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Leads por fonte
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(source_page, 'Não informado') as source,
            COUNT(*) as count
        FROM leads 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
        GROUP BY source_page
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute();
    $stats['leads_by_source'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Vendas por mês (últimos 6 meses)
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as sales_count,
            COALESCE(SUM(sale_value), 0) as total_value
        FROM sales
        WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute();
    $stats['monthly_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Dados de leads por status
    $stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM leads
        GROUP BY status
        ORDER BY count DESC
    ");
    $stmt->execute();
    $stats['leads_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Últimas atividades
    $stmt = $conn->prepare("
        SELECT 
            l.name as lead_name,
            l.phone,
            l.source_page as source,
            l.status,
            l.created_at,
            u.full_name as assigned_to
        FROM leads l
        LEFT JOIN users u ON l.assigned_to = u.id
        ORDER BY l.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $stats['recent_leads'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'debug' => [
            'database_connected' => $conn ? true : false,
            'current_time' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'user_role' => $userRole,
            'is_admin' => $isAdmin,
            'seller_filter' => $sellerFilter
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Erro nas estatísticas do dashboard: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage(),
        'debug' => [
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>