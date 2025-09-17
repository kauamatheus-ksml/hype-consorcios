<?php
/**
 * Script para atualizar a configuração de vídeo para tipo 'image' (upload)
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception('Erro na conexão com o banco de dados');
    }

    // Atualizar o tipo da configuração hero_video para 'image' (que permite upload)
    $stmt = $conn->prepare("
        UPDATE site_config
        SET config_type = 'image', description = 'Upload do vídeo de fundo'
        WHERE config_key = 'hero_video'
    ");

    $result = $stmt->execute();

    if ($result) {
        echo "✅ Configuração do vídeo atualizada com sucesso!<br>";
        echo "Agora o campo 'Vídeo de Fundo' aceita upload de arquivos.<br><br>";
        echo "<a href='../debug-config.php'>🔍 Ver debug</a> | ";
        echo "<a href='site-config.php'>⚙️ Ir para configurações</a>";
    } else {
        echo "❌ Erro ao atualizar configuração.";
    }

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>