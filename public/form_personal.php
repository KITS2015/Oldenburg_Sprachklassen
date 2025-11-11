<?php
// public/form_personal.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/functions_form.php'; // DB-/Save-Helper

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
    $_SESSION['form']['personal']['email'] = (string)$_SESSION['access']['email'];
}

// --- No-Email: Token sofort erzeugen, wenn noch keiner da ist ---
if ($noEmailMode && current_access_token() === '') {
    issue_access_token();
}

$errors = [];
$kontakt_errors = [];

// Kleine Helper
function age_on_reference(string $dmy, int $year): ?int {
    $bd = DateTimeImmutable::createFromFormat('d.m.Y', $dmy);
    if (!$bd) return null;
    $ref = DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-09-30', $year));
    if (!$ref) return null;
    $diff = $bd->diff($ref);
    // Wenn Geburtsdatum nach dem 30.09. (Zukunft), diff->invert wäre 1; hier reicht .y
    return $diff->y;
}

$refYear = (int)date('Y');

// ---------- POST: Validierung & Speichern ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

    // Pflichtfelder (E-Mail nur, wenn NICHT im No-Email-Modus)
    $req = ['name','vorname','geschlecht','geburtsdatum','geburtsort_land','staatsang','strasse','plz','telefon_vorwahl','telefon_nummer','dsgvo_ok'];
    if (!$noEmailMode) { $req[] = 'email'; }

    foreach ($req as $f) {
        if ($f === 'dsgvo_ok') {
            if (($_POST['dsgvo_ok'] ?? '') !== '1') $errors['dsgvo_ok'] = 'Erforderlich.';
        } else {
            if (empty($_POST[$f]) && !($emailMode && $f === 'email')) {
                $errors[$f] = 'Erforderlich.';
            }
        }
    }

    // Feldvalidierungen
    if (!isset($errors['name']) && !preg_match('/^[\p{L} .\'-]+$/u', (string)$_POST['name'])) $errors['name'] = 'Bitte nur Buchstaben.';
    if (!isset($errors['vorname']) && !preg_match('/^[\p{L} .\'-]+$/u', (string)$_POST['vorname'])) $errors['vorname'] = 'Bitte nur Buchstaben.';
    if (!in_array(($_POST['geschlecht'] ?? ''), ['m','w','d'], true)) $errors['geschlecht'] = 'Ungültig.';

    if (!isset($errors['geburtsdatum'])) {
        if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', (string)$_POST['geburtsdatum'])) {
            $errors['geburtsdatum'] = 'TT.MM.JJJJ';
        } else {
            [$t,$m,$j] = explode('.', (string)$_POST['geburtsdatum']);
            if (!checkdate((int)$m,(int)$t,(int)$j)) $errors['geburtsdatum'] = 'Ungültiges Datum.';
        }
    }

    // Alters-Plausibilität (serverseitiger Fallback)
    if (!isset($errors['geburtsdatum'])) {
        $age = age_on_reference((string)$_POST['geburtsdatum'], $refYear);
        if ($age !== null && ($age < 16 || $age > 18)) {
            // Hinweis zeigen, OK -> weiterleiten und hier abbrechen
            $msg = "Hinweis: Sind Sie am 30.09.$refYear unter 16 oder über 18 Jahre alt, "
                 . "können Sie nicht in die Sprachlernklasse der BBS aufgenommen werden. "
                 . "Bitte bewerben Sie sich für eine andere Klasse einer BBS hier:\nhttps://bbs-ol.de/";
            echo '<!doctype html><html><head><meta charset="utf-8"><title>Weiterleitung</title></head><body>';
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
            $errors['plz'] = 'Nur PLZ aus Oldenburg (26121–26135).';
        }
    }

    // Telefon: +49 fix, Vorwahl mit/ohne führende 0 erlaubt; normalisiert auf E.164 (+49 ohne 0)
    $telefon_pretty = '';
    $telefon_e164   = '';
    if (!isset($errors['telefon_vorwahl']) || !isset($errors['telefon_nummer'])) {
        $vv_raw = (string)($_POST['telefon_vorwahl'] ?? '');
        $vn_raw = (string)($_POST['telefon_nummer'] ?? '');
        $vv = preg_replace('/\D+/', '', $vv_raw ?? '');
        $vn = preg_replace('/\D+/', '', $vn_raw ?? '');

        if (!isset($errors['telefon_vorwahl'])) {
            if ($vv === '' || strlen($vv) < 2 || strlen($vv) > 6) {
                $errors['telefon_vorwahl'] = 'Vorwahl 2–6 Ziffern.';
            }
        }
        if (!isset($errors['telefon_nummer'])) {
            if ($vn === '' || strlen($vn) < 3 || strlen($vn) > 12) {
                $errors['telefon_nummer'] = 'Rufnummer 3–12 Ziffern.';
            }
        }
        if (!isset($errors['telefon_vorwahl']) && !isset($errors['telefon_nummer'])) {
            $vv_norm = ltrim($vv, '0');
            if ($vv_norm === '') $vv_norm = $vv;
            $telefon_e164   = '+49' . $vv_norm . $vn;
            $telefon_pretty = '+49 ' . $vv_norm . ' ' . $vn;
        }
    }

    // E-Mail
    if ($emailMode) {
        $email_raw = (string)($_SESSION['access']['email'] ?? '');
        if ($email_raw === '') { $errors['email'] = 'Erforderlich.'; }
    } else {
        $email_raw = trim((string)($_POST['email'] ?? ''));
        if ($email_raw !== '') {
            if (!filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Ungültige E-Mail.';
            } elseif (preg_match('/@iserv\.de$/i', $email_raw)) {
                $errors['email'] = 'Bitte private E-Mail (keine IServ).';
            }
        } else {
            if (!$noEmailMode && !isset($errors['email'])) {
                $errors['email'] = 'Erforderlich.';
            }
        }
    }

    // ---------- Strukturierte Zusatzkontakte ----------
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
        if ($name === '') $rowErr[] = 'Name/Bezeichnung fehlt';
        if ($tel === '' && $mail === '') $rowErr[] = 'Telefon ODER E-Mail angeben';
        if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) $rowErr[] = 'E-Mail ungültig';
        if ($tel  !== '' && !preg_match('/^[0-9 +\/()\-]+$/', $tel))    $rowErr[] = 'Telefon ungültig';
        if ($rowErr) $kontakt_errors[$i] = $rowErr;

        $contacts[] = ['rolle'=>$role,'name'=>$name,'tel'=>$tel,'mail'=>$mail,'notiz'=>$note];
    }
    if ($kontakt_errors) $errors['kontakte'] = 'Bitte prüfen Sie die zusätzlichen Kontakte.';

    // ---------- Speichern, wenn valide ----------
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

