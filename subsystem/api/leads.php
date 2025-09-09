<?php
/**
 * API para gerenciamento de leads
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
            
            if ($action === 'get' || $action === 'details') {
                handleGetLeadDetails($conn, $userRole, $userId);
            } else {
                handleGetLeads($conn, $userRole, $userId);
            }
            break;
            
        case 'POST':
            if (!in_array($userRole, ['admin', 'manager', 'seller'])) {
                throw new Exception('Sem permissão para criar leads');
            }
            handleCreateLead($conn, $userId);
            break;
            
        case 'PUT':
            if (!in_array($userRole, ['admin', 'manager', 'seller'])) {
                throw new Exception('Sem permissão para editar leads');
            }
            handleUpdateLead($conn, $userRole, $userId);
            break;
            
        case 'DELETE':
            if (!in_array($userRole, ['admin', 'manager'])) {
                throw new Exception('Sem permissão para deletar leads');
            }
            handleDeleteLead($conn);
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    error_log("Erro na API de leads: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleGetLeads($conn, $userRole, $userId) {
    // Parâmetros de paginação
    $page = (int)($_GET['page'] ?? 1);
    $limit = min(50, (int)($_GET['limit'] ?? 20)); // Máximo 50 por página
    $offset = ($page - 1) * $limit;
    
    // Filtros
    $search = trim($_GET['search'] ?? '');
    $status = $_GET['status'] ?? '';
    $source = $_GET['source'] ?? '';
    $priority = $_GET['priority'] ?? '';
    $assigned_to = $_GET['assigned_to'] ?? '';
    
    // Construir query base
    $baseQuery = "
        FROM leads l
        LEFT JOIN users u ON l.assigned_to = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Aplicar filtros baseados no papel do usuário
    if (!in_array($userRole, ['admin', 'manager'])) {
        $baseQuery .= " AND (l.assigned_to = ? OR l.assigned_to IS NULL)";
        $params[] = $userId;
    }
    
    // Filtros de busca
    if ($search) {
        $baseQuery .= " AND (l.name LIKE ? OR l.phone LIKE ? OR l.email LIKE ? OR l.vehicle_interest LIKE ?)";
        $searchTerm = "%{$search}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($status) {
        $baseQuery .= " AND l.status = ?";
        $params[] = $status;
    }
    
    if ($source) {
        $baseQuery .= " AND l.source_page = ?";
        $params[] = $source;
    }
    
    if ($priority) {
        $baseQuery .= " AND l.priority = ?";
        $params[] = $priority;
    }
    
    if ($assigned_to) {
        $baseQuery .= " AND l.assigned_to = ?";
        $params[] = $assigned_to;
    }
    
    // Contar total de registros
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery;
    $stmt = $conn->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    
    // Buscar leads com paginação
    $leadsQuery = "
        SELECT 
            l.*,
            u.full_name as assigned_to_name
        " . $baseQuery . "
        ORDER BY l.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    $stmt = $conn->prepare($leadsQuery);
    $stmt->execute($params);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
        'leads' => $leads,
        'pagination' => $pagination
    ], JSON_UNESCAPED_UNICODE);
}

function handleCreateLead($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validação
    $requiredFields = ['name', 'phone'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Campo '{$field}' é obrigatório");
        }
    }
    
    // Verificar se o telefone já existe
    $stmt = $conn->prepare("SELECT id FROM leads WHERE phone = ?");
    $stmt->execute([$input['phone']]);
    if ($stmt->fetch()) {
        throw new Exception('Já existe um lead com este telefone');
    }
    
    // Inserir lead
    $stmt = $conn->prepare("
        INSERT INTO leads (
            name, email, phone, vehicle_interest, has_down_payment, 
            down_payment_value, source_page, status, priority, 
            notes, assigned_to, ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $input['name'],
        $input['email'] ?? null,
        $input['phone'],
        $input['vehicle_interest'] ?? null,
        $input['has_down_payment'] ?? 'no',
        $input['down_payment_value'] ?? null,
        $input['source_page'] ?? 'manual',
        $input['status'] ?? 'new',
        $input['priority'] ?? 'medium',
        $input['notes'] ?? null,
        $input['assigned_to'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    if ($result) {
        $leadId = $conn->lastInsertId();
        
        // Registrar interação de criação
        $stmt = $conn->prepare("
            INSERT INTO lead_interactions (lead_id, user_id, interaction_type, description)
            VALUES (?, ?, 'note', 'Lead criado manualmente no sistema')
        ");
        $stmt->execute([$leadId, $userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Lead criado com sucesso',
            'lead_id' => (int)$leadId
        ]);
    } else {
        throw new Exception('Erro ao criar lead');
    }
}

function handleUpdateLead($conn, $userRole, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $leadId = $input['id'] ?? null;
    
    if (!$leadId) {
        throw new Exception('ID do lead é obrigatório');
    }
    
    // Verificar se o lead existe e se o usuário tem permissão
    $stmt = $conn->prepare("SELECT * FROM leads WHERE id = ?");
    $stmt->execute([$leadId]);
    $lead = $stmt->fetch();
    
    if (!$lead) {
        throw new Exception('Lead não encontrado');
    }
    
    // Verificar permissão
    if (!in_array($userRole, ['admin', 'manager']) && $lead['assigned_to'] != $userId) {
        throw new Exception('Sem permissão para editar este lead');
    }
    
    // Campos que podem ser atualizados
    $updateFields = [];
    $params = [];
    
    $allowedFields = [
        'name', 'email', 'phone', 'vehicle_interest', 'has_down_payment',
        'down_payment_value', 'status', 'priority', 'notes', 'assigned_to'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateFields[] = "{$field} = ?";
            $params[] = $input[$field];
        }
    }
    
    if (empty($updateFields)) {
        throw new Exception('Nenhum campo para atualizar');
    }
    
    // Atualizar lead
    $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
    $params[] = $leadId;
    
    $stmt = $conn->prepare("
        UPDATE leads 
        SET " . implode(', ', $updateFields) . " 
        WHERE id = ?
    ");
    
    $result = $stmt->execute($params);
    
    if ($result) {
        // Registrar interação de atualização
        $changes = [];
        foreach ($allowedFields as $field) {
            if (isset($input[$field]) && $input[$field] != $lead[$field]) {
                $changes[] = "{$field}: '{$lead[$field]}' → '{$input[$field]}'";
            }
        }
        
        if (!empty($changes)) {
            $description = "Lead atualizado: " . implode(', ', $changes);
            $stmt = $conn->prepare("
                INSERT INTO lead_interactions (lead_id, user_id, interaction_type, description)
                VALUES (?, ?, 'note', ?)
            ");
            $stmt->execute([$leadId, $userId, $description]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Lead atualizado com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao atualizar lead');
    }
}

function handleDeleteLead($conn) {
    $leadId = $_GET['id'] ?? null;
    
    if (!$leadId) {
        throw new Exception('ID do lead é obrigatório');
    }
    
    // Verificar se o lead existe
    $stmt = $conn->prepare("SELECT id FROM leads WHERE id = ?");
    $stmt->execute([$leadId]);
    if (!$stmt->fetch()) {
        throw new Exception('Lead não encontrado');
    }
    
    // Deletar lead (as interações serão deletadas automaticamente devido ao CASCADE)
    $stmt = $conn->prepare("DELETE FROM leads WHERE id = ?");
    $result = $stmt->execute([$leadId]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Lead deletado com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao deletar lead');
    }
}

function handleGetLeadDetails($conn, $userRole, $userId) {
    $leadId = $_GET['id'] ?? null;
    
    if (!$leadId) {
        throw new Exception('ID do lead é obrigatório');
    }
    
    // Buscar lead com detalhes
    $stmt = $conn->prepare("
        SELECT 
            l.*,
            u.full_name as assigned_to_name,
            u.email as assigned_to_email
        FROM leads l
        LEFT JOIN users u ON l.assigned_to = u.id
        WHERE l.id = ?
    ");
    $stmt->execute([$leadId]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        throw new Exception('Lead não encontrado');
    }
    
    // Verificar permissão
    if (!in_array($userRole, ['admin', 'manager']) && $lead['assigned_to'] != $userId) {
        throw new Exception('Sem permissão para ver este lead');
    }
    
    // Buscar histórico de interações
    $stmt = $conn->prepare("
        SELECT 
            i.*,
            u.full_name as user_name
        FROM lead_interactions i
        LEFT JOIN users u ON i.user_id = u.id
        WHERE i.lead_id = ?
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([$leadId]);
    $interactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar vendas relacionadas
    $stmt = $conn->prepare("
        SELECT 
            s.*,
            u.full_name as seller_name
        FROM sales s
        LEFT JOIN users u ON s.seller_id = u.id
        WHERE s.lead_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$leadId]);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'lead' => $lead,
        'interactions' => $interactions,
        'sales' => $sales
    ], JSON_UNESCAPED_UNICODE);
}
?>