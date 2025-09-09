<?php
/**
 * API para estatísticas do dashboard
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

// Verificar cookie de sessão do CRM
if (isset($_COOKIE['crm_session'])) {
    require_once __DIR__ . '/../classes/Auth.php';
    $auth = new Auth();
    $sessionResult = $auth->validateSession($_COOKIE['crm_session']);
    
    if ($sessionResult['success']) {
        $authenticated = true;
        $user = $sessionResult['user'];
    }
} 
// Fallback para sessão PHP tradicional
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
    require_once __DIR__ . '/../classes/LeadManager.php';
    require_once __DIR__ . '/../classes/SalesManager.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    $leadManager = new LeadManager($conn);
    $salesManager = new SalesManager($conn);
    
    $userRole = $user['role'] ?? 'viewer';
    $userId = $user['id'] ?? null;
    
    // Estatísticas básicas
    $stats = [
        'total_leads' => 0,
        'total_sales' => 0,
        'conversion_rate' => 0,
        'today_leads' => 0
    ];
    
    // Total de leads
    $leadsQuery = "SELECT COUNT(*) as total FROM leads WHERE 1=1";
    $params = [];
    
    // Se não é admin/manager, filtrar por vendedor
    if (!in_array($userRole, ['admin', 'manager'])) {
        $leadsQuery .= " AND assigned_to = ?";
        $params[] = $userId;
    }
    
    $stmt = $conn->prepare($leadsQuery);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_leads'] = (int)$result['total'];
    
    // Total de vendas
    $salesQuery = "SELECT COUNT(*) as total FROM sales WHERE 1=1";
    $salesParams = [];
    
    if (!in_array($userRole, ['admin', 'manager'])) {
        $salesQuery .= " AND seller_id = ?";
        $salesParams[] = $userId;
    }
    
    $stmt = $conn->prepare($salesQuery);
    $stmt->execute($salesParams);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_sales'] = (int)$result['total'];
    
    // Taxa de conversão
    if ($stats['total_leads'] > 0) {
        $stats['conversion_rate'] = round(($stats['total_sales'] / $stats['total_leads']) * 100, 1);
    }
    
    // Leads de hoje
    $todayQuery = "SELECT COUNT(*) as total FROM leads WHERE DATE(created_at) = CURDATE()";
    $todayParams = [];
    
    if (!in_array($userRole, ['admin', 'manager'])) {
        $todayQuery .= " AND assigned_to = ?";
        $todayParams[] = $userId;
    }
    
    $stmt = $conn->prepare($todayQuery);
    $stmt->execute($todayParams);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['today_leads'] = (int)$result['total'];
    
    // Estatísticas adicionais para admin/manager
    if (in_array($userRole, ['admin', 'manager'])) {
        // Top vendedores do mês
        $topSellersQuery = "
            SELECT 
                u.full_name,
                COUNT(s.id) as sales_count,
                COALESCE(SUM(s.commission_amount), 0) as total_commission
            FROM users u
            LEFT JOIN sales s ON u.id = s.seller_id 
                AND MONTH(s.created_at) = MONTH(CURRENT_DATE())
                AND YEAR(s.created_at) = YEAR(CURRENT_DATE())
            WHERE u.role IN ('seller', 'manager', 'admin')
            AND u.status = 'active'
            GROUP BY u.id, u.full_name
            ORDER BY sales_count DESC
            LIMIT 5
        ";
        
        $stmt = $conn->prepare($topSellersQuery);
        $stmt->execute();
        $stats['top_sellers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Leads por fonte
        $sourceQuery = "
            SELECT 
                source,
                COUNT(*) as count
            FROM leads 
            WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
            GROUP BY source
            ORDER BY count DESC
        ";
        
        $stmt = $conn->prepare($sourceQuery);
        $stmt->execute();
        $stats['leads_by_source'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Vendas por mês (últimos 6 meses)
        $monthlyQuery = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as sales_count,
                COALESCE(SUM(sale_value), 0) as total_value
            FROM sales
            WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ";
        
        $stmt = $conn->prepare($monthlyQuery);
        $stmt->execute();
        $stats['monthly_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Erro nas estatísticas do dashboard: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ], JSON_UNESCAPED_UNICODE);
}
?>