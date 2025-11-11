<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/email.php';

$to = $argv[1] ?? null;
if (!$to) { fwrite(STDERR, "Usage: php test_mail.php recipient@example.org\n"); exit(1); }

$code = str_pad((string)random_int(0,999999), 6, '0', STR_PAD_LEFT);
$ok = send_verification_email($to, $code);
echo $ok ? "OK: Mail an $to gesendet (Code: $code)\n" : "FAIL: Versand fehlgeschlagen\n";
