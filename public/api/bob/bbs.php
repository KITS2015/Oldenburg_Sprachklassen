<?php
declare(strict_types=1);

// Datei: public/api/bob/bbs.php

require_once __DIR__ . '/../../../app/config.php';

date_default_timezone_set('Europe/Berlin');

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

function require_bob(PDO $pdo): array {
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
    $bbs = $st->fetch(PDO::FETCH_ASSOC);
    if (!$bbs) {
        json_out(401, ['ok' => false, 'error' => 'invalid_token']);
    }
    return $bbs;
}

require_bob($pdo);

try {
    $rows = $pdo->query("
        SELECT bbs_id, bbs_schulnummer, bbs_kurz, bbs_bezeichnung
        FROM bbs
        WHERE is_active=1
        ORDER BY bbs_bezeichnung ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Fallback falls bbs_kurz fehlt
    $rows = $pdo->query("
        SELECT bbs_id, bbs_schulnummer, bbs_bezeichnung
        FROM bbs
        WHERE is_active=1
        ORDER BY bbs_bezeichnung ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

$out = [];
foreach ($rows as $r) {
    $id = (int)$r['bbs_id'];
    $label = '';
    if (isset($r['bbs_kurz'])) $label = trim((string)$r['bbs_kurz']);
    if ($label === '') $label = trim((string)($r['bbs_schulnummer'] ?? ''));
    if ($label === '') $label = (string)($r['bbs_bezeichnung'] ?? '');

    $out[] = [
        'bbs_id' => $id,
        'label' => $label,
        'bezeichnung' => (string)($r['bbs_bezeichnung'] ?? ''),
    ];
}

json_out(200, ['ok' => true, 'data' => $out]);
