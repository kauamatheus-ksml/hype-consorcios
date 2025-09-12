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
$userId = $user['id'] ?? null;

// Incluir componente da sidebar
require_once 'components/sidebar.php';
$currentPage = 'sales';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendas - Hype Consórcios</title>
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
            flex: 1;
            margin-left: 280px;
            background: #f8fafc;
        }

        .top-bar {
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: var(--foreground);
        }

        .page-header {
            flex: 1;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--foreground);
            margin: 0;
        }

        .page-subtitle {
            color: var(--muted-foreground);
            margin: 0;
            font-size: 0.875rem;
        }

        .page-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        /* Content Area */
        .content-wrapper {
            padding: 2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.sales { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon.revenue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-icon.commission { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.pending { background: linear-gradient(135deg, #ef4444, #dc2626); }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--foreground);
            margin: 0;
        }

        .stat-label {
            color: var(--muted-foreground);
            font-size: 0.875rem;
            margin: 0;
        }

        /* Filters and Controls */
        .controls-bar {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .controls-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .controls-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--foreground);
            margin: 0;
        }

        .btn-new-sale {
            background: var(--primary);
            color: var(--primary-foreground);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            outline: none;
        }

        .btn-new-sale:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 225, 201, 0.4);
            background: #2dd4bf;
        }

        .btn-new-sale:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(59, 225, 201, 0.3);
        }

        .btn-new-sale:focus {
            box-shadow: 0 0 0 3px rgba(59, 225, 201, 0.3);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--foreground);
        }

        .filter-input {
            padding: 0.75rem;
            border: 2px solid var(--border);
            border-radius: 6px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }

        .filter-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Sales Table */
        .sales-section {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            background: #f8fafc;
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--foreground);
            margin: 0;
        }

        .table-container {
            overflow-x: auto;
        }

        .sales-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sales-table thead th {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: var(--foreground);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .sales-table tbody td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            color: var(--foreground);
        }

        .sales-table tbody tr:hover {
            background: #f8fafc;
        }

        .sales-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-pending { background-color: #fef3c7; color: #d97706; }
        .status-confirmed { background-color: #d1fae5; color: #065f46; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem;
            border: 1px solid var(--border);
            background: white;
            border-radius: 6px;
            cursor: pointer;
            color: var(--muted-foreground);
            transition: all 0.2s;
        }

        .btn-sm:hover {
            color: var(--foreground);
            background: #f8fafc;
        }

        /* Loading and spinner */
        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            padding: 3rem;
        }

        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid #f3f3f3;
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

        /* Notification animations */
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-top: 1px solid var(--border);
            background: #f8fafc;
        }

        .pagination-info {
            color: var(--muted-foreground);
            font-size: 0.875rem;
        }

        .pagination-controls {
            display: flex;
            gap: 0.5rem;
        }

        .pagination-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .pagination-btn:hover:not(:disabled) {
            background: #f8fafc;
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-btn.active {
            background: var(--primary);
            color: var(--primary-foreground);
            border-color: var(--primary);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            
            .content-wrapper {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                font-size: 0.875rem;
            }
            
            .controls-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
        }

        /* Modal Nova Venda */
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
            z-index: 10000;
            padding: 2rem;
            backdrop-filter: blur(4px);
        }

        .modal-container {
            background: white;
            border-radius: 16px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            animation: modalAppear 0.3s ease;
        }

        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem 2rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--foreground);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-title i {
            color: var(--primary);
        }

        .modal-close {
            background: #f3f4f6;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: #e5e7eb;
            color: #374151;
        }

        .modal-form {
            padding: 0 2rem 2rem;
        }

        .form-sections {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .form-section {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            background: #f9fafb;
        }

        .section-title {
            margin: 0 0 1.5rem 0;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--foreground);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--primary);
        }

        .section-title i {
            color: var(--primary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: var(--foreground);
            font-size: 0.875rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.875rem;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 225, 201, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .btn-primary,
        .btn-secondary {
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--primary-foreground);
        }

        .btn-primary:hover {
            background: #2dd4bf;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 225, 201, 0.4);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            color: #374151;
        }

        /* Loading States */
        .btn-primary:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Mobile Modal */
        @media (max-width: 768px) {
            .modal-overlay {
                padding: 1rem;
            }
            
            .modal-header {
                padding: 1.5rem 1.5rem 1rem;
            }
            
            .modal-form {
                padding: 0 1.5rem 1.5rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-actions {
                flex-direction: column-reverse;
            }
            
            .btn-primary,
            .btn-secondary {
                justify-content: center;
            }
        }

        /* Animações para notificações */
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php renderMobileMenuButton(); ?>
        
        <?php renderSidebar($currentPage, $userRole, $userName); ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="page-header">
                    <h1 class="page-title">Vendas</h1>
                    <p class="page-subtitle">Gerencie todas as vendas e comissões</p>
                </div>
                
                <div class="page-actions">
                    <button class="btn-new-sale" onclick="window.openNewSaleModal()">
                        <i class="fas fa-plus"></i>
                        Nova Venda
                    </button>
                    <!-- Botão de teste temporário -->
                    <button style="margin-left: 10px; padding: 0.5rem 1rem; background: #ff6b6b; color: white; border: none; border-radius: 4px; cursor: pointer;" onclick="window.testButton()">
                        Teste
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="content-wrapper">
                <!-- Stats -->
                <div class="stats-grid" id="statsGrid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon sales">
                                <i class="fas fa-handshake"></i>
                            </div>
                        </div>
                        <h3 class="stat-value" id="totalSales">-</h3>
                        <p class="stat-label">Total de Vendas</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon revenue">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                        <h3 class="stat-value" id="totalRevenue">-</h3>
                        <p class="stat-label">Receita Total</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon commission">
                                <i class="fas fa-percentage"></i>
                            </div>
                        </div>
                        <h3 class="stat-value" id="totalCommission">-</h3>
                        <p class="stat-label">Comissões</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <h3 class="stat-value" id="pendingSales">-</h3>
                        <p class="stat-label">Pendentes</p>
                    </div>
                </div>

                <!-- Controls -->
                <div class="controls-bar">
                    <div class="controls-header">
                        <h2 class="controls-title">Filtros e Pesquisa</h2>
                    </div>
                    
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label class="filter-label">Buscar</label>
                            <input type="text" class="filter-input" id="searchFilter" placeholder="Nome, contrato, veículo...">
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Status</label>
                            <select class="filter-input" id="statusFilter">
                                <option value="">Todos</option>
                                <option value="pending">Pendente</option>
                                <option value="confirmed">Confirmado</option>
                                <option value="cancelled">Cancelado</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Vendedor</label>
                            <select class="filter-input" id="sellerFilter">
                                <option value="">Todos</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Período</label>
                            <select class="filter-input" id="periodFilter">
                                <option value="">Todos</option>
                                <option value="today">Hoje</option>
                                <option value="week">Esta semana</option>
                                <option value="month">Este mês</option>
                                <option value="quarter">Trimestre</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Sales Table -->
                <div class="sales-section">
                    <div class="section-header">
                        <h2 class="section-title">Lista de Vendas</h2>
                    </div>
                    
                    <div class="table-container">
                        <table class="sales-table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Veículo</th>
                                    <th>Valor</th>
                                    <th>Comissão</th>
                                    <th>Vendedor</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="salesTableBody">
                                <tr>
                                    <td colspan="8" class="loading">
                                        <div class="spinner"></div>
                                        <p>Carregando vendas...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination" id="pagination">
                        <div class="pagination-info" id="paginationInfo">
                            Carregando...
                        </div>
                        <div class="pagination-controls" id="paginationControls">
                            <!-- Pagination buttons will be inserted here -->
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Nova Venda -->
    <div class="modal-overlay" id="newSaleModal" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-plus-circle"></i>
                    Nova Venda
                </h2>
                <button class="modal-close" onclick="closeNewSaleModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="newSaleForm" class="modal-form">
                <div class="form-sections">
                    <!-- Seção: Informações do Cliente -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i>
                            Informações do Cliente
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Lead Relacionado</label>
                                <select name="lead_id" id="leadSelect">
                                    <option value="">Selecionar lead existente (opcional)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nome do Cliente *</label>
                                <input type="text" name="customer_name" required placeholder="Nome completo">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" placeholder="email@exemplo.com">
                            </div>
                            <div class="form-group">
                                <label>Telefone</label>
                                <input type="tel" name="phone" placeholder="(11) 99999-9999">
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Detalhes da Venda -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-handshake"></i>
                            Detalhes da Venda
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Veículo Vendido *</label>
                                <input type="text" name="vehicle_sold" required placeholder="Ex: Honda Civic 2023">
                            </div>
                            <div class="form-group">
                                <label>Número do Contrato</label>
                                <input type="text" name="contract_number" placeholder="123456789">
                            </div>
                            <div class="form-group">
                                <label>Forma de Pagamento *</label>
                                <select name="payment_type" required>
                                    <option value="">Selecionar</option>
                                    <option value="consorcio">Consórcio</option>
                                    <option value="financiamento">Financiamento</option>
                                    <option value="vista">À Vista</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Data da Venda</label>
                                <input type="date" name="sale_date" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Valores Financeiros -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-dollar-sign"></i>
                            Informações Financeiras
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Valor da Venda (R$) *</label>
                                <input type="number" name="sale_value" step="0.01" min="0" required placeholder="0,00">
                            </div>
                            <div class="form-group">
                                <label>Entrada (R$)</label>
                                <input type="number" name="down_payment" step="0.01" min="0" placeholder="0,00">
                            </div>
                            <div class="form-group">
                                <label>Comissão (%)</label>
                                <input type="number" name="commission_percentage" step="0.01" min="0" max="100" placeholder="5,00">
                            </div>
                            <div class="form-group">
                                <label>Parcelas</label>
                                <input type="number" name="financing_months" min="1" max="120" placeholder="12">
                            </div>
                            <div class="form-group">
                                <label>Valor da Parcela (R$)</label>
                                <input type="number" name="monthly_payment" step="0.01" min="0" placeholder="0,00">
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Observações -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-comment"></i>
                            Observações
                        </h3>
                        <div class="form-group full-width">
                            <label>Observações da Venda</label>
                            <textarea name="notes" rows="4" placeholder="Informações adicionais sobre a venda..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeNewSaleModal()">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar Venda
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Global variables
        let currentUser = {
            role: '<?= $userRole ?>',
            id: <?= $userId ?>
        };
        let currentPage = 1;
        let totalPages = 1;
        let currentFilters = {};

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing sales page...');
            
            loadSales();
            loadStats();
            loadSellers();
            
            // Initialize filters
            initializeFilters();
            
            // Initialize table event delegation early
            addTableEventDelegation();
            
            // Add debug info to console after initialization
            setTimeout(() => {
                console.log('=== SALES PAGE DEBUG INFO ===');
                console.log('Para testar funcionalidades, digite no console:');
                console.log('debugTest() - Teste completo');
                console.log('window.viewSale(1) - Ver detalhes de uma venda');
                console.log('window.editSale(1) - Editar uma venda');
                console.log('================================');
            }, 3000);
        });

        function addTableEventDelegation() {
            const tbody = document.getElementById('salesTableBody');
            if (!tbody) return;
            
            // Remove existing event listener
            if (tbody._eventHandler) {
                tbody.removeEventListener('click', tbody._eventHandler);
            }
            
            // Create new event handler
            const eventHandler = (e) => {
                const button = e.target.closest('button[data-sale-id]');
                if (!button) return;
                
                e.preventDefault();
                e.stopPropagation();
                
                const saleId = button.getAttribute('data-sale-id');
                const title = button.getAttribute('title');
                
                if (!saleId) {
                    console.error('No sale ID found on button');
                    return;
                }
                
                try {
                    switch (title) {
                        case 'Ver detalhes':
                            console.log('Calling viewSale from delegation');
                            if (typeof window.viewSale === 'function') {
                                window.viewSale(saleId);
                            } else {
                                console.error('window.viewSale not found');
                            }
                            break;
                        case 'Editar':
                            console.log('Calling editSale from delegation');
                            if (typeof window.editSale === 'function') {
                                window.editSale(saleId);
                            } else {
                                console.error('window.editSale not found');
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
            
            console.log('Sales table event delegation added');
        }

        function initializeFilters() {
            const filters = ['searchFilter', 'statusFilter', 'sellerFilter', 'periodFilter'];
            
            filters.forEach(filterId => {
                const filter = document.getElementById(filterId);
                if (filter) {
                    filter.addEventListener('input', debounce(applyFilters, 300));
                    filter.addEventListener('change', applyFilters);
                }
            });
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        async function loadStats() {
            try {
                const response = await fetch('api/sales_stats.php');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('totalSales').textContent = data.stats.total || '0';
                    document.getElementById('totalRevenue').textContent = 
                        'R$ ' + (parseFloat(data.stats.revenue || 0)).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                    document.getElementById('totalCommission').textContent = 
                        'R$ ' + (parseFloat(data.stats.commission || 0)).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                    document.getElementById('pendingSales').textContent = data.stats.pending || '0';
                } else {
                    console.error('Error loading stats:', data.message);
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        async function loadSellers() {
            try {
                const response = await fetch('api/users.php?role=seller,manager,admin');
                const data = await response.json();
                
                if (data.success && data.users) {
                    const sellerFilter = document.getElementById('sellerFilter');
                    
                    // Clear existing options except "Todos"
                    while (sellerFilter.children.length > 1) {
                        sellerFilter.removeChild(sellerFilter.lastChild);
                    }
                    
                    data.users.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.full_name;
                        sellerFilter.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading sellers:', error);
            }
        }

        async function loadSales(page = 1) {
            try {
                const params = new URLSearchParams({
                    page: page,
                    limit: 20,
                    ...currentFilters
                });
                
                const response = await fetch(`api/sales_simple.php?${params}`);
                const data = await response.json();
                
                if (data.success) {
                    renderSalesTable(data.sales || []);
                    renderPagination(data.pagination || {});
                    currentPage = page;
                    totalPages = data.pagination?.total_pages || 1;
                } else {
                    throw new Error(data.message || 'Erro ao carregar vendas');
                }
            } catch (error) {
                console.error('Error loading sales:', error);
                showError('Erro ao carregar vendas: ' + error.message);
            }
        }

        function renderSalesTable(sales) {
            const tbody = document.getElementById('salesTableBody');
            
            if (!sales || sales.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--muted-foreground);">
                            <i class="fas fa-handshake" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>Nenhuma venda encontrada</p>
                        </td>
                    </tr>
                `;
                return;
            }

            const html = sales.map(sale => `
                <tr data-sale-id="${sale.id}">
                    <td>
                        <div style="font-weight: 600; color: var(--foreground);">${sale.customer_name || sale.lead_name || 'N/A'}</div>
                        ${sale.contract_number ? `<small style="color: var(--muted-foreground);">Contrato: ${sale.contract_number}</small>` : ''}
                    </td>
                    <td>
                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="${sale.vehicle_sold || 'Não informado'}">
                            ${sale.vehicle_sold || 'Não informado'}
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--foreground);">
                            R$ ${parseFloat(sale.sale_value || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                        </div>
                        ${sale.down_payment ? `<small style="color: var(--muted-foreground);">Entrada: R$ ${parseFloat(sale.down_payment).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</small>` : ''}
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--primary);">
                            R$ ${parseFloat(sale.commission_value || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                        </div>
                        ${sale.commission_percentage ? `<small style="color: var(--muted-foreground);">${sale.commission_percentage}%</small>` : ''}
                    </td>
                    <td>
                        <div>${sale.seller_name || 'Não atribuído'}</div>
                    </td>
                    <td>
                        <span class="status-badge status-${sale.status}">
                            ${getStatusLabel(sale.status)}
                        </span>
                    </td>
                    <td>
                        <div>${formatDate(sale.sale_date)}</div>
                        <small style="color: var(--muted-foreground);">${formatTime(sale.sale_date)}</small>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-sm" title="Ver detalhes" data-sale-id="${sale.id}">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${canEditSale(sale) ? `<button class="btn-sm" title="Editar" data-sale-id="${sale.id}">
                                <i class="fas fa-edit"></i>
                            </button>` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');
            
            tbody.innerHTML = html;
        }

        function renderPagination(pagination) {
            const info = document.getElementById('paginationInfo');
            const controls = document.getElementById('paginationControls');
            
            if (!pagination.total_records) {
                info.textContent = 'Nenhum resultado encontrado';
                controls.innerHTML = '';
                return;
            }
            
            const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
            const end = Math.min(pagination.current_page * pagination.per_page, pagination.total_records);
            
            info.textContent = `Mostrando ${start} a ${end} de ${pagination.total_records} vendas`;
            
            let controlsHTML = '';
            
            // Previous button
            controlsHTML += `
                <button class="pagination-btn" ${!pagination.has_prev ? 'disabled' : ''} 
                        onclick="loadSales(${pagination.current_page - 1})">
                    <i class="fas fa-chevron-left"></i>
                </button>
            `;
            
            // Page numbers
            const totalPages = pagination.total_pages;
            const currentPage = pagination.current_page;
            
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) {
                controlsHTML += `<button class="pagination-btn" onclick="loadSales(1)">1</button>`;
                if (startPage > 2) {
                    controlsHTML += `<span class="pagination-ellipsis">...</span>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                controlsHTML += `
                    <button class="pagination-btn ${i === currentPage ? 'active' : ''}" 
                            onclick="loadSales(${i})">${i}</button>
                `;
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    controlsHTML += `<span class="pagination-ellipsis">...</span>`;
                }
                controlsHTML += `<button class="pagination-btn" onclick="loadSales(${totalPages})">${totalPages}</button>`;
            }
            
            // Next button
            controlsHTML += `
                <button class="pagination-btn" ${!pagination.has_next ? 'disabled' : ''} 
                        onclick="loadSales(${pagination.current_page + 1})">
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;
            
            controls.innerHTML = controlsHTML;
        }

        function applyFilters() {
            const search = document.getElementById('searchFilter').value;
            const status = document.getElementById('statusFilter').value;
            const seller = document.getElementById('sellerFilter').value;
            const period = document.getElementById('periodFilter').value;
            
            currentFilters = {};
            
            if (search.trim()) currentFilters.search = search.trim();
            if (status) currentFilters.status = status;
            if (seller) currentFilters.seller_id = seller;
            if (period) currentFilters.period = period;
            
            // Remove empty filters
            Object.keys(currentFilters).forEach(key => {
                if (!currentFilters[key]) {
                    delete currentFilters[key];
                }
            });

            loadSales(1); // Reset to first page
        }

        function getStatusLabel(status) {
            const labels = {
                'pending': 'Pendente',
                'confirmed': 'Confirmado',
                'cancelled': 'Cancelado'
            };
            return labels[status?.toLowerCase()] || status;
        }

        function canEditSale(sale) {
            const userRole = currentUser.role;
            const userId = currentUser.id;
            
            // Admin and managers can edit all sales
            if (['admin', 'manager'].includes(userRole)) {
                return true;
            }
            
            // Sellers can only edit their own sales and only if pending
            if (userRole === 'seller') {
                return sale.seller_id == userId && sale.status === 'pending';
            }
            
            return false;
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }

        function formatTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        }

        // Quick test function - call debugTest() from console
        window.debugTest = function() {
            console.log('=== SALES PAGE DEBUG TEST ===');
            
            // Test 1: Check if functions exist
            console.log('window.viewSale exists:', typeof window.viewSale);
            console.log('window.editSale exists:', typeof window.editSale);
            
            // Test 2: Check sales table
            const salesTable = document.querySelector('#salesTableBody');
            const tableRows = salesTable ? salesTable.querySelectorAll('tr') : [];
            console.log('Sales table exists:', !!salesTable);
            console.log('Table rows found:', tableRows.length);
            
            // Test 3: Find action buttons
            const viewButtons = document.querySelectorAll('.btn-sm[title="Ver detalhes"]');
            const editButtons = document.querySelectorAll('.btn-sm[title="Editar"]');
            console.log('View buttons found:', viewButtons.length);
            console.log('Edit buttons found:', editButtons.length);
            
            // Test 4: Show button details if any exist
            const allButtons = document.querySelectorAll('.btn-sm');
            if (allButtons.length > 0) {
                console.log('Button details:');
                allButtons.forEach((btn, idx) => {
                    console.log(`  Button ${idx}: title="${btn.title}", data-sale-id="${btn.getAttribute('data-sale-id')}"`);
                });
            } else {
                console.log('No buttons found - sales may not be loaded yet. Try running debugTest() again in a few seconds.');
            }
            
            console.log('=== END DEBUG TEST ===');
        };

        function showError(message) {
            const tbody = document.getElementById('salesTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem; color: #dc2626;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>${message}</p>
                        <button class="btn-primary" onclick="loadSales()">Tentar Novamente</button>
                    </td>
                </tr>
            `;
        }

        // Funções do Modal de Nova Venda - Tornar globais
        window.openNewSaleModal = function() {
            console.log('openNewSaleModal chamada');
            const modal = document.getElementById('newSaleModal');
            if (!modal) {
                console.error('Modal newSaleModal não encontrado');
                return;
            }
            
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Carregar leads para o select
            loadLeadsForSelect();
            
            // Reset form
            const form = document.getElementById('newSaleForm');
            if (form) {
                form.reset();
                const dateInput = document.querySelector('input[name="sale_date"]');
                if (dateInput) {
                    dateInput.value = new Date().toISOString().split('T')[0];
                }
            }
            console.log('Modal aberto');
        };

        window.closeNewSaleModal = function() {
            console.log('closeNewSaleModal chamada');
            const modal = document.getElementById('newSaleModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        };

        async function loadLeadsForSelect() {
            try {
                const response = await fetch('api/leads.php?status=new,contacted,negotiating&limit=100');
                const data = await response.json();
                
                if (data.success && data.leads) {
                    const leadSelect = document.getElementById('leadSelect');
                    
                    // Limpar opções existentes (exceto a primeira)
                    while (leadSelect.children.length > 1) {
                        leadSelect.removeChild(leadSelect.lastChild);
                    }
                    
                    // Adicionar leads
                    data.leads.forEach(lead => {
                        const option = document.createElement('option');
                        option.value = lead.id;
                        option.textContent = `${lead.lead_name || lead.name} - ${lead.phone || 'Sem telefone'}`;
                        option.dataset.leadData = JSON.stringify(lead);
                        leadSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Erro ao carregar leads:', error);
            }
        }

        // Auto-preenchimento quando um lead é selecionado
        document.addEventListener('DOMContentLoaded', function() {
            const leadSelect = document.getElementById('leadSelect');
            leadSelect?.addEventListener('change', function() {
                if (this.value) {
                    const leadData = JSON.parse(this.options[this.selectedIndex].dataset.leadData || '{}');
                    
                    // Preencher campos do formulário
                    document.querySelector('input[name="customer_name"]').value = leadData.lead_name || leadData.name || '';
                    document.querySelector('input[name="email"]').value = leadData.email || '';
                    document.querySelector('input[name="phone"]').value = leadData.phone || '';
                } else {
                    // Limpar campos se "Selecionar lead" foi escolhido
                    document.querySelector('input[name="customer_name"]').value = '';
                    document.querySelector('input[name="email"]').value = '';
                    document.querySelector('input[name="phone"]').value = '';
                }
            });
        });

        // Submissão do formulário
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('newSaleForm');
            form?.addEventListener('submit', async function(e) {
                e.preventDefault();
                await submitNewSale(this);
            });
        });

        async function submitNewSale(form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            try {
                // Desabilitar botão e mostrar loading
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
                
                // Coletar dados do formulário
                const formData = new FormData(form);
                const data = {
                    lead_id: formData.get('lead_id') || null,
                    customer_name: formData.get('customer_name'),
                    email: formData.get('email') || null,
                    phone: formData.get('phone') || null,
                    vehicle_sold: formData.get('vehicle_sold'),
                    contract_number: formData.get('contract_number') || null,
                    payment_type: formData.get('payment_type'),
                    sale_date: formData.get('sale_date') || new Date().toISOString().split('T')[0],
                    sale_value: parseFloat(formData.get('sale_value')) || 0,
                    down_payment: parseFloat(formData.get('down_payment')) || null,
                    commission_percentage: parseFloat(formData.get('commission_percentage')) || null,
                    financing_months: parseInt(formData.get('financing_months')) || null,
                    monthly_payment: parseFloat(formData.get('monthly_payment')) || null,
                    notes: formData.get('notes') || null,
                    seller_id: currentUser.id
                };

                // Validações básicas
                if (!data.customer_name.trim()) {
                    throw new Error('Nome do cliente é obrigatório');
                }
                if (!data.vehicle_sold.trim()) {
                    throw new Error('Veículo vendido é obrigatório');
                }
                if (!data.payment_type) {
                    throw new Error('Forma de pagamento é obrigatória');
                }
                if (data.sale_value <= 0) {
                    throw new Error('Valor da venda deve ser maior que zero');
                }

                // Enviar para API
                const response = await fetch('api/sales_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    // Sucesso
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Salvo!';
                    submitBtn.style.background = '#10b981';
                    
                    // Mostrar mensagem de sucesso
                    showNotification('Venda criada com sucesso!', 'success');
                    
                    // Fechar modal após delay
                    setTimeout(() => {
                        closeNewSaleModal();
                        
                        // Recarregar dados
                        loadSales(currentPage);
                        loadStats();
                        
                        // Reset button
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                        submitBtn.style.background = '';
                    }, 1500);
                    
                } else {
                    throw new Error(result.message || 'Erro ao criar venda');
                }

            } catch (error) {
                console.error('Erro ao criar venda:', error);
                
                // Mostrar erro
                showNotification('Erro ao criar venda: ' + error.message, 'error');
                
                // Re-habilitar botão
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }

        // Função para mostrar notificações
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 2rem;
                right: 2rem;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 11000;
                animation: slideIn 0.3s ease;
                max-width: 400px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            `;
            
            switch (type) {
                case 'success':
                    notification.style.background = '#10b981';
                    notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
                    break;
                case 'error':
                    notification.style.background = '#ef4444';
                    notification.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                    break;
                default:
                    notification.style.background = '#3b82f6';
                    notification.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
            }
            
            document.body.appendChild(notification);
            
            // Remover após 4 segundos
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        // Fechar modal ao clicar fora
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('newSaleModal');
            if (e.target === modal) {
                closeNewSaleModal();
            }
        });

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('newSaleModal');
                if (modal && modal.style.display === 'flex') {
                    closeNewSaleModal();
                }
            }
        });

        // Função de teste simples (temporária)
        window.testButton = function() {
            alert('Botão funcionando!');
        };

        // Backup: Adicionar event listener direto no botão
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado - procurando botão Nova Venda');
            
            // Aguardar um pouco para garantir que tudo foi carregado
            setTimeout(function() {
                const btnNewSale = document.querySelector('.btn-new-sale');
                if (btnNewSale) {
                    console.log('Botão Nova Venda encontrado, adicionando listener');
                    
                    // Remover qualquer event listener existente
                    btnNewSale.removeAttribute('onclick');
                    
                    // Adicionar novo event listener
                    btnNewSale.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('Botão Nova Venda clicado via event listener');
                        
                        // Verificar se a função existe
                        if (typeof window.openNewSaleModal === 'function') {
                            window.openNewSaleModal();
                        } else {
                            console.error('Função openNewSaleModal não encontrada');
                            alert('Erro: Função não encontrada. Recarregue a página.');
                        }
                    });
                    
                    // Adicionar atributo onclick como backup
                    btnNewSale.setAttribute('onclick', 'window.openNewSaleModal()');
                    
                } else {
                    console.error('Botão Nova Venda não encontrado');
                }

                // Verificar se modal existe
                const modal = document.getElementById('newSaleModal');
                if (!modal) {
                    console.error('Modal newSaleModal não encontrado no DOM');
                } else {
                    console.log('Modal newSaleModal encontrado no DOM');
                }
            }, 500);
        });

        // Make functions globally available for debugging
        window.viewSale = async function(id) {
            console.log('=== viewSale START ===');
            console.log('viewSale called with ID:', id);
            
            try {
                // Remove any existing detail rows first
                const existingDetailRow = document.querySelector(`#saleDetails-${id}`);
                if (existingDetailRow) {
                    existingDetailRow.remove();
                    return; // Toggle off if already open
                }
                
                // Remove any other open detail rows
                const allDetailRows = document.querySelectorAll('[id^="saleDetails-"]');
                allDetailRows.forEach(row => row.remove());
                
                // Find the sale row
                const saleRow = document.querySelector(`tr[data-sale-id="${id}"]`);
                if (!saleRow) {
                    throw new Error('Sale row not found');
                }
                
                console.log('Found sale row, inserting details...');
                
                // Create details row
                const detailsRow = document.createElement('tr');
                detailsRow.id = `saleDetails-${id}`;
                detailsRow.innerHTML = `
                    <td colspan="8" style="background: #f8fafc; border: none; padding: 0;">
                        <div class="sale-details-container" style="padding: 1.5rem; animation: slideDown 0.3s ease;">
                            <div class="loading-inline" style="text-align: center; padding: 2rem;">
                                <div class="spinner" style="border: 4px solid #f3f3f3; border-top: 4px solid #3be1c9; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                                <p style="color: #6b7280;">Carregando detalhes da venda...</p>
                            </div>
                        </div>
                    </td>
                `;
                
                // Insert after the sale row
                saleRow.parentNode.insertBefore(detailsRow, saleRow.nextSibling);
                
                // Fetch sale details
                console.log('Fetching sale details from API...');
                const response = await fetch(`api/sales_simple.php?action=details&id=${id}`);
                console.log('API response status:', response.status);
                
                const data = await response.json();
                console.log('API response data:', data);

                if (data.success) {
                    console.log('Sale data received, rendering inline details...');
                    renderInlineSaleDetails(data, detailsRow);
                    console.log('=== viewSale SUCCESS ===');
                } else {
                    console.error('API returned error:', data.message);
                    throw new Error(data.message || 'Erro ao carregar detalhes');
                }
            } catch (error) {
                console.error('=== viewSale ERROR ===');
                console.error('Error details:', error);
                
                // Show error in inline format
                const saleRow = document.querySelector(`tr[data-sale-id="${id}"]`);
                if (saleRow) {
                    const errorRow = document.createElement('tr');
                    errorRow.id = `saleDetails-${id}`;
                    errorRow.innerHTML = `
                        <td colspan="8" style="background: #fef2f2; border: none; padding: 0;">
                            <div style="padding: 1.5rem; text-align: center; color: #dc2626;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                                <h4 style="margin: 0.5rem 0;">Erro ao carregar detalhes</h4>
                                <p style="margin: 0.5rem 0;">${error.message}</p>
                                <button onclick="window.viewSale(${id})" style="margin-top: 1rem; padding: 0.5rem 1rem; background: #3be1c9; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                    Tentar Novamente
                                </button>
                            </div>
                        </td>
                    `;
                    saleRow.parentNode.insertBefore(errorRow, saleRow.nextSibling);
                }
            }
        }

        window.editSale = async function(id) {
            console.log('=== editSale START ===');
            console.log('editSale called with ID:', id);
            
            try {
                // Find the sale row
                const saleRow = document.querySelector(`tr[data-sale-id="${id}"]`);
                if (!saleRow) {
                    throw new Error('Sale row not found');
                }
                
                // Check if already in edit mode
                const existingEditRow = document.querySelector(`#saleEdit-${id}`);
                if (existingEditRow) {
                    existingEditRow.remove();
                    return; // Toggle off if already open
                }
                
                // Remove any other open edit/detail rows
                const allEditRows = document.querySelectorAll('[id^="saleEdit-"], [id^="saleDetails-"]');
                allEditRows.forEach(row => row.remove());
                
                console.log('Fetching sale data for editing...');
                
                // Fetch sale details
                const response = await fetch(`api/sales_simple.php?action=details&id=${id}`);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Erro ao carregar dados da venda');
                }
                
                console.log('Sale data received, rendering edit form...');
                renderInlineSaleEditForm(data.sale, saleRow);
                
                console.log('=== editSale SUCCESS ===');
                
            } catch (error) {
                console.error('=== editSale ERROR ===');
                console.error('Error details:', error);
                alert('Erro ao carregar dados para edição: ' + error.message);
            }
        }

        function renderInlineSaleEditForm(sale, saleRow) {
            console.log('Rendering inline edit form for sale:', sale);
            
            // Create edit row
            const editRow = document.createElement('tr');
            editRow.id = `saleEdit-${sale.id}`;
            editRow.innerHTML = `
                <td colspan="8" style="background: #f8fafc; border: none; padding: 0;">
                    <div class="sale-edit-container" style="padding: 1.5rem; animation: slideDown 0.3s ease;">
                        <div class="inline-sale-edit" style="background: white; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                            <!-- Header -->
                            <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; background: #fef3c7; border-radius: 8px 8px 0 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h3 style="margin: 0 0 0.5rem 0; color: var(--foreground); font-size: 1.25rem;">
                                            <i class="fas fa-edit" style="margin-right: 0.5rem; color: #f59e0b;"></i>
                                            Editar Venda: ${sale.customer_name || sale.lead_name || 'N/A'}
                                        </h3>
                                        <p style="margin: 0; color: var(--muted-foreground); font-size: 0.875rem;">
                                            Contrato: ${sale.contract_number || 'N/A'} | ID: ${sale.id}
                                        </p>
                                    </div>
                                    <button onclick="window.editSale(${sale.id})" style="background: #f3f4f6; border: 1px solid #d1d5db; padding: 0.5rem; border-radius: 4px; cursor: pointer; color: #6b7280;" title="Cancelar edição">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Edit Form -->
                            <div style="padding: 1.5rem;">
                                <form id="editSaleForm-${sale.id}">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                                        <!-- Financial Information -->
                                        <div>
                                            <h4 style="margin: 0 0 1rem 0; color: var(--primary); font-size: 1rem;">Informações Financeiras</h4>
                                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                                <div>
                                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--foreground); font-size: 0.875rem;">Valor da Venda (R$)*</label>
                                                    <input type="number" name="sale_value" value="${sale.sale_value || ''}" step="0.01" min="0" required 
                                                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--border); border-radius: 6px; font-size: 0.875rem;" 
                                                           placeholder="0,00">
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--foreground); font-size: 0.875rem;">Comissão (%)</label>
                                                    <input type="number" name="commission_percentage" value="${sale.commission_percentage || ''}" step="0.01" min="0" max="100" 
                                                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--border); border-radius: 6px; font-size: 0.875rem;" 
                                                           placeholder="0,00">
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--foreground); font-size: 0.875rem;">Entrada (R$)</label>
                                                    <input type="number" name="down_payment" value="${sale.down_payment || ''}" step="0.01" min="0" 
                                                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--border); border-radius: 6px; font-size: 0.875rem;" 
                                                           placeholder="0,00">
                                                </div>
                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                                    <div>
                                                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--foreground); font-size: 0.875rem;">Parcelas</label>
                                                        <input type="number" name="financing_months" value="${sale.financing_months || ''}" min="1" max="120" 
                                                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--border); border-radius: 6px; font-size: 0.875rem;" 
                                                               placeholder="12">
                                                    </div>
                                                    <div>
                                                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--foreground); font-size: 0.875rem;">Valor Parcela (R$)</label>
                                                        <input type="number" name="monthly_payment" value="${sale.monthly_payment || ''}" step="0.01" min="0" 
                                                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--border); border-radius: 6px; font-size: 0.875rem;" 
                                                               placeholder="0,00">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Sale Information -->
                                        <div>
                                            <h4 style="margin: 0 0 1rem 0; color: var(--primary); font-size: 1rem;">Detalhes da Venda</h4>
                                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                                <div>
                                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--foreground); font-size: 0.875rem;">Veículo Vendido*</label>
                                                    <input type="text" name="vehicle_sold" value="${sale.vehicle_sold || ''}" required 
                                                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--border); border-radius: 6px; font-size: 0.875rem;" 
                                                           placeholder="Ex: Honda Civic 2023">
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--foreground); font-size: 0.875rem;">Número do Contrato</label>
                                                    <input type="text" name="contract_number" value="${sale.contract_number || ''}" 
                                                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--border); border-radius: 6px; font-size: 0.875rem;" 
                                                           placeholder="123456789">
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--foreground); font-size: 0.875rem;">Forma de Pagamento</label>
                                                    <select name="payment_type" style="width: 100%; padding: 0.75rem; border: 2px solid var(--border); border-radius: 6px; font-size: 0.875rem;">
                                                        <option value="consorcio" ${sale.payment_type === 'consorcio' ? 'selected' : ''}>Consórcio</option>
                                                        <option value="financiamento" ${sale.payment_type === 'financiamento' ? 'selected' : ''}>Financiamento</option>
                                                        <option value="vista" ${sale.payment_type === 'vista' ? 'selected' : ''}>À Vista</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--foreground); font-size: 0.875rem;">Status</label>
                                                    <select name="status" style="width: 100%; padding: 0.75rem; border: 2px solid var(--border); border-radius: 6px; font-size: 0.875rem;">
                                                        <option value="pending" ${sale.status === 'pending' ? 'selected' : ''}>Pendente</option>
                                                        <option value="confirmed" ${sale.status === 'confirmed' ? 'selected' : ''}>Confirmado</option>
                                                        <option value="cancelled" ${sale.status === 'cancelled' ? 'selected' : ''}>Cancelado</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Notes -->
                                    <div style="margin-bottom: 2rem;">
                                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--foreground); font-size: 0.875rem;">Observações</label>
                                        <textarea name="notes" rows="3" 
                                                  style="width: 100%; padding: 0.75rem; border: 2px solid var(--border); border-radius: 6px; font-size: 0.875rem; resize: vertical;" 
                                                  placeholder="Observações sobre a venda...">${sale.notes || ''}</textarea>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div style="display: flex; justify-content: flex-end; gap: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                                        <button type="button" onclick="window.editSale(${sale.id})" 
                                                style="padding: 0.75rem 1.5rem; border: 1px solid #d1d5db; background: white; border-radius: 6px; cursor: pointer; color: var(--muted-foreground); font-weight: 500;">
                                            Cancelar
                                        </button>
                                        <button type="submit" 
                                                style="padding: 0.75rem 1.5rem; border: none; background: var(--primary); color: var(--primary-foreground); border-radius: 6px; cursor: pointer; font-weight: 600;">
                                            <i class="fas fa-save" style="margin-right: 0.5rem;"></i>
                                            Salvar Alterações
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </td>
            `;
            
            // Insert after the sale row
            saleRow.parentNode.insertBefore(editRow, saleRow.nextSibling);
            
            // Add form submit handler
            const form = document.getElementById(`editSaleForm-${sale.id}`);
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await saveSaleEditForm(sale.id, form);
            });
            
            console.log('Inline edit form rendered successfully');
        }

        async function saveSaleEditForm(saleId, form) {
            console.log('=== saveSaleEditForm START ===');
            console.log('Saving sale ID:', saleId);
            
            try {
                // Disable form during submit
                const submitBtn = form.querySelector('button[type="submit"]');
                const cancelBtn = form.querySelector('button[type="button"]');
                const originalSubmitText = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                cancelBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i>Salvando...';
                
                // Collect form data
                const formData = new FormData(form);
                const data = {
                    id: saleId,
                    sale_value: formData.get('sale_value'),
                    commission_percentage: formData.get('commission_percentage'),
                    vehicle_sold: formData.get('vehicle_sold'),
                    payment_type: formData.get('payment_type'),
                    down_payment: formData.get('down_payment'),
                    financing_months: formData.get('financing_months'),
                    monthly_payment: formData.get('monthly_payment'),
                    contract_number: formData.get('contract_number'),
                    notes: formData.get('notes'),
                    status: formData.get('status')
                };
                
                console.log('Form data collected:', data);
                
                // Send update request
                const response = await fetch('api/sales_simple.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                console.log('API response:', result);
                
                if (result.success) {
                    console.log('Sale updated successfully');
                    
                    // Show success feedback
                    submitBtn.innerHTML = '<i class="fas fa-check" style="margin-right: 0.5rem;"></i>Salvo!';
                    submitBtn.style.background = '#10b981';
                    
                    // Remove edit form after short delay
                    setTimeout(() => {
                        const editRow = document.getElementById(`saleEdit-${saleId}`);
                        if (editRow) editRow.remove();
                        
                        // Reload sales to show updated data
                        loadSales(currentPage);
                        loadStats(); // Update stats as well
                    }, 1500);
                    
                    console.log('=== saveSaleEditForm SUCCESS ===');
                    
                } else {
                    console.error('API returned error:', result.message);
                    throw new Error(result.message || 'Erro ao salvar alterações');
                }
                
            } catch (error) {
                console.error('=== saveSaleEditForm ERROR ===');
                console.error('Error details:', error);
                
                // Re-enable form and show error
                const submitBtn = form.querySelector('button[type="submit"]');
                const cancelBtn = form.querySelector('button[type="button"]');
                
                submitBtn.disabled = false;
                cancelBtn.disabled = false;
                submitBtn.innerHTML = originalSubmitText || '<i class="fas fa-save" style="margin-right: 0.5rem;"></i>Salvar Alterações';
                submitBtn.style.background = '';
                
                alert('Erro ao salvar alterações: ' + error.message);
            }
        }

        function renderInlineSaleDetails(data, detailsRow) {
            const { sale } = data;
            
            const detailsContainer = detailsRow.querySelector('.sale-details-container');
            detailsContainer.innerHTML = `
                <div class="inline-sale-details" style="background: white; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; background: #f0fdf4; border-radius: 8px 8px 0 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="margin: 0 0 0.5rem 0; color: var(--foreground); font-size: 1.25rem;">
                                    <i class="fas fa-handshake" style="margin-right: 0.5rem; color: #10b981;"></i>
                                    Venda: ${sale.customer_name || sale.lead_name || 'N/A'}
                                </h3>
                                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                    <span class="status-badge status-${sale.status}" style="padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 500;">
                                        ${getStatusLabel(sale.status)}
                                    </span>
                                    ${sale.contract_number ? `<span style="padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 500; background: #dbeafe; color: #1d4ed8;">Contrato: ${sale.contract_number}</span>` : ''}
                                </div>
                            </div>
                            <button onclick="window.viewSale(${sale.id})" style="background: #f3f4f6; border: 1px solid #d1d5db; padding: 0.5rem; border-radius: 4px; cursor: pointer; color: #6b7280;" title="Fechar detalhes">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Content Grid -->
                    <div style="padding: 1.5rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                            <!-- Informações Financeiras -->
                            <div>
                                <h4 style="margin: 0 0 1rem 0; color: var(--primary); font-size: 1rem;">Informações Financeiras</h4>
                                <div style="space-y: 0.75rem;">
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Valor da Venda:</strong>
                                        <div style="font-size: 1.25rem; font-weight: 600; color: var(--foreground);">
                                            R$ ${parseFloat(sale.sale_value || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                                        </div>
                                    </div>
                                    ${sale.down_payment ? `
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Entrada:</strong>
                                        <div style="color: var(--foreground);">R$ ${parseFloat(sale.down_payment).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                                    </div>
                                    ` : ''}
                                    ${sale.monthly_payment && sale.financing_months ? `
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Financiamento:</strong>
                                        <div style="color: var(--foreground);">
                                            ${sale.financing_months}x R$ ${parseFloat(sale.monthly_payment).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                                        </div>
                                    </div>
                                    ` : ''}
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Comissão:</strong>
                                        <div style="color: var(--primary); font-weight: 600;">
                                            R$ ${parseFloat(sale.commission_value || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                                            ${sale.commission_percentage ? ` (${sale.commission_percentage}%)` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informações da Venda -->
                            <div>
                                <h4 style="margin: 0 0 1rem 0; color: var(--primary); font-size: 1rem;">Detalhes da Venda</h4>
                                <div style="space-y: 0.75rem;">
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Veículo:</strong>
                                        <div style="color: var(--foreground);">${sale.vehicle_sold || 'Não informado'}</div>
                                    </div>
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Vendedor:</strong>
                                        <div style="color: var(--foreground);">${sale.seller_name || 'Não atribuído'}</div>
                                    </div>
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Data da Venda:</strong>
                                        <div style="color: var(--foreground);">${formatDate(sale.sale_date)} às ${formatTime(sale.sale_date)}</div>
                                    </div>
                                    ${sale.payment_type ? `
                                    <div style="margin-bottom: 0.75rem;">
                                        <strong style="color: var(--muted-foreground); font-size: 0.875rem;">Forma de Pagamento:</strong>
                                        <div style="color: var(--foreground);">${sale.payment_type}</div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        
                        ${sale.notes ? `
                        <div style="margin-bottom: 2rem;">
                            <h4 style="margin: 0 0 0.75rem 0; color: var(--primary); font-size: 1rem;">Observações</h4>
                            <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; border: 1px solid #e5e7eb;">
                                ${sale.notes}
                            </div>
                        </div>
                        ` : ''}
                        
                        <!-- Informações do Sistema -->
                        <div style="border-top: 1px solid #e5e7eb; padding-top: 1rem; font-size: 0.75rem; color: var(--muted-foreground);">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>Criado em: ${formatDate(sale.created_at)}</div>
                                <div>Atualizado em: ${formatDate(sale.updated_at)}</div>
                                ${sale.contract_number ? `<div>Contrato: ${sale.contract_number}</div>` : ''}
                                ${sale.lead_id ? `<div>Lead ID: ${sale.lead_id}</div>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    </script>
    
    <!-- Sidebar Scripts -->
    <?= getSidebarScripts() ?>
</body>
</html>