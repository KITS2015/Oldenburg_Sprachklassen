<?php
// public/application_pdf.php
declare(strict_types=1);

/**
 * Bewerbungs-PDF (Download/Print)
 *
 * Datenquelle primär: applications.data_json
 * Fallback: Tabellen personal/contacts/school/uploads
 *
 * Composer: tecnickcom/tcpdf
 */

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/db.php';

// Composer Autoload (TCPDF via vendor)
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit(t('pdf.err.autoload_missing'));
}
require_once $autoload;

use TCPDF;

function s(mixed $v): string { return trim((string)$v); }
function dash(mixed $v): string { $t = trim((string)$v); return $t !== '' ? $t : '–'; }

// i18n yes/no + fallback
function yn_i18n(mixed $v): string {
    $t = strtolower(s($v));
    if ($t === '1' || $t === 'ja' || $t === 'true')  return t('pdf.yes');
    if ($t === '0' || $t === 'nein' || $t === 'false') return t('pdf.no');
    return $t !== '' ? (string)$v : '–';
}

function hpdf(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function join_nonempty(array $parts, string $sep = ' '): string {
    $p = [];
    foreach ($parts as $x) {
        $x = trim((string)$x);
        if ($x !== '') $p[] = $x;
    }
    return $p ? implode($sep, $p) : '';
}
function gender_label_i18n(string $g): string {
    $g = strtolower(trim($g));
    return match ($g) {
        'm' => t('pdf.gender.m'),
        'w' => t('pdf.gender.w'),
        'd' => t('pdf.gender.d'),
        default => $g !== '' ? $g : '–',
    };
}

function fetch_application(PDO $pdo, string $token): array {
    $st = $pdo->prepare("
        SELECT id, token, email, dob, status, data_json, created_at, updated_at
        FROM applications
        WHERE token = :t
        LIMIT 1
    ");
    $st->execute([':t' => $token]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}
function fetch_personal(PDO $pdo, int $appId): array {
    $st = $pdo->prepare("SELECT * FROM personal WHERE application_id = :id LIMIT 1");
    $st->execute([':id' => $appId]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}
function fetch_contacts(PDO $pdo, int $appId): array {
    $st = $pdo->prepare("
        SELECT rolle, name, tel, mail, notiz, created_at
        FROM contacts
        WHERE application_id = :id
        ORDER BY id ASC
    ");
    $st->execute([':id' => $appId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
function fetch_school(PDO $pdo, int $appId): array {
    $st = $pdo->prepare("SELECT * FROM school WHERE application_id = :id LIMIT 1");
    $st->execute([':id' => $appId]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}
function fetch_uploads(PDO $pdo, int $appId): array {
    $st = $pdo->prepare("
        SELECT typ, filename, mime, size_bytes, uploaded_at
        FROM uploads
        WHERE application_id = :id
        ORDER BY typ ASC
    ");
    $st->execute([':id' => $appId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

final class AppPdf extends TCPDF {
    public string $headerTitle = '';
    public string $headerSub   = '';
    public string $footerLeft  = '';

    public function Header(): void {
        $this->SetY(10);
        $this->SetFont('dejavusans', 'B', 14);
        $this->Cell(0, 6, $this->headerTitle, 0, 1, 'L', false, '', 0, false, 'T', 'M');

        if ($this->headerSub !== '') {
            $this->SetFont('dejavusans', '', 9);
            $this->SetTextColor(80, 80, 80);
            $this->MultiCell(0, 4, $this->headerSub, 0, 'L', false, 1);
            $this->SetTextColor(0, 0, 0);
        }

        $this->Line(15, 26, 195, 26);
        $this->Ln(4);
    }

    public function Footer(): void {
        $this->SetY(-15);
        $this->SetFont('dejavusans', '', 8);
        $this->SetTextColor(110, 110, 110);

        $left = $this->footerLeft !== '' ? $this->footerLeft : t('pdf.footer_auto');
        $this->Cell(0, 5, $left, 0, 0, 'L');

        $page = t('pdf.footer_page');
        $page = str_replace(
            ['{cur}', '{max}'],
            [$this->getAliasNumPage(), $this->getAliasNbPages()],
            $page
        );
        $this->Cell(0, 5, $page, 0, 0, 'R');

        $this->SetTextColor(0, 0, 0);
    }
}

// simple placeholder replace
function tr_pdf(string $key, array $vars = []): string {
    $s = t($key);
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string)$v, $s);
    }
    return $s;
}

$token = current_access_token();
if ($token === '') {
    http_response_code(403);
    exit(t('pdf.err.no_token'));
}

try {
    $pdo = db();
    $app = fetch_application($pdo, $token);
    if (!$app) {
        http_response_code(404);
        exit(t('pdf.err.not_found'));
    }

    $appId  = (int)$app['id'];
    $status = s($app['status'] ?? 'draft');

    // data_json laden
    $dj = [];
    $dataJson = $app['data_json'] ?? null;
    if ($dataJson !== null && $dataJson !== '') {
        if (is_string($dataJson)) {
            $tmp = json_decode($dataJson, true);
            if (is_array($tmp)) $dj = $tmp;
        } elseif (is_array($dataJson)) {
            $dj = $dataJson;
        }
    }

    $p   = is_array($dj['form']['personal'] ?? null) ? $dj['form']['personal'] : [];
    $scl = is_array($dj['form']['school'] ?? null)   ? $dj['form']['school']   : [];
    $upl = is_array($dj['form']['upload'] ?? null)   ? $dj['form']['upload']   : [];

    // Fallbacks DB
    $pDb = fetch_personal($pdo, $appId);
    if (!$p) $p = $pDb ?: [];

    $contacts = $p['contacts'] ?? [];
    if (!is_array($contacts) || count($contacts) === 0) {
        $contacts = fetch_contacts($pdo, $appId);
    }

    $schoolDb = fetch_school($pdo, $appId);
    if (!$scl) $scl = $schoolDb ?: [];

    $uploads = fetch_uploads($pdo, $appId);

    // Derived / display values
    $generatedAt = (new DateTimeImmutable('now'))->format('d.m.Y H:i');
    $ref = tr_pdf('pdf.meta.ref', ['id' => (string)$appId]);

    $fullName = trim(join_nonempty([s($p['vorname'] ?? $pDb['vorname'] ?? ''), s($p['name'] ?? $pDb['name'] ?? '')]));
    if ($fullName === '') $fullName = '–';

    $schoolLabel = s($scl['schule_label'] ?? '');
    if ($schoolLabel === '') $schoolLabel = s($scl['schule_freitext'] ?? '');
    if ($schoolLabel === '') $schoolLabel = s($scl['schule_aktuell'] ?? '');

    $since = s($scl['seit_wann_schule'] ?? '');
    if ($since === '') {
        $since = join_nonempty([s($scl['seit_monat'] ?? ''), s($scl['seit_jahr'] ?? '')], '.');
    }

    $interessen = $scl['interessen'] ?? '';
    if (is_array($interessen)) {
        $interessen = implode(', ', array_map(static fn($x) => (string)$x, $interessen));
    } else {
        $interessen = (string)$interessen;
    }

    // Upload status
    $has = ['zeugnis' => false, 'lebenslauf' => false];
    foreach ($uploads as $urow) {
        $typ = s($urow['typ'] ?? '');
        if (array_key_exists($typ, $has)) $has[$typ] = true;
    }

    // PDF
    $pdf = new AppPdf('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('BBS Bewerbungssystem');
    $pdf->SetAuthor('BBS Bewerbungssystem');
    $pdf->SetTitle(t('pdf.header_title'));
    $pdf->SetSubject('Bewerbung');
    $pdf->SetMargins(15, 30, 15);
    $pdf->SetAutoPageBreak(true, 18);

    $pdf->headerTitle = t('pdf.header_title');
    $pdf->headerSub   = $ref
        . ' • ' . t('pdf.meta.created_at') . ' ' . $generatedAt
        . ' • ' . t('pdf.meta.status') . ': ' . ($status !== '' ? $status : '–');
    $pdf->footerLeft  = $ref;

    $pdf->AddPage();
    $pdf->SetFont('dejavusans', '', 10);

    // Small helper: section card
    $css = '
      <style>
        .card { border: 1px solid #d9d9d9; background-color: #f7f7f7; padding: 10px; }
        .h2 { font-size: 12pt; font-weight: bold; margin: 0 0 6px 0; }
        .meta { font-size: 9pt; color: #555; }
        .tbl { width: 100%; border-collapse: collapse; }
        .tbl td { padding: 4px 6px; vertical-align: top; }
        .k { width: 32%; color: #333; font-weight: bold; }
        .v { width: 68%; color: #000; }
        .sep { height: 8px; }
        .tag { display: inline-block; border: 1px solid #cfcfcf; padding: 2px 6px; background: #fff; }
        .small { font-size: 9pt; color: #666; }
        .table { width:100%; border-collapse: collapse; }
        .table th { background: #efefef; border: 1px solid #d0d0d0; padding: 5px; font-weight: bold; }
        .table td { border: 1px solid #d0d0d0; padding: 5px; }
      </style>
    ';

    // Cover/meta block
    $hintPlaceholder = t('pdf.hint_placeholder');

    $htmlTop = $css . '
      <div class="card">
        <div class="h2">' . hpdf(t('pdf.top.title')) . '</div>
        <table class="tbl">
          <tr>
            <td class="k">' . hpdf(t('pdf.top.name')) . '</td><td class="v">' . hpdf($fullName) . '</td>
          </tr>
          <tr>
            <td class="k">' . hpdf(t('pdf.top.reference')) . '</td><td class="v">' . hpdf($ref) . '</td>
          </tr>
          <tr>
            <td class="k">' . hpdf(t('pdf.top.generated')) . '</td><td class="v">' . hpdf($generatedAt) . '</td>
          </tr>
          <tr>
            <td class="k">' . hpdf(t('pdf.top.hint')) . '</td><td class="v"><span class="tag">' . hpdf($hintPlaceholder) . '</span></td>
          </tr>
        </table>
        <div class="small" style="margin-top:6px;">
          ' . hpdf(t('pdf.top.keep_note')) . '
        </div>
      </div>
      <div class="sep"></div>
    ';
    $pdf->writeHTML($htmlTop, true, false, true, false, '');

    // Section 1: Personal
    $htmlPersonal = '
      <div class="card">
        <div class="h2">' . hpdf(t('pdf.sec1.title')) . '</div>
        <table class="tbl">
          <tr><td class="k">' . hpdf(t('pdf.lbl.name')) . '</td><td class="v">' . hpdf(dash($p['name'] ?? $pDb['name'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.vorname')) . '</td><td class="v">' . hpdf(dash($p['vorname'] ?? $pDb['vorname'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.gender')) . '</td><td class="v">' . hpdf(gender_label_i18n(s($p['geschlecht'] ?? $pDb['geschlecht'] ?? ''))) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.dob')) . '</td><td class="v">' . hpdf(dash($p['geburtsdatum'] ?? $pDb['geburtsdatum'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.birthplace')) . '</td><td class="v">' . hpdf(dash($p['geburtsort_land'] ?? $pDb['geburtsort_land'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.nationality')) . '</td><td class="v">' . hpdf(dash($p['staatsang'] ?? $pDb['staatsang'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.address')) . '</td><td class="v">' . hpdf(dash(join_nonempty([
              $p['strasse'] ?? $pDb['strasse'] ?? '',
              join_nonempty([$p['plz'] ?? $pDb['plz'] ?? '', $p['wohnort'] ?? $pDb['wohnort'] ?? ''], ' ')
          ], ', '))) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.phone')) . '</td><td class="v">' . hpdf(dash($p['telefon'] ?? $pDb['telefon'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.email_optional')) . '</td><td class="v">' . hpdf(dash($p['email'] ?? $pDb['email'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.more')) . '</td><td class="v">' . hpdf(dash($p['weitere_angaben'] ?? $pDb['weitere_angaben'] ?? null)) . '</td></tr>
        </table>
      </div>
      <div class="sep"></div>
    ';
    $pdf->writeHTML($htmlPersonal, true, false, true, false, '');

    // Section 2: Contacts
    $htmlContacts = '
      <div class="card">
        <div class="h2">' . hpdf(t('pdf.sec2.title')) . '</div>
    ';

    $validContacts = [];
    if (is_array($contacts)) {
        foreach ($contacts as $c) {
            $name  = s($c['name'] ?? '');
            $rolle = s($c['rolle'] ?? '');
            $tel   = s($c['tel'] ?? '');
            $mail  = s($c['mail'] ?? '');
            $notiz = s($c['notiz'] ?? '');

            if ($name === '' && $rolle === '' && $tel === '' && $mail === '' && $notiz === '') {
                continue;
            }
            $validContacts[] = [
                'rolle' => $rolle,
                'name'  => $name,
                'tel'   => $tel,
                'mail'  => $mail,
                'notiz' => $notiz,
            ];
        }
    }

    if (count($validContacts) > 0) {
        $htmlContacts .= '
          <table class="table">
            <thead>
              <tr>
                <th style="width:16%;">' . hpdf(t('pdf.contacts.th.role')) . '</th>
                <th style="width:26%;">' . hpdf(t('pdf.contacts.th.name')) . '</th>
                <th style="width:18%;">' . hpdf(t('pdf.contacts.th.tel')) . '</th>
                <th style="width:24%;">' . hpdf(t('pdf.contacts.th.mail')) . '</th>
                <th style="width:16%;">' . hpdf(t('pdf.contacts.th.note')) . '</th>
              </tr>
            </thead>
            <tbody>
        ';
        foreach ($validContacts as $c) {
            $htmlContacts .= '
              <tr>
                <td>' . hpdf(dash($c['rolle'])) . '</td>
                <td>' . hpdf(dash($c['name'])) . '</td>
                <td>' . hpdf(dash($c['tel'])) . '</td>
                <td>' . hpdf(dash($c['mail'])) . '</td>
                <td>' . hpdf(dash($c['notiz'])) . '</td>
              </tr>
            ';
        }
        $htmlContacts .= '</tbody></table>';
    } else {
        $htmlContacts .= '<div class="small">' . hpdf(t('pdf.contacts.none')) . '</div>';
    }

    $htmlContacts .= '</div><div class="sep"></div>';
    $pdf->writeHTML($htmlContacts, true, false, true, false, '');

    // Section 3: School
    $htmlSchool = '
      <div class="card">
        <div class="h2">' . hpdf(t('pdf.sec3.title')) . '</div>
        <table class="tbl">
          <tr><td class="k">' . hpdf(t('pdf.lbl.school_current')) . '</td><td class="v">' . hpdf(dash($schoolLabel)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.teacher')) . '</td><td class="v">' . hpdf(dash($scl['klassenlehrer'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.teacher_email')) . '</td><td class="v">' . hpdf(dash($scl['mail_lehrkraft'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.since_school')) . '</td><td class="v">' . hpdf(dash($since)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.years_in_de')) . '</td><td class="v">' . hpdf(dash($scl['jahre_in_de'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.family_lang')) . '</td><td class="v">' . hpdf(dash($scl['familiensprache'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.de_level')) . '</td><td class="v">' . hpdf(dash($scl['deutsch_niveau'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.school_origin')) . '</td><td class="v">' . hpdf(dash($scl['schule_herkunft'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.years_origin')) . '</td><td class="v">' . hpdf(dash($scl['jahre_schule_herkunft'] ?? null)) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.interests')) . '</td><td class="v">' . hpdf(dash($interessen)) . '</td></tr>
        </table>
      </div>
      <div class="sep"></div>
    ';
    $pdf->writeHTML($htmlSchool, true, false, true, false, '');

    // Section 4: Uploads
    $htmlUploads = '
      <div class="card">
        <div class="h2">' . hpdf(t('pdf.sec4.title')) . '</div>
        <table class="tbl">
          <tr><td class="k">' . hpdf(t('pdf.lbl.report')) . '</td><td class="v">' . hpdf($has['zeugnis'] ? t('pdf.uploaded') : t('pdf.not_uploaded')) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.cv')) . '</td><td class="v">' . hpdf($has['lebenslauf'] ? t('pdf.uploaded') : t('pdf.not_uploaded')) . '</td></tr>
          <tr><td class="k">' . hpdf(t('pdf.lbl.report_later')) . '</td><td class="v">' . hpdf(yn_i18n($upl['zeugnis_spaeter'] ?? '0')) . '</td></tr>
        </table>
        <div class="small" style="margin-top:6px;">
          ' . hpdf(t('pdf.sec4.note')) . '
        </div>
      </div>
    ';
    $pdf->writeHTML($htmlUploads, true, false, true, false, '');

    // Dateiname
    $safeName = preg_replace('/[^a-z0-9_\-]+/i', '_', s(($p['name'] ?? '') . '_' . ($p['vorname'] ?? ''))) ?: 'bewerbung';
    $fileName = t('pdf.filename_prefix') . '_' . $safeName . '_' . $appId . '.pdf';

    $pdf->Output($fileName, 'I');
    exit;

} catch (Throwable $e) {
    error_log('application_pdf.php: ' . $e->getMessage());
    http_response_code(500);
    exit(t('pdf.err.server'));
}
