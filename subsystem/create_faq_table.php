<?php
/**
 * Script para criar tabela de FAQs gerenciáveis
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception('Erro na conexão com o banco de dados');
    }

    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Criar Tabela de FAQs</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .success { color: green; background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .error { color: red; background: #ffe8e8; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .info { background: #e8f0ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>";

    echo "<h1>❓ Criar Tabela de FAQs</h1>";

    // Criar tabela de FAQs
    $sql = "
    CREATE TABLE IF NOT EXISTS faqs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question TEXT NOT NULL,
        answer TEXT NOT NULL,
        display_order INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        INDEX idx_active (is_active),
        INDEX idx_order (display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $conn->exec($sql);
    echo "<div class='success'>✅ Tabela 'faqs' criada com sucesso!</div>";

    // Verificar se já existem FAQs
    $stmt = $conn->query("SELECT COUNT(*) as total FROM faqs");
    $total = $stmt->fetch()['total'];

    if ($total == 0) {
        echo "<div class='info'><h2>📝 Inserindo FAQs padrão...</h2></div>";

        // FAQs padrão do sistema atual
        $defaultFaqs = [
            [
                'question' => 'Como funciona o consórcio de veículos?',
                'answer' => 'O consórcio é um sistema de autofinanciamento onde um grupo de pessoas se une para adquirir bens. Mensalmente, cada participante paga uma parcela e alguns são contemplados por sorteio ou lance.',
                'display_order' => 1
            ],
            [
                'question' => 'Quais são as vantagens do consórcio?',
                'answer' => 'As principais vantagens são: sem juros, parcelas menores, sem consulta ao SPC/Serasa, possibilidade de usar FGTS, e você pode ser contemplado a qualquer momento.',
                'display_order' => 2
            ],
            [
                'question' => 'Posso usar o FGTS para pagamento?',
                'answer' => 'Sim! Você pode usar o FGTS tanto para dar lance quanto para amortizar parcelas do seu consórcio, seguindo as regras da Caixa Econômica Federal.',
                'display_order' => 3
            ],
            [
                'question' => 'Como funciona a contemplação?',
                'answer' => 'A contemplação pode acontecer por sorteio mensal (gratuito) ou por lance (oferta de valor). Quanto maior o lance, maiores as chances de contemplação.',
                'display_order' => 4
            ]
        ];

        // Inserir FAQs padrão
        $stmt = $conn->prepare("
            INSERT INTO faqs (question, answer, display_order)
            VALUES (?, ?, ?)
        ");

        $insertedCount = 0;
        foreach ($defaultFaqs as $faq) {
            $stmt->execute([$faq['question'], $faq['answer'], $faq['display_order']]);
            $insertedCount++;
            echo "<div class='success'>✅ Inserida: " . htmlspecialchars(substr($faq['question'], 0, 50)) . "...</div>";
        }

        echo "<div class='info'><h3>📊 $insertedCount FAQs inseridas com sucesso!</h3></div>";
    } else {
        echo "<div class='info'><h2>ℹ️ Tabela já contém $total FAQs</h2></div>";
    }

    // Listar FAQs existentes
    echo "<h2>📋 FAQs Atuais:</h2>";
    $stmt = $conn->query("SELECT * FROM faqs ORDER BY display_order, id");
    $faqs = $stmt->fetchAll();

    if (empty($faqs)) {
        echo "<div class='error'>❌ Nenhuma FAQ encontrada</div>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Pergunta</th><th>Resposta</th><th>Ordem</th><th>Ativa</th><th>Criada</th>";
        echo "</tr>";

        foreach ($faqs as $faq) {
            echo "<tr>";
            echo "<td>" . $faq['id'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($faq['question'], 0, 50)) . "...</td>";
            echo "<td>" . htmlspecialchars(substr($faq['answer'], 0, 50)) . "...</td>";
            echo "<td>" . $faq['display_order'] . "</td>";
            echo "<td>" . ($faq['is_active'] ? '✅' : '❌') . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($faq['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<hr>";
    echo "<h2>🔗 Próximos Passos:</h2>";
    echo "<ul>";
    echo "<li><a href='site-config.php'>⚙️ Ir para configurações do site</a></li>";
    echo "<li><a href='../index.php' target='_blank'>🏠 Ver site principal</a></li>";
    echo "</ul>";

    echo "</body></html>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
}
?>