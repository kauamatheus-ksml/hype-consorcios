<?php
/**
 * API para buscar usuários
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
    
    // Verificar se usuário tem permissão para listar usuários
    if (!in_array($userRole, ['admin', 'manager'])) {
        throw new Exception('Sem permissão para listar usuários');
    }
    
    // Filtros
    $role = $_GET['role'] ?? '';
    $status = $_GET['status'] ?? 'active';
    
    // Base query
    $query = "SELECT id, username, full_name, email, role, status FROM users WHERE 1=1";
    $params = [];
    
    // Filtrar por status
    if ($status) {
        $query .= " AND status = ?";
        $params[] = $status;
    }
    
    // Filtrar por role(s)
    if ($role) {
        $roles = explode(',', $role);
        $placeholders = str_repeat('?,', count($roles) - 1) . '?';
        $query .= " AND role IN ($placeholders)";
        $params = array_merge($params, $roles);
    }
    
    $query .= " ORDER BY full_name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Erro na API de usuários: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>