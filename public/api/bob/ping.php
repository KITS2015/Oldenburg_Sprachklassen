<?php
declare(strict_types=1);

// Datei: public/api/bob/ping.php

require_once __DIR__ . '/../../app/config.php';

// Mini-DB connect (wie im Admin)
$dsn = 'mysql:host=127.0.0.1;dbname=' . APP_DB_NAME . ';port=3306;charset=utf8mb4';
$pdo = pdo($dsn, APP_DB_USER, APP_DB_PASS);

function json_out(int $code, array $data): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bearer_token(): string {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($hdr === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $hdr = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', $hdr, $m)) {
        return trim($m[1]);
    }
    return '';
}

$token = bearer_token();
if ($token === '') {
    json_out(401, ['ok' => false, 'error' => 'missing_bearer_token']);
}

$hash = hash('sha256', $token);

$st = $pdo->prepare("
    SELECT bbs_id, bbs_schulnummer, bbs_bezeichnung
    FROM bbs
    WHERE is_active=1 AND rest_token_hash=?
    LIMIT 1
");
$st->execute([$hash]);
$bbs = $st->fetch();

if (!$bbs) {
    json_out(401, ['ok' => false, 'error' => 'invalid_token']);
}

json_out(200, [
    'ok' => true,
    'bbs' => [
        'bbs_id' => (int)$bbs['bbs_id'],
        'schulnummer' => (string)$bbs['bbs_schulnummer'],
        'bezeichnung' => (string)$bbs['bbs_bezeichnung'],
    ],
    'ts' => date('c'),
]);
