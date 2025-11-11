<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

function mail_config(): array {
  $path = __DIR__ . '/mail.php';
  if (!is_readable($path)) {
    throw new RuntimeException('Mail-Konfiguration fehlt: ' . $path);
  }
  /** @noinspection PhpIncludeInspection */
  return require $path;
}

function send_verification_email(string $to, string $code): bool {
  $cfg = mail_config();
  $mail = new PHPMailer(true);

  try {
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->SMTPDebug = (int)($cfg['debug'] ?? 0);

    // SMTP aktivieren
    $mail->isSMTP();
    $mail->Host = $cfg['host'];
    $mail->Port = $cfg['port'] ?? 587;
    $mail->SMTPAuth = true;
    $mail->Username = $cfg['username'];
    $mail->Password = $cfg['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    // Absender & Reply-To
    $from = $cfg['from'] ?? ['address'=>$cfg['username'],'name'=>''];
    $mail->setFrom($from['address'], $from['name']);
    if (!empty($cfg['reply_to']['address'])) {
      $mail->addReplyTo($cfg['reply_to']['address'], $cfg['reply_to']['name'] ?? '');
    }

    // Empfänger & Inhalt
    $mail->addAddress($to);
    $mail->Subject = 'Ihr Bestätigungscode';
    $plain = "Guten Tag,\n\nIhr Bestätigungscode lautet: $code\n\nBitte geben Sie diesen Code auf der Website ein, um Ihre E-Mail-Adresse zu verifizieren.\n\nMit freundlichen Grüßen\nBBS Oldenburg";
    $mail->Body = nl2br($plain);
    $mail->AltBody = $plain;
    $mail->isHTML(true);

    return $mail->send();
  } catch (Throwable $e) {
    error_log('Mail error: '.$e->getMessage());
    return false;
  }
}
