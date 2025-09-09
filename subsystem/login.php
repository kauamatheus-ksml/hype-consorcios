<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hype Consórcios CRM</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 16px;
            padding: 3rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-header {
            margin-bottom: 2rem;
        }

        .login-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, #3be1c9 0%, #3be1c9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--foreground);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: var(--muted-foreground);
            font-size: 0.95rem;
        }

        .login-form {
            text-align: left;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--foreground);
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 225, 201, 0.1);
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted-foreground);
            pointer-events: none;
        }

        .input-group .form-input {
            padding-left: 3rem;
        }

        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: var(--gradient-primary);
            border: none;
            border-radius: 8px;
            color: var(--primary-foreground);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 225, 201, 0.3);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .loading-spinner {
            display: none;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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
        }

        .back-link a:hover {
            color: var(--primary);
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1 class="login-title">Acesso ao Sistema</h1>
            <p class="login-subtitle">Faça login para acessar o CRM</p>
        </div>

        <div id="alertContainer"></div>

        <form class="login-form" id="loginForm">
            <div class="form-group">
                <label for="username">Usuário ou E-mail</label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" class="form-input" 
                           placeholder="Digite seu usuário ou e-mail" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Digite sua senha" required>
                </div>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                <span id="btnText">Entrar</span>
                <i class="fas fa-spinner loading-spinner" id="loadingSpinner"></i>
            </button>
        </form>

        <div class="back-link">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i>
                Voltar ao site
            </a>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = e.target;
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const alertContainer = document.getElementById('alertContainer');
            
            // Show loading state
            loginBtn.disabled = true;
            btnText.textContent = 'Entrando...';
            loadingSpinner.style.display = 'inline-block';
            
            // Clear previous alerts
            alertContainer.innerHTML = '';
            
            try {
                const formData = new FormData(form);
                const data = {
                    username: formData.get('username'),
                    password: formData.get('password')
                };
                
                const response = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message
                    showAlert('success', '✅ Login realizado com sucesso! Redirecionando...');
                    
                    // Redirect after 1 second
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                    
                } else {
                    showAlert('error', '❌ ' + result.message);
                }
                
            } catch (error) {
                console.error('Erro no login:', error);
                showAlert('error', '❌ Erro de conexão. Tente novamente.');
            } finally {
                // Reset button state
                loginBtn.disabled = false;
                btnText.textContent = 'Entrar';
                loadingSpinner.style.display = 'none';
            }
        });
        
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            
            alertContainer.innerHTML = `
                <div class="alert ${alertClass}">
                    ${message}
                </div>
            `;
        }
        
        // Focus first input on load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Handle enter key on inputs
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('loginForm').dispatchEvent(new Event('submit'));
                }
            });
        });
    </script>
</body>
</html>