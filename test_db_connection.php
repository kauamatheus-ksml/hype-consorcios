<?php
require_once __DIR__ . '/subsystem/config/database.php';

echo "Testando conexão com o banco de dados...\n";

$database = new Database();
$result = $database->testConnection();

if ($result['success']) {
    echo "✅ " . $result['message'] . "\n";
    echo "Servidor: " . $result['server_info'] . "\n";
    echo "Banco: " . $result['database'] . "\n";
    echo "Host: " . $result['host'] . "\n";
} else {
    echo "❌ " . $result['message'] . "\n";
}
?>
