<?php
require_once __DIR__ . '/config.php';

// Permite passar ?to=user@exemplo.com para testar envio a qualquer endereço
$to = trim($_GET['to'] ?? 'help-desk@arrudaempresarial.com.br');
$subject = 'Teste SMTP help desk';
$body = 'Teste de envio SMTP no help desk.';

echo "Enviando para: " . htmlspecialchars($to) . "\n";
$result = sendEmailSMTP($to, $subject, $body);
var_dump($result);
if (!$result) {
	echo "Envio falhou. Verifique logs, SPAM e configurações SMTP.\n";
} else {
	echo "Envio OK. Verifique a caixa de entrada/Spam do destinatário.\n";
}
?>
