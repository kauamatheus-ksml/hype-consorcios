<?php
/**
 * Página de Perfil do Usuário
 * Hype Consórcios CRM
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
        'email' => $_SESSION['email'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'viewer'
    ];
}

if (!$authenticated) {
    header('Location: ../index.php');
    exit();
}

// Buscar dados completos do usuário
require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("
    SELECT id, username, email, full_name, role, status, created_at, updated_at, last_login
    FROM users 
    WHERE id = ?
");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    header('Location: ../index.php');
    exit();
}

// Incluir componentes
require_once 'components/sidebar.php';
$currentPage = 'profile';

// Buscar estatísticas do usuário baseado no role
$stats = [];

switch ($userData['role']) {
    case 'admin':
        // Admins veem estatísticas globais
        // Total de vendas (todas)
        $stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(sale_value), 0) as total_value FROM sales");
        $stmt->execute();
        $salesStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_sales'] = $salesStats['total'] ?? 0;
        $stats['total_sales_value'] = $salesStats['total_value'] ?? 0;
        
        // Vendas este mês (todas)
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total, COALESCE(SUM(sale_value), 0) as total_value 
            FROM sales 
            WHERE sale_date >= DATE_TRUNC('month', CURRENT_DATE)
            AND sale_date < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
        ");
        $stmt->execute();
        $monthlySales = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['monthly_sales'] = $monthlySales['total'] ?? 0;
        $stats['monthly_sales_value'] = $monthlySales['total_value'] ?? 0;
        
        // Total de leads (todos)
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM leads");
        $stmt->execute();
        $leadsStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_leads'] = $leadsStats['total'] ?? 0;
        
        // Leads convertidos (todos)
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM leads WHERE status = 'converted'");
        $stmt->execute();
        $convertedStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['converted_leads'] = $convertedStats['total'] ?? 0;
        break;
        
    case 'manager':
        // Managers veem estatísticas globais (como admin)
        $stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(sale_value), 0) as total_value FROM sales");
        $stmt->execute();
        $salesStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_sales'] = $salesStats['total'] ?? 0;
        $stats['total_sales_value'] = $salesStats['total_value'] ?? 0;
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total, COALESCE(SUM(sale_value), 0) as total_value 
            FROM sales 
            WHERE sale_date >= DATE_TRUNC('month', CURRENT_DATE)
            AND sale_date < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
        ");
        $stmt->execute();
        $monthlySales = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['monthly_sales'] = $monthlySales['total'] ?? 0;
        $stats['monthly_sales_value'] = $monthlySales['total_value'] ?? 0;
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM leads");
        $stmt->execute();
        $leadsStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_leads'] = $leadsStats['total'] ?? 0;
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM leads WHERE status = 'converted'");
        $stmt->execute();
        $convertedStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['converted_leads'] = $convertedStats['total'] ?? 0;
        break;
        
    case 'seller':
        // Vendedores veem apenas suas próprias estatísticas
        $stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(sale_value), 0) as total_value FROM sales WHERE seller_id = ?");
        $stmt->execute([$user['id']]);
        $salesStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_sales'] = $salesStats['total'] ?? 0;
        $stats['total_sales_value'] = $salesStats['total_value'] ?? 0;
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total, COALESCE(SUM(sale_value), 0) as total_value 
            FROM sales 
            WHERE seller_id = ?
            AND sale_date >= DATE_TRUNC('month', CURRENT_DATE)
            AND sale_date < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
        ");
        $stmt->execute([$user['id']]);
        $monthlySales = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['monthly_sales'] = $monthlySales['total'] ?? 0;
        $stats['monthly_sales_value'] = $monthlySales['total_value'] ?? 0;
        
        // Leads atribuídos ao vendedor
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM leads WHERE assigned_to = ?");
        $stmt->execute([$user['id']]);
        $leadsStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_leads'] = $leadsStats['total'] ?? 0;
        
        // Leads convertidos pelo vendedor
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM leads WHERE assigned_to = ? AND status = 'converted'");
        $stmt->execute([$user['id']]);
        $convertedStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['converted_leads'] = $convertedStats['total'] ?? 0;
        break;
        
    case 'viewer':
    default:
        // Viewers não veem estatísticas de vendas, apenas leads atribuídos
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM leads WHERE assigned_to = ?");
        $stmt->execute([$user['id']]);
        $leadsStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_leads'] = $leadsStats['total'] ?? 0;
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM leads WHERE assigned_to = ? AND status = 'converted'");
        $stmt->execute([$user['id']]);
        $convertedStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['converted_leads'] = $convertedStats['total'] ?? 0;
        break;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.ico">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - <?php echo htmlspecialchars($userData['full_name']); ?> | Hype Consórcios CRM</title>
    
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

        .page-subtitle {
            color: var(--muted-foreground);
            font-size: 1.1rem;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .profile-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border);
        }

        .profile-avatar {
            text-align: center;
            margin-bottom: 2rem;
        }

        .avatar-circle {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--primary), #2dd4bf);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: white;
            font-weight: bold;
        }

        .user-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .user-role {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .role-admin { background: #fef3c7; color: #92400e; }
        .role-manager { background: #dbeafe; color: #1e40af; }
        .role-seller { background: #d1fae5; color: #065f46; }
        .role-viewer { background: #f3f4f6; color: #374151; }

        .profile-info {
            margin-top: 2rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: var(--muted-foreground);
        }

        .info-value {
            font-weight: 600;
            color: #1e293b;
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

        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input {
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 235, 201, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            font-size: 1rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--primary-foreground);
        }

        .btn-primary:hover {
            background: #2dd4bf;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 235, 201, 0.4);
        }

        .btn-secondary {
            background: var(--secondary);
            color: var(--secondary-foreground);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: #f0fdf4;
            border-color: var(--success);
            color: #166534;
        }

        .alert-error {
            background: #fef2f2;
            border-color: var(--danger);
            color: #991b1b;
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
            
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php renderSidebar($currentPage, $userData['role'] ?? 'viewer', $userData['full_name']); ?>
        
        <div class="content-area">
            <!-- Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-user"></i>
                    Meu Perfil
                </h1>
                <p class="page-subtitle">Gerencie suas informações pessoais e configurações de conta</p>
            </div>

            <!-- Alerts -->
            <div id="alertContainer"></div>

            <!-- Profile Grid -->
            <div class="profile-grid">
                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-avatar">
                        <div class="avatar-circle">
                            <?php echo strtoupper(substr($userData['full_name'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div class="user-name"><?php echo htmlspecialchars($userData['full_name']); ?></div>
                        <span class="user-role role-<?php echo $userData['role']; ?>">
                            <?php
                            $roles = [
                                'admin' => 'Administrador',
                                'manager' => 'Gerente',
                                'seller' => 'Vendedor',
                                'viewer' => 'Visualizador'
                            ];
                            echo $roles[$userData['role']] ?? $userData['role'];
                            ?>
                        </span>
                    </div>

                    <div class="profile-info">
                        <div class="info-item">
                            <span class="info-label">Username:</span>
                            <span class="info-value"><?php echo htmlspecialchars($userData['username']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status:</span>
                            <span class="info-value" style="color: var(--success);">
                                <?php echo $userData['status'] === 'active' ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Membro desde:</span>
                            <span class="info-value">
                                <?php echo date('d/m/Y', strtotime($userData['created_at'])); ?>
                            </span>
                        </div>
                        <?php if ($userData['last_login']): ?>
                        <div class="info-item">
                            <span class="info-label">Último login:</span>
                            <span class="info-value">
                                <?php echo date('d/m/Y H:i', strtotime($userData['last_login'])); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-edit"></i>
                        Editar Informações
                    </h3>
                    
                    <form id="profileForm" onsubmit="updateProfile(event)">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Nome Completo*</label>
                                <input type="text" name="full_name" class="form-input" 
                                       value="<?php echo htmlspecialchars($userData['full_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email*</label>
                                <input type="email" name="email" class="form-input" 
                                       value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="section-title">Alterar Senha</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Senha Atual</label>
                                <input type="password" name="current_password" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" name="new_password" class="form-input">
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Confirmar Nova Senha</label>
                                <input type="password" name="confirm_password" class="form-input">
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Salvar Alterações
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                <i class="fas fa-undo"></i>
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics - Visibilidade por Role -->
            <?php if (!empty($stats)): ?>
            <div class="stats-grid">
                <?php if (isset($stats['total_leads'])): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_leads']); ?></div>
                    <div class="stat-label">Total de Leads</div>
                </div>
                <?php endif; ?>

                <?php if (isset($stats['converted_leads'])): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['converted_leads']); ?></div>
                    <div class="stat-label">Leads Convertidos</div>
                </div>
                <?php endif; ?>

                <?php if (isset($stats['total_sales'])): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_sales']); ?></div>
                    <div class="stat-label">Total de Vendas</div>
                </div>
                <?php endif; ?>

                <?php if (isset($stats['total_sales_value'])): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-value">R$ <?php echo number_format($stats['total_sales_value'], 2, ',', '.'); ?></div>
                    <div class="stat-label">Valor Total</div>
                </div>
                <?php endif; ?>

                <?php if (isset($stats['monthly_sales'])): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-month"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['monthly_sales']); ?></div>
                    <div class="stat-label">Vendas Este Mês</div>
                </div>
                <?php endif; ?>

                <?php if (isset($stats['monthly_sales_value'])): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-value">R$ <?php echo number_format($stats['monthly_sales_value'], 2, ',', '.'); ?></div>
                    <div class="stat-label">Valor Mensal</div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <?php echo getSidebarScripts(); ?>
    
    <script>
        // Variáveis globais
        const originalFormData = {
            full_name: '<?php echo htmlspecialchars($userData['full_name']); ?>',
            email: '<?php echo htmlspecialchars($userData['email']); ?>'
        };

        // Função para mostrar alertas
        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            
            const alert = document.createElement('div');
            alert.className = `alert ${alertClass}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alert);
            
            // Auto-remover após 5 segundos
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
            
            // Scroll para o topo
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Função para resetar formulário
        function resetForm() {
            const form = document.getElementById('profileForm');
            
            // Resetar campos básicos
            form.querySelector('[name="full_name"]').value = originalFormData.full_name;
            form.querySelector('[name="email"]').value = originalFormData.email;
            
            // Limpar campos de senha
            form.querySelector('[name="current_password"]').value = '';
            form.querySelector('[name="new_password"]').value = '';
            form.querySelector('[name="confirm_password"]').value = '';
            
            showAlert('Formulário resetado', 'success');
        }

        // Função para atualizar perfil
        async function updateProfile(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            // Validações
            if (!data.full_name.trim()) {
                showAlert('Nome completo é obrigatório', 'error');
                return;
            }
            
            if (!data.email.trim()) {
                showAlert('Email é obrigatório', 'error');
                return;
            }
            
            // Validação de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(data.email)) {
                showAlert('Email inválido', 'error');
                return;
            }
            
            // Validação de senha (se preenchida)
            if (data.new_password || data.confirm_password || data.current_password) {
                if (!data.current_password) {
                    showAlert('Senha atual é obrigatória para alterar a senha', 'error');
                    return;
                }
                
                if (!data.new_password) {
                    showAlert('Nova senha é obrigatória', 'error');
                    return;
                }
                
                if (data.new_password.length < 6) {
                    showAlert('Nova senha deve ter pelo menos 6 caracteres', 'error');
                    return;
                }
                
                if (data.new_password !== data.confirm_password) {
                    showAlert('Confirmação de senha não confere', 'error');
                    return;
                }
            }
            
            // Desabilitar botão durante o envio
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            
            try {
                const response = await fetch('api/profile.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message || 'Perfil atualizado com sucesso!', 'success');
                    
                    // Atualizar dados originais se houve mudança nos dados básicos
                    if (data.full_name !== originalFormData.full_name || data.email !== originalFormData.email) {
                        originalFormData.full_name = data.full_name;
                        originalFormData.email = data.email;
                        
                        // Atualizar nome na interface
                        document.querySelector('.user-name').textContent = data.full_name;
                        document.querySelector('.avatar-circle').textContent = data.full_name.charAt(0).toUpperCase();
                    }
                    
                    // Limpar campos de senha
                    form.querySelector('[name="current_password"]').value = '';
                    form.querySelector('[name="new_password"]').value = '';
                    form.querySelector('[name="confirm_password"]').value = '';
                    
                } else {
                    showAlert(result.message || 'Erro ao atualizar perfil', 'error');
                }
                
            } catch (error) {
                console.error('Erro ao atualizar perfil:', error);
                showAlert('Erro de conexão. Tente novamente.', 'error');
            } finally {
                // Reabilitar botão
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎉 Página de perfil carregada');
        });
    </script>
</body>
</html>
