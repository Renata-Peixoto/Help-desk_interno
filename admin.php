<?php
require_once __DIR__ . '/config.php';

$db = getDb();
$error = '';
$success = '';

if (!empty($importResult['error']) && $error === '') {
    $error = $importResult['error'];
}
$activeTab = trim($_GET['tab'] ?? 'overview'); // 'overview', 'tickets', or 'users'

// Garantir que as abas válidas sejam respeitadas
if (!in_array($activeTab, ['overview', 'tickets', 'users'])) {
    $activeTab = 'overview';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Processamento de Login Administrativo
    if (isset($_POST['admin_login'])) {
        $email = trim($_POST['admin_email'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        if ($email === '' || $password === '') {
            $error = 'Por favor, preencha todos os campos.';
        } else {
            $admin = verifyAdminByEmail($db, $email, $password);
            if ($admin) {
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $success = 'Autenticado com sucesso no Painel Admin.';
            } else {
                $error = 'E-mail ou senha administrativa incorretos.';
            }
        }
    }
    
    // Verificações que exigem ser admin
    if (!empty($_SESSION['is_admin'])) {
        // 2. Atualizar Status, Responsável e resposta do ticket
        if (isset($_POST['update_ticket'])) {
            $ticketId = (int)$_POST['ticket_id'];
            $status = trim($_POST['status'] ?? '');
            $responsible = trim($_POST['responsible'] ?? '');
            $replyMsg = trim($_POST['reply_message'] ?? '');
            
            $allowedStatus = ['Pendente', 'Em andamento', 'Resolvido'];
            $allowedResponsible = ['Não sabe', 'Renata', 'Celeste'];

            if (!in_array($status, $allowedStatus, true)) {
                $error = 'Status selecionado é inválido.';
            } elseif (!in_array($responsible, $allowedResponsible, true)) {
                $error = 'Responsável selecionado é inválido.';
            } else {
                $stmt = $db->prepare('UPDATE tickets SET status = ?, responsible = ? WHERE id = ?');
                $stmt->execute([$status, $responsible, $ticketId]);

                $saved = true;
                $sentMessage = false;
                if ($replyMsg !== '') {
                    $adminEmail = $_SESSION['admin_email'] ?? MAIL_ADMIN_ADDRESS;
<<<<<<< HEAD
                    $saved = saveTicketResponse($db, $ticketId, 'admin', $replyMsg, $adminEmail);
=======
                    $stmtIns = $db->prepare('INSERT INTO ticket_responses (ticket_id, sender, message, admin_email) VALUES (?, ?, ?, ?)');
                    $saved = $stmtIns->execute([$ticketId, 'admin', $replyMsg, $adminEmail]);
>>>>>>> 243678c3e4b8b408795331c9a885c0e0c146c3a2
                    if ($saved) {
                        $ticket = getTicketById($db, $ticketId);
                        if ($ticket) {
                            $body = '<p>Olá ' . htmlspecialchars($ticket['name'], ENT_QUOTES, 'UTF-8') . ',</p>' .
                                '<p>' . nl2br(htmlspecialchars($replyMsg, ENT_QUOTES, 'UTF-8')) . '</p>';
                            $sentMessage = sendEmailSMTP($ticket['email'], 'Resposta ao seu ticket #' . $ticketId . ': ' . $ticket['subject'], $body);
                            sendEmailSMTP(MAIL_ADMIN_ADDRESS, 'Cópia: resposta no ticket #' . $ticketId, $body);
                        }
                    }
                }

                $statusSent = sendTicketStatusChangeNotification($db, $ticketId, $status);
                if ($saved && $statusSent) {
                    $success = "Ticket #$ticketId atualizado para '$status' (Responsável: $responsible).";
                    if ($replyMsg !== '') {
                        $success .= $sentMessage ? ' Resposta enviada ao usuário.' : ' Resposta salva, mas falha ao notificar o usuário.';
                    }
                } else {
                    $success = "Ticket #$ticketId atualizado para '$status' (Responsável: $responsible).";
                    $error = 'Atualização salva, mas ocorreu um problema ao enviar notificações.';
                    if ($replyMsg !== '' && !$saved) {
                        $error = 'Falha ao salvar a resposta no banco.';
                    }
                }
<<<<<<< HEAD

                header('Location: admin.php?tab=tickets');
                exit;
=======
>>>>>>> 243678c3e4b8b408795331c9a885c0e0c146c3a2
            }
        }
        
        // 3. Criar Novo Administrador
        elseif (isset($_POST['create_admin'])) {
            $name = trim($_POST['admin_name'] ?? '');
            $email = trim($_POST['admin_email'] ?? '');
            $password = trim($_POST['admin_password'] ?? '');

            if ($name === '' || $email === '' || $password === '') {
                $error = 'Por favor, preencha todos os campos do novo administrador.';
            } else {
                $stmt = $db->prepare('SELECT id FROM admins WHERE email = ?');
                $stmt->execute([$email]);
                $existing = $stmt->fetch();
                if ($existing) {
                    $error = 'Já existe um administrador com este e-mail.';
                } elseif (createAdmin($db, $name, $email, $password)) {
                    $success = "Administrador '$name' cadastrado com sucesso.";
                } else {
                    $error = 'Falha ao cadastrar administrador.';
                }
            }
        }

        // 4. Editar Administrador
        elseif (isset($_POST['update_admin'])) {
            $adminId = (int)($_POST['admin_id'] ?? 0);
            $name = trim($_POST['edit_admin_name'] ?? '');
            $email = trim($_POST['edit_admin_email'] ?? '');
            $newPassword = trim($_POST['edit_admin_password'] ?? '');

            if ($adminId <= 0 || $name === '' || $email === '') {
                $error = 'Dados inválidos para atualização do administrador.';
            } else {
                $ok = updateAdmin($db, $adminId, $name, $email, $newPassword !== '' ? $newPassword : null);
                if ($ok) {
                    $success = "Administrador '$name' atualizado com sucesso.";
                } else {
                    $error = 'Erro ao salvar os dados do administrador.';
                }
            }
        }

        // 5. Excluir Administrador
        elseif (isset($_POST['delete_admin'])) {
            $adminId = (int)($_POST['admin_id'] ?? 0);
            if ($adminId <= 0) {
                $error = 'ID de administrador inválido.';
            } elseif ((int)($_SESSION['admin_id'] ?? 0) === $adminId) {
                $error = 'Você não pode excluir sua própria conta de administrador.';
            } else {
                $stmt = $db->prepare('DELETE FROM admins WHERE id = ?');
                if ($stmt->execute([$adminId])) {
                    $success = 'Administrador removido com sucesso.';
                } else {
                    $error = 'Erro ao remover o administrador.';
                }
            }
        }

        // 6. Criar Novo Usuário
        elseif (isset($_POST['create_user'])) {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if ($username === '' || $email === '' || $password === '') {
                $error = 'Por favor, preencha todos os campos do novo usuário.';
            } else {
                $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
                $stmt->execute([$username, $email]);
                $existing = $stmt->fetch();
                if ($existing) {
                    $error = 'Nome de usuário ou e-mail já estão cadastrados. Escolha outros valores.';
                } else {
                    if (createUser($db, $username, $email, $password)) {
                        $success = "Usuário '$username' cadastrado com sucesso.";
                    } else {
                        $error = 'Falha ao registrar usuário.';
                    }
                }
            }
        }

        // 4. Editar Dados do Usuário
        // 4. Editar Dados do Usuário
        elseif (isset($_POST['update_user'])) {
            $userId = (int)($_POST['user_id'] ?? 0);
            $username = trim($_POST['edit_username'] ?? '');
            $email = trim($_POST['edit_email'] ?? '');
            $newPassword = trim($_POST['edit_password'] ?? '');

            if ($userId <= 0 || $username === '' || $email === '') {
                $error = 'Dados inválidos informados para a atualização.';
            } else {
                if ($newPassword !== '') {
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $db->prepare('UPDATE users SET username = ?, email = ?, password_hash = ? WHERE id = ?');
                    $ok = $stmt->execute([$username, $email, $hash, $userId]);
                } else {
                    $stmt = $db->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
                    $ok = $stmt->execute([$username, $email, $userId]);
                }

                if ($ok) {
                    $success = "Usuário '$username' atualizado com sucesso.";
                } else {
                    $error = 'Erro ao salvar os novos dados do usuário.';
                }
            }
        }
// 5. Excluir Ticket (com suas respostas)
        elseif (isset($_POST['delete_ticket'])) {
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            if ($ticketId <= 0) {
                $error = 'ID de ticket inválido para exclusão.';
            } else {
                // Primeiro exclui as respostas associadas ao ticket
                $stmt = $db->prepare('DELETE FROM ticket_responses WHERE ticket_id = ?');
                $stmt->execute([$ticketId]);

                // Depois exclui o ticket
                $stmt = $db->prepare('DELETE FROM tickets WHERE id = ?');
                if ($stmt->execute([$ticketId])) {
                    $success = "Ticket #$ticketId excluído permanentemente do banco de dados.";
                } else {
                    $error = 'Erro ao excluir o ticket.';
                }
            }
        }

        // 6. Excluir Usuário
        elseif (isset($_POST['delete_user'])) {
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId <= 0) {
                $error = 'ID de usuário inválido para a exclusão.';
            } else {
                // Remove associação sem deletar chamados
                $stmt = $db->prepare('UPDATE tickets SET user_id = NULL WHERE user_id = ?');
                $stmt->execute([$userId]);

                $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
                if ($stmt->execute([$userId])) {
                    $success = 'Usuário excluído com sucesso do banco de dados.';
                } else {
                    $error = 'Erro ao realizar a exclusão do usuário.';
                }
            }
        }
    }
}

$isAdmin = !empty($_SESSION['is_admin']);

// Carregar Dados da Dashboard
if ($isAdmin) {
    // 1. Métricas
    $totalTickets = (int)$db->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
    $pendingTickets = (int)$db->query("SELECT COUNT(*) FROM tickets WHERE status = 'Pendente'")->fetchColumn();
    $inProgressTickets = (int)$db->query("SELECT COUNT(*) FROM tickets WHERE status = 'Em andamento'")->fetchColumn();
    $resolvedTickets = (int)$db->query("SELECT COUNT(*) FROM tickets WHERE status = 'Resolvido'")->fetchColumn();
    $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // 2. Filtros e Lista de Tickets (com ordenação e busca)
    $statusFilter = trim($_GET['status_filter'] ?? '');
    $responsibleFilter = trim($_GET['responsible_filter'] ?? '');
    $search = trim($_GET['search'] ?? '');

    $sql = "SELECT * FROM tickets WHERE 1=1";
    $params = [];

    if ($statusFilter !== '') {
        $sql .= " AND status = ?";
        $params[] = $statusFilter;
    }
    if ($responsibleFilter !== '') {
        $sql .= " AND responsible = ?";
        $params[] = $responsibleFilter;
    }
    if ($search !== '') {
        $sql .= " AND (subject LIKE ? OR name LIKE ? OR email LIKE ? OR message LIKE ?)";
        $searchWildcard = "%$search%";
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
    }
    $sql .= " ORDER BY created_at DESC";

// --- MÁGICA DA PAGINAÇÃO (TICKETS) ---
    $limit = 10; // Mudamos para 10 tickets por página!
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; 
    $offset = ($page - 1) * $limit; 

    // 1. Primeiro, contamos quantos tickets existem no total com esses filtros
    $countSql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
    $stmtCount = $db->prepare($countSql);
    $stmtCount->execute($params);
    $totalTicketsFiltered = (int)$stmtCount->fetchColumn();
    
    // Calcula o total de páginas
    $totalPages = ceil($totalTicketsFiltered / $limit);

    // 2. Agora sim, puxamos apenas os tickets da página atual!
    $sql .= " LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();

    // 3. Lista de Usuários (AGORA COM PAGINAÇÃO!)
    $limitUsers = 10; // 10 usuários por página
    $pageUsers = isset($_GET['page_users']) ? max(1, (int)$_GET['page_users']) : 1;
    $offsetUsers = ($pageUsers - 1) * $limitUsers;

    $totalUsersCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalPagesUsers = ceil($totalUsersCount / $limitUsers);

    $users = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT $limitUsers OFFSET $offsetUsers")->fetchAll();
    
    $admins = getAllAdmins($db);
    
    // 4. Últimos Cadastrados (para a Visão Geral)
    $latestTickets = $db->query("SELECT * FROM tickets ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $latestUsers = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Help Desk</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 512 512%22><path fill=%22%232b82b3%22 d=%22M495.9 166.6c3.2 8.7.5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6-4.4 11.9-9.7 23.3-15.8 34.3-4.7 8.3-10.3 16.1-16.8 23.2-5.6 6.1-12.3 11.2-19.7 15.2l-56.9-19c-16.6 13.1-35.6 23.6-56.3 31.1l-11.8 59.2c-2.3 11.5-10.9 20.6-22.3 23-13.6 2.8-27.5 4.3-41.6 4.3s-28-1.5-41.6-4.3c-11.4-2.4-20-11.5-22.3-23l-11.8-59.2c-20.7-7.5-39.7-18.1-56.3-31.1l-56.9 19c-7.4-4-14.1-9.1-19.7-15.2-6.5-7.1-12.1-14.9-16.8-23.2-6.1-11-11.4-22.4-15.8-34.3-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6 4.4-11.9 9.7-23.3 15.8-34.3 4.7-8.3 10.3-16.1 16.8-23.2 5.6-6.1 12.3-11.2 19.7-15.2l56.9 19c16.6-13.1 35.6-23.6 56.3-31.1l11.8-59.2c2.3-11.5 10.9-20.6 22.3-23C228.4 1.5 242.3 0 256 0s28 1.5 41.6 4.3c11.4 2.4 20 11.5 22.3 23l11.8 59.2c20.7 7.5 39.7 18.1 56.3 31.1l56.9-19c7.4 4 14.1 9.1 19.7 15.2 6.5 7.1 12.1 14.9 16.8 23.2 6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z%22/></svg>">

    <style>
        body {
            background-color: #f1f5f9;
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
        /* Login page gradient for not-logged-in state */
        .login-gradient {
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
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
            background: #2b82b3;
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

        /* Admin Dashboard styles (keep existing) */
        .admin-layout {
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 260px;
            background: #1e293b;
            color: #94a3b8;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #2b82b3;
        }
        .admin-sidebar .sidebar-brand {
            padding: 24px;
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            border-bottom: 1px solid #1e293b;
        }
        .admin-sidebar .sidebar-brand span {
            color: #4ecdc4;
        }
        .admin-sidebar .nav-menu {
            list-style: none;
            padding: 20px 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .admin-sidebar .nav-item-dashboard {
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #94a3b8;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        .admin-sidebar .nav-item-dashboard:hover, 
        .admin-sidebar .nav-item-dashboard.active {
            color: white;
            background:#2b82b3;
            border-left: 4px solid #4ecdc4;
        }
        .admin-content {
            flex-grow: 1;
            padding: 40px;
        }
        .kpi-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s;
        }
        .kpi-card:hover {
            transform: translateY(-3px);
        }
        .kpi-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .bg-tickets { background: rgba(78, 205, 196, 0.15); color: #20b2aa; }
        .bg-pending { background: rgba(245, 158, 11, 0.15); color: #d97706; }
        .bg-progress { background: rgba(14, 165, 233, 0.15); color: #0284c7; }
        .bg-resolved { background: rgba(34, 197, 94, 0.15); color: #16a34a; }
        .bg-users { background: rgba(139, 92, 246, 0.15); color: #7c3aed; }
        
        .tab-content-section {
            display: none;
        }
        .tab-content-section.active {
            display: block;
        }
    </style>
</head>
<body class="<?= !$isAdmin ? 'login-gradient' : '' ?>">

<?php include 'header.php'; ?>

<?php if (!$isAdmin): ?>
    <!-- Formulário de Login Administrativo (mesmo estilo do login_fun.php) -->
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
                    <h2><i class="fas fa-lock me-2"></i> Administrativo</h2>
                    <p>Identifique-se para acessar o painel de controle</p>
                </div>

                <div class="card-body-custom">
                    <form method="post" action="admin.php">
                        <input type="hidden" name="admin_login" value="1">
                        
                        <div class="form-group">
                            <label for="admin_email">E-mail Administrativo</label>
                            <div class="input-group-custom">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="admin_email" name="admin_email" placeholder="admin@arruda.com" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_password">Senha</label>
                            <div class="input-group-custom">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="admin_password" name="admin_password" placeholder="Digite sua senha" required>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-sign-in-alt me-2"></i> Entrar no Painel
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="inicio.php" class="text-decoration-none text-muted">
                            <i class="fas fa-arrow-left me-1"></i> Voltar ao início
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php else: ?>
    <!-- Painel de Controle Admin Unificado -->
    <div class="d-flex flex-column flex-md-row admin-layout">
        <!-- Main Admin Content -->
        <main class="admin-content">
            <?php if ($error !== ''): ?>
                <div class="alert alert-error mb-4 shadow-sm">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success mb-4 shadow-sm">
                    <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <!-- ABA 1: VISÃO GERAL (OVERVIEW) -->
            <section id="tab-overview" class="tab-content-section <?= $activeTab === 'overview' ? 'active' : '' ?>">
                <h2 class="font-bold text-slate-800 mb-4">Visão Geral</h2>
                
                <!-- KPIs Row -->
                <div class="row g-4 mb-5">
                    <div class="col-xl col-md-4">
                        <div class="kpi-card h-100">
                            <div class="kpi-icon bg-tickets"><i class="fas fa-ticket-alt"></i></div>
                            <div>
                                <h3 class="m-0 font-bold text-slate-800"><?= $totalTickets ?></h3>
                                <small class="text-muted">Total Tickets</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl col-md-4">
                        <div class="kpi-card h-100">
                            <div class="kpi-icon bg-pending"><i class="fas fa-exclamation-triangle"></i></div>
                            <div>
                                <h3 class="m-0 font-bold text-slate-800"><?= $pendingTickets ?></h3>
                                <small class="text-muted">Pendentes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl col-md-4">
                        <div class="kpi-card h-100">
                            <div class="kpi-icon bg-progress"><i class="fas fa-spinner"></i></div>
                            <div>
                                <h3 class="m-0 font-bold text-slate-800"><?= $inProgressTickets ?></h3>
                                <small class="text-muted">Em Andamento</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl col-md-4">
                        <div class="kpi-card h-100">
                            <div class="kpi-icon bg-resolved"><i class="fas fa-check-circle"></i></div>
                            <div>
                                <h3 class="m-0 font-bold text-slate-800"><?= $resolvedTickets ?></h3>
                                <small class="text-muted">Resolvidos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl col-md-4">
                        <div class="kpi-card h-100">
                            <div class="kpi-icon bg-users"><i class="fas fa-users"></i></div>
                            <div>
                                <h3 class="m-0 font-bold text-slate-800"><?= $totalUsers ?></h3>
                                <small class="text-muted">Usuários</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Últimos Chamados -->
                    <div class="col-lg-6 mb-4">
                        <div class="card border-0 shadow-sm p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h4 class="font-bold text-slate-800 m-0"><i class="fas fa-history text-primary me-2"></i>Últimos Chamados</h4>
                                <a href="admin.php?tab=tickets" class="btn btn-sm btn-link text-decoration-none">Ver todos</a>
                            </div>
                            <?php if (count($latestTickets) === 0): ?>
                                <p class="text-muted text-center py-4">Nenhum chamado aberto recentemente.</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($latestTickets as $tk): ?>
                                        <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="text-slate-800 d-block"><?= htmlspecialchars($tk['subject']) ?></strong>
                                                <small class="text-muted">Por: <?= htmlspecialchars($tk['name']) ?> (<?= htmlspecialchars($tk['email']) ?>)</small>
                                            </div>
                                            <span class="badge bg-<?= $tk['status'] === 'Pendente' ? 'warning' : ($tk['status'] === 'Em andamento' ? 'info' : 'success') ?>-light text-<?= $tk['status'] === 'Pendente' ? 'warning' : ($tk['status'] === 'Em andamento' ? 'info' : 'success') ?> rounded-pill px-3 py-1">
                                                <?= htmlspecialchars($tk['status']) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Últimos Usuários Cadastrados -->
                    <div class="col-lg-6 mb-4">
                        <div class="card border-0 shadow-sm p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h4 class="font-bold text-slate-800 m-0"><i class="fas fa-user-plus text-primary me-2"></i>Últimos Usuários</h4>
                                <a href="admin.php?tab=users" class="btn btn-sm btn-link text-decoration-none">Ver todos</a>
                            </div>
                            <?php if (count($latestUsers) === 0): ?>
                                <p class="text-muted text-center py-4">Nenhum usuário cadastrado recentemente.</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($latestUsers as $us): ?>
                                        <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="text-slate-800 d-block"><?= htmlspecialchars($us['username']) ?></strong>
                                                <small class="text-muted"><?= htmlspecialchars($us['email']) ?></small>
                                            </div>
                                            <span class="text-xs text-muted"><?= htmlspecialchars(date('d/m/Y', strtotime($us['created_at']))) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ABA 2: GERENCIAR TICKETS -->
            <section id="tab-tickets" class="tab-content-section <?= $activeTab === 'tickets' ? 'active' : '' ?>">
                <h2 class="font-bold text-slate-800 mb-4">Gerenciar Chamados (Tickets)</h2>
                
                <!-- Card de Filtros -->
           <div class="card border-0 shadow-sm p-4 mb-4">
    <form method="get" action="admin.php" class="row g-3 align-items-end">
        <input type="hidden" name="tab" value="tickets">
        
        <div class="col-lg-4">
            <label class="form-label font-semibold">Pesquisa Livre</label>
            <div class="input-group" style="margin-top: 5px;">
                <span class="input-group-text bg-white border-end-0" style="padding: 12px;">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Buscar assunto, cliente ou conteúdo..." value="<?= htmlspecialchars($search ?? '') ?>" style="margin-top: 0; box-shadow: none;">
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <label class="form-label font-semibold">Status</label>
            <select name="status_filter" class="form-select">
                <option value="">Todos os status</option>
                <?php foreach (['Pendente', 'Em andamento', 'Resolvido'] as $stOpt): ?>
                    <option value="<?= $stOpt ?>" <?= ($statusFilter ?? '') === $stOpt ? 'selected' : '' ?>><?= $stOpt ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-lg-3 col-sm-6">
            <label class="form-label font-semibold">Responsável</label>
            <select name="responsible_filter" class="form-select">
                <option value="">Todos os responsáveis</option>
                <?php foreach (['Não sabe', 'Renata', 'Celeste'] as $respOpt): ?>
                    <option value="<?= $respOpt ?>" <?= ($responsibleFilter ?? '') === $respOpt ? 'selected' : '' ?>><?= $respOpt ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1" style="padding: 12px; margin-top: 5px;">
                <i class="fas fa-filter me-1"></i> Filtrar
            </button>
            <a href="admin.php?tab=tickets" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" title="Limpar Filtros" style="padding: 12px 20px; margin-top: 5px;">
                <i class="fas fa-sync-alt"></i>
            </a>
        </div>
    </form>
</div>

                <!-- Lista de Tickets -->
                <?php if (count($tickets) === 0): ?>
                    <div class="card border-0 shadow-sm p-5 text-center text-muted">
                        <i class="fas fa-folder-open mb-3" style="font-size: 3rem; color: #ddd;"></i>
                        <p class="m-0">Nenhum chamado encontrado com os filtros selecionados.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($tickets as $ticket): ?>
                            <!-- Item Compacto da Lista (Fugindo da classe .card para evitar conflitos de CSS) -->
                            <div class="border rounded-3 shadow-sm bg-white p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3" style="transition: all 0.2s;">
                                <div class="d-flex align-items-center gap-3 flex-grow-1 overflow-hidden">
                                    <span class="badge rounded-pill px-3 py-2 font-bold bg-<?= $ticket['status'] === 'Pendente' ? 'warning' : ($ticket['status'] === 'Em andamento' ? 'info' : 'success') ?>-light text-<?= $ticket['status'] === 'Pendente' ? 'warning' : ($ticket['status'] === 'Em andamento' ? 'info' : 'success') ?>" style="min-width: 110px; text-align: center;">
                                        <?= htmlspecialchars($ticket['status']) ?>
                                    </span>
                                    <div class="text-truncate">
                                        <strong class="text-slate-800 me-2">#<?= $ticket['id'] ?></strong>
                                        <span class="text-slate-700 font-semibold"><?= htmlspecialchars($ticket['subject']) ?></span>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center gap-3 flex-shrink-0">
                                    <div class="text-end d-none d-sm-block me-3">
                                        <small class="d-block text-slate-800 font-semibold"><?= htmlspecialchars($ticket['name']) ?></small>
                                        <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($ticket['created_at']))) ?></small>
                                    </div>
<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ticketModal<?= $ticket['id'] ?>" style="padding: 8px 16px; margin: 0; background-color: #2b82b3; border-color: #2b82b3; color: white;">
                                        <i class="fas fa-eye me-1"></i> Detalhes
                                    </button>
                                    <form method="post" action="admin.php?tab=tickets" class="m-0" onsubmit="return confirm('Excluir permanentemente o ticket #<?= $ticket['id'] ?>? Esta ação não pode ser desfeita.');">
                                        <input type="hidden" name="delete_ticket" value="1">
                                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
<<<<<<< HEAD
                                        <button type="submit" class="btn btn-danger btn-sm" data-loading-text="Excluindo ticket..." style="padding: 8px 16px; margin: 0; background-color: #dc3545; border-color: #dc3545; color: white;" title="Excluir Ticket">
=======
                                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 8px 16px; margin: 0; background-color: #dc3545; border-color: #dc3545; color: white;" title="Excluir Ticket">
>>>>>>> 243678c3e4b8b408795331c9a885c0e0c146c3a2
                                            <i class="fas fa-trash-alt me-1"></i> Excluir
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Modal de Detalhes do Ticket -->
                            <div class="modal fade" id="ticketModal<?= $ticket['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg">
                                        <div class="modal-header text-white" style="background:#2b82b3">
                                            <h5 class="modal-title font-bold"><i class="fas fa-ticket-alt me-2"></i> Chamado #<?= $ticket['id'] ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        
<<<<<<< HEAD
                                        <!-- ✨ O formulário agora abraça TODO o corpo e o rodapé do modal! -->
                                        <form method="post" action="admin.php?tab=tickets">
                                            <input type="hidden" name="update_ticket" value="1">
                                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                            
                                            <div class="modal-body p-4">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <h3 class="text-slate-800 font-bold m-0"><?= htmlspecialchars($ticket['subject']) ?></h3>
                                                    <span class="badge rounded-pill px-3 py-2 font-bold bg-<?= $ticket['status'] === 'Pendente' ? 'warning' : ($ticket['status'] === 'Em andamento' ? 'info' : 'success') ?>-light text-<?= $ticket['status'] === 'Pendente' ? 'warning' : ($ticket['status'] === 'Em andamento' ? 'info' : 'success') ?>">
                                                        <?= htmlspecialchars($ticket['status']) ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="row g-3 mb-4 bg-light p-3 rounded-2">
                                                    <div class="col-md-6">
                                                        <strong class="text-slate-600 d-block mb-1"><i class="fas fa-user-circle me-1"></i> Solicitante</strong>
                                                        <span class="d-block font-semibold text-slate-800"><?= htmlspecialchars($ticket['name']) ?></span>
                                                        <a href="mailto:<?= htmlspecialchars($ticket['email']) ?>" class="text-muted text-sm text-decoration-none"><?= htmlspecialchars($ticket['email']) ?></a>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong class="text-slate-600 d-block mb-1"><i class="fas fa-user-tie me-1"></i> Responsável Atual</strong>
                                                        <span class="d-block font-semibold text-slate-800"><?= htmlspecialchars($ticket['responsible']) ?></span>
                                                        <span class="text-muted text-sm">Aberto em: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($ticket['created_at']))) ?></span>
                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <strong class="text-slate-600 d-block mb-2"><i class="fas fa-align-left me-1"></i> Descrição do Chamado</strong>
                                                    <div class="text-slate-700 bg-slate-50 p-4 rounded-3 border-start border-primary border-3" style="white-space: pre-line; background-color: #f8f9fa;">
                                                        <?= htmlspecialchars($ticket['message']) ?>
                                                    </div>
                                                </div>

                                                <?php if (!empty($ticket['attachment'])): ?>
                                                    <div class="mb-3">
                                                        <strong class="d-block text-slate-600 mb-2"><i class="fas fa-paperclip me-1"></i> Arquivo Anexo</strong>
                                                        <?php $ext = strtolower(pathinfo($ticket['attachment'], PATHINFO_EXTENSION)); ?>
                                                        <?php if (in_array($ext, ['jpg','jpeg','png','gif'], true)): ?>
                                                            <a href="uploads/<?= htmlspecialchars($ticket['attachment']) ?>" target="_blank" class="d-inline-block">
                                                                <img src="uploads/<?= htmlspecialchars($ticket['attachment']) ?>" alt="Anexo" class="img-thumbnail rounded-3" style="max-height: 200px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="uploads/<?= htmlspecialchars($ticket['attachment']) ?>" target="_blank" class="btn btn-outline-info">
                                                                <i class="fas fa-file-pdf me-1"></i> Visualizar Documento
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php
                                                    // Busca respostas relacionadas a este ticket (filtrando mensagens antigas/órfãs)
                                                    $stmtR = $db->prepare('SELECT * FROM ticket_responses WHERE ticket_id = ? AND created_at >= ? ORDER BY created_at ASC');
                                                    $stmtR->execute([$ticket['id'], $ticket['created_at']]);
                                                    $responses = $stmtR->fetchAll();
                                                ?>

                                                <div class="mb-4">
                                                    <strong class="text-slate-600 d-block mb-2"><i class="fas fa-comments me-1"></i> Histórico de Respostas</strong>
                                                    <?php if (count($responses) === 0): ?>
                                                        <p class="text-muted">Nenhuma resposta registrada.</p>
                                                    <?php else: ?>
                                                        <div class="d-flex flex-column gap-2">
                                                            <?php foreach ($responses as $res): ?>
                                                                <div class="p-3 rounded-2" style="background: <?= $res['sender'] === 'admin' ? '#ffffff' : '#f8f9fa' ?>; border: 1px solid #e9ecef;">
                                                                    <small class="text-muted d-block mb-1"><?= htmlspecialchars($res['sender']) ?> — <?= htmlspecialchars(date('d/m/Y H:i', strtotime($res['created_at']))) ?></small>
                                                                    <div class="text-slate-700" style="white-space: pre-line;"><?= nl2br(htmlspecialchars($res['message'])) ?></div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label text-sm text-slate-500 font-semibold mb-1">Responder ao Usuário</label>
                                                    <textarea name="reply_message" class="form-control" rows="4" placeholder="Digite sua resposta para o usuário..."></textarea>
                                                </div>
                                            </div> <!-- ✨ Fim do modal-body -->

                                            <div class="modal-footer bg-light p-3">
                                                <div class="row g-2 align-items-end w-100 m-0">
                                                    <div class="col-md-4">
                                                        <label class="form-label text-sm text-slate-500 font-semibold mb-1">Atualizar Status</label>
                                                        <select name="status" class="form-select form-select-sm" style="box-shadow: none;">
                                                            <?php foreach (['Pendente', 'Em andamento', 'Resolvido'] as $st): ?>
                                                                <option value="<?= $st ?>" <?= $ticket['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label class="form-label text-sm text-slate-500 font-semibold mb-1">Atribuir Responsável</label>
                                                        <select name="responsible" class="form-select form-select-sm" style="box-shadow: none;">
                                                            <?php foreach (['Não sabe', 'Renata', 'Celeste'] as $resp): ?>
                                                                <option value="<?= $resp ?>" <?= $ticket['responsible'] === $resp ? 'selected' : '' ?>><?= $resp ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-4 d-flex justify-content-end gap-2">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="padding: 8px 16px; margin: 0;">Fechar</button>
                                                        
                                                        <button type="submit" class="btn btn-primary btn-sm" data-loading-text="Salvando alterações..." style="padding: 8px 16px; margin: 0; background-color: #5dc171; border-color: #5dc171; color: white;">
                                                            <i class="fas fa-save me-1"></i> Salvar
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form> 
=======
                                        <div class="modal-body p-4">
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <h3 class="text-slate-800 font-bold m-0"><?= htmlspecialchars($ticket['subject']) ?></h3>
                                                <span class="badge rounded-pill px-3 py-2 font-bold bg-<?= $ticket['status'] === 'Pendente' ? 'warning' : ($ticket['status'] === 'Em andamento' ? 'info' : 'success') ?>-light text-<?= $ticket['status'] === 'Pendente' ? 'warning' : ($ticket['status'] === 'Em andamento' ? 'info' : 'success') ?>">
                                                    <?= htmlspecialchars($ticket['status']) ?>
                                                </span>
                                            </div>
                                            
                                            <div class="row g-3 mb-4 bg-light p-3 rounded-2">
                                                <div class="col-md-6">
                                                    <strong class="text-slate-600 d-block mb-1"><i class="fas fa-user-circle me-1"></i> Solicitante</strong>
                                                    <span class="d-block font-semibold text-slate-800"><?= htmlspecialchars($ticket['name']) ?></span>
                                                    <a href="mailto:<?= htmlspecialchars($ticket['email']) ?>" class="text-muted text-sm text-decoration-none"><?= htmlspecialchars($ticket['email']) ?></a>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong class="text-slate-600 d-block mb-1"><i class="fas fa-user-tie me-1"></i> Responsável Atual</strong>
                                                    <span class="d-block font-semibold text-slate-800"><?= htmlspecialchars($ticket['responsible']) ?></span>
                                                    <span class="text-muted text-sm">Aberto em: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($ticket['created_at']))) ?></span>
                                                </div>
                                            </div>

                                            <div class="mb-4">
                                                <strong class="text-slate-600 d-block mb-2"><i class="fas fa-align-left me-1"></i> Descrição do Chamado</strong>
                                                <div class="text-slate-700 bg-slate-50 p-4 rounded-3 border-start border-primary border-3" style="white-space: pre-line; background-color: #f8f9fa;">
                                                    <?= htmlspecialchars($ticket['message']) ?>
                                                </div>
                                            </div>

                                            <?php if (!empty($ticket['attachment'])): ?>
                                                <div class="mb-3">
                                                    <strong class="d-block text-slate-600 mb-2"><i class="fas fa-paperclip me-1"></i> Arquivo Anexo</strong>
                                                    <?php $ext = strtolower(pathinfo($ticket['attachment'], PATHINFO_EXTENSION)); ?>
                                                    <?php if (in_array($ext, ['jpg','jpeg','png','gif'], true)): ?>
                                                        <a href="uploads/<?= htmlspecialchars($ticket['attachment']) ?>" target="_blank" class="d-inline-block">
                                                            <img src="uploads/<?= htmlspecialchars($ticket['attachment']) ?>" alt="Anexo" class="img-thumbnail rounded-3" style="max-height: 200px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="uploads/<?= htmlspecialchars($ticket['attachment']) ?>" target="_blank" class="btn btn-outline-info">
                                                            <i class="fas fa-file-pdf me-1"></i> Visualizar Documento
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                                    <?php
                                                        // Busca respostas relacionadas a este ticket
                                                        $stmtR = $db->prepare('SELECT * FROM ticket_responses WHERE ticket_id = ? ORDER BY created_at ASC');
                                                        $stmtR->execute([$ticket['id']]);
                                                        $responses = $stmtR->fetchAll();
                                                    ?>

                                                    <div class="mb-4">
                                                        <strong class="text-slate-600 d-block mb-2"><i class="fas fa-comments me-1"></i> Histórico de Respostas</strong>
                                                        <?php if (count($responses) === 0): ?>
                                                            <p class="text-muted">Nenhuma resposta registrada.</p>
                                                        <?php else: ?>
                                                            <div class="d-flex flex-column gap-2">
                                                                <?php foreach ($responses as $res): ?>
                                                                    <div class="p-3 rounded-2" style="background: <?= $res['sender'] === 'admin' ? '#ffffff' : '#f8f9fa' ?>; border: 1px solid #e9ecef;">
                                                                        <small class="text-muted d-block mb-1"><?= htmlspecialchars($res['sender']) ?> — <?= htmlspecialchars(date('d/m/Y H:i', strtotime($res['created_at']))) ?></small>
                                                                        <div class="text-slate-700" style="white-space: pre-line;"><?= nl2br(htmlspecialchars($res['message'])) ?></div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <form method="post" action="admin.php?tab=tickets">
                                                        <input type="hidden" name="update_ticket" value="1">
                                                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label text-sm text-slate-500 font-semibold mb-1">Responder ao Usuário</label>
                                                            <textarea name="reply_message" class="form-control" rows="4" placeholder="Digite sua resposta para o usuário..."></textarea>
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer bg-light p-3">
                                                        <div class="row g-2 align-items-end w-100 m-0">
                                                            <div class="col-md-4">
                                                                <label class="form-label text-sm text-slate-500 font-semibold mb-1">Atualizar Status</label>
                                                                <select name="status" class="form-select form-select-sm" style="box-shadow: none;">
                                                                    <?php foreach (['Pendente', 'Em andamento', 'Resolvido'] as $st): ?>
                                                                        <option value="<?= $st ?>" <?= $ticket['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="col-md-4">
                                                                <label class="form-label text-sm text-slate-500 font-semibold mb-1">Atribuir Responsável</label>
                                                                <select name="responsible" class="form-select form-select-sm" style="box-shadow: none;">
                                                                    <?php foreach (['Não sabe', 'Renata', 'Celeste'] as $resp): ?>
                                                                        <option value="<?= $resp ?>" <?= $ticket['responsible'] === $resp ? 'selected' : '' ?>><?= $resp ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="col-md-4 d-flex justify-content-end gap-2">
                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="padding: 8px 16px; margin: 0;">Fechar</button>
                                                                <button type="submit" class="btn btn-primary btn-sm" style="padding: 8px 16px; margin: 0; background-color: #5dc171; border-color: #5dc171; color: white;">
                                                                    <i class="fas fa-save me-1"></i> Salvar
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    </form>
>>>>>>> 243678c3e4b8b408795331c9a885c0e0c146c3a2
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Botões de Paginação de Tickets (AGORA NO LUGAR CERTO) -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Navegação de tickets" class="mt-4 mb-2">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?tab=tickets&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status_filter=<?= urlencode($statusFilter) ?>&responsible_filter=<?= urlencode($responsibleFilter) ?>" style="color: #2b82b3;">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?tab=tickets&page=<?= $i ?>&search=<?= urlencode($search) ?>&status_filter=<?= urlencode($statusFilter) ?>&responsible_filter=<?= urlencode($responsibleFilter) ?>" style="<?= $i === $page ? 'background-color: #2b82b3; border-color: #2b82b3; color: white;' : 'color: #2b82b3;' ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?tab=tickets&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status_filter=<?= urlencode($statusFilter) ?>&responsible_filter=<?= urlencode($responsibleFilter) ?>" style="color: #2b82b3;">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    
                <?php endif; ?>
            </section>
            <!-- ABA 3: GERENCIAR USUÁRIOS -->
            <section id="tab-users" class="tab-content-section <?= $activeTab === 'users' ? 'active' : '' ?>">
                <h2 class="font-bold text-slate-800 mb-4">Gerenciar Usuários</h2>

                <div class="row">
                    <!-- Criar Novo Administrador -->
                    <div class="col-lg-4 mb-4">
                        <div class="card border-0 shadow-sm p-4">
                            <h3 class="font-bold text-slate-800 mb-3 border-bottom pb-2"><i class="fas fa-shield-alt text-primary me-2"></i> Criar Novo Administrador</h3>
                            <form method="post" action="admin.php?tab=users">
                                <input type="hidden" name="create_admin" value="1">

                                <div class="mb-3">
                                    <label class="form-label font-semibold">Nome</label>
                                    <div class="input-group" style="margin-top: 5px;">
                                        <span class="input-group-text bg-white border-end-0" style="padding: 12px;">
                                            <i class="fas fa-user text-muted"></i>
                                        </span>
                                        <input type="text" name="admin_name" class="form-control border-start-0" placeholder="Nome do administrador" required style="margin-top: 0; box-shadow: none;">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label font-semibold">E-mail</label>
                                    <div class="input-group" style="margin-top: 5px;">
                                        <span class="input-group-text bg-white border-end-0" style="padding: 12px;">
                                            <i class="fas fa-envelope text-muted"></i>
                                        </span>
                                        <input type="email" name="admin_email" class="form-control border-start-0" placeholder="admin@arruda.com" required style="margin-top: 0; box-shadow: none;">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label font-semibold">Senha</label>
                                    <div class="input-group" style="margin-top: 5px;">
                                        <span class="input-group-text bg-white border-end-0" style="padding: 12px;">
                                            <i class="fas fa-lock text-muted"></i>
                                        </span>
                                        <input type="password" name="admin_password" class="form-control border-start-0" placeholder="Senha inicial" required style="margin-top: 0; box-shadow: none;">
                                    </div>
                                </div>

<<<<<<< HEAD
                                <button type="submit" class="btn btn-primary w-100" data-loading-text="Salvando administrador..." style="padding: 12px; margin-top: 15px; background-color: #5dc171; border-color: #5dc171; color: white;">
=======
                                <button type="submit" class="btn btn-primary w-100" style="padding: 12px; margin-top: 15px; background-color: #5dc171; border-color: #5dc171; color: white;">
>>>>>>> 243678c3e4b8b408795331c9a885c0e0c146c3a2
                                    <i class="fas fa-user-shield me-1"></i> Cadastrar Administrador
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Administradores Cadastrados -->
                    <div class="col-lg-8 mb-4">
                        <div class="card border-0 shadow-sm p-4">
                            <h3 class="font-bold text-slate-800 mb-3 border-bottom pb-2"><i class="fas fa-users-cog text-primary me-2"></i> Administradores Cadastrados</h3>

                            <?php if (count($admins) === 0): ?>
                                <p class="text-muted text-center py-5">Nenhum administrador cadastrado.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr class="table-light">
                                                <th>ID</th>
                                                <th>Nome</th>
                                                <th>E-mail</th>
                                                <th>Data de Cadastro</th>
                                                <th class="text-end">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($admins as $admin): ?>
                                                <tr>
                                                    <td><strong class="text-muted"><?= $admin['id'] ?></strong></td>
                                                    <td><strong class="text-slate-800"><?= htmlspecialchars($admin['name']) ?></strong></td>
                                                    <td><?= htmlspecialchars($admin['email']) ?></td>
                                                    <td><span class="text-sm text-muted"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($admin['created_at']))) ?></span></td>
                                                    <td class="text-end">
                                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                                            <button type="button" class="btn btn-primary btn-sm" style="padding: 6px 12px; margin: 0; background-color: #2b82b3; border-color: #2b82b3; color: white;" onclick="openEditAdminModal(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($admin['email'], ENT_QUOTES) ?>')" title="Editar Administrador">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <form method="post" action="admin.php?tab=users" class="m-0" onsubmit="return confirm('Excluir o administrador \"<?= htmlspecialchars($admin['name'], ENT_QUOTES) ?>\"?');">
                                                                <input type="hidden" name="delete_admin" value="1">
                                                                <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
<<<<<<< HEAD
                                                                <button type="submit" class="btn btn-danger btn-sm" data-loading-text="Excluindo administrador..." style="padding: 6px 12px; margin: 0; background-color: #dc3545; border-color: #dc3545; color: white;" title="Excluir Administrador">
=======
                                                                <button type="submit" class="btn btn-danger btn-sm" style="padding: 6px 12px; margin: 0; background-color: #dc3545; border-color: #dc3545; color: white;" title="Excluir Administrador">
>>>>>>> 243678c3e4b8b408795331c9a885c0e0c146c3a2
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Cadastro de Usuário -->
                   <div class="col-lg-4 mb-4">
                        <div class="card border-0 shadow-sm p-4">
                            <h3 class="font-bold text-slate-800 mb-3 border-bottom pb-2"><i class="fas fa-user-plus text-primary me-2"></i> Criar Novo Usuário</h3>
                            <form method="post" action="admin.php?tab=users">
                                <input type="hidden" name="create_user" value="1">
                                <input type="hidden" name="current_page_users" id="current_page_users" value="<?= $pageUsers ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label font-semibold">Nome de Usuário</label>
                                    <div class="input-group" style="margin-top: 5px;">
                                        <span class="input-group-text bg-white border-end-0" style="padding: 12px;">
                                            <i class="fas fa-user text-muted"></i>
                                        </span>
                                        <input type="text" name="username" class="form-control border-start-0" placeholder="joaosilva" required style="margin-top: 0; box-shadow: none;">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label font-semibold">E-mail</label>
                                    <div class="input-group" style="margin-top: 5px;">
                                        <span class="input-group-text bg-white border-end-0" style="padding: 12px;">
                                            <i class="fas fa-envelope text-muted"></i>
                                        </span>
                                        <input type="email" name="email" class="form-control border-start-0" placeholder="joao@arruda.com" required style="margin-top: 0; box-shadow: none;">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label font-semibold">Senha Inicial</label>
                                    <div class="input-group" style="margin-top: 5px;">
                                        <span class="input-group-text bg-white border-end-0" style="padding: 12px;">
                                            <i class="fas fa-lock text-muted"></i>
                                        </span>
                                        <input type="password" name="password" class="form-control border-start-0" placeholder="Mínimo 6 caracteres" required style="margin-top: 0; box-shadow: none;">
                                    </div>
                                </div>

<<<<<<< HEAD
                                <button type="submit" class="btn btn-primary w-100" data-loading-text="Salvando usuário..." style="padding: 12px; margin-top: 15px; background-color: #5dc171; border-color: #5dc171; color: white;">
=======
                                <button type="submit" class="btn btn-primary w-100" style="padding: 12px; margin-top: 15px; background-color: #5dc171; border-color: #5dc171; color: white;">
>>>>>>> 243678c3e4b8b408795331c9a885c0e0c146c3a2
                                    <i class="fas fa-check-circle me-1"></i> Cadastrar Usuário
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Tabela de Usuários -->

                   <div class="col-lg-8 mb-4">
                        <div class="card border-0 shadow-sm p-4">
                            <h3 class="font-bold text-slate-800 mb-3 border-bottom pb-2"><i class="fas fa-users text-primary me-2"></i> Usuários Cadastrados</h3>
                            
                            <?php if (count($users) === 0): ?>
                                <p class="text-muted text-center py-5">Nenhum usuário registrado no banco de dados.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr class="table-light">
                                                <th>ID</th>
                                                <th>Usuário</th>
                                                <th>E-mail</th>
                                                <th>Data de Cadastro</th>
                                                <th class="text-end">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><strong class="text-muted"><?= $user['id'] ?></strong></td>
                                                    <td><strong class="text-slate-800"><?= htmlspecialchars($user['username']) ?></strong></td>
                                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                                    <td><span class="text-sm text-muted"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($user['created_at']))) ?></span></td>
                                                    <td class="text-end">
                                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                                            <button type="button" class="btn btn-primary btn-sm" style="padding: 6px 12px; margin: 0; background-color: #2b82b3; border-color: #2b82b3; color: white;" onclick="openEditModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>')" title="Editar Usuário">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            
                                                            <form method="post" action="admin.php?tab=users" class="m-0" onsubmit="return confirm('Excluir permanentemente o usuário \'<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>\'? Os tickets dele continuarão existindo mas sem vínculo de usuário.');">
                                                                <input type="hidden" name="delete_user" value="1">
                                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
<<<<<<< HEAD
                                                                <button type="submit" class="btn btn-danger btn-sm" data-loading-text="Excluindo usuário..." style="padding: 6px 12px; margin: 0; background-color: #dc3545; border-color: #dc3545; color: white;" title="Excluir Usuário">
=======
                                                                <button type="submit" class="btn btn-danger btn-sm" style="padding: 6px 12px; margin: 0; background-color: #dc3545; border-color: #dc3545; color: white;" title="Excluir Usuário">
>>>>>>> 243678c3e4b8b408795331c9a885c0e0c146c3a2
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Botões de Paginação de Usuários -->
                                <?php if ($totalPagesUsers > 1): ?>
                                    <nav aria-label="Navegação de usuários" class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item <?= $pageUsers <= 1 ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?tab=users&page_users=<?= $pageUsers - 1 ?>" style="color: var(--arruda-blue);">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                            <?php for ($i = 1; $i <= $totalPagesUsers; $i++): ?>
                                                <li class="page-item <?= $i === $pageUsers ? 'active' : '' ?>">
                                                    <a class="page-link" href="?tab=users&page_users=<?= $i ?>" style="<?= $i === $pageUsers ? 'background-color: var(--arruda-blue); border-color: var(--arruda-blue); color: white;' : 'color: var(--arruda-blue);' ?>">
                                                        <?= $i ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            <li class="page-item <?= $pageUsers >= $totalPagesUsers ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?tab=users&page_users=<?= $pageUsers + 1 ?>" style="color: var(--arruda-blue);">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

    <!-- Modal de Edição de Usuário -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background:#2b82b3">
                    <h5 class="modal-title font-bold" id="editUserModalLabel"><i class="fas fa-user-edit me-2"></i> Editar Usuário</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="admin.php?tab=users&page_users=<?= $pageUsers ?>">
                <input type="hidden" name="update_user" value="1">
                <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label font-semibold">Nome de Usuário</label>
                            <input type="text" name="edit_username" id="edit_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">E-mail</label>
                            <input type="email" name="edit_email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">Nova Senha (deixe em branco para manter a atual)</label>
                            <input type="password" name="edit_password" id="edit_password" class="form-control" placeholder="Senha nova (opcional)">
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background:#2b82b3">
                    <h5 class="modal-title font-bold" id="editAdminModalLabel"><i class="fas fa-user-shield me-2"></i> Editar Administrador</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="admin.php?tab=users">
                    <input type="hidden" name="update_admin" value="1">
                    <input type="hidden" name="admin_id" id="edit_admin_id">

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label font-semibold">Nome</label>
                            <input type="text" name="edit_admin_name" id="edit_admin_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">E-mail</label>
                            <input type="email" name="edit_admin_email" id="edit_admin_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">Nova Senha (deixe em branco para manter a atual)</label>
                            <input type="password" name="edit_admin_password" id="edit_admin_password" class="form-control" placeholder="Senha nova (opcional)">
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openEditModal(id, username, email) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_password').value = '';

           var myModal = new bootstrap.Modal(document.getElementById('editUserModal'));
        myModal.show();
        }

        function openEditAdminModal(id, name, email) {
            document.getElementById('edit_admin_id').value = id;
            document.getElementById('edit_admin_name').value = name;
            document.getElementById('edit_admin_email').value = email;
            document.getElementById('edit_admin_password').value = '';

            const modal = new bootstrap.Modal(document.getElementById('editAdminModal'));
            modal.show();
        }
    </script>
<<<<<<< HEAD
    <script>
    // Bloqueia envios repetidos e mostra feedback no botão correto
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (form.getAttribute('data-submitting') === 'true') {
                e.preventDefault();
                return false;
            }

            form.setAttribute('data-submitting', 'true');

            var btn = this.querySelector('button[type="submit"]');
            if (!btn) {
                return;
            }

            var loadingText = btn.dataset.loadingText || 'Salvando...';

            if (form.querySelector('input[name="delete_ticket"], input[name="delete_user"], input[name="delete_admin"]')) {
                loadingText = 'Excluindo...';
            } else if (form.querySelector('input[name="reply_message"], input[name="update_ticket"]')) {
                loadingText = 'Salvando...';
            } else if (form.querySelector('input[name="create_admin"], input[name="create_user"], input[name="update_admin"], input[name="update_user"]')) {
                loadingText = 'Salvando...';
            } else if (form.querySelector('input[name="admin_login"]')) {
                loadingText = 'Entrando...';
            }

            setTimeout(function() {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> ' + loadingText;
            }, 10);
        });
    });
</script>
=======
>>>>>>> 243678c3e4b8b408795331c9a885c0e0c146c3a2
<?php endif; ?>

</body>
</html>