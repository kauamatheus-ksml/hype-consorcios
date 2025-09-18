<?php
/**
 * API de Relatório de Comissões por Mês
 * Hype Consórcios CRM
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();

// Validar sessão
$sessionId = $_COOKIE['crm_session'] ?? $_GET['session_id'] ?? '';
$sessionResult = $auth->validateSession($sessionId);

if (!$sessionResult['success']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sessão inválida ou expirada'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$currentUser = $sessionResult['user'];

try {
    require_once __DIR__ . '/../config/database.php';

    $database = new Database();
    $conn = $database->getConnection();

    // Parâmetros de filtro
    $sellerId = $_GET['seller_id'] ?? '';
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? '';

    // Construir query base
    $whereConditions = ["s.status = 'completed'"];
    $params = [];

    // Se não for admin/manager, só pode ver próprias comissões
    if (!in_array($currentUser['role'], ['admin', 'manager'])) {
        $whereConditions[] = "s.seller_id = ?";
        $params[] = $currentUser['id'];
    } elseif ($sellerId) {
        $whereConditions[] = "s.seller_id = ?";
        $params[] = $sellerId;
    }

    // Filtro por ano
    $whereConditions[] = "YEAR(s.sale_date) = ?";
    $params[] = $year;

    // Filtro por mês específico (opcional)
    if ($month) {
        $whereConditions[] = "MONTH(s.sale_date) = ?";
        $params[] = $month;
    }

    $whereClause = implode(' AND ', $whereConditions);

    if ($month) {
        // Relatório detalhado para um mês específico
        $stmt = $conn->prepare("
            SELECT
                s.id,
                s.sale_value,
                s.commission_percentage,
                s.commission_value,
                s.commission_installments,
                s.monthly_commission,
                s.sale_date,
                s.vehicle_sold,
                l.name as customer_name,
                u.full_name as seller_name,
                u.id as seller_id
            FROM sales s
            JOIN leads l ON s.lead_id = l.id
            JOIN users u ON s.seller_id = u.id
            WHERE {$whereClause}
            ORDER BY s.sale_date DESC
        ");
        $stmt->execute($params);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular totais do mês
        $totalSales = 0;
        $totalCommission = 0;
        $totalMonthlyCommission = 0;

        foreach ($sales as $sale) {
            $totalSales += $sale['sale_value'];
            $totalCommission += $sale['commission_value'];
            $totalMonthlyCommission += $sale['monthly_commission'];
        }

        echo json_encode([
            'success' => true,
            'period' => [
                'year' => $year,
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1))
            ],
            'summary' => [
                'total_sales' => $totalSales,
                'total_commission' => $totalCommission,
                'total_monthly_commission' => $totalMonthlyCommission,
                'sales_count' => count($sales)
            ],
            'sales' => $sales
        ], JSON_UNESCAPED_UNICODE);

    } else {
        // Relatório resumido por mês do ano
        $stmt = $conn->prepare("
            SELECT
                YEAR(s.sale_date) as year,
                MONTH(s.sale_date) as month,
                MONTHNAME(s.sale_date) as month_name,
                u.id as seller_id,
                u.full_name as seller_name,
                COUNT(s.id) as sales_count,
                SUM(s.sale_value) as total_sales,
                SUM(s.commission_value) as total_commission,
                SUM(s.monthly_commission) as total_monthly_commission,
                AVG(s.commission_percentage) as avg_commission_percentage
            FROM sales s
            JOIN users u ON s.seller_id = u.id
            JOIN leads l ON s.lead_id = l.id
            WHERE {$whereClause}
            GROUP BY YEAR(s.sale_date), MONTH(s.sale_date), u.id
            ORDER BY YEAR(s.sale_date) DESC, MONTH(s.sale_date) DESC, u.full_name
        ");
        $stmt->execute($params);
        $monthlyReport = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar por mês
        $reportByMonth = [];
        foreach ($monthlyReport as $row) {
            $monthKey = $row['year'] . '-' . str_pad($row['month'], 2, '0', STR_PAD_LEFT);
            if (!isset($reportByMonth[$monthKey])) {
                $reportByMonth[$monthKey] = [
                    'year' => $row['year'],
                    'month' => $row['month'],
                    'month_name' => $row['month_name'],
                    'sellers' => [],
                    'totals' => [
                        'sales_count' => 0,
                        'total_sales' => 0,
                        'total_commission' => 0,
                        'total_monthly_commission' => 0
                    ]
                ];
            }

            $reportByMonth[$monthKey]['sellers'][] = [
                'seller_id' => $row['seller_id'],
                'seller_name' => $row['seller_name'],
                'sales_count' => $row['sales_count'],
                'total_sales' => $row['total_sales'],
                'total_commission' => $row['total_commission'],
                'total_monthly_commission' => $row['total_monthly_commission'],
                'avg_commission_percentage' => $row['avg_commission_percentage']
            ];

            // Somar totais do mês
            $reportByMonth[$monthKey]['totals']['sales_count'] += $row['sales_count'];
            $reportByMonth[$monthKey]['totals']['total_sales'] += $row['total_sales'];
            $reportByMonth[$monthKey]['totals']['total_commission'] += $row['total_commission'];
            $reportByMonth[$monthKey]['totals']['total_monthly_commission'] += $row['total_monthly_commission'];
        }

        echo json_encode([
            'success' => true,
            'year' => $year,
            'seller_filter' => $sellerId ? $sellerId : 'all',
            'months' => array_values($reportByMonth)
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    error_log("Erro no relatório de comissões: " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>