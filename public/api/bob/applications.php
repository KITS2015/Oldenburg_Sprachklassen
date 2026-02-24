<?php
declare(strict_types=1);

// Datei: public/api/bob/applications.php

require_once __DIR__ . '/../../../app/config.php';

date_default_timezone_set('Europe/Berlin');

// --- CORS (BoB läuft auf anderem Host) ---
$origin = isset($_SERVER['HTTP_ORIGIN']) ? (string)$_SERVER['HTTP_ORIGIN'] : '';
$allowOrigins = array(
    'https://silbobdev.svs.schule',
    // ggf. weitere erlaubte BoB-Hosts:
    // 'https://silbob.svs.schule',
);

if ($origin !== '' && in_array($origin, $allowOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
}

// Preflight muss ohne Auth funktionieren
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// DB
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
    $bbs = $st->fetch();
    if (!$bbs) {
        json_out(401, ['ok' => false, 'error' => 'invalid_token']);
    }
    return $bbs;
}

$bbs = require_bob($pdo);

// --- Detailmodus: /api/bob/applications.php?id=123 ---
$appId = (int)($_GET['id'] ?? 0);
if ($appId > 0) {

    // 1 Datensatz (wie Liste, aber WHERE a.id=?)
    $st = $pdo->prepare("
        SELECT
            a.id,
            a.token,
            a.status,
            a.created_at,
            a.updated_at,

            a.assigned_bbs_id,
            a.locked_by_bbs_id,
            a.locked_at,

            p.name, p.vorname, p.geschlecht, p.geburtsdatum, p.geburtsort_land, p.staatsang,
            p.strasse, p.plz, p.wohnort, p.telefon, p.email, p.weitere_angaben, p.dsgvo_ok,

            s.schule_aktuell, s.schule_freitext, s.schule_label, s.klassenlehrer, s.mail_lehrkraft,
            s.seit_monat, s.seit_jahr, s.seit_text, s.jahre_in_de, s.schule_herkunft, s.jahre_schule_herkunft,
            s.familiensprache, s.deutsch_niveau, s.interessen

        FROM applications a
        LEFT JOIN personal p ON p.application_id = a.id
        LEFT JOIN school   s ON s.application_id = a.id
        WHERE a.id = ?
        LIMIT 1
    ");
    $st->execute([$appId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        json_out(404, ['ok' => false, 'error' => 'not_found']);
    }

    // contacts
    $contacts = [];
    $stC = $pdo->prepare("
        SELECT rolle, name, tel, mail, notiz
        FROM contacts
        WHERE application_id = ?
        ORDER BY id ASC
    ");
    $stC->execute([$appId]);
    while ($c = $stC->fetch(PDO::FETCH_ASSOC)) {
        $contacts[] = [
            'rolle' => (string)($c['rolle'] ?? ''),
            'name'  => (string)($c['name'] ?? ''),
            'tel'   => (string)($c['tel'] ?? ''),
            'mail'  => (string)($c['mail'] ?? ''),
            'notiz' => (string)($c['notiz'] ?? ''),
        ];
    }
    $row['contacts'] = $contacts;

    // uploads (Metadaten)
    $uploads = [];
    $stU = $pdo->prepare("
        SELECT typ, filename, mime, size_bytes, uploaded_at
        FROM uploads
        WHERE application_id = ?
        ORDER BY id ASC
    ");
    $stU->execute([$appId]);
    while ($u = $stU->fetch(PDO::FETCH_ASSOC)) {
        $uploads[] = [
            'typ'         => (string)($u['typ'] ?? ''),
            'filename'    => (string)($u['filename'] ?? ''),
            'mime'        => (string)($u['mime'] ?? ''),
            'size_bytes'  => (int)($u['size_bytes'] ?? 0),
            'uploaded_at' => (string)($u['uploaded_at'] ?? ''),
        ];
    }
    $row['uploads'] = $uploads;

    json_out(200, [
        'ok' => true,
        'bbs' => [
            'bbs_id' => (int)$bbs['bbs_id'],
            'schulnummer' => (string)$bbs['bbs_schulnummer'],
            'bezeichnung' => (string)$bbs['bbs_bezeichnung'],
        ],
        'data' => $row,
    ]);
}

// --- Paging ---
$limit = (int)($_GET['limit'] ?? 200);
if ($limit < 1) $limit = 1;
if ($limit > 500) $limit = 500;

$offset = (int)($_GET['offset'] ?? 0);
if ($offset < 0) $offset = 0;

// Optionaler Statusfilter (z.B. submitted)
$status = (string)($_GET['status'] ?? '');
$allowedStatus = ['', 'draft', 'submitted', 'withdrawn'];
if (!in_array($status, $allowedStatus, true)) {
    $status = '';
}

$where = [];
$params = [];

if ($status !== '') {
    $where[] = 'a.status = ?';
    $params[] = $status;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// total
$stCount = $pdo->prepare("
    SELECT COUNT(*)
    FROM applications a
    $whereSql
");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();

// data (ohne uploads)
$st = $pdo->prepare("
    SELECT
        a.id,
        a.token,
        a.status,
        a.created_at,
        a.updated_at,

        -- Zuweisung / Lock (für BoB-Portal UI)
        a.assigned_bbs_id,
        a.locked_by_bbs_id,
        a.locked_at,

        p.name, p.vorname, p.geschlecht, p.geburtsdatum, p.geburtsort_land, p.staatsang,
        p.strasse, p.plz, p.wohnort, p.telefon, p.email, p.weitere_angaben, p.dsgvo_ok,

        s.schule_aktuell, s.schule_freitext, s.schule_label, s.klassenlehrer, s.mail_lehrkraft,
        s.seit_monat, s.seit_jahr, s.seit_text, s.jahre_in_de, s.schule_herkunft, s.jahre_schule_herkunft,
        s.familiensprache, s.deutsch_niveau, s.interessen

    FROM applications a
    LEFT JOIN personal p ON p.application_id = a.id
    LEFT JOIN school   s ON s.application_id = a.id
    $whereSql
    ORDER BY a.id ASC
    LIMIT $limit OFFSET $offset
");
$st->execute($params);
$rows = $st->fetchAll();

// contacts in bulk
$appIds = array_map(fn($r) => (int)$r['id'], $rows);
$contactsByApp = [];

if ($appIds) {
    $ph = implode(',', array_fill(0, count($appIds), '?'));
    $stC = $pdo->prepare("
        SELECT application_id, rolle, name, tel, mail, notiz
        FROM contacts
        WHERE application_id IN ($ph)
        ORDER BY application_id ASC, id ASC
    ");
    $stC->execute($appIds);

    while ($c = $stC->fetch()) {
        $aid = (int)$c['application_id'];
        $contactsByApp[$aid][] = [
            'rolle' => (string)($c['rolle'] ?? ''),
            'name'  => (string)($c['name'] ?? ''),
            'tel'   => (string)($c['tel'] ?? ''),
            'mail'  => (string)($c['mail'] ?? ''),
            'notiz' => (string)($c['notiz'] ?? ''),
        ];
    }
}

// attach contacts
foreach ($rows as &$r) {
    $aid = (int)$r['id'];
    $r['contacts'] = $contactsByApp[$aid] ?? [];
}
unset($r);

json_out(200, [
    'ok' => true,
    'bbs' => [
        'bbs_id' => (int)$bbs['bbs_id'],
        'schulnummer' => (string)$bbs['bbs_schulnummer'],
        'bezeichnung' => (string)$bbs['bbs_bezeichnung'],
    ],
    'paging' => [
        'limit' => $limit,
        'offset' => $offset,
        'total' => $total,
        'has_more' => ($offset + $limit) < $total,
    ],
    'data' => $rows,
]);
