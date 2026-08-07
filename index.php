<?php
require_once __DIR__ . '/config.php';

// Flag para o header.php saber que esta página é área do cliente (não admin)
$hideAdminInHeader = true;


date_default_timezone_set('America/Sao_Paulo');
$db = getDb();
$message = '';
//$importResult = importPop3Emails($db);
//if (!empty($importResult['imported']) && $message === '') {
  //  $message = 'Novos tickets foram importados a partir do e-mail.';
//}

// Verifica se o usuário está autenticado. Caso contrário, redireciona para login_fun.php
if (empty($_SESSION['user_id'])) {
    header('Location: login_fun.php');
    exit;
}

$currentUser = getUserById($db, (int)$_SESSION['user_id']);
if (!$currentUser) {
    session_unset();
    session_destroy();
    header('Location: login_fun.php');
    exit;
}

// garante que a pasta de uploads exista
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', __DIR__ . '/uploads');
}
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Inicia com os dados do usuário autenticado
$name = $currentUser['username'];
$email = $currentUser['email'];
$subject = '';
$messageText = '';
$responsible = 'Não sabe';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Força o nome e e-mail a serem os do usuário autenticado para segurança
    $name = $currentUser['username'];
    $email = $currentUser['email'];
    $subject = trim($_POST['subject'] ?? '');
    $messageText = trim($_POST['message'] ?? '');
    $responsible = trim($_POST['responsible'] ?? 'Não sabe');
    $attachment = null;

    if ($subject === '' || $messageText === '') {
        $message = 'Por favor, preencha todos os campos obrigatórios do formulário.';
    } else {
        // processa anexo, se houver
        if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'application/pdf' => 'pdf',
            ];
            if (!array_key_exists($mime, $allowed)) {
                $message = 'Anexo inválido. Apenas imagens e PDFs são permitidos.';
            } elseif ($file['size'] > 20 * 1024 * 1024) {
                $message = 'Arquivo muito grande. Limite 20MB.';
            } else {
                $ext = $allowed[$mime];
                $basename = bin2hex(random_bytes(8));
                $filename = $basename . '.' . $ext;
                if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $filename)) {
                    $message = 'Falha ao salvar o anexo.';
                } else {
                    $attachment = $filename;
                }
            }
        }

        // se não houve erro de anexo, insere no banco
        if ($message === '') {
            $stmt = $db->prepare('INSERT INTO tickets (user_id, name, email, subject, message, responsible, attachment) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $userId = $currentUser['id'];
            $stmt->execute([$userId, $name, $email, $subject, $messageText, $responsible, $attachment]);
            $ticketId = (int) $db->lastInsertId();
            $sent = sendNewTicketNotifications($db, $ticketId);
            if ($sent) {
                $message = 'Solicitação registrada com sucesso. Confirmação enviada para o seu email.';
            } else {
                $message = 'Solicitação registrada com sucesso. Falha ao enviar confirmação para o seu email.';
            }
            // Limpa os campos do formulário pós-envio
            $subject = $messageText = '';
            $responsible = 'Não sabe';
        }
    }
}

