<?php
// Configuração de acesso ao banco MySQL
// No KingHost, crie um banco de dados e um usuário, depois atualize esses valores.

const DB_HOST = 'SEU';
const DB_NAME = 'SEU';
const DB_USER = 'SEU';
const DB_PASS = 'SEU';

// Configuração de email para notificações e recebimento de tickets
const MAIL_FROM_ADDRESS = 'SEU';
const MAIL_FROM_NAME = 'SEU';
const MAIL_ADMIN_ADDRESS = 'SEU';

// Configurações de Envio (SMTP)
const MAIL_SMTP_HOST = 'SEU';
const MAIL_SMTP_PORT = 587;
const MAIL_SMTP_USER = 'SEU';
const MAIL_SMTP_PASS = 'SEU';
const MAIL_SMTP_SECURE = 'tls';

// Configurações de Recebimento (POP3)
const MAIL_POP3_HOST = 'SEU';
const MAIL_POP3_PORT = 995; 
const MAIL_POP3_USER = 'SEU';
const MAIL_POP3_PASS = 'SEU';
const MAIL_POP3_SECURE = 'ssl'; // use 'ssl', 'tls' ou deixe vazio se não usar

// Diretório para armazenar uploads (anexos)
define('UPLOAD_DIR', __DIR__ . '/uploads');

// Inicia sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getDb(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $db = new PDO($dsn, DB_USER, DB_PASS, $options);
    ensureDbSchema($db);
    return $db;
}

