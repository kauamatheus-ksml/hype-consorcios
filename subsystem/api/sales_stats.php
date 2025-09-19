<?php
/**
 * API para estatísticas de vendas
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Iniciar sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
elseif (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $authenticated = true;
    $user = [
        'id' => $_SESSION['user_id'] ?? null,
        'role' => $_SESSION['user_role'] ?? 'viewer'
    ];
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
        'pending' => 0,
        'confirmed' => 0,
        'cancelled' => 0
    ];
    
    // Definir parâmetros baseado no papel do usuário
    $userFilter = "";
    $params = [];

    // Se não é admin/manager, filtrar apenas vendas próprias
    if (!in_array($userRole, ['admin', 'manager'])) {
        $userFilter = " AND s.seller_id = ?";
        $params[] = $userId;
    }

    // Total de vendas
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM sales s WHERE 1=1" . $userFilter);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total'] = (int)($result['total'] ?? 0);

    // Receita total (apenas vendas confirmadas)
    $stmt = $conn->prepare("SELECT COALESCE(SUM(s.sale_value), 0) as total_revenue FROM sales s WHERE s.status = 'confirmed'" . $userFilter);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['revenue'] = (float)($result['total_revenue'] ?? 0);

    // Comissões (recalculadas com base nas configurações atuais)
    $commissionQuery = "
        SELECT
            s.seller_id,
            s.sale_value,
            COALESCE(scs.commission_percentage, 1.50) as commission_percentage
        FROM sales s
        LEFT JOIN seller_commission_settings scs ON s.seller_id = scs.seller_id AND scs.is_active = 1
        WHERE s.status = 'confirmed'";

    if (!in_array($userRole, ['admin', 'manager'])) {
        $commissionQuery .= " AND s.seller_id = ?";
        $commissionParams = [$userId];
    } else {
        $commissionParams = [];
    }

    $stmt = $conn->prepare($commissionQuery);
    $stmt->execute($commissionParams);
    $commissionResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalCommissions = 0;
    foreach ($commissionResults as $commission) {
        $commissionValue = ($commission['sale_value'] * $commission['commission_percentage']) / 100;
        $totalCommissions += $commissionValue;
    }

    $stats['commission'] = $totalCommissions;
    
    // Vendas por status
    $stmt = $conn->prepare("
        SELECT
            s.status,
            COUNT(*) as count
        FROM sales s
        WHERE 1=1" . $userFilter . "
        GROUP BY s.status
    ");
    $stmt->execute($params);
    $statusResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($statusResults as $row) {
        if (isset($stats[$row['status']])) {
            $stats[$row['status']] = (int)$row['count'];
        }
    }
    
    // Estatísticas adicionais para admin/manager
    $additionalStats = [];
    
    if (in_array($userRole, ['admin', 'manager'])) {
        // Vendas por período (últimos 7 dias)
        $stmt = $conn->prepare("
            SELECT 
                DATE(sale_date) as date,
                COUNT(*) as count,
                SUM(sale_value) as revenue
            FROM sales 
            WHERE sale_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
            GROUP BY DATE(sale_date)
            ORDER BY date DESC
        ");
        $stmt->execute();
        $additionalStats['daily_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top vendedores
        $stmt = $conn->prepare("
            SELECT 
                u.full_name as seller_name,
                COUNT(s.id) as total_sales,
                SUM(s.sale_value) as total_revenue,
                SUM(s.commission_value) as total_commission
            FROM sales s
            LEFT JOIN users u ON s.seller_id = u.id
            WHERE s.sale_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
            GROUP BY s.seller_id, u.full_name
            ORDER BY total_revenue DESC
            LIMIT 10
        ");
        $stmt->execute();
        $additionalStats['top_sellers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Vendas por veículo (mais vendidos)
        $stmt = $conn->prepare("
            SELECT 
                vehicle_sold,
                COUNT(*) as count,
                SUM(sale_value) as total_value
            FROM sales 
            WHERE vehicle_sold IS NOT NULL 
            AND vehicle_sold != ''
            AND sale_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
            GROUP BY vehicle_sold
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->execute();
        $additionalStats['top_vehicles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Vendas criadas hoje
        $stmt = $conn->prepare("
            SELECT COUNT(*) as today_sales, SUM(sale_value) as today_revenue
            FROM sales 
            WHERE DATE(sale_date) = CURRENT_DATE()
        ");
        $stmt->execute();
        $todayData = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['today'] = (int)($todayData['today_sales'] ?? 0);
        $stats['today_revenue'] = (float)($todayData['today_revenue'] ?? 0);
        
        // Vendas da semana
        $stmt = $conn->prepare("
            SELECT COUNT(*) as week_sales, SUM(sale_value) as week_revenue
            FROM sales 
            WHERE sale_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        $weekData = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['this_week'] = (int)($weekData['week_sales'] ?? 0);
        $stats['this_week_revenue'] = (float)($weekData['week_revenue'] ?? 0);
        
        // Vendas do mês
        $stmt = $conn->prepare("
            SELECT COUNT(*) as month_sales, SUM(sale_value) as month_revenue
            FROM sales 
            WHERE MONTH(sale_date) = MONTH(CURRENT_DATE())
            AND YEAR(sale_date) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute();
        $monthData = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['this_month'] = (int)($monthData['month_sales'] ?? 0);
        $stats['this_month_revenue'] = (float)($monthData['month_revenue'] ?? 0);
        
        // Ticket médio
        if ($stats['total'] > 0) {
            $stats['average_ticket'] = $stats['revenue'] / $stats['total'];
        } else {
            $stats['average_ticket'] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'additional' => $additionalStats,
        'user_role' => $userRole,
        'debug' => [
            'user_id' => $userId,
            'user_filter' => $userFilter,
            'params_count' => count($params),
            'current_time' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Erro nas estatísticas de vendas: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>