// Filtra para exibir apenas os tickets do próprio usuário logado
$stmt = $db->prepare('SELECT * FROM tickets WHERE user_id = ? OR email = ? ORDER BY created_at DESC LIMIT 20');
$stmt->execute([$currentUser['id'], $currentUser['email']]);
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Tickets - Help Desk</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"> 
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 
   <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 512 512%22><path fill=%22%232b82b3%22 d=%22M495.9 166.6c3.2 8.7.5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6-4.4 11.9-9.7 23.3-15.8 34.3-4.7 8.3-10.3 16.1-16.8 23.2-5.6 6.1-12.3 11.2-19.7 15.2l-56.9-19c-16.6 13.1-35.6 23.6-56.3 31.1l-11.8 59.2c-2.3 11.5-10.9 20.6-22.3 23-13.6 2.8-27.5 4.3-41.6 4.3s-28-1.5-41.6-4.3c-11.4-2.4-20-11.5-22.3-23l-11.8-59.2c-20.7-7.5-39.7-18.1-56.3-31.1l-56.9 19c-7.4-4-14.1-9.1-19.7-15.2-6.5-7.1-12.1-14.9-16.8-23.2-6.1-11-11.4-22.4-15.8-34.3-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6 4.4-11.9 9.7-23.3 15.8-34.3 4.7-8.3 10.3-16.1 16.8-23.2 5.6-6.1 12.3-11.2 19.7-15.2l56.9 19c16.6-13.1 35.6-23.6 56.3-31.1l11.8-59.2c2.3-11.5 10.9-20.6 22.3-23C228.4 1.5 242.3 0 256 0s28 1.5 41.6 4.3c11.4 2.4 20 11.5 22.3 23l11.8 59.2c20.7 7.5 39.7 18.1 56.3 31.1l56.9-19c7.4 4 14.1 9.1 19.7 15.2 6.5 7.1 12.1 14.9 16.8 23.2 6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z%22/></svg>">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 512 512%22><path fill=%22%232b82b3%22 d=%22M495.9 166.6c3.2 8.7.5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6-4.4 11.9-9.7 23.3-15.8 34.3-4.7 8.3-10.3 16.1-16.8 23.2-5.6 6.1-12.3 11.2-19.7 15.2l-56.9-19c-16.6 13.1-35.6 23.6-56.3 31.1l-11.8 59.2c-2.3 11.5-10.9 20.6-22.3 23-13.6 2.8-27.5 4.3-41.6 4.3s-28-1.5-41.6-4.3c-11.4-2.4-20-11.5-22.3-23l-11.8-59.2c-20.7-7.5-39.7-18.1-56.3-31.1l-56.9 19c-7.4-4-14.1-9.1-19.7-15.2-6.5-7.1-12.1-14.9-16.8-23.2-6.1-11-11.4-22.4-15.8-34.3-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6 4.4-11.9 9.7-23.3 15.8-34.3 4.7-8.3 10.3-16.1 16.8-23.2 5.6-6.1 12.3-11.2 19.7-15.2l56.9 19c16.6-13.1 35.6-23.6 56.3-31.1l11.8-59.2c2.3-11.5 10.9-20.6 22.3-23C228.4 1.5 242.3 0 256 0s28 1.5 41.6 4.3c11.4 2.4 20 11.5 22.3 23l11.8 59.2c20.7 7.5 39.7 18.1 56.3 31.1l56.9-19c7.4 4 14.1 9.1 19.7 15.2 6.5 7.1 12.1 14.9 16.8 23.2 6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z%22/></svg>">

