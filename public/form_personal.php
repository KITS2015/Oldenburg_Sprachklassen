<?php
// public/form_personal.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/functions_form.php'; // DB-/Save-Helper

// --- Sprache / RTL aus _common.php ---
$lang     = current_lang();
$html_lang = $lang;
$html_dir  = is_rtl_lang($lang) ? 'rtl' : 'ltr';

// --- Übersetzungen nur für diese Seite (Schritt 1) ---
// (Später kannst du das in /app/i18n/*.php auslagern – aber so ist es erstmal komplett lauffähig.)
$TT = [
  'de' => [
    'step_title' => 'Schritt 1/4 – Persönliche Daten',
    'required_hint' => 'Pflichtfelder sind blau am Rahmen hervorgehoben.',
    'errors_hint' => 'Bitte prüfen Sie die markierten Felder.',

    'email_login_active' => 'E-Mail-Login aktiv:',
    'email_login_text' => 'Angemeldet mit der E-Mail-Adresse',
    'email_login_text2' => 'Diese E-Mail wird nur für den Zugangscode (Access-Token) und zum Wiederfinden Ihrer Bewerbung verwendet.',
    'email_login_text3' => 'Unten können Sie eine E-Mail-Adresse der Schülerin / des Schülers angeben (falls vorhanden).',

    'noemail_hint_title' => 'Hinweis (ohne E-Mail):',
    'noemail_hint_text' => 'Bitte notieren/fotografieren Sie Ihren Zugangscode (Access-Token), den Sie nach dem Speichern auf dieser Seite angezeigt bekommen.',
    'noemail_hint_text2' => 'Ohne verifizierte E-Mail ist eine Wiederherstellung nur mit Token + Geburtsdatum möglich.',

    'name' => 'Name',
    'vorname' => 'Vorname',
    'gender' => 'Geschlecht',
    'male' => 'männlich',
    'female' => 'weiblich',
    'diverse' => 'divers',

    'born_on' => 'Geboren am',
    'dmy' => '(TT.MM.JJJJ)',
    'birthplace' => 'Geburtsort / Geburtsland',
    'nationality' => 'Staatsangehörigkeit',
    'street' => 'Straße, Nr.',
    'plz' => 'PLZ',
    'plz_choose' => '– bitte wählen –',
    'plz_hint' => 'Nur Oldenburg (Oldb).',
    'city' => 'Wohnort',

    'phone' => 'Telefonnummer',
    'phone_area' => 'Vorwahl mit/ohne 0',
    'phone_number' => 'Rufnummer',

    'student_email_label' => 'E-Mail-Adresse der Schülerin / des Schülers (optional, keine IServ-Adresse)',
    'student_email_hint1' => 'Diese E-Mail gehört zur Schülerin / zum Schüler (falls vorhanden)',
    'student_email_hint2' => 'und ist unabhängig von der E-Mail-Adresse für den Zugangscode.',

    'contacts_title' => 'Weitere Kontaktdaten',
    'contacts_sub' => '(z. B. Eltern, Betreuer, Einrichtung)',
    'contacts_err' => 'Bitte prüfen Sie die zusätzlichen Kontakte.',
    'role' => 'Rolle',
    'name_org' => 'Name / Einrichtung',
    'tel' => 'Telefon',
    'email' => 'E-Mail',
    'note' => 'Notiz',
    'add_contact' => '+ Kontakt hinzufügen',
    'remove_contact' => 'Kontakt entfernen',

    'roles' => [
      ''            => '–',
      'Mutter'      => 'Mutter',
      'Vater'       => 'Vater',
      'Elternteil'  => 'Elternteil',
      'Betreuer*in' => 'Betreuer*in',
      'Einrichtung' => 'Einrichtung',
      'Sonstiges'   => 'Sonstiges',
    ],

    'more_info' => 'Weitere Angaben (z. B. Förderstatus):',
    'more_info_ph' => 'Hier können Sie z. B. besonderen Förderbedarf, sonderpädagogische Unterstützungsbedarfe oder weitere Hinweise angeben.',
    'more_info_hint' => 'Optional. Maximal 1500 Zeichen.',

    'privacy' => 'Ich habe die Datenschutzhinweise gelesen und bin einverstanden.',
    'privacy_link' => 'Datenschutzhinweise',

    'cancel' => 'Abbrechen',
    'next' => 'Weiter',

    'age_hint' => 'Hinweis: Sind Sie am 30.09.%Y% unter 16 oder über 18 Jahre alt, können Sie nicht in die Sprachlernklasse der BBS aufgenommen werden. Bitte bewerben Sie sich für eine andere Klasse hier:',
    'age_confirm' => 'Hinweis: Sind Sie am 30.09.%Y% unter 16 oder über 18 Jahre alt, können Sie nicht in die Sprachlernklasse der BBS aufgenommen werden. Bitte bewerben Sie sich für eine andere Klasse einer BBS hier:\n%URL%',
    'redirect_title' => 'Weiterleitung',
    'req_required' => 'Erforderlich.',
    'req_letters' => 'Bitte nur Buchstaben.',
    'req_gender' => 'Bitte wählen Sie ein Geschlecht aus.',
    'req_dmy' => 'TT.MM.JJJJ',
    'req_invalid_date' => 'Ungültiges Datum.',
    'plz_only' => 'Nur PLZ aus Oldenburg (26121–26135).',
    'area_len' => 'Vorwahl 2–6 Ziffern.',
    'num_len' => 'Rufnummer 3–12 Ziffern.',
    'bad_email' => 'Ungültige E-Mail.',
    'no_iserv' => 'Bitte private E-Mail (keine IServ).',
    'more_len' => 'Bitte maximal 1500 Zeichen.',
    'contact_missing_name' => 'Name/Bezeichnung fehlt',
    'contact_need_tel_or_mail' => 'Telefon ODER E-Mail angeben',
    'contact_mail_invalid' => 'E-Mail ungültig',
    'contact_tel_invalid' => 'Telefon ungültig',
    'contacts_check' => 'Bitte prüfen Sie die zusätzlichen Kontakte.',
  ],
];

