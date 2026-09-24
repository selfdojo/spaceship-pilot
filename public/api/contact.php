<?php
// Test endpoint for validating that Spaceship's Spacemail can receive mail
// sent by a PHP script on the same account. Spacemail turned out to be a
// separate mail backend (mail.spacemail.com) rather than something PHP's
// built-in mail() can reach locally, so this sends via real SMTP with
// authentication instead, using nothing beyond PHP's own socket functions
// (no Composer/PHPMailer needed).
//
// Fill in the two values below from Spacemail's IMAP/SMTP/POP3 panel and
// the mailbox's own password before testing. Do not commit real
// credentials to version control — this file is meant to be hand-edited
// directly on the server for this pilot, not pushed with secrets filled in.
$smtp_host = 'mail.spacemail.com';
$smtp_port = 465; // SSL
$smtp_user = 'info@garrymconsulting.com';
$smtp_pass = 'SET_ME'; // <-- fill in the mailbox password here, on the server only
$recipient = 'info@garrymconsulting.com';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'reason' => 'method-not-allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$name = isset($body['name']) ? trim($body['name']) : '';
$email = isset($body['email']) ? trim($body['email']) : '';
$message = isset($body['message']) ? trim($body['message']) : '';

if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'reason' => 'invalid-input']);
    exit;
}

function smtp_expect($sock, $want_code)
{
    $line = fgets($sock, 515);
    $code = (int) substr($line, 0, 3);
    // Multi-line SMTP replies use "code-" until the final "code ".
    while (isset($line[3]) && $line[3] === '-') {
        $line = fgets($sock, 515);
    }
    if ($code !== $want_code) {
        throw new Exception("SMTP: expected $want_code, got: " . trim($line));
    }
    return $line;
}

function smtp_send($sock, $line)
{
    fwrite($sock, $line . "\r\n");
}

try {
    $sock = stream_socket_client(
        "ssl://$smtp_host:$smtp_port",
        $errno,
        $errstr,
        15,
        STREAM_CLIENT_CONNECT
    );
    if (!$sock) {
        throw new Exception("Connect failed: $errstr ($errno)");
    }

    smtp_expect($sock, 220);
    smtp_send($sock, 'EHLO garrymconsulting.com');
    smtp_expect($sock, 250);
    smtp_send($sock, 'AUTH LOGIN');
    smtp_expect($sock, 334);
    smtp_send($sock, base64_encode($smtp_user));
    smtp_expect($sock, 334);
    smtp_send($sock, base64_encode($smtp_pass));
    smtp_expect($sock, 235);

    smtp_send($sock, "MAIL FROM:<$smtp_user>");
    smtp_expect($sock, 250);
    smtp_send($sock, "RCPT TO:<$recipient>");
    smtp_expect($sock, 250);
    smtp_send($sock, 'DATA');
    smtp_expect($sock, 354);

    $date = date('r');
    $data = "From: Spaceship Pilot <$smtp_user>\r\n"
        . "To: <$recipient>\r\n"
        . "Reply-To: $email\r\n"
        . "Subject: Spaceship pilot: new test inquiry\r\n"
        . "Date: $date\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "\r\n"
        . "Name: $name\r\nEmail: $email\r\n\r\n$message\r\n";
    // Dot-stuff any line that starts with a lone '.' per RFC 5321.
    $data = preg_replace('/^\./m', '..', $data);
    smtp_send($sock, $data . "\r\n.");
    smtp_expect($sock, 250);

    smtp_send($sock, 'QUIT');
    fclose($sock);

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'reason' => 'smtp-error', 'detail' => $e->getMessage()]);
}
