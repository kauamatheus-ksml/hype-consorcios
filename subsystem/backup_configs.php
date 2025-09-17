<?php
/**
 * Script de backup das configurações do site
 * Deve ser executado ANTES de qualquer deploy/atualização
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception('Erro na conexão com o banco de dados');
    }

    // Criar diretório de backup se não existir
    $backupDir = '../backups/configs/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    // Nome do arquivo de backup com timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . "site_config_backup_$timestamp.json";

    // Buscar todas as configurações
    $stmt = $conn->prepare("
        SELECT config_key, config_value, config_type, section, display_name, description,
               created_at, updated_at
        FROM site_config
        ORDER BY section, config_key
    ");
    $stmt->execute();
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Criar backup dos arquivos de upload também
    $uploadFiles = [];
    $uploadDirs = [
        '../assets/images/admin/',
        '../assets/videos/admin/'
    ];

    foreach ($uploadDirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $relativePath = str_replace('../', '', $file);
                    $uploadFiles[] = [
                        'path' => $relativePath,
                        'size' => filesize($file),
                        'modified' => filemtime($file),
                        'exists' => true
                    ];
                }
            }
        }
    }

    // Criar estrutura do backup
    $backupData = [
        'backup_info' => [
            'created_at' => date('Y-m-d H:i:s'),
            'timestamp' => $timestamp,
            'total_configs' => count($configs),
            'total_upload_files' => count($uploadFiles),
            'php_version' => PHP_VERSION,
            'server_info' => $_SERVER['SERVER_NAME'] ?? 'unknown'
        ],
        'site_configs' => $configs,
        'upload_files' => $uploadFiles
    ];

    // Salvar backup em JSON
    file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Criar backup SQL também
    $sqlFile = $backupDir . "site_config_backup_$timestamp.sql";
    $sqlContent = "-- Backup das configurações do site - $timestamp\n\n";
    $sqlContent .= "-- Limpar tabela existente\n";
    $sqlContent .= "DELETE FROM site_config;\n\n";
    $sqlContent .= "-- Inserir configurações\n";

    foreach ($configs as $config) {
        $key = addslashes($config['config_key']);
        $value = addslashes($config['config_value']);
        $type = addslashes($config['config_type']);
        $section = addslashes($config['section']);
        $name = addslashes($config['display_name']);
        $description = addslashes($config['description']);

        $sqlContent .= "INSERT INTO site_config (config_key, config_value, config_type, section, display_name, description) VALUES ";
        $sqlContent .= "('$key', '$value', '$type', '$section', '$name', '$description');\n";
    }

    file_put_contents($sqlFile, $sqlContent);

    // Limpar backups antigos (manter apenas os últimos 10)
    $backupFiles = glob($backupDir . 'site_config_backup_*.json');
    if (count($backupFiles) > 10) {
        usort($backupFiles, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        $filesToDelete = array_slice($backupFiles, 0, count($backupFiles) - 10);
        foreach ($filesToDelete as $file) {
            unlink($file);
            $sqlFile = str_replace('.json', '.sql', $file);
            if (file_exists($sqlFile)) {
                unlink($sqlFile);
            }
        }
    }

    // Retornar resultado
    if (php_sapi_name() === 'cli') {
        // Executado via linha de comando
        echo "✅ Backup criado com sucesso!\n";
        echo "📄 JSON: $backupFile\n";
        echo "🗃️ SQL: $sqlFile\n";
        echo "📊 Configurações: " . count($configs) . "\n";
        echo "📁 Arquivos: " . count($uploadFiles) . "\n";
    } else {
        // Executado via browser
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Backup das Configurações</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .success { color: green; background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0; }
                .info { background: #e8f0ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
                .file-list { background: #f5f5f5; padding: 15px; border-radius: 5px; }
            </style>
        </head>
        <body>
            <h1>💾 Backup das Configurações</h1>

            <div class='success'>
                <h2>✅ Backup criado com sucesso!</h2>
                <p><strong>Timestamp:</strong> $timestamp</p>
                <p><strong>Configurações:</strong> " . count($configs) . "</p>
                <p><strong>Arquivos de upload:</strong> " . count($uploadFiles) . "</p>
            </div>

            <div class='info'>
                <h3>📁 Arquivos de backup criados:</h3>
                <div class='file-list'>
                    <p><strong>JSON:</strong> " . basename($backupFile) . "</p>
                    <p><strong>SQL:</strong> " . basename($sqlFile) . "</p>
                    <p><strong>Localização:</strong> $backupDir</p>
                </div>
            </div>

            <hr>
            <p><a href='site-config.php'>← Voltar para configurações</a></p>
        </body>
        </html>";
    }

} catch (Exception $e) {
    $error = "❌ Erro no backup: " . $e->getMessage();

    if (php_sapi_name() === 'cli') {
        echo $error . "\n";
        exit(1);
    } else {
        echo "<div style='color: red; background: #ffe8e8; padding: 15px; border-radius: 5px;'>$error</div>";
    }
}
?>