// Fallback: wenn Sprache nicht vorhanden -> de
$tx = $TT[$lang] ?? $TT['de'];

function t(array $tx, string $key): string {
    return (string)($tx[$key] ?? $key);
}
function t_roles(array $tx): array {
    return (array)($tx['roles'] ?? []);
}

$errors = [];
$kontakt_errors = [];

// --- Modus erkennen ---
$modeParam    = (string)($_GET['mode'] ?? '');
$noEmailMode  = ($modeParam === 'noemail');
$emailMode    = ($modeParam === 'email');

// --- E-Mail-Flow absichern & vorbereiten ---
if ($emailMode) {
    if (empty($_SESSION['access']) || ($_SESSION['access']['mode'] ?? '') !== 'email' || empty($_SESSION['access']['email'])) {
        header('Location: /index.php');
        exit;
    }
    if (function_exists('current_access_token') && function_exists('issue_access_token')) {
        if (current_access_token() === '') { issue_access_token(); }
    }
}

// Helper
function age_on_reference(string $dmy, int $year): ?int {
    $bd = DateTimeImmutable::createFromFormat('d.m.Y', $dmy);
    if (!$bd) return null;
    $ref = DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-09-30', $year));
    if (!$ref) return null;
    $diff = $bd->diff($ref);
    return $diff->y;
}
function field_error(string $key, array $errors): string {
    if (empty($errors[$key])) return '';
    return '<div class="invalid-feedback d-block">'.h((string)$errors[$key]).'</div>';
}

$refYear = (int)date('Y');

