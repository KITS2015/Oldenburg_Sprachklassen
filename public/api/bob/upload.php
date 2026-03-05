<?php
declare(strict_types=1);

// Datei: public/api/bob/upload.php
require_once __DIR__ . '/../../../app/config.php';

date_default_timezone_set('Europe/Berlin');

// --- CORS (BoB läuft auf anderem Host) ---
$origin = isset($_SERVER['HTTP_ORIGIN']) ? trim((string)$_SERVER['HTTP_ORIGIN']) : '';
$originLower = strtolower($origin);

$allowOrigins = array(
    'https://silbobdev.svs.schule',
    'https://bbs-3-ol.svs.schule',
    'https://bbs-haarentor-ol.svs.schule',
    'https://bbs-wechloy-ol.svs.schule',
    'https://bbs-bztg-ol.svs.schule',
);

$allowMap = array();
for ($i=0; $i<count($allowOrigins); $i++) {
    $allowMap[strtolower($allowOrigins[$i])] = true;
}

if ($origin !== '' && isset($allowMap[$originLower])) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');

    // Credentials kannst du drin lassen; wichtig ist: nicht "*" als Origin
    header('Access-Control-Allow-Credentials: true');

    // WICHTIG: X-Requested-With + evtl. Range (PDF Viewer) erlauben
    header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, X-Requested-With, Range');

    header('Access-Control-Allow-Methods: GET, OPTIONS');

    // Optional aber hilfreich (PDF/Viewer/Debug):
    header('Access-Control-Expose-Headers: Content-Type, Content-Disposition, Content-Length');
}

// Preflight muss ohne Auth funktionieren
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($origin !== '' && in_array($origin, $allowOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// DB
$dsn = 'mysql:host=127.0.0.1;dbname=' . APP_DB_NAME . ';port=3306;charset=utf8mb4';
$pdo = pdo($dsn, APP_DB_USER, APP_DB_PASS);

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
        http_response_code(401);
        echo "missing_bearer_token";
        exit;
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
        http_response_code(401);
        echo "invalid_token";
        exit;
    }
    return $bbs;
}

require_bob($pdo);

$appId = (int)($_GET['app_id'] ?? 0);
$typ   = (string)($_GET['typ'] ?? '');

if ($appId <= 0) {
    http_response_code(400);
    echo "invalid_app_id";
    exit;
}
if (!in_array($typ, array('zeugnis', 'lebenslauf'), true)) {
    http_response_code(400);
    echo "invalid_typ";
    exit;
}

$st = $pdo->prepare("
    SELECT filename, mime, size_bytes
    FROM uploads
    WHERE application_id = ? AND typ = ?
    LIMIT 1
");
$st->execute([$appId, $typ]);
$u = $st->fetch(PDO::FETCH_ASSOC);

if (!$u || empty($u['filename'])) {
    http_response_code(404);
    echo "not_found";
    exit;
}

$uploadDir = rtrim(APP_UPLOADS, '/');
$fn = (string)$u['filename'];
$path = $uploadDir . '/' . $fn;

if (!is_file($path)) {
    http_response_code(404);
    echo "file_missing";
    exit;
}

$mime = (string)$u['mime'];
if ($mime === '') $mime = 'application/octet-stream';

$ext = '';
$dot = strrpos($fn, '.');
if ($dot !== false) $ext = substr($fn, $dot); // inkl. Punkt

$dlName = $typ . '_' . $appId . ($ext ?: '');

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($path));
header('Content-Disposition: inline; filename="' . $dlName . '"');
header('X-Content-Type-Options: nosniff');

readfile($path);
exit;
