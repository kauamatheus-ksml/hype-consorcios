<?php
/**
 * API de Autenticação
 * Hype Consórcios CRM
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            switch ($action) {
                case 'login':
                    if (empty($input['username']) || empty($input['password'])) {
                        throw new Exception('Username e senha são obrigatórios');
                    }
                    
                    $result = $auth->login(
                        $input['username'],
                        $input['password'],
                        !empty($input['remember'])
                    );
                    
                    if ($result['success']) {
                        // Definir cookie de sessão
                        setcookie('crm_session', $result['session_id'], [
                            'expires' => time() + (86400 * 30), // 30 dias
                            'path' => '/',
                            'secure' => isset($_SERVER['HTTPS']),
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]);
                    }
                    
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'logout':
                    $sessionId = $input['session_id'] ?? $_COOKIE['crm_session'] ?? '';
                    
                    if ($sessionId) {
                        $result = $auth->logout($sessionId);
                        setcookie('crm_session', '', time() - 3600, '/');
                    } else {
                        $result = ['success' => true, 'message' => 'Já deslogado'];
                    }
                    
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'create_user':
                    // Verificar permissão
                    $sessionId = $_COOKIE['crm_session'] ?? $input['session_id'] ?? '';
                    $sessionResult = $auth->validateSession($sessionId);
                    
                    if (!$sessionResult['success']) {
                        throw new Exception('Sessão inválida');
                    }
                    
                    if (!$auth->hasPermission($sessionResult['user']['role'], 'manager')) {
                        throw new Exception('Sem permissão para criar usuários');
                    }
                    
                    $result = $auth->createUser($input, $sessionResult['user']['id']);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'change_password':
                    $sessionId = $_COOKIE['crm_session'] ?? $input['session_id'] ?? '';
                    $sessionResult = $auth->validateSession($sessionId);
                    
                    if (!$sessionResult['success']) {
                        throw new Exception('Sessão inválida');
                    }
                    
                    $result = $auth->changePassword(
                        $sessionResult['user']['id'],
                        $input['current_password'],
                        $input['new_password']
                    );
                    
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                default:
                    throw new Exception('Ação não encontrada');
            }
            break;
            
        case 'GET':
            switch ($action) {
                case 'validate':
                    $sessionId = $_COOKIE['crm_session'] ?? $_GET['session_id'] ?? '';
                    $result = $auth->validateSession($sessionId);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'users':
                    // Verificar permissão
                    $sessionId = $_COOKIE['crm_session'] ?? $_GET['session_id'] ?? '';
                    $sessionResult = $auth->validateSession($sessionId);
                    
                    if (!$sessionResult['success']) {
                        throw new Exception('Sessão inválida');
                    }
                    
                    if (!$auth->hasPermission($sessionResult['user']['role'], 'manager')) {
                        throw new Exception('Sem permissão para listar usuários');
                    }
                    
                    $filters = [
                        'role' => $_GET['role'] ?? '',
                        'status' => $_GET['status'] ?? '',
                        'limit' => $_GET['limit'] ?? 100
                    ];
                    
                    $result = $auth->getUsers($filters);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'cleanup':
                    // Limpar sessões expiradas
                    $result = $auth->cleanupExpiredSessions();
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