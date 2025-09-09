<?php
/**
 * Teste de Conexão com o Banco de Dados
 * Hype Consórcios - Sistema de Teste
 */

// Incluir a classe de conexão
require_once 'config/database.php';

// Cabeçalho HTML
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Conexão - Hype Consórcios</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 2.5em;
        }
        .header p {
            color: #666;
            margin: 10px 0 0 0;
        }
        .test-result {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .success {
            border-left: 5px solid #28a745;
            background: #d4edda;
            color: #155724;
        }
        .error {
            border-left: 5px solid #dc3545;
            background: #f8d7da;
            color: #721c24;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
            font-size: 0.9em;
        }
        .info-value {
            margin-top: 5px;
            color: #333;
            font-family: 'Courier New', monospace;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .status-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Teste de Conexão</h1>
            <p>Sistema de Consórcios - Hype</p>
        </div>

        <?php
        // Executar teste de conexão
        $database = new Database();
        $testResult = $database->testConnection();
        
        if ($testResult['success']) {
            echo '<div class="test-result success">';
            echo '<div class="status-icon">✅</div>';
            echo '<h3>Conexão Estabelecida com Sucesso!</h3>';
            echo '<p>' . $testResult['message'] . '</p>';
            
            echo '<div class="info-grid">';
            echo '<div class="info-item">';
            echo '<div class="info-label">Servidor MySQL:</div>';
            echo '<div class="info-value">' . $testResult['host'] . '</div>';
            echo '</div>';
            
            echo '<div class="info-item">';
            echo '<div class="info-label">Banco de Dados:</div>';
            echo '<div class="info-value">' . $testResult['database'] . '</div>';
            echo '</div>';
            
            echo '<div class="info-item">';
            echo '<div class="info-label">Versão do Servidor:</div>';
            echo '<div class="info-value">' . $testResult['server_info'] . '</div>';
            echo '</div>';
            
            echo '<div class="info-item">';
            echo '<div class="info-label">Status:</div>';
            echo '<div class="info-value">Online ✅</div>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
            
            // Teste adicional - Verificar tabelas existentes
            try {
                $conn = $database->getConnection();
                $stmt = $conn->query("SHOW TABLES");
                $tables = $stmt->fetchAll();
                
                echo '<div class="test-result">';
                echo '<h4>📋 Tabelas no Banco de Dados:</h4>';
                if (count($tables) > 0) {
                    echo '<ul>';
                    foreach ($tables as $table) {
                        echo '<li>' . array_values($table)[0] . '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p><em>Nenhuma tabela encontrada (banco vazio)</em></p>';
                }
                echo '</div>';
                
            } catch (PDOException $e) {
                echo '<div class="test-result error">';
                echo '<p>Erro ao listar tabelas: ' . $e->getMessage() . '</p>';
                echo '</div>';
            }
            
        } else {
            echo '<div class="test-result error">';
            echo '<div class="status-icon">❌</div>';
            echo '<h3>Falha na Conexão</h3>';
            echo '<p>' . $testResult['message'] . '</p>';
            echo '</div>';
        }
        
        // Fechar conexão
        $database->closeConnection();
        ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="../index.php" class="btn">← Voltar ao Site Principal</a>
            <a href="javascript:location.reload()" class="btn">🔄 Testar Novamente</a>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; text-align: center; color: #666; font-size: 0.9em;">
            <p>
                <strong>Dados da Conexão:</strong><br>
                Host: srv406.hstgr.io | Database: u383946504_hypeconsorcio<br>
                Usuário: u383946504_hypeconsorcio
            </p>
            <p><small>Teste executado em: <?php echo date('d/m/Y H:i:s'); ?></small></p>
        </div>
    </div>
</body>
</html>