// ---------- POST: Validierung & Speichern ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

    $req = [
        'name','vorname','geschlecht','geburtsdatum','geburtsort_land','staatsang','strasse','plz',
        'telefon_vorwahl','telefon_nummer','dsgvo_ok'
    ];

    foreach ($req as $f) {
        if ($f === 'dsgvo_ok') {
            if (($_POST['dsgvo_ok'] ?? '') !== '1') {
                $errors['dsgvo_ok'] = $tx['req_required'];
            }
        } else {
            if (empty($_POST[$f])) {
                $errors[$f] = $tx['req_required'];
            }
        }
    }

    if (!isset($errors['name']) && !preg_match('/^[\p{L} .\'-]+$/u', (string)$_POST['name'])) {
        $errors['name'] = $tx['req_letters'];
    }
    if (!isset($errors['vorname']) && !preg_match('/^[\p{L} .\'-]+$/u', (string)$_POST['vorname'])) {
        $errors['vorname'] = $tx['req_letters'];
    }
    if (!in_array(($_POST['geschlecht'] ?? ''), ['m','w','d'], true)) {
        $errors['geschlecht'] = $tx['req_gender'];
    }

    if (!isset($errors['geburtsdatum'])) {
        if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', (string)$_POST['geburtsdatum'])) {
            $errors['geburtsdatum'] = $tx['req_dmy'];
        } else {
            [$tDay,$tMon,$tYear] = explode('.', (string)$_POST['geburtsdatum']);
            if (!checkdate((int)$tMon,(int)$tDay,(int)$tYear)) {
                $errors['geburtsdatum'] = $tx['req_invalid_date'];
            }
        }
    }

    // Alters-Plausibilität (serverseitiger Fallback)
    if (!isset($errors['geburtsdatum'])) {
        $age = age_on_reference((string)$_POST['geburtsdatum'], $refYear);
        if ($age !== null && ($age < 16 || $age > 18)) {
            $msg = str_replace(
                ['%Y%','%URL%'],
                [(string)$refYear, 'https://bbs-ol.de/'],
                (string)$tx['age_confirm']
            );
            echo '<!doctype html><html lang="'.h($lang).'" dir="'.h($html_dir).'"><head><meta charset="utf-8"><title>'.h((string)$tx['redirect_title']).'</title></head><body>';
            echo '<script>';
            echo 'if (confirm('.json_encode($msg, JSON_UNESCAPED_UNICODE).')) {';
            echo '  window.location.href = "https://bbs-ol.de/";';
            echo '} else { history.back(); }';
            echo '</script>';
            echo '</body></html>';
            exit;
        }
    }

    // PLZ-Whitelist Oldenburg
    $plzWhitelist = ['26121','26122','26123','26125','26127','26129','26131','26133','26135'];
    if (!isset($errors['plz'])) {
        $plz = (string)($_POST['plz'] ?? '');
        if (!in_array($plz, $plzWhitelist, true)) {
            $errors['plz'] = $tx['plz_only'];
        }
    }

    // Telefon normalisieren
    $telefon_pretty = '';
    $telefon_e164   = '';
    if (!isset($errors['telefon_vorwahl']) || !isset($errors['telefon_nummer'])) {
        $vv_raw = (string)($_POST['telefon_vorwahl'] ?? '');
        $vn_raw = (string)($_POST['telefon_nummer'] ?? '');
        $vv = preg_replace('/\D+/', '', $vv_raw ?? '');
        $vn = preg_replace('/\D+/', '', $vn_raw ?? '');

        if (!isset($errors['telefon_vorwahl'])) {
            if ($vv === '' || strlen($vv) < 2 || strlen($vv) > 6) {
                $errors['telefon_vorwahl'] = $tx['area_len'];
            }
        }
        if (!isset($errors['telefon_nummer'])) {
            if ($vn === '' || strlen($vn) < 3 || strlen($vn) > 12) {
                $errors['telefon_nummer'] = $tx['num_len'];
            }
        }
        if (!isset($errors['telefon_vorwahl']) && !isset($errors['telefon_nummer'])) {
            $vv_norm = ltrim($vv, '0');
            if ($vv_norm === '') $vv_norm = $vv;
            $telefon_e164   = '+49' . $vv_norm . $vn;
            $telefon_pretty = '+49 ' . $vv_norm . ' ' . $vn;
        }
    }

    // E-Mail optional
    $email_raw = trim((string)($_POST['email'] ?? ''));
    if ($email_raw !== '') {
        if (!filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = $tx['bad_email'];
        } elseif (preg_match('/@iserv\.de$/i', $email_raw)) {
            $errors['email'] = $tx['no_iserv'];
        }
    }

    // Weitere Angaben optional
    $weitere_angaben_raw = trim((string)($_POST['weitere_angaben'] ?? ''));
    if ($weitere_angaben_raw !== '') {
        $weitere_angaben_raw = str_replace("\0", '', $weitere_angaben_raw);
        if (mb_strlen($weitere_angaben_raw) > 1500) {
            $errors['weitere_angaben'] = $tx['more_len'];
        }
    }

    // Zusatzkontakte
    $roles  = $_POST['kontakt_role'] ?? [];
    $names  = $_POST['kontakt_name'] ?? [];
    $tels   = $_POST['kontakt_tel']  ?? [];
    $mails  = $_POST['kontakt_mail'] ?? [];
    $notes  = $_POST['kontakt_notiz']?? [];

    $contacts = [];
    $rowCount = max(count($roles), count($names), count($tels), count($mails), count($notes));

    for ($i=0; $i<$rowCount; $i++) {
        $role = trim((string)($roles[$i]  ?? ''));
        $name = trim((string)($names[$i]  ?? ''));
        $tel  = trim((string)($tels[$i]   ?? ''));
        $mail = trim((string)($mails[$i]  ?? ''));
        $note = trim((string)($notes[$i]  ?? ''));

        $isEmpty = ($role==='' && $name==='' && $tel==='' && $mail==='' && $note==='');
        if ($isEmpty) continue;

        $rowErr = [];
        if ($name === '') $rowErr[] = $tx['contact_missing_name'];
        if ($tel === '' && $mail === '') $rowErr[] = $tx['contact_need_tel_or_mail'];
        if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) $rowErr[] = $tx['contact_mail_invalid'];
        if ($tel  !== '' && !preg_match('/^[0-9 +\/()\-]+$/', $tel))    $rowErr[] = $tx['contact_tel_invalid'];
        if ($rowErr) $kontakt_errors[$i] = $rowErr;

        $contacts[] = ['rolle'=>$role,'name'=>$name,'tel'=>$tel,'mail'=>$mail,'notiz'=>$note];
    }
    if ($kontakt_errors) $errors['kontakte'] = $tx['contacts_check'];

    // Speichern
    if (!$errors) {
        $_SESSION['form']['personal'] = [
            'name'            => trim((string)$_POST['name']),
            'vorname'         => trim((string)$_POST['vorname']),
            'geschlecht'      => (string)$_POST['geschlecht'],
            'geburtsdatum'    => (string)$_POST['geburtsdatum'],
            'geburtsort_land' => trim((string)$_POST['geburtsort_land']),
            'staatsang'       => trim((string)$_POST['staatsang']),
            'strasse'         => trim((string)$_POST['strasse']),
            'plz'             => (string)$_POST['plz'],
            'wohnort'         => $_POST['wohnort'] ?? 'Oldenburg (Oldb)',
            'telefon'         => $telefon_pretty,
            'telefon_e164'    => $telefon_e164,
            'telefon_vorwahl' => (string)($_POST['telefon_vorwahl'] ?? ''),
            'telefon_nummer'  => (string)($_POST['telefon_nummer'] ?? ''),
            'email'           => $email_raw,
            'contacts'        => $contacts,
            'weitere_angaben' => $weitere_angaben_raw,
            'dsgvo_ok'        => (($_POST['dsgvo_ok'] ?? '') === '1' ? '1' : '0'),
        ];

        $save = save_scope_allow_noemail('personal', $_SESSION['form']['personal']);
        $_SESSION['last_save'] = $save;

        if (function_exists('flash_set')) {
            if ($save['ok']) {
                $tokenToShow = $save['token'] ?? current_access_token();
                flash_set('success', 'Daten gespeichert. Access-Token: ' . $tokenToShow);
            } elseif (($save['err'] ?? '') === 'nur Session (DOB fehlt)') {
                flash_set('info', 'Daten zwischengespeichert. Mit Geburtsdatum werden sie dauerhaft gesichert.');
            } else {
                flash_set('warning', 'Daten gespeichert (Session). Hinweis: '. ($save['err'] ?? ''));
            }
        }

        header('Location: /form_school.php');
        exit;
    }
}

