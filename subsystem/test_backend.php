<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Backend Completo - Hype Consórcios CRM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.2em; opacity: 0.9; }
        
        .content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 40px;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 25px;
            background: #f8f9fa;
        }
        
        .section h3 {
            color: #495057;
            margin-bottom: 20px;
            font-size: 1.5em;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #495057;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
            transition: transform 0.2s;
        }
        
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        
        .btn-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .btn-danger { background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%); }
        .btn-info { background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%); }
        
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .result.info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .session-info {
            background: #e2e3e5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #6c757d;
            margin-top: 5px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .data-table th,
        .data-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .data-table tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Teste Backend Completo</h1>
            <p>Sistema CRM - Hype Consórcios</p>
            <div id="currentUser" style="margin-top: 15px; font-size: 0.9em;"></div>
        </div>

        <div class="content">
            <!-- Seção de Login/Autenticação -->
            <div class="section">
                <h3>🔐 Autenticação</h3>
                
                <div id="loginSection">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Usuário/Email:</label>
                            <input type="text" id="loginUsername" placeholder="admin" value="admin">
                        </div>
                        <div class="form-group">
                            <label>Senha:</label>
                            <input type="password" id="loginPassword" placeholder="password" value="password">
                        </div>
                    </div>
                    <button class="btn" onclick="login()">🔑 Fazer Login</button>
                    <button class="btn btn-info" onclick="validateSession()">✓ Validar Sessão</button>
                </div>

                <div id="loggedInSection" style="display: none;">
                    <div class="session-info">
                        <strong>Usuário Logado:</strong> <span id="userInfo"></span>
                        <button class="btn btn-danger" onclick="logout()" style="float: right;">🚪 Logout</button>
                    </div>
                </div>

                <div id="authResult" class="result" style="display: none;"></div>
            </div>

            <!-- Seção de Gerenciamento de Usuários -->
            <div class="section">
                <h3>👥 Gerenciamento de Usuários</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nome de Usuário:</label>
                        <input type="text" id="newUsername" placeholder="vendedor1">
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" id="newEmail" placeholder="vendedor@email.com">
                    </div>
                    <div class="form-group">
                        <label>Nome Completo:</label>
                        <input type="text" id="newFullName" placeholder="João da Silva">
                    </div>
                    <div class="form-group">
                        <label>Função:</label>
                        <select id="newRole">
                            <option value="viewer">Viewer</option>
                            <option value="seller">Seller</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Senha:</label>
                        <input type="password" id="newPassword" placeholder="senha123">
                    </div>
                </div>

                <button class="btn btn-success" onclick="createUser()">➕ Criar Usuário</button>
                <button class="btn btn-info" onclick="listUsers()">📋 Listar Usuários</button>

                <div id="usersResult" class="result" style="display: none;"></div>
            </div>

            <!-- Seção de Teste de Captura de Leads -->
            <div class="section">
                <h3>📝 Teste de Captura de Leads</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nome:</label>
                        <input type="text" id="leadName" placeholder="Cliente Teste" value="João Silva">
                    </div>
                    <div class="form-group">
                        <label>Telefone:</label>
                        <input type="text" id="leadPhone" placeholder="(47) 99999-9999" value="47999999999">
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" id="leadEmail" placeholder="cliente@email.com" value="joao@teste.com">
                    </div>
                    <div class="form-group">
                        <label>Veículo de Interesse:</label>
                        <input type="text" id="leadVehicle" placeholder="Volkswagen Polo" value="Volkswagen Polo 2024">
                    </div>
                    <div class="form-group">
                        <label>Tem Entrada:</label>
                        <select id="leadDownPayment">
                            <option value="no">Não</option>
                            <option value="yes">Sim</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Valor da Entrada:</label>
                        <input type="number" id="leadDownPaymentValue" placeholder="10000" value="15000">
                    </div>
                </div>

                <button class="btn btn-success" onclick="captureLead()">📨 Capturar Lead</button>
                <button class="btn btn-info" onclick="listLeads()">📋 Listar Leads</button>
                <button class="btn btn-info" onclick="getLeadStats()">📊 Estatísticas</button>

                <div id="leadsResult" class="result" style="display: none;"></div>
            </div>

            <!-- Seção de Gerenciamento de Leads -->
            <div class="section">
                <h3>🎯 Gerenciamento de Leads</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>ID do Lead:</label>
                        <input type="number" id="leadId" placeholder="1">
                    </div>
                    <div class="form-group">
                        <label>Novo Status:</label>
                        <select id="leadStatus">
                            <option value="new">Novo</option>
                            <option value="contacted">Contatado</option>
                            <option value="negotiating">Negociando</option>
                            <option value="converted">Convertido</option>
                            <option value="lost">Perdido</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Observações:</label>
                        <input type="text" id="leadNotes" placeholder="Cliente interessado...">
                    </div>
                    <div class="form-group">
                        <label>Atribuir para Usuário ID:</label>
                        <input type="number" id="assignToUser" placeholder="2">
                    </div>
                </div>

                <button class="btn" onclick="updateLead()">✏️ Atualizar Lead</button>
                <button class="btn" onclick="assignLead()">👤 Atribuir Lead</button>
                <button class="btn" onclick="getLeadWhatsApp()">💬 Link WhatsApp</button>
                <button class="btn" onclick="addInteraction()">📞 Adicionar Interação</button>

                <div id="leadManagementResult" class="result" style="display: none;"></div>
            </div>

            <!-- Seção de Gerenciamento de Vendas -->
            <div class="section">
                <h3>💰 Gerenciamento de Vendas</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>ID do Lead para Converter:</label>
                        <input type="number" id="saleLeadId" placeholder="1">
                    </div>
                    <div class="form-group">
                        <label>ID do Vendedor:</label>
                        <input type="number" id="saleSellerId" placeholder="2">
                    </div>
                    <div class="form-group">
                        <label>Valor da Venda:</label>
                        <input type="number" id="saleValue" placeholder="50000" value="45000">
                    </div>
                    <div class="form-group">
                        <label>% Comissão:</label>
                        <input type="number" id="saleCommission" placeholder="3.5" step="0.1" value="3.5">
                    </div>
                    <div class="form-group">
                        <label>Veículo Vendido:</label>
                        <input type="text" id="saleVehicle" placeholder="Volkswagen Polo 2024" value="Volkswagen Polo 2024">
                    </div>
                    <div class="form-group">
                        <label>Número do Contrato:</label>
                        <input type="text" id="saleContract" placeholder="VW2024001" value="VW2024001">
                    </div>
                </div>

                <button class="btn btn-success" onclick="convertSale()">🎯 Converter em Venda</button>
                <button class="btn btn-info" onclick="listSales()">📋 Listar Vendas</button>
                <button class="btn btn-info" onclick="getSalesReport()">📊 Relatório de Vendas</button>
                <button class="btn btn-info" onclick="getSalesDashboard()">📈 Dashboard</button>

                <div id="salesResult" class="result" style="display: none;"></div>
            </div>

            <!-- Seção de Ações Rápidas -->
            <div class="section">
                <h3>⚡ Ações Rápidas</h3>
                
                <div class="quick-actions">
                    <button class="btn btn-info" onclick="testConnection()">🔗 Testar Conexão</button>
                    <button class="btn btn-info" onclick="cleanupSessions()">🧹 Limpar Sessões</button>
                    <button class="btn btn-info" onclick="getSystemStats()">📊 Estatísticas Gerais</button>
                    <button class="btn btn-success" onclick="simulateFullWorkflow()">🚀 Simular Fluxo Completo</button>
                </div>

                <div id="quickActionsResult" class="result" style="display: none;"></div>
            </div>
        </div>
    </div>

    <script>
        let currentSessionId = null;
        let currentUser = null;

        // Função para fazer requisições à API
        async function apiRequest(url, method = 'GET', data = null) {
            try {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    }
                };

                if (data) {
                    options.body = JSON.stringify(data);
                }

                const response = await fetch(url, options);
                return await response.json();
            } catch (error) {
                return { success: false, message: 'Erro de conexão: ' + error.message };
            }
        }

        // Mostrar resultado na tela
        function showResult(elementId, result, type = null) {
            const element = document.getElementById(elementId);
            element.style.display = 'block';
            element.textContent = JSON.stringify(result, null, 2);
            
            if (type) {
                element.className = `result ${type}`;
            } else {
                element.className = result.success ? 'result success' : 'result error';
            }
        }

        // === AUTENTICAÇÃO ===
        async function login() {
            const username = document.getElementById('loginUsername').value;
            const password = document.getElementById('loginPassword').value;

            const result = await apiRequest('api/auth.php?action=login', 'POST', {
                username: username,
                password: password,
                remember: true
            });

            if (result.success) {
                currentSessionId = result.session_id;
                currentUser = result.user;
                updateUIAfterLogin();
            }

            showResult('authResult', result);
        }

        async function logout() {
            const result = await apiRequest('api/auth.php?action=logout', 'POST', {
                session_id: currentSessionId
            });

            currentSessionId = null;
            currentUser = null;
            updateUIAfterLogout();
            showResult('authResult', result);
        }

        async function validateSession() {
            const result = await apiRequest('api/auth.php?action=validate');
            if (result.success) {
                currentUser = result.user;
                updateUIAfterLogin();
            }
            showResult('authResult', result);
        }

        function updateUIAfterLogin() {
            document.getElementById('loginSection').style.display = 'none';
            document.getElementById('loggedInSection').style.display = 'block';
            document.getElementById('userInfo').textContent = 
                `${currentUser.full_name} (${currentUser.role})`;
            document.getElementById('currentUser').innerHTML = 
                `👤 Logado como: <strong>${currentUser.full_name}</strong> | Função: <strong>${currentUser.role}</strong>`;
        }

        function updateUIAfterLogout() {
            document.getElementById('loginSection').style.display = 'block';
            document.getElementById('loggedInSection').style.display = 'none';
            document.getElementById('currentUser').innerHTML = '❌ Não autenticado';
        }

        // === USUÁRIOS ===
        async function createUser() {
            const result = await apiRequest('api/auth.php?action=create_user', 'POST', {
                username: document.getElementById('newUsername').value,
                email: document.getElementById('newEmail').value,
                full_name: document.getElementById('newFullName').value,
                role: document.getElementById('newRole').value,
                password: document.getElementById('newPassword').value
            });

            showResult('usersResult', result);
        }

        async function listUsers() {
            const result = await apiRequest('api/auth.php?action=users');
            showResult('usersResult', result);
        }

        // === LEADS ===
        async function captureLead() {
            const result = await apiRequest('api/capture_lead.php', 'POST', {
                name: document.getElementById('leadName').value,
                phone: document.getElementById('leadPhone').value,
                email: document.getElementById('leadEmail').value,
                vehicle: document.getElementById('leadVehicle').value,
                hasDownPayment: document.getElementById('leadDownPayment').value,
                downPayment: document.getElementById('leadDownPaymentValue').value,
                source: 'test_backend'
            });

            showResult('leadsResult', result);
        }

        async function listLeads() {
            const result = await apiRequest('api/leads.php?action=list');
            showResult('leadsResult', result);
        }

        async function getLeadStats() {
            const result = await apiRequest('api/leads.php?action=stats');
            showResult('leadsResult', result);
        }

        async function updateLead() {
            const leadId = document.getElementById('leadId').value;
            const result = await apiRequest('api/leads.php?action=update', 'POST', {
                id: leadId,
                status: document.getElementById('leadStatus').value,
                notes: document.getElementById('leadNotes').value
            });

            showResult('leadManagementResult', result);
        }

        async function assignLead() {
            const result = await apiRequest('api/leads.php?action=assign', 'POST', {
                lead_id: document.getElementById('leadId').value,
                user_id: document.getElementById('assignToUser').value
            });

            showResult('leadManagementResult', result);
        }

        async function getLeadWhatsApp() {
            const leadId = document.getElementById('leadId').value;
            const result = await apiRequest(`api/leads.php?action=whatsapp_url&id=${leadId}`);
            
            if (result.success && result.whatsapp_url) {
                window.open(result.whatsapp_url, '_blank');
            }
            
            showResult('leadManagementResult', result);
        }

        async function addInteraction() {
            const result = await apiRequest('api/leads.php?action=interaction', 'POST', {
                lead_id: document.getElementById('leadId').value,
                type: 'note',
                description: document.getElementById('leadNotes').value || 'Interação de teste'
            });

            showResult('leadManagementResult', result);
        }

        // === VENDAS ===
        async function convertSale() {
            const result = await apiRequest('api/sales.php?action=convert', 'POST', {
                lead_id: document.getElementById('saleLeadId').value,
                seller_id: document.getElementById('saleSellerId').value,
                sale_value: document.getElementById('saleValue').value,
                commission_percentage: document.getElementById('saleCommission').value,
                vehicle_sold: document.getElementById('saleVehicle').value,
                contract_number: document.getElementById('saleContract').value,
                status: 'confirmed'
            });

            showResult('salesResult', result);
        }

        async function listSales() {
            const result = await apiRequest('api/sales.php?action=list');
            showResult('salesResult', result);
        }

        async function getSalesReport() {
            const result = await apiRequest('api/sales.php?action=report');
            showResult('salesResult', result);
        }

        async function getSalesDashboard() {
            const result = await apiRequest('api/sales.php?action=dashboard');
            showResult('salesResult', result);
        }

        // === AÇÕES RÁPIDAS ===
        async function testConnection() {
            const result = await apiRequest('../test_connection.php');
            showResult('quickActionsResult', { message: 'Teste de conexão executado. Verifique a página de teste.' }, 'info');
        }

        async function cleanupSessions() {
            const result = await apiRequest('api/auth.php?action=cleanup');
            showResult('quickActionsResult', result);
        }

        async function getSystemStats() {
            const results = await Promise.all([
                apiRequest('api/leads.php?action=stats'),
                apiRequest('api/sales.php?action=dashboard'),
                apiRequest('api/auth.php?action=users')
            ]);

            const stats = {
                leads: results[0],
                sales: results[1], 
                users: results[2]
            };

            showResult('quickActionsResult', stats, 'info');
        }

        async function simulateFullWorkflow() {
            showResult('quickActionsResult', { message: 'Iniciando simulação do fluxo completo...' }, 'info');
            
            const workflow = [];
            
            // 1. Capturar lead
            const leadResult = await apiRequest('api/capture_lead.php', 'POST', {
                name: 'Cliente Simulação',
                phone: '47987654321',
                email: 'simulacao@teste.com',
                vehicle: 'Volkswagen T-Cross 2024',
                hasDownPayment: 'yes',
                downPayment: '20000',
                source: 'simulacao_completa'
            });
            workflow.push({ step: '1. Capturar Lead', result: leadResult });

            if (leadResult.success) {
                // 2. Listar leads para pegar o ID
                const leadsResult = await apiRequest('api/leads.php?action=list&limit=1');
                workflow.push({ step: '2. Listar Leads', result: leadsResult });

                if (leadsResult.success && leadsResult.leads.length > 0) {
                    const leadId = leadsResult.leads[0].id;

                    // 3. Atualizar status do lead
                    const updateResult = await apiRequest('api/leads.php?action=update', 'POST', {
                        id: leadId,
                        status: 'contacted',
                        notes: 'Cliente contatado via simulação'
                    });
                    workflow.push({ step: '3. Atualizar Lead', result: updateResult });

                    // 4. Adicionar interação
                    const interactionResult = await apiRequest('api/leads.php?action=interaction', 'POST', {
                        lead_id: leadId,
                        type: 'call',
                        description: 'Ligação realizada - cliente demonstrou interesse'
                    });
                    workflow.push({ step: '4. Adicionar Interação', result: interactionResult });

                    // 5. Converter em venda
                    const saleResult = await apiRequest('api/sales.php?action=convert', 'POST', {
                        lead_id: leadId,
                        seller_id: currentUser ? currentUser.id : 1,
                        sale_value: 55000,
                        commission_percentage: 4.0,
                        vehicle_sold: 'Volkswagen T-Cross 2024',
                        contract_number: 'SIM' + Date.now(),
                        status: 'confirmed'
                    });
                    workflow.push({ step: '5. Converter em Venda', result: saleResult });
                }
            }

            showResult('quickActionsResult', { 
                message: 'Simulação completa finalizada!',
                workflow: workflow
            }, 'success');
        }

        // Validar sessão ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            validateSession();
        });
    </script>
</body>
</html>