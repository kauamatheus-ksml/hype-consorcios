<?php
/**
 * API para gerenciamento de perfil do usuário
 * Hype Consórcios CRM
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
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
    require_once __DIR__ . '/../classes/AuditLogger.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    $auditLogger = new AuditLogger($conn);
    
    $userId = $user['id'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetProfile($conn, $userId);
            break;
            
        case 'PUT':
            handleUpdateProfile($conn, $userId, $auditLogger);
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    error_log("Erro na API de perfil: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleGetProfile($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT id, username, email, full_name, role, status, created_at, updated_at, last_login
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('Usuário não encontrado');
    }
    
    // Remover dados sensíveis
    unset($user['password_hash']);
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ], JSON_UNESCAPED_UNICODE);
}

function handleUpdateProfile($conn, $userId, $auditLogger) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados inválidos');
    }
    
    // Buscar dados atuais do usuário
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentUser) {
        throw new Exception('Usuário não encontrado');
    }
    
    // Validações básicas
    if (empty($input['full_name'])) {
        throw new Exception('Nome completo é obrigatório');
    }
    
    if (empty($input['email'])) {
        throw new Exception('Email é obrigatório');
    }
    
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    
    // Verificar se o email já existe em outro usuário
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$input['email'], $userId]);
    if ($stmt->fetch()) {
        throw new Exception('Este email já está sendo usado por outro usuário');
    }
    
    // Inicializar arrays de atualização
    $updateFields = [];
    $updateParams = [];
    $changes = [];
    
    // Verificar mudanças nos dados básicos
    if ($input['full_name'] !== $currentUser['full_name']) {
        $updateFields[] = "full_name = ?";
        $updateParams[] = trim($input['full_name']);
        $changes[] = "Nome: '{$currentUser['full_name']}' → '{$input['full_name']}'";
    }
    
    if ($input['email'] !== $currentUser['email']) {
        $updateFields[] = "email = ?";
        $updateParams[] = trim($input['email']);
        $changes[] = "Email: '{$currentUser['email']}' → '{$input['email']}'";
    }
    
    // Gerenciar mudança de senha
    $passwordChanged = false;
    if (!empty($input['current_password']) && !empty($input['new_password'])) {
        // Verificar senha atual
        if (!password_verify($input['current_password'], $currentUser['password_hash'])) {
            throw new Exception('Senha atual incorreta');
        }
        
        // Validar nova senha
        if (strlen($input['new_password']) < 6) {
            throw new Exception('Nova senha deve ter pelo menos 6 caracteres');
        }
        
        if ($input['new_password'] !== $input['confirm_password']) {
            throw new Exception('Confirmação de senha não confere');
        }
        
        // Adicionar hash da nova senha
        $newPasswordHash = password_hash($input['new_password'], PASSWORD_DEFAULT);
        if (!$newPasswordHash) {
            throw new Exception('Erro ao gerar hash da nova senha');
        }
        
        $updateFields[] = "password_hash = ?";
        $updateParams[] = $newPasswordHash;
        $changes[] = "Senha alterada";
        $passwordChanged = true;
    }
    
    // Se não há nada para atualizar
    if (empty($updateFields)) {
        echo json_encode([
            'success' => true,
            'message' => 'Nenhuma alteração detectada'
        ]);
        return;
    }
    
    // Adicionar timestamp de atualização
    $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
    $updateParams[] = $userId;
    
    // Executar atualização
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($updateParams);
    
    if (!$result) {
        throw new Exception('Erro ao atualizar perfil');
    }
    
    // Atualizar dados da sessão se necessário
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        if (isset($input['full_name'])) {
            $_SESSION['full_name'] = $input['full_name'];
        }
        if (isset($input['email'])) {
            $_SESSION['email'] = $input['email'];
        }
    }
    
    // Log da atividade no sistema de auditoria
    $auditLogger->logProfileUpdate($userId, $currentUser, array_merge($currentUser, $input), $changes);
    
    // Log adicional para arquivo
    $logMessage = "Perfil atualizado: " . implode(', ', $changes);
    error_log("Usuário {$userId}: {$logMessage}");
    
    // Resposta de sucesso
    $response = [
        'success' => true,
        'message' => 'Perfil atualizado com sucesso',
        'changes' => $changes
    ];
    
    // Se a senha foi alterada, mencionar isso especificamente
    if ($passwordChanged) {
        $response['password_changed'] = true;
        $response['message'] = 'Perfil e senha atualizados com sucesso';
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>