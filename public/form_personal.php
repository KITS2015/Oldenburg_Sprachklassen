<?php
// public/form_personal.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/functions_form.php'; // <- DB-Helpers (ensure_application, save_personal, ...)

$errors = [];
$kontakt_errors = []; // zeilenweise Fehlerhinweise

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

  // Pflichtfelder
  $req = ['name','vorname','geschlecht','geburtsdatum','geburtsort_land','staatsang','strasse','plz','telefon','email','dsgvo_ok'];
  foreach ($req as $f) {
    if ($f === 'dsgvo_ok') {
      if (($_POST['dsgvo_ok'] ?? '') !== '1') $errors['dsgvo_ok'] = 'Erforderlich.';
    } else {
      if (empty($_POST[$f])) $errors[$f] = 'Erforderlich.';
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
      [$t,$m,$j] = explode('.', $_POST['geburtsdatum']);
      if (!checkdate((int)$m,(int)$t,(int)$j)) $errors['geburtsdatum'] = 'Ungültiges Datum.';
    }
  }
  if (!isset($errors['plz']) && !preg_match('/^\d{5}$/', (string)$_POST['plz'])) $errors['plz'] = '5 Ziffern.';
  if (!isset($errors['telefon']) && !preg_match('/^[0-9 +\/()\-]+$/', (string)$_POST['telefon'])) $errors['telefon'] = 'Ungültig.';
  if (!isset($errors['email'])) {
    if (!filter_var((string)$_POST['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Ungültige E-Mail.';
    elseif (preg_match('/@iserv\.de$/i', (string)$_POST['email'])) $errors['email'] = 'Bitte private E-Mail (keine IServ).';
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
    if ($isEmpty) continue; // komplett leere Zeile ignorieren

    $rowErr = [];
    if ($name === '') $rowErr[] = 'Name/Bezeichnung fehlt';
    if ($tel === '' && $mail === '') $rowErr[] = 'Telefon ODER E-Mail angeben';
    if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) $rowErr[] = 'E-Mail ungültig';
    if ($tel  !== '' && !preg_match('/^[0-9 +\/()\-]+$/', $tel))    $rowErr[] = 'Telefon ungültig';

    if ($rowErr) $kontakt_errors[$i] = $rowErr;

    $contacts[] = [
      'rolle' => $role,
      'name'  => $name,
      'tel'   => $tel,
      'mail'  => $mail,
      'notiz' => $note,
    ];
  }
  if ($kontakt_errors) $errors['kontakte'] = 'Bitte prüfen Sie die zusätzlichen Kontakte.';

  // ---------- Wenn alles ok: Session + DB speichern ----------
  if (!$errors) {
    // Session (für UI-Vorbelegung)
    $_SESSION['form']['personal'] = [
      'name'            => trim((string)$_POST['name']),
      'vorname'         => trim((string)$_POST['vorname']),
      'geschlecht'      => (string)$_POST['geschlecht'],
      'geburtsdatum'    => (string)$_POST['geburtsdatum'],
      'geburtsort_land' => trim((string)$_POST['geburtsort_land']),
      'staatsang'       => trim((string)$_POST['staatsang']),
      'strasse'         => trim((string)$_POST['strasse']),
      'plz'             => trim((string)$_POST['plz']),
      'wohnort'         => $_POST['wohnort'] ?? 'Oldenburg (Oldb)',
      'telefon'         => trim((string)$_POST['telefon']),
      'email'           => trim((string)$_POST['email']),
      'contacts'        => $contacts,
      'dsgvo_ok'        => (($_POST['dsgvo_ok'] ?? '') === '1' ? '1' : '0'),
    ];

    // DB persistieren
    save_personal([
      'name'            => $_SESSION['form']['personal']['name'],
      'vorname'         => $_SESSION['form']['personal']['vorname'],
      'geschlecht'      => $_SESSION['form']['personal']['geschlecht'],
      'geburtsdatum'    => $_SESSION['form']['personal']['geburtsdatum'], // TT.MM.JJJJ
      'geburtsort_land' => $_SESSION['form']['personal']['geburtsort_land'],
      'staatsang'       => $_SESSION['form']['personal']['staatsang'],
      'strasse'         => $_SESSION['form']['personal']['strasse'],
      'plz'             => $_SESSION['form']['personal']['plz'],
      'wohnort'         => $_SESSION['form']['personal']['wohnort'],
      'telefon'         => $_SESSION['form']['personal']['telefon'],
      'email'           => $_SESSION['form']['personal']['email'],
      'dsgvo_ok'        => $_SESSION['form']['personal']['dsgvo_ok'] === '1',
      'contacts'        => $contacts, // <- wichtig: Name muss 'contacts' heißen (siehe functions_form.php)
    ]);

    header('Location: /form_school.php'); exit;
  }
}

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
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Schritt 1/4 – Persönliche Daten</title>
  <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css"><link rel="stylesheet" href="/assets/form.css">
  <style>
    .card { border-radius: 1rem; }
    .table-contacts td { vertical-align: middle; }
    .row-error { background: #fff3f3; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-3">Schritt 1/4 – Persönliche Daten</h1>
      <?php if ($errors): ?>
        <div class="alert alert-danger">Bitte prüfen Sie die markierten Felder.</div>
      <?php endif; ?>

      <form method="post" action="" novalidate>
        <?php csrf_field(); ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Name*</label>
            <input name="name" class="form-control<?= has_err('name',$errors) ?>" value="<?= old('name','personal') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Vorname*</label>
            <input name="vorname" class="form-control<?= has_err('vorname',$errors) ?>" value="<?= old('vorname','personal') ?>" required>
          </div>

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
            <input name="geburtsdatum" class="form-control<?= has_err('geburtsdatum',$errors) ?>" placeholder="TT.MM.JJJJ" value="<?= old('geburtsdatum','personal') ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Geburtsort / Geburtsland*</label>
            <input name="geburtsort_land" class="form-control<?= has_err('geburtsort_land',$errors) ?>" value="<?= old('geburtsort_land','personal') ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Staatsangehörigkeit*</label>
            <input name="staatsang" class="form-control<?= has_err('staatsang',$errors) ?>" value="<?= old('staatsang','personal') ?>" required>
          </div>

          <div class="col-md-8">
            <label class="form-label">Straße, Nr.*</label>
            <input name="strasse" class="form-control<?= has_err('strasse',$errors) ?>" value="<?= old('strasse','personal') ?>" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">PLZ*</label>
            <input name="plz" class="form-control<?= has_err('plz',$errors) ?>" inputmode="numeric" pattern="\d{5}" value="<?= old('plz','personal') ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Wohnort</label>
            <input name="wohnort" class="form-control" value="<?= h($_SESSION['form']['personal']['wohnort'] ?? 'Oldenburg (Oldb)') ?>" readonly>
          </div>

          <div class="col-md-6">
            <label class="form-label">Telefonnummer*</label>
            <input name="telefon" class="form-control<?= has_err('telefon',$errors) ?>" value="<?= old('telefon','personal') ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Private E-Mail-Adresse* (keine IServ)</label>
            <input name="email" type="email" class="form-control<?= has_err('email',$errors) ?>" value="<?= old('email','personal') ?>" required>
          </div>

          <!-- Strukturierte weitere Kontakte -->
          <div class="col-12">
            <label class="form-label">
              Weitere Kontaktdaten <span class="text-muted">(z. B. Eltern, Betreuer, Einrichtung)</span>
            </label>
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
                          foreach ($roles as $rv=>$rl) echo '<option value="'.h($rv).'" '.sel($rv, (string)$k['rolle']).'>'.h($rl).'</option>';
                        ?>
                      </select>
                    </td>
                    <td><input name="kontakt_name[]" class="form-control form-control-sm" value="<?= h((string)$k['name']) ?>" placeholder="Name oder Bezeichnung"></td>
                    <td><input name="kontakt_tel[]"  class="form-control form-control-sm" value="<?= h((string)$k['tel'])  ?>" placeholder="+49 …"></td>
                    <td><input name="kontakt_mail[]" class="form-control form-control-sm" value="<?= h((string)$k['mail']) ?>" placeholder="name@example.org"></td>
                    <td><input name="kontakt_notiz[]" class="form-control form-control-sm" value="<?= h((string)$k['notiz']) ?>" placeholder="z. B. Erreichbarkeit, Sprache"></td>
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
          <!-- Ende strukturierte Kontakte -->

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

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
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
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