</head>
<body>
    <!-- Navbar -->
    <?php include 'header.php'; ?>

    <main class="container mt-5 mb-5">
        <h1 class="text-arruda-deep" style="font-weight: 700; margin-bottom: 40px; text-align: center;">Área do Cliente</h1>


        <div class="row">
            <div class="col-lg-8">
                <section class="card ">
                    <h2><i class="fas fa-plus " ></i> Abrir Novo Ticket</h2>
                    <?php if ($message !== ''): ?>
                        <div class="alert <?= strpos($message, 'sucesso') !== false ? 'alert-success' : 'alert-error' ?>">
                            <i class="fas <?= strpos($message, 'sucesso') !== false ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i> 
                            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                        </div>

                    <?php endif; ?>
                    <form method="post" action="index.php" enctype="multipart/form-data">
                        <label><i class="fas fa-user"></i> Nome
                            <input type="text" name="name" value="<?= htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8') ?>" required readonly style="background-color: #e9ecef; cursor: not-allowed;">
                        </label>
                        <label><i class="fas fa-envelope"></i> E-mail
                            <input type="email" name="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>" required readonly style="background-color: #e9ecef; cursor: not-allowed;">
                        </label>
                        <label><i class="fas fa-heading"></i> Assunto
                            <input type="text" name="subject" value="<?= htmlspecialchars($subject ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Descreva seu problema brevemente" required>
                        </label>
                        <label><i class="fas fa-user-tie"></i> Responsável
                            <select name="responsible">
                                <?php foreach (['Não sabe', 'Renata', 'Celeste'] as $option): ?>
                                    <option value="<?= $option ?>" <?= ($responsible ?? 'Não sabe') === $option ? 'selected' : '' ?>><?= $option ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label><i class="fas fa-comment"></i> Mensagem
                            <textarea name="message" rows="6" placeholder="Descreva detalhadamente o seu problema..." required><?= htmlspecialchars($messageText ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </label>
                        <label><i class="fas fa-paperclip"></i> Anexo (imagem ou PDF - Máximo 20MB)
                        <input type="file" name="attachment" accept="image/*,application/pdf">
                        </label>
                        <button type="submit" class="btn-arruda-primary" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i> Enviar Ticket
                        </button>

                    </form>
                </section>
            </div>

            <div class="col-lg-4">
                <section class="card card-arruda-info">
                    <h2 class="text-arruda-cyan" style="border-bottom-color: var(--arruda-deep);"><i class="fas fa-lightbulb"></i> Dica</h2>
                    <p class="text-arruda-deep" style="margin: 0; font-weight: 600; margin-bottom: 10px;">Como descrever melhor seu problema:</p>

                    <ul style="color: #555; line-height: 2; margin: 0; padding-left: 20px;">
                        <li><i class="fas fa-check-circle" style="color: #4ecdc4; margin-right: 8px;"></i> Seja claro e objetivo</li>
                        <li><i class="fas fa-check-circle" style="color: #4ecdc4; margin-right: 8px;"></i> Inclua mensagens de erro</li>
                        <li><i class="fas fa-check-circle" style="color: #4ecdc4; margin-right: 8px;"></i> Anexe prints se necessário</li>
                    </ul>
                </section>
            </div>
        </div>

       <section class="card border-0 shadow-sm p-4" style="margin-top: 40px;">
    <h2 class="font-bold text-slate-800 mb-4 border-bottom pb-2"><i class="fas fa-list text-primary me-2"></i> Meus Chamados</h2>
    
    <?php if (count($tickets) === 0): ?>
        <div style="text-align: center; padding: 60px 20px; color: #999;">
            <i class="fas fa-inbox" style="font-size: 4rem; color: #ddd; display: block; margin-bottom: 20px;"></i>
            <p style="font-size: 1.1rem; margin: 0;">Você ainda não possui solicitações registradas.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($tickets as $ticket): ?>
                <div class="border rounded-3 shadow-sm bg-white p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3" style="transition: all 0.2s;">
                    <div class="d-flex align-items-center gap-3 flex-grow-1 overflow-hidden">
                        <span class="badge rounded-pill px-3 py-2 font-bold bg-<?= $ticket['status'] === 'Pendente' ? 'warning' : ($ticket['status'] === 'Em andamento' ? 'info' : 'success') ?>-light text-<?= $ticket['status'] === 'Pendente' ? 'warning' : ($ticket['status'] === 'Em andamento' ? 'info' : 'success') ?>" style="min-width: 110px; text-align: center;">
                            <?= htmlspecialchars($ticket['status']) ?>
                        </span>
                        
                        <div class="text-truncate">
                            <strong class="text-slate-800 font-semibold"><?= htmlspecialchars($ticket['subject']) ?></strong>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3 flex-shrink-0">
                        <div class="text-end d-none d-sm-block me-2">
                            <small class="text-muted"><i class="fas fa-clock me-1"></i> <?= htmlspecialchars($ticket['created_at']) ?></small>
                        </div>
                        
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#clientTicketModal<?= $ticket['id'] ?>" style="padding: 8px 16px; margin: 0; background-color: #2b82b3; border-color: #2b82b3; color: white;">
                            <i class="fas fa-eye me-1"></i> Detalhes
                        </button>
                    </div>
                </div>

                <div class="modal fade" id="clientTicketModal<?= $ticket['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header text-white" style="background:#2b82b3">
                                <h5 class="modal-title font-bold"><i class="fas fa-ticket-alt me-2"></i> Chamado: <?= htmlspecialchars($ticket['subject']) ?></h5>
                               <button type="button" class="ms-auto" data-bs-dismiss="modal" aria-label="Close" style="background: transparent; border: none; color: white; font-size: 1.5rem; padding: 0; box-shadow: none;">
    <i class="fas fa-times"></i>
</button>
                            </div>
                            
                            <div class="modal-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                                    <h3 class="text-slate-800 font-bold m-0"><?= htmlspecialchars($ticket['subject']) ?></h3>
                                    <span class="badge rounded-pill px-3 py-2 font-bold bg-<?= $ticket['status'] === 'Pendente' ? 'warning' : ($ticket['status'] === 'Em andamento' ? 'info' : 'success') ?>-light text-<?= $ticket['status'] === 'Pendente' ? 'warning' : ($ticket['status'] === 'Em andamento' ? 'info' : 'success') ?>">
                                        <?= htmlspecialchars($ticket['status']) ?>
                                    </span>
                                </div>
                                
                                <div class="row g-3 mb-4 bg-light p-3 rounded-2">
                                    <div class="col-md-6">
                                        <strong class="text-arruda-deep d-block mb-1"><i class="fas fa-user-circle me-1"></i> Solicitante</strong>
                                        <span class="d-block font-semibold text-slate-800"><?= htmlspecialchars($ticket['name']) ?></span>
                                        <span class="text-muted text-sm"><?= htmlspecialchars($ticket['email']) ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong class="text-arruda-deep d-block mb-1"><i class="fas fa-briefcase me-1"></i> Responsável</strong>
                                        <span class="d-block font-semibold text-slate-800"><?= htmlspecialchars($ticket['responsible']) ?></span>
                                        <span class="text-muted text-sm">Registrado em: <?= htmlspecialchars($ticket['created_at']) ?></span>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <strong class="text-arruda-deep d-block mb-2"><i class="fas fa-align-left me-1"></i> Mensagem / Descrição</strong>
                                    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 3px solid var(--arruda-cyan); color: var(--arruda-text); line-height: 1.6; white-space: pre-line;">
                                        <?= nl2br(htmlspecialchars($ticket['message'], ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                </div>

                                <?php if (!empty($ticket['attachment'])): ?>
                                    <div class="mb-3">
                                        <strong class="d-block text-arruda-deep mb-2"><i class="fas fa-paperclip me-1"></i> Arquivo Anexo</strong>
                                        <?php $ext = strtolower(pathinfo($ticket['attachment'], PATHINFO_EXTENSION)); ?>
                                        <?php if (in_array($ext, ['jpg','jpeg','png','gif'], true)): ?>
                                            <a href="uploads/<?= htmlspecialchars($ticket['attachment'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="d-inline-block">
                                                <img src="uploads/<?= htmlspecialchars($ticket['attachment'], ENT_QUOTES, 'UTF-8') ?>" alt="Anexo" class="img-thumbnail rounded-3" style="max-height: 250px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                            </a>
                                        <?php else: ?>
                                            <a href="uploads/<?= htmlspecialchars($ticket['attachment'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-outline-info btn-sm" style="color: var(--arruda-cyan); border-color: var(--arruda-cyan);">
                                                <i class="fas fa-file-pdf me-1"></i> Download do anexo
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="modal-footer bg-light p-3">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="padding: 8px 16px;">Fechar Detalhes</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
    </main>

    <footer style="background-color: #3f9acb; color: #ffff; padding: 30px 20px; text-align: center;">
        <p style="margin: 0;">&copy; 2026 Arruda Empresarial - Help Desk. Todos os direitos reservados.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelector('form').addEventListener('submit', function(e) {
    var btn = this.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    }
});
</script>
</body>
</html>
