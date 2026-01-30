<?php
// public/form_review.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/db.php';

require_step('review');

// Readonly, wenn bereits eingereichte Bewerbung geladen wurde
$readonly = !empty($_SESSION['application_readonly']);

// i18n helper: {var} Platzhalter ersetzen (Strings)
function tr(string $key, array $vars = []): string {
    $s = t($key);
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string)$v, $s);
    }
    return $s;
}

// UI Helper
function show_val(string $v): string {
    $v = trim($v);
    return $v !== '' ? h($v) : h(t('review.value.empty'));
}
function norm_space(?string $v): string {
    return trim((string)$v);
}
function dl_row(string $label, string $valueHtml, string $col = 'col-md-6'): string {
    $empty = h(t('review.value.empty'));
    $val   = trim($valueHtml) !== '' ? $valueHtml : $empty;

    return '
      <div class="'.h($col).'">
        <div class="small text-muted">'.h($label).'</div>
        <div class="fw-semibold">'.$val.'</div>
      </div>
    ';
}

/**
 * Fallback: hole Feld aus applications.data_json (wenn Session leer/inkonsistent ist)
 * Erwartet Struktur: {"form":{"personal":{...}}}
 */
function datajson_get_personal(PDO $pdo, string $token, string $key): ?string {
    try {
        $st = $pdo->prepare("SELECT data_json FROM applications WHERE token = :t LIMIT 1");
        $st->execute([':t' => $token]);
        $raw = $st->fetchColumn();
        if (!$raw) return null;

        $arr = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($arr)) return null;

        $val = $arr['form']['personal'][$key] ?? null;
        if (!is_string($val)) return null;

        $val = trim($val);
        return $val !== '' ? $val : null;
    } catch (Throwable $e) {
        error_log('form_review datajson_get_personal: ' . $e->getMessage());
        return null;
    }
}

