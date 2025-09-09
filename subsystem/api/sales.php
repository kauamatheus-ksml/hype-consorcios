<?php
/**
 * API de Gerenciamento de Vendas
 * Hype Consórcios CRM
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/SalesManager.php';

$auth = new Auth();
$salesManager = new SalesManager();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

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
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    $filters = [
                        'status' => $_GET['status'] ?? '',
                        'seller_id' => $_GET['seller_id'] ?? '',
                        'date_from' => $_GET['date_from'] ?? '',
                        'date_to' => $_GET['date_to'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];
                    
                    $page = intval($_GET['page'] ?? 1);
                    $limit = intval($_GET['limit'] ?? 50);
                    
                    // Se não for admin/manager, só pode ver vendas próprias
                    if (!$auth->hasPermission($currentUser['role'], 'manager')) {
                        $filters['seller_id'] = $currentUser['id'];
                    }
                    
                    $result = $salesManager->getSales($filters, $page, $limit);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'get':
                    $saleId = $_GET['id'] ?? '';
                    if (!$saleId) {
                        throw new Exception('ID da venda é obrigatório');
                    }
                    
                    $result = $salesManager->getSaleById($saleId);
                    
                    // Verificar permissão
                    if ($result['success']) {
                        $sale = $result['sale'];
                        if (!$auth->hasPermission($currentUser['role'], 'manager') && 
                            $sale['seller_id'] != $currentUser['id']) {
                            throw new Exception('Sem permissão para ver esta venda');
                        }
                    }
                    
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'report':
                    if (!$auth->hasPermission($currentUser['role'], 'manager')) {
                        throw new Exception('Sem permissão para ver relatórios');
                    }
                    
                    $filters = [
                        'date_from' => $_GET['date_from'] ?? '',
                        'date_to' => $_GET['date_to'] ?? ''
                    ];
                    
                    $result = $salesManager->getSalesReport($filters);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'dashboard':
                    if (!$auth->hasPermission($currentUser['role'], 'manager')) {
                        // Para vendedores, mostrar apenas suas próprias estatísticas
                        $filters['seller_id'] = $currentUser['id'];
                    }
                    
                    $period = intval($_GET['period'] ?? 30);
                    $result = $salesManager->getDashboardStats($period);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                default:
                    throw new Exception('Ação não encontrada');
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            switch ($action) {
                case 'convert':
                    // Converter lead em venda
                    $leadId = $input['lead_id'] ?? '';
                    if (!$leadId) {
                        throw new Exception('ID do lead é obrigatório');
                    }
                    
                    // Verificar se pode criar vendas
                    if (!$auth->hasPermission($currentUser['role'], 'seller')) {
                        throw new Exception('Sem permissão para criar vendas');
                    }
                    
                    // Dados da venda
                    $saleData = [
                        'seller_id' => $input['seller_id'] ?? $currentUser['id'],
                        'sale_value' => $input['sale_value'] ?? 0,
                        'commission_percentage' => $input['commission_percentage'] ?? 0,
                        'vehicle_sold' => $input['vehicle_sold'] ?? '',
                        'payment_type' => $input['payment_type'] ?? 'consorcio',
                        'down_payment' => $input['down_payment'] ?? 0,
                        'financing_months' => $input['financing_months'] ?? null,
                        'monthly_payment' => $input['monthly_payment'] ?? null,
                        'contract_number' => $input['contract_number'] ?? null,
                        'notes' => $input['notes'] ?? null,
                        'status' => $input['status'] ?? 'pending'
                    ];
                    
                    // Se não for manager, só pode criar venda para si mesmo
                    if (!$auth->hasPermission($currentUser['role'], 'manager')) {
                        $saleData['seller_id'] = $currentUser['id'];
                    }
                    
                    $result = $salesManager->convertLead($leadId, $saleData, $currentUser['id']);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'update':
                    $saleId = $input['id'] ?? '';
                    if (!$saleId) {
                        throw new Exception('ID da venda é obrigatório');
                    }
                    
                    // Verificar permissão
                    $saleResult = $salesManager->getSaleById($saleId);
                    if (!$saleResult['success']) {
                        throw new Exception('Venda não encontrada');
                    }
                    
                    $sale = $saleResult['sale'];
                    if (!$auth->hasPermission($currentUser['role'], 'manager') && 
                        $sale['seller_id'] != $currentUser['id']) {
                        throw new Exception('Sem permissão para editar esta venda');
                    }
                    
                    // Se não for manager, não pode alterar vendedor
                    if (!$auth->hasPermission($currentUser['role'], 'manager')) {
                        unset($input['seller_id']);
                    }
                    
                    $result = $salesManager->updateSale($saleId, $input, $currentUser['id']);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'cancel':
                    if (!$auth->hasPermission($currentUser['role'], 'manager')) {
                        throw new Exception('Sem permissão para cancelar vendas');
                    }
                    
                    $saleId = $input['sale_id'] ?? '';
                    $reason = $input['reason'] ?? '';
                    
                    if (!$saleId || !$reason) {
                        throw new Exception('ID da venda e motivo são obrigatórios');
                    }
                    
                    $result = $salesManager->cancelSale($saleId, $reason, $currentUser['id']);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                default:
                    throw new Exception('Ação não encontrada');
            }
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>