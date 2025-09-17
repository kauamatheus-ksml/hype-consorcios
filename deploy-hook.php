<?php
/**
 * Script de hook de deploy
 * Este script deve ser executado automaticamente após cada deploy via webhook
 */

// Log do deploy
$logFile = 'deploy.log';
$timestamp = date('Y-m-d H:i:s');

function logMessage($message) {
    global $logFile, $timestamp;
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    logMessage("=== INÍCIO DO DEPLOY HOOK ===");

    // 1. Verificar se existem backups das configurações
    $backupDir = 'backups/configs/';
    $hasBackup = false;

    if (is_dir($backupDir)) {
        $backupFiles = glob($backupDir . 'site_config_backup_*.json');
        if (!empty($backupFiles)) {
            $hasBackup = true;
            $latestBackup = array_reduce($backupFiles, function($latest, $file) {
                return (!$latest || filemtime($file) > filemtime($latest)) ? $file : $latest;
            });
            logMessage("Backup mais recente encontrado: " . basename($latestBackup));
        }
    }

    // 2. Verificar se a tabela de configurações existe
    require_once 'subsystem/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();

    if ($conn) {
        $stmt = $conn->query("SHOW TABLES LIKE 'site_config'");
        $tableExists = $stmt->rowCount() > 0;

        if ($tableExists) {
            $countStmt = $conn->query("SELECT COUNT(*) as total FROM site_config");
            $configCount = $countStmt->fetch()['total'];
            logMessage("Tabela site_config existe com $configCount configurações");

            if ($configCount == 0 && $hasBackup) {
                // Restaurar do backup se a tabela estiver vazia
                logMessage("Tabela vazia, tentando restaurar do backup...");

                $backupData = json_decode(file_get_contents($latestBackup), true);
                if ($backupData && !empty($backupData['site_configs'])) {
                    $stmt = $conn->prepare("
                        INSERT INTO site_config (config_key, config_value, config_type, section, display_name, description)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    $restored = 0;
                    foreach ($backupData['site_configs'] as $config) {
                        $stmt->execute([
                            $config['config_key'],
                            $config['config_value'],
                            $config['config_type'],
                            $config['section'],
                            $config['display_name'],
                            $config['description']
                        ]);
                        $restored++;
                    }
                    logMessage("$restored configurações restauradas automaticamente");
                }
            }
        } else {
            logMessage("Tabela site_config não existe, criando...");
            // Executar script de instalação
            include 'subsystem/create_site_config_table.php';
            logMessage("Tabela criada e configurações iniciais inseridas");
        }
    } else {
        logMessage("ERRO: Não foi possível conectar ao banco de dados");
    }

    // 3. Verificar/criar diretórios necessários
    $requiredDirs = [
        'assets/images/admin/',
        'assets/videos/admin/',
        'backups/configs/'
    ];

    foreach ($requiredDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            logMessage("Diretório criado: $dir");
        }
    }

    // 4. Definir permissões corretas
    chmod('assets/images/admin/', 0755);
    chmod('assets/videos/admin/', 0755);
    chmod('backups/', 0755);

    logMessage("Deploy hook executado com sucesso");
    logMessage("=== FIM DO DEPLOY HOOK ===\n");

    // Se executado via web, mostrar resultado
    if (!isset($argv)) {
        echo json_encode([
            'success' => true,
            'message' => 'Deploy hook executado com sucesso',
            'timestamp' => $timestamp,
            'has_backup' => $hasBackup,
            'config_count' => $configCount ?? 0
        ]);
    }

} catch (Exception $e) {
    $error = "ERRO no deploy hook: " . $e->getMessage();
    logMessage($error);

    if (!isset($argv)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $error,
            'timestamp' => $timestamp
        ]);
    }
}
?>