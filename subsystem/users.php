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

// Verificar permissões - apenas admin e manager podem acessar
if (!in_array($userRole, ['admin', 'manager'])) {
    header('Location: dashboard.php');
    exit();
}

// Incluir componente da sidebar
require_once 'components/sidebar.php';
$currentPage = 'users';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Hype Consórcios</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.ico">

    <!-- CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Custom Styles -->
    <?= getSidebarStyles() ?>

    <style>
        :root {
            --primary: #3be1c9;
            --primary-foreground: #ffffff;
            --secondary: #f1f5f9;
            --secondary-foreground: #0f172a;
            --muted: #f8fafc;
            --muted-foreground: #64748b;
            --accent: #f1f5f9;
            --accent-foreground: #0f172a;
            --destructive: #ef4444;
            --destructive-foreground: #ffffff;
            --border: #e2e8f0;
            --input: #e2e8f0;
            --ring: #3be1c9;
            --background: #ffffff;
            --foreground: #0f172a;
            --card: #ffffff;
            --card-foreground: #0f172a;
            --dark: #1e293b;
            --dark-foreground: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            color: var(--foreground);
            line-height: 1.5;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--foreground);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--muted-foreground);
            font-size: 1rem;
        }

        .users-controls {
            display: flex;
            justify-content: between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .controls-left {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex: 1;
        }

        .controls-right {
            display: flex;
            gap: 1rem;
        }

        .filter-select {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--background);
            color: var(--foreground);
            font-size: 0.875rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--primary-foreground);
        }

        .btn-primary:hover {
            background: #2dd4bf;
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: var(--foreground);
            border: 1px solid var(--border);
        }

        .btn-outline:hover {
            background: var(--muted);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .users-table-container {
            background: var(--card);
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .users-table th {
            background: var(--muted);
            font-weight: 600;
            color: var(--foreground);
            font-size: 0.875rem;
        }

        .users-table td {
            color: var(--muted-foreground);
            font-size: 0.875rem;
        }

        .users-table tbody tr:hover {
            background: var(--muted);
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
            font-size: 0.875rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-name {
            font-weight: 500;
            color: var(--foreground);
        }

        .user-username {
            color: var(--muted-foreground);
            font-size: 0.8rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fef2f2;
            color: #991b1b;
        }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .role-admin {
            background: #fee2e2;
            color: #991b1b;
        }

        .role-manager {
            background: #fef3c7;
            color: #92400e;
        }

        .role-seller {
            background: #dbeafe;
            color: #1e40af;
        }

        .role-viewer {
            background: #f3f4f6;
            color: #374151;
        }

        .actions-dropdown {
            position: relative;
            display: inline-block;
        }

        .actions-btn {
            background: transparent;
            border: 1px solid var(--border);
            padding: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            color: var(--muted-foreground);
            transition: all 0.2s;
        }

        .actions-btn:hover {
            background: var(--muted);
            color: var(--foreground);
        }

        .actions-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 6px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 10;
            min-width: 150px;
            display: none;
        }

        .actions-menu.show {
            display: block;
        }

        .actions-menu a,
        .actions-menu button {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: var(--foreground);
            background: transparent;
            border: none;
            text-align: left;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 0.875rem;
        }

        .actions-menu a:hover,
        .actions-menu button:hover {
            background: var(--muted);
        }

        .loading {
            text-align: center;
            padding: 2rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--muted-foreground);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: var(--card);
            border-radius: 12px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }

        .modal-overlay.show .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .modal-close {
            background: transparent;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--muted-foreground);
            padding: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--foreground);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--background);
            color: var(--foreground);
            font-size: 0.875rem;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--ring);
            box-shadow: 0 0 0 3px rgba(59, 225, 201, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--background);
            color: var(--foreground);
            font-size: 0.875rem;
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .users-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .controls-left,
            .controls-right {
                justify-content: stretch;
            }

            .users-table-container {
                overflow-x: auto;
            }

            .users-table {
                min-width: 700px;
            }
        }
    </style>
