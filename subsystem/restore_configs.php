<?php
/**
 * Script de restauração das configurações do site
 * Deve ser executado APÓS qualquer deploy/atualização
 */

require_once 'config/database.php';

function listBackupFiles() {
    $backupDir = '../backups/configs/';
    if (!is_dir($backupDir)) {
        return [];
    }

    $backupFiles = glob($backupDir . 'site_config_backup_*.json');
    usort($backupFiles, function($a, $b) {
        return filemtime($b) - filemtime($a); // Mais recente primeiro
    });

    return $backupFiles;
}

function restoreFromBackup($backupFile) {
    try {
        $database = new Database();
        $conn = $database->getConnection();

        if (!$conn) {
            throw new Exception('Erro na conexão com o banco de dados');
        }

        // Ler arquivo de backup
        $backupData = json_decode(file_get_contents($backupFile), true);
        if (!$backupData) {
            throw new Exception('Arquivo de backup inválido ou corrompido');
        }

        $configs = $backupData['site_configs'] ?? [];
        $uploadFiles = $backupData['upload_files'] ?? [];

        // Iniciar transação
        $conn->beginTransaction();

        try {
            // Limpar configurações existentes
            $conn->exec("DELETE FROM site_config");

            // Preparar statement de inserção
            $stmt = $conn->prepare("
                INSERT INTO site_config (config_key, config_value, config_type, section, display_name, description)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            // Restaurar configurações
            $restoredConfigs = 0;
            foreach ($configs as $config) {
                $stmt->execute([
                    $config['config_key'],
                    $config['config_value'],
                    $config['config_type'],
                    $config['section'],
                    $config['display_name'],
                    $config['description']
                ]);
                $restoredConfigs++;
            }

            // Commit da transação
            $conn->commit();

            return [
                'success' => true,
                'configs_restored' => $restoredConfigs,
                'upload_files_info' => count($uploadFiles),
                'backup_info' => $backupData['backup_info'] ?? []
            ];

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Interface web
if (!isset($_GET['action'])) {
    $backupFiles = listBackupFiles();

    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Restaurar Configurações</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .backup-item {
                border: 1px solid #ddd;
                padding: 15px;
                margin: 10px 0;
                border-radius: 5px;
                background: #f9f9f9;
            }
            .backup-item:hover { background: #f0f0f0; }
            .success { color: green; background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .error { color: red; background: #ffe8e8; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .warning { color: orange; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .btn { padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; cursor: pointer; border: none; }
            .btn-primary { background: #007bff; color: white; }
            .btn-success { background: #28a745; color: white; }
            .btn-warning { background: #ffc107; color: black; }
        </style>
    </head>
    <body>
        <h1>🔄 Restaurar Configurações do Site</h1>

        <div class='warning'>
            <h3>⚠️ Atenção!</h3>
            <p>Esta operação irá <strong>substituir TODAS</strong> as configurações atuais do site pelas configurações do backup selecionado.</p>
            <p>Certifique-se de fazer um backup das configurações atuais antes de continuar.</p>
        </div>

        <div style='margin: 20px 0;'>
            <a href='backup_configs.php' class='btn btn-warning'>📦 Fazer Backup Atual</a>
        </div>";

    if (empty($backupFiles)) {
        echo "<div class='error'>❌ Nenhum arquivo de backup encontrado!</div>";
        echo "<p>Execute primeiro o script de backup para criar um arquivo de backup.</p>";
    } else {
        echo "<h2>📋 Backups Disponíveis:</h2>";

        foreach ($backupFiles as $backupFile) {
            $fileName = basename($backupFile);
            $fileTime = filemtime($backupFile);
            $fileSize = filesize($backupFile);

            // Tentar ler info do backup
            $backupData = json_decode(file_get_contents($backupFile), true);
            $backupInfo = $backupData['backup_info'] ?? [];
            $configCount = count($backupData['site_configs'] ?? []);
            $uploadCount = count($backupData['upload_files'] ?? []);

            echo "<div class='backup-item'>";
            echo "<h3>📄 $fileName</h3>";
            echo "<p><strong>Data:</strong> " . date('d/m/Y H:i:s', $fileTime) . "</p>";
            echo "<p><strong>Tamanho:</strong> " . number_format($fileSize / 1024, 2) . " KB</p>";
            echo "<p><strong>Configurações:</strong> $configCount</p>";
            echo "<p><strong>Arquivos de upload:</strong> $uploadCount</p>";

            if (!empty($backupInfo['server_info'])) {
                echo "<p><strong>Servidor:</strong> " . $backupInfo['server_info'] . "</p>";
            }

            echo "<div style='margin-top: 10px;'>";
            echo "<a href='?action=restore&file=" . urlencode($fileName) . "' class='btn btn-success' onclick='return confirm(\"Tem certeza que deseja restaurar este backup? Esta ação não pode ser desfeita!\")'>🔄 Restaurar</a>";
            echo "</div>";
            echo "</div>";
        }
    }

    echo "<hr>";
    echo "<p><a href='site-config.php'>← Voltar para configurações</a></p>";
    echo "</body></html>";

} elseif ($_GET['action'] === 'restore') {
    $fileName = $_GET['file'] ?? '';
    $backupFile = '../backups/configs/' . $fileName;

    if (!file_exists($backupFile)) {
        echo "<div class='error'>❌ Arquivo de backup não encontrado!</div>";
        exit;
    }

    $result = restoreFromBackup($backupFile);

    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Resultado da Restauração</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .success { color: green; background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .error { color: red; background: #ffe8e8; padding: 15px; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>";

    if ($result['success']) {
        echo "<div class='success'>";
        echo "<h2>✅ Configurações restauradas com sucesso!</h2>";
        echo "<p><strong>Configurações restauradas:</strong> " . $result['configs_restored'] . "</p>";
        echo "<p><strong>Arquivo de backup:</strong> $fileName</p>";
        echo "</div>";

        if (!empty($result['backup_info'])) {
            echo "<h3>ℹ️ Informações do backup:</h3>";
            echo "<ul>";
            foreach ($result['backup_info'] as $key => $value) {
                echo "<li><strong>$key:</strong> $value</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<div class='error'>";
        echo "<h2>❌ Erro na restauração!</h2>";
        echo "<p>" . $result['error'] . "</p>";
        echo "</div>";
    }

    echo "<hr>";
    echo "<p><a href='site-config.php'>🔧 Ir para configurações</a> | <a href='restore_configs.php'>🔄 Voltar para restauração</a></p>";
    echo "</body></html>";
}
?>