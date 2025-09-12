<?php
/**
 * Script para inicializar o sistema de auditoria
 * Hype Consórcios CRM
 */

require_once 'config/database.php';
require_once 'classes/AuditLogger.php';

try {
    echo "🔧 Inicializando sistema de auditoria...\n";
    
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Não foi possível conectar ao banco");
    }
    
    // Criar instância do AuditLogger (isso criará a tabela automaticamente)
    $auditLogger = new AuditLogger($conn);
    echo "✅ Tabela de audit_logs criada/verificada\n";
    
    // Registrar log de inicialização
    $auditLogger->log(1, 'SYSTEM_INIT', 'Sistema de auditoria inicializado com sucesso');
    echo "✅ Log de inicialização registrado\n";
    
    // Testar algumas operações
    echo "🧪 Testando funcionalidades...\n";
    
    // Simular log de login
    $auditLogger->logLogin(1, 'admin', true);
    echo "✅ Teste de log de login\n";
    
    // Buscar logs recentes
    $recentLogs = $auditLogger->getRecentLogs(5);
    echo "✅ Teste de busca de logs: " . count($recentLogs) . " logs encontrados\n";
    
    echo "\n🎉 Sistema de auditoria inicializado com sucesso!\n";
    echo "📋 Acesse audit-logs.php para visualizar os logs\n";
    echo "👤 Acesse profile.php para testar alterações de perfil\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inicialização do Sistema de Auditoria - Hype CRM</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; }
        pre { background: #333; padding: 20px; border-radius: 10px; }
        .success { color: #00ff00; }
        .error { color: #ff6b6b; }
        .info { color: #74c0fc; }
    </style>
</head>
<body>
    <h2>🔧 Sistema de Auditoria Inicializado</h2>
    <pre class="success"><?php 
        // Se executado via web, mostrar resultado formatado
        if (isset($_SERVER['HTTP_HOST'])) {
            ob_flush();
        }
    ?></pre>
    
    <h3>📋 Funcionalidades Implementadas:</h3>
    <ul class="info">
        <li><strong>Página de Perfil Completa:</strong> profile.php</li>
        <li><strong>API de Perfil:</strong> api/profile.php</li>
        <li><strong>Sistema de Auditoria:</strong> classes/AuditLogger.php</li>
        <li><strong>Visualização de Logs:</strong> audit-logs.php (apenas admin)</li>
        <li><strong>Validações de Segurança:</strong> Senhas, emails, permissões</li>
        <li><strong>Interface Responsiva:</strong> Mobile-friendly</li>
    </ul>
    
    <h3>🔗 Links Úteis:</h3>
    <ul>
        <li><a href="profile.php" style="color: #00ffff;">Acessar Página de Perfil</a></li>
        <li><a href="audit-logs.php" style="color: #00ffff;">Ver Logs de Auditoria (Admin)</a></li>
        <li><a href="dashboard.php" style="color: #00ffff;">Voltar ao Dashboard</a></li>
    </ul>
</body>
</html>