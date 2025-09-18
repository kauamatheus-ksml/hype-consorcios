<?php
/**
 * API para Gerenciamento de Comissões por Vendedor
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

$auth = new Auth();
$method = $_SERVER['REQUEST_METHOD'];

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
    require_once __DIR__ . '/../config/database.php';

    $database = new Database();
    $conn = $database->getConnection();

    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'list';

            if ($action === 'list') {
                // Listar todos os vendedores e suas configurações de comissão
                if (!in_array($currentUser['role'], ['admin', 'manager'])) {
                    throw new Exception('Acesso negado');
                }

                $stmt = $conn->prepare("
                    SELECT
                        u.id,
                        u.full_name,
                        u.username,
                        u.role,
                        u.status,
                        scs.commission_percentage,
                        scs.commission_installments,
                        scs.min_sale_value,
                        scs.max_sale_value,
                        scs.bonus_percentage,
                        scs.bonus_threshold,
                        scs.is_active,
                        scs.notes,
                        scs.updated_at as commission_updated_at,
                        updater.full_name as updated_by_name
                    FROM users u
                    LEFT JOIN seller_commission_settings scs ON u.id = scs.seller_id
                    LEFT JOIN users updater ON scs.updated_by = updater.id
                    WHERE u.role IN ('seller', 'manager', 'admin')
                    ORDER BY u.full_name
                ");
                $stmt->execute();
                $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success' => true,
                    'sellers' => $sellers
                ]);

            } elseif ($action === 'get') {
                // Obter configuração específica de um vendedor
                $sellerId = $_GET['seller_id'] ?? '';

                if (!$sellerId) {
                    throw new Exception('ID do vendedor é obrigatório');
                }

                // Verificar permissão
                if (!in_array($currentUser['role'], ['admin', 'manager']) && $currentUser['id'] != $sellerId) {
                    throw new Exception('Acesso negado');
                }

                $stmt = $conn->prepare("
                    SELECT
                        scs.*,
                        u.full_name as seller_name,
                        u.username,
                        u.role
                    FROM seller_commission_settings scs
                    JOIN users u ON scs.seller_id = u.id
                    WHERE scs.seller_id = ?
                ");
                $stmt->execute([$sellerId]);
                $config = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$config) {
                    // Criar configuração padrão se não existir
                    $stmt = $conn->prepare("
                        INSERT INTO seller_commission_settings (seller_id, created_by)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$sellerId, $currentUser['id']]);

                    // Buscar novamente
                    $stmt = $conn->prepare("
                        SELECT
                            scs.*,
                            u.full_name as seller_name,
                            u.username,
                            u.role
                        FROM seller_commission_settings scs
                        JOIN users u ON scs.seller_id = u.id
                        WHERE scs.seller_id = ?
                    ");
                    $stmt->execute([$sellerId]);
                    $config = $stmt->fetch(PDO::FETCH_ASSOC);
                }

                echo json_encode([
                    'success' => true,
                    'config' => $config
                ]);

            } else {
                throw new Exception('Ação não encontrada');
            }
            break;

        case 'POST':
        case 'PUT':
            // Apenas admins podem alterar configurações de comissão
            if ($currentUser['role'] !== 'admin') {
                throw new Exception('Apenas administradores podem alterar configurações de comissão');
            }

            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

            if (empty($input['seller_id'])) {
                throw new Exception('ID do vendedor é obrigatório');
            }

            $sellerId = $input['seller_id'];

            // Validar se o vendedor existe
            $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE id = ? AND role IN ('seller', 'manager', 'admin')");
            $stmt->execute([$sellerId]);
            $seller = $stmt->fetch();

            if (!$seller) {
                throw new Exception('Vendedor não encontrado');
            }

            // Validar campos
            $commissionPercentage = floatval($input['commission_percentage'] ?? 1.5);
            $commissionInstallments = intval($input['commission_installments'] ?? 5);
            $minSaleValue = floatval($input['min_sale_value'] ?? 0);
            $maxSaleValue = !empty($input['max_sale_value']) ? floatval($input['max_sale_value']) : null;
            $bonusPercentage = floatval($input['bonus_percentage'] ?? 0);
            $bonusThreshold = !empty($input['bonus_threshold']) ? floatval($input['bonus_threshold']) : null;
            $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;
            $notes = $input['notes'] ?? '';

            // Validações
            if ($commissionPercentage < 0 || $commissionPercentage > 100) {
                throw new Exception('Comissão deve estar entre 0% e 100%');
            }

            if ($commissionInstallments < 1) {
                throw new Exception('Número de parcelas deve ser maior que zero');
            }

            if ($maxSaleValue && $maxSaleValue <= $minSaleValue) {
                throw new Exception('Valor máximo deve ser maior que o valor mínimo');
            }

            if ($bonusPercentage < 0 || $bonusPercentage > 100) {
                throw new Exception('Bônus deve estar entre 0% e 100%');
            }

            // Inserir ou atualizar configuração
            $stmt = $conn->prepare("
                INSERT INTO seller_commission_settings (
                    seller_id, commission_percentage, commission_installments,
                    min_sale_value, max_sale_value, bonus_percentage, bonus_threshold,
                    is_active, notes, created_by, updated_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                commission_percentage = VALUES(commission_percentage),
                commission_installments = VALUES(commission_installments),
                min_sale_value = VALUES(min_sale_value),
                max_sale_value = VALUES(max_sale_value),
                bonus_percentage = VALUES(bonus_percentage),
                bonus_threshold = VALUES(bonus_threshold),
                is_active = VALUES(is_active),
                notes = VALUES(notes),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
            ");

            $stmt->execute([
                $sellerId,
                $commissionPercentage,
                $commissionInstallments,
                $minSaleValue,
                $maxSaleValue,
                $bonusPercentage,
                $bonusThreshold,
                $isActive,
                $notes,
                $currentUser['id'],
                $currentUser['id']
            ]);

            echo json_encode([
                'success' => true,
                'message' => "Configuração de comissão para {$seller['full_name']} atualizada com sucesso"
            ]);
            break;

        default:
            throw new Exception('Método não permitido');
    }

} catch (Exception $e) {
    error_log("Erro na API de comissões: " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>