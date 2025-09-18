<?php
// Componente Sidebar Reutilizável para o Subsistema

// Função para renderizar a sidebar
function renderSidebar($currentPage, $userRole, $userName) {
?>
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
            <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-home nav-icon"></i>
                Dashboard
            </a>
        </div>
        
        <div class="nav-item">
            <a href="leads.php" class="nav-link <?= $currentPage === 'leads' ? 'active' : '' ?>">
                <i class="fas fa-users nav-icon"></i>
                Leads
            </a>
        </div>
        
        <?php if (in_array($userRole, ['admin', 'manager', 'seller'])): ?>
        <div class="nav-item">
            <a href="sales.php" class="nav-link <?= $currentPage === 'sales' ? 'active' : '' ?>">
                <i class="fas fa-handshake nav-icon"></i>
                Vendas
            </a>
        </div>
        <?php endif; ?>
        
        <?php if (in_array($userRole, ['admin', 'manager'])): ?>
        <div class="nav-item">
            <a href="#" class="nav-link" onclick="showComingSoon('Relatórios')">
                <i class="fas fa-chart-bar nav-icon"></i>
                Relatórios
            </a>
        </div>
        
        <div class="nav-item">
            <a href="users.php" class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>">
                <i class="fas fa-user-cog nav-icon"></i>
                Usuários
            </a>
        </div>
        <?php endif; ?>

        <div class="nav-item">
            <a href="profile.php" class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                <i class="fas fa-user nav-icon"></i>
                Perfil
            </a>
        </div>
        
        <?php if (in_array($userRole, ['admin'])): ?>
        <div class="nav-item">
            <a href="commission_settings.php" class="nav-link <?= $currentPage === 'commission_settings' ? 'active' : '' ?>">
                <i class="fas fa-percentage nav-icon"></i>
                Configurações de Comissão
            </a>
        </div>

        <div class="nav-item">
            <a href="site-config.php" class="nav-link <?= $currentPage === 'site-config' ? 'active' : '' ?>">
                <i class="fas fa-cog nav-icon"></i>
                Configurações do Site
            </a>
        </div>

        <div class="nav-item">
            <a href="audit-logs.php" class="nav-link <?= $currentPage === 'audit-logs' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list nav-icon"></i>
                Logs de Auditoria
            </a>
        </div>
        <?php endif; ?>
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
<?php
}

// Função para incluir os estilos da sidebar
function getSidebarStyles() {
    ob_start();
    ?>
    <style>
        /* Sidebar Styles */
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

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.open,
            .sidebar.show {
                transform: translateX(0);
            }
        }
    </style>
    <?php
    return ob_get_clean();
}

// Função para incluir os scripts JavaScript da sidebar
function getSidebarScripts() {
    ob_start();
    ?>
    <script>
        // Sidebar JavaScript Functions
        
        function showComingSoon(sectionName) {
            // Criar modal de "Em breve"
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.3s ease;
            `;
            
            modal.innerHTML = `
                <div style="
                    background: white;
                    padding: 2rem;
                    border-radius: 12px;
                    text-align: center;
                    max-width: 400px;
                    margin: 1rem;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                    animation: slideUp 0.3s ease;
                ">
                    <div style="
                        width: 64px;
                        height: 64px;
                        background: linear-gradient(135deg, #3be1c9, #2dd4bf);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 1.5rem;
                        font-size: 2rem;
                        color: white;
                    ">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3 style="
                        margin: 0 0 1rem 0;
                        color: var(--foreground);
                        font-size: 1.5rem;
                        font-weight: 600;
                    ">Em Breve!</h3>
                    <p style="
                        margin: 0 0 2rem 0;
                        color: var(--muted-foreground);
                        line-height: 1.5;
                    ">A seção "<strong>${sectionName}</strong>" está sendo desenvolvida e estará disponível em breve. Continue acompanhando as atualizações!</p>
                    <button onclick="this.closest('[style*=position]').remove()" style="
                        background: var(--primary);
                        color: var(--primary-foreground);
                        border: none;
                        padding: 0.75rem 2rem;
                        border-radius: 8px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s;
                    " onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
                        Entendi
                    </button>
                </div>
            `;
            
            // Adicionar animações CSS
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideUp {
                    from { 
                        opacity: 0;
                        transform: translateY(20px);
                    }
                    to { 
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            `;
            document.head.appendChild(style);
            document.body.appendChild(modal);
            
            // Fechar ao clicar fora
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                    style.remove();
                }
            });
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('open');
                sidebar.classList.toggle('show');
                
                // For dashboard container overlay
                const container = document.getElementById('dashboardContainer');
                if (container) {
                    container.classList.toggle('sidebar-open');
                }
            }
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
                
                if (sidebar && mobileBtn && !sidebar.contains(e.target) && !mobileBtn.contains(e.target)) {
                    sidebar.classList.remove('open');
                    sidebar.classList.remove('show');
                    
                    const container = document.getElementById('dashboardContainer');
                    if (container) {
                        container.classList.remove('sidebar-open');
                    }
                }
            }
        });
    </script>
    <?php
    return ob_get_clean();
}

// Função para incluir um botão mobile menu
function renderMobileMenuButton() {
?>
<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" onclick="toggleSidebar()" style="display: none;">
    <i class="fas fa-bars"></i>
</button>

<style>
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

    @media (max-width: 768px) {
        .mobile-menu-btn {
            display: block !important;
        }
    }
</style>
<?php
}
?>