function ensureDbSchema(PDO $db): void
{
    $db->exec(
        "CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(180) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            `responsible` VARCHAR(80) NOT NULL DEFAULT 'Não sabe',
            attachment VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Pendente'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // Se a tabela já existia sem a coluna `attachment`, adiciona-a
    $hasAttachment = false;
    try {
        $stmt = $db->query("SHOW COLUMNS FROM `tickets` LIKE 'attachment'");
        $hasAttachment = (bool) $stmt->fetch();
    } catch (Throwable $e) {
        $hasAttachment = false;
    }

    if (!$hasAttachment) {
        $db->exec("ALTER TABLE `tickets` ADD COLUMN `attachment` VARCHAR(255) DEFAULT NULL");
    }

    $hasResponsible = false;
    try {
        $stmt = $db->query("SHOW COLUMNS FROM `tickets` LIKE 'responsible'");
        $hasResponsible = (bool) $stmt->fetch();
    } catch (Throwable $e) {
        $hasResponsible = false;
    }

    if (!$hasResponsible) {
        $db->exec("ALTER TABLE `tickets` ADD COLUMN `responsible` VARCHAR(80) NOT NULL DEFAULT 'Não sabe' AFTER `message`");
    }

    // cria tabela users se não existir
    $db->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(80) NOT NULL UNIQUE,
            email VARCHAR(180) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // cria tabela admins se não existir
    $db->exec(
        "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(180) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // semeia a tabela admins com dois administradores fixos se estiver vazia
    $stmtAdmins = $db->query("SELECT COUNT(*) FROM admins");
    $adminsCount = (int)$stmtAdmins->fetchColumn();
    if ($adminsCount === 0) {
        $hash = password_hash('arruda@2026', PASSWORD_DEFAULT);
        $stmtInsert = $db->prepare("INSERT INTO admins (name, email, password_hash) VALUES (?, ?, ?)");
        $stmtInsert->execute([''SEU';', ''SEU';', $hash]);
        $stmtInsert->execute([''SEU';', ''SEU';', $hash]);
    }

    // se a tabela tickets não tiver user_id, adiciona a coluna
    $hasUserId = false;
    try {
        $stmt = $db->query("SHOW COLUMNS FROM `tickets` LIKE 'user_id'");
        $hasUserId = (bool) $stmt->fetch();
    } catch (Throwable $e) {
        $hasUserId = false;
    }
    if (!$hasUserId) {
        $db->exec("ALTER TABLE `tickets` ADD COLUMN `user_id` INT NULL AFTER `id`");
    }

    // cria tabela de respostas de tickets (historico de mensagens entre admin/usuario)
    $db->exec(
        "CREATE TABLE IF NOT EXISTS ticket_responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            sender VARCHAR(20) NOT NULL COMMENT 'user|admin',
            message TEXT NOT NULL,
            admin_email VARCHAR(180) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (ticket_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

// Funções auxiliares de autenticação
function createUser(PDO $db, string $username, string $email, string $password): bool
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
    try {
        return $stmt->execute([$username, $email, $hash]);
    } catch (PDOException $e) {
        mailLog('createUser failed: ' . $e->getMessage());
        return false;
    }
}

function getUserByUsername(PDO $db, string $username)
{
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function getUserById(PDO $db, int $id)
{
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function verifyUser(PDO $db, string $username, string $password)
{
    $user = getUserByUsername($db, $username);
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }
    return false;
}

function getUserByEmail(PDO $db, string $email)
{
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function verifyUserByEmail(PDO $db, string $email, string $password)
{
    $user = getUserByEmail($db, $email);
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }
    return false;
}

function verifyAdminByEmail(PDO $db, string $email, string $password)
{
    $stmt = $db->prepare('SELECT * FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        return $admin;
    }
    return false;
}

function createAdmin(PDO $db, string $name, string $email, string $password): bool
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO admins (name, email, password_hash) VALUES (?, ?, ?)');
    try {
        return $stmt->execute([$name, $email, $hash]);
    } catch (PDOException $e) {
        mailLog('createAdmin failed: ' . $e->getMessage());
        return false;
    }
}

function getAdminById(PDO $db, int $id)
{
    $stmt = $db->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllAdmins(PDO $db): array
{
    $stmt = $db->query('SELECT * FROM admins ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function updateAdmin(PDO $db, int $id, string $name, string $email, ?string $password = null): bool
{
    if ($password !== null && $password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE admins SET name = ?, email = ?, password_hash = ? WHERE id = ?');
        return $stmt->execute([$name, $email, $hash, $id]);
    }

    $stmt = $db->prepare('UPDATE admins SET name = ?, email = ? WHERE id = ?');
    return $stmt->execute([$name, $email, $id]);
}

function getTicketById(PDO $db, int $id)
{
    $stmt = $db->prepare('SELECT * FROM tickets WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function mimeHeader(string $text): string
{
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

function mailLog(string $text): void
{
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/mail.log';
    $time = date('Y-m-d H:i:s');
    file_put_contents($file, "[$time] " . $text . "\n", FILE_APPEND | LOCK_EX);
}

function sendEmailSMTP(string $to, string $subject, string $message, string $fromEmail = MAIL_FROM_ADDRESS, string $fromName = MAIL_FROM_NAME, array $attachments = []): bool
{
    $fromHeader = mimeHeader($fromName) . ' <' . $fromEmail . '>';
    $subjectHeader = mimeHeader($subject);
    $headers = "From: $fromHeader\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "X-Helpdesk: 1\r\n";

    $boundary = '==BOUNDARY_' . md5(uniqid('', true));
    $hasAttachments = count($attachments) > 0;

    if ($hasAttachments) {
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    } else {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    }

    $bodyHtml = '<html><body style="font-family:Arial,sans-serif;background:#f5f7fb;color:#1f2937;margin:0;padding:0;">'
        . '<div style="max-width:680px;margin:0 auto;background:#ffffff;padding:24px;border-radius:12px;box-shadow:0 8px 24px rgba(31,41,55,0.08);">'
        . $message
        . '<hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">'
        . '<p style="color:#6b7280;font-size:14px;margin:0;">Equipe Help Desk Arruda<br>help-desk@arrudaempresarial.com.br</p>'
        . '</div></body></html>';

    if ($hasAttachments) {
        $body = "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($bodyHtml) . "\r\n\r\n";

        foreach ($attachments as $attachment) {
            if (!is_file($attachment) || !is_readable($attachment)) {
                continue;
            }
            $filename = basename($attachment);
            $content = chunk_split(base64_encode(file_get_contents($attachment)));
            $mimeType = mime_content_type($attachment) ?: 'application/octet-stream';

            $body .= "--$boundary\r\n";
            $body .= "Content-Type: $mimeType; name=\"$filename\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
            $body .= $content . "\r\n\r\n";
        }
        $body .= "--$boundary--\r\n";
    } else {
        $body = $bodyHtml;
    }

    $logEntries = [];
    $logEntries[] = "Send attempt to: $to | Subject: $subject";
    $logEntries[] = 'Attachments: ' . ($hasAttachments ? implode(', ', array_map('basename', $attachments)) : 'none');

    if (MAIL_SMTP_HOST === '') {
        $result = mail($to, $subjectHeader, $body, $headers);
        $logEntries[] = 'Used PHP mail(): ' . ($result ? 'success' : 'failure');
        mailLog(implode("\n", $logEntries));
        return $result;
    }

    $remote = (MAIL_SMTP_SECURE === 'ssl' ? 'ssl://' : 'tcp://') . MAIL_SMTP_HOST . ':' . MAIL_SMTP_PORT;
    $logEntries[] = "Connecting to $remote";
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ]);
    $socket = @stream_socket_client($remote, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        $logEntries[] = "socket failed: $errno - $errstr";
        mailLog(implode("\n", $logEntries));
        return false;
    }

    stream_set_timeout($socket, 30);
    $response = trim(fgets($socket, 515));
    $logEntries[] = "S: $response";
    if (substr($response, 0, 3) !== '220') {
        fclose($socket);
        $logEntries[] = 'Server did not greet with 220';
        mailLog(implode("\n", $logEntries));
        return false;
    }

    $send = function (string $command) use ($socket, &$logEntries): void {
        fwrite($socket, $command . "\r\n");
        $logEntries[] = "C: $command";
    };

    $send('EHLO localhost');
    $line = trim(fgets($socket, 515));
    $logEntries[] = "S: $line";
    while ($line !== false && strlen($line) >= 4 && ($line[3] === '-' || $line[3] === ' ')) {
        if ($line[3] === '-') {
            $line = trim(fgets($socket, 515));
            $logEntries[] = "S: $line";
            continue;
        }
        break;
    }
    if (MAIL_SMTP_SECURE === 'tls') {
        $send('STARTTLS');
        $response = trim(fgets($socket, 515));
        $logEntries[] = "S: $response";
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            $logEntries[] = 'STARTTLS rejected';
            mailLog(implode("\n", $logEntries));
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            $logEntries[] = 'Failed to enable crypto';
            mailLog(implode("\n", $logEntries));
            return false;
        }
        $send('EHLO localhost');
        $line = trim(fgets($socket, 515));
        $logEntries[] = "S: $line";
        while ($line !== false && strlen($line) >= 4 && ($line[3] === '-' || $line[3] === ' ')) {
            if ($line[3] === '-') {
                $line = trim(fgets($socket, 515));
                $logEntries[] = "S: $line";
                continue;
            }
            break;
        }
    }

    $send('AUTH LOGIN');
    $response = trim(fgets($socket, 515));
    $logEntries[] = "S: $response";
    if (substr($response, 0, 3) !== '334') {
        fclose($socket);
        $logEntries[] = 'AUTH LOGIN not accepted';
        mailLog(implode("\n", $logEntries));
        return false;
    }

    $send(base64_encode(MAIL_SMTP_USER));
    $response = trim(fgets($socket, 515));
    $logEntries[] = "S: $response";
    if (substr($response, 0, 3) !== '334') {
        fclose($socket);
        $logEntries[] = 'SMTP did not request password';
        mailLog(implode("\n", $logEntries));
        return false;
    }

    $send(base64_encode(MAIL_SMTP_PASS));
    $response = trim(fgets($socket, 515));
    $logEntries[] = "S: $response";
    if (substr($response, 0, 3) !== '235') {
        fclose($socket);
        $logEntries[] = 'Authentication failed';
        mailLog(implode("\n", $logEntries));
        return false;
    }

    $send('MAIL FROM:<' . $fromEmail . '>');
    $response = trim(fgets($socket, 515));
    $logEntries[] = "S: $response";
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        $logEntries[] = 'MAIL FROM rejected';
        mailLog(implode("\n", $logEntries));
        return false;
    }

    $send('RCPT TO:<' . $to . '>');
    $response = trim(fgets($socket, 515));
    $logEntries[] = "S: $response";
    if (substr($response, 0, 3) !== '250' && substr($response, 0, 3) !== '251') {
        fclose($socket);
        $logEntries[] = 'RCPT TO rejected';
        mailLog(implode("\n", $logEntries));
        return false;
    }

    $send('DATA');
    $response = trim(fgets($socket, 515));
    $logEntries[] = "S: $response";
    if (substr($response, 0, 3) !== '354') {
        fclose($socket);
        $logEntries[] = 'DATA command rejected';
        mailLog(implode("\n", $logEntries));
        return false;
    }

    $messageContent = str_replace("\n.", "\n..", $body);
    $data = "Subject: $subjectHeader\r\n";
    $data .= "To: $to\r\n";
    $data .= $headers;
    $data .= "\r\n";
    $data .= $messageContent . "\r\n.\r\n";
    fwrite($socket, $data);

    $response = trim(fgets($socket, 515));
    $logEntries[] = "S: $response";
    $send('QUIT');
    $responseQuit = trim(fgets($socket, 515));
    $logEntries[] = "S: $responseQuit";
    fclose($socket);

    $success = substr($response, 0, 3) === '250';
    $logEntries[] = 'Result: ' . ($success ? 'queued/accepted' : 'failed');
    mailLog(implode("\n", $logEntries));
    return $success;
}

function sendNewTicketNotifications(PDO $db, int $ticketId): bool
{
    $ticket = getTicketById($db, $ticketId);
    if (!$ticket) {
        return false;
    }

    $userBody = '<div style="padding:16px;">'
        . '<h2 style="color:#205493;margin-bottom:16px;">Ticket recebido com sucesso!</h2>'
        . '<p>Olá <strong>' . htmlspecialchars($ticket['name'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
        . '<p>Registramos sua solicitação e nossa equipe já está analisando.</p>'
        . '<table style="width:100%;border-collapse:collapse;margin:20px 0;">'
        . '<tr><td style="padding:8px 0;font-weight:600;width:150px;color:#374151;">ID do ticket</td><td style="padding:8px 0;color:#111827;">#' . $ticket['id'] . '</td></tr>'
        . '<tr><td style="padding:8px 0;font-weight:600;color:#374151;">Assunto</td><td style="padding:8px 0;color:#111827;">' . htmlspecialchars($ticket['subject'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:8px 0;font-weight:600;color:#374151;">Responsável</td><td style="padding:8px 0;color:#111827;">' . htmlspecialchars($ticket['responsible'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:8px 0;font-weight:600;color:#374151;">Email</td><td style="padding:8px 0;color:#111827;">' . htmlspecialchars($ticket['email'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '</table>'
        . '<h3 style="color:#205493;margin:0 0 8px;">Detalhes da solicitação:</h3>'
        . '<div style="background:#f8fafc;padding:14px;border-radius:10px;color:#111827;line-height:1.7;white-space:pre-wrap;">'
        . nl2br(htmlspecialchars($ticket['message'], ENT_QUOTES, 'UTF-8')) . '</div>'
        . '<p style="margin-top:24px;color:#475569;">Obrigado por utilizar o Help Desk Arruda. Em breve entraremos em contato.</p>'
        . '</div>';

    $adminBody = '<div style="padding:16px;">'
        . '<h2 style="color:#1f2937;margin-bottom:16px;">Novo ticket recebido</h2>'
        . '<p>Um novo ticket foi aberto no sistema.</p>'
        . '<table style="width:100%;border-collapse:collapse;margin:20px 0;">'
        . '<tr><td style="padding:8px 0;font-weight:600;width:150px;color:#334155;">ID</td><td style="padding:8px 0;color:#0f172a;">#' . $ticket['id'] . '</td></tr>'
        . '<tr><td style="padding:8px 0;font-weight:600;color:#334155;">Solicitante</td><td style="padding:8px 0;color:#0f172a;">' . htmlspecialchars($ticket['name'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:8px 0;font-weight:600;color:#334155;">Email</td><td style="padding:8px 0;color:#0f172a;">' . htmlspecialchars($ticket['email'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:8px 0;font-weight:600;color:#334155;">Assunto</td><td style="padding:8px 0;color:#0f172a;">' . htmlspecialchars($ticket['subject'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '<tr><td style="padding:8px 0;font-weight:600;color:#334155;">Responsável</td><td style="padding:8px 0;color:#0f172a;">' . htmlspecialchars($ticket['responsible'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '</table>'
        . '<h3 style="margin:0 0 8px;color:#1f2937;">Mensagem do usuário</h3>'
        . '<div style="background:#f8fafc;padding:14px;border-radius:10px;color:#0f172a;line-height:1.7;white-space:pre-wrap;">'
        . nl2br(htmlspecialchars($ticket['message'], ENT_QUOTES, 'UTF-8')) . '</div>'
        . '<p style="margin-top:24px;color:#334155;">Acesse o painel administrativo para responder e acompanhar este ticket.</p>'
        . '</div>';

    $attachments = [];
    if (!empty($ticket['attachment'])) {
        $attachmentPath = UPLOAD_DIR . '/' . $ticket['attachment'];
        if (is_file($attachmentPath)) {
            $attachments[] = $attachmentPath;
            $userBody .= '<p><strong>Anexo enviado:</strong> ' . htmlspecialchars($ticket['attachment'], ENT_QUOTES, 'UTF-8') . '</p>';
            $adminBody .= '<p><strong>Anexo incluído:</strong> ' . htmlspecialchars($ticket['attachment'], ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }

    $sentUser = sendEmailSMTP($ticket['email'], 'Recebemos seu ticket: ' . $ticket['subject'], $userBody, MAIL_FROM_ADDRESS, MAIL_FROM_NAME, $attachments);
    $sentAdmin = sendEmailSMTP(MAIL_ADMIN_ADDRESS, 'Novo ticket recebido: #' . $ticket['id'], $adminBody, MAIL_FROM_ADDRESS, MAIL_FROM_NAME, $attachments);

    mailLog('sendNewTicketNotifications: ticket_id=' . $ticket['id'] . ' user_sent=' . ($sentUser ? '1' : '0') . ' admin_sent=' . ($sentAdmin ? '1' : '0'));

    return ($sentUser || $sentAdmin);
}

function sendTicketStatusChangeNotification(PDO $db, int $ticketId, string $status): bool
{
    $ticket = getTicketById($db, $ticketId);
    if (!$ticket) {
        return false;
    }

    $body = '<div style="padding:16px;">'
        . '<h2 style="color:#205493;margin-bottom:16px;">Atualização do seu ticket</h2>'
        . '<p>Olá <strong>' . htmlspecialchars($ticket['name'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
        . '<p>O status do seu ticket foi updated para: <strong>' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
        . '<table style="width:100%;border-collapse:collapse;margin:20px 0;">'
        . '<tr><td style="padding:8px 0;font-weight:600;width:150px;color:#334155;">ID</td><td style="padding:8px 0;color:#0f172a;">#' . $ticket['id'] . '</td></tr>'
        . '<tr><td style="padding:8px 0;font-weight:600;color:#334155;">Assunto</td><td style="padding:8px 0;color:#0f172a;">' . htmlspecialchars($ticket['subject'], ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '</table>'
        . '<h3 style="margin:0 0 8px;color:#1f2937;">Mensagem original</h3>'
        . '<div style="background:#f8fafc;padding:14px;border-radius:10px;color:#0f172a;line-height:1.7;white-space:pre-wrap;">'
        . nl2br(htmlspecialchars($ticket['message'], ENT_QUOTES, 'UTF-8')) . '</div>'
        . '<p style="margin-top:24px;color:#475569;">Se precisar, responda diretamente a este email ou abra um novo chamado.</p>'
        . '</div>';

    return sendEmailSMTP($ticket['email'], 'Atualização do seu ticket: ' . $ticket['subject'], $body);
}

function pop3Send($socket, string $command): void
{
    fwrite($socket, $command . "\r\n");
}

function pop3ReadLine($socket): string
{
    return trim(fgets($socket, 515));
}

function pop3ReadMultiline($socket): array
{
    $lines = [];
    while (($line = fgets($socket, 515)) !== false) {
        $line = rtrim($line, "\r\n");
        if ($line === '.') {
            break;
        }
        if (substr($line, 0, 2) === '..') {
            $line = substr($line, 1);
        }
        $lines[] = $line;
    }
    return $lines;
}

function decodeMimeHeader(string $text): string
{
    return preg_replace_callback('/=\?([^?]+)\?([BQbq])\?([^?]+)\?=/i', function ($matches) {
        $charset = $matches[1];
        $encoding = strtoupper($matches[2]);
        $data = $matches[3];
        if ($encoding === 'B') {
            $decoded = base64_decode($data);
        } else {
            $decoded = quoted_printable_decode(str_replace('_', ' ', $data));
        }
        return $decoded !== false ? $decoded : $matches[0];
    }, $text);
}

function parseEmailHeaders(string $raw): array
{
    $result = [];
    $raw = str_replace("\r\n", "\n", $raw);
    $parts = preg_split('/\n\n/', $raw, 2);
    $headerText = $parts[0] ?? '';
    $headerText = preg_replace('/\n[ \t]+/', ' ', $headerText);
    $lines = preg_split('/\n/', $headerText);
    foreach ($lines as $line) {
        if (strpos($line, ':') === false) {
            continue;
        }
        [$name, $value] = explode(':', $line, 2);
        $result[strtolower(trim($name))] = trim($value);
    }
    return $result;
}

function extractEmailBody(string $raw, array $headers): string
{
    $raw = str_replace("\r\n", "\n", $raw);
    $parts = preg_split('/\n\n/', $raw, 2);
    $body = $parts[1] ?? '';
    
    $textBody = '';
    $htmlBody = '';

    // Verifica se é um e-mail multipart (com divisões/anexos)
    if (preg_match_all('/boundary="?([^"\n;]+)"?/i', $headers['content-type'] ?? '', $matches)) {
        $boundaries = $matches[1] ?? [];
        foreach ($boundaries as $boundary) {
            if (empty($boundary)) continue;
            $pattern = '/--' . preg_quote($boundary, '/') . '(?:\n|--)/';
            $sections = preg_split($pattern, $body);
            
            foreach ($sections as $section) {
                $section = trim($section);
                if (empty($section)) continue;
                
                $sectionHeaders = '';
                $sectionBody = $section;
                
                if (preg_match('/^(.*?)\n\n(.*)$/s', $section, $matches)) {
                    if (stripos($matches[1], 'Content-Type:') !== false) {
                        $sectionHeaders = $matches[1];
                        $sectionBody = $matches[2];
                    }
                }
                
                $subCharset = 'UTF-8';
                if (preg_match('/charset=["\']?([^"\n\s;]+)["\']?/i', $sectionHeaders, $charsetMatch)) {
                    $subCharset = strtoupper(trim($charsetMatch[1]));
                }

                if (stripos($sectionHeaders, 'Content-Transfer-Encoding: base64') !== false) {
                    $sectionBody = base64_decode(trim($sectionBody));
                } elseif (stripos($sectionHeaders, 'Content-Transfer-Encoding: quoted-printable') !== false) {
                    $sectionBody = quoted_printable_decode($sectionBody);
                }

                if ($subCharset !== 'UTF-8' && $subCharset !== 'US-ASCII' && function_exists('mb_convert_encoding')) {
                    $sectionBody = @mb_convert_encoding($sectionBody, 'UTF-8', $subCharset);
                }

                // Captura texto plano ou html separadamente
                if (stripos($sectionHeaders, 'Content-Type: text/plain') !== false) {
                    $textBody .= $sectionBody . "\n";
                } elseif (stripos($sectionHeaders, 'Content-Type: text/html') !== false) {
                    $htmlBody .= $sectionBody . "\n";
                }
            }
        }
    }

    // Define qual corpo usar (prioriza o texto plano, se não houver, limpa o HTML)
    $finalBody = !empty(trim($textBody)) ? trim($textBody) : trim($htmlBody);

    if (empty($finalBody)) {
        $finalBody = trim($body); // Fallback caso não ache boundaries
    }

    // Se houver tags HTML, limpa para exibir apenas o texto legível da mensagem
    if (preg_match('/<(html|body|div|p|br|style|script)/i', $finalBody)) {
        $finalBody = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/i', '', $finalBody);
        $finalBody = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $finalBody);
        $finalBody = strip_tags($finalBody);
    }

    return trim($finalBody);
}

function extractEmailAttachments(string $raw, array $headers): array
{
    $attachments = [];
    
    if (empty($headers['content-type']) || !preg_match('/multipart\/[^"]+;\s*boundary="?([^"\n;]+)"?/i', $headers['content-type'], $match)) {
        return $attachments;
    }
    
    $raw = str_replace("\r\n", "\n", $raw);
    $parts = preg_split("/\n\n/", $raw, 2);
    $body = $parts[1] ?? '';
    
    $boundary = $match[1];
    $pattern = '/--' . preg_quote($boundary, '/') . '(?:\n|--)/';
    $sections = preg_split($pattern, $body);
    
    foreach ($sections as $section) {
        $section = trim($section);
        if (empty($section)) continue;
        
        if (!preg_match('/^(.*?)\n\n(.*)$/s', $section, $matches)) {
            continue;
        }
        
        $sectionHeaders = strtolower($matches[1]);
        $sectionBody = $matches[2];
        
        if (stripos($sectionHeaders, 'content-type: text/') !== false) {
            continue;
        }
        
        if (!preg_match('/content-type:\s*([^;]+)/i', $sectionHeaders, $ctMatch)) {
            continue;
        }
        
        $contentType = trim($ctMatch[1]);
        
        $filename = null;
        if (preg_match('/filename\s*=\s*"?([^";\n]+)"?/i', $sectionHeaders, $fnMatch)) {
            $filename = trim($fnMatch[1], '"');
        } elseif (preg_match('/name\s*=\s*"?([^";\n]+)"?/i', $sectionHeaders, $fnMatch)) {
            $filename = trim($fnMatch[1], '"');
        }
        
        if (!$filename && preg_match('/content-id:\s*<([^>]+)>/i', $sectionHeaders, $cidMatch)) {
            $cid = trim($cidMatch[1]);
            if (preg_match('/image\/([a-z0-9]+)/i', $contentType, $extMatch)) {
                $ext = strtolower($extMatch[1]);
                $filename = 'signature_' . preg_replace('/[^a-z0-9]/i', '', $cid) . '.' . $ext;
            }
        }
        
        if (!$filename) continue;
        
        $fileContent = $sectionBody;
        if (stripos($sectionHeaders, 'content-transfer-encoding: base64') !== false) {
            $fileContent = base64_decode($fileContent, true);
            if ($fileContent === false) continue;
        } elseif (stripos($sectionHeaders, 'content-transfer-encoding: quoted-printable') !== false) {
            $fileContent = quoted_printable_decode($fileContent);
        }
        
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($filename));
        
        $uploadDir = UPLOAD_DIR;
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $newFilename = $baseName . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $filepath = $uploadDir . '/' . $newFilename;
        
        if (file_put_contents($filepath, $fileContent) !== false) {
            $attachments[] = $newFilename;
            mailLog("Attachment saved: $newFilename (from email, type: $contentType)");
        }
    }
    
    return $attachments;
}

function parseRawEmail(string $raw): array
{
    $headers = parseEmailHeaders($raw);
    $subject = $headers['subject'] ?? 'Sem assunto';
    $from = $headers['from'] ?? MAIL_ADMIN_ADDRESS;
    if (preg_match('/"?([^"<]+)"?\s*<([^>]+)>/', $from, $matches)) {
        $name = trim($matches[1]);
        $email = trim($matches[2]);
    } else {
        $name = preg_replace('/@.*$/', '', $from);
        $email = trim($from);
    }
    $subject = decodeMimeHeader($subject);
    $body = extractEmailBody($raw, $headers);
    $attachments = extractEmailAttachments($raw, $headers);
    
    return [
        'name' => $name ?: 'Solicitante',
        'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : MAIL_ADMIN_ADDRESS,
        'subject' => $subject,
        'body' => trim($body),
        'headers' => $headers,
        'attachments' => $attachments,
    ];
}

function importPop3Emails(PDO $db): array
{
    $remote = (MAIL_POP3_SECURE === 'ssl' ? 'ssl://' : 'tcp://') . MAIL_POP3_HOST . ':' . MAIL_POP3_PORT;
    $socket = @stream_socket_client($remote, $errno, $errstr, 30);
    if (!$socket) {
        return ['imported' => [], 'error' => "Falha ao conectar ao servidor POP3: $errstr ($errno)", 'warnings' => []];
    }

    stream_set_timeout($socket, 30);
    $greeting = pop3ReadLine($socket);
    if (substr($greeting, 0, 3) !== '+OK') {
        fclose($socket);
        return ['imported' => [], 'error' => 'Servidor POP3 não respondeu corretamente.', 'warnings' => []];
    }

    pop3Send($socket, 'USER ' . MAIL_POP3_USER);
    if (substr(pop3ReadLine($socket), 0, 3) !== '+OK') {
        fclose($socket);
        return ['imported' => [], 'error' => 'Erro no login POP3 (usuário).', 'warnings' => []];
    }

    pop3Send($socket, 'PASS ' . MAIL_POP3_PASS);
    if (substr(pop3ReadLine($socket), 0, 3) !== '+OK') {
        fclose($socket);
        return ['imported' => [], 'error' => 'Erro no login POP3 (senha).', 'warnings' => []];
    }

    pop3Send($socket, 'STAT');
    $stat = pop3ReadLine($socket);
    if (substr($stat, 0, 3) !== '+OK') {
        fclose($socket);
        return ['imported' => [], 'error' => 'Não foi possível obter o status da caixa de entrada.', 'warnings' => []];
    }

    $parts = explode(' ', $stat);
    $count = isset($parts[1]) ? (int) $parts[1] : 0;
    if ($count === 0) {
        pop3Send($socket, 'QUIT');
        pop3ReadLine($socket);
        fclose($socket);
        return ['imported' => [], 'error' => '', 'warnings' => ['Nenhum email novo encontrado.']];
    }

    pop3Send($socket, 'LIST');
    $listResponse = pop3ReadLine($socket);
    if (substr($listResponse, 0, 3) !== '+OK') {
        fclose($socket);
        return ['imported' => [], 'error' => 'Não foi possível listar mensagens POP3.', 'warnings' => []];
    }

    $listLines = pop3ReadMultiline($socket);
    $messageNumbers = [];
    foreach ($listLines as $line) {
        $parts = preg_split('/\s+/', $line);
        if (count($parts) >= 2) {
            $messageNumbers[] = (int) $parts[0];
        }
    }

    $imported = [];
    $warnings = [];
    foreach ($messageNumbers as $messageNumber) {
        pop3Send($socket, 'RETR ' . $messageNumber);
        $retrResponse = pop3ReadLine($socket);
        if (substr($retrResponse, 0, 3) !== '+OK') {
            $warnings[] = "Falha ao ler mensagem #$messageNumber";
            continue;
        }

        $emailLines = pop3ReadMultiline($socket);
        $rawEmail = implode("\r\n", $emailLines);
        $parsed = parseRawEmail($rawEmail);

        $fromLower = strtolower($parsed['email'] ?? '');
        if ($fromLower === strtolower(MAIL_POP3_USER) || $fromLower === strtolower(MAIL_FROM_ADDRESS) || $fromLower === strtolower(MAIL_ADMIN_ADDRESS)) {
            $warnings[] = "Ignorado: mensagem #$messageNumber vinda do próprio sistema ({$parsed['email']})";
            pop3Send($socket, 'DELE ' . $messageNumber);
            pop3ReadLine($socket);
            continue;
        }

        if (!empty($parsed['headers']['x-helpdesk'])) {
            $warnings[] = "Ignorado: mensagem #$messageNumber marcada como originada pelo sistema.";
            pop3Send($socket, 'DELE ' . $messageNumber);
            pop3ReadLine($socket);
            continue;
        }

        $subjectLower = strtolower($parsed['subject'] ?? '');
        $skipPhrases = ['recebemos seu ticket', 'novo ticket recebido', 'ticket recebido com sucesso', 'cópia: resposta no ticket', 'resposta ao seu ticket'];
        foreach ($skipPhrases as $phrase) {
            if ($phrase !== '' && strpos($subjectLower, $phrase) !== false) {
                $warnings[] = "Ignorado: mensagem #$messageNumber com assunto de notificação ({$parsed['subject']}).";
                pop3Send($socket, 'DELE ' . $messageNumber);
                pop3ReadLine($socket);
                continue 2;
            }
        }

        $stmt = $db->prepare('INSERT INTO tickets (name, email, subject, message, attachment) VALUES (?, ?, ?, ?, ?)');
        $attachment = !empty($parsed['attachments']) ? $parsed['attachments'][0] : null;
        if ($stmt->execute([$parsed['name'], $parsed['email'], $parsed['subject'], $parsed['body'], $attachment])) {
            $ticketId = (int) $db->lastInsertId();
            $imported[] = ['number' => $messageNumber, 'subject' => $parsed['subject'], 'email' => $parsed['email'], 'ticket_id' => $ticketId];
            sendNewTicketNotifications($db, $ticketId);
            pop3Send($socket, 'DELE ' . $messageNumber);
            pop3ReadLine($socket);
        } else {
            $warnings[] = "Falha ao salvar ticket da mensagem #$messageNumber";
        }
    }

    pop3Send($socket, 'QUIT');
    pop3ReadLine($socket);
    fclose($socket);

    return ['imported' => $imported, 'error' => '', 'warnings' => $warnings];
}