<?php
require_once __DIR__ . '/classes/Auth.php';

// Usar o sistema de autenticação por cookie
$auth = new Auth();
$sessionId = $_COOKIE['crm_session'] ?? '';

if (!$sessionId) {
    header('Location: login.php');
    exit();
}

// Validar sessão
$sessionResult = $auth->validateSession($sessionId);

if (!$sessionResult['success']) {
    header('Location: login.php');
    exit();
}

// Verificar se é admin
$user = $sessionResult['user'];
if ($user['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Definir variáveis para uso no resto do código
$isAdmin = true;
$adminId = $user['id'];
$userRole = $user['role'];
$userId = $user['id'];
$userName = $user['full_name'] ?? 'Usuário';

// Incluir componente da sidebar
require_once 'components/sidebar.php';
$currentPage = 'commission_settings';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações de Comissão - Hype Consórcios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Sidebar Styles -->
    <?= getSidebarStyles() ?>

    <style>
        :root {
            --primary: #2563eb;
            --primary-foreground: #ffffff;
            --secondary: #64748b;
            --muted: #f1f5f9;
            --muted-foreground: #64748b;
            --border: #e2e8f0;
            --foreground: #0f172a;
            --background: #ffffff;
            --dark: #1e293b;
            --dark-foreground: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: var(--foreground);
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            overflow-y: auto;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: var(--foreground);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--primary-foreground);
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: var(--foreground);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .sellers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        .seller-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .seller-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .seller-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .seller-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .seller-info .role {
            font-size: 0.875rem;
            color: var(--muted-foreground);
            text-transform: capitalize;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fef2f2;
            color: #991b1b;
        }

        .commission-details {
            padding: 1.5rem;
        }

        .commission-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .commission-row:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }

        .commission-label {
            font-size: 0.875rem;
            color: var(--muted-foreground);
        }

        .commission-value {
            font-weight: 600;
            color: var(--foreground);
        }

        .commission-highlight {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .card-actions {
            padding: 1rem 1.5rem;
            background: #f8fafc;
            display: flex;
            gap: 0.75rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: var(--muted-foreground);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--foreground);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--foreground);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .field-hint {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.75rem;
            color: var(--muted-foreground);
        }

        .modal-actions {
            padding: 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--muted-foreground);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--muted-foreground);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .sellers-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
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
            <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-users-cog"></i> Configurações de Comissão por Vendedor</h1>
                <p style="color: var(--muted-foreground); margin-top: 0.5rem;">
                    Gerencie as configurações individuais de comissão para cada vendedor
                </p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Voltar ao Dashboard
                </a>
            </div>
        </div>

        <div id="sellersContainer" class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            Carregando vendedores...
        </div>
    </div>

    <!-- Modal de Configuração -->
    <div class="modal-overlay" id="configModal">
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="modalTitle">
                    <i class="fas fa-cog"></i>
                    Configurar Comissão
                </h2>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="configForm">
                <div class="modal-body">
                    <input type="hidden" name="seller_id" id="sellerId">

                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-percentage"></i>
                            Configurações de Comissão
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Taxa de Comissão (%)</label>
                                <input type="number" name="commission_percentage" step="0.01" min="0" max="100" required>
                                <small class="field-hint">Percentual de comissão sobre o valor da venda</small>
                            </div>
                            <div class="form-group">
                                <label>Parcelas da Comissão</label>
                                <select name="commission_installments" required>
                                    <option value="1">1x (à vista)</option>
                                    <option value="2">2x</option>
                                    <option value="3">3x</option>
                                    <option value="4">4x</option>
                                    <option value="5">5x</option>
                                    <option value="6">6x</option>
                                    <option value="10">10x</option>
                                    <option value="12">12x</option>
                                </select>
                                <small class="field-hint">Número de parcelas para pagamento da comissão</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-chart-line"></i>
                            Limites de Vendas
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Valor Mínimo de Venda (R$)</label>
                                <input type="number" name="min_sale_value" step="0.01" min="0">
                                <small class="field-hint">Valor mínimo para ter direito à comissão</small>
                            </div>
                            <div class="form-group">
                                <label>Valor Máximo de Venda (R$)</label>
                                <input type="number" name="max_sale_value" step="0.01" min="0">
                                <small class="field-hint">Valor máximo para aplicar esta comissão (opcional)</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-trophy"></i>
                            Configurações de Bônus
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Bônus Adicional (%)</label>
                                <input type="number" name="bonus_percentage" step="0.01" min="0" max="100">
                                <small class="field-hint">Percentual de bônus para vendas acima do limite</small>
                            </div>
                            <div class="form-group">
                                <label>Limite para Bônus (R$)</label>
                                <input type="number" name="bonus_threshold" step="0.01" min="0">
                                <small class="field-hint">Valor mínimo para ganhar o bônus adicional</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-cog"></i>
                            Outras Configurações
                        </h3>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" checked style="margin-right: 0.5rem;">
                                Configuração ativa
                            </label>
                            <small class="field-hint">Desmarque para desativar temporariamente</small>
                        </div>
                        <div class="form-group full-width">
                            <label>Observações</label>
                            <textarea name="notes" rows="3" placeholder="Observações sobre esta configuração..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar Configuração
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let sellers = [];

        // Carregar vendedores
        async function loadSellers() {
            try {
                const response = await fetch('api/seller_commission.php?action=list');
                const result = await response.json();

                if (result.success) {
                    sellers = result.sellers;
                    renderSellers();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error('Erro ao carregar vendedores:', error);
                document.getElementById('sellersContainer').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; color: #dc2626;"></i>
                        <p>Erro ao carregar vendedores: ${error.message}</p>
                        <button class="btn btn-primary" onclick="loadSellers()" style="margin-top: 1rem;">
                            <i class="fas fa-retry"></i> Tentar Novamente
                        </button>
                    </div>
                `;
            }
        }

        // Renderizar vendedores
        function renderSellers() {
            const container = document.getElementById('sellersContainer');

            if (sellers.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>Nenhum vendedor encontrado</p>
                    </div>
                `;
                return;
            }

            const html = sellers.map(seller => {
                const commission = parseFloat(seller.commission_percentage || 1.5);
                const installments = parseInt(seller.commission_installments || 5);
                const isActive = seller.is_active !== '0';
                const hasConfig = seller.commission_percentage !== null;

                return `
                    <div class="seller-card">
                        <div class="seller-header">
                            <div class="seller-info">
                                <h3>${seller.full_name}</h3>
                                <div class="role">
                                    <i class="fas fa-user"></i>
                                    ${seller.role === 'seller' ? 'Vendedor' : seller.role === 'manager' ? 'Gerente' : 'Admin'}
                                </div>
                            </div>
                            <span class="status-badge status-${isActive ? 'active' : 'inactive'}">
                                ${isActive ? 'Ativo' : 'Inativo'}
                            </span>
                        </div>

                        <div class="commission-details">
                            <div class="commission-row">
                                <span class="commission-label">Taxa de Comissão:</span>
                                <span class="commission-value commission-highlight">${commission}%</span>
                            </div>
                            <div class="commission-row">
                                <span class="commission-label">Parcelas:</span>
                                <span class="commission-value">${installments}x</span>
                            </div>
                            ${seller.min_sale_value > 0 ? `
                                <div class="commission-row">
                                    <span class="commission-label">Valor Mínimo:</span>
                                    <span class="commission-value">R$ ${parseFloat(seller.min_sale_value).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                                </div>
                            ` : ''}
                            ${seller.max_sale_value ? `
                                <div class="commission-row">
                                    <span class="commission-label">Valor Máximo:</span>
                                    <span class="commission-value">R$ ${parseFloat(seller.max_sale_value).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                                </div>
                            ` : ''}
                            ${seller.bonus_percentage > 0 ? `
                                <div class="commission-row">
                                    <span class="commission-label">Bônus:</span>
                                    <span class="commission-value">${seller.bonus_percentage}% (acima de R$ ${parseFloat(seller.bonus_threshold || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})})</span>
                                </div>
                            ` : ''}
                            ${seller.commission_updated_at ? `
                                <div class="commission-row">
                                    <span class="commission-label">Última Atualização:</span>
                                    <span class="commission-value">${formatDate(seller.commission_updated_at)}</span>
                                </div>
                            ` : ''}
                        </div>

                        <div class="card-actions">
                            <button class="btn btn-primary btn-sm" onclick="configureCommission(${seller.id})">
                                <i class="fas fa-cog"></i>
                                ${hasConfig ? 'Editar' : 'Configurar'}
                            </button>
                            ${seller.notes ? `
                                <button class="btn btn-secondary btn-sm" onclick="showNotes('${seller.full_name}', '${seller.notes}')">
                                    <i class="fas fa-sticky-note"></i>
                                    Observações
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = `<div class="sellers-grid">${html}</div>`;
        }

        // Configurar comissão
        async function configureCommission(sellerId) {
            try {
                const seller = sellers.find(s => s.id == sellerId);
                if (!seller) {
                    throw new Error('Vendedor não encontrado');
                }

                // Preencher formulário
                document.getElementById('modalTitle').innerHTML = `
                    <i class="fas fa-cog"></i>
                    Configurar Comissão - ${seller.full_name}
                `;

                document.getElementById('sellerId').value = sellerId;
                document.querySelector('input[name="commission_percentage"]').value = seller.commission_percentage || 1.5;
                document.querySelector('select[name="commission_installments"]').value = seller.commission_installments || 5;
                document.querySelector('input[name="min_sale_value"]').value = seller.min_sale_value || '';
                document.querySelector('input[name="max_sale_value"]').value = seller.max_sale_value || '';
                document.querySelector('input[name="bonus_percentage"]').value = seller.bonus_percentage || '';
                document.querySelector('input[name="bonus_threshold"]').value = seller.bonus_threshold || '';
                document.querySelector('input[name="is_active"]').checked = seller.is_active !== '0';
                document.querySelector('textarea[name="notes"]').value = seller.notes || '';

                // Mostrar modal
                document.getElementById('configModal').classList.add('show');
                document.body.style.overflow = 'hidden';

            } catch (error) {
                alert('Erro: ' + error.message);
            }
        }

        // Fechar modal
        function closeModal() {
            document.getElementById('configModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        // Salvar configuração
        async function saveConfiguration(event) {
            event.preventDefault();

            const form = document.getElementById('configForm');
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

                const formData = new FormData(form);
                const data = {
                    seller_id: formData.get('seller_id'),
                    commission_percentage: parseFloat(formData.get('commission_percentage')),
                    commission_installments: parseInt(formData.get('commission_installments')),
                    min_sale_value: parseFloat(formData.get('min_sale_value')) || 0,
                    max_sale_value: formData.get('max_sale_value') ? parseFloat(formData.get('max_sale_value')) : null,
                    bonus_percentage: parseFloat(formData.get('bonus_percentage')) || 0,
                    bonus_threshold: formData.get('bonus_threshold') ? parseFloat(formData.get('bonus_threshold')) : null,
                    is_active: formData.has('is_active'),
                    notes: formData.get('notes')
                };

                const response = await fetch('api/seller_commission.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert('Configuração salva com sucesso!');
                    closeModal();
                    loadSellers(); // Recarregar lista
                } else {
                    throw new Error(result.message);
                }

            } catch (error) {
                alert('Erro ao salvar: ' + error.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }

        // Mostrar observações
        function showNotes(sellerName, notes) {
            alert(`Observações para ${sellerName}:\n\n${notes}`);
        }

        // Formatar data
        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Event listeners
        document.getElementById('configForm').addEventListener('submit', saveConfiguration);

        // Fechar modal ao clicar fora
        document.getElementById('configModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Carregar vendedores ao inicializar
        loadSellers();
    </script>

    <!-- Sidebar Scripts -->
    <?= getSidebarScripts() ?>
            </div>
        </main>
    </div>
</body>
</html>