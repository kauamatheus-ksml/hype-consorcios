<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Get and validate input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

// Required fields validation
$required_fields = ['name', 'vehicle', 'phone'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Campo obrigatório: {$field}"]);
        exit;
    }
}

// Sanitize inputs
$data = [
    'name' => filter_var($input['name'], FILTER_SANITIZE_STRING),
    'vehicle' => filter_var($input['vehicle'], FILTER_SANITIZE_STRING),
    'phone' => filter_var($input['phone'], FILTER_SANITIZE_STRING),
    'email' => filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL),
    'has_down_payment' => $input['hasDownPayment'] ?? 'no',
    'down_payment' => filter_var($input['downPayment'] ?? '', FILTER_SANITIZE_STRING),
    'created_at' => date('Y-m-d H:i:s')
];

// Save to database (optional)
try {
    $pdo = new PDO('sqlite:simulations.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS simulations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        vehicle TEXT NOT NULL,
        phone TEXT NOT NULL,
        email TEXT,
        has_down_payment TEXT,
        down_payment TEXT,
        created_at DATETIME
    )");
    
    // Insert simulation
    $stmt = $pdo->prepare("INSERT INTO simulations (name, vehicle, phone, email, has_down_payment, down_payment, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['name'],
        $data['vehicle'],
        $data['phone'],
        $data['email'],
        $data['has_down_payment'],
        $data['down_payment'],
        $data['created_at']
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    // Continue without database
}

// Send email notification (optional)
$to = 'contato@hypeconsorcios.com.br';
$subject = 'Nova Simulação de Consórcio - ' . $data['name'];
$message = "Nova simulação recebida:\n\n";
$message .= "Nome: " . $data['name'] . "\n";
$message .= "Veículo: " . $data['vehicle'] . "\n";
$message .= "Telefone: " . $data['phone'] . "\n";
$message .= "E-mail: " . $data['email'] . "\n";
$message .= "Possui entrada: " . ($data['has_down_payment'] === 'yes' ? 'Sim' : 'Não') . "\n";
if ($data['has_down_payment'] === 'yes' && !empty($data['down_payment'])) {
    $message .= "Valor da entrada: " . $data['down_payment'] . "\n";
}
$message .= "Data: " . $data['created_at'] . "\n";

$headers = "From: noreply@hypeconsorcios.com.br\r\n";
$headers .= "Reply-To: " . $data['email'] . "\r\n";

@mail($to, $subject, $message, $headers);

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Simulação enviada com sucesso!',
    'whatsapp_message' => createWhatsAppMessage($data)
]);

function createWhatsAppMessage($data) {
    $message = "🚗 *SIMULAÇÃO DE CONSÓRCIO*\n\n";
    $message .= "👤 *Nome:* " . $data['name'] . "\n";
    $message .= "🚙 *Veículo:* " . $data['vehicle'] . "\n";
    $message .= "📱 *Telefone:* " . $data['phone'] . "\n";
    
    if (!empty($data['email'])) {
        $message .= "📧 *E-mail:* " . $data['email'] . "\n";
    }
    
    if ($data['has_down_payment'] === 'yes' && !empty($data['down_payment'])) {
        $message .= "💰 *Entrada:* " . $data['down_payment'] . "\n";
    } else {
        $message .= "💰 *Entrada:* Não possui\n";
    }
    
    $message .= "\nGostaria de receber uma simulação personalizada! 😊";
    
    return $message;
}
?>