<?php
/**
 * API para Capturar Leads do Formulário
 * Hype Consórcios CRM
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Apenas aceitar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido. Use POST.'
    ]);
    exit;
}

require_once __DIR__ . '/../classes/LeadManager.php';

try {
    // Obter dados do POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Se não veio JSON, tentar $_POST
    if (!$input) {
        $input = $_POST;
    }
    
    // Validações básicas
    $required = ['name', 'phone', 'vehicle'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($input[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        throw new Exception('Campos obrigatórios faltando: ' . implode(', ', $missing));
    }
    
    // Preparar dados do lead
    $leadData = [
        'name' => trim($input['name']),
        'email' => !empty($input['email']) ? trim($input['email']) : null,
        'phone' => preg_replace('/\D/', '', $input['phone']), // Apenas números
        'vehicle_interest' => trim($input['vehicle']),
        'has_down_payment' => $input['hasDownPayment'] ?? 'no',
        'down_payment_value' => null,
        'source_page' => $input['source'] ?? 'index',
        'ip_address' => getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'priority' => 'medium'
    ];
    
    // Tratar valor da entrada se informado
    if ($leadData['has_down_payment'] === 'yes' && !empty($input['downPayment'])) {
        // Remover formatação e converter para decimal
        $value = preg_replace('/[^\d,.]/', '', $input['downPayment']);
        $value = str_replace(',', '.', $value);
        $leadData['down_payment_value'] = floatval($value);
    }
    
    // Validar telefone (básico)
    if (strlen($leadData['phone']) < 10 || strlen($leadData['phone']) > 11) {
        throw new Exception('Telefone inválido. Use formato: (XX) XXXXX-XXXX');
    }
    
    // Validar email se fornecido
    if ($leadData['email'] && !filter_var($leadData['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    
    // Criar lead manager e salvar
    $leadManager = new LeadManager();
    $result = $leadManager->createLead($leadData);
    
    if ($result['success']) {
        // Log da conversão para análise
        error_log("LEAD CAPTURADO: ID {$result['lead_id']} - {$leadData['name']} - {$leadData['phone']} - {$leadData['vehicle_interest']}");
        
        // Resposta de sucesso
        echo json_encode([
            'success' => true,
            'message' => 'Lead capturado com sucesso!',
            'lead_id' => $result['lead_id'],
            'redirect_whatsapp' => generateWhatsAppURL($leadData)
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception($result['message']);
    }
    
} catch (Exception $e) {
    error_log("ERRO CAPTURA LEAD: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Obter IP real do cliente
 */
function getClientIP() {
    $ipKeys = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_FORWARDED_FOR',      // Proxy
        'HTTP_X_REAL_IP',            // Nginx
        'HTTP_CLIENT_IP',            // Proxy
        'REMOTE_ADDR'                // Padrão
    ];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Gerar URL do WhatsApp com mensagem personalizada
 */
function generateWhatsAppURL($leadData) {
    $phone = '5547996862997'; // Número da Hype
    
    $message = "Olá! Vim do site da Hype Consórcios e tenho interesse em:\n\n";
    $message .= "🚗 Veículo: {$leadData['vehicle_interest']}\n";
    $message .= "👤 Nome: {$leadData['name']}\n";
    $message .= "📱 Telefone: " . formatPhone($leadData['phone']) . "\n";
    
    if ($leadData['email']) {
        $message .= "📧 Email: {$leadData['email']}\n";
    }
    
    if ($leadData['has_down_payment'] === 'yes' && $leadData['down_payment_value']) {
        $message .= "💰 Entrada disponível: R$ " . number_format($leadData['down_payment_value'], 2, ',', '.') . "\n";
    } else {
        $message .= "💰 Entrada: Não tenho entrada disponível\n";
    }
    
    $message .= "\nPoderia me ajudar com mais informações sobre o consórcio?";
    
    return "https://api.whatsapp.com/send/?phone={$phone}&text=" . urlencode($message);
}

/**
 * Formatar telefone para exibição
 */
function formatPhone($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    
    if (strlen($phone) == 11) {
        return "(" . substr($phone, 0, 2) . ") " . substr($phone, 2, 5) . "-" . substr($phone, 7);
    } elseif (strlen($phone) == 10) {
        return "(" . substr($phone, 0, 2) . ") " . substr($phone, 2, 4) . "-" . substr($phone, 6);
    }
    
    return $phone;
}
?>