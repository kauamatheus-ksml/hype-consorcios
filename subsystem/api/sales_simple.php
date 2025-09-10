<?php
/**
 * API para gerenciamento de vendas - Versão Simplificada
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'list';
            
            if ($action === 'details') {
                handleGetSaleDetails($conn, $userRole, $userId);
            } else {
                handleGetSales($conn, $userRole, $userId);
            }
            break;
            
        case 'POST':
            if (!in_array($userRole, ['admin', 'manager', 'seller'])) {
                throw new Exception('Sem permissão para criar vendas');
            }
            handleCreateSale($conn, $userId);
            break;
            
        case 'PUT':
            if (!in_array($userRole, ['admin', 'manager', 'seller'])) {
                throw new Exception('Sem permissão para editar vendas');
            }
            handleUpdateSale($conn, $userRole, $userId);
            break;
            
        case 'DELETE':
            if (!in_array($userRole, ['admin', 'manager'])) {
                throw new Exception('Sem permissão para deletar vendas');
            }
            handleDeleteSale($conn);
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    error_log("Erro na API de vendas: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleGetSales($conn, $userRole, $userId) {
    // Parâmetros de paginação
    $page = (int)($_GET['page'] ?? 1);
    $limit = min(50, (int)($_GET['limit'] ?? 20)); // Máximo 50 por página
    $offset = ($page - 1) * $limit;
    
    // Filtros
    $search = trim($_GET['search'] ?? '');
    $status = $_GET['status'] ?? '';
    $seller_id = $_GET['seller_id'] ?? '';
    $period = $_GET['period'] ?? '';
    
    // Construir query base
    $baseQuery = "
        FROM sales s
        LEFT JOIN users u ON s.seller_id = u.id
        LEFT JOIN leads l ON s.lead_id = l.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Aplicar filtros baseados no papel do usuário
    if (!in_array($userRole, ['admin', 'manager'])) {
        $baseQuery .= " AND s.seller_id = ?";
        $params[] = $userId;
    }
    
    // Filtros de busca
    if ($search) {
        $baseQuery .= " AND (l.name LIKE ? OR s.vehicle_sold LIKE ? OR s.contract_number LIKE ?)";
        $searchTerm = "%{$search}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($status) {
        $baseQuery .= " AND s.status = ?";
        $params[] = $status;
    }
    
    if ($seller_id) {
        $baseQuery .= " AND s.seller_id = ?";
        $params[] = $seller_id;
    }
    
    // Filtro por período
    if ($period) {
        switch ($period) {
            case 'today':
                $baseQuery .= " AND DATE(s.sale_date) = CURRENT_DATE()";
                break;
            case 'week':
                $baseQuery .= " AND s.sale_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $baseQuery .= " AND MONTH(s.sale_date) = MONTH(CURRENT_DATE()) AND YEAR(s.sale_date) = YEAR(CURRENT_DATE())";
                break;
            case 'quarter':
                $baseQuery .= " AND s.sale_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 3 MONTH)";
                break;
        }
    }
    
    // Contar total de registros
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery;
    $stmt = $conn->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    
    // Buscar vendas com paginação
    $salesQuery = "
        SELECT 
            s.*,
            u.full_name as seller_name,
            l.name as customer_name,
            l.name as lead_name
        " . $baseQuery . "
        ORDER BY s.sale_date DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    $stmt = $conn->prepare($salesQuery);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular informações de paginação
    $totalPages = ceil($totalRecords / $limit);
    
    $pagination = [
        'current_page' => $page,
        'per_page' => $limit,
        'total_records' => (int)$totalRecords,
        'total_pages' => (int)$totalPages,
        'has_next' => $page < $totalPages,
        'has_prev' => $page > 1
    ];
    
    echo json_encode([
        'success' => true,
        'sales' => $sales,
        'pagination' => $pagination
    ], JSON_UNESCAPED_UNICODE);
}

function handleGetSaleDetails($conn, $userRole, $userId) {
    $saleId = $_GET['id'] ?? null;
    
    if (!$saleId) {
        throw new Exception('ID da venda é obrigatório');
    }
    
    // Buscar venda com detalhes
    $stmt = $conn->prepare("
        SELECT 
            s.*,
            u.full_name as seller_name,
            u.email as seller_email,
            l.name as customer_name,
            l.phone as customer_phone,
            l.email as customer_email
        FROM sales s
        LEFT JOIN users u ON s.seller_id = u.id
        LEFT JOIN leads l ON s.lead_id = l.id
        WHERE s.id = ?
    ");
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        throw new Exception('Venda não encontrada');
    }
    
    // Verificar permissão
    if (!in_array($userRole, ['admin', 'manager']) && $sale['seller_id'] != $userId) {
        throw new Exception('Sem permissão para ver esta venda');
    }
    
    echo json_encode([
        'success' => true,
        'sale' => $sale
    ], JSON_UNESCAPED_UNICODE);
}

function handleCreateSale($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validação
    $requiredFields = ['lead_id', 'sale_value', 'vehicle_sold'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Campo '{$field}' é obrigatório");
        }
    }
    
    // Verificar se o lead existe
    $stmt = $conn->prepare("SELECT id FROM leads WHERE id = ?");
    $stmt->execute([$input['lead_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Lead não encontrado');
    }
    
    // Calcular comissão se percentual foi fornecido
    $commission_value = 0;
    if (!empty($input['commission_percentage']) && !empty($input['sale_value'])) {
        $commission_value = ($input['sale_value'] * $input['commission_percentage']) / 100;
    }
    
    // Inserir venda
    $stmt = $conn->prepare("
        INSERT INTO sales (
            lead_id, seller_id, sale_value, commission_percentage, 
            commission_value, vehicle_sold, payment_type, down_payment,
            financing_months, monthly_payment, contract_number, notes, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $input['lead_id'],
        $input['seller_id'] ?? $userId,
        $input['sale_value'],
        $input['commission_percentage'] ?? 0,
        $commission_value,
        $input['vehicle_sold'],
        $input['payment_type'] ?? 'consorcio',
        $input['down_payment'] ?? 0,
        $input['financing_months'] ?? null,
        $input['monthly_payment'] ?? null,
        $input['contract_number'] ?? null,
        $input['notes'] ?? null,
        $input['status'] ?? 'pending'
    ]);
    
    if ($result) {
        $saleId = $conn->lastInsertId();
        
        // Atualizar status do lead para convertido
        $stmt = $conn->prepare("UPDATE leads SET status = 'converted' WHERE id = ?");
        $stmt->execute([$input['lead_id']]);
        
        // Registrar interação no lead
        $stmt = $conn->prepare("
            INSERT INTO lead_interactions (lead_id, user_id, interaction_type, description)
            VALUES (?, ?, 'note', ?)
        ");
        $description = "Lead convertido em venda - Valor: R$ " . number_format($input['sale_value'], 2, ',', '.');
        $stmt->execute([$input['lead_id'], $userId, $description]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Venda criada com sucesso',
            'sale_id' => (int)$saleId
        ]);
    } else {
        throw new Exception('Erro ao criar venda');
    }
}

function handleUpdateSale($conn, $userRole, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $saleId = $input['id'] ?? null;
    
    if (!$saleId) {
        throw new Exception('ID da venda é obrigatório');
    }
    
    // Verificar se a venda existe e se o usuário tem permissão
    $stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch();
    
    if (!$sale) {
        throw new Exception('Venda não encontrada');
    }
    
    // Verificar permissão
    if (!in_array($userRole, ['admin', 'manager']) && $sale['seller_id'] != $userId) {
        throw new Exception('Sem permissão para editar esta venda');
    }
    
    // Campos que podem ser atualizados
    $updateFields = [];
    $params = [];
    
    $allowedFields = [
        'sale_value', 'commission_percentage', 'vehicle_sold', 'payment_type',
        'down_payment', 'financing_months', 'monthly_payment', 'contract_number',
        'notes', 'status'
    ];
    
    // Se não for manager, não pode alterar seller_id
    if (in_array($userRole, ['admin', 'manager'])) {
        $allowedFields[] = 'seller_id';
    }
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateFields[] = "{$field} = ?";
            $params[] = $input[$field];
        }
    }
    
    // Recalcular comissão se necessário
    if (isset($input['sale_value']) || isset($input['commission_percentage'])) {
        $saleValue = $input['sale_value'] ?? $sale['sale_value'];
        $commissionPercentage = $input['commission_percentage'] ?? $sale['commission_percentage'];
        
        if ($saleValue && $commissionPercentage) {
            $commissionValue = ($saleValue * $commissionPercentage) / 100;
            $updateFields[] = "commission_value = ?";
            $params[] = $commissionValue;
        }
    }
    
    if (empty($updateFields)) {
        throw new Exception('Nenhum campo para atualizar');
    }
    
    // Atualizar venda
    $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
    $params[] = $saleId;
    
    $stmt = $conn->prepare("
        UPDATE sales 
        SET " . implode(', ', $updateFields) . " 
        WHERE id = ?
    ");
    
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Venda atualizada com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao atualizar venda');
    }
}

function handleDeleteSale($conn) {
    $saleId = $_GET['id'] ?? null;
    
    if (!$saleId) {
        throw new Exception('ID da venda é obrigatório');
    }
    
    // Verificar se a venda existe
    $stmt = $conn->prepare("SELECT lead_id FROM sales WHERE id = ?");
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch();
    if (!$sale) {
        throw new Exception('Venda não encontrada');
    }
    
    // Deletar venda
    $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
    $result = $stmt->execute([$saleId]);
    
    if ($result) {
        // Reverter status do lead se necessário
        if ($sale['lead_id']) {
            $stmt = $conn->prepare("UPDATE leads SET status = 'negotiating' WHERE id = ?");
            $stmt->execute([$sale['lead_id']]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Venda deletada com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao deletar venda');
    }
}
?>