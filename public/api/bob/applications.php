<?php
declare(strict_types=1);

// Datei: public/api/bob/applications.php

require_once __DIR__ . '/../../../app/config.php';

date_default_timezone_set('Europe/Berlin');

// --- CORS (BoB läuft auf anderem Host) ---
$origin = isset($_SERVER['HTTP_ORIGIN']) ? (string)$_SERVER['HTTP_ORIGIN'] : '';
$allowOrigins = array(
    'https://silbobdev.svs.schule',
    // Produktiv/weitere Schulen:
    'https://bbs-3-ol.svs.schule',
    'https://bbs-haarentor-ol.svs.schule',
    'https://bbs-wechloy-ol.svs.schule',
    'https://bbs-bztg-ol.svs.schule',

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

function json_out($code, array $data)
{
    http_response_code((int)$code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bearer_token()
{
    $hdr = isset($_SERVER['HTTP_AUTHORIZATION']) ? (string)$_SERVER['HTTP_AUTHORIZATION'] : '';
    if ($hdr === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) $hdr = $headers['Authorization'];
        elseif (isset($headers['authorization'])) $hdr = $headers['authorization'];
    }
    if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', $hdr, $m)) {
        return trim($m[1]);
    }
    return '';
}

function require_bob(PDO $pdo)
{
    $token = bearer_token();
    if ($token === '') {
        json_out(401, array('ok' => false, 'error' => 'missing_bearer_token'));
    }
    $hash = hash('sha256', $token);

    $st = $pdo->prepare("
        SELECT bbs_id, bbs_schulnummer, bbs_bezeichnung
        FROM bbs
        WHERE is_active=1 AND rest_token_hash=?
        LIMIT 1
    ");
    $st->execute(array($hash));
    $bbs = $st->fetch(PDO::FETCH_ASSOC);
    if (!$bbs) {
        json_out(401, array('ok' => false, 'error' => 'invalid_token'));
    }
    return $bbs;
}

$bbs = require_bob($pdo);

// --- Detailmodus: /api/bob/applications.php?id=123 ---
$appId = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
if ($appId > 0) {

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

            -- Uploads (Flags für Detail oben optional, schadet nicht)
            (SELECT u.filename FROM uploads u WHERE u.application_id = a.id AND u.typ = 'zeugnis'    LIMIT 1) AS upload_zeugnis_filename,
            (SELECT u.mime     FROM uploads u WHERE u.application_id = a.id AND u.typ = 'zeugnis'    LIMIT 1) AS upload_zeugnis_mime,
            (SELECT u.filename FROM uploads u WHERE u.application_id = a.id AND u.typ = 'lebenslauf' LIMIT 1) AS upload_lebenslauf_filename,
            (SELECT u.mime     FROM uploads u WHERE u.application_id = a.id AND u.typ = 'lebenslauf' LIMIT 1) AS upload_lebenslauf_mime,

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
    $st->execute(array($appId));
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        json_out(404, array('ok' => false, 'error' => 'not_found'));
    }

    // contacts
    $contacts = array();
    $stC = $pdo->prepare("
        SELECT rolle, name, tel, mail, notiz
        FROM contacts
        WHERE application_id = ?
        ORDER BY id ASC
    ");
    $stC->execute(array($appId));
    while ($c = $stC->fetch(PDO::FETCH_ASSOC)) {
        $contacts[] = array(
            'rolle' => (string)(isset($c['rolle']) ? $c['rolle'] : ''),
            'name'  => (string)(isset($c['name']) ? $c['name'] : ''),
            'tel'   => (string)(isset($c['tel']) ? $c['tel'] : ''),
            'mail'  => (string)(isset($c['mail']) ? $c['mail'] : ''),
            'notiz' => (string)(isset($c['notiz']) ? $c['notiz'] : ''),
        );
    }
    $row['contacts'] = $contacts;

    // uploads (alle Metadaten)
    $uploads = array();
    $stU = $pdo->prepare("
        SELECT typ, filename, mime, size_bytes, uploaded_at
        FROM uploads
        WHERE application_id = ?
        ORDER BY id ASC
    ");
    $stU->execute(array($appId));
    while ($u = $stU->fetch(PDO::FETCH_ASSOC)) {
        $uploads[] = array(
            'typ'         => (string)(isset($u['typ']) ? $u['typ'] : ''),
            'filename'    => (string)(isset($u['filename']) ? $u['filename'] : ''),
            'mime'        => (string)(isset($u['mime']) ? $u['mime'] : ''),
            'size_bytes'  => (int)(isset($u['size_bytes']) ? $u['size_bytes'] : 0),
            'uploaded_at' => (string)(isset($u['uploaded_at']) ? $u['uploaded_at'] : ''),
        );
    }
    $row['uploads'] = $uploads;

    json_out(200, array(
        'ok' => true,
        'bbs' => array(
            'bbs_id'      => (int)$bbs['bbs_id'],
            'schulnummer' => (string)$bbs['bbs_schulnummer'],
            'bezeichnung' => (string)$bbs['bbs_bezeichnung'],
        ),
        'data' => $row,
    ));
}

// --- Paging ---
$limit = (int)(isset($_GET['limit']) ? $_GET['limit'] : 200);
if ($limit < 1) $limit = 1;
if ($limit > 500) $limit = 500;

$offset = (int)(isset($_GET['offset']) ? $_GET['offset'] : 0);
if ($offset < 0) $offset = 0;

// Optionaler Statusfilter (z.B. submitted)
$status = (string)(isset($_GET['status']) ? $_GET['status'] : '');
$allowedStatus = array('', 'draft', 'submitted', 'withdrawn');
if (!in_array($status, $allowedStatus, true)) {
    $status = '';
}

$where = array();
$params = array();

if ($status !== '') {
    $where[]  = 'a.status = ?';
    $params[] = $status;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// total
$stCount = $pdo->prepare("
    SELECT COUNT(*)
    FROM applications a
    $whereSql
");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();

// data (Liste) inkl. Upload-Metadaten (2 Typen)
$sql = "
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

        -- Uploads (nur Metadaten/Flags für Listenansicht)
        (SELECT u.filename FROM uploads u WHERE u.application_id = a.id AND u.typ = 'zeugnis'    LIMIT 1) AS upload_zeugnis_filename,
        (SELECT u.mime     FROM uploads u WHERE u.application_id = a.id AND u.typ = 'zeugnis'    LIMIT 1) AS upload_zeugnis_mime,
        (SELECT u.filename FROM uploads u WHERE u.application_id = a.id AND u.typ = 'lebenslauf' LIMIT 1) AS upload_lebenslauf_filename,
        (SELECT u.mime     FROM uploads u WHERE u.application_id = a.id AND u.typ = 'lebenslauf' LIMIT 1) AS upload_lebenslauf_mime,

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
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// contacts in bulk (optional – falls du sie in der Liste brauchst)
$appIds = array();
for ($i = 0; $i < count($rows); $i++) {
    $appIds[] = (int)$rows[$i]['id'];
}

$contactsByApp = array();

if (!empty($appIds)) {
    $ph = implode(',', array_fill(0, count($appIds), '?'));
    $stC = $pdo->prepare("
        SELECT application_id, rolle, name, tel, mail, notiz
        FROM contacts
        WHERE application_id IN ($ph)
        ORDER BY application_id ASC, id ASC
    ");
    $stC->execute($appIds);

    while ($c = $stC->fetch(PDO::FETCH_ASSOC)) {
        $aid = (int)$c['application_id'];
        if (!isset($contactsByApp[$aid])) $contactsByApp[$aid] = array();
        $contactsByApp[$aid][] = array(
            'rolle' => (string)(isset($c['rolle']) ? $c['rolle'] : ''),
            'name'  => (string)(isset($c['name']) ? $c['name'] : ''),
            'tel'   => (string)(isset($c['tel']) ? $c['tel'] : ''),
            'mail'  => (string)(isset($c['mail']) ? $c['mail'] : ''),
            'notiz' => (string)(isset($c['notiz']) ? $c['notiz'] : ''),
        );
    }
}

// attach contacts
for ($j = 0; $j < count($rows); $j++) {
    $aid = (int)$rows[$j]['id'];
    $rows[$j]['contacts'] = isset($contactsByApp[$aid]) ? $contactsByApp[$aid] : array();

    // (optional) Typ-Sicherheit/Normalisierung für die Upload-Felder
    $rows[$j]['upload_zeugnis_filename'] = isset($rows[$j]['upload_zeugnis_filename']) ? (string)$rows[$j]['upload_zeugnis_filename'] : '';
    $rows[$j]['upload_zeugnis_mime']     = isset($rows[$j]['upload_zeugnis_mime']) ? (string)$rows[$j]['upload_zeugnis_mime'] : '';
    $rows[$j]['upload_lebenslauf_filename'] = isset($rows[$j]['upload_lebenslauf_filename']) ? (string)$rows[$j]['upload_lebenslauf_filename'] : '';
    $rows[$j]['upload_lebenslauf_mime']     = isset($rows[$j]['upload_lebenslauf_mime']) ? (string)$rows[$j]['upload_lebenslauf_mime'] : '';
}

json_out(200, array(
    'ok' => true,
    'bbs' => array(
        'bbs_id'      => (int)$bbs['bbs_id'],
        'schulnummer' => (string)$bbs['bbs_schulnummer'],
        'bezeichnung' => (string)$bbs['bbs_bezeichnung'],
    ),
    'paging' => array(
        'limit'    => (int)$limit,
        'offset'   => (int)$offset,
        'total'    => (int)$total,
        'has_more' => ((int)$offset + (int)$limit) < (int)$total,
    ),
    'data' => $rows,
));
