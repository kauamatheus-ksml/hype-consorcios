<?php
/**
 * API para buscar usuários
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
    
    // Verificar se usuário tem permissão para acessar usuários
    if (!in_array($userRole, ['admin', 'manager'])) {
        throw new Exception('Sem permissão para acessar usuários');
    }

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            handleGetUsers($conn, $userRole);
            break;
        case 'POST':
            handleCreateOrUpdateUser($conn, $userRole);
            break;
        case 'DELETE':
            handleDeleteUser($conn, $userRole);
            break;
        default:
            throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    error_log("Erro na API de usuários: " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleGetUsers($conn, $userRole) {
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
}

function handleCreateOrUpdateUser($conn, $userRole) {
    // Apenas admin pode criar/editar usuários
    if ($userRole !== 'admin') {
        throw new Exception('Apenas administradores podem criar/editar usuários');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    $userId = $input['id'] ?? null;
    $fullName = trim($input['full_name'] ?? '');
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $role = $input['role'] ?? '';
    $status = $input['status'] ?? 'active';
    $password = $input['password'] ?? '';

    // Validações
    if (empty($fullName)) {
        throw new Exception('Nome completo é obrigatório');
    }
    if (empty($username)) {
        throw new Exception('Nome de usuário é obrigatório');
    }
    if (empty($role)) {
        throw new Exception('Função é obrigatória');
    }
    if (!in_array($role, ['admin', 'manager', 'seller', 'viewer'])) {
        throw new Exception('Função inválida');
    }
    if (!in_array($status, ['active', 'inactive'])) {
        throw new Exception('Status inválido');
    }

    if ($userId) {
        // Atualizar usuário existente
        $query = "UPDATE users SET full_name = ?, username = ?, email = ?, role = ?, status = ?";
        $params = [$fullName, $username, $email, $role, $status];

        if (!empty($password)) {
            if (strlen($password) < 6) {
                throw new Exception('Senha deve ter pelo menos 6 caracteres');
            }
            $query .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $query .= " WHERE id = ?";
        $params[] = $userId;

        // Verificar se usuário existe
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $checkStmt->execute([$userId]);
        if (!$checkStmt->fetch()) {
            throw new Exception('Usuário não encontrado');
        }

        // Verificar se username já existe em outro usuário
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $checkStmt->execute([$username, $userId]);
        if ($checkStmt->fetch()) {
            throw new Exception('Nome de usuário já existe');
        }

    } else {
        // Criar novo usuário
        if (empty($password)) {
            throw new Exception('Senha é obrigatória para novo usuário');
        }
        if (strlen($password) < 6) {
            throw new Exception('Senha deve ter pelo menos 6 caracteres');
        }

        // Verificar se username já existe
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->execute([$username]);
        if ($checkStmt->fetch()) {
            throw new Exception('Nome de usuário já existe');
        }

        $query = "INSERT INTO users (full_name, username, email, role, status, password) VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$fullName, $username, $email, $role, $status, password_hash($password, PASSWORD_DEFAULT)];
    }

    $stmt = $conn->prepare($query);
    $result = $stmt->execute($params);

    if (!$result) {
        throw new Exception('Erro ao salvar usuário');
    }

    echo json_encode([
        'success' => true,
        'message' => $userId ? 'Usuário atualizado com sucesso' : 'Usuário criado com sucesso'
    ], JSON_UNESCAPED_UNICODE);
}

function handleDeleteUser($conn, $userRole) {
    // Apenas admin pode excluir usuários
    if ($userRole !== 'admin') {
        throw new Exception('Apenas administradores podem excluir usuários');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['id'] ?? null;

    if (!$userId) {
        throw new Exception('ID do usuário é obrigatório');
    }

    // Verificar se usuário existe
    $checkStmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
    $checkStmt->execute([$userId]);
    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Usuário não encontrado');
    }

    // Não permitir excluir o próprio usuário (se implementar verificação de usuário atual)
    // TODO: Implementar verificação para não permitir auto-exclusão

    // Excluir usuário
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $result = $stmt->execute([$userId]);

    if (!$result) {
        throw new Exception('Erro ao excluir usuário');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Usuário excluído com sucesso'
    ], JSON_UNESCAPED_UNICODE);
}
?>