<?php
/**
 * Script para criar dados de exemplo no sistema
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "🚀 Criando dados de exemplo...\n\n";
    
    // 1. Criar alguns leads de exemplo
    $leads = [
        [
            'name' => 'João Silva',
            'email' => 'joao.silva@email.com',
            'phone' => '(47) 99123-4567',
            'vehicle_interest' => 'Volkswagen Polo',
            'has_down_payment' => 'yes',
            'down_payment_value' => 15000.00,
            'source_page' => 'index',
            'status' => 'new'
        ],
        [
            'name' => 'Maria Santos',
            'email' => 'maria.santos@email.com',
            'phone' => '(11) 98765-4321',
            'vehicle_interest' => 'Honda Civic',
            'has_down_payment' => 'no',
            'down_payment_value' => null,
            'source_page' => 'leves',
            'status' => 'contacted'
        ],
        [
            'name' => 'Carlos Oliveira',
            'email' => 'carlos.oliveira@email.com',
            'phone' => '(21) 97654-3210',
            'vehicle_interest' => 'Toyota Corolla',
            'has_down_payment' => 'yes',
            'down_payment_value' => 25000.00,
            'source_page' => 'premio',
            'status' => 'negotiating'
        ],
        [
            'name' => 'Ana Costa',
            'email' => 'ana.costa@email.com',
            'phone' => '(85) 96543-2109',
            'vehicle_interest' => 'Mercedes-Benz Classe A',
            'has_down_payment' => 'yes',
            'down_payment_value' => 40000.00,
            'source_page' => 'premio',
            'status' => 'converted'
        ],
        [
            'name' => 'Pedro Almeida',
            'email' => 'pedro.almeida@email.com',
            'phone' => '(31) 95432-1098',
            'vehicle_interest' => 'Scania R450',
            'has_down_payment' => 'no',
            'down_payment_value' => null,
            'source_page' => 'pesados',
            'status' => 'new'
        ],
        [
            'name' => 'Luciana Ferreira',
            'email' => 'luciana.ferreira@email.com',
            'phone' => '(71) 94321-0987',
            'vehicle_interest' => 'Volkswagen T-Cross',
            'has_down_payment' => 'yes',
            'down_payment_value' => 20000.00,
            'source_page' => 'index',
            'status' => 'lost'
        ]
    ];
    
    $stmt = $conn->prepare("
        INSERT INTO leads (name, email, phone, vehicle_interest, has_down_payment, down_payment_value, source_page, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($leads as $lead) {
        // Criar leads em datas diferentes (últimos 30 dias)
        $daysAgo = rand(0, 30);
        $createdAt = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));
        
        $stmt->execute([
            $lead['name'],
            $lead['email'],
            $lead['phone'],
            $lead['vehicle_interest'],
            $lead['has_down_payment'],
            $lead['down_payment_value'],
            $lead['source_page'],
            $lead['status'],
            $createdAt
        ]);
    }
    
    echo "✅ Criados " . count($leads) . " leads de exemplo\n";
    
    // 2. Obter ID do admin para criar vendas
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "❌ Usuário admin não encontrado\n";
        exit;
    }
    
    $adminId = $admin['id'];
    
    // 3. Criar algumas vendas baseadas nos leads convertidos
    $convertedLeads = $conn->prepare("SELECT id FROM leads WHERE status = 'converted'");
    $convertedLeads->execute();
    $leadIds = $convertedLeads->fetchAll(PDO::FETCH_COLUMN);
    
    $sales = [
        [
            'sale_value' => 85000.00,
            'commission_percentage' => 3.5,
            'vehicle_sold' => 'Mercedes-Benz Classe A 200',
            'payment_type' => 'consorcio',
            'down_payment' => 40000.00,
            'financing_months' => 60,
            'monthly_payment' => 1200.00,
            'contract_number' => 'CNT-2024-001'
        ]
    ];
    
    if (count($leadIds) > 0) {
        $stmt = $conn->prepare("
            INSERT INTO sales (lead_id, seller_id, sale_value, commission_percentage, commission_value, 
                             vehicle_sold, payment_type, down_payment, financing_months, monthly_payment, 
                             contract_number, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?)
        ");
        
        foreach ($sales as $index => $sale) {
            if (isset($leadIds[$index])) {
                $commissionValue = ($sale['sale_value'] * $sale['commission_percentage']) / 100;
                
                // Criar venda em data aleatória (últimos 30 dias)
                $daysAgo = rand(0, 30);
                $saleDate = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));
                
                $stmt->execute([
                    $leadIds[$index],
                    $adminId,
                    $sale['sale_value'],
                    $sale['commission_percentage'],
                    $commissionValue,
                    $sale['vehicle_sold'],
                    $sale['payment_type'],
                    $sale['down_payment'],
                    $sale['financing_months'],
                    $sale['monthly_payment'],
                    $sale['contract_number'],
                    $saleDate
                ]);
            }
        }
        
        echo "✅ Criadas " . count($sales) . " vendas de exemplo\n";
    }
    
    // 4. Criar algumas interações de exemplo
    $recentLeadIds = $conn->prepare("SELECT id FROM leads ORDER BY created_at DESC LIMIT 3");
    $recentLeadIds->execute();
    $recentIds = $recentLeadIds->fetchAll(PDO::FETCH_COLUMN);
    
    $interactions = [
        [
            'interaction_type' => 'call',
            'description' => 'Primeiro contato realizado. Cliente demonstrou interesse.',
            'result' => 'positive'
        ],
        [
            'interaction_type' => 'whatsapp',
            'description' => 'Enviadas informações sobre financiamento.',
            'result' => 'neutral'
        ],
        [
            'interaction_type' => 'meeting',
            'description' => 'Reunião agendada para apresentação de proposta.',
            'result' => 'positive'
        ]
    ];
    
    if (count($recentIds) > 0) {
        $stmt = $conn->prepare("
            INSERT INTO lead_interactions (lead_id, user_id, interaction_type, description, result, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($interactions as $index => $interaction) {
            if (isset($recentIds[$index])) {
                $daysAgo = rand(0, 15);
                $interactionDate = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));
                
                $stmt->execute([
                    $recentIds[$index],
                    $adminId,
                    $interaction['interaction_type'],
                    $interaction['description'],
                    $interaction['result'],
                    $interactionDate
                ]);
            }
        }
        
        echo "✅ Criadas " . count($interactions) . " interações de exemplo\n";
    }
    
    echo "\n🎉 Dados de exemplo criados com sucesso!\n";
    echo "📊 Agora você pode acessar o dashboard e ver os dados reais.\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao criar dados de exemplo: " . $e->getMessage() . "\n";
}
?>