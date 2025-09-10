<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema - Hype Consórcios</title>
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
            background: linear-gradient(135deg, var(--primary) 0%, #242328 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            font-family: var(--font-family);
        }

        .crm-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .crm-header {
            margin-bottom: 2rem;
        }

        .crm-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
            background: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 20px;
        }

        .crm-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .crm-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--foreground);
            margin-bottom: 0.5rem;
        }

        .crm-subtitle {
            color: var(--muted-foreground);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .feature-list {
            text-align: left;
            margin: 2rem 0;
            padding-left: 1rem;
        }

        .feature-list li {
            margin: 0.75rem 0;
            color: var(--muted-foreground);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .feature-icon {
            color: var(--primary);
            font-size: 1.1rem;
            width: 20px;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--primary-foreground);
            border: 2px solid transparent;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px rgba(59, 225, 201, 0.3);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--primary-foreground);
            transform: translateY(-2px);
        }

        .back-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .back-link a {
            color: var(--muted-foreground);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .back-link a:hover {
            color: var(--primary);
        }

        .test-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }

        .test-link {
            font-size: 0.85rem;
            color: var(--muted-foreground);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background: var(--muted);
            transition: all 0.2s ease;
        }

        .test-link:hover {
            background: var(--primary);
            color: var(--primary-foreground);
        }

        @media (max-width: 480px) {
            .crm-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .crm-title {
                font-size: 1.5rem;
            }
            
            .test-links {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="crm-container">
        <div class="crm-header">
            <div class="crm-logo">
                <img src="../assets/images/logo.png" alt="Hype Consórcios Logo">
            </div>
            <h1 class="crm-title">Hype Consórcios</h1>
            <p class="crm-subtitle">Sistema de Gestão de Leads e Vendas</p>
        </div>

        <ul class="feature-list">
            <li>
                <i class="fas fa-users feature-icon"></i>
                Gerenciamento completo de leads
            </li>
            <li>
                <i class="fas fa-handshake feature-icon"></i>
                Controle de vendas e comissões
            </li>
            <li>
                <i class="fas fa-chart-bar feature-icon"></i>
                Relatórios e estatísticas
            </li>
            <li>
                <i class="fas fa-users-cog feature-icon"></i>
                Gestão de usuários e permissões
            </li>
            <li>
                <i class="fab fa-whatsapp feature-icon"></i>
                Integração com WhatsApp
            </li>
        </ul>

        <div class="btn-group">
            <a href="login.php" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i>
                Acessar Sistema
            </a>
            
            <a href="test_backend.php" class="btn btn-outline">
                <i class="fas fa-flask"></i>
                Testar Sistema
            </a>
        </div>

        <div class="test-links">
            <a href="test_auth.php" class="test-link">
                <i class="fas fa-shield-alt"></i>
                Teste Auth
            </a>
            <a href="installer.php" class="test-link">
                <i class="fas fa-download"></i>
                Instalador
            </a>
            <a href="create_sample_data.php" class="test-link">
                <i class="fas fa-database"></i>
                Dados Exemplo
            </a>
        </div>

        <div class="back-link">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i>
                Voltar ao site principal
            </a>
        </div>
    </div>
</body>
</html>