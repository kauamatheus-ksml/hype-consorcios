<?php
// Iniciar sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação via cookie ou sessão PHP
$authenticated = false;
$user = null;

// Verificar cookie de sessão do CRM
if (isset($_COOKIE['crm_session'])) {
    require_once 'classes/Auth.php';
    $auth = new Auth();
    $sessionResult = $auth->validateSession($_COOKIE['crm_session']);
    
    if ($sessionResult['success']) {
        $authenticated = true;
        $user = $sessionResult['user'];
    }
} 
// Fallback para sessão PHP tradicional
elseif (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $authenticated = true;
    $user = [
        'role' => $_SESSION['user_role'] ?? 'viewer',
        'full_name' => $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Usuário'
    ];
}

// Redirecionar se não autenticado
if (!$authenticated) {
    header('Location: login.php');
    exit();
}

$userRole = $user['role'] ?? 'viewer';
$userName = $user['full_name'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hype Consórcios</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.ico">
    
    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        body {
            background: #f8fafc;
            margin: 0;
            font-family: var(--font-family);
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--dark);
            color: var(--dark-foreground);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-logo-icon {
            width: 40px;
            height: 40px;
            background: #242328;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
        }

        .sidebar-logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .sidebar-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover,
        .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-left-color: var(--primary);
        }

        .nav-icon {
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-foreground);
            font-weight: 600;
        }

        .user-details h4 {
            margin: 0;
            font-size: 0.9rem;
            color: white;
        }

        .user-details p {
            margin: 0;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            text-transform: capitalize;
        }

        .logout-btn {
            width: 100%;
            padding: 0.75rem;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.8);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            background: rgba(220, 38, 38, 0.2);
            border-color: #dc2626;
            color: #fca5a5;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 2rem;
        }

        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--foreground);
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            color: var(--muted-foreground);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-card);
            border-left: 4px solid var(--primary);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: between;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: rgba(59, 225, 201, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--foreground);
            margin: 0;
        }

        .stat-label {
            color: var(--muted-foreground);
            font-size: 0.9rem;
            margin: 0;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow-card);
            margin-bottom: 3rem;
        }

        .quick-actions h3 {
            margin-bottom: 1.5rem;
            color: var(--foreground);
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: var(--muted);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: var(--foreground);
        }

        .action-btn:hover {
            background: var(--primary);
            color: var(--primary-foreground);
            transform: translateY(-2px);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .dashboard-container::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.3s ease;
            }

            .dashboard-container.sidebar-open::before {
                opacity: 1;
                pointer-events: auto;
            }

            .mobile-menu-btn {
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1001;
                background: var(--primary);
                color: var(--primary-foreground);
                border: none;
                padding: 0.75rem;
                border-radius: 8px;
                cursor: pointer;
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container" id="dashboardContainer">
        <!-- Mobile Menu Button -->
        <button class="mobile-menu-btn" onclick="toggleSidebar()" style="display: none;">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="sidebar-logo-icon">
                        <img src="../assets/images/logo.png" alt="Hype Consórcios Logo">
                    </div>
                    <h1 class="sidebar-title">Hype Consórcios</h1>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-home nav-icon"></i>
                        Dashboard
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="leads.php" class="nav-link">
                        <i class="fas fa-users nav-icon"></i>
                        Leads
                    </a>
                </div>
                
                <?php if (in_array($userRole, ['admin', 'manager', 'seller'])): ?>
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="loadSection('sales')">
                        <i class="fas fa-handshake nav-icon"></i>
                        Vendas
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (in_array($userRole, ['admin', 'manager'])): ?>
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="loadSection('reports')">
                        <i class="fas fa-chart-bar nav-icon"></i>
                        Relatórios
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="loadSection('users')">
                        <i class="fas fa-user-cog nav-icon"></i>
                        Usuários
                    </a>
                </div>
                <?php endif; ?>

                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="loadSection('profile')">
                        <i class="fas fa-user nav-icon"></i>
                        Perfil
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($userName, 0, 2)) ?>
                    </div>
                    <div class="user-details">
                        <h4><?= htmlspecialchars($userName) ?></h4>
                        <p><?= htmlspecialchars($userRole) ?></p>
                    </div>
                </div>
                
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Sair
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="dashboard-header">
                <h1 class="dashboard-title">Dashboard</h1>
                <p class="dashboard-subtitle">Bem-vindo ao sistema CRM da Hype Consórcios</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <h3 class="stat-value" id="totalLeads">-</h3>
                    <p class="stat-label">Total de Leads</p>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                    </div>
                    <h3 class="stat-value" id="totalSales">-</h3>
                    <p class="stat-label">Vendas Realizadas</p>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                    <h3 class="stat-value" id="conversionRate">-</h3>
                    <p class="stat-label">Taxa de Conversão</p>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                    <h3 class="stat-value" id="todayLeads">-</h3>
                    <p class="stat-label">Leads Hoje</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>Ações Rápidas</h3>
                <div class="actions-grid">
                    <a href="leads.php" class="action-btn">
                        <i class="fas fa-users"></i>
                        Ver Leads
                    </a>
                    
                    <?php if (in_array($userRole, ['admin', 'manager', 'seller'])): ?>
                    <a href="#" class="action-btn" onclick="loadSection('sales')">
                        <i class="fas fa-handshake"></i>
                        Registrar Venda
                    </a>
                    <?php endif; ?>
                    
                    <?php if (in_array($userRole, ['admin', 'manager'])): ?>
                    <a href="#" class="action-btn" onclick="loadSection('reports')">
                        <i class="fas fa-chart-line"></i>
                        Ver Relatórios
                    </a>
                    
                    <a href="#" class="action-btn" onclick="loadSection('users')">
                        <i class="fas fa-user-plus"></i>
                        Gerenciar Usuários
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <!-- Recent Leads -->
                <div class="info-card" style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow-card);">
                    <h4 style="margin-bottom: 1rem; color: var(--foreground); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-clock" style="color: var(--primary);"></i>
                        Leads Recentes
                    </h4>
                    <div id="recentLeads" style="color: var(--muted-foreground); font-size: 0.9rem;">
                        Carregando...
                    </div>
                </div>

                <!-- Leads by Status -->
                <div class="info-card" style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow-card);">
                    <h4 style="margin-bottom: 1rem; color: var(--foreground); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-chart-pie" style="color: var(--primary);"></i>
                        Leads por Status
                    </h4>
                    <div id="leadsByStatus" style="color: var(--muted-foreground); font-size: 0.9rem;">
                        Carregando...
                    </div>
                </div>

                <!-- Top Sellers -->
                <div class="info-card" style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow-card);">
                    <h4 style="margin-bottom: 1rem; color: var(--foreground); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-trophy" style="color: var(--primary);"></i>
                        Top Vendedores
                    </h4>
                    <div id="topSellers" style="color: var(--muted-foreground); font-size: 0.9rem;">
                        Carregando...
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Load dashboard stats
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardStats();
            
            // Check mobile
            if (window.innerWidth <= 768) {
                document.querySelector('.mobile-menu-btn').style.display = 'block';
            }
        });

        async function loadDashboardStats() {
            try {
                console.log('Carregando estatísticas...');
                const response = await fetch('api/dashboard_stats_simple.php');
                console.log('Response status:', response.status);
                
                const data = await response.json();
                console.log('Data received:', data);
                
                if (data.success) {
                    // Atualizar cards principais
                    document.getElementById('totalLeads').textContent = data.stats.total_leads || '0';
                    document.getElementById('totalSales').textContent = data.stats.total_sales || '0';
                    document.getElementById('conversionRate').textContent = (data.stats.conversion_rate || 0) + '%';
                    document.getElementById('todayLeads').textContent = data.stats.today_leads || '0';
                    
                    // Adicionar informações extras se disponíveis
                    updateRecentLeads(data.stats.recent_leads || []);
                    updateLeadsByStatus(data.stats.leads_by_status || []);
                    updateTopSellers(data.stats.top_sellers || []);
                    
                    console.log('Estatísticas carregadas com sucesso!');
                } else {
                    console.error('Erro na resposta:', data.message);
                    showStatsError('Erro ao carregar dados: ' + data.message);
                }
            } catch (error) {
                console.error('Erro ao carregar estatísticas:', error);
                showStatsError('Erro de conexão: ' + error.message);
            }
        }
        
        function updateRecentLeads(leads) {
            const container = document.getElementById('recentLeads');
            if (!leads || leads.length === 0) {
                container.innerHTML = '<p style="color: var(--muted-foreground);">Nenhum lead encontrado</p>';
                return;
            }
            
            const html = leads.slice(0, 5).map(lead => {
                const date = new Date(lead.created_at).toLocaleDateString('pt-BR');
                const statusColor = getStatusColor(lead.status);
                return `
                    <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: var(--foreground);">${lead.lead_name || 'Nome não informado'}</strong>
                            <br>
                            <small style="color: var(--muted-foreground);">
                                ${lead.phone || 'Tel. não informado'} • ${lead.source || 'Origem não informada'}
                            </small>
                        </div>
                        <div style="text-align: right;">
                            <span style="background: ${statusColor}; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                                ${getStatusLabel(lead.status) || 'Novo'}
                            </span>
                            <br>
                            <small style="color: var(--muted-foreground); margin-top: 0.25rem; display: block;">
                                ${date}
                            </small>
                        </div>
                    </div>
                `;
            }).join('');
            
            container.innerHTML = html;
        }
        
        function updateLeadsByStatus(statusData) {
            const container = document.getElementById('leadsByStatus');
            if (!statusData || statusData.length === 0) {
                container.innerHTML = '<p style="color: var(--muted-foreground);">Nenhum dado encontrado</p>';
                return;
            }
            
            const html = statusData.map(item => {
                const statusColor = getStatusColor(item.status);
                return `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="width: 12px; height: 12px; background: ${statusColor}; border-radius: 50%; display: block;"></span>
                            <span>${getStatusLabel(item.status) || 'Não definido'}</span>
                        </div>
                        <strong style="color: var(--foreground);">${item.count}</strong>
                    </div>
                `;
            }).join('');
            
            container.innerHTML = html;
        }
        
        function updateTopSellers(sellers) {
            const container = document.getElementById('topSellers');
            if (!sellers || sellers.length === 0) {
                container.innerHTML = '<p style="color: var(--muted-foreground);">Nenhum vendedor encontrado</p>';
                return;
            }
            
            const html = sellers.slice(0, 5).map((seller, index) => {
                const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `${index + 1}º`;
                const commission = parseFloat(seller.total_commission || 0).toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                });
                
                return `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                        <div>
                            <span style="margin-right: 0.5rem;">${medal}</span>
                            <strong style="color: var(--foreground);">${seller.full_name || 'Nome não informado'}</strong>
                        </div>
                        <div style="text-align: right;">
                            <div style="color: var(--foreground); font-weight: 600;">${seller.sales_count || 0} vendas</div>
                            <small style="color: var(--muted-foreground);">${commission}</small>
                        </div>
                    </div>
                `;
            }).join('');
            
            container.innerHTML = html;
        }
        
        function getStatusColor(status) {
            const colors = {
                'new': '#3b82f6',
                'contacted': '#f59e0b',
                'negotiating': '#f97316',
                'converted': '#22c55e',
                'lost': '#ef4444'
            };
            return colors[status?.toLowerCase()] || '#6b7280';
        }
        
        function getStatusLabel(status) {
            const labels = {
                'new': 'Novo',
                'contacted': 'Contatado',
                'negotiating': 'Negociando',
                'converted': 'Convertido',
                'lost': 'Perdido'
            };
            return labels[status?.toLowerCase()] || status;
        }

        function showStatsError(message) {
            document.getElementById('totalLeads').textContent = 'Erro';
            document.getElementById('totalSales').textContent = 'Erro';
            document.getElementById('conversionRate').textContent = 'Erro';
            document.getElementById('todayLeads').textContent = 'Erro';
            
            // Atualizar seções adicionais
            document.getElementById('recentLeads').innerHTML = '<p style="color: #dc2626;">Erro ao carregar</p>';
            document.getElementById('leadsByStatus').innerHTML = '<p style="color: #dc2626;">Erro ao carregar</p>';
            document.getElementById('topSellers').innerHTML = '<p style="color: #dc2626;">Erro ao carregar</p>';
            
            // Mostrar mensagem de erro
            const statsGrid = document.getElementById('statsGrid');
            const errorDiv = document.createElement('div');
            errorDiv.style.gridColumn = '1 / -1';
            errorDiv.style.textAlign = 'center';
            errorDiv.style.color = '#dc2626';
            errorDiv.style.background = '#fee2e2';
            errorDiv.style.padding = '1rem';
            errorDiv.style.borderRadius = '8px';
            errorDiv.style.marginTop = '1rem';
            errorDiv.textContent = message;
            statsGrid.appendChild(errorDiv);
        }

        function loadSection(section) {
            // Placeholder para carregamento de seções
            alert('Seção "' + section + '" em desenvolvimento');
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const container = document.getElementById('dashboardContainer');
            
            sidebar.classList.toggle('open');
            container.classList.toggle('sidebar-open');
        }

        async function logout() {
            if (confirm('Deseja realmente sair do sistema?')) {
                try {
                    const response = await fetch('api/auth.php?action=logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });
                    
                    window.location.href = 'login.php';
                } catch (error) {
                    console.error('Erro no logout:', error);
                    window.location.href = 'login.php';
                }
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const mobileBtn = document.querySelector('.mobile-menu-btn');
                
                if (!sidebar.contains(e.target) && !mobileBtn.contains(e.target)) {
                    sidebar.classList.remove('open');
                    document.getElementById('dashboardContainer').classList.remove('sidebar-open');
                }
            }
        });
    </script>
</body>
</html>