<?php
/**
 * Página de Logs de Auditoria
 * Hype Consórcios CRM - Apenas para Administradores
 */

// Iniciar sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
$authenticated = false;
$user = null;

if (isset($_COOKIE['crm_session'])) {
    require_once 'classes/Auth.php';
    $auth = new Auth();
    $sessionResult = $auth->validateSession($_COOKIE['crm_session']);
    
    if ($sessionResult['success']) {
        $authenticated = true;
        $user = $sessionResult['user'];
    }
} 
elseif (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $authenticated = true;
    $user = [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'viewer'
    ];
}

if (!$authenticated || !in_array($user['role'], ['admin', 'manager'])) {
    header('Location: dashboard.php');
    exit();
}

// Incluir dependências
require_once 'config/database.php';
require_once 'classes/AuditLogger.php';
require_once 'components/sidebar.php';

$currentPage = 'audit-logs';
$database = new Database();
$conn = $database->getConnection();
$auditLogger = new AuditLogger($conn);

// Parâmetros de paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

// Buscar logs
$logs = $auditLogger->getRecentLogs($limit, $offset);

// Contar total para paginação
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM audit_logs");
$stmt->execute();
$totalLogs = $stmt->fetch()['total'];
$totalPages = ceil($totalLogs / $limit);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.ico">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Auditoria | Hype Consórcios CRM</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #3bebc9;
            --primary-foreground: #ffffff;
            --secondary: #f1f5f9;
            --secondary-foreground: #0f172a;
            --muted: #f8fafc;
            --muted-foreground: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        /* Reset apenas para área de conteúdo, não para sidebar */
        .content-area,
        .content-area * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .main-container {
            display: flex;
            min-height: 100vh;
        }

        .content-area {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .logs-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logs-table th,
        .logs-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .logs-table th {
            background: var(--muted);
            font-weight: 600;
            color: #374151;
            position: sticky;
            top: 0;
        }

        .logs-table tr:hover {
            background: #f8fafc;
        }

        .action-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .action-login-success { background: #d1fae5; color: #065f46; }
        .action-login-failed { background: #fee2e2; color: #991b1b; }
        .action-logout { background: #fef3c7; color: #92400e; }
        .action-profile-update { background: #dbeafe; color: #1e40af; }
        .action-password-change { background: #fce7f3; color: #be185d; }
        .action-default { background: #f3f4f6; color: #374151; }

        .timestamp {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            color: var(--muted-foreground);
        }

        .ip-address {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            padding: 0.125rem 0.375rem;
            background: #f1f5f9;
            border-radius: 4px;
            color: #475569;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 2rem;
            background: white;
            border-top: 1px solid var(--border);
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
            transition: all 0.2s ease;
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination .current {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border);
            text-align: center;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), #2dd4bf);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--muted-foreground);
            font-size: 0.875rem;
        }

        /* Estilos da Sidebar diretamente na página */
        .sidebar {
            width: 280px;
            background: #1a1b23;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
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
            color: #ffffff;
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
            border-left-color: #3bebc9;
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
            background: #3bebc9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 600;
            color: #1a1b23;
        }

        .user-details h4 {
            margin: 0;
            font-size: 0.875rem;
            font-weight: 600;
            color: white;
        }

        .user-details p {
            margin: 0;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
            text-transform: capitalize;
        }

        .logout-btn {
            width: 100%;
            padding: 0.75rem;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .content-area {
                margin-left: 0;
                padding: 1rem;
            }
            
            .logs-table-container {
                overflow-x: auto;
            }
            
            .logs-table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php renderSidebar($currentPage, $user['role'], $user['full_name']); ?>
        
        <div class="content-area">
            <!-- Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-clipboard-list"></i>
                    Logs de Auditoria
                </h1>
                <p class="page-subtitle">Histórico de atividades e alterações no sistema</p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalLogs); ?></div>
                    <div class="stat-label">Total de Logs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-value">
                        <?php 
                        $stmt = $conn->prepare("SELECT COUNT(*) as today FROM audit_logs WHERE DATE(created_at) = CURRENT_DATE()");
                        $stmt->execute();
                        echo number_format($stmt->fetch()['today']);
                        ?>
                    </div>
                    <div class="stat-label">Hoje</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-value">
                        <?php 
                        $stmt = $conn->prepare("SELECT COUNT(*) as week FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                        $stmt->execute();
                        echo number_format($stmt->fetch()['week']);
                        ?>
                    </div>
                    <div class="stat-label">Últimos 7 dias</div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="logs-table-container">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Usuário</th>
                            <th>Ação</th>
                            <th>Descrição</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: var(--muted-foreground);">
                                <i class="fas fa-info-circle"></i>
                                Nenhum log encontrado
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <span class="timestamp">
                                    <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($log['full_name']): ?>
                                    <strong><?php echo htmlspecialchars($log['full_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($log['username'] ?? ''); ?></small>
                                <?php else: ?>
                                    <span style="color: var(--muted-foreground);">Sistema</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $actionClass = 'action-default';
                                switch ($log['action']) {
                                    case 'LOGIN_SUCCESS': $actionClass = 'action-login-success'; break;
                                    case 'LOGIN_FAILED': $actionClass = 'action-login-failed'; break;
                                    case 'LOGOUT': $actionClass = 'action-logout'; break;
                                    case 'PROFILE_UPDATE': $actionClass = 'action-profile-update'; break;
                                    case 'PASSWORD_CHANGE': $actionClass = 'action-password-change'; break;
                                }
                                ?>
                                <span class="action-badge <?php echo $actionClass; ?>">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($log['description'] ?? ''); ?>
                            </td>
                            <td>
                                <?php if ($log['ip_address']): ?>
                                <span class="ip-address"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>">
                            Próximo <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <?php echo getSidebarScripts(); ?>
    
    <script>
        // Auto-refresh da página a cada 2 minutos
        setTimeout(() => {
            location.reload();
        }, 120000);
        
        // Adicionar tooltip com detalhes completos
        document.querySelectorAll('.logs-table tr').forEach(row => {
            row.addEventListener('mouseover', function() {
                // Adicionar hover effects ou tooltips se necessário
            });
        });
        
        console.log('📋 Página de logs de auditoria carregada');
        console.log('📊 Total de logs:', <?php echo $totalLogs; ?>);
    </script>
</body>
</html>