<?php
/**
 * Instalador do Banco de Dados
 * Sistema CRM - Hype Consórcios
 */

require_once 'config/database.php';

class DatabaseInstaller {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function install() {
        try {
            $conn = $this->db->getConnection();
            if (!$conn) {
                throw new Exception("Não foi possível conectar ao banco de dados");
            }
            
            // Ler arquivo SQL
            $sql = file_get_contents('database_setup.sql');
            if (!$sql) {
                throw new Exception("Não foi possível ler o arquivo database_setup.sql");
            }
            
            // Dividir em comandos individuais
            $commands = array_filter(array_map('trim', explode(';', $sql)));
            
            $results = [];
            $conn->beginTransaction();
            
            foreach ($commands as $command) {
                if (empty($command) || strpos($command, '--') === 0) continue;
                
                try {
                    $stmt = $conn->prepare($command);
                    $stmt->execute();
                    
                    // Identificar tipo de comando
                    $commandType = strtoupper(substr(trim($command), 0, 6));
                    if (in_array($commandType, ['CREATE', 'INSERT', 'ALTER '])) {
                        $results[] = [
                            'type' => $commandType,
                            'status' => 'success',
                            'message' => 'Comando executado com sucesso',
                            'command' => substr($command, 0, 100) . '...'
                        ];
                    }
                    
                } catch (PDOException $e) {
                    // Ignorar erros de "já existe" para CREATE TABLE
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate entry') === false) {
                        throw $e;
                    }
                    
                    $results[] = [
                        'type' => 'INFO',
                        'status' => 'skipped',
                        'message' => 'Já existe: ' . $e->getMessage(),
                        'command' => substr($command, 0, 100) . '...'
                    ];
                }
            }
            
            $conn->commit();
            
            return [
                'success' => true,
                'message' => 'Banco de dados instalado com sucesso!',
                'results' => $results
            ];
            
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            }
            
            return [
                'success' => false,
                'message' => 'Erro na instalação: ' . $e->getMessage(),
                'results' => isset($results) ? $results : []
            ];
        }
    }
    
    public function checkTables() {
        try {
            $conn = $this->db->getConnection();
            
            $requiredTables = ['users', 'leads', 'sales', 'lead_interactions', 'user_sessions', 'system_settings'];
            $existingTables = [];
            $missingTables = [];
            
            $stmt = $conn->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($requiredTables as $table) {
                if (in_array($table, $tables)) {
                    $existingTables[] = $table;
                } else {
                    $missingTables[] = $table;
                }
            }
            
            return [
                'existing' => $existingTables,
                'missing' => $missingTables,
                'all_tables' => $tables
            ];
            
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}

// Interface web simples
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $installer = new DatabaseInstaller();
    $result = $installer->install();
    
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$installer = new DatabaseInstaller();
$tableCheck = $installer->checkTables();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador do Banco - Hype Consórcios CRM</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 40px; }
        .status-card { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .success { border-left: 4px solid #28a745; background: #d4edda; }
        .error { border-left: 4px solid #dc3545; background: #f8d7da; }
        .warning { border-left: 4px solid #ffc107; background: #fff3cd; }
        .info { border-left: 4px solid #17a2b8; background: #d1ecf1; }
        .btn { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .table-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
        .table-item { padding: 8px 12px; background: #e9ecef; border-radius: 4px; font-family: monospace; }
        .table-item.exists { background: #d4edda; color: #155724; }
        .table-item.missing { background: #f8d7da; color: #721c24; }
        #result { margin-top: 20px; }
        .command-log { max-height: 400px; overflow-y: auto; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🗄️ Instalador do Banco de Dados</h1>
        <p>Sistema CRM - Hype Consórcios</p>
    </div>

    <?php if (isset($tableCheck['error'])): ?>
        <div class="status-card error">
            <h3>❌ Erro de Conexão</h3>
            <p><?php echo htmlspecialchars($tableCheck['error']); ?></p>
        </div>
    <?php else: ?>
        <div class="status-card info">
            <h3>📊 Status das Tabelas</h3>
            
            <?php if (count($tableCheck['existing']) > 0): ?>
                <h4>✅ Tabelas Existentes (<?php echo count($tableCheck['existing']); ?>):</h4>
                <div class="table-list">
                    <?php foreach ($tableCheck['existing'] as $table): ?>
                        <div class="table-item exists"><?php echo $table; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($tableCheck['missing']) > 0): ?>
                <h4>❌ Tabelas Faltando (<?php echo count($tableCheck['missing']); ?>):</h4>
                <div class="table-list">
                    <?php foreach ($tableCheck['missing'] as $table): ?>
                        <div class="table-item missing"><?php echo $table; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <h4>🗃️ Todas as Tabelas no Banco (<?php echo count($tableCheck['all_tables']); ?>):</h4>
            <div class="table-list">
                <?php foreach ($tableCheck['all_tables'] as $table): ?>
                    <div class="table-item"><?php echo $table; ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="status-card">
            <h3>🚀 Instalação</h3>
            <p>Clique no botão abaixo para instalar/atualizar a estrutura do banco de dados:</p>
            <button class="btn" onclick="installDatabase()">Instalar/Atualizar Banco de Dados</button>
            
            <div id="result"></div>
        </div>
    <?php endif; ?>

    <div class="status-card warning">
        <h3>⚠️ Informações Importantes</h3>
        <ul>
            <li><strong>Usuário Admin Padrão:</strong> admin / admin@hypeconsorcios.com.br</li>
            <li><strong>Senha Padrão:</strong> password (altere imediatamente após o primeiro login)</li>
            <li><strong>Fuso Horário:</strong> Configurado para -3 horas (Brasil)</li>
            <li><strong>Backup:</strong> Sempre faça backup antes de executar alterações</li>
        </ul>
    </div>

    <script>
        function installDatabase() {
            const btn = event.target;
            const result = document.getElementById('result');
            
            btn.disabled = true;
            btn.textContent = 'Instalando...';
            result.innerHTML = '<div class="status-card info"><p>⏳ Instalando banco de dados...</p></div>';
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.textContent = 'Instalar/Atualizar Banco de Dados';
                
                let html = '';
                if (data.success) {
                    html += '<div class="status-card success">';
                    html += '<h3>✅ ' + data.message + '</h3>';
                } else {
                    html += '<div class="status-card error">';
                    html += '<h3>❌ ' + data.message + '</h3>';
                }
                
                if (data.results && data.results.length > 0) {
                    html += '<h4>📋 Log de Execução:</h4>';
                    html += '<div class="command-log">';
                    
                    data.results.forEach(result => {
                        const icon = result.status === 'success' ? '✅' : result.status === 'skipped' ? '⏭️' : '❌';
                        html += `<div>${icon} [${result.type}] ${result.message}</div>`;
                        if (result.command) {
                            html += `<div style="margin-left: 20px; color: #666; font-size: 11px;">${result.command}</div>`;
                        }
                        html += '<br>';
                    });
                    
                    html += '</div>';
                }
                
                html += '</div>';
                html += '<div style="text-align: center; margin-top: 20px;"><button class="btn" onclick="location.reload()">🔄 Atualizar Página</button></div>';
                
                result.innerHTML = html;
            })
            .catch(error => {
                btn.disabled = false;
                btn.textContent = 'Instalar/Atualizar Banco de Dados';
                result.innerHTML = `<div class="status-card error"><h3>❌ Erro na Requisição</h3><p>${error.message}</p></div>`;
            });
        }
    </script>
</body>
</html>