// ---------- Header ----------
$title = (string)$tx['step_title'];
require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';

// Vorbelegung Kontakte
$prevKontakte = $_SESSION['form']['personal']['contacts'] ?? [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rc = max(
        count($_POST['kontakt_role'] ?? []),
        count($_POST['kontakt_name'] ?? []),
        count($_POST['kontakt_tel']  ?? []),
        count($_POST['kontakt_mail'] ?? []),
        count($_POST['kontakt_notiz']?? [])
    );
    $prevKontakte = [];
    for ($i=0; $i<$rc; $i++) {
        $prevKontakte[] = [
            'rolle' => $_POST['kontakt_role'][$i] ?? '',
            'name'  => $_POST['kontakt_name'][$i] ?? '',
            'tel'   => $_POST['kontakt_tel'][$i]  ?? '',
            'mail'  => $_POST['kontakt_mail'][$i] ?? '',
            'notiz' => $_POST['kontakt_notiz'][$i]?? '',
        ];
    }
}
if (!$prevKontakte) $prevKontakte = [['rolle'=>'','name'=>'','tel'=>'','mail'=>'','notiz'=>'']];

$prevWeitereAngaben = $_SESSION['form']['personal']['weitere_angaben'] ?? ($_POST['weitere_angaben'] ?? '');
?>
<div class="container py-4">
  <?php if (function_exists('flash_render')) { flash_render(); } ?>

  <?php if ($emailMode): ?>
    <div class="alert alert-success">
      <strong><?= h($tx['email_login_active']) ?></strong><br>
      <?= h($tx['email_login_text']) ?> <code><?= h((string)$_SESSION['access']['email']) ?></code>.<br>
      <?= h($tx['email_login_text2']) ?><br>
      <?= h($tx['email_login_text3']) ?>
    </div>
  <?php endif; ?>

  <?php if ($noEmailMode): ?>
    <div class="alert alert-warning">
      <strong><?= h($tx['noemail_hint_title']) ?></strong>
      <?= h($tx['noemail_hint_text']) ?><br>
      <?= h($tx['noemail_hint_text2']) ?>
    </div>
  <?php endif; ?>

  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-2"><?= h($tx['step_title']) ?></h1>
      <div class="text-muted small mb-3"><?= h($tx['required_hint']) ?></div>

      <?php if ($errors): ?>
        <div class="alert alert-danger"><?= h($tx['errors_hint']) ?></div>
      <?php endif; ?>

      <form method="post" action="" novalidate class="mt-3" id="personalForm">
        <?php csrf_field(); ?>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label"><?= h($tx['name']) ?></label>
            <input name="name" class="form-control is-required<?= has_err('name',$errors) ?>" value="<?= old('name','personal') ?>" required>
            <?= field_error('name', $errors) ?>
          </div>
          <div class="col-md-6">
            <label class="form-label"><?= h($tx['vorname']) ?></label>
            <input name="vorname" class="form-control is-required<?= has_err('vorname',$errors) ?>" value="<?= old('vorname','personal') ?>" required>
            <?= field_error('vorname', $errors) ?>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label d-block"><?= h($tx['gender']) ?></label>
            <?php $g = $_SESSION['form']['personal']['geschlecht'] ?? ($_POST['geschlecht'] ?? ''); ?>
            <?php $gErr = !empty($errors['geschlecht']); ?>

            <div class="<?= $gErr ? 'border border-danger rounded p-2' : 'is-required-group' ?>" role="group" aria-label="Geschlecht">
              <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="geschlecht" id="g_m" value="m" <?= $g==='m'?'checked':''; ?> required>
                <label class="btn btn-outline-primary" for="g_m"><?= h($tx['male']) ?></label>

                <input type="radio" class="btn-check" name="geschlecht" id="g_w" value="w" <?= $g==='w'?'checked':''; ?>>
                <label class="btn btn-outline-primary" for="g_w"><?= h($tx['female']) ?></label>

                <input type="radio" class="btn-check" name="geschlecht" id="g_d" value="d" <?= $g==='d'?'checked':''; ?>>
                <label class="btn btn-outline-primary" for="g_d"><?= h($tx['diverse']) ?></label>
              </div>
            </div>

            <?= field_error('geschlecht', $errors) ?>
          </div>

          <div class="col-md-6">
            <label class="form-label"><?= h($tx['born_on']) ?> <span class="text-muted"><?= h($tx['dmy']) ?></span></label>
            <input id="geburtsdatum" name="geburtsdatum" class="form-control is-required<?= has_err('geburtsdatum',$errors) ?>" placeholder="TT.MM.JJJJ" value="<?= old('geburtsdatum','personal') ?>" required>
            <?= field_error('geburtsdatum', $errors) ?>
            <div class="form-text">
              <?= h(str_replace('%Y%', (string)$refYear, (string)$tx['age_hint'])) ?>
              <a href="https://bbs-ol.de/" target="_blank" rel="noopener">https://bbs-ol.de/</a>
            </div>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label"><?= h($tx['birthplace']) ?></label>
            <input name="geburtsort_land" class="form-control is-required<?= has_err('geburtsort_land',$errors) ?>" value="<?= old('geburtsort_land','personal') ?>" required>
            <?= field_error('geburtsort_land', $errors) ?>
          </div>
          <div class="col-md-6">
            <label class="form-label"><?= h($tx['nationality']) ?></label>
            <input name="staatsang" class="form-control is-required<?= has_err('staatsang',$errors) ?>" value="<?= old('staatsang','personal') ?>" required>
            <?= field_error('staatsang', $errors) ?>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label"><?= h($tx['street']) ?></label>
            <input name="strasse" class="form-control is-required<?= has_err('strasse',$errors) ?>" value="<?= old('strasse','personal') ?>" required>
            <?= field_error('strasse', $errors) ?>
          </div>

          <div class="col-md-3">
            <label class="form-label"><?= h($tx['plz']) ?></label>
            <?php
              $plzList = ['26121','26122','26123','26125','26127','26129','26131','26133','26135'];
              // ACHTUNG: old() ist escaped -> für Vergleich brauchen wir raw
              $rawPlz = (string)($_SESSION['form']['personal']['plz'] ?? ($_POST['plz'] ?? ''));
            ?>
            <select name="plz" class="form-select is-required<?= has_err('plz',$errors) ?>" required>
              <option value=""><?= h($tx['plz_choose']) ?></option>
              <?php foreach ($plzList as $plz): ?>
                <option value="<?= h($plz) ?>" <?= ($plz === $rawPlz) ? 'selected' : '' ?>><?= h($plz) ?></option>
              <?php endforeach; ?>
            </select>
            <?= field_error('plz', $errors) ?>
            <div class="form-text"><?= h($tx['plz_hint']) ?></div>
          </div>

          <div class="col-md-3">
            <label class="form-label"><?= h($tx['city']) ?></label>
            <input name="wohnort" class="form-control" value="<?= h($_SESSION['form']['personal']['wohnort'] ?? 'Oldenburg (Oldb)') ?>" readonly>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label"><?= h($tx['phone']) ?></label>
            <div class="row g-2">
              <div class="col-5 col-sm-4">
                <div class="input-group">
                  <span class="input-group-text">+49</span>
                  <input
                    name="telefon_vorwahl"
                    class="form-control is-required<?= has_err('telefon_vorwahl',$errors) ?>"
                    inputmode="numeric"
                    pattern="^0?\d{2,6}$"
                    placeholder="(0)441"
                    value="<?= h($_SESSION['form']['personal']['telefon_vorwahl'] ?? ($_POST['telefon_vorwahl'] ?? '')) ?>"
                    required>
                </div>
                <?= field_error('telefon_vorwahl', $errors) ?>
                <div class="form-text"><?= h($tx['phone_area']) ?></div>
              </div>

              <div class="col-7 col-sm-8">
                <input
                  name="telefon_nummer"
                  class="form-control is-required<?= has_err('telefon_nummer',$errors) ?>"
                  inputmode="numeric"
                  pattern="^\d[\d\s\-\/()]{2,}$"
                  placeholder="123456"
                  value="<?= h($_SESSION['form']['personal']['telefon_nummer'] ?? ($_POST['telefon_nummer'] ?? '')) ?>"
                  required>
                <?= field_error('telefon_nummer', $errors) ?>
                <div class="form-text"><?= h($tx['phone_number']) ?></div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label"><?= h($tx['student_email_label']) ?></label>
            <input
              name="email"
              type="email"
              class="form-control<?= has_err('email',$errors) ?>"
              value="<?= old('email','personal') ?>"
            >
            <?= field_error('email', $errors) ?>
            <div class="form-text">
              <?= h($tx['student_email_hint1']) ?><br>
              <?= h($tx['student_email_hint2']) ?>
            </div>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-12">
            <label class="form-label"><?= h($tx['contacts_title']) ?> <span class="text-muted"><?= h($tx['contacts_sub']) ?></span></label>
            <?php if (!empty($errors['kontakte'])): ?>
              <div class="text-danger mb-2 small"><?= h((string)$errors['kontakte']) ?></div>
            <?php endif; ?>

            <div class="table-responsive">
              <table class="table table-sm align-middle table-contacts">
                <thead>
                  <tr>
                    <th style="width:14rem"><?= h($tx['role']) ?></th>
                    <th style="width:20rem"><?= h($tx['name_org']) ?></th>
                    <th style="width:16rem"><?= h($tx['tel']) ?></th>
                    <th style="width:20rem"><?= h($tx['email']) ?></th>
                    <th style="width:4rem"></th>
                  </tr>
                  <tr>
                    <th colspan="5" class="text-muted fw-normal"><?= h($tx['note']) ?></th>
                  </tr>
                </thead>

                <tbody id="contacts-body">
                <?php foreach ($prevKontakte as $idx => $k):
                  $rowHasErr = isset($kontakt_errors[$idx]);
                  $rowClass  = $rowHasErr ? 'table-danger' : '';
                  $rolesMap  = t_roles($tx);
                ?>
                  <tr class="contact-main <?= $rowClass ?>">
                    <td>
                      <select name="kontakt_role[]" class="form-select form-select-sm">
                        <?php foreach ($rolesMap as $rv=>$rl): ?>
                          <option value="<?= h((string)$rv) ?>" <?= ((string)$rv === (string)($k['rolle'] ?? '')) ? 'selected' : '' ?>>
                            <?= h((string)$rl) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td><input name="kontakt_name[]" class="form-control form-control-sm" value="<?= h((string)($k['name'] ?? '')) ?>" placeholder="<?= h((string)$tx['name_org']) ?>"></td>
                    <td><input name="kontakt_tel[]"  class="form-control form-control-sm" value="<?= h((string)($k['tel'] ?? ''))  ?>" placeholder="+49 …"></td>
                    <td><input name="kontakt_mail[]" class="form-control form-control-sm" value="<?= h((string)($k['mail'] ?? '')) ?>" placeholder="name@example.org"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)" title="<?= h((string)$tx['remove_contact']) ?>">&times;</button></td>
                  </tr>

                  <tr class="contact-note <?= $rowClass ?>">
                    <td colspan="5">
                      <textarea name="kontakt_notiz[]" class="form-control form-control-sm" rows="3" placeholder="<?= h((string)$tx['note']) ?>"><?= h((string)($k['notiz'] ?? '')) ?></textarea>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addRow()"><?= h($tx['add_contact']) ?></button>

            <template id="row-template">
              <tr class="contact-main">
                <td>
                  <select name="kontakt_role[]" class="form-select form-select-sm">
                    <?php foreach (t_roles($tx) as $rv=>$rl): ?>
                      <option value="<?= h((string)$rv) ?>"><?= h((string)$rl) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td><input name="kontakt_name[]" class="form-control form-control-sm" placeholder="<?= h((string)$tx['name_org']) ?>"></td>
                <td><input name="kontakt_tel[]"  class="form-control form-control-sm" placeholder="+49 …"></td>
                <td><input name="kontakt_mail[]" class="form-control form-control-sm" placeholder="name@example.org"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">&times;</button></td>
              </tr>
              <tr class="contact-note">
                <td colspan="5">
                  <textarea name="kontakt_notiz[]" class="form-control form-control-sm" rows="3" placeholder="<?= h((string)$tx['note']) ?>"></textarea>
                </td>
              </tr>
            </template>

            <?php if ($kontakt_errors): ?>
              <div class="small text-danger mt-2">
                <?php foreach ($kontakt_errors as $i=>$msgs) echo 'Kontakt '.($i+1).': '.h(implode(', ', $msgs)).'<br>'; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-12">
            <label class="form-label"><?= h($tx['more_info']) ?></label>
            <textarea
              name="weitere_angaben"
              class="form-control<?= has_err('weitere_angaben',$errors) ?>"
              rows="4"
              placeholder="<?= h($tx['more_info_ph']) ?>"><?= h((string)$prevWeitereAngaben) ?></textarea>
            <?= field_error('weitere_angaben', $errors) ?>
            <div class="form-text"><?= h($tx['more_info_hint']) ?></div>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-12">
            <?php $ok = $_SESSION['form']['personal']['dsgvo_ok'] ?? ($_POST['dsgvo_ok'] ?? ''); ?>
            <?php $dsgvoErr = !empty($errors['dsgvo_ok']); ?>

            <div class="form-check <?= $dsgvoErr ? 'border border-danger rounded p-2' : 'is-required-check' ?>">
              <input class="form-check-input<?= has_err('dsgvo_ok',$errors) ?>" type="checkbox" id="dsgvo_ok" name="dsgvo_ok" value="1" <?= $ok==='1'?'checked':''; ?> required>
              <label class="form-check-label" for="dsgvo_ok">
                <?= h($tx['privacy']) ?>
                <a href="/datenschutz.php" target="_blank" rel="noopener"><?= h($tx['privacy_link']) ?></a>
              </label>
              <?= field_error('dsgvo_ok', $errors) ?>
            </div>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="/index.php" class="btn btn-outline-secondary"><?= h($tx['cancel']) ?></a>
          <button class="btn btn-primary"><?= h($tx['next']) ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function addRow(){
  const tpl = document.getElementById('row-template');
  const tbody = document.getElementById('contacts-body');
  tbody.appendChild(tpl.content.cloneNode(true));
}

