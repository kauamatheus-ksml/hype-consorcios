<?php
session_start();

echo "<h2>🔍 Debug da Sessão</h2>";
echo "<style>body { font-family: Arial; padding: 20px; }</style>";

echo "<h3>Variáveis de Sessão:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Verificações Específicas:</h3>";
echo "logged_in: " . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'true' : 'false') : 'não definido') . "<br>";
echo "user_role: " . ($_SESSION['user_role'] ?? 'não definido') . "<br>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'não definido') . "<br>";

echo "<h3>Outras variações possíveis:</h3>";
echo "role: " . ($_SESSION['role'] ?? 'não definido') . "<br>";
echo "id: " . ($_SESSION['id'] ?? 'não definido') . "<br>";
echo "admin: " . ($_SESSION['admin'] ?? 'não definido') . "<br>";

echo "<h3>Validação atual do commission_settings.php:</h3>";
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

echo "Logado: " . ($isLoggedIn ? 'SIM' : 'NÃO') . "<br>";
echo "É Admin: " . ($isAdmin ? 'SIM' : 'NÃO') . "<br>";

if ($isLoggedIn && $isAdmin) {
    echo "<br><span style='color: green; font-weight: bold;'>✅ ACESSO LIBERADO</span>";
} else {
    echo "<br><span style='color: red; font-weight: bold;'>❌ ACESSO NEGADO</span>";
}
?>