<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$error = '';
$success = '';
$activeTab = 'login'; // Aba ativa por padrão

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_login'])) {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if ($email === '' || $password === '') {
            $error = 'Por favor, preencha todos os campos.';
        } else {
            $user = verifyUserByEmail($db, $email, $password);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_email'] = $user['email'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'E-mail ou senha incorretos.';
            }
        }
    } elseif (isset($_POST['action_register'])) {
        $activeTab = 'register';
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            $error = 'Por favor, preencha todos os campos.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail inválido.';
        } else {
            // Verificar se o e-mail já existe
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Este e-mail já está cadastrado.';
            } else {
                // Verificar se o username já existe
                $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = 'Este nome de usuário já está sendo utilizado.';
                } else {
                    if (createUser($db, $username, $email, $password)) {
                        $user = getUserByEmail($db, $email);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_email'] = $user['email'];
                        header('Location: index.php');
                        exit;
                    } else {
                        $error = 'Erro ao registrar usuário. Tente novamente.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso do Cliente - Help Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 512 512%22><path fill=%22%232b82b3%22 d=%22M495.9 166.6c3.2 8.7.5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6-4.4 11.9-9.7 23.3-15.8 34.3-4.7 8.3-10.3 16.1-16.8 23.2-5.6 6.1-12.3 11.2-19.7 15.2l-56.9-19c-16.6 13.1-35.6 23.6-56.3 31.1l-11.8 59.2c-2.3 11.5-10.9 20.6-22.3 23-13.6 2.8-27.5 4.3-41.6 4.3s-28-1.5-41.6-4.3c-11.4-2.4-20-11.5-22.3-23l-11.8-59.2c-20.7-7.5-39.7-18.1-56.3-31.1l-56.9 19c-7.4-4-14.1-9.1-19.7-15.2-6.5-7.1-12.1-14.9-16.8-23.2-6.1-11-11.4-22.4-15.8-34.3-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6 4.4-11.9 9.7-23.3 15.8-34.3 4.7-8.3 10.3-16.1 16.8-23.2 5.6-6.1 12.3-11.2 19.7-15.2l56.9 19c16.6-13.1 35.6-23.6 56.3-31.1l11.8-59.2c2.3-11.5 10.9-20.6 22.3-23C228.4 1.5 242.3 0 256 0s28 1.5 41.6 4.3c11.4 2.4 20 11.5 22.3 23l11.8 59.2c20.7 7.5 39.7 18.1 56.3 31.1l56.9-19c7.4 4 14.1 9.1 19.7 15.2 6.5 7.1 12.1 14.9 16.8 23.2 6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z%22/></svg>">

    <style>
        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(26, 58, 82, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            transition: all 0.3s ease;
           
        }
        .login-header {
            background:#2b82b3;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .login-header h2 {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 1.8rem;
            color: #ffffff;
            border: none;
            padding: 0;
        }
        .login-header p {
            margin: 0;
            opacity: 0.85;
            font-size: 0.95rem;
        }
        .tabs-header {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .tab-btn {
            background: none;
            border: none;
            padding: 14px;
            font-weight: 600;
            color: #64748b;
            flex: 1;
            transition: all 0.2s;
            border-bottom: 3px solid transparent;
            font-size: 0.95rem;
        }
        .tab-btn:hover {
            color: #1a3a52;
            background: rgba(0,0,0,0.02);
        }
        .tab-btn.active {
            color: #1a3a52;
            border-bottom-color: #4ecdc4;
            background: white;
        }
        .card-body-custom {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1a3a52;
            font-size: 0.9rem;
        }
        .input-group-custom {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-group-custom i {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 1rem;
        }
        .input-group-custom input {
            width: 100%;
            padding: 12px 12px 12px 42px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: #ffffff;
        }
        .input-group-custom input:focus {
            outline: none;
            border-color: #4ecdc4;
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.15);
        }
        .btn-submit {
            background: #2b82b3;
            color: white;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(26, 58, 82, 0.1);
            margin-top: 10px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(26, 58, 82, 0.2);
            color: white;
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        .alert {
            font-size: 0.9rem;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main class="main-content">
    <div class="login-container">
        
        <?php if ($error !== ''): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="login-card">
            <div class="login-header">
                <h2><i class="fas fa-headset me-2"></i> Suporte Técnico</h2>
                <p>Abra chamados e acompanhe suas solicitações</p>
            </div>
            
            

            <div class="card-body-custom">
                <!-- Formulário de Login -->
                <form id="loginForm" method="post" action="login_fun.php" style="display: <?= $activeTab === 'login' ? 'block' : 'none' ?>;">
                    <input type="hidden" name="action_login" value="1">
                    
                    <div class="form-group">
                        <label for="login_email">E-mail de Acesso</label>
                        <div class="input-group-custom">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="login_email" name="email" placeholder="seuemail@exemplo.com" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="login_password">Senha</label>
                        <div class="input-group-custom">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="login_password" name="password" placeholder="Sua senha" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-sign-in-alt me-2"></i> Acessar Painel
                    </button>
                </form>

                <!-- Formulário de Cadastro -->
    
        
        <div class="text-center mt-3">
            <a href="inicio.php" class="text-decoration-none text-muted">
                <i class="fas fa-arrow-left me-1"></i> Voltar ao início
            </a>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function switchTab(tab) {
        // Obter elementos
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const tabs = document.querySelectorAll('.tab-btn');
        
        if (tab === 'login') {
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
            tabs[0].classList.add('active');
            tabs[1].classList.remove('active');
        } else {
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
            tabs[0].classList.remove('active');
            tabs[1].classList.add('active');
        }
    }
</script>
</body>
</html>