// ===== POST-Verarbeitung (Bewerben / Zur Startseite / Neue Bewerbung) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { http_response_code(400); exit(t('review.err.invalid_request')); }

    $action = (string)($_POST['action'] ?? 'submit');

    // --- Session zurücksetzen & zur Startseite ---
    if ($action === 'reset') {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: ' . url_with_lang('/index.php'));
        exit;
    }

    // --- Session zurücksetzen & neue Bewerbung starten ---
    if ($action === 'new_application') {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: ' . url_with_lang('/access_create.php'));
        exit;
    }

    // --- Bewerbung final einreichen ---
    if ($action === 'submit') {
        // Bewerbung ist bereits eingereicht -> nicht noch einmal abschicken
        if ($readonly) {
            if (function_exists('flash_set')) {
                flash_set('info', t('review.flash.already_submitted'));
            }
            header('Location: ' . url_with_lang('/form_review.php'));
            exit;
        }

        $pdo = db();

        // Form-Daten aus der Session holen
        $p = $_SESSION['form']['personal'] ?? [];
        $s = $_SESSION['form']['school']   ?? [];
        $u = $_SESSION['form']['upload']   ?? [];

        $token = current_access_token();
        if ($token === '') {
            error_log('form_review – kein Access-Token in Session');
            if (function_exists('flash_set')) {
                flash_set('danger', t('review.flash.no_token'));
            }
            header('Location: ' . url_with_lang('/index.php'));
            exit;
        }

        try {
            $pdo->beginTransaction();

            // ---------- applications: Datensatz laden ----------
            $st = $pdo->prepare("SELECT * FROM applications WHERE token = :t LIMIT 1");
            $st->execute([':t' => $token]);
            $appRow = $st->fetch(PDO::FETCH_ASSOC);
            if (!$appRow) {
                throw new RuntimeException(t('review.err.not_found_token'));
            }
            $appId = (int)$appRow['id'];

            // ---------- E-Mail / DOB / Status aktualisieren ----------
            $dobSql = $appRow['dob'] ?? null;
            if (!$dobSql && !empty($p['geburtsdatum']) && function_exists('norm_date_dmy_to_ymd')) {
                $dobTmp = norm_date_dmy_to_ymd((string)$p['geburtsdatum']);
                if ($dobTmp !== '') $dobSql = $dobTmp;
            }

            $accessEmail = trim((string)($_SESSION['access']['email'] ?? ''));
            $emailSql    = $accessEmail !== '' ? $accessEmail : ($appRow['email'] ?? null);

            $emailVerified = (int)($appRow['email_verified'] ?? 0);
            if (!empty($_SESSION['email_verified']) && $emailSql) {
                $emailVerified = 1;
            }

            $ipBin = null;
            if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
                $ipBin = inet_pton($_SERVER['REMOTE_ADDR']);
            }

            $upApp = $pdo->prepare("
                UPDATE applications
                SET email          = :e,
                    dob            = :d,
                    email_verified = :v,
                    status         = 'submitted',
                    submit_ip      = :ip
                WHERE id = :id
            ");
            $upApp->execute([
                ':e'  => $emailSql,
                ':d'  => $dobSql,
                ':v'  => $emailVerified,
                ':ip' => $ipBin,
                ':id' => $appId,
            ]);

            // ---------- personal ----------
            $applicantEmail = trim((string)($p['email'] ?? ''));
            $applicantEmail = ($applicantEmail !== '') ? $applicantEmail : null;

            $geburtsdatumDate = $dobSql;
            if (!$geburtsdatumDate && !empty($p['geburtsdatum']) && function_exists('norm_date_dmy_to_ymd')) {
                $dobTmp = norm_date_dmy_to_ymd((string)$p['geburtsdatum']);
                if ($dobTmp !== '') $geburtsdatumDate = $dobTmp;
            }

            // weitere_angaben: primär aus Session, fallback aus applications.data_json
            $weitereAngabenToSave = norm_space($p['weitere_angaben'] ?? '');
            if ($weitereAngabenToSave === '') {
                $fallback = datajson_get_personal($pdo, $token, 'weitere_angaben');
                if ($fallback !== null) $weitereAngabenToSave = $fallback;
            }
            $weitereAngabenToSave = ($weitereAngabenToSave !== '') ? $weitereAngabenToSave : null;

            $insPers = $pdo->prepare("
                INSERT INTO personal (
                    application_id,
                    name,
                    vorname,
                    geschlecht,
                    geburtsdatum,
                    geburtsort_land,
                    staatsang,
                    strasse,
                    plz,
                    wohnort,
                    telefon,
                    email,
                    weitere_angaben,
                    dsgvo_ok,
                    created_at,
                    updated_at
                ) VALUES (
                    :id,
                    :name,
                    :vorname,
                    :g,
                    :gebdat,
                    :ort,
                    :staat,
                    :strasse,
                    :plz,
                    :wohnort,
                    :tel,
                    :mail,
                    :weitere,
                    :dsgvo,
                    NOW(),
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    name            = VALUES(name),
                    vorname         = VALUES(vorname),
                    geschlecht      = VALUES(geschlecht),
                    geburtsdatum    = VALUES(geburtsdatum),
                    geburtsort_land = VALUES(geburtsort_land),
                    staatsang       = VALUES(staatsang),
                    strasse         = VALUES(strasse),
                    plz             = VALUES(plz),
                    wohnort         = VALUES(wohnort),
                    telefon         = VALUES(telefon),
                    email           = VALUES(email),
                    weitere_angaben = VALUES(weitere_angaben),
                    dsgvo_ok        = VALUES(dsgvo_ok),
                    updated_at      = NOW()
            ");

            $insPers->execute([
                ':id'      => $appId,
                ':name'    => (string)($p['name']            ?? ''),
                ':vorname' => (string)($p['vorname']         ?? ''),
                ':g'       => (string)($p['geschlecht']      ?? ''),
                ':gebdat'  => $geburtsdatumDate,
                ':ort'     => (string)($p['geburtsort_land'] ?? ''),
                ':staat'   => (string)($p['staatsang']       ?? ''),
                ':strasse' => (string)($p['strasse']         ?? ''),
                ':plz'     => (string)($p['plz']             ?? ''),
                ':wohnort' => (string)($p['wohnort']         ?? 'Oldenburg (Oldb)'),
                ':tel'     => (string)($p['telefon']         ?? ''),
                ':mail'    => $applicantEmail,
                ':weitere' => $weitereAngabenToSave,
                ':dsgvo'   => (isset($p['dsgvo_ok']) && $p['dsgvo_ok'] === '1') ? 1 : 0,
            ]);

            // ---------- contacts ----------
            $pdo->prepare("DELETE FROM contacts WHERE application_id = :id")
                ->execute([':id' => $appId]);

            $contacts = $p['contacts'] ?? [];
            if (is_array($contacts)) {
                $insC = $pdo->prepare("
                    INSERT INTO contacts (application_id, rolle, name, tel, mail, notiz, created_at)
                    VALUES (:id, :rolle, :name, :tel, :mail, :notiz, NOW())
                ");
                foreach ($contacts as $c) {
                    $nameC = trim((string)($c['name'] ?? ''));
                    $telC  = trim((string)($c['tel']  ?? ''));
                    $mailC = trim((string)($c['mail'] ?? ''));
                    $notiz = trim((string)($c['notiz']?? ''));
                    $rolle = trim((string)($c['rolle']?? ''));

                    if ($rolle === '' && $nameC === '' && $telC === '' && $mailC === '' && $notiz === '') continue;
                    if ($nameC === '') continue; // DB: name NOT NULL

                    $insC->execute([
                        ':id'    => $appId,
                        ':rolle' => $rolle,
                        ':name'  => $nameC,
                        ':tel'   => $telC,
                        ':mail'  => $mailC,
                        ':notiz' => $notiz,
                    ]);
                }
            }

            // ---------- school ----------
            global $INTERESSEN;

            $seit_monat = null;
            if (($s['seit_monat'] ?? '') !== '' && ctype_digit((string)$s['seit_monat'])) $seit_monat = (int)$s['seit_monat'];

            $seit_jahr = null;
            if (($s['seit_jahr'] ?? '') !== '' && ctype_digit((string)$s['seit_jahr'])) $seit_jahr = (int)$s['seit_jahr'];

            $jahre_in_de = null;
            if (($s['jahre_in_de'] ?? '') !== '' && ctype_digit((string)$s['jahre_in_de'])) $jahre_in_de = (int)$s['jahre_in_de'];

            $schule_herkunft = null;
            $sh = trim((string)($s['schule_herkunft'] ?? ''));
            if ($sh === 'ja' || $sh === 'nein') $schule_herkunft = $sh;

            $jahre_schule_herkunft = null;
            if (($s['jahre_schule_herkunft'] ?? '') !== '' && ctype_digit((string)$s['jahre_schule_herkunft'])) {
                $jahre_schule_herkunft = (int)$s['jahre_schule_herkunft'];
            }
            if ($schule_herkunft === 'nein') $jahre_schule_herkunft = null;

            $nivRaw = trim((string)($s['deutsch_niveau'] ?? ''));
            $allowedCodes = ['kein','A0','A1','A2','B1','B2','C1','C2'];
            $deutsch_niveau = null;
            if ($nivRaw !== '') {
                if (in_array($nivRaw, $allowedCodes, true)) $deutsch_niveau = $nivRaw;
                elseif (preg_match('/^(kein|A0|A1|A2|B1|B2|C1|C2)\b/u', $nivRaw, $m)) $deutsch_niveau = $m[1];
            }

            $interessenArr = $s['interessen'] ?? [];
            $interessenLabels = [];
            if (is_array($interessenArr)) {
                foreach ($interessenArr as $key) {
                    $keyStr = (string)$key;
                    $interessenLabels[] = $INTERESSEN[$keyStr] ?? $keyStr;
                }
            }
            $interessenStr = $interessenLabels ? implode(', ', $interessenLabels) : null;

            $schule_aktuell  = norm_space($s['schule_aktuell'] ?? '');
            $schule_freitext = norm_space($s['schule_freitext'] ?? '');
            $schule_label    = norm_space($s['schule_label'] ?? '');
            $klassenlehrer   = norm_space($s['klassenlehrer'] ?? '');
            $mail_lehrkraft  = norm_space($s['mail_lehrkraft'] ?? '');
            $seit_text       = norm_space($s['seit_text'] ?? '');
            $familiensprache = norm_space($s['familiensprache'] ?? '');

            $insSchool = $pdo->prepare("
                INSERT INTO school (
                    application_id,
                    schule_aktuell,
                    schule_freitext,
                    schule_label,
                    klassenlehrer,
                    mail_lehrkraft,
                    seit_monat,
                    seit_jahr,
                    seit_text,
                    jahre_in_de,
                    schule_herkunft,
                    jahre_schule_herkunft,
                    familiensprache,
                    deutsch_niveau,
                    interessen,
                    created_at,
                    updated_at
                ) VALUES (
                    :id,
                    :schule_aktuell,
                    :schule_freitext,
                    :schule_label,
                    :klassenlehrer,
                    :mail_lehrkraft,
                    :seit_monat,
                    :seit_jahr,
                    :seit_text,
                    :jahre_in_de,
                    :schule_herkunft,
                    :jahre_schule_herkunft,
                    :familiensprache,
                    :deutsch_niveau,
                    :interessen,
                    NOW(),
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    schule_aktuell        = VALUES(schule_aktuell),
                    schule_freitext       = VALUES(schule_freitext),
                    schule_label          = VALUES(schule_label),
                    klassenlehrer         = VALUES(klassenlehrer),
                    mail_lehrkraft        = VALUES(mail_lehrkraft),
                    seit_monat            = VALUES(seit_monat),
                    seit_jahr             = VALUES(seit_jahr),
                    seit_text             = VALUES(seit_text),
                    jahre_in_de           = VALUES(jahre_in_de),
                    schule_herkunft       = VALUES(schule_herkunft),
                    jahre_schule_herkunft = VALUES(jahre_schule_herkunft),
                    familiensprache       = VALUES(familiensprache),
                    deutsch_niveau        = VALUES(deutsch_niveau),
                    interessen            = VALUES(interessen),
                    updated_at            = NOW()
            ");

            $insSchool->execute([
                ':id'                   => $appId,
                ':schule_aktuell'        => $schule_aktuell !== '' ? $schule_aktuell : null,
                ':schule_freitext'       => $schule_freitext !== '' ? $schule_freitext : null,
                ':schule_label'          => $schule_label !== '' ? $schule_label : null,
                ':klassenlehrer'         => $klassenlehrer !== '' ? $klassenlehrer : null,
                ':mail_lehrkraft'        => $mail_lehrkraft !== '' ? $mail_lehrkraft : null,
                ':seit_monat'            => $seit_monat,
                ':seit_jahr'             => $seit_jahr,
                ':seit_text'             => $seit_text !== '' ? $seit_text : null,
                ':jahre_in_de'           => $jahre_in_de,
                ':schule_herkunft'       => $schule_herkunft,
                ':jahre_schule_herkunft' => $jahre_schule_herkunft,
                ':familiensprache'       => $familiensprache !== '' ? $familiensprache : null,
                ':deutsch_niveau'        => $deutsch_niveau,
                ':interessen'            => $interessenStr,
            ]);

            // ---------- audit_log ----------
            $meta = [
                'ua'      => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            ];
            $insAudit = $pdo->prepare("
                INSERT INTO audit_log (application_id, event, meta_json, created_at)
                VALUES (:id, 'submitted', :meta, NOW())
            ");
            $insAudit->execute([
                ':id'   => $appId,
                ':meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            ]);

            $pdo->commit();

            // Nach Submit: auf Status-Seite umleiten (Session NICHT komplett killen)
            $_SESSION['application_readonly'] = true;
            $_SESSION['application_submitted'] = [
                'app_id' => $appId,
                'ts'     => time(),
            ];

            // Optional: Form-Daten wegwerfen, damit nichts mehr „editierbar“ ist
            unset($_SESSION['form'], $_SESSION['last_save']);

            // Token/Access in der Session lassen (für Status/PDF)
            session_regenerate_id(true);

            header('Location: ' . url_with_lang('/form_status.php'));
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('form_review – submit error: ' . $e->getMessage());
            if (function_exists('flash_set')) {
                flash_set('danger', t('review.flash.submit_error'));
            }
        }
    }
}

// ===== Daten für Anzeige =====
$p = $_SESSION['form']['personal'] ?? [];
$s = $_SESSION['form']['school']   ?? [];
$u = $_SESSION['form']['upload']   ?? [];

// Upload-Status: erst aus Session, dann – falls möglich – aus DB
$hasZeugnis    = !empty($u['zeugnis'] ?? null);
$hasLebenslauf = !empty($u['lebenslauf'] ?? null);

try {
    $token = current_access_token()
        ?: (string)($_SESSION['access']['token'] ?? ($_SESSION['access_token'] ?? ''));

    if ($token !== '') {
        $pdo = db();
        $st  = $pdo->prepare("SELECT id FROM applications WHERE token = :t LIMIT 1");
        $st->execute([':t' => $token]);
        $appId = $st->fetchColumn();

        if ($appId) {
            $st2 = $pdo->prepare("SELECT typ FROM uploads WHERE application_id = :id");
            $st2->execute([':id' => $appId]);
            while ($row = $st2->fetch(PDO::FETCH_ASSOC)) {
                $typ = (string)($row['typ'] ?? '');
                if ($typ === 'zeugnis') $hasZeugnis = true;
                elseif ($typ === 'lebenslauf') $hasLebenslauf = true;
            }
        }
    }
} catch (Throwable $e) {
    error_log('form_review uploads-check: ' . $e->getMessage());
}

// Header
$title     = t('review.page_title');
$html_lang = html_lang();
$html_dir  = html_dir();

require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';

// Geschlecht-Label (i18n)
$gKey = (string)($p['geschlecht'] ?? '');
$gLbl = ($gKey !== '') ? t('review.gender.' . $gKey) : t('review.value.empty');
if ($gLbl === 'review.gender.' . $gKey) { // falls Key nicht existiert
    $gLbl = ($gKey !== '') ? $gKey : t('review.value.empty');
}

// Schule Anzeige (nutze Session-Label/Freitext)
$schoolDisplay = t('review.value.empty');
if (!empty($s['schule_label'])) $schoolDisplay = (string)$s['schule_label'];
elseif (!empty($s['schule_freitext'])) $schoolDisplay = (string)$s['schule_freitext'];
elseif (!empty($s['schule_aktuell'])) $schoolDisplay = (string)$s['schule_aktuell'];

// Seit wann an Schule Anzeige
$sinceDisplay = t('review.value.empty');
if (!empty($s['seit_wann_schule'])) {
    $sinceDisplay = (string)$s['seit_wann_schule'];
} else {
    $parts = [];
    if (!empty($s['seit_monat'])) $parts[] = (string)$s['seit_monat'];
    if (!empty($s['seit_jahr']))  $parts[] = (string)$s['seit_jahr'];
    $sinceDisplay = $parts ? implode('.', $parts) : t('review.value.empty');
}

// Interessen Anzeige
global $INTERESSEN;
$interessenLbls = [];
if (is_array($s['interessen'] ?? null)) {
    foreach (($s['interessen'] ?? []) as $k) {
        $kStr = (string)$k;
        $interessenLbls[] = $INTERESSEN[$kStr] ?? $kStr;
    }
}
$interessenDisplay = $interessenLbls ? implode(', ', $interessenLbls) : t('review.value.empty');

// Neue Felder Schritt 1
$weitereAngaben = norm_space($p['weitere_angaben'] ?? '');

// Badges Upload
$badgeUploaded = '<span class="badge text-bg-success">'.h(t('review.badge.uploaded')).'</span>';
$badgeNot      = '<span class="badge text-bg-secondary">'.h(t('review.badge.not_uploaded')).'</span>';

?>
<div class="container py-4">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
        <div>
          <h1 class="h4 mb-1"><?= h(t('review.h1')) ?></h1>
          <div class="text-muted"><?= h(t('review.subhead')) ?></div>
        </div>
      </div>

      <?php if ($readonly): ?>
        <div class="alert alert-warning">
          <?= h(t('review.readonly_alert')) ?>
        </div>
      <?php endif; ?>

      <div class="alert alert-info">
        <p class="mb-2"><?= h(t('review.info.p1')) ?></p>
        <p class="mb-2"><?= t('review.info.p2') /* enthält <strong> */ ?></p>
        <p class="mb-2"><?= t('review.info.p3') /* enthält <strong> */ ?></p>
        <p class="mb-1"><?= h(t('review.info.p4')) ?></p>
        <ul class="mb-0">
          <li><?= h(t('review.info.li1')) ?></li>
        </ul>
      </div>

      <div class="accordion" id="reviewAccordion">

        <!-- Persönliche Daten -->
        <div class="accordion-item">
          <h2 class="accordion-header" id="hPersonal">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#cPersonal" aria-expanded="true" aria-controls="cPersonal">
              <?= h(t('review.acc.personal')) ?>
            </button>
          </h2>
          <div id="cPersonal" class="accordion-collapse collapse show" aria-labelledby="hPersonal" data-bs-parent="#reviewAccordion">
            <div class="accordion-body">
              <div class="row g-3">
                <?= dl_row(t('review.lbl.name'), show_val((string)($p['name'] ?? ''))) ?>
                <?= dl_row(t('review.lbl.vorname'), show_val((string)($p['vorname'] ?? ''))) ?>
                <?= dl_row(t('review.lbl.geschlecht'), h($gLbl)) ?>
                <?= dl_row(t('review.lbl.geburtsdatum'), show_val((string)($p['geburtsdatum'] ?? ''))) ?>
                <?= dl_row(t('review.lbl.geburtsort'), show_val((string)($p['geburtsort_land'] ?? ''))) ?>
                <?= dl_row(t('review.lbl.staatsang'), show_val((string)($p['staatsang'] ?? ''))) ?>
                <?= dl_row(t('review.lbl.strasse'), show_val((string)($p['strasse'] ?? ''))) ?>
                <?= dl_row(
                      t('review.lbl.plz_ort'),
                      show_val(trim((string)($p['plz'] ?? '') . ' ' . (string)($p['wohnort'] ?? 'Oldenburg (Oldb)')))
                    ) ?>
                <?= dl_row(t('review.lbl.telefon'), show_val((string)($p['telefon'] ?? ''))) ?>
                <?= dl_row(t('review.lbl.email'), show_val((string)($p['email'] ?? ''))) ?>

                <div class="col-12">
                  <div class="small text-muted"><?= h(t('review.lbl.weitere_angaben')) ?></div>
                  <div class="fw-semibold"><?= $weitereAngaben !== '' ? nl2br(h($weitereAngaben)) : h(t('review.value.empty')) ?></div>
                </div>
              </div>

              <hr class="my-4">

              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="fw-semibold"><?= h(t('review.contacts.title')) ?></div>
                <div class="text-muted small"><?= h(t('review.contacts.optional')) ?></div>
              </div>

              <?php
                $contacts = $p['contacts'] ?? [];
                $hasContacts = is_array($contacts) && count($contacts) > 0;
              ?>
              <?php if ($hasContacts): ?>
                <div class="table-responsive">
                  <table class="table table-sm align-middle">
                    <thead class="table-light">
                      <tr>
                        <th style="width:14rem"><?= h(t('review.contacts.th.role')) ?></th>
                        <th style="width:22rem"><?= h(t('review.contacts.th.name')) ?></th>
                        <th style="width:16rem"><?= h(t('review.contacts.th.tel')) ?></th>
                        <th style="width:22rem"><?= h(t('review.contacts.th.mail')) ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($contacts as $c): ?>
                        <?php
                          $rolle = trim((string)($c['rolle'] ?? ''));
                          $name  = trim((string)($c['name']  ?? ''));
                          $tel   = trim((string)($c['tel']   ?? ''));
                          $mail  = trim((string)($c['mail']  ?? ''));
                          $notiz = trim((string)($c['notiz'] ?? ''));

                          if ($rolle === '' && $name === '' && $tel === '' && $mail === '' && $notiz === '') continue;
                        ?>
                        <tr>
                          <td><?= h($rolle) ?></td>
                          <td><?= h($name) ?></td>
                          <td><?= h($tel) ?></td>
                          <td><?= h($mail) ?></td>
                        </tr>
                        <tr class="table-light">
                          <td colspan="4">
                            <span class="text-muted small"><?= h(t('review.contacts.note')) ?></span>
                            <span class="fw-semibold"><?= $notiz !== '' ? h($notiz) : h(t('review.value.empty')) ?></span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="text-muted"><?= h(t('review.contacts.none')) ?></div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Schule & Interessen -->
        <div class="accordion-item">
          <h2 class="accordion-header" id="hSchool">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cSchool" aria-expanded="false" aria-controls="cSchool">
              <?= h(t('review.acc.school')) ?>
            </button>
          </h2>
          <div id="cSchool" class="accordion-collapse collapse" aria-labelledby="hSchool" data-bs-parent="#reviewAccordion">
            <div class="accordion-body">
              <div class="row g-3">
                <?= dl_row(t('review.lbl.school_current'), show_val($schoolDisplay), 'col-12') ?>
                <?= dl_row(t('review.lbl.klassenlehrer'), show_val((string)($s['klassenlehrer'] ?? ''))) ?>
                <?= dl_row(t('review.lbl.mail_lehrkraft'), show_val((string)($s['mail_lehrkraft'] ?? ''))) ?>
                <?= dl_row(t('review.lbl.since'), show_val($sinceDisplay)) ?>
                <?= dl_row(t('review.lbl.years_de'), show_val((string)($s['jahre_in_de'] ?? ''))) ?>
                <?= dl_row(t('review.lbl.family_lang'), show_val((string)($s['familiensprache'] ?? ''))) ?>
                <?= dl_row(t('review.lbl.de_level'), show_val((string)($s['deutsch_niveau'] ?? ''))) ?>
                <?= dl_row(t('review.lbl.school_origin'), show_val((string)($s['schule_herkunft'] ?? ''))) ?>

                <?php if (($s['schule_herkunft'] ?? '') === 'ja'): ?>
                  <?= dl_row(t('review.lbl.years_origin'), show_val((string)($s['jahre_schule_herkunft'] ?? ''))) ?>
                <?php endif; ?>

                <?= dl_row(t('review.lbl.interests'), show_val($interessenDisplay), 'col-12') ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Unterlagen -->
        <div class="accordion-item">
          <h2 class="accordion-header" id="hUploads">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cUploads" aria-expanded="false" aria-controls="cUploads">
              <?= h(t('review.acc.uploads')) ?>
            </button>
          </h2>
          <div id="cUploads" class="accordion-collapse collapse" aria-labelledby="hUploads" data-bs-parent="#reviewAccordion">
            <div class="accordion-body">
              <div class="row g-3">
                <?= dl_row(t('review.lbl.zeugnis'), $hasZeugnis ? $badgeUploaded : $badgeNot) ?>
                <?= dl_row(t('review.lbl.lebenslauf'), $hasLebenslauf ? $badgeUploaded : $badgeNot) ?>
                <?= dl_row(
                      t('review.lbl.later'),
                      h((($u['zeugnis_spaeter'] ?? '0') === '1') ? t('review.yes') : t('review.no')),
                      'col-12'
                    ) ?>
              </div>
            </div>
          </div>
        </div>

      </div>

      <?php if ($readonly): ?>
        <div class="mt-4 d-flex gap-2 flex-wrap">
          <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="reset">
            <button class="btn btn-outline-secondary"><?= h(t('review.btn.home')) ?></button>
          </form>

          <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="new_application">
            <button class="btn btn-primary"><?= h(t('review.btn.newapp')) ?></button>
          </form>
        </div>
      <?php else: ?>
        <form method="post" action="" class="mt-4 d-flex gap-2 flex-wrap">
          <?php csrf_field(); ?>
          <input type="hidden" name="action" value="submit">
          <a href="<?= h(url_with_lang('/form_upload.php')) ?>" class="btn btn-outline-secondary"><?= h(t('review.btn.back')) ?></a>
          <button class="btn btn-success"><?= h(t('review.btn.submit')) ?></button>
        </form>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/partials/footer.php'; ?>
