<?php
/**
 * API para Configurações do Sistema
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
            $setting = $_GET['setting'] ?? '';

            if ($setting === 'default_commission_rate') {
                // Buscar taxa padrão de comissão
                $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
                $stmt->execute(['default_commission_rate']);
                $result = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'value' => $result ? floatval($result['setting_value']) : 1.5
                ]);
            } else {
                // Buscar todas as configurações (apenas para admins)
                if ($currentUser['role'] !== 'admin') {
                    throw new Exception('Acesso negado');
                }

                $stmt = $conn->prepare("SELECT * FROM system_settings ORDER BY setting_key");
                $stmt->execute();
                $settings = $stmt->fetchAll();

                echo json_encode([
                    'success' => true,
                    'settings' => $settings
                ]);
            }
            break;

        case 'POST':
        case 'PUT':
            // Apenas admins podem alterar configurações
            if ($currentUser['role'] !== 'admin') {
                throw new Exception('Apenas administradores podem alterar configurações');
            }

            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

            if (empty($input['setting_key']) || !isset($input['setting_value'])) {
                throw new Exception('setting_key e setting_value são obrigatórios');
            }

            $settingKey = $input['setting_key'];
            $settingValue = $input['setting_value'];
            $description = $input['description'] ?? '';

            // Validar configurações específicas
            if ($settingKey === 'default_commission_rate') {
                $settingValue = floatval($settingValue);
                if ($settingValue < 0 || $settingValue > 100) {
                    throw new Exception('Taxa de comissão deve estar entre 0% e 100%');
                }
            }

            // Inserir ou atualizar configuração
            $stmt = $conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value, description, updated_by)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                description = VALUES(description),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
            ");

            $stmt->execute([$settingKey, $settingValue, $description, $currentUser['id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Configuração atualizada com sucesso'
            ]);
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