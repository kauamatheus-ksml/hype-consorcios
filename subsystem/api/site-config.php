<?php
/**
 * API para gerenciar configurações do site
 * Hype Consórcios
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar autenticação
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

if (!$authenticated || $userRole !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado. Apenas administradores podem acessar esta funcionalidade.'
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
            handleGetConfigs($conn);
            break;

        case 'save':
            handleSaveConfigs($conn);
            break;

        case 'get_by_section':
            $section = $_GET['section'] ?? '';
            handleGetConfigsBySection($conn, $section);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Ação não especificada ou inválida'
            ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

function handleGetConfigs($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT config_key, config_value, config_type, section, display_name, description
            FROM site_config
            ORDER BY section, display_name
        ");

        $stmt->execute();
        $results = $stmt->fetchAll();

        $configs = [];
        foreach ($results as $row) {
            $configs[$row['config_key']] = $row;
        }

        echo json_encode([
            'success' => true,
            'configs' => $configs
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao buscar configurações: ' . $e->getMessage()
        ]);
    }
}

function handleGetConfigsBySection($conn, $section) {
    try {
        $stmt = $conn->prepare("
            SELECT config_key, config_value, config_type, display_name, description
            FROM site_config
            WHERE section = ?
            ORDER BY display_name
        ");

        $stmt->execute([$section]);
        $results = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'configs' => $results
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao buscar configurações da seção: ' . $e->getMessage()
        ]);
    }
}

function handleSaveConfigs($conn) {
    try {
        $section = $_POST['section'] ?? '';

        if (empty($section)) {
            throw new Exception('Seção não especificada');
        }

        // Buscar configurações da seção
        $stmt = $conn->prepare("
            SELECT config_key, config_type
            FROM site_config
            WHERE section = ?
        ");
        $stmt->execute([$section]);
        $sectionConfigs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $updatedConfigs = [];
        $baseUploadDir = '../../assets/';
        $imageUploadDir = $baseUploadDir . 'images/admin/';
        $videoUploadDir = $baseUploadDir . 'videos/admin/';

        // Criar diretórios de upload se não existirem
        if (!is_dir($imageUploadDir)) {
            mkdir($imageUploadDir, 0755, true);
        }
        if (!is_dir($videoUploadDir)) {
            mkdir($videoUploadDir, 0755, true);
        }

        // Preparar statement de atualização
        $updateStmt = $conn->prepare("
            UPDATE site_config
            SET config_value = ?, updated_at = CURRENT_TIMESTAMP
            WHERE config_key = ?
        ");

        foreach ($sectionConfigs as $configKey => $configType) {
            $newValue = null;

            if ($configType === 'image') {
                // Processar upload de imagem
                if (isset($_FILES[$configKey]) && $_FILES[$configKey]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES[$configKey];
                    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $file['name']);

                    // Validar tipo de arquivo baseado na configuração ou tipo MIME
                    if (strpos($configKey, 'video') !== false || strpos($file['type'], 'video/') === 0) {
                        // Para vídeos
                        $allowedTypes = ['video/mp4', 'video/webm', 'video/avi', 'video/mov'];
                        $maxSize = 50 * 1024 * 1024; // 50MB para vídeos
                        $targetPath = $videoUploadDir . $fileName;
                        $relativePath = 'assets/videos/admin/' . $fileName;
                    } else {
                        // Para imagens
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        $maxSize = 5 * 1024 * 1024; // 5MB para imagens
                        $targetPath = $imageUploadDir . $fileName;
                        $relativePath = 'assets/images/admin/' . $fileName;
                    }

                    if (!in_array($file['type'], $allowedTypes)) {
                        throw new Exception('Tipo de arquivo não permitido para ' . $configKey . '. Tipos aceitos: ' . implode(', ', $allowedTypes));
                    }

                    // Validar tamanho
                    if ($file['size'] > $maxSize) {
                        $maxSizeMB = $maxSize / (1024 * 1024);
                        throw new Exception('Arquivo muito grande para ' . $configKey . ' (máximo ' . $maxSizeMB . 'MB)');
                    }

                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $newValue = $relativePath;

                        // Excluir imagem anterior se existir
                        $currentValue = $_POST[$configKey . '_current'] ?? '';
                        if ($currentValue && file_exists('../../' . $currentValue)) {
                            unlink('../../' . $currentValue);
                        }
                    } else {
                        throw new Exception('Erro ao fazer upload da imagem ' . $configKey);
                    }
                } else {
                    // Manter imagem atual se não foi enviada nova
                    $newValue = $_POST[$configKey . '_current'] ?? '';
                }
            } else {
                // Processar campos de texto
                $newValue = $_POST[$configKey] ?? '';
            }

            // Atualizar no banco
            $updateStmt->execute([$newValue, $configKey]);
            $updatedConfigs[$configKey] = $newValue;
        }

        // Buscar configurações atualizadas
        $stmt = $conn->prepare("
            SELECT config_key, config_value, config_type, section, display_name, description
            FROM site_config
            WHERE section = ?
        ");
        $stmt->execute([$section]);
        $results = $stmt->fetchAll();

        $configs = [];
        foreach ($results as $row) {
            $configs[$row['config_key']] = $row;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Configurações salvas com sucesso',
            'configs' => $configs,
            'updated_count' => count($updatedConfigs)
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao salvar configurações: ' . $e->getMessage()
        ]);
    }
}

/**
 * Função para obter configuração específica (para usar no site)
 */
function getSiteConfig($configKey, $defaultValue = '') {
    static $configs = null;

    if ($configs === null) {
        try {
            $database = new Database();
            $conn = $database->getConnection();

            if ($conn) {
                $stmt = $conn->prepare("SELECT config_key, config_value FROM site_config");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                $configs = $results;
            } else {
                $configs = [];
            }
        } catch (Exception $e) {
            $configs = [];
        }
    }

    return $configs[$configKey] ?? $defaultValue;
}

/**
 * Função para obter todas as configurações (para usar no site)
 */
function getAllSiteConfigs() {
    static $configs = null;

    if ($configs === null) {
        try {
            $database = new Database();
            $conn = $database->getConnection();

            if ($conn) {
                $stmt = $conn->prepare("SELECT config_key, config_value FROM site_config");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                $configs = $results;
            } else {
                $configs = [];
            }
        } catch (Exception $e) {
            $configs = [];
        }
    }

    return $configs;
}
?>