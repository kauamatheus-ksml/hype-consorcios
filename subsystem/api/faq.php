<?php
/**
 * API para gerenciamento de FAQs
 * Hype Consórcios - Sistema CRM
 */

require_once '../config/database.php';

// Iniciar sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
$authenticated = false;
$user = null;

if (isset($_COOKIE['crm_session'])) {
    require_once '../classes/Auth.php';
    $auth = new Auth();
    $sessionResult = $auth->validateSession($_COOKIE['crm_session']);

    if ($sessionResult['success']) {
        $authenticated = true;
        $user = $sessionResult['user'];
    }
} elseif (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $authenticated = true;
    $user = [
        'role' => $_SESSION['user_role'] ?? 'viewer',
        'full_name' => $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Usuário'
    ];
}

// Verificar se está autenticado e é admin
if (!$authenticated || ($user['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Configurar para não mostrar warnings/notices como HTML
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception('Falha na conexão com o banco de dados');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro na conexão: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // Verificar se tabela existe primeiro
                try {
                    $stmt = $conn->query("SHOW TABLES LIKE 'faqs'");
                    $tableExists = $stmt->rowCount() > 0;

                    if (!$tableExists) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Tabela de FAQs não encontrada. Execute create_faq_table.php primeiro.',
                            'needsSetup' => true
                        ]);
                        break;
                    }

                    // Listar todas as FAQs
                    $stmt = $conn->query("SELECT * FROM faqs ORDER BY display_order, id");
                    $faqs = $stmt->fetchAll();
                    echo json_encode(['success' => true, 'data' => $faqs]);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erro ao acessar tabela: ' . $e->getMessage(),
                        'needsSetup' => true
                    ]);
                }
            } else {
                // Buscar FAQ específica
                $id = $_GET['id'] ?? 0;
                $stmt = $conn->prepare("SELECT * FROM faqs WHERE id = ?");
                $stmt->execute([$id]);
                $faq = $stmt->fetch();

                if ($faq) {
                    echo json_encode(['success' => true, 'data' => $faq]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'FAQ não encontrada']);
                }
            }
            break;

        case 'POST':
            switch ($action) {
                case 'create':
                    // Criar nova FAQ
                    $question = $_POST['question'] ?? '';
                    $answer = $_POST['answer'] ?? '';
                    $display_order = $_POST['display_order'] ?? 0;
                    $is_active = isset($_POST['is_active']) ? 1 : 0;

                    if (empty($question) || empty($answer)) {
                        echo json_encode(['success' => false, 'message' => 'Pergunta e resposta são obrigatórias']);
                        break;
                    }

                    $stmt = $conn->prepare("
                        INSERT INTO faqs (question, answer, display_order, is_active)
                        VALUES (?, ?, ?, ?)
                    ");

                    if ($stmt->execute([$question, $answer, $display_order, $is_active])) {
                        echo json_encode(['success' => true, 'message' => 'FAQ criada com sucesso', 'id' => $conn->lastInsertId()]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Erro ao criar FAQ']);
                    }
                    break;

                case 'update':
                    // Atualizar FAQ existente
                    $id = $_POST['id'] ?? 0;
                    $question = $_POST['question'] ?? '';
                    $answer = $_POST['answer'] ?? '';
                    $display_order = $_POST['display_order'] ?? 0;
                    $is_active = isset($_POST['is_active']) ? 1 : 0;

                    if ($id <= 0 || empty($question) || empty($answer)) {
                        echo json_encode(['success' => false, 'message' => 'ID, pergunta e resposta são obrigatórios']);
                        break;
                    }

                    $stmt = $conn->prepare("
                        UPDATE faqs
                        SET question = ?, answer = ?, display_order = ?, is_active = ?
                        WHERE id = ?
                    ");

                    if ($stmt->execute([$question, $answer, $display_order, $is_active, $id])) {
                        echo json_encode(['success' => true, 'message' => 'FAQ atualizada com sucesso']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar FAQ']);
                    }
                    break;

                case 'delete':
                    // Deletar FAQ
                    $id = $_POST['id'] ?? 0;

                    if ($id <= 0) {
                        echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
                        break;
                    }

                    $stmt = $conn->prepare("DELETE FROM faqs WHERE id = ?");

                    if ($stmt->execute([$id])) {
                        echo json_encode(['success' => true, 'message' => 'FAQ deletada com sucesso']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Erro ao deletar FAQ']);
                    }
                    break;

                case 'reorder':
                    // Reordenar FAQs
                    $orders = $_POST['orders'] ?? [];

                    if (empty($orders)) {
                        echo json_encode(['success' => false, 'message' => 'Dados de ordenação não fornecidos']);
                        break;
                    }

                    $conn->beginTransaction();
                    $stmt = $conn->prepare("UPDATE faqs SET display_order = ? WHERE id = ?");

                    foreach ($orders as $order) {
                        $stmt->execute([$order['display_order'], $order['id']]);
                    }

                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Ordem atualizada com sucesso']);
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => 'Ação não especificada']);
                    break;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>