function removeRow(btn){
  const main = btn.closest('tr');
  if (!main) return;

  const tbody = main.parentNode;
  const note = main.nextElementSibling;
  const hasNote = note && note.classList && note.classList.contains('contact-note');

  const mainRows = tbody.querySelectorAll('tr.contact-main');
  if (mainRows.length > 1) {
    if (hasNote) note.remove();
    main.remove();
    return;
  }

  main.querySelectorAll('input,select').forEach(el => el.value = '');
  if (hasNote) {
    note.querySelectorAll('textarea').forEach(el => el.value = '');
  }
}

// --- Clientseitige Alters-Plausibelprüfung ---
(function(){
  const REF_YEAR = new Date().getFullYear();
  const URL_REDIRECT = "https://bbs-ol.de/";
  const msg = <?= json_encode(str_replace(['%Y%','%URL%'], ['"+REF_YEAR+"', URL_REDIRECT], (string)$tx['age_confirm']), JSON_UNESCAPED_UNICODE) ?>;
  // msg enthält Platzhalter-String-Building -> wir ersetzen REF_YEAR dynamisch unten

  function buildMsg(){
    return <?= json_encode((string)$tx['age_confirm'], JSON_UNESCAPED_UNICODE) ?>
      .replace('%Y%', String(REF_YEAR))
      .replace('%URL%', URL_REDIRECT);
  }

  function parseDMY(dmy){
    const m = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(dmy || "");
    if(!m) return null;
    const d = parseInt(m[1],10), mo = parseInt(m[2],10)-1, y = parseInt(m[3],10);
    const dt = new Date(y, mo, d);
    if(dt.getFullYear()!==y || dt.getMonth()!==mo || dt.getDate()!==d) return null;
    return dt;
  }
  function ageOnRef(dob){
    const ref = new Date(REF_YEAR, 8, 30); // 30.09.
    let age = ref.getFullYear() - dob.getFullYear();
    const m = ref.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && ref.getDate() < dob.getDate())) age--;
    return age;
  }
  function checkAgeAndMaybeRedirect(dstr){
    const dob = parseDMY(dstr);
    if(!dob) return true;
    const age = ageOnRef(dob);
    if (age < 16 || age > 18) {
      if (confirm(buildMsg())) {
        window.location.href = URL_REDIRECT;
      }
      return false;
    }
    return true;
  }

  const input = document.getElementById('geburtsdatum');
  if (input){
    input.addEventListener('change', function(){
      checkAgeAndMaybeRedirect(input.value);
    });
  }

  const form = document.getElementById('personalForm');
  if (form){
    form.addEventListener('submit', function(ev){
      const ok = checkAgeAndMaybeRedirect((document.getElementById('geburtsdatum')||{}).value || "");
      if (!ok) {
        ev.preventDefault();
        ev.stopPropagation();
        return false;
      }
      return true;
    });
  }
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
