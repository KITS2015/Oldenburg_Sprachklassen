<?php
// public/application_pdf.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/db.php';

// TCPDF laden (Pfad ggf. anpassen!)
require_once __DIR__ . '/../vendor/autoload.php';

$token = current_access_token();
$readonly  = !empty($_SESSION['application_readonly']);
$submitted = $_SESSION['application_submitted'] ?? null;

if (!$token || !$readonly || !$submitted) {
    http_response_code(403);
    exit('Kein Zugriff.');
}

$appId = (int)($submitted['app_id'] ?? 0);
if ($appId <= 0) {
    http_response_code(400);
    exit('Ungültige Bewerbung.');
}

try {
    $pdo = db();

    // application prüfen
    $st = $pdo->prepare("SELECT id, status, email, dob, created_at, updated_at FROM applications WHERE id = :id AND token = :t LIMIT 1");
    $st->execute([':id' => $appId, ':t' => $token]);
    $app = $st->fetch(PDO::FETCH_ASSOC);
    if (!$app || ($app['status'] ?? '') !== 'submitted') {
        http_response_code(403);
        exit('Bewerbung nicht verfügbar.');
    }

    // personal
    $st = $pdo->prepare("SELECT * FROM personal WHERE application_id = :id LIMIT 1");
    $st->execute([':id' => $appId]);
    $p = $st->fetch(PDO::FETCH_ASSOC) ?: [];

    // contacts
    $st = $pdo->prepare("SELECT rolle, name, tel, mail, notiz FROM contacts WHERE application_id = :id ORDER BY id ASC");
    $st->execute([':id' => $appId]);
    $contacts = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // school
    $st = $pdo->prepare("SELECT * FROM school WHERE application_id = :id LIMIT 1");
    $st->execute([':id' => $appId]);
    $s = $st->fetch(PDO::FETCH_ASSOC) ?: [];

    // uploads (nur Status)
    $st = $pdo->prepare("SELECT typ FROM uploads WHERE application_id = :id");
    $st->execute([':id' => $appId]);
    $ups = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $hasZeugnis = false;
    $hasLebenslauf = false;
    foreach ($ups as $u) {
        if (($u['typ'] ?? '') === 'zeugnis') $hasZeugnis = true;
        if (($u['typ'] ?? '') === 'lebenslauf') $hasLebenslauf = true;
    }

} catch (Throwable $e) {
    error_log('application_pdf: '.$e->getMessage());
    http_response_code(500);
    exit('Serverfehler.');
}

// -------- PDF bauen --------
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Bewerbungsportal');
$pdf->SetAuthor('Bewerbungsportal');
$pdf->SetTitle('Bewerbung '.$appId);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Write(0, 'Bewerbung – Zusammenfassung', '', 0, 'L', true);

$pdf->SetFont('helvetica', '', 10);
$pdf->Ln(2);
$pdf->Write(0, 'Bewerbung #'.$appId, '', 0, 'L', true);
$pdf->Write(0, 'Status: '.$app['status'], '', 0, 'L', true);
$pdf->Write(0, 'Erstellt: '.($app['created_at'] ?? ''), '', 0, 'L', true);
$pdf->Write(0, 'Abgesendet: '.($app['updated_at'] ?? ''), '', 0, 'L', true);

$pdf->Ln(4);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Write(0, '1) Persönliche Daten', '', 0, 'L', true);
$pdf->SetFont('helvetica', '', 10);

$lines = [
    'Name: ' . trim(($p['name'] ?? '').' '.($p['vorname'] ?? '')),
    'Geschlecht: ' . ($p['geschlecht'] ?? ''),
    'Geburtsdatum: ' . ($p['geburtsdatum'] ?? ''),
    'Geburtsort/Land: ' . ($p['geburtsort_land'] ?? ''),
    'Staatsangehörigkeit: ' . ($p['staatsang'] ?? ''),
    'Adresse: ' . trim(($p['strasse'] ?? '').', '.($p['plz'] ?? '').' '.($p['wohnort'] ?? '')),
    'Telefon: ' . ($p['telefon'] ?? ''),
    'E-Mail (Schüler*in): ' . ($p['email'] ?? ''),
];
foreach ($lines as $l) {
    $pdf->Write(0, $l, '', 0, 'L', true);
}

$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Write(0, 'Weitere Angaben:', '', 0, 'L', true);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 0, (string)($p['weitere_angaben'] ?? ''), 0, 'L', false, 1);

$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Write(0, '2) Weitere Kontakte', '', 0, 'L', true);
$pdf->SetFont('helvetica', '', 10);

if (!$contacts) {
    $pdf->Write(0, '–', '', 0, 'L', true);
} else {
    foreach ($contacts as $c) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Write(0, (string)($c['rolle'] ?? 'Kontakt'), '', 0, 'L', true);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Write(0, 'Name/Einrichtung: '.($c['name'] ?? ''), '', 0, 'L', true);
        $pdf->Write(0, 'Telefon: '.($c['tel'] ?? ''), '', 0, 'L', true);
        $pdf->Write(0, 'E-Mail: '.($c['mail'] ?? ''), '', 0, 'L', true);
        $pdf->Write(0, 'Notiz: '.($c['notiz'] ?? ''), '', 0, 'L', true);
        $pdf->Ln(1);
    }
}

$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Write(0, '3) Schule & Interessen', '', 0, 'L', true);
$pdf->SetFont('helvetica', '', 10);

$pdf->Write(0, 'Jahre in Deutschland: '.($s['schule_jahre'] ?? ''), '', 0, 'L', true);
$pdf->Write(0, 'Seit (Monat/Jahr): '.trim((string)($s['seit_monat'] ?? '').' / '.(string)($s['seit_jahr'] ?? '')), '', 0, 'L', true);
$pdf->Write(0, 'Deutsch-Niveau: '.($s['deutsch_niveau'] ?? ''), '', 0, 'L', true);
$pdf->Write(0, 'Interessen: '.($s['interessen'] ?? ''), '', 0, 'L', true);

$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Write(0, '4) Unterlagen', '', 0, 'L', true);
$pdf->SetFont('helvetica', '', 10);
$pdf->Write(0, 'Halbjahreszeugnis: '.($hasZeugnis ? 'hochgeladen' : 'nicht hochgeladen'), '', 0, 'L', true);
$pdf->Write(0, 'Lebenslauf: '.($hasLebenslauf ? 'hochgeladen' : 'nicht hochgeladen'), '', 0, 'L', true);

$filename = 'bewerbung_'.$appId.'.pdf';
$pdf->Output($filename, 'I');
exit;
