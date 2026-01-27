<?php
// public/application_pdf.php
declare(strict_types=1);

/**
 * Bewerbungs-PDF (Download/Print)
 *
 * Erwartet eine laufende Session mit Access-Token (current_access_token()).
 * Datenquelle: applications.data_json (Fallback: Tabellen personal/contacts/school/uploads).
 *
 * Composer: "tecnickcom/tcpdf"
 * Aufruf z.B.: /application_pdf.php
 */

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/db.php';

// Composer Autoload (TCPDF via vendor)
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit('Composer Autoload nicht gefunden. Bitte composer install ausführen.');
}
require_once $autoload;

use TCPDF;

function s(?string $v): string { return trim((string)$v); }
function dash(?string $v): string { $t = trim((string)$v); return $t !== '' ? $t : '–'; }
function join_nonempty(array $parts, string $sep = ' '): string {
    $p = [];
    foreach ($parts as $x) {
        $x = trim((string)$x);
        if ($x !== '') $p[] = $x;
    }
    return $p ? implode($sep, $p) : '';
}

function fetch_application(PDO $pdo, string $token): array {
    $st = $pdo->prepare("SELECT id, token, email, dob, status, data_json, created_at, updated_at
                         FROM applications WHERE token = :t LIMIT 1");
    $st->execute([':t' => $token]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: [];
}

function fetch_personal(PDO $pdo, int $appId): array {
    $st = $pdo->prepare("SELECT * FROM personal WHERE application_id = :id LIMIT 1");
    $st->execute([':id' => $appId]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}

function fetch_contacts(PDO $pdo, int $appId): array {
    $st = $pdo->prepare("SELECT rolle, name, tel, mail, notiz, created_at
                         FROM contacts WHERE application_id = :id ORDER BY id ASC");
    $st->execute([':id' => $appId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function fetch_school(PDO $pdo, int $appId): array {
    $st = $pdo->prepare("SELECT * FROM school WHERE application_id = :id LIMIT 1");
    $st->execute([':id' => $appId]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}

function fetch_uploads(PDO $pdo, int $appId): array {
    $st = $pdo->prepare("SELECT typ, filename, mime, size_bytes, uploaded_at
                         FROM uploads WHERE application_id = :id ORDER BY typ ASC");
    $st->execute([':id' => $appId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$token = current_access_token();
if ($token === '') {
    http_response_code(403);
    exit('Kein gültiger Zugangscode. Bitte beginnen Sie den Vorgang neu.');
}

try {
    $pdo = db();
    $app = fetch_application($pdo, $token);
    if (!$app) {
        http_response_code(404);
        exit('Bewerbung nicht gefunden.');
    }

    $appId  = (int)$app['id'];
    $status = (string)($app['status'] ?? 'draft');

    // --- Daten aus data_json (Primärquelle) ---
    $dataJson = $app['data_json'] ?? null;
    $dj = [];
    if ($dataJson !== null && $dataJson !== '') {
        if (is_string($dataJson)) {
            $tmp = json_decode($dataJson, true);
            if (is_array($tmp)) $dj = $tmp;
        } elseif (is_array($dataJson)) {
            $dj = $dataJson;
        }
    }

    $p = $dj['form']['personal'] ?? [];
    $scl = $dj['form']['school'] ?? [];
    $upl = $dj['form']['upload'] ?? [];

    // --- Fallbacks aus Tabellen, falls data_json leer/inkomplett ist ---
    $pDb = fetch_personal($pdo, $appId);
    if (!is_array($p) || !$p) {
        $p = $pDb ?: [];
    }

    $contacts = $p['contacts'] ?? [];
    if (!is_array($contacts) || count($contacts) === 0) {
        $contacts = fetch_contacts($pdo, $appId);
    }

    $schoolDb = fetch_school($pdo, $appId);
    if (!is_array($scl) || !$scl) {
        $scl = $schoolDb ?: [];
    }

    $uploads = fetch_uploads($pdo, $appId);

    // --- PDF erstellen ---
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('BBS Bewerbungssystem');
    $pdf->SetAuthor('BBS Bewerbungssystem');
    $pdf->SetTitle('Bewerbung – Zusammenfassung');
    $pdf->SetSubject('Bewerbung');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    // Font: DejaVu unterstützt Umlaute sauber
    $pdf->SetFont('dejavusans', '', 11);

    $generatedAt = (new DateTimeImmutable('now'))->format('d.m.Y H:i');

    // Header
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->MultiCell(0, 8, 'Bewerbung – Zusammenfassung', 0, 'L', false, 1);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->MultiCell(0, 6, "Erstellt am: {$generatedAt}", 0, 'L', false, 1);
    $pdf->MultiCell(0, 6, "Status: " . ($status ?: 'draft'), 0, 'L', false, 1);

    // Platzhalter-Textbaustein (Kunde liefert finalen Text)
    $pdf->Ln(2);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetFont('dejavusans', 'B', 11);
    $pdf->MultiCell(0, 7, 'Hinweis (Platzhalter)', 0, 'L', true, 1);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->MultiCell(
        0,
        6,
        "<<< TEXTBAUSTEIN VOM KUNDEN FOLGT >>>\nDiese Bewerbung wurde gespeichert. Bitte bewahren Sie dieses Dokument für Ihre Unterlagen auf.",
        0,
        'L',
        false,
        1
    );

    // Abschnitt: Persönliche Daten
    $pdf->Ln(3);
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->MultiCell(0, 7, '1) Persönliche Daten', 0, 'L', false, 1);
    $pdf->SetFont('dejavusans', '', 10);

    $rowsPersonal = [
        ['Name', dash($p['name'] ?? ($pDb['name'] ?? null))],
        ['Vorname', dash($p['vorname'] ?? ($pDb['vorname'] ?? null))],
        ['Geschlecht', dash($p['geschlecht'] ?? ($pDb['geschlecht'] ?? null))],
        ['Geburtsdatum', dash($p['geburtsdatum'] ?? ($pDb['geburtsdatum'] ?? null))],
        ['Geburtsort/Land', dash($p['geburtsort_land'] ?? ($pDb['geburtsort_land'] ?? null))],
        ['Staatsangehörigkeit', dash($p['staatsang'] ?? ($pDb['staatsang'] ?? null))],
        ['Straße, Nr.', dash($p['strasse'] ?? ($pDb['strasse'] ?? null))],
        ['PLZ / Wohnort', dash(join_nonempty([
            $p['plz'] ?? ($pDb['plz'] ?? null),
            $p['wohnort'] ?? ($pDb['wohnort'] ?? null),
        ]))],
        ['Telefon', dash($p['telefon'] ?? ($pDb['telefon'] ?? null))],
        ['E-Mail (Schüler*in)', dash($p['email'] ?? ($pDb['email'] ?? null))],
        ['Weitere Angaben', dash($p['weitere_angaben'] ?? ($pDb['weitere_angaben'] ?? null))],
    ];

    // simple "table" via MultiCell
    foreach ($rowsPersonal as [$k, $v]) {
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->MultiCell(45, 6, $k . ':', 0, 'L', false, 0);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->MultiCell(0, 6, (string)$v, 0, 'L', false, 1);
    }

    // Abschnitt: Kontakte
    $pdf->Ln(2);
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->MultiCell(0, 7, '2) Weitere Kontaktdaten', 0, 'L', false, 1);
    $pdf->SetFont('dejavusans', '', 10);

    if (is_array($contacts) && count($contacts) > 0) {
        foreach ($contacts as $idx => $c) {
            $rolle = dash($c['rolle'] ?? null);
            $name  = dash($c['name'] ?? null);
            $tel   = dash($c['tel'] ?? null);
            $mail  = dash($c['mail'] ?? null);
            $notiz = dash($c['notiz'] ?? null);

            $pdf->SetFont('dejavusans', 'B', 10);
            $pdf->MultiCell(0, 6, 'Kontakt ' . ($idx + 1), 0, 'L', false, 1);
            $pdf->SetFont('dejavusans', '', 10);

            $pdf->MultiCell(45, 6, 'Rolle:', 0, 'L', false, 0);
            $pdf->MultiCell(0, 6, $rolle, 0, 'L', false, 1);

            $pdf->MultiCell(45, 6, 'Name/Einrichtung:', 0, 'L', false, 0);
            $pdf->MultiCell(0, 6, $name, 0, 'L', false, 1);

            $pdf->MultiCell(45, 6, 'Telefon:', 0, 'L', false, 0);
            $pdf->MultiCell(0, 6, $tel, 0, 'L', false, 1);

            $pdf->MultiCell(45, 6, 'E-Mail:', 0, 'L', false, 0);
            $pdf->MultiCell(0, 6, $mail, 0, 'L', false, 1);

            $pdf->MultiCell(45, 6, 'Notiz:', 0, 'L', false, 0);
            $pdf->MultiCell(0, 6, $notiz, 0, 'L', false, 1);

            $pdf->Ln(1);
        }
    } else {
        $pdf->MultiCell(0, 6, '–', 0, 'L', false, 1);
    }

    // Abschnitt: Schule & Interessen
    $pdf->Ln(2);
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->MultiCell(0, 7, '3) Schule & Interessen', 0, 'L', false, 1);
    $pdf->SetFont('dejavusans', '', 10);

    $schoolLabel = s($scl['schule_label'] ?? '') !== '' ? (string)$scl['schule_label'] : (string)($scl['schule_freitext'] ?? ($scl['schule_aktuell'] ?? ''));
    $since = s($scl['seit_wann_schule'] ?? '') !== '' ? (string)$scl['seit_wann_schule'] : join_nonempty([$scl['seit_monat'] ?? '', $scl['seit_jahr'] ?? ''], '.');

    $interessen = $scl['interessen'] ?? null;
    if (is_array($interessen)) {
        $interessen = implode(', ', array_map('strval', $interessen));
    } else {
        $interessen = (string)($scl['interessen'] ?? '');
    }

    $rowsSchool = [
        ['Aktuelle Schule', dash($schoolLabel)],
        ['Lehrer*in', dash($scl['klassenlehrer'] ?? null)],
        ['E-Mail Lehrkraft', dash($scl['mail_lehrkraft'] ?? null)],
        ['Seit wann an der Schule', dash($since)],
        ['Jahre in Deutschland', dash($scl['jahre_in_de'] ?? null)],
        ['Schule im Herkunftsland', dash($scl['schule_herkunft'] ?? null)],
        ['Jahre Schule im Herkunftsland', dash($scl['jahre_schule_herkunft'] ?? null)],
        ['Familiensprache', dash($scl['familiensprache'] ?? null)],
        ['Deutsch-Niveau', dash($scl['deutsch_niveau'] ?? null)],
        ['Interessen', dash((string)$interessen)],
    ];

    foreach ($rowsSchool as [$k, $v]) {
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->MultiCell(55, 6, $k . ':', 0, 'L', false, 0);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->MultiCell(0, 6, (string)$v, 0, 'L', false, 1);
    }

    // Abschnitt: Unterlagen
    $pdf->Ln(2);
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->MultiCell(0, 7, '4) Unterlagen', 0, 'L', false, 1);
    $pdf->SetFont('dejavusans', '', 10);

    $upMap = ['zeugnis' => 'Halbjahreszeugnis', 'lebenslauf' => 'Lebenslauf'];
    $has = ['zeugnis' => false, 'lebenslauf' => false];

    foreach ($uploads as $urow) {
        $typ = (string)($urow['typ'] ?? '');
        if (isset($has[$typ])) $has[$typ] = true;
    }

    foreach ($upMap as $typ => $label) {
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->MultiCell(55, 6, $label . ':', 0, 'L', false, 0);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->MultiCell(0, 6, $has[$typ] ? 'hochgeladen' : 'nicht hochgeladen', 0, 'L', false, 1);
    }

    $zeugnisSpaeter = (string)($upl['zeugnis_spaeter'] ?? '');
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->MultiCell(55, 6, 'Zeugnis später nachreichen:', 0, 'L', false, 0);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->MultiCell(0, 6, ($zeugnisSpaeter === '1') ? 'Ja' : 'Nein', 0, 'L', false, 1);

    // Footer/Meta
    $pdf->Ln(4);
    $pdf->SetFont('dejavusans', '', 9);
    $pdf->SetTextColor(90, 90, 90);
    $pdf->MultiCell(0, 5, 'Dieses Dokument ist eine automatisch erzeugte Zusammenfassung der eingegebenen Daten.', 0, 'L', false, 1);

    // Download-Name
    $safeName = preg_replace('/[^a-z0-9_\-]+/i', '_', s(($p['name'] ?? '') . '_' . ($p['vorname'] ?? ''))) ?: 'bewerbung';
    $fileName = 'Bewerbung_' . $safeName . '_' . $appId . '.pdf';

    // Inline im Browser anzeigen (I) oder Download erzwingen (D)
    $pdf->Output($fileName, 'I');
    exit;

} catch (Throwable $e) {
    error_log('application_pdf.php: ' . $e->getMessage());
    http_response_code(500);
    exit('Serverfehler beim Erzeugen des PDFs.');
}
