<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Lädt SMTP-Konfiguration aus app/mail.php
 * Erwartet z.B.:
 * return [
 *   'host' => '...',
 *   'port' => 587,
 *   'username' => '...',
 *   'password' => '...',
 *   'from' => ['address'=>'...', 'name'=>'...'],
 *   'reply_to' => ['address'=>'...', 'name'=>'...'],
 *   'debug' => 0,
 * ];
 */
function mail_config(): array {
    $path = __DIR__ . '/mail.php';
    if (!is_readable($path)) {
        throw new RuntimeException('Mail-Konfiguration fehlt: ' . $path);
    }
    /** @noinspection PhpIncludeInspection */
    $cfg = require $path;
    if (!is_array($cfg)) {
        throw new RuntimeException('Mail-Konfiguration ungültig (kein Array): ' . $path);
    }
    return $cfg;
}

/**
 * Zentrale Factory für PHPMailer anhand config
 */
function make_mailer(array $cfg): PHPMailer {
    $mail = new PHPMailer(true);

    $mail->CharSet  = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->SMTPDebug = (int)($cfg['debug'] ?? 0);

    // SMTP aktivieren
    $mail->isSMTP();
    $mail->Host       = (string)($cfg['host'] ?? '');
    $mail->Port       = (int)($cfg['port'] ?? 587);
    $mail->SMTPAuth   = true;
    $mail->Username   = (string)($cfg['username'] ?? '');
    $mail->Password   = (string)($cfg['password'] ?? '');

    // STARTTLS als Standard
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    // Absender
    $from = $cfg['from'] ?? ['address' => $mail->Username, 'name' => ''];
    $fromAddr = (string)($from['address'] ?? $mail->Username);
    $fromName = (string)($from['name'] ?? '');
    $mail->setFrom($fromAddr, $fromName);

    // Reply-To optional
    if (!empty($cfg['reply_to']['address'])) {
        $mail->addReplyTo(
            (string)$cfg['reply_to']['address'],
            (string)($cfg['reply_to']['name'] ?? '')
        );
    }

    // HTML Mails
    $mail->isHTML(true);

    return $mail;
}

/**
 * Sendet Bestätigungscode (Register/Verify)
 * $lang optional, damit dein access_create.php damit arbeiten kann.
 */
function send_verification_email(string $to, string $code, string $lang = 'de'): bool {
    $cfg  = mail_config();
    $mail = make_mailer($cfg);

    $lang = strtolower($lang);
    $subject = ($lang === 'de') ? 'Ihr Bestätigungscode' : 'Your verification code';

    $plain = ($lang === 'de')
        ? "Guten Tag,\n\nIhr Bestätigungscode lautet: {$code}\n\nBitte geben Sie diesen Code auf der Website ein, um Ihre E-Mail-Adresse zu verifizieren.\n\nMit freundlichen Grüßen\nBBS Oldenburg"
        : "Hello,\n\nYour verification code is: {$code}\n\nPlease enter this code on the website to verify your email address.\n\nBest regards\nBBS Oldenburg";

    try {
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($plain);
        $mail->AltBody = $plain;

        return $mail->send();
    } catch (Throwable $e) {
        error_log('Mail error (verification): ' . $e->getMessage());
        return false;
    }
}

/**
 * Sendet initiales Passwort nach erfolgreicher E-Mail-Verifizierung
 */
function send_account_password_email(string $to, string $password, string $lang = 'de'): bool {
    $cfg  = mail_config();
    $mail = make_mailer($cfg);

    $lang = strtolower($lang);

    $subject = ($lang === 'de')
        ? 'Ihr Passwort für die Online-Anmeldung'
        : 'Your password for the online registration';

    $plain = ($lang === 'de')
        ? "Guten Tag,\n\nIhr Zugang wurde erstellt.\n\nE-Mail: {$to}\nPasswort: {$password}\n\nBitte bewahren Sie dieses Passwort sicher auf.\n\nMit freundlichen Grüßen\nBBS Oldenburg"
        : "Hello,\n\nYour account has been created.\n\nEmail: {$to}\nPassword: {$password}\n\nPlease keep this password safe.\n\nBest regards\nBBS Oldenburg";

    try {
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($plain);
        $mail->AltBody = $plain;

        return $mail->send();
    } catch (Throwable $e) {
        error_log('Mail error (password): ' . $e->getMessage());
        return false;
    }
}