// ---------- Header-Infos ----------
$title     = 'Schritt 1/4 – Persönliche Daten';
$html_lang = 'de';
$html_dir  = 'ltr';

require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';

// UI-Helper
function sel($a,$b){ return $a===$b ? 'selected' : ''; }

// Vorbelegung für die Kontakte (Session oder aus POST rekonstruieren)
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
?>

<div class="container py-4">
  <?php if (function_exists('flash_render')) { flash_render(); } ?>

  <?php if ($emailMode): ?>
    <div class="alert alert-success">
      <strong>E-Mail-Login aktiv:</strong>
      Angemeldet als <code><?= h($_SESSION['access']['email']) ?></code>.
      Änderungen der E-Mail sind in diesem Schritt nicht möglich.
    </div>
  <?php endif; ?>

  <?php if ($noEmailMode): ?>
    <div class="alert alert-warning">
      <strong>Hinweis (ohne E-Mail):</strong> Bitte notieren/fotografieren Sie Ihren Zugangscode (Access-Token) oben.
      Ohne verifizierte E-Mail ist eine Wiederherstellung nur mit <strong>Token + Geburtsdatum</strong> möglich.
    </div>
  <?php endif; ?>

  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-3">Schritt 1/4 – Persönliche Daten</h1>
      <?php if ($errors): ?>
        <div class="alert alert-danger">Bitte prüfen Sie die markierten Felder.</div>
      <?php endif; ?>

      <form method="post" action="" novalidate class="mt-3" id="personalForm">
        <?php csrf_field(); ?>

        <!-- Reihe 1: Name / Vorname -->
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Name*</label>
            <input name="name" class="form-control<?= has_err('name',$errors) ?>" value="<?= old('name','personal') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Vorname*</label>
            <input name="vorname" class="form-control<?= has_err('vorname',$errors) ?>" value="<?= old('vorname','personal') ?>" required>
          </div>
        </div>

        <!-- Reihe 2: Geschlecht / Geburtsdatum -->
        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label d-block">Geschlecht*</label>
            <div class="btn-group" role="group">
              <?php $g = $_SESSION['form']['personal']['geschlecht'] ?? ($_POST['geschlecht'] ?? ''); ?>
              <input type="radio" class="btn-check" name="geschlecht" id="g_m" value="m" <?= $g==='m'?'checked':''; ?> required>
              <label class="btn btn-outline-primary" for="g_m">männlich</label>
              <input type="radio" class="btn-check" name="geschlecht" id="g_w" value="w" <?= $g==='w'?'checked':''; ?>>
              <label class="btn btn-outline-primary" for="g_w">weiblich</label>
              <input type="radio" class="btn-check" name="geschlecht" id="g_d" value="d" <?= $g==='d'?'checked':''; ?>>
              <label class="btn btn-outline-primary" for="g_d">divers</label>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Geboren am* <span class="text-muted">(TT.MM.JJJJ)</span></label>
            <input id="geburtsdatum" name="geburtsdatum" class="form-control<?= has_err('geburtsdatum',$errors) ?>" placeholder="TT.MM.JJJJ" value="<?= old('geburtsdatum','personal') ?>" required>
            <div class="form-text">
              Hinweis: Sind Sie am 30.09.<?= date('Y') ?> unter 16 oder über 18 Jahre alt,
              können Sie nicht in die Sprachlernklasse der BBS aufgenommen werden.
              Bitte bewerben Sie sich für eine andere Klasse hier:
              <a href="https://bbs-ol.de/" target="_blank" rel="noopener">https://bbs-ol.de/</a>
            </div>
          </div>
        </div>

        <!-- Reihe 3: Geburtsort/Geburtsland / Staatsangehörigkeit -->
        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label">Geburtsort / Geburtsland*</label>
            <input name="geburtsort_land" class="form-control<?= has_err('geburtsort_land',$errors) ?>" value="<?= old('geburtsort_land','personal') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Staatsangehörigkeit*</label>
            <input name="staatsang" class="form-control<?= has_err('staatsang',$errors) ?>" value="<?= old('staatsang','personal') ?>" required>
          </div>
        </div>

        <!-- Reihe 4: Straße / PLZ / Wohnort -->
        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label">Straße, Nr.*</label>
            <input name="strasse" class="form-control<?= has_err('strasse',$errors) ?>" value="<?= old('strasse','personal') ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">PLZ*</label>
            <select name="plz" class="form-select<?= has_err('plz',$errors) ?>" required>
              <option value="">– bitte wählen –</option>
              <?php
                $plzList = ['26121','26122','26123','26125','26127','26129','26131','26133','26135'];
                $currentPlz = old('plz','personal');
                foreach ($plzList as $plz) {
                    $sel = ($plz === $currentPlz) ? 'selected' : '';
                    echo "<option value=\"$plz\" $sel>$plz</option>";
                }
              ?>
            </select>
            <div class="form-text">Nur Oldenburg (Oldb).</div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Wohnort</label>
            <input name="wohnort" class="form-control" value="<?= h($_SESSION['form']['personal']['wohnort'] ?? 'Oldenburg (Oldb)') ?>" readonly>
          </div>
        </div>

        <!-- Reihe 5: Telefon (geteilt) / E-Mail -->
        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label">Telefonnummer*</label>
            <div class="row g-2">
              <div class="col-5 col-sm-4">
                <div class="input-group">
                  <span class="input-group-text">+49</span>
                  <input
                    name="telefon_vorwahl"
                    class="form-control<?= has_err('telefon_vorwahl',$errors) ?>"
                    inputmode="numeric"
                    pattern="^0?\d{2,6}$"
                    placeholder="(0)441"
                    value="<?= h($_SESSION['form']['personal']['telefon_vorwahl'] ?? ($_POST['telefon_vorwahl'] ?? '')) ?>"
                    required>
                </div>
                <div class="form-text">Vorwahl mit/ohne 0</div>
              </div>
              <div class="col-7 col-sm-8">
                <input
                  name="telefon_nummer"
                  class="form-control<?= has_err('telefon_nummer',$errors) ?>"
                  inputmode="numeric"
                  pattern="^\d[\d\s\-\/()]{2,}$"
                  placeholder="123456"
                  value="<?= h($_SESSION['form']['personal']['telefon_nummer'] ?? ($_POST['telefon_nummer'] ?? '')) ?>"
                  required>
                <div class="form-text">Rufnummer</div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label">
              Private E-Mail-Adresse<?= $noEmailMode ? '' : '*' ?> (keine IServ)
            </label>
            <?php if ($emailMode): ?>
              <input name="email" type="email" class="form-control" value="<?= h($_SESSION['access']['email']) ?>" readonly>
              <div class="form-text">Diese Adresse wurde per Code bestätigt.</div>
            <?php else: ?>
              <input name="email" type="email" class="form-control<?= has_err('email',$errors) ?>" value="<?= old('email','personal') ?>" <?= $noEmailMode ? '' : 'required' ?>>
              <?php if ($noEmailMode): ?>
                <div class="form-text">Freiwillig. Ohne E-Mail ist Wiederherstellung nur mit Token + Geburtsdatum möglich.</div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Kontakte -->
        <div class="row g-3 mt-0">
          <div class="col-12">
            <label class="form-label">Weitere Kontaktdaten <span class="text-muted">(z. B. Eltern, Betreuer, Einrichtung)</span></label>
            <?php if (!empty($errors['kontakte'])): ?>
              <div class="text-danger mb-2 small"><?= h($errors['kontakte']) ?></div>
            <?php endif; ?>

            <div class="table-responsive">
              <table class="table table-sm align-middle table-contacts">
                <thead>
                  <tr>
                    <th style="width:14rem">Rolle</th>
                    <th style="width:20rem">Name / Einrichtung</th>
                    <th style="width:16rem">Telefon</th>
                    <th style="width:20rem">E-Mail</th>
                    <th>Notiz</th>
                    <th style="width:4rem"></th>
                  </tr>
                </thead>
                <tbody id="contacts-body">
                <?php foreach ($prevKontakte as $idx => $k):
                  $rowHasErr = isset($kontakt_errors[$idx]); ?>
                  <tr class="<?= $rowHasErr ? 'row-error' : '' ?>">
                    <td>
                      <select name="kontakt_role[]" class="form-select form-select-sm">
                        <?php
                          $roles = [''=>'–','Mutter'=>'Mutter','Vater'=>'Vater','Elternteil'=>'Elternteil','Betreuer*in'=>'Betreuer*in','Einrichtung'=>'Einrichtung','Sonstiges'=>'Sonstiges'];
                          foreach ($roles as $rv=>$rl) echo '<option value="'.h($rv).'" '.($rv === (string)($k['rolle'] ?? '') ? 'selected' : '').'>'.h($rl).'</option>';
                        ?>
                      </select>
                    </td>
                    <td><input name="kontakt_name[]" class="form-control form-control-sm" value="<?= h((string)($k['name'] ?? '')) ?>" placeholder="Name oder Bezeichnung"></td>
                    <td><input name="kontakt_tel[]"  class="form-control form-control-sm" value="<?= h((string)($k['tel'] ?? ''))  ?>" placeholder="+49 …"></td>
                    <td><input name="kontakt_mail[]" class="form-control form-control-sm" value="<?= h((string)($k['mail'] ?? '')) ?>" placeholder="name@example.org"></td>
                    <td><input name="kontakt_notiz[]" class="form-control form-control-sm" value="<?= h((string)($k['notiz'] ?? '')) ?>" placeholder="z. B. Erreichbarkeit, Sprache"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)" title="Zeile entfernen">&times;</button></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addRow()">+ Kontakt hinzufügen</button>

            <template id="row-template">
              <tr>
                <td>
                  <select name="kontakt_role[]" class="form-select form-select-sm">
                    <option value="">–</option>
                    <option>Mutter</option>
                    <option>Vater</option>
                    <option>Elternteil</option>
                    <option>Betreuer*in</option>
                    <option>Einrichtung</option>
                    <option>Sonstiges</option>
                  </select>
                </td>
                <td><input name="kontakt_name[]" class="form-control form-control-sm" placeholder="Name oder Bezeichnung"></td>
                <td><input name="kontakt_tel[]"  class="form-control form-control-sm" placeholder="+49 …"></td>
                <td><input name="kontakt_mail[]" class="form-control form-control-sm" placeholder="name@example.org"></td>
                <td><input name="kontakt_notiz[]" class="form-control form-control-sm" placeholder="z. B. Erreichbarkeit, Sprache"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">&times;</button></td>
              </tr>
            </template>

            <?php if ($kontakt_errors): ?>
              <div class="small text-danger mt-2">
                <?php foreach ($kontakt_errors as $i=>$msgs) echo 'Kontakt '.($i+1).': '.h(implode(', ', $msgs)).'<br>'; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- DSGVO -->
        <div class="row g-3 mt-0">
          <div class="col-12">
            <div class="form-check">
              <?php $ok = $_SESSION['form']['personal']['dsgvo_ok'] ?? ($_POST['dsgvo_ok'] ?? ''); ?>
              <input class="form-check-input<?= has_err('dsgvo_ok',$errors) ?>" type="checkbox" id="dsgvo_ok" name="dsgvo_ok" value="1" <?= $ok==='1'?'checked':''; ?> required>
              <label class="form-check-label" for="dsgvo_ok">
                Ich habe die <a href="/datenschutz.php" target="_blank" rel="noopener">Datenschutzhinweise</a> gelesen und bin einverstanden.*
              </label>
            </div>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="/index.php" class="btn btn-outline-secondary">Abbrechen</a>
          <button class="btn btn-primary">Weiter</button>
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
  const tr = btn.closest('tr');
  if (!tr) return;
  const tbody = tr.parentNode;
  if (tbody.querySelectorAll('tr').length > 1) tr.remove();
  else Array.from(tr.querySelectorAll('input,select')).forEach(el => el.value='');
}

// --- Clientseitige Alters-Plausibelprüfung ---
(function(){
  const REF_YEAR = new Date().getFullYear();
  const URL_REDIRECT = "https://bbs-ol.de/";
  const msg = `Hinweis: Sind Sie am 30.09.${REF_YEAR} unter 16 oder über 18 Jahre alt, `
            + `können Sie nicht in die Sprachlernklasse der BBS aufgenommen werden. `
            + `Bitte bewerben Sie sich für eine andere Klasse einer BBS hier:\n${URL_REDIRECT}`;

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
    if(!dob) return true; // Format wird separat geprüft
    const age = ageOnRef(dob);
    if (age < 16 || age > 18) {
      if (confirm(msg)) {
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

<?php require __DIR__ . '/partials/footer.php';
