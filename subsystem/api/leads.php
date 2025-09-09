<?php
/**
 * API de Gerenciamento de Leads
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
require_once __DIR__ . '/../classes/LeadManager.php';

$auth = new Auth();
$leadManager = new LeadManager();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Validar sessão para todas as requisições
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
                        'priority' => $_GET['priority'] ?? '',
                        'assigned_to' => $_GET['assigned_to'] ?? '',
                        'date_from' => $_GET['date_from'] ?? '',
                        'date_to' => $_GET['date_to'] ?? '',
                        'search' => $_GET['search'] ?? ''
                    ];
                    
                    $page = intval($_GET['page'] ?? 1);
                    $limit = intval($_GET['limit'] ?? 50);
                    
                    // Se não for admin/manager, só pode ver leads próprios
                    if (!$auth->hasPermission($currentUser['role'], 'manager')) {
                        $filters['assigned_to'] = $currentUser['id'];
                    }
                    
                    $result = $leadManager->getLeads($filters, $page, $limit);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'get':
                    $leadId = $_GET['id'] ?? '';
                    if (!$leadId) {
                        throw new Exception('ID do lead é obrigatório');
                    }
                    
                    $result = $leadManager->getLeadById($leadId);
                    
                    // Verificar permissão para ver lead
                    if ($result['success']) {
                        $lead = $result['lead'];
                        if (!$auth->hasPermission($currentUser['role'], 'manager') && 
                            $lead['assigned_to'] != $currentUser['id']) {
                            throw new Exception('Sem permissão para ver este lead');
                        }
                    }
                    
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'stats':
                    if (!$auth->hasPermission($currentUser['role'], 'manager')) {
                        throw new Exception('Sem permissão para ver estatísticas');
                    }
                    
                    $filters = [
                        'date_from' => $_GET['date_from'] ?? '',
                        'date_to' => $_GET['date_to'] ?? ''
                    ];
                    
                    $result = $leadManager->getStats($filters);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'whatsapp_url':
                    $leadId = $_GET['id'] ?? '';
                    if (!$leadId) {
                        throw new Exception('ID do lead é obrigatório');
                    }
                    
                    $leadResult = $leadManager->getLeadById($leadId);
                    if (!$leadResult['success']) {
                        throw new Exception('Lead não encontrado');
                    }
                    
                    $lead = $leadResult['lead'];
                    
                    // Verificar permissão
                    if (!$auth->hasPermission($currentUser['role'], 'manager') && 
                        $lead['assigned_to'] != $currentUser['id']) {
                        throw new Exception('Sem permissão para contatar este lead');
                    }
                    
                    // Gerar URL do WhatsApp
                    $message = "Olá {$lead['name']}! 👋\n\n";
                    $message .= "Sou {$currentUser['full_name']} da Hype Consórcios.\n";
                    $message .= "Vi que você demonstrou interesse em: {$lead['vehicle_interest']}\n\n";
                    $message .= "Posso te ajudar com mais informações sobre nossos consórcios? 🚗";
                    
                    $whatsappURL = "https://api.whatsapp.com/send/?phone=55" . preg_replace('/\D/', '', $lead['phone']) . "&text=" . urlencode($message);
                    
                    // Registrar interação
                    $leadManager->addInteraction($leadId, $currentUser['id'], 'whatsapp', 'Link do WhatsApp gerado');
                    
                    echo json_encode([
                        'success' => true,
                        'whatsapp_url' => $whatsappURL
                    ], JSON_UNESCAPED_UNICODE);
                    break;
                    
                default:
                    throw new Exception('Ação não encontrada');
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            switch ($action) {
                case 'update':
                    $leadId = $input['id'] ?? '';
                    if (!$leadId) {
                        throw new Exception('ID do lead é obrigatório');
                    }
                    
                    // Verificar permissão
                    $leadResult = $leadManager->getLeadById($leadId);
                    if (!$leadResult['success']) {
                        throw new Exception('Lead não encontrado');
                    }
                    
                    $lead = $leadResult['lead'];
                    if (!$auth->hasPermission($currentUser['role'], 'manager') && 
                        $lead['assigned_to'] != $currentUser['id']) {
                        throw new Exception('Sem permissão para editar este lead');
                    }
                    
                    $result = $leadManager->updateLead($leadId, $input, $currentUser['id']);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'assign':
                    if (!$auth->hasPermission($currentUser['role'], 'manager')) {
                        throw new Exception('Sem permissão para atribuir leads');
                    }
                    
                    $leadId = $input['lead_id'] ?? '';
                    $userId = $input['user_id'] ?? '';
                    
                    if (!$leadId || !$userId) {
                        throw new Exception('Lead ID e User ID são obrigatórios');
                    }
                    
                    $result = $leadManager->assignLead($leadId, $userId, $currentUser['id']);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'interaction':
                    $leadId = $input['lead_id'] ?? '';
                    $type = $input['type'] ?? '';
                    $description = $input['description'] ?? '';
                    
                    if (!$leadId || !$type || !$description) {
                        throw new Exception('Lead ID, tipo e descrição são obrigatórios');
                    }
                    
                    // Verificar permissão
                    $leadResult = $leadManager->getLeadById($leadId);
                    if (!$leadResult['success']) {
                        throw new Exception('Lead não encontrado');
                    }
                    
                    $lead = $leadResult['lead'];
                    if (!$auth->hasPermission($currentUser['role'], 'manager') && 
                        $lead['assigned_to'] != $currentUser['id']) {
                        throw new Exception('Sem permissão para interagir com este lead');
                    }
                    
                    $result = $leadManager->addInteraction(
                        $leadId,
                        $currentUser['id'],
                        $type,
                        $description,
                        $input['result'] ?? null,
                        $input['next_contact_date'] ?? null
                    );
                    
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