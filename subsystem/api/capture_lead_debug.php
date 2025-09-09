<?php
/**
 * Debug da API de Captura de Leads
 */

// Configurar headers JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Desabilitar exibição de erros na tela (para não quebrar JSON)
ini_set('display_errors', 0);
error_reporting(0);

// Capturar erros em variável
ob_start();

try {
    // Log de debug
    $debug = [];
    $debug[] = "Iniciando captura de lead...";
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido. Use POST.');
    }

    $debug[] = "Método POST OK";

    // Obter dados
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $debug[] = "Dados recebidos: " . json_encode($input);

    // Validações básicas
    if (empty($input['name']) || empty($input['phone']) || empty($input['vehicle'])) {
        throw new Exception('Campos obrigatórios: name, phone, vehicle');
    }

    $debug[] = "Validação básica OK";

    // Tentar carregar classes
    if (!file_exists(__DIR__ . '/../config/database.php')) {
        throw new Exception('Arquivo database.php não encontrado');
    }
    
    require_once __DIR__ . '/../config/database.php';
    $debug[] = "Database.php carregado";

    if (!file_exists(__DIR__ . '/../classes/LeadManager.php')) {
        throw new Exception('Arquivo LeadManager.php não encontrado');
    }
    
    require_once __DIR__ . '/../classes/LeadManager.php';
    $debug[] = "LeadManager.php carregado";

    // Testar conexão
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('Falha na conexão com banco de dados');
    }
    
    $debug[] = "Conexão com banco OK";

    // Preparar dados do lead
    $leadData = [
        'name' => trim($input['name']),
        'email' => !empty($input['email']) ? trim($input['email']) : null,
        'phone' => preg_replace('/\D/', '', $input['phone']),
        'vehicle_interest' => trim($input['vehicle']),
        'has_down_payment' => $input['hasDownPayment'] ?? 'no',
        'down_payment_value' => null,
        'source_page' => $input['source'] ?? 'debug',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'priority' => 'medium'
    ];

    $debug[] = "Dados do lead preparados: " . json_encode($leadData);

    // Criar lead manager
    $leadManager = new LeadManager();
    $debug[] = "LeadManager criado";

    // Salvar lead
    $result = $leadManager->createLead($leadData);
    $debug[] = "Resultado createLead: " . json_encode($result);

    if ($result['success']) {
        // Gerar URL WhatsApp
        $phone = '5547996862997';
        $message = "Olá! Vim do site da Hype Consórcios e tenho interesse em:\n\n";
        $message .= "🚗 Veículo: {$leadData['vehicle_interest']}\n";
        $message .= "👤 Nome: {$leadData['name']}\n";
        
        $whatsappURL = "https://api.whatsapp.com/send/?phone={$phone}&text=" . urlencode($message);
        
        $response = [
            'success' => true,
            'message' => 'Lead capturado com sucesso!',
            'lead_id' => $result['lead_id'],
            'redirect_whatsapp' => $whatsappURL,
            'debug' => $debug
        ];
    } else {
        $response = [
            'success' => false,
            'message' => $result['message'],
            'debug' => $debug
        ];
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => $debug ?? [],
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine()
    ];
}

// Capturar saída buffer (caso tenha algum erro/warning)
$buffer = ob_get_clean();
if (!empty($buffer)) {
    $response['php_output'] = $buffer;
}

// Retornar JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>