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

// Incluir componente da sidebar
require_once 'components/sidebar.php';
$currentPage = 'dashboard';
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
    
    <!-- Sidebar Styles -->
    <?= getSidebarStyles() ?>
    
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
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
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
        }
    </style>
</head>
<body>
    <div class="dashboard-container" id="dashboardContainer">
        <?php renderMobileMenuButton(); ?>
        
        <?php renderSidebar($currentPage, $userRole, $userName); ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="dashboard-header">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h1 class="dashboard-title">Dashboard</h1>
                        <p class="dashboard-subtitle">
                            Bem-vindo ao sistema CRM da Hype Consórcios
                            <span id="viewIndicator" style="font-weight: 600; margin-left: 8px; font-size: 0.9rem;"></span>
                        </p>
                    </div>

                    <?php if ($userRole === 'admin'): ?>
                    <div style="min-width: 250px;">
                        <label for="sellerFilter" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--foreground);">
                            Visualizar dados de:
                        </label>
                        <select id="sellerFilter" style="
                            width: 100%;
                            padding: 0.75rem;
                            border: 1px solid var(--border);
                            border-radius: 6px;
                            background: white;
                            color: var(--foreground);
                            font-size: 0.875rem;
                            cursor: pointer;
                        ">
                            <option value="">🌐 Todos os Vendedores (Visão Global)</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
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
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                    <h3 class="stat-value" id="totalCommissions">-</h3>
                    <p class="stat-label">Comissões Este Mês</p>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <h3 class="stat-value" id="conversionRate">-</h3>
                    <p class="stat-label">Taxa de Conversão</p>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <h3 class="stat-value" id="leadsThisMonth">-</h3>
                    <p class="stat-label">Leads Este Mês</p>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                    </div>
                    <h3 class="stat-value" id="salesThisMonth">-</h3>
                    <p class="stat-label">Vendas Este Mês</p>
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
                    <a href="sales.php" class="action-btn">
                        <i class="fas fa-handshake"></i>
                        Gerenciar Vendas
                    </a>
                    <?php endif; ?>
                    
                    <?php if (in_array($userRole, ['admin', 'manager'])): ?>
                    <a href="#" class="action-btn" onclick="showComingSoon('Relatórios')">
                        <i class="fas fa-chart-line"></i>
                        Ver Relatórios
                    </a>
                    
                    <a href="#" class="action-btn" onclick="showComingSoon('Usuários')">
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
            console.log('🚀 Dashboard carregado');
            console.log('🔍 Verificando elementos dos cards...');

            // Verificar se os elementos existem
            const totalLeads = document.getElementById('totalLeads');
            const totalCommissions = document.getElementById('totalCommissions');
            const conversionRate = document.getElementById('conversionRate');
            const leadsThisMonth = document.getElementById('leadsThisMonth');
            const salesThisMonth = document.getElementById('salesThisMonth');

            console.log('totalLeads element:', totalLeads);
            console.log('totalCommissions element:', totalCommissions);
            console.log('conversionRate element:', conversionRate);
            console.log('leadsThisMonth element:', leadsThisMonth);
            console.log('salesThisMonth element:', salesThisMonth);

            loadDashboardStats();

            // Auto-refresh stats every 5 minutes
            setInterval(loadDashboardStats, 5 * 60 * 1000);
            
            // Check mobile
            if (window.innerWidth <= 768) {
                document.querySelector('.mobile-menu-btn').style.display = 'block';
            }
            
            // Add refresh button to dashboard
            addRefreshButton();
        });

        async function loadDashboardStats(sellerId = null) {
            try {
                console.log('Carregando estatísticas...');
                let url = 'api/dashboard_stats_simple.php';
                if (sellerId) {
                    url += `?seller_id=${sellerId}`;
                }
                const response = await fetch(url);
                console.log('Response status:', response.status);
                
                const data = await response.json();
                console.log('Data received:', data);
                console.log('Stats object:', data.stats);
                console.log('Total sales:', data.stats?.total_sales);
                console.log('Total revenue:', data.stats?.total_revenue);
                console.log('Total commissions:', data.stats?.total_commissions);
                console.log('Pending sales:', data.stats?.pending_sales);
                
                if (data.success) {
                    // Preencher lista de vendedores (apenas para admin)
                    if (data.stats.is_admin && data.stats.sellers) {
                        populateSellerSelect(data.stats.sellers);
                    }

                    // Atualizar indicador de visualização
                    const viewIndicator = document.getElementById('viewIndicator');
                    if (viewIndicator) {
                        if (data.stats.is_admin) {
                            if (data.stats.is_global_view) {
                                viewIndicator.textContent = '(Visualização Global - Todos os Vendedores)';
                                viewIndicator.style.color = '#059669';
                            } else {
                                // Buscar nome do vendedor selecionado
                                const selectedSeller = data.stats.sellers?.find(s => s.id == data.stats.selected_seller_id);
                                const sellerName = selectedSeller ? selectedSeller.full_name : 'Vendedor';
                                viewIndicator.textContent = `(Visualizando: ${sellerName})`;
                                viewIndicator.style.color = '#2563eb';
                            }
                        } else {
                            viewIndicator.textContent = '(Suas Estatísticas Pessoais)';
                            viewIndicator.style.color = '#dc2626';
                        }
                    }

                    // Atualizar cards principais
                    const totalLeadsEl = document.getElementById('totalLeads');
                    const totalCommissionsEl = document.getElementById('totalCommissions');
                    const conversionRateEl = document.getElementById('conversionRate');
                    const leadsThisMonthEl = document.getElementById('leadsThisMonth');
                    const salesThisMonthEl = document.getElementById('salesThisMonth');

                    console.log('🔄 Atualizando elementos...');

                    if (totalLeadsEl) {
                        totalLeadsEl.textContent = data.stats.total_leads || '0';
                        console.log('✅ totalLeads atualizado:', totalLeadsEl.textContent);
                    }

                    if (totalCommissionsEl) {
                        totalCommissionsEl.textContent = 'R$ ' + (data.stats.total_commissions || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                        console.log('✅ totalCommissions atualizado:', totalCommissionsEl.textContent);
                    }

                    if (conversionRateEl) {
                        conversionRateEl.textContent = (data.stats.conversion_rate || 0) + '%';
                        console.log('✅ conversionRate atualizado:', conversionRateEl.textContent);
                    }

                    if (leadsThisMonthEl) {
                        leadsThisMonthEl.textContent = data.stats.leads_this_month || '0';
                        console.log('✅ leadsThisMonth atualizado:', leadsThisMonthEl.textContent);
                    }

                    if (salesThisMonthEl) {
                        salesThisMonthEl.textContent = data.stats.sales_this_month || '0';
                        console.log('✅ salesThisMonth atualizado:', salesThisMonthEl.textContent);
                    }
                    
                    // Adicionar informações extras se disponíveis
                    updateRecentLeads(data.stats.recent_leads || []);
                    updateLeadsByStatus(data.stats.leads_by_status || []);
                    updateTopSellers(data.stats.top_sellers || []);
                    
                    // Atualizar horário da última atualização
                    updateLastUpdateTime();
                    
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
                    <div style="
                        padding: 0.75rem 0; 
                        border-bottom: 1px solid var(--border); 
                        display: flex; 
                        justify-content: space-between; 
                        align-items: center;
                        transition: background-color 0.2s ease;
                        cursor: pointer;
                        border-radius: 4px;
                        margin: 0 -0.5rem;
                        padding-left: 0.5rem;
                        padding-right: 0.5rem;
                    " onmouseover="this.style.backgroundColor='var(--muted)'" onmouseout="this.style.backgroundColor='transparent'" onclick="window.location.href='leads.php'">
                        <div>
                            <strong style="color: var(--foreground);">${lead.lead_name || lead.name || 'Nome não informado'}</strong>
                            <br>
                            <small style="color: var(--muted-foreground);">
                                ${lead.phone || 'Tel. não informado'} • ${lead.source || lead.source_page || 'Origem não informada'}
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
            
            // Adicionar link para ver todos os leads
            const viewAllHtml = `
                <div style="text-align: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                    <a href="leads.php" style="
                        color: var(--primary);
                        text-decoration: none;
                        font-weight: 500;
                        font-size: 0.875rem;
                        display: inline-flex;
                        align-items: center;
                        gap: 0.5rem;
                    ">
                        Ver todos os leads
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            `;
            
            container.innerHTML = html + viewAllHtml;
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
            document.getElementById('totalCommissions').textContent = 'Erro';
            document.getElementById('conversionRate').textContent = 'Erro';
            document.getElementById('leadsThisMonth').textContent = 'Erro';
            document.getElementById('salesThisMonth').textContent = 'Erro';
            
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


        function addRefreshButton() {
            // Adicionar botão de atualização no header do dashboard
            const dashboardHeader = document.querySelector('.dashboard-header');
            
            const refreshContainer = document.createElement('div');
            refreshContainer.style.cssText = `
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                margin-top: 1rem;
            `;
            
            const lastUpdateDiv = document.createElement('div');
            lastUpdateDiv.id = 'lastUpdate';
            lastUpdateDiv.style.cssText = `
                color: var(--muted-foreground);
                font-size: 0.875rem;
            `;
            lastUpdateDiv.textContent = 'Última atualização: Carregando...';
            
            const refreshButton = document.createElement('button');
            refreshButton.innerHTML = '<i class="fas fa-sync-alt"></i> Atualizar';
            refreshButton.style.cssText = `
                background: var(--primary);
                color: var(--primary-foreground);
                border: none;
                padding: 0.5rem 1rem;
                border-radius: 6px;
                font-size: 0.875rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            `;
            
            refreshButton.addEventListener('click', function() {
                const icon = this.querySelector('i');
                icon.style.animation = 'spin 1s linear infinite';
                
                const sellerSelect = document.getElementById('sellerFilter');
                const selectedSellerId = sellerSelect ? sellerSelect.value : null;

                loadDashboardStats(selectedSellerId || null).then(() => {
                    icon.style.animation = '';
                });
            });
            
            refreshContainer.appendChild(lastUpdateDiv);
            refreshContainer.appendChild(refreshButton);
            dashboardHeader.appendChild(refreshContainer);
        }

        function updateLastUpdateTime() {
            const lastUpdateDiv = document.getElementById('lastUpdate');
            if (lastUpdateDiv) {
                const now = new Date();
                const timeString = now.toLocaleTimeString('pt-BR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                lastUpdateDiv.textContent = `Última atualização: ${timeString}`;
            }
        }

        // Função para preencher o select de vendedores
        function populateSellerSelect(sellers) {
            const sellerSelect = document.getElementById('sellerFilter');
            if (!sellerSelect) return;

            // Limpar opções existentes (exceto a primeira)
            while (sellerSelect.children.length > 1) {
                sellerSelect.removeChild(sellerSelect.lastChild);
            }

            // Adicionar vendedores
            sellers.forEach(seller => {
                const option = document.createElement('option');
                option.value = seller.id;
                option.textContent = `👤 ${seller.full_name} (${seller.username})`;
                sellerSelect.appendChild(option);
            });

            // Adicionar event listener se ainda não foi adicionado
            if (!sellerSelect.hasAttribute('data-listener-added')) {
                sellerSelect.addEventListener('change', function() {
                    const selectedSellerId = this.value;
                    console.log('Mudando visualização para vendedor:', selectedSellerId);

                    // Recarregar estatísticas com filtro
                    loadDashboardStats(selectedSellerId || null);
                });
                sellerSelect.setAttribute('data-listener-added', 'true');
            }
        }

    </script>
    
    <!-- Sidebar Scripts -->
    <?= getSidebarScripts() ?>
</body>
</html>