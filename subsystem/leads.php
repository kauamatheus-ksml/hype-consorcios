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
        'id' => $_SESSION['user_id'] ?? null,
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
$userId = $user['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads - Hype Consórcios</title>
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--foreground);
            margin: 0;
        }

        .page-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--primary-foreground);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 225, 201, 0.3);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--primary-foreground);
        }

        .btn-secondary {
            background: var(--muted);
            color: var(--muted-foreground);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        /* Filters */
        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-card);
            margin-bottom: 2rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-weight: 500;
            color: var(--foreground);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .filter-input {
            padding: 0.75rem;
            border: 2px solid var(--border);
            border-radius: 6px;
            font-size: 0.9rem;
            transition: border-color 0.2s ease;
        }

        .filter-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Statistics Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-card);
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--foreground);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--muted-foreground);
            font-size: 0.9rem;
        }

        /* Leads Table */
        .leads-section {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-card);
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--foreground);
            margin: 0;
        }

        .table-container {
            overflow-x: auto;
        }

        .leads-table {
            width: 100%;
            border-collapse: collapse;
        }

        .leads-table th,
        .leads-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .leads-table th {
            background: var(--muted);
            font-weight: 600;
            color: var(--foreground);
            font-size: 0.9rem;
        }

        .leads-table td {
            color: var(--muted-foreground);
            font-size: 0.9rem;
        }

        .leads-table tr:hover {
            background: rgba(59, 225, 201, 0.05);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }

        .status-new { background: #3b82f6; }
        .status-contacted { background: #f59e0b; }
        .status-negotiating { background: #f97316; }
        .status-converted { background: #22c55e; }
        .status-lost { background: #ef4444; }

        .priority-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .priority-low { background: #e5e7eb; color: #6b7280; }
        .priority-medium { background: #fef3c7; color: #d97706; }
        .priority-high { background: #fed7d7; color: #dc2626; }
        .priority-urgent { background: #dc2626; color: white; }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem;
            font-size: 0.8rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--muted);
            color: var(--muted-foreground);
        }

        .btn-sm:hover {
            background: var(--primary);
            color: var(--primary-foreground);
        }

        .btn-whatsapp {
            background: #25d366;
            color: white;
        }

        .btn-whatsapp:hover {
            background: #128c7e;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            padding: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .page-btn {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            background: white;
            color: var(--muted-foreground);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .page-btn:hover,
        .page-btn.active {
            background: var(--primary);
            color: var(--primary-foreground);
            border-color: var(--primary);
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 3rem;
            color: var(--muted-foreground);
        }

        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid var(--border);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Status badges inline */
        .status-new { background-color: #dbeafe; color: #1d4ed8; }
        .status-contacted { background-color: #fef3c7; color: #d97706; }
        .status-negotiating { background-color: #fed7d7; color: #c53030; }
        .status-converted { background-color: #d1fae5; color: #065f46; }
        .status-lost { background-color: #f3f4f6; color: #6b7280; }

        .priority-low { background-color: #f3f4f6; color: #6b7280; }
        .priority-medium { background-color: #fef3c7; color: #d97706; }
        .priority-high { background-color: #fed7d7; color: #c53030; }
        .priority-urgent { background-color: #fee2e2; color: #991b1b; }

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

            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .page-actions {
                justify-content: stretch;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .table-container {
                font-size: 0.8rem;
            }

            .action-buttons {
                flex-direction: column;
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
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home nav-icon"></i>
                        Dashboard
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="leads.php" class="nav-link active">
                        <i class="fas fa-users nav-icon"></i>
                        Leads
                    </a>
                </div>
                
                <?php if (in_array($userRole, ['admin', 'manager', 'seller'])): ?>
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-handshake nav-icon"></i>
                        Vendas
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (in_array($userRole, ['admin', 'manager'])): ?>
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-chart-bar nav-icon"></i>
                        Relatórios
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-user-cog nav-icon"></i>
                        Usuários
                    </a>
                </div>
                <?php endif; ?>

                <div class="nav-item">
                    <a href="#" class="nav-link">
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
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Gerenciamento de Leads</h1>
                <div class="page-actions">
                    <button class="btn btn-secondary" onclick="exportLeads()">
                        <i class="fas fa-download"></i>
                        Exportar
                    </button>
                    <button class="btn btn-primary" onclick="openNewLeadModal()">
                        <i class="fas fa-plus"></i>
                        Novo Lead
                    </button>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="stats-row" id="statsRow">
                <div class="stat-card">
                    <div class="stat-value" id="totalLeads">-</div>
                    <div class="stat-label">Total de Leads</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="newLeads">-</div>
                    <div class="stat-label">Novos Leads</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="activeLeads">-</div>
                    <div class="stat-label">Em Negociação</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="convertedLeads">-</div>
                    <div class="stat-label">Convertidos</div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Buscar</label>
                        <input type="text" class="filter-input" id="searchFilter" 
                               placeholder="Nome, telefone ou email...">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select class="filter-input" id="statusFilter">
                            <option value="">Todos os status</option>
                            <option value="new">Novo</option>
                            <option value="contacted">Contatado</option>
                            <option value="negotiating">Negociando</option>
                            <option value="converted">Convertido</option>
                            <option value="lost">Perdido</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Origem</label>
                        <select class="filter-input" id="sourceFilter">
                            <option value="">Todas as origens</option>
                            <option value="index">Index</option>
                            <option value="leves">Leves</option>
                            <option value="premio">Premio</option>
                            <option value="pesados">Pesados</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Prioridade</label>
                        <select class="filter-input" id="priorityFilter">
                            <option value="">Todas as prioridades</option>
                            <option value="low">Baixa</option>
                            <option value="medium">Média</option>
                            <option value="high">Alta</option>
                            <option value="urgent">Urgente</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-search"></i>
                            Filtrar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Leads Table Section -->
            <div class="leads-section">
                <div class="section-header">
                    <h3 class="section-title">Lista de Leads</h3>
                    <div class="section-actions">
                        <span id="resultsCount" class="text-muted">Carregando...</span>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="leads-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Contato</th>
                                <th>Veículo</th>
                                <th>Status</th>
                                <th>Prioridade</th>
                                <th>Origem</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="leadsTableBody">
                            <tr>
                                <td colspan="8" class="loading">
                                    <div class="spinner"></div>
                                    <p>Carregando leads...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination" id="pagination">
                    <!-- Pagination buttons will be inserted here -->
                </div>
            </div>
        </main>
    </div>

    <!-- Lead Details Modal -->
    <div class="modal-overlay" id="leadDetailsModal" style="display: none;">
        <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h2 id="leadDetailsTitle">Detalhes do Lead</h2>
                <button class="modal-close" onclick="closeLeadDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body" id="leadDetailsContent">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Carregando detalhes...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Lead Modal -->
    <div class="modal-overlay" id="editLeadModal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="editLeadTitle">Editar Lead</h2>
                <button class="modal-close" onclick="closeEditLeadModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="editLeadForm" onsubmit="submitEditLead(event)">
                    <input type="hidden" id="editLeadId" name="id">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="editName">Nome completo *</label>
                            <input type="text" id="editName" name="name" required class="form-input">
                        </div>

                        <div class="form-group">
                            <label for="editPhone">Telefone *</label>
                            <input type="tel" id="editPhone" name="phone" required class="form-input">
                        </div>

                        <div class="form-group">
                            <label for="editEmail">E-mail</label>
                            <input type="email" id="editEmail" name="email" class="form-input">
                        </div>

                        <div class="form-group">
                            <label for="editVehicle">Veículo de interesse</label>
                            <input type="text" id="editVehicle" name="vehicle_interest" class="form-input">
                        </div>

                        <div class="form-group">
                            <label for="editStatus">Status</label>
                            <select id="editStatus" name="status" class="form-input">
                                <option value="new">Novo</option>
                                <option value="contacted">Contatado</option>
                                <option value="negotiating">Negociando</option>
                                <option value="converted">Convertido</option>
                                <option value="lost">Perdido</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="editPriority">Prioridade</label>
                            <select id="editPriority" name="priority" class="form-input">
                                <option value="low">Baixa</option>
                                <option value="medium">Média</option>
                                <option value="high">Alta</option>
                                <option value="urgent">Urgente</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="editDownPayment">Possui entrada?</label>
                            <select id="editDownPayment" name="has_down_payment" class="form-input" onchange="toggleDownPaymentValue()">
                                <option value="no">Não</option>
                                <option value="yes">Sim</option>
                            </select>
                        </div>

                        <div class="form-group" id="editDownPaymentValueGroup" style="display: none;">
                            <label for="editDownPaymentValue">Valor da entrada</label>
                            <input type="text" id="editDownPaymentValue" name="down_payment_value" class="form-input currency-input" placeholder="R$ 0,00">
                        </div>

                        <div class="form-group form-group-full">
                            <label for="editNotes">Observações</label>
                            <textarea id="editNotes" name="notes" class="form-input" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditLeadModal()">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="saveLeadBtn">
                            <i class="fas fa-save"></i>
                            <span id="saveLeadBtnText">Salvar</span>
                            <i class="fas fa-spinner fa-spin" id="saveLeadSpinner" style="display: none;"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 1rem;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--foreground);
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--muted-foreground);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: var(--muted);
            color: var(--foreground);
        }

        .modal-body {
            padding: 1.5rem;
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--foreground);
            font-size: 0.9rem;
        }

        .form-input {
            padding: 0.75rem;
            border: 2px solid var(--border);
            border-radius: 6px;
            font-size: 0.9rem;
            transition: border-color 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        textarea.form-input {
            resize: vertical;
            min-height: 80px;
        }

        /* Lead Details Styles */
        .lead-details {
            display: grid;
            gap: 1.5rem;
        }

        .detail-section {
            background: var(--muted);
            padding: 1.5rem;
            border-radius: 8px;
        }

        .detail-section h3 {
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--foreground);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.8rem;
            color: var(--muted-foreground);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 0.9rem;
            color: var(--foreground);
            margin-top: 0.25rem;
        }

        .interaction-item {
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .interaction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .interaction-type {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }

        .type-call { background: #3b82f6; }
        .type-whatsapp { background: #25d366; }
        .type-email { background: #f59e0b; }
        .type-meeting { background: #8b5cf6; }
        .type-note { background: #6b7280; }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                margin: 1rem;
                max-height: calc(100vh - 2rem);
            }
        }
    </style>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let currentFilters = {};

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing page...');
            
            loadLeads();
            loadStats();
            
            // Add debug info to console after initialization
            setTimeout(() => {
                console.log('=== DEBUG INFO ===');
                console.log('Para testar o modal, digite no console:');
                console.log('debugTest() - Teste completo');
                console.log('debugModal() - Teste apenas do modal');
                console.log('window.viewLead(4) - Teste com um ID específico');
                console.log('==================');
            }, 3000);
            
            // Initialize table event delegation early
            addTableEventDelegation();
            
            // Check mobile
            if (window.innerWidth <= 768) {
                document.querySelector('.mobile-menu-btn').style.display = 'block';
            }

            // Search with delay
            let searchTimeout;
            document.getElementById('searchFilter').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    applyFilters();
                }, 500);
            });
        });

        async function loadStats() {
            try {
                const response = await fetch('api/leads_stats.php');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('totalLeads').textContent = data.stats.total || '0';
                    document.getElementById('newLeads').textContent = data.stats.new || '0';
                    document.getElementById('activeLeads').textContent = data.stats.negotiating || '0';
                    document.getElementById('convertedLeads').textContent = data.stats.converted || '0';
                }
            } catch (error) {
                console.error('Erro ao carregar estatísticas:', error);
            }
        }

        async function loadLeads(page = 1) {
            try {
                currentPage = page;
                const params = new URLSearchParams({
                    page: page,
                    limit: 20,
                    ...currentFilters
                });

                const response = await fetch(`api/leads.php?${params}`);
                const data = await response.json();
                
                if (data.success) {
                    renderLeadsTable(data.leads);
                    renderPagination(data.pagination);
                    updateResultsCount(data.pagination);
                } else {
                    showError('Erro ao carregar leads: ' + data.message);
                }
            } catch (error) {
                console.error('Erro ao carregar leads:', error);
                showError('Erro de conexão ao carregar leads');
            }
        }

        function addTableEventDelegation() {
            const tbody = document.getElementById('leadsTableBody');
            if (!tbody) return;
            
            // Remove existing delegation listeners to avoid duplicates
            const existingHandler = tbody._eventHandler;
            if (existingHandler) {
                tbody.removeEventListener('click', existingHandler);
            }
            
            // Create new event handler
            const eventHandler = (e) => {
                const button = e.target.closest('.btn-sm');
                if (!button) return;
                
                e.preventDefault();
                e.stopPropagation();
                
                const leadId = button.getAttribute('data-lead-id');
                const title = button.getAttribute('title');
                
                console.log('Table delegation - button clicked:', { leadId, title, button });
                
                if (!leadId) {
                    console.error('No lead ID found on button');
                    return;
                }
                
                try {
                    switch (title) {
                        case 'Ver detalhes':
                            console.log('Calling viewLead from delegation');
                            if (typeof window.viewLead === 'function') {
                                window.viewLead(leadId);
                            } else {
                                console.error('window.viewLead not found');
                            }
                            break;
                        case 'Editar':
                            console.log('Calling editLead from delegation');
                            if (typeof window.editLead === 'function') {
                                window.editLead(leadId);
                            } else {
                                console.error('window.editLead not found');
                            }
                            break;
                        case 'WhatsApp':
                            console.log('Calling openWhatsApp from delegation');
                            const phone = button.getAttribute('data-phone');
                            const name = button.getAttribute('data-name');
                            if (phone && name) {
                                openWhatsApp(phone, name, leadId);
                            }
                            break;
                        default:
                            console.log('Unknown button title:', title);
                    }
                } catch (error) {
                    console.error('Error in table delegation:', error);
                }
            };
            
            // Add event listener and store reference
            tbody.addEventListener('click', eventHandler);
            tbody._eventHandler = eventHandler;
            
            console.log('Table event delegation added');
            
            // Debug: check if functions are available
            console.log('Functions available:', {
                viewLead: typeof window.viewLead,
                editLead: typeof window.editLead,
                openWhatsApp: typeof window.openWhatsApp
            });
        }

        function addActionButtonListeners() {
            // Event listeners para botões de visualização
            const viewButtons = document.querySelectorAll('.btn-sm[title="Ver detalhes"]');
            console.log('Found view buttons:', viewButtons.length);
            
            viewButtons.forEach((btn, index) => {
                console.log(`Adding listener to view button ${index}:`, btn);
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const leadId = btn.getAttribute('data-lead-id');
                    console.log('View button clicked, lead ID:', leadId);
                    console.log('Button element:', btn);
                    if (leadId) {
                        // Prevent multiple calls
                        if (btn.disabled) return;
                        btn.disabled = true;
                        
                        try {
                            if (typeof window.viewLead === 'function') {
                                window.viewLead(leadId).finally(() => {
                                    // Re-enable button after operation
                                    setTimeout(() => {
                                        btn.disabled = false;
                                    }, 1000);
                                });
                            } else {
                                console.error('viewLead function not found');
                                btn.disabled = false;
                            }
                        } catch (error) {
                            console.error('Error calling viewLead:', error);
                            btn.disabled = false;
                        }
                    } else {
                        console.error('No lead ID found on button');
                    }
                });
            });

            // Event listeners para botões de edição
            const editButtons = document.querySelectorAll('.btn-sm[title="Editar"]');
            console.log('Found edit buttons:', editButtons.length);
            
            editButtons.forEach((btn, index) => {
                console.log(`Adding listener to edit button ${index}:`, btn);
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const leadId = btn.getAttribute('data-lead-id');
                    console.log('Edit button clicked, lead ID:', leadId);
                    console.log('Button element:', btn);
                    if (leadId) {
                        // Prevent multiple calls
                        if (btn.disabled) return;
                        btn.disabled = true;
                        
                        try {
                            if (typeof window.editLead === 'function') {
                                window.editLead(leadId).finally(() => {
                                    // Re-enable button after operation
                                    setTimeout(() => {
                                        btn.disabled = false;
                                    }, 1000);
                                });
                            } else {
                                console.error('editLead function not found');
                                btn.disabled = false;
                            }
                        } catch (error) {
                            console.error('Error calling editLead:', error);
                            btn.disabled = false;
                        }
                    } else {
                        console.error('No lead ID found on button');
                    }
                });
            });

            // Event listeners para botões do WhatsApp
            const whatsappButtons = document.querySelectorAll('.btn-sm[title="WhatsApp"]');
            console.log('Found WhatsApp buttons:', whatsappButtons.length);
            
            whatsappButtons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const leadId = btn.getAttribute('data-lead-id');
                    const phone = btn.getAttribute('data-phone');
                    const name = btn.getAttribute('data-name');
                    console.log('WhatsApp button clicked:', { leadId, phone, name });
                    if (phone && name) {
                        openWhatsApp(phone, name, leadId);
                    }
                });
            });
        }

        function renderLeadsTable(leads) {
            const tbody = document.getElementById('leadsTableBody');
            
            if (!leads || leads.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--muted-foreground);">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>Nenhum lead encontrado</p>
                        </td>
                    </tr>
                `;
                return;
            }

            const html = leads.map(lead => `
                <tr data-lead-id="${lead.id}">
                    <td>
                        <div style="font-weight: 600; color: var(--foreground);">${lead.name}</div>
                        ${lead.assigned_to_name ? `<small style="color: var(--muted-foreground);">Atribuído: ${lead.assigned_to_name}</small>` : ''}
                    </td>
                    <td>
                        <div>${lead.phone || '-'}</div>
                        ${lead.email ? `<small style="color: var(--muted-foreground);">${lead.email}</small>` : ''}
                    </td>
                    <td>
                        <div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;" title="${lead.vehicle_interest || 'Não informado'}">
                            ${lead.vehicle_interest || 'Não informado'}
                        </div>
                    </td>
                    <td>
                        <span class="status-badge status-${lead.status}">${getStatusLabel(lead.status)}</span>
                    </td>
                    <td>
                        <span class="priority-badge priority-${lead.priority}">${getPriorityLabel(lead.priority)}</span>
                    </td>
                    <td>
                        <span style="text-transform: capitalize;">${lead.source_page || 'N/A'}</span>
                    </td>
                    <td>
                        <div>${formatDate(lead.created_at)}</div>
                        <small style="color: var(--muted-foreground);">${formatTime(lead.created_at)}</small>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-sm" title="Ver detalhes" data-lead-id="${lead.id}">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-sm" title="Editar" data-lead-id="${lead.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-sm btn-whatsapp" title="WhatsApp" data-lead-id="${lead.id}" data-phone="${lead.phone}" data-name="${lead.name}">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

            tbody.innerHTML = html;
            
            // Adicionar event listeners aos botões de ação
            console.log('Renderized table, adding listeners...');
            setTimeout(() => {
                addActionButtonListeners();
            }, 100);
            
            // Adicionar event delegation como backup
            addTableEventDelegation();
        }

        function renderPagination(pagination) {
            if (!pagination) return;

            const container = document.getElementById('pagination');
            totalPages = pagination.total_pages;
            
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '';
            
            // Previous button
            if (currentPage > 1) {
                html += `<button class="page-btn" onclick="loadLeads(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </button>`;
            }

            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                html += `<button class="page-btn" onclick="loadLeads(1)">1</button>`;
                if (startPage > 2) {
                    html += `<span style="padding: 0.5rem;">...</span>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="loadLeads(${i})">${i}</button>`;
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += `<span style="padding: 0.5rem;">...</span>`;
                }
                html += `<button class="page-btn" onclick="loadLeads(${totalPages})">${totalPages}</button>`;
            }

            // Next button
            if (currentPage < totalPages) {
                html += `<button class="page-btn" onclick="loadLeads(${currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </button>`;
            }

            container.innerHTML = html;
        }

        function updateResultsCount(pagination) {
            if (!pagination) return;
            
            const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
            const end = Math.min(start + pagination.per_page - 1, pagination.total_records);
            
            document.getElementById('resultsCount').textContent = 
                `Mostrando ${start}-${end} de ${pagination.total_records} leads`;
        }

        function applyFilters() {
            currentFilters = {
                search: document.getElementById('searchFilter').value.trim(),
                status: document.getElementById('statusFilter').value,
                source: document.getElementById('sourceFilter').value,
                priority: document.getElementById('priorityFilter').value
            };

            // Remove empty filters
            Object.keys(currentFilters).forEach(key => {
                if (!currentFilters[key]) {
                    delete currentFilters[key];
                }
            });

            loadLeads(1); // Reset to first page
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

        function getPriorityLabel(priority) {
            const labels = {
                'low': 'Baixa',
                'medium': 'Média',
                'high': 'Alta',
                'urgent': 'Urgente'
            };
            return labels[priority?.toLowerCase()] || priority;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }

        function formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        }

        window.openWhatsApp = function(phone, name, id) {
            console.log('openWhatsApp called:', { phone, name, id });
            const cleanPhone = phone.replace(/\D/g, '');
            const message = `Olá ${name}, aqui é da Hype Consórcios. Estamos entrando em contato sobre seu interesse em consórcio de veículos.`;
            const url = `https://api.whatsapp.com/send/?phone=55${cleanPhone}&text=${encodeURIComponent(message)}`;
            window.open(url, '_blank');
        }

        // Quick test function - call debugTest() from console
        window.debugTest = function() {
            console.log('=== QUICK DEBUG TEST ===');
            
            // Test 1: Check if functions exist
            console.log('window.viewLead exists:', typeof window.viewLead);
            console.log('window.editLead exists:', typeof window.editLead);
            console.log('window.debugModal exists:', typeof window.debugModal);
            
            // Test 2: Check modal elements
            const modal = document.getElementById('leadDetailsModal');
            const content = document.getElementById('leadDetailsContent');
            console.log('Modal exists:', !!modal);
            console.log('Content exists:', !!content);
            
            // Test 3: Check if leads are loaded
            const leadsTable = document.querySelector('#leadsTableBody');
            const tableRows = leadsTable ? leadsTable.querySelectorAll('tr') : [];
            console.log('Leads table body exists:', !!leadsTable);
            console.log('Table rows found:', tableRows.length);
            
            // Test 4: Find view buttons
            const viewButtons = document.querySelectorAll('.btn-sm[title="Ver detalhes"]');
            const allButtons = document.querySelectorAll('.btn-sm');
            console.log('View buttons found:', viewButtons.length);
            console.log('All .btn-sm buttons found:', allButtons.length);
            
            // Test 5: Show button details if any exist
            if (allButtons.length > 0) {
                console.log('Button details:');
                allButtons.forEach((btn, idx) => {
                    console.log(`  Button ${idx}: title="${btn.title}", data-lead-id="${btn.getAttribute('data-lead-id')}"`);
                });
            }
            
            // Test 6: Get first lead ID if available
            const firstButton = viewButtons[0];
            if (firstButton) {
                const leadId = firstButton.getAttribute('data-lead-id');
                console.log('First button lead ID:', leadId);
                
                // Test 7: Try to call viewLead with first ID
                if (leadId) {
                    console.log('Testing viewLead with first lead ID...');
                    window.viewLead(leadId);
                }
            } else if (allButtons.length > 0) {
                // Try with any button that has a lead ID
                const buttonWithId = Array.from(allButtons).find(btn => btn.getAttribute('data-lead-id'));
                if (buttonWithId) {
                    const leadId = buttonWithId.getAttribute('data-lead-id');
                    console.log('Testing viewLead with any available ID:', leadId);
                    window.viewLead(leadId);
                }
            } else {
                console.log('No buttons found - leads may not be loaded yet. Try running debugTest() again in a few seconds.');
            }
            
            console.log('=== END QUICK TEST ===');
        };

        // Debug function to test modal elements
        window.debugModal = function() {
            console.log('=== MODAL DEBUG ===');
            const modal = document.getElementById('leadDetailsModal');
            const content = document.getElementById('leadDetailsContent');
            
            console.log('Modal element:', modal);
            console.log('Modal computed style:', modal ? getComputedStyle(modal) : 'N/A');
            console.log('Content element:', content);
            
            if (modal) {
                console.log('Modal current display:', modal.style.display);
                console.log('Modal offsetWidth:', modal.offsetWidth);
                console.log('Modal offsetHeight:', modal.offsetHeight);
                console.log('Modal position:', {
                    top: modal.offsetTop,
                    left: modal.offsetLeft
                });
            }
            
            // Test modal display
            if (modal && content) {
                console.log('Testing modal display...');
                modal.style.display = 'flex';
                modal.style.position = 'fixed';
                modal.style.top = '0';
                modal.style.left = '0';
                modal.style.right = '0';
                modal.style.bottom = '0';
                modal.style.zIndex = '9999';
                modal.style.background = 'rgba(0, 0, 0, 0.8)';
                
                content.innerHTML = `
                    <div style="background: white; padding: 2rem; border-radius: 8px; margin: auto;">
                        <h2>Debug Modal Test</h2>
                        <p>Se você está vendo isto, o modal está funcionando!</p>
                        <button onclick="closeLeadDetailsModal()" style="padding: 0.5rem 1rem; background: #3be1c9; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Fechar
                        </button>
                    </div>
                `;
                
                setTimeout(() => {
                    console.log('Modal after 1s - display:', modal.style.display);
                    console.log('Modal visible:', modal.offsetWidth > 0 && modal.offsetHeight > 0);
                }, 1000);
            }
            console.log('=== END DEBUG ===');
        };

        // Make functions globally available for debugging
        window.viewLead = async function(id) {
            console.log('=== viewLead START ===');
            console.log('viewLead called with ID:', id);
            
            try {
                // Remove any existing detail rows first
                const existingDetailRow = document.querySelector(`#leadDetails-${id}`);
                if (existingDetailRow) {
                    existingDetailRow.remove();
                    return; // Toggle off if already open
                }
                
                // Remove any other open detail rows
                const allDetailRows = document.querySelectorAll('[id^="leadDetails-"]');
                allDetailRows.forEach(row => row.remove());
                
                // Find the lead row
                const leadRow = document.querySelector(`tr[data-lead-id="${id}"]`);
                if (!leadRow) {
                    throw new Error('Lead row not found');
                }
                
                console.log('Found lead row, inserting details...');
                
                // Create details row
                const detailsRow = document.createElement('tr');
                detailsRow.id = `leadDetails-${id}`;
                detailsRow.innerHTML = `
                    <td colspan="8" style="background: #f8fafc; border: none; padding: 0;">
                        <div class="lead-details-container" style="padding: 1.5rem; animation: slideDown 0.3s ease;">
                            <div class="loading-inline" style="text-align: center; padding: 2rem;">
                                <div class="spinner" style="border: 4px solid #f3f3f3; border-top: 4px solid #3be1c9; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                                <p style="color: #6b7280;">Carregando detalhes do lead...</p>
                            </div>
                        </div>
                    </td>
                `;
                
                // Insert after the lead row
                leadRow.parentNode.insertBefore(detailsRow, leadRow.nextSibling);
                
                // Fetch lead details
                console.log('Fetching lead details from API...');
                const response = await fetch(`api/leads.php?action=details&id=${id}`);
                console.log('API response status:', response.status);
                
                const data = await response.json();
                console.log('API response data:', data);

                if (data.success) {
                    console.log('Lead data received, rendering inline details...');
                    renderInlineLeadDetails(data, detailsRow);
                    console.log('=== viewLead SUCCESS ===');
                } else {
                    console.error('API returned error:', data.message);
                    throw new Error(data.message || 'Erro ao carregar detalhes');
                }
            } catch (error) {
                console.error('=== viewLead ERROR ===');
                console.error('Error details:', error);
                
                // Show error in inline format
                const leadRow = document.querySelector(`tr[data-lead-id="${id}"]`);
                if (leadRow) {
                    const errorRow = document.createElement('tr');
                    errorRow.id = `leadDetails-${id}`;
                    errorRow.innerHTML = `
                        <td colspan="8" style="background: #fef2f2; border: none; padding: 0;">
                            <div style="padding: 1.5rem; text-align: center; color: #dc2626;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                                <h4 style="margin: 0.5rem 0;">Erro ao carregar detalhes</h4>
                                <p style="margin: 0.5rem 0;">${error.message}</p>
                                <button onclick="window.viewLead(${id})" style="margin-top: 1rem; padding: 0.5rem 1rem; background: #3be1c9; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                    Tentar Novamente
                                </button>
                            </div>
                        </td>
                    `;
                    leadRow.parentNode.insertBefore(errorRow, leadRow.nextSibling);
                }
            }
        }

        window.editLead = async function(id) {
            console.log('editLead called:', id);
            try {
                // Fetch lead data first
                const response = await fetch(`api/leads.php?action=details&id=${id}`);
                const data = await response.json();

                if (data.success) {
                    populateEditForm(data.lead);
                    document.getElementById('editLeadModal').style.display = 'flex';
                } else {
                    throw new Error(data.message || 'Erro ao carregar dados');
                }
            } catch (error) {
                console.error('Erro ao carregar lead para edição:', error);
                alert('Erro ao carregar dados do lead: ' + error.message);
            }
        }

        function renderInlineLeadDetails(data, detailsRow) {
            const { lead, interactions, sales } = data;
            
            const detailsContainer = detailsRow.querySelector('.lead-details-container');
            detailsContainer.innerHTML = `
                <div class="inline-lead-details" style="background: white; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb; border-radius: 8px 8px 0 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="margin: 0 0 0.5rem 0; color: var(--foreground); font-size: 1.25rem;">
                                    ${lead.name}
                                </h3>
                                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                    <span class="status-badge status-${lead.status}" style="padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 500;">
                                        ${getStatusLabel(lead.status)}
                                    </span>
                                    <span class="priority-badge priority-${lead.priority}" style="padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 500;">
                                        ${getPriorityLabel(lead.priority)}
                                    </span>
                                </div>
                            </div>
                            <button onclick="window.viewLead(${lead.id})" style="background: #f3f4f6; border: 1px solid #d1d5db; padding: 0.5rem; border-radius: 4px; cursor: pointer; color: #6b7280;" title="Fechar detalhes">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Content Grid -->
                    <div style="padding: 1.5rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                            <!-- Informações Básicas -->
                            <div>
                                <h4 style="margin: 0 0 1rem 0; color: var(--primary); font-size: 1rem;">Informações de Contato</h4>
                                <div style="space-y: 0.75rem;">
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Telefone:</strong>
                                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                                            <span>${lead.phone || 'Não informado'}</span>
                                            ${lead.phone ? `<a href="https://api.whatsapp.com/send/?phone=55${lead.phone.replace(/\D/g, '')}" target="_blank" style="color: #25d366; text-decoration: none;" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>` : ''}
                                        </div>
                                    </div>
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Email:</strong>
                                        <div>${lead.email || 'Não informado'}</div>
                                    </div>
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Origem:</strong>
                                        <div>${lead.source_page || 'Não informado'}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Interesse -->
                            <div>
                                <h4 style="margin: 0 0 1rem 0; color: var(--primary); font-size: 1rem;">Interesse</h4>
                                <div style="space-y: 0.75rem;">
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Veículo:</strong>
                                        <div>${lead.vehicle_interest || 'Não especificado'}</div>
                                    </div>
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Possui entrada:</strong>
                                        <div>${lead.has_down_payment === 'yes' ? 'Sim' : 'Não'}</div>
                                    </div>
                                    ${lead.down_payment_value ? `
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Valor da entrada:</strong>
                                        <div>R$ ${parseFloat(lead.down_payment_value || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        
                        ${lead.notes ? `
                        <div style="margin-bottom: 2rem;">
                            <h4 style="margin: 0 0 0.75rem 0; color: var(--primary); font-size: 1rem;">Observações</h4>
                            <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; border: 1px solid #e5e7eb;">
                                ${lead.notes}
                            </div>
                        </div>
                        ` : ''}
                        
                        <!-- Histórico de Interações -->
                        ${interactions && interactions.length > 0 ? `
                        <div style="margin-bottom: 2rem;">
                            <h4 style="margin: 0 0 1rem 0; color: var(--primary); font-size: 1rem;">Histórico de Interações (${interactions.length})</h4>
                            <div style="max-height: 200px; overflow-y: auto;">
                                ${interactions.map(interaction => `
                                    <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 1rem; margin-bottom: 0.5rem; background: white;">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                            <span style="font-weight: 500; color: var(--foreground);">${interaction.user_name || 'Sistema'}</span>
                                            <span style="font-size: 0.75rem; color: var(--muted-foreground);">${formatDate(interaction.created_at)}</span>
                                        </div>
                                        <div style="font-size: 0.875rem; color: var(--muted-foreground);">
                                            ${interaction.description}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        ` : ''}
                        
                        <!-- Informações do Sistema -->
                        <div style="border-top: 1px solid #e5e7eb; padding-top: 1rem; font-size: 0.75rem; color: var(--muted-foreground);">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>Criado em: ${formatDate(lead.created_at)}</div>
                                <div>Atualizado em: ${formatDate(lead.updated_at)}</div>
                                ${lead.assigned_to_name ? `<div>Atribuído para: ${lead.assigned_to_name}</div>` : ''}
                                ${lead.ip_address ? `<div>IP: ${lead.ip_address}</div>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderLeadDetails(data) {
            const lead = data.lead;
            const interactions = data.interactions || [];
            const sales = data.sales || [];

            document.getElementById('leadDetailsTitle').textContent = `Detalhes - ${lead.name}`;

            const formatCurrency = (value) => {
                if (!value) return 'Não informado';
                return new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                }).format(value);
            };

            const formatDate = (dateString) => {
                if (!dateString) return 'Não informado';
                return new Date(dateString).toLocaleString('pt-BR');
            };

            let html = `
                <div class="lead-details">
                    <!-- Informações Básicas -->
                    <div class="detail-section">
                        <h3><i class="fas fa-user"></i> Informações Básicas</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Nome</span>
                                <span class="detail-value">${lead.name}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Telefone</span>
                                <span class="detail-value">${lead.phone || 'Não informado'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">E-mail</span>
                                <span class="detail-value">${lead.email || 'Não informado'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Veículo</span>
                                <span class="detail-value">${lead.vehicle_interest || 'Não informado'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <span class="detail-value">
                                    <span class="status-badge status-${lead.status}">${getStatusLabel(lead.status)}</span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Prioridade</span>
                                <span class="detail-value">
                                    <span class="priority-badge priority-${lead.priority}">${getPriorityLabel(lead.priority)}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Informações Financeiras -->
                    <div class="detail-section">
                        <h3><i class="fas fa-money-bill-wave"></i> Informações Financeiras</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Possui Entrada</span>
                                <span class="detail-value">${lead.has_down_payment === 'yes' ? 'Sim' : 'Não'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Valor da Entrada</span>
                                <span class="detail-value">${formatCurrency(lead.down_payment_value)}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Informações do Sistema -->
                    <div class="detail-section">
                        <h3><i class="fas fa-info-circle"></i> Informações do Sistema</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Origem</span>
                                <span class="detail-value">${lead.source_page || 'Não informado'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Atribuído para</span>
                                <span class="detail-value">${lead.assigned_to_name || 'Não atribuído'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Criado em</span>
                                <span class="detail-value">${formatDate(lead.created_at)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Última atualização</span>
                                <span class="detail-value">${formatDate(lead.updated_at)}</span>
                            </div>
                        </div>
                        ${lead.notes ? `
                            <div style="margin-top: 1rem;">
                                <span class="detail-label">Observações</span>
                                <div class="detail-value" style="margin-top: 0.5rem; padding: 1rem; background: white; border-radius: 6px; white-space: pre-wrap;">${lead.notes}</div>
                            </div>
                        ` : ''}
                    </div>
            `;

            // Histórico de Interações
            if (interactions.length > 0) {
                html += `
                    <div class="detail-section">
                        <h3><i class="fas fa-history"></i> Histórico de Interações</h3>
                        <div style="max-height: 300px; overflow-y: auto;">
                `;

                interactions.forEach(interaction => {
                    html += `
                        <div class="interaction-item">
                            <div class="interaction-header">
                                <div>
                                    <span class="interaction-type type-${interaction.interaction_type}">
                                        ${interaction.interaction_type.toUpperCase()}
                                    </span>
                                    <span style="margin-left: 1rem; color: var(--muted-foreground); font-size: 0.9rem;">
                                        por ${interaction.user_name}
                                    </span>
                                </div>
                                <small style="color: var(--muted-foreground);">
                                    ${formatDate(interaction.created_at)}
                                </small>
                            </div>
                            <p style="margin: 0; color: var(--foreground);">${interaction.description}</p>
                        </div>
                    `;
                });

                html += `
                        </div>
                    </div>
                `;
            }

            // Vendas
            if (sales.length > 0) {
                html += `
                    <div class="detail-section">
                        <h3><i class="fas fa-handshake"></i> Vendas Relacionadas</h3>
                `;

                sales.forEach(sale => {
                    html += `
                        <div class="interaction-item">
                            <div class="interaction-header">
                                <div>
                                    <strong>Venda #${sale.id}</strong>
                                    <span style="margin-left: 1rem; color: var(--muted-foreground);">
                                        por ${sale.seller_name}
                                    </span>
                                </div>
                                <span class="status-badge status-${sale.status}">${sale.status}</span>
                            </div>
                            <div style="margin-top: 0.5rem;">
                                <p><strong>Veículo:</strong> ${sale.vehicle_sold || 'Não informado'}</p>
                                <p><strong>Valor:</strong> ${formatCurrency(sale.sale_value)}</p>
                                <p><strong>Data:</strong> ${formatDate(sale.sale_date)}</p>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
            }

            html += '</div>';

            document.getElementById('leadDetailsContent').innerHTML = html;
        }

        function populateEditForm(lead) {
            document.getElementById('editLeadId').value = lead.id;
            document.getElementById('editName').value = lead.name || '';
            document.getElementById('editPhone').value = lead.phone || '';
            document.getElementById('editEmail').value = lead.email || '';
            document.getElementById('editVehicle').value = lead.vehicle_interest || '';
            document.getElementById('editStatus').value = lead.status || 'new';
            document.getElementById('editPriority').value = lead.priority || 'medium';
            document.getElementById('editDownPayment').value = lead.has_down_payment || 'no';
            document.getElementById('editNotes').value = lead.notes || '';

            // Handle down payment value
            const hasDownPayment = lead.has_down_payment === 'yes';
            const downPaymentGroup = document.getElementById('editDownPaymentValueGroup');
            
            if (hasDownPayment) {
                downPaymentGroup.style.display = 'block';
                if (lead.down_payment_value) {
                    document.getElementById('editDownPaymentValue').value = 
                        new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        }).format(lead.down_payment_value);
                }
            } else {
                downPaymentGroup.style.display = 'none';
            }

            document.getElementById('editLeadTitle').textContent = `Editar - ${lead.name}`;
        }

        function toggleDownPaymentValue() {
            const hasDownPayment = document.getElementById('editDownPayment').value === 'yes';
            const downPaymentGroup = document.getElementById('editDownPaymentValueGroup');
            
            downPaymentGroup.style.display = hasDownPayment ? 'block' : 'none';
            
            if (!hasDownPayment) {
                document.getElementById('editDownPaymentValue').value = '';
            }
        }

        async function submitEditLead(event) {
            event.preventDefault();
            
            const saveBtn = document.getElementById('saveLeadBtn');
            const btnText = document.getElementById('saveLeadBtnText');
            const btnSpinner = document.getElementById('saveLeadSpinner');
            
            // Loading state
            saveBtn.disabled = true;
            btnText.textContent = 'Salvando...';
            btnSpinner.style.display = 'inline-block';
            
            try {
                const formData = new FormData(event.target);
                const data = Object.fromEntries(formData.entries());
                
                // Convert currency to number
                if (data.down_payment_value) {
                    data.down_payment_value = parseFloat(
                        data.down_payment_value.replace(/[^\d,]/g, '').replace(',', '.')
                    ) || null;
                }
                
                const response = await fetch('api/leads.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeEditLeadModal();
                    loadLeads(currentPage); // Reload current page
                    alert('✅ Lead atualizado com sucesso!');
                } else {
                    throw new Error(result.message || 'Erro ao salvar');
                }
                
            } catch (error) {
                console.error('Erro ao salvar lead:', error);
                alert('❌ Erro ao salvar: ' + error.message);
            } finally {
                // Reset button state
                saveBtn.disabled = false;
                btnText.textContent = 'Salvar';
                btnSpinner.style.display = 'none';
            }
        }

        function closeLeadDetailsModal() {
            document.getElementById('leadDetailsModal').style.display = 'none';
        }

        function closeEditLeadModal() {
            document.getElementById('editLeadModal').style.display = 'none';
            document.getElementById('editLeadForm').reset();
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            const detailsModal = document.getElementById('leadDetailsModal');
            const editModal = document.getElementById('editLeadModal');
            
            if (event.target === detailsModal) {
                closeLeadDetailsModal();
            }
            
            if (event.target === editModal) {
                closeEditLeadModal();
            }
        });

        function openNewLeadModal() {
            // Placeholder for new lead modal
            alert('Novo lead - Em desenvolvimento');
        }

        function exportLeads() {
            // Placeholder for export function
            alert('Exportar leads - Em desenvolvimento');
        }

        function showError(message) {
            const tbody = document.getElementById('leadsTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem; color: #dc2626;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>${message}</p>
                        <button class="btn btn-primary" onclick="loadLeads()">Tentar Novamente</button>
                    </td>
                </tr>
            `;
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