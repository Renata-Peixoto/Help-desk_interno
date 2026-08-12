<?php
require_once __DIR__ . '/config.php';
function debugSendEmailSMTP(string $to, string $subject, string $message) {
    $fromEmail = MAIL_FROM_ADDRESS;
    $fromName = MAIL_FROM_NAME;
    $fromHeader = '=?UTF-8?B?' . base64_encode($fromName) . '?=' . ' <' . $fromEmail . '>';
    $subjectHeader = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = "From: $fromHeader\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body = '<html><body>' . $message . '</body></html>';
    $remote = (MAIL_SMTP_SECURE === 'ssl' ? 'ssl://' : 'tcp://') . MAIL_SMTP_HOST . ':' . MAIL_SMTP_PORT;
    echo "Connecting to $remote\n";
    $socket = @stream_socket_client($remote, $errno, $errstr, 30);
    if (!$socket) {
        echo "socket failed: $errno - $errstr\n";
        return false;
    }
    stream_set_timeout($socket, 30);
    $response = trim(fgets($socket, 515));
    echo "response 1: $response\n";
    if (substr($response, 0, 3) !== '220') {
        fclose($socket);
        return false;
    }
    $send = function (string $command) use ($socket) {
        echo ">> $command\n";
        fwrite($socket, $command . "\r\n");
    };
    $read = function (): string {
        global $socket;
        $line = trim(fgets($socket, 515));
        echo "<< $line\n";
        return $line;
    };
    $send('EHLO localhost');
    $line = trim(fgets($socket, 515)); echo "<< $line\n";
    while ($line !== false && (strlen($line) === 0 || $line[3] === '-')) {
        $line = trim(fgets($socket, 515)); echo "<< $line\n";
    }
    if (MAIL_SMTP_SECURE === 'tls') {
        $send('STARTTLS');
        $line = trim(fgets($socket, 515)); echo "<< $line\n";
        if (substr($line, 0, 3) !== '220') {
            fclose($socket);
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            echo "failed to enable crypto\n";
            fclose($socket);
            return false;
        }
        $send('EHLO localhost');
        $line = trim(fgets($socket, 515)); echo "<< $line\n";
        while ($line !== false && (strlen($line) === 0 || $line[3] === '-')) {
            $line = trim(fgets($socket, 515)); echo "<< $line\n";
        }
    }
    $send('AUTH LOGIN');
    $line = trim(fgets($socket, 515)); echo "<< $line\n";
    $send(base64_encode(MAIL_SMTP_USER));
    $line = trim(fgets($socket, 515)); echo "<< $line\n";
    $send(base64_encode(MAIL_SMTP_PASS));
    $line = trim(fgets($socket, 515)); echo "<< $line\n";
    $send('MAIL FROM:<' . $fromEmail . '>');
    $line = trim(fgets($socket, 515)); echo "<< $line\n";
    $send('RCPT TO:<' . $to . '>');
    $line = trim(fgets($socket, 515)); echo "<< $line\n";
    $send('DATA');
    $line = trim(fgets($socket, 515)); echo "<< $line\n";
    $content = "Subject: $subjectHeader\r\n";
    $content .= "From: $fromHeader\r\n";
    $content .= "To: $to\r\n";
    $content .= $headers;
    $content .= "\r\n";
    $content .= $body . "\r\n.\r\n";
    fwrite($socket, $content);
    $line = trim(fgets($socket, 515)); echo "<< $line\n";
    $send('QUIT');
    $line = trim(fgets($socket, 515)); echo "<< $line\n";
    fclose($socket);
    return substr($line, 0, 3) === '221';
}

$success = debugSendEmailSMTP('help-desk@arrudaempresarial.com.br', 'Teste SMTP help desk', 'Teste de envio SMTP no help desk.');
var_dump($success);
?>
