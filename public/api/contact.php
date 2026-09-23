<?php
// Minimal test endpoint for validating that Spaceship's shared hosting can
// send mail server-side via PHP's mail() (backed by Spacemail once DNS/MX
// is configured for the domain). Only works if the host executes PHP for
// this path — if Spaceship serves this app through a Node-only pipeline
// (e.g. Hyperlift), this file may not run at all, which is itself one of
// the things this pilot is meant to find out.
//
// Set this to whichever mailbox you want test submissions to land in.
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

$subject = 'Spaceship pilot: new test inquiry';
$body_text = "Name: $name\nEmail: $email\n\n$message";
$headers = "From: no-reply@" . $_SERVER['SERVER_NAME'] . "\r\nReply-To: $email";

$sent = mail($recipient, $subject, $body_text, $headers);

if ($sent) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'reason' => 'mail-failed']);
}
