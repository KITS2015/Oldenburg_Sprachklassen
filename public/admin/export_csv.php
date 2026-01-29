<?php
declare(strict_types=1);

// Datei: public/admin/export_csv.php

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';

require_admin();

$pdo = admin_db();

$mode = (string)($_REQUEST['mode'] ?? 'all');
if ($mode !== 'all' && $mode !== 'selected') {
    $mode = 'all';
}

// Für selected: CSRF prüfen (POST)
if ($mode === 'selected') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo 'Method Not Allowed';
        exit;
    }
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (!csrf_verify($csrf)) {
        http_response_code(400);
        echo 'CSRF ungültig';
        exit;
    }
}

$status = trim((string)($_GET['status'] ?? ''));
$allowedStatus = ['', 'draft', 'submitted', 'withdrawn'];
if (!in_array($status, $allowedStatus, true)) {
    $status = '';
}

$q = trim((string)($_GET['q'] ?? ''));
$q = mb_substr($q, 0, 200);

// ---- WHERE für "all" ----
$where = [];
$params = [];

if ($mode === 'all') {
    if ($status !== '') {
        $where[] = 'a.status = ?';
        $params[] = $status;
    }

    if ($q !== '') {
        $where[] = '('
            . 'a.email LIKE ? OR '
            . 'COALESCE(p.email, "") LIKE ? OR '
            . 'COALESCE(p.name, "") LIKE ? OR '
            . 'COALESCE(p.vorname, "") LIKE ? OR '
            . 'a.token LIKE ? OR '
            . 'CAST(a.id AS CHAR) LIKE ?'
            . ')';
        $like = '%' . $q . '%';
        $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
    }
} else {
    // selected
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids) || count($ids) === 0) {
        http_response_code(400);
        echo 'Keine Datensätze ausgewählt.';
        exit;
    }

    $cleanIds = [];
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id > 0) { $cleanIds[] = $id; }
    }
    $cleanIds = array_values(array_unique($cleanIds));
    if (!$cleanIds) {
        http_response_code(400);
        echo 'Keine gültigen IDs ausgewählt.';
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
    $where[] = "a.id IN ($placeholders)";
    $params = array_merge($params, $cleanIds);
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---- CSV Header ----
$filename = 'bewerbungen_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// BOM für Excel-Kompatibilität (optional, hilft oft)
fwrite($out, "\xEF\xBB\xBF");

// Spalten: personal + school + contacts (kompakt) + meta
$header = [
    'application_id',
    'status',
    'created_at',
    'updated_at',

    // personal
    'name',
    'vorname',
    'geschlecht',
    'geburtsdatum',
    'geburtsort_land',
    'staatsang',
    'strasse',
    'plz',
    'wohnort',
    'telefon',
    'email',
    'weitere_angaben',
    'dsgvo_ok',

    // school
    'schule_aktuell',
    'schule_freitext',
    'schule_label',
    'klassenlehrer',
    'mail_lehrkraft',
    'seit_monat',
    'seit_jahr',
    'seit_text',
    'jahre_in_de',
    'schule_herkunft',
    'jahre_schule_herkunft',
    'familiensprache',
    'deutsch_niveau',
    'interessen',

    // contacts (kompakt)
    'contacts'
];
fputcsv($out, $header, ';');

// ---- Daten lesen (ohne uploads) ----
$st = $pdo->prepare("
    SELECT
        a.id,
        a.status,
        a.created_at,
        a.updated_at,

        p.name,
        p.vorname,
        p.geschlecht,
        p.geburtsdatum,
        p.geburtsort_land,
        p.staatsang,
        p.strasse,
        p.plz,
        p.wohnort,
        p.telefon,
        p.email,
        p.weitere_angaben,
        p.dsgvo_ok,

        s.schule_aktuell,
        s.schule_freitext,
        s.schule_label,
        s.klassenlehrer,
        s.mail_lehrkraft,
        s.seit_monat,
        s.seit_jahr,
        s.seit_text,
        s.jahre_in_de,
        s.schule_herkunft,
        s.jahre_schule_herkunft,
        s.familiensprache,
        s.deutsch_niveau,
        s.interessen

    FROM applications a
    LEFT JOIN personal p ON p.application_id = a.id
    LEFT JOIN school   s ON s.application_id = a.id
    $whereSql
    ORDER BY a.id DESC
");
$st->execute($params);

$appIds = [];
$rows = $st->fetchAll();
foreach ($rows as $r) {
    $appIds[] = (int)$r['id'];
}
$appIds = array_values(array_unique($appIds));

// Kontakte in einem Schwung laden und je application_id zusammenbauen
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
        $line = trim((string)($c['rolle'] ?? '')) . ':' .
                trim((string)($c['name'] ?? '')) . ':' .
                trim((string)($c['tel'] ?? '')) . ':' .
                trim((string)($c['mail'] ?? '')) . ':' .
                trim((string)($c['notiz'] ?? ''));
        $contactsByApp[$aid][] = $line;
    }
}

// Schreiben
foreach ($rows as $r) {
    $aid = (int)$r['id'];
    $contactsCompact = '';
    if (isset($contactsByApp[$aid])) {
        $contactsCompact = implode(' | ', $contactsByApp[$aid]);
    }

    $csvRow = [
        $aid,
        (string)($r['status'] ?? ''),
        (string)($r['created_at'] ?? ''),
        (string)($r['updated_at'] ?? ''),

        (string)($r['name'] ?? ''),
        (string)($r['vorname'] ?? ''),
        (string)($r['geschlecht'] ?? ''),
        (string)($r['geburtsdatum'] ?? ''),
        (string)($r['geburtsort_land'] ?? ''),
        (string)($r['staatsang'] ?? ''),
        (string)($r['strasse'] ?? ''),
        (string)($r['plz'] ?? ''),
        (string)($r['wohnort'] ?? ''),
        (string)($r['telefon'] ?? ''),
        (string)($r['email'] ?? ''),
        (string)($r['weitere_angaben'] ?? ''),
        (string)($r['dsgvo_ok'] ?? ''),

        (string)($r['schule_aktuell'] ?? ''),
        (string)($r['schule_freitext'] ?? ''),
        (string)($r['schule_label'] ?? ''),
        (string)($r['klassenlehrer'] ?? ''),
        (string)($r['mail_lehrkraft'] ?? ''),
        (string)($r['seit_monat'] ?? ''),
        (string)($r['seit_jahr'] ?? ''),
        (string)($r['seit_text'] ?? ''),
        (string)($r['jahre_in_de'] ?? ''),
        (string)($r['schule_herkunft'] ?? ''),
        (string)($r['jahre_schule_herkunft'] ?? ''),
        (string)($r['familiensprache'] ?? ''),
        (string)($r['deutsch_niveau'] ?? ''),
        (string)($r['interessen'] ?? ''),

        $contactsCompact,
    ];

    fputcsv($out, $csvRow, ';');
}

fclose($out);
exit;
