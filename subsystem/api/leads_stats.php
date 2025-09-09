<?php
/**
 * API para estatísticas de leads
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
        'new' => 0,
        'contacted' => 0,
        'negotiating' => 0,
        'converted' => 0,
        'lost' => 0
    ];
    
    // Query base para contagem por status
    $baseQuery = "FROM leads l WHERE 1=1";
    $params = [];
    
    // Se não é admin/manager, filtrar apenas leads próprios ou sem atribuição
    if (!in_array($userRole, ['admin', 'manager'])) {
        $baseQuery .= " AND (l.assigned_to = ? OR l.assigned_to IS NULL)";
        $params[] = $userId;
    }
    
    // Total de leads
    $stmt = $conn->prepare("SELECT COUNT(*) as total " . $baseQuery);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total'] = (int)($result['total'] ?? 0);
    
    // Leads por status
    $stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as count
        " . $baseQuery . "
        GROUP BY status
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
        // Leads por período (últimos 7 dias)
        $stmt = $conn->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM leads 
            WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute();
        $additionalStats['daily_leads'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Leads por origem
        $stmt = $conn->prepare("
            SELECT 
                COALESCE(source_page, 'Não informado') as source,
                COUNT(*) as count
            FROM leads 
            WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
            GROUP BY source_page
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->execute();
        $additionalStats['leads_by_source'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Leads por prioridade
        $stmt = $conn->prepare("
            SELECT 
                priority,
                COUNT(*) as count
            FROM leads 
            GROUP BY priority
            ORDER BY 
                CASE priority 
                    WHEN 'urgent' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    WHEN 'low' THEN 4 
                    ELSE 5 
                END
        ");
        $stmt->execute();
        $additionalStats['leads_by_priority'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Leads por vendedor (atribuição)
        $stmt = $conn->prepare("
            SELECT 
                COALESCE(u.full_name, 'Não atribuído') as seller_name,
                COUNT(*) as count
            FROM leads l
            LEFT JOIN users u ON l.assigned_to = u.id
            WHERE l.created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
            GROUP BY l.assigned_to, u.full_name
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->execute();
        $additionalStats['leads_by_seller'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Taxa de conversão por período
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_leads,
                SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_leads,
                CASE 
                    WHEN COUNT(*) > 0 THEN 
                        ROUND((SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2)
                    ELSE 0 
                END as conversion_rate
            FROM leads 
            WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $conversionData = $stmt->fetch(PDO::FETCH_ASSOC);
        $additionalStats['conversion_rate'] = [
            'total_leads' => (int)($conversionData['total_leads'] ?? 0),
            'converted_leads' => (int)($conversionData['converted_leads'] ?? 0),
            'conversion_rate' => (float)($conversionData['conversion_rate'] ?? 0)
        ];
        
        // Leads criados hoje
        $stmt = $conn->prepare("
            SELECT COUNT(*) as today_leads
            FROM leads 
            WHERE DATE(created_at) = CURRENT_DATE()
        ");
        $stmt->execute();
        $todayData = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['today'] = (int)($todayData['today_leads'] ?? 0);
        
        // Leads da semana
        $stmt = $conn->prepare("
            SELECT COUNT(*) as week_leads
            FROM leads 
            WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        $weekData = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['this_week'] = (int)($weekData['week_leads'] ?? 0);
        
        // Leads do mês
        $stmt = $conn->prepare("
            SELECT COUNT(*) as month_leads
            FROM leads 
            WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute();
        $monthData = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['this_month'] = (int)($monthData['month_leads'] ?? 0);
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'additional' => $additionalStats,
        'user_role' => $userRole
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Erro nas estatísticas de leads: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>