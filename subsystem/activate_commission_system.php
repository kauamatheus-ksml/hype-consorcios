<?php
/**
 * Script de Ativação do Sistema de Comissões
 * Execute este arquivo APENAS UMA VEZ para ativar o sistema
 */

session_start();

// Verificar se é admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    die('❌ Acesso negado. Faça login como administrador.');
}

require_once __DIR__ . '/config/database.php';

$success = true;
$messages = [];

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "<h1>🚀 Ativando Sistema de Comissões</h1>";
    echo "<pre>";

    // 1. Verificar se as colunas já existem
    echo "1️⃣ Verificando estrutura da tabela sales...\n";

    $stmt = $conn->query("SHOW COLUMNS FROM sales LIKE 'commission_installments'");
    if ($stmt->rowCount() == 0) {
        echo "   ➕ Adicionando coluna commission_installments...\n";
        $conn->exec("ALTER TABLE sales ADD COLUMN commission_installments INT DEFAULT 5 AFTER commission_value");
    } else {
        echo "   ✅ Coluna commission_installments já existe\n";
    }

    $stmt = $conn->query("SHOW COLUMNS FROM sales LIKE 'monthly_commission'");
    if ($stmt->rowCount() == 0) {
        echo "   ➕ Adicionando coluna monthly_commission...\n";
        $conn->exec("ALTER TABLE sales ADD COLUMN monthly_commission DECIMAL(10,2) AFTER commission_installments");
    } else {
        echo "   ✅ Coluna monthly_commission já existe\n";
    }

    // 2. Criar tabela de configurações de vendedor
    echo "\n2️⃣ Criando tabela de configurações por vendedor...\n";

    $createTable = "
    CREATE TABLE IF NOT EXISTS seller_commission_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL,
        commission_percentage DECIMAL(5,2) DEFAULT 1.50,
        commission_installments INT DEFAULT 5,
        min_sale_value DECIMAL(12,2) DEFAULT 0.00,
        max_sale_value DECIMAL(12,2) NULL,
        bonus_percentage DECIMAL(5,2) DEFAULT 0.00,
        bonus_threshold DECIMAL(12,2) NULL,
        is_active BOOLEAN DEFAULT TRUE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        updated_by INT,
        UNIQUE KEY unique_seller (seller_id),
        INDEX idx_seller_id (seller_id),
        INDEX idx_is_active (is_active),
        FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
    )";

    $conn->exec($createTable);
    echo "   ✅ Tabela seller_commission_settings criada/verificada\n";

    // 3. Atualizar comissão padrão
    echo "\n3️⃣ Atualizando configurações padrão...\n";

    $conn->exec("ALTER TABLE sales MODIFY COLUMN commission_percentage DECIMAL(5,2) DEFAULT 1.50");
    echo "   ✅ Comissão padrão atualizada para 1.5%\n";

    // 4. Corrigir registros existentes
    echo "\n4️⃣ Corrigindo registros existentes...\n";

    $stmt = $conn->exec("UPDATE sales SET commission_percentage = 1.50 WHERE commission_percentage = 0.00 OR commission_percentage IS NULL");
    echo "   ✅ $stmt registros de venda atualizados\n";

    $stmt = $conn->exec("UPDATE sales SET commission_installments = 5 WHERE commission_installments IS NULL");
    echo "   ✅ Parcelas de comissão definidas\n";

    $stmt = $conn->exec("UPDATE sales SET monthly_commission = commission_value / commission_installments WHERE monthly_commission IS NULL AND commission_value > 0 AND commission_installments > 0");
    echo "   ✅ Comissões mensais calculadas\n";

    // 5. Criar configurações para vendedores existentes
    echo "\n5️⃣ Criando configurações para vendedores...\n";

    $adminId = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        INSERT INTO seller_commission_settings (seller_id, commission_percentage, commission_installments, created_by)
        SELECT
            id as seller_id,
            1.50 as commission_percentage,
            5 as commission_installments,
            ? as created_by
        FROM users
        WHERE role IN ('seller', 'manager', 'admin')
        AND id NOT IN (SELECT seller_id FROM seller_commission_settings)
    ");

    $stmt->execute([$adminId]);
    $vendedoresConfigurados = $stmt->rowCount();
    echo "   ✅ $vendedoresConfigurados vendedores configurados\n";

    // 6. Adicionar configuração do sistema
    echo "\n6️⃣ Configurando sistema...\n";

    $stmt = $conn->prepare("
        INSERT INTO system_settings (setting_key, setting_value, description, updated_by)
        VALUES ('default_commission_rate', '1.5', 'Taxa de comissão padrão do sistema (%)', ?)
        ON DUPLICATE KEY UPDATE setting_value = '1.5', updated_by = ?
    ");
    $stmt->execute([$adminId, $adminId]);
    echo "   ✅ Configuração padrão do sistema salva\n";

    // 7. Criar índice para relatórios
    echo "\n7️⃣ Otimizando banco de dados...\n";

    $conn->exec("CREATE INDEX IF NOT EXISTS idx_sales_date_month ON sales (YEAR(sale_date), MONTH(sale_date))");
    echo "   ✅ Índices criados para relatórios mensais\n";

    echo "\n🎉 SISTEMA ATIVADO COM SUCESSO!\n\n";
    echo "📋 Próximos passos:\n";
    echo "   1. Acesse: commission_settings.php para configurar vendedores\n";
    echo "   2. Teste criando uma nova venda\n";
    echo "   3. Verifique se as comissões estão sendo calculadas automaticamente\n\n";

    echo "🔗 Links úteis:\n";
    echo "   • Configurar Comissões: <a href='commission_settings.php' target='_blank'>commission_settings.php</a>\n";
    echo "   • Página de Vendas: <a href='sales.php' target='_blank'>sales.php</a>\n\n";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $success = false;
}

echo "</pre>";

if ($success) {
    echo "<div style='background: #dcfce7; border: 1px solid #22c55e; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h3 style='color: #166534; margin: 0;'>✅ Sistema Ativado!</h3>";
    echo "<p style='color: #166534; margin: 0.5rem 0 0 0;'>O sistema de comissões por vendedor está funcionando.</p>";
    echo "</div>";

    echo "<div style='background: #fef3c7; border: 1px solid #f59e0b; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h3 style='color: #92400e; margin: 0;'>⚠️ Importante</h3>";
    echo "<p style='color: #92400e; margin: 0.5rem 0 0 0;'>Execute este script apenas uma vez. Se executar novamente, pode duplicar dados.</p>";
    echo "</div>";

    echo "<a href='commission_settings.php' style='background: #2563eb; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-block; margin: 1rem 0;'>🔧 Configurar Comissões dos Vendedores</a>";

} else {
    echo "<div style='background: #fef2f2; border: 1px solid #ef4444; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h3 style='color: #991b1b; margin: 0;'>❌ Erro na Ativação</h3>";
    echo "<p style='color: #991b1b; margin: 0.5rem 0 0 0;'>Verifique as mensagens de erro acima e tente novamente.</p>";
    echo "</div>";
}

?>

<style>
body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: #f8fafc;
}

h1 {
    color: #1f2937;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 1rem;
}

pre {
    background: #1f2937;
    color: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    overflow-x: auto;
    line-height: 1.5;
}

a {
    color: #2563eb;
}
</style>