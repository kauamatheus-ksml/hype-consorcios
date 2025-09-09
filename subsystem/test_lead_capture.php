<?php
/**
 * Teste de Captura de Lead
 */

// Configurar para mostrar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 Teste de Captura de Lead</h2>";

try {
    // Testar includes
    echo "<p>✅ Testando includes...</p>";
    
    require_once __DIR__ . '/config/database.php';
    echo "<p>✅ Database.php carregado</p>";
    
    require_once __DIR__ . '/classes/LeadManager.php';
    echo "<p>✅ LeadManager.php carregado</p>";
    
    // Testar conexão
    echo "<p>✅ Testando conexão com banco...</p>";
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "<p>✅ Conexão com banco OK</p>";
    } else {
        throw new Exception("Falha na conexão");
    }
    
    // Testar LeadManager
    echo "<p>✅ Testando LeadManager...</p>";
    $leadManager = new LeadManager();
    echo "<p>✅ LeadManager criado OK</p>";
    
    // Testar captura de lead
    echo "<p>✅ Testando captura de lead...</p>";
    $leadData = [
        'name' => 'Teste Lead',
        'phone' => '47999999999',
        'email' => 'teste@teste.com',
        'vehicle_interest' => 'Carro Teste',
        'has_down_payment' => 'no',
        'source_page' => 'teste',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Agent',
        'priority' => 'medium'
    ];
    
    $result = $leadManager->createLead($leadData);
    
    echo "<h3>📊 Resultado:</h3>";
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    if ($result['success']) {
        echo "<p>🎉 Captura de lead funcionando!</p>";
        
        // Testar listagem
        echo "<p>✅ Testando listagem de leads...</p>";
        $listResult = $leadManager->getLeads([], 1, 5);
        echo "<h3>📋 Leads existentes:</h3>";
        echo "<pre>" . json_encode($listResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p>📍 Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste Lead Capture - Hype CRM</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        pre { background: #333; color: #00ff00; padding: 15px; border-radius: 5px; overflow-x: auto; }
        p { margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <p><a href="test_backend.php">← Voltar para Teste Backend</a></p>
</body>
</html>