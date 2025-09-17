<?php
/**
 * Versão de debug da API site-config para identificar o problema
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Debug: mostrar todas as informações recebidas
$debug_info = [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'post_data' => $_POST,
    'get_data' => $_GET,
    'files_data' => $_FILES,
    'headers' => getallheaders(),
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown',
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'unknown'
];

// Verificar se é uma requisição de debug
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    echo json_encode([
        'success' => true,
        'message' => 'Debug info',
        'debug' => $debug_info
    ]);
    exit();
}

// Continuar com a lógica normal
session_start();

$authenticated = false;
$userRole = null;

// Verificar cookie de sessão do CRM
if (isset($_COOKIE['crm_session'])) {
    require_once '../classes/Auth.php';
    $auth = new Auth();
    $sessionResult = $auth->validateSession($_COOKIE['crm_session']);

    if ($sessionResult['success']) {
        $authenticated = true;
        $userRole = $sessionResult['user']['role'] ?? 'viewer';
    }
}
// Fallback para sessão PHP tradicional
elseif (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $authenticated = true;
    $userRole = $_SESSION['user_role'] ?? 'viewer';
}

// TEMPORARIAMENTE: pular verificação de auth para debug
$skipAuth = isset($_GET['skip_auth']) && $_GET['skip_auth'] === '1';

if (!$skipAuth && (!$authenticated || $userRole !== 'admin')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado. Apenas administradores podem acessar esta funcionalidade.',
        'debug' => array_merge($debug_info, [
            'authenticated' => $authenticated,
            'user_role' => $userRole,
            'session_data' => $_SESSION,
            'cookies' => $_COOKIE
        ])
    ]);
    exit();
}

require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception('Erro na conexão com o banco de dados');
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'get':
            $stmt = $conn->prepare("SELECT config_key, config_value, config_type, section, display_name, description FROM site_config ORDER BY section, display_name");
            $stmt->execute();
            $results = $stmt->fetchAll();

            $configs = [];
            foreach ($results as $row) {
                $configs[$row['config_key']] = $row;
            }

            echo json_encode([
                'success' => true,
                'configs' => $configs,
                'debug' => $debug_info
            ]);
            break;

        case 'save':
            echo json_encode([
                'success' => true,
                'message' => 'Teste de save recebido com sucesso',
                'debug' => $debug_info
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Ação não especificada ou inválida: ' . $action,
                'debug' => $debug_info
            ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage(),
        'debug' => $debug_info
    ]);
}
?>