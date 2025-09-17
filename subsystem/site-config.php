<?php
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
} elseif (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
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

// Verificar se é admin
if ($userRole !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Incluir componente da sidebar
require_once 'components/sidebar.php';
$currentPage = 'site-config';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Site - Hype Consórcios</title>
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

        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 2rem;
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
        }

        .config-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border);
        }

        .config-tab {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            color: var(--muted-foreground);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            font-weight: 500;
        }

        .config-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .config-tab:hover {
            color: var(--foreground);
        }

        .config-section {
            display: none;
        }

        .config-section.active {
            display: block;
        }

        .config-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow-card);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--foreground);
        }

        .form-description {
            font-size: 0.875rem;
            color: var(--muted-foreground);
            margin-bottom: 0.75rem;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.875rem;
            background: white;
            color: var(--foreground);
            transition: border-color 0.2s;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 225, 201, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-display {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .file-input-display:hover {
            border-color: var(--primary);
        }

        .current-image {
            max-width: 200px;
            max-height: 100px;
            margin-top: 0.5rem;
            border-radius: 4px;
            border: 1px solid var(--border);
        }

        .current-media-container video {
            max-width: 300px;
            max-height: 200px;
            border-radius: 4px;
            border: 1px solid var(--border);
        }

        .client-images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .client-image-item {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            background: #fafafa;
        }

        .client-image-item .form-label {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .client-image-item .current-image {
            max-width: 100%;
        }

        /* FAQ Management Styles */
        .faq-management {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .faq-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .faq-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--foreground);
        }

        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .faq-item {
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fafafa;
            overflow: hidden;
        }

        .faq-item-header {
            padding: 1rem;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            border-bottom: 1px solid var(--border);
        }

        .faq-item-header:hover {
            background: #f8f9fa;
        }

        .faq-question {
            font-weight: 600;
            color: var(--foreground);
            flex: 1;
        }

        .faq-actions {
            display: flex;
            gap: 0.5rem;
        }

        .faq-actions button {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-delete {
            background: #dc2626;
            color: white;
        }

        .btn-toggle {
            background: #059669;
            color: white;
        }

        .btn-toggle.inactive {
            background: #6b7280;
        }

        .faq-form {
            padding: 1rem;
            background: white;
            display: none;
        }

        .faq-form.active {
            display: block;
        }

        .faq-form .form-group {
            margin-bottom: 1rem;
        }

        .faq-form .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--foreground);
        }

        .faq-form .form-input,
        .faq-form .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 1rem;
        }

        .faq-form .form-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .drag-handle {
            cursor: move;
            color: #6b7280;
            margin-right: 0.5rem;
        }

        .faq-item.dragging {
            opacity: 0.5;
        }
            max-height: 120px;
            width: 100%;
            object-fit: cover;
        }

        .save-btn {
            background: var(--primary);
            color: var(--primary-foreground);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .save-btn:hover {
            background: rgba(59, 225, 201, 0.9);
            transform: translateY(-1px);
        }

        .save-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .success-message,
        .error-message {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }

        .success-message {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .preview-btn {
            background: var(--muted);
            color: var(--foreground);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            margin-left: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .preview-btn:hover {
            background: var(--primary);
            color: var(--primary-foreground);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .config-tabs {
                flex-wrap: wrap;
            }

            .config-tab {
                flex: 1;
                min-width: 120px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container" id="dashboardContainer">
        <?php renderMobileMenuButton(); ?>

        <?php renderSidebar($currentPage, $userRole, $userName); ?>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Configurações do Site</h1>
                <p class="page-subtitle">Edite o conteúdo e as informações do site principal</p>
                <a href="../index.php" target="_blank" class="preview-btn">
                    <i class="fas fa-external-link-alt"></i>
                    Visualizar Site
                </a>
                <a href="backup_configs.php" target="_blank" class="preview-btn" style="background: #28a745;">
                    <i class="fas fa-download"></i>
                    Backup Configurações
                </a>
                <a href="restore_configs.php" target="_blank" class="preview-btn" style="background: #ffc107; color: #000;">
                    <i class="fas fa-upload"></i>
                    Restaurar Configurações
                </a>
            </div>

            <div class="success-message" id="successMessage">
                <i class="fas fa-check-circle"></i>
                <span id="successText">Configurações salvas com sucesso!</span>
            </div>

            <div class="error-message" id="errorMessage">
                <i class="fas fa-exclamation-circle"></i>
                <span id="errorText">Erro ao salvar configurações</span>
            </div>

            <div class="config-tabs">
                <button class="config-tab active" onclick="showConfigSection('hero')">Hero Section</button>
                <button class="config-tab" onclick="showConfigSection('meta')">Meta Tags</button>
                <button class="config-tab" onclick="showConfigSection('company')">Empresa</button>
                <button class="config-tab" onclick="showConfigSection('about')">Sobre</button>
                <button class="config-tab" onclick="showConfigSection('cars')">Veículos</button>
                <button class="config-tab" onclick="showConfigSection('clients')">Clientes</button>
                <button class="config-tab" onclick="showConfigSection('career')">Carreira</button>
                <button class="config-tab" onclick="showConfigSection('faq')">FAQ</button>
                <button class="config-tab" onclick="showConfigSection('location')">Localização</button>
            </div>

            <div id="configSections">
                <!-- Seções serão carregadas dinamicamente via JavaScript -->
            </div>
        </main>
    </div>

    <script>
        let currentConfigs = {};
        let currentSection = 'hero';

        document.addEventListener('DOMContentLoaded', function() {
            loadConfigurations();
        });

        async function loadConfigurations() {
            try {
                const response = await fetch('api/site-config.php?action=get');
                const data = await response.json();

                if (data.success) {
                    currentConfigs = data.configs;
                    showConfigSection(currentSection);
                } else {
                    showError('Erro ao carregar configurações: ' + data.message);
                }
            } catch (error) {
                showError('Erro de conexão: ' + error.message);
            }
        }

        function showConfigSection(section) {
            currentSection = section;

            // Atualizar tabs
            document.querySelectorAll('.config-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`[onclick="showConfigSection('${section}')"]`).classList.add('active');

            // Gerar formulário para a seção
            const sectionsContainer = document.getElementById('configSections');
            sectionsContainer.innerHTML = generateSectionForm(section);

            // Se for a seção FAQ, carregar as FAQs
            if (section === 'faq') {
                loadFaqs();
            }
        }

        function generateSectionForm(section) {
            const sectionConfigs = Object.values(currentConfigs).filter(config => config.section === section);

            if (sectionConfigs.length === 0) {
                return `
                    <div class="config-form">
                        <p>Nenhuma configuração encontrada para esta seção.</p>
                    </div>
                `;
            }

            let formHtml = `
                <div class="config-form">
                    <form id="configForm-${section}" onsubmit="saveConfigurations(event, '${section}')">
            `;

            // Para a seção de FAQs, criar interface especial de gerenciamento
            if (section === 'faq') {
                formHtml += `
                    <div class="faq-management">
                        <div class="faq-header">
                            <h3><i class="fas fa-question-circle"></i> Gerenciar Perguntas Frequentes</h3>
                            <button type="button" class="btn btn-primary" onclick="addNewFaq()">
                                <i class="fas fa-plus"></i> Nova Pergunta
                            </button>
                        </div>
                        <div id="faqList" class="faq-list">
                            <!-- FAQs serão carregadas aqui -->
                        </div>
                    </div>
                `;
            }
            // Para a seção de clientes, criar layout especial para imagens
            else if (section === 'clients') {
                const textConfigs = sectionConfigs.filter(config => !config.config_key.includes('client_image_'));
                const imageConfigs = sectionConfigs.filter(config => config.config_key.includes('client_image_'));

                // Adicionar campos de texto primeiro
                textConfigs.forEach(config => {
                    formHtml += generateFieldHtml(config);
                });

                // Adicionar seção especial para imagens dos clientes
                if (imageConfigs.length > 0) {
                    formHtml += `
                        <div style="margin: 2rem 0;">
                            <h3 style="margin-bottom: 1rem; color: var(--foreground);">
                                <i class="fas fa-images"></i> Imagens dos Clientes Contemplados
                            </h3>
                            <p style="color: var(--muted-foreground); margin-bottom: 1rem;">
                                Faça upload das fotos dos clientes que realizaram o sonho do carro novo.
                            </p>
                            <div class="client-images-grid">
                    `;

                    imageConfigs.forEach(config => {
                        const clientNumber = config.config_key.replace('client_image_', '');
                        formHtml += `
                            <div class="client-image-item">
                                <div class="form-label">Cliente ${clientNumber}</div>
                                ${generateFieldHtml(config, true)}
                            </div>
                        `;
                    });

                    formHtml += `
                            </div>
                        </div>
                    `;
                }
            } else {
                // Layout normal para outras seções
                sectionConfigs.forEach(config => {
                    formHtml += generateFieldHtml(config);
                });
            }

            formHtml += `
                        <button type="submit" class="save-btn">
                            <i class="fas fa-save"></i>
                            <span>Salvar Alterações</span>
                        </button>
                    </form>
                </div>
            `;

            return formHtml;
        }

        function generateFieldHtml(config, isCompact = false) {
            const fieldId = `field-${config.config_key}`;

            let inputHtml = '';

            switch (config.config_type) {
                case 'textarea':
                    inputHtml = `
                        <textarea
                            id="${fieldId}"
                            name="${config.config_key}"
                            class="form-textarea"
                            rows="4"
                        >${config.config_value || ''}</textarea>
                    `;
                    break;

                case 'image':
                    const isVideo = config.config_key.includes('video') || (config.config_value && (config.config_value.includes('.mp4') || config.config_value.includes('.webm') || config.config_value.includes('.avi')));
                    const fileType = isVideo ? 'vídeo' : 'imagem';
                    const acceptType = isVideo ? 'video/*' : 'image/*';
                    const icon = isVideo ? 'fa-video' : 'fa-image';

                    inputHtml = `
                        <div class="file-input-wrapper">
                            <input
                                type="file"
                                id="${fieldId}"
                                name="${config.config_key}"
                                class="file-input"
                                accept="${acceptType}"
                                onchange="previewMedia(this, '${config.config_key}', ${isVideo})"
                            >
                            <div class="file-input-display">
                                <i class="fas ${icon}"></i>
                                <span>Escolher novo ${fileType}</span>
                            </div>
                        </div>
                        <input type="hidden" name="${config.config_key}_current" value="${config.config_value || ''}">
                        ${config.config_value ? `
                            <div class="current-media-container">
                                <p style="margin: 0.5rem 0 0.25rem 0; font-size: 0.875rem; color: var(--muted-foreground);">${fileType.charAt(0).toUpperCase() + fileType.slice(1)} atual:</p>
                                ${isVideo ? `
                                    <video src="../${config.config_value}" class="current-image" id="preview-${config.config_key}" controls style="max-width: 300px; max-height: 200px;"></video>
                                ` : `
                                    <img src="../${config.config_value}" alt="Imagem atual" class="current-image" id="preview-${config.config_key}">
                                `}
                            </div>
                        ` : ''}
                    `;
                    break;

                default:
                    inputHtml = `
                        <input
                            type="text"
                            id="${fieldId}"
                            name="${config.config_key}"
                            class="form-input"
                            value="${config.config_value || ''}"
                        >
                    `;
            }

            if (isCompact) {
                // Layout compacto para imagens dos clientes
                return `
                    ${!config.description || config.description.includes('cliente') ? '' : `
                        <div class="form-description" style="font-size: 0.75rem; margin-bottom: 0.5rem;">
                            ${config.description}
                        </div>
                    `}
                    ${inputHtml}
                `;
            } else {
                // Layout completo normal
                return `
                    <div class="form-group">
                        <label for="${fieldId}" class="form-label">
                            ${config.display_name}
                        </label>
                        ${config.description ? `
                            <div class="form-description">
                                ${config.description}
                            </div>
                        ` : ''}
                        ${inputHtml}
                    </div>
                `;
            }
        }

        function previewMedia(input, configKey, isVideo = false) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(`preview-${configKey}`);
                    const mediaType = isVideo ? 'vídeo' : 'imagem';

                    if (preview) {
                        preview.src = e.target.result;
                        if (isVideo) {
                            preview.load(); // Recarregar o vídeo
                        }
                    } else {
                        // Criar preview se não existir
                        const container = input.closest('.file-input-wrapper').parentNode;
                        const previewHtml = isVideo ? `
                            <div class="current-media-container">
                                <p style="margin: 0.5rem 0 0.25rem 0; font-size: 0.875rem; color: var(--muted-foreground);">Novo ${mediaType}:</p>
                                <video src="${e.target.result}" class="current-image" id="preview-${configKey}" controls style="max-width: 300px; max-height: 200px;"></video>
                            </div>
                        ` : `
                            <div class="current-media-container">
                                <p style="margin: 0.5rem 0 0.25rem 0; font-size: 0.875rem; color: var(--muted-foreground);">Nova ${mediaType}:</p>
                                <img src="${e.target.result}" alt="Nova imagem" class="current-image" id="preview-${configKey}">
                            </div>
                        `;
                        container.insertAdjacentHTML('beforeend', previewHtml);
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        async function saveConfigurations(event, section) {
            event.preventDefault();

            const form = event.target;
            const button = form.querySelector('.save-btn');
            const buttonText = button.querySelector('span');
            const originalText = buttonText.textContent;

            // Mostrar loading
            button.disabled = true;
            buttonText.textContent = 'Salvando...';

            try {
                const formData = new FormData(form);
                formData.append('action', 'save');
                formData.append('section', section);

                const response = await fetch('api/site-config.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess('Configurações salvas com sucesso!');
                    // Atualizar configurações locais
                    if (data.configs) {
                        currentConfigs = { ...currentConfigs, ...data.configs };
                    }
                } else {
                    let errorMessage = 'Erro ao salvar: ' + data.message;
                    if (data.debug) {
                        console.log('Debug info:', data.debug);
                        errorMessage += '\n\nInfo de debug no console.';
                    }
                    showError(errorMessage);
                }

            } catch (error) {
                showError('Erro de conexão: ' + error.message);
            } finally {
                button.disabled = false;
                buttonText.textContent = originalText;
            }
        }

        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            const textSpan = document.getElementById('successText');
            textSpan.textContent = message;
            successDiv.style.display = 'flex';

            // Esconder após 5 segundos
            setTimeout(() => {
                successDiv.style.display = 'none';
            }, 5000);
        }

        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            const textSpan = document.getElementById('errorText');
            textSpan.textContent = message;
            errorDiv.style.display = 'flex';

            // Esconder após 5 segundos
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }

        // FAQ Management Functions
        let currentFaqs = [];
        let editingFaqId = null;

        async function loadFaqs() {
            try {
                const response = await fetch('api/faq.php?action=list');
                const data = await response.json();

                if (data.success) {
                    currentFaqs = data.data;
                    renderFaqList();
                } else {
                    showError('Erro ao carregar FAQs: ' + data.message);
                }
            } catch (error) {
                showError('Erro de conexão: ' + error.message);
            }
        }

        function renderFaqList() {
            const faqList = document.getElementById('faqList');

            if (currentFaqs.length === 0) {
                faqList.innerHTML = '<p style="text-align: center; color: var(--muted-foreground);">Nenhuma FAQ encontrada. Clique em "Nova Pergunta" para adicionar a primeira.</p>';
                return;
            }

            faqList.innerHTML = currentFaqs.map(faq => `
                <div class="faq-item" data-id="${faq.id}">
                    <div class="faq-item-header" onclick="toggleFaqForm(${faq.id})">
                        <div style="display: flex; align-items: center;">
                            <i class="fas fa-grip-vertical drag-handle"></i>
                            <span class="faq-question">${faq.question}</span>
                            <span style="margin-left: 1rem; font-size: 0.75rem; color: var(--muted-foreground);">Ordem: ${faq.display_order}</span>
                        </div>
                        <div class="faq-actions" onclick="event.stopPropagation()">
                            <button class="btn-toggle ${faq.is_active ? '' : 'inactive'}" onclick="toggleFaqStatus(${faq.id}, ${faq.is_active})">
                                ${faq.is_active ? 'Ativo' : 'Inativo'}
                            </button>
                            <button class="btn-edit" onclick="editFaq(${faq.id})">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn-delete" onclick="deleteFaq(${faq.id})">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        </div>
                    </div>
                    <div class="faq-form" id="faq-form-${faq.id}">
                        <div class="form-group">
                            <label class="form-label">Pergunta:</label>
                            <input type="text" class="form-input" id="question-${faq.id}" value="${faq.question}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Resposta:</label>
                            <textarea class="form-textarea" id="answer-${faq.id}" rows="4">${faq.answer}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ordem de Exibição:</label>
                            <input type="number" class="form-input" id="order-${faq.id}" value="${faq.display_order}" min="0">
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="cancelEdit(${faq.id})">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="saveFaq(${faq.id})">Salvar</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function addNewFaq() {
            const newFaqHtml = `
                <div class="faq-item" data-id="new">
                    <div class="faq-form active">
                        <h4 style="margin-bottom: 1rem; color: var(--foreground);">Nova Pergunta Frequente</h4>
                        <div class="form-group">
                            <label class="form-label">Pergunta:</label>
                            <input type="text" class="form-input" id="question-new" placeholder="Digite a pergunta...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Resposta:</label>
                            <textarea class="form-textarea" id="answer-new" rows="4" placeholder="Digite a resposta..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ordem de Exibição:</label>
                            <input type="number" class="form-input" id="order-new" value="${currentFaqs.length + 1}" min="0">
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="cancelNewFaq()">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="createFaq()">Criar FAQ</button>
                        </div>
                    </div>
                </div>
            `;

            const faqList = document.getElementById('faqList');
            faqList.insertAdjacentHTML('afterbegin', newFaqHtml);
        }

        function toggleFaqForm(faqId) {
            const form = document.getElementById(`faq-form-${faqId}`);
            form.classList.toggle('active');
        }

        function editFaq(faqId) {
            const form = document.getElementById(`faq-form-${faqId}`);
            form.classList.add('active');
            editingFaqId = faqId;
        }

        function cancelEdit(faqId) {
            const form = document.getElementById(`faq-form-${faqId}`);
            form.classList.remove('active');
            editingFaqId = null;

            // Restaurar valores originais
            const faq = currentFaqs.find(f => f.id == faqId);
            if (faq) {
                document.getElementById(`question-${faqId}`).value = faq.question;
                document.getElementById(`answer-${faqId}`).value = faq.answer;
                document.getElementById(`order-${faqId}`).value = faq.display_order;
            }
        }

        function cancelNewFaq() {
            const newFaqItem = document.querySelector('[data-id="new"]');
            if (newFaqItem) {
                newFaqItem.remove();
            }
        }

        async function createFaq() {
            const question = document.getElementById('question-new').value.trim();
            const answer = document.getElementById('answer-new').value.trim();
            const displayOrder = document.getElementById('order-new').value;

            if (!question || !answer) {
                showError('Pergunta e resposta são obrigatórias');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'create');
                formData.append('question', question);
                formData.append('answer', answer);
                formData.append('display_order', displayOrder);
                formData.append('is_active', '1');

                const response = await fetch('api/faq.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess('FAQ criada com sucesso!');
                    loadFaqs(); // Recarregar lista
                } else {
                    showError('Erro ao criar FAQ: ' + data.message);
                }
            } catch (error) {
                showError('Erro de conexão: ' + error.message);
            }
        }

        async function saveFaq(faqId) {
            const question = document.getElementById(`question-${faqId}`).value.trim();
            const answer = document.getElementById(`answer-${faqId}`).value.trim();
            const displayOrder = document.getElementById(`order-${faqId}`).value;

            if (!question || !answer) {
                showError('Pergunta e resposta são obrigatórias');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'update');
                formData.append('id', faqId);
                formData.append('question', question);
                formData.append('answer', answer);
                formData.append('display_order', displayOrder);
                formData.append('is_active', '1');

                const response = await fetch('api/faq.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess('FAQ atualizada com sucesso!');
                    loadFaqs(); // Recarregar lista
                } else {
                    showError('Erro ao atualizar FAQ: ' + data.message);
                }
            } catch (error) {
                showError('Erro de conexão: ' + error.message);
            }
        }

        async function deleteFaq(faqId) {
            if (!confirm('Tem certeza que deseja excluir esta FAQ? Esta ação não pode ser desfeita.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', faqId);

                const response = await fetch('api/faq.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess('FAQ excluída com sucesso!');
                    loadFaqs(); // Recarregar lista
                } else {
                    showError('Erro ao excluir FAQ: ' + data.message);
                }
            } catch (error) {
                showError('Erro de conexão: ' + error.message);
            }
        }

        async function toggleFaqStatus(faqId, currentStatus) {
            const newStatus = currentStatus ? 0 : 1;

            try {
                const faq = currentFaqs.find(f => f.id == faqId);
                const formData = new FormData();
                formData.append('action', 'update');
                formData.append('id', faqId);
                formData.append('question', faq.question);
                formData.append('answer', faq.answer);
                formData.append('display_order', faq.display_order);
                formData.append('is_active', newStatus);

                const response = await fetch('api/faq.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(`FAQ ${newStatus ? 'ativada' : 'desativada'} com sucesso!`);
                    loadFaqs(); // Recarregar lista
                } else {
                    showError('Erro ao alterar status: ' + data.message);
                }
            } catch (error) {
                showError('Erro de conexão: ' + error.message);
            }
        }
    </script>

    <!-- Sidebar Scripts -->
    <?= getSidebarScripts() ?>
</body>
</html>