</head>
<body>
    <?php
    renderSidebar($currentPage, $userRole, $userName);
    renderMobileMenuButton();
    ?>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Usuários</h1>
            <p class="page-subtitle">Gerencie os usuários do sistema</p>
        </div>

        <div class="users-controls">
            <div class="controls-left">
                <select class="filter-select" id="roleFilter" onchange="filterUsers()">
                    <option value="">Todas as funções</option>
                    <option value="admin">Administrador</option>
                    <option value="manager">Gerente</option>
                    <option value="seller">Vendedor</option>
                    <option value="viewer">Visualizador</option>
                </select>

                <select class="filter-select" id="statusFilter" onchange="filterUsers()">
                    <option value="active">Ativos</option>
                    <option value="inactive">Inativos</option>
                    <option value="">Todos os status</option>
                </select>
            </div>

            <div class="controls-right">
                <?php if ($userRole === 'admin'): ?>
                <button class="btn btn-primary" onclick="openUserModal()">
                    <i class="fas fa-plus"></i>
                    Novo Usuário
                </button>
                <?php endif; ?>

                <button class="btn btn-outline" onclick="loadUsers()">
                    <i class="fas fa-sync-alt"></i>
                    Atualizar
                </button>
            </div>
        </div>

        <div class="users-table-container">
            <div class="loading" id="loadingUsers">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Carregando usuários...</p>
            </div>

            <table class="users-table" id="usersTable" style="display: none;">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Email</th>
                        <th>Função</th>
                        <th>Status</th>
                        <th width="80">Ações</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <!-- Dados serão carregados via JavaScript -->
                </tbody>
            </table>

            <div class="empty-state" id="emptyUsers" style="display: none;">
                <i class="fas fa-users"></i>
                <h3>Nenhum usuário encontrado</h3>
                <p>Não há usuários com os filtros selecionados.</p>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div class="modal-overlay" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Novo Usuário</h2>
                <button class="modal-close" onclick="closeUserModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="userForm" onsubmit="saveUser(event)">
                <input type="hidden" id="userId" name="userId">

                <div class="form-group">
                    <label class="form-label" for="fullName">Nome Completo *</label>
                    <input type="text" class="form-input" id="fullName" name="fullName" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="username">Nome de Usuário *</label>
                    <input type="text" class="form-input" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-input" id="email" name="email">
                </div>

                <div class="form-group">
                    <label class="form-label" for="role">Função *</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Selecione uma função</option>
                        <option value="viewer">Visualizador</option>
                        <option value="seller">Vendedor</option>
                        <option value="manager">Gerente</option>
                        <?php if ($userRole === 'admin'): ?>
                        <option value="admin">Administrador</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Senha *</label>
                    <input type="password" class="form-input" id="password" name="password" required>
                    <small style="color: var(--muted-foreground); font-size: 0.8rem;">
                        Mínimo 6 caracteres
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status *</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="active">Ativo</option>
                        <option value="inactive">Inativo</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeUserModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveUserBtn">
                        <i class="fas fa-save"></i>
                        <span>Salvar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?= getSidebarScripts() ?>

    <script>
        let users = [];
        let currentEditingUser = null;

        // Carregar usuários na inicialização
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
        });

        async function loadUsers() {
            const loadingEl = document.getElementById('loadingUsers');
            const tableEl = document.getElementById('usersTable');
            const emptyEl = document.getElementById('emptyUsers');

            loadingEl.style.display = 'block';
            tableEl.style.display = 'none';
            emptyEl.style.display = 'none';

            try {
                const roleFilter = document.getElementById('roleFilter').value;
                const statusFilter = document.getElementById('statusFilter').value;

                let url = 'api/users.php?';
                if (roleFilter) url += `role=${roleFilter}&`;
                if (statusFilter) url += `status=${statusFilter}&`;

                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    users = data.users;
                    renderUsersTable();
                } else {
                    throw new Error(data.message || 'Erro ao carregar usuários');
                }
            } catch (error) {
                console.error('Erro ao carregar usuários:', error);
                showAlert('Erro ao carregar usuários: ' + error.message, 'error');
                emptyEl.style.display = 'block';
            } finally {
                loadingEl.style.display = 'none';
            }
        }

        function renderUsersTable() {
            const tableBody = document.getElementById('usersTableBody');
            const tableEl = document.getElementById('usersTable');
            const emptyEl = document.getElementById('emptyUsers');

            if (users.length === 0) {
                emptyEl.style.display = 'block';
                tableEl.style.display = 'none';
                return;
            }

            tableBody.innerHTML = users.map(user => `
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="user-avatar">
                                ${user.full_name ? user.full_name.substring(0, 2).toUpperCase() : user.username.substring(0, 2).toUpperCase()}
                            </div>
                            <div>
                                <div class="user-name">${user.full_name || user.username}</div>
                                <div class="user-username">@${user.username}</div>
                            </div>
                        </div>
                    </td>
                    <td>${user.email || '-'}</td>
                    <td>
                        <span class="role-badge role-${user.role}">
                            ${getRoleLabel(user.role)}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-${user.status}">
                            ${user.status === 'active' ? 'Ativo' : 'Inativo'}
                        </span>
                    </td>
                    <td>
                        <div class="actions-dropdown">
                            <button class="actions-btn" onclick="toggleActionsMenu(${user.id})">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="actions-menu" id="actions-${user.id}">
                                <?php if ($userRole === 'admin'): ?>
                                <button onclick="editUser(${user.id})">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button onclick="confirmDeleteUser(${user.id}, '${user.username}')">
                                    <i class="fas fa-trash"></i> Excluir
                                </button>
                                <?php else: ?>
                                <button onclick="viewUser(${user.id})">
                                    <i class="fas fa-eye"></i> Visualizar
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                </tr>
            `).join('');

            emptyEl.style.display = 'none';
            tableEl.style.display = 'table';
        }

        function getRoleLabel(role) {
            const labels = {
                'admin': 'Administrador',
                'manager': 'Gerente',
                'seller': 'Vendedor',
                'viewer': 'Visualizador'
            };
            return labels[role] || role;
        }

        function filterUsers() {
            loadUsers();
        }

        function toggleActionsMenu(userId) {
            // Fechar todos os outros menus
            document.querySelectorAll('.actions-menu').forEach(menu => {
                if (menu.id !== `actions-${userId}`) {
                    menu.classList.remove('show');
                }
            });

            // Toggle do menu atual
            const menu = document.getElementById(`actions-${userId}`);
            menu.classList.toggle('show');
        }

        // Fechar menus ao clicar fora
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.actions-dropdown')) {
                document.querySelectorAll('.actions-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });

        function openUserModal(editUser = null) {
            currentEditingUser = editUser;
            const modal = document.getElementById('userModal');
            const title = document.getElementById('modalTitle');
            const form = document.getElementById('userForm');

            if (editUser) {
                title.textContent = 'Editar Usuário';
                document.getElementById('userId').value = editUser.id;
                document.getElementById('fullName').value = editUser.full_name || '';
                document.getElementById('username').value = editUser.username;
                document.getElementById('email').value = editUser.email || '';
                document.getElementById('role').value = editUser.role;
                document.getElementById('status').value = editUser.status;
                document.getElementById('password').required = false;
                document.getElementById('password').placeholder = 'Deixe em branco para manter a senha atual';
            } else {
                title.textContent = 'Novo Usuário';
                form.reset();
                document.getElementById('password').required = true;
                document.getElementById('password').placeholder = '';
            }

            modal.classList.add('show');
        }

        function closeUserModal() {
            const modal = document.getElementById('userModal');
            modal.classList.remove('show');
            currentEditingUser = null;
        }

        function editUser(userId) {
            const user = users.find(u => u.id == userId);
            if (user) {
                openUserModal(user);
            }
        }

        function viewUser(userId) {
            const user = users.find(u => u.id == userId);
            if (user) {
                // Para usuários não-admin, apenas mostrar informações
                openUserModal(user);

                // Desabilitar todos os campos
                document.querySelectorAll('#userForm input, #userForm select').forEach(field => {
                    field.disabled = true;
                });

                // Ocultar botão salvar
                document.getElementById('saveUserBtn').style.display = 'none';
            }
        }

        async function saveUser(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const userData = {
                id: formData.get('userId') || null,
                full_name: formData.get('fullName'),
                username: formData.get('username'),
                email: formData.get('email'),
                role: formData.get('role'),
                status: formData.get('status'),
                password: formData.get('password')
            };

            const btn = document.getElementById('saveUserBtn');
            const btnText = btn.querySelector('span');
            const btnIcon = btn.querySelector('i');

            btn.disabled = true;
            btnIcon.className = 'fas fa-spinner fa-spin';
            btnText.textContent = 'Salvando...';

            try {
                const response = await fetch('api/users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(userData)
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(currentEditingUser ? 'Usuário atualizado com sucesso!' : 'Usuário criado com sucesso!', 'success');
                    closeUserModal();
                    loadUsers();
                } else {
                    throw new Error(result.message || 'Erro ao salvar usuário');
                }
            } catch (error) {
                console.error('Erro ao salvar usuário:', error);
                showAlert('Erro ao salvar usuário: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btnIcon.className = 'fas fa-save';
                btnText.textContent = 'Salvar';
            }
        }

        function confirmDeleteUser(userId, username) {
            if (confirm(`Deseja realmente excluir o usuário "${username}"?`)) {
                deleteUser(userId);
            }
        }

        async function deleteUser(userId) {
            try {
                const response = await fetch('api/users.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: userId })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Usuário excluído com sucesso!', 'success');
                    loadUsers();
                } else {
                    throw new Error(result.message || 'Erro ao excluir usuário');
                }
            } catch (error) {
                console.error('Erro ao excluir usuário:', error);
                showAlert('Erro ao excluir usuário: ' + error.message, 'error');
            }
        }

        function showAlert(message, type = 'info') {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 6px;
                color: white;
                z-index: 10000;
                max-width: 400px;
                animation: slideIn 0.3s ease;
            `;

            if (type === 'success') {
                alert.style.background = '#10b981';
            } else if (type === 'error') {
                alert.style.background = '#ef4444';
            } else {
                alert.style.background = '#3b82f6';
            }

            alert.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: between;">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()"
                            style="background: none; border: none; color: white; margin-left: 1rem; cursor: pointer; font-size: 1.2rem;">
                        ×
                    </button>
                </div>
            `;

            document.body.appendChild(alert);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.remove();
                }
            }, 5000);
        }

        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = `
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
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>