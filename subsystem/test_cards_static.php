<!DOCTYPE html>
<html>
<head>
    <title>Teste Cards Dashboard</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .stat-value { font-size: 2rem; font-weight: bold; margin: 0.5rem 0; }
        .stat-label { color: #666; margin: 0; }
    </style>
</head>
<body>
    <h1>Teste dos Cards do Dashboard</h1>

    <div class="stats-grid">
        <div class="stat-card">
            <h3 class="stat-value" id="totalSales">25</h3>
            <p class="stat-label">Total de Vendas</p>
        </div>

        <div class="stat-card">
            <h3 class="stat-value" id="totalRevenue">R$ 1.250.000,00</h3>
            <p class="stat-label">Receita Total</p>
        </div>

        <div class="stat-card">
            <h3 class="stat-value" id="totalCommissions">R$ 18.750,00</h3>
            <p class="stat-label">Comissões</p>
        </div>

        <div class="stat-card">
            <h3 class="stat-value" id="pendingSales">3</h3>
            <p class="stat-label">Pendentes</p>
        </div>
    </div>

    <br><br>

    <button onclick="testUpdate()">Testar Atualização via JavaScript</button>

    <script>
        function testUpdate() {
            console.log('🔧 Testando atualização dos cards...');

            const totalSales = document.getElementById('totalSales');
            const totalRevenue = document.getElementById('totalRevenue');
            const totalCommissions = document.getElementById('totalCommissions');
            const pendingSales = document.getElementById('pendingSales');

            console.log('Elements found:', {
                totalSales: !!totalSales,
                totalRevenue: !!totalRevenue,
                totalCommissions: !!totalCommissions,
                pendingSales: !!pendingSales
            });

            if (totalSales) totalSales.textContent = '42';
            if (totalRevenue) totalRevenue.textContent = 'R$ 2.100.000,00';
            if (totalCommissions) totalCommissions.textContent = 'R$ 31.500,00';
            if (pendingSales) pendingSales.textContent = '7';

            console.log('✅ Cards atualizados com sucesso!');
        }

        // Teste automático ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📊 Página de teste carregada');
            console.log('Elementos encontrados:', {
                totalSales: document.getElementById('totalSales'),
                totalRevenue: document.getElementById('totalRevenue'),
                totalCommissions: document.getElementById('totalCommissions'),
                pendingSales: document.getElementById('pendingSales')
            });
        });
    </script>
</body>
</html>