<?php
session_start();

echo "<h2>🔍 Debug de Acesso - Commission Settings</h2>";
echo "<style>body { font-family: Arial; padding: 20px; background: #f5f5f5; }</style>";

echo "<div style='background: white; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
echo "<h3>Todas as Variáveis de Sessão:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "</div>";

// Replicar a lógica de verificação do commission_settings.php
$isAdmin = false;
$adminId = null;
$userRole = null;
$userId = null;

echo "<div style='background: white; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
echo "<h3>Verificações de Autenticação:</h3>";

// Tentar diferentes formas de verificar se é admin
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    $isAdmin = true;
    $adminId = $_SESSION['user_id'] ?? 1;
    $userRole = $_SESSION['user_role'];
    $userId = $_SESSION['user_id'];
    echo "✅ Método 1: user_role = admin<br>";
} elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $isAdmin = true;
    $adminId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1;
    $userRole = $_SESSION['role'];
    $userId = $_SESSION['user_id'] ?? $_SESSION['id'];
    echo "✅ Método 2: role = admin<br>";
} elseif (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo "⚡ Método 3: Verificando no banco de dados...<br>";

    // Se está logado, vamos verificar no banco se é admin
    require_once __DIR__ . '/config/database.php';
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $currentUserId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
        echo "   User ID para consulta: " . ($currentUserId ?? 'null') . "<br>";

        if ($currentUserId) {
            $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$currentUserId]);
            $user = $stmt->fetch();

            echo "   Resultado da consulta: ";
            print_r($user);
            echo "<br>";

            if ($user && $user['role'] === 'admin') {
                $isAdmin = true;
                $adminId = $currentUserId;
                $userRole = 'admin';
                $userId = $currentUserId;
                echo "✅ Método 3: Confirmado como admin via banco<br>";
            } else {
                echo "❌ Método 3: Não é admin ou usuário não encontrado<br>";
            }
        }
    } catch (Exception $e) {
        echo "❌ Erro no banco: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Não está logado<br>";
}

echo "</div>";

echo "<div style='background: white; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
echo "<h3>Resultado Final:</h3>";
echo "É Admin: " . ($isAdmin ? 'SIM' : 'NÃO') . "<br>";
echo "User ID: " . ($userId ?? 'null') . "<br>";
echo "User Role: " . ($userRole ?? 'null') . "<br>";

if ($isAdmin) {
    echo "<br><div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 4px;'>";
    echo "✅ <strong>ACESSO LIBERADO</strong><br>";
    echo "Você pode acessar commission_settings.php";
    echo "</div>";

    echo "<br><a href='commission_settings.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Acessar Commission Settings</a>";
} else {
    echo "<br><div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px;'>";
    echo "❌ <strong>ACESSO NEGADO</strong><br>";
    echo "Faça login como administrador";
    echo "</div>";
}
echo "</div>";
?>