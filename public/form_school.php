<?php
// public/form_school.php
require __DIR__ . '/wizard/_common.php';
require_step('school'); // stellt sicher, dass Schritt 1 ausgefüllt wurde

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

  // --- Pflichtfelder (ohne "seit wann", das hat Sonderlogik) ---
  $req = ['schule_aktuell','klassenlehrer','jahre_in_de','schule_herkunft','familiensprache'];
  foreach ($req as $f) {
    if (empty($_POST[$f])) $errors[$f] = 'Erforderlich.';
  }

  // Schule gültig?
  if (!array_key_exists($_POST['schule_aktuell'] ?? '', $SCHULEN)) {
    $errors['schule_aktuell'] = 'Bitte eine gültige Schule wählen.';
  }

  // --- Seit wann an der Schule? -> (Monat & Jahr) ODER Freitext ---
  $seit_monat = trim($_POST['seit_monat'] ?? '');
  $seit_jahr  = trim($_POST['seit_jahr'] ?? '');
  $seit_text  = trim($_POST['seit_text'] ?? '');

  // Mindestens eines muss befüllt sein
  if (($seit_monat === '' || $seit_jahr === '') && $seit_text === '') {
    $errors['seit_monat'] = 'Bitte Monat+Jahr oder Freitext angeben.';
    $errors['seit_jahr']  = 'Bitte Monat+Jahr oder Freitext angeben.';
  } else {
    // Einzelprüfungen nur wenn gesetzt
    if ($seit_monat !== '' && !preg_match('/^(0[1-9]|1[0-2])$/', $seit_monat)) {
      $errors['seit_monat'] = 'Monat muss 01–12 sein.';
    }
    if ($seit_jahr !== '' && (!preg_match('/^\d{4}$/', $seit_jahr) || (int)$seit_jahr < 1900 || (int)$seit_jahr > 2100)) {
      $errors['seit_jahr'] = 'Bitte gültiges Jahr (JJJJ) angeben.';
    }
  }

  // Jahre in Deutschland – Zahl
  if (!isset($errors['jahre_in_de']) && $_POST['jahre_in_de'] !== '') {
    if (!preg_match('/^\d{1,2}$/', $_POST['jahre_in_de'])) {
      $errors['jahre_in_de'] = 'Bitte Zahl angeben.';
    }
  }

  // Schule im Herkunftsland? + Folgefeld
  $herkunft = $_POST['schule_herkunft'] ?? '';
  if (!in_array($herkunft, ['ja','nein'], true)) {
    $errors['schule_herkunft'] = 'Bitte auswählen.';
  }
  if ($herkunft === 'ja') {
    if (empty($_POST['jahre_schule_herkunft']) || !preg_match('/^\d{1,2}$/', $_POST['jahre_schule_herkunft'])) {
      $errors['jahre_schule_herkunft'] = 'Bitte Anzahl Jahre angeben.';
    }
  }

  // Deutsch-Niveau – optional, aber nur erlaubte Werte
  $niveau = $_POST['deutsch_niveau'] ?? '';
  if ($niveau !== '' && !in_array($niveau, $GERMAN_LEVELS, true)) {
    $errors['deutsch_niveau'] = 'Ungültige Auswahl.';
  }

  // Interessen – min 1, max 2
  $chosen = array_keys(array_filter($_POST['interessen'] ?? [], fn($v) => $v === '1'));
  $chosen_valid = array_values(array_intersect($chosen, array_keys($INTERESSEN)));
  if (count($chosen_valid) < 1) $errors['interessen'] = 'Bitte mindestens 1 Bereich wählen.';
  if (count($chosen_valid) > 2) $errors['interessen'] = 'Bitte höchstens 2 Bereiche wählen.';

  // Speichern & weiter
  if (!$errors) {
    // Normalisiert: Wenn Monat+Jahr vorhanden -> "MM.JJJJ", sonst Freitext
    $seit_wann_norm = ($seit_monat !== '' && $seit_jahr !== '')
      ? ($seit_monat . '.' . $seit_jahr)
      : $seit_text;

    $_SESSION['form']['school'] = [
      'schule_aktuell'        => $_POST['schule_aktuell'],
      'klassenlehrer'         => trim($_POST['klassenlehrer']),
      'mail_lehrkraft'        => trim($_POST['mail_lehrkraft'] ?? ''),
      'seit_monat'            => $seit_monat,
      'seit_jahr'             => $seit_jahr,
      'seit_text'             => $seit_text,
      'seit_wann_schule'      => $seit_wann_norm,  // für die Zusammenfassung
      'jahre_in_de'           => $_POST['jahre_in_de'],
      'schule_herkunft'       => $herkunft,
      'jahre_schule_herkunft' => trim($_POST['jahre_schule_herkunft'] ?? ''),
      'familiensprache'       => trim($_POST['familiensprache']),
      'deutsch_niveau'        => $niveau,
      'interessen'            => $chosen_valid,
    ];

    header('Location: /form_upload.php');
    exit;
  }
}

// Vorauswahl für Radios etc.
$herk = $_SESSION['form']['school']['schule_herkunft'] ?? ($_POST['schule_herkunft'] ?? '');
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Schritt 2/4 – Schule & Interessen</title>
  <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/form.css">
  <style>.card{border-radius:1rem}</style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-3">Schritt 2/4 – Schule & Interessen</h1>
      <?php if ($errors): ?><div class="alert alert-danger">Bitte prüfen Sie die markierten Felder.</div><?php endif; ?>

      <form method="post" action="" novalidate>
        <?php csrf_field(); ?>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Aktuelle Schule*</label>
            <select name="schule_aktuell" class="form-select<?= has_err('schule_aktuell',$errors) ?>" required>
              <?php $cur = $_SESSION['form']['school']['schule_aktuell'] ?? ($_POST['schule_aktuell'] ?? ''); ?>
              <?php foreach ($SCHULEN as $k => $v): ?>
                <option value="<?= h($k) ?>" <?= $cur===$k ? 'selected' : '' ?>><?= h($v) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['schule_aktuell'])): ?><div class="text-danger small mt-1"><?= h($errors['schule_aktuell']) ?></div><?php endif; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label">Klassenlehrer*in*</label>
            <input name="klassenlehrer" class="form-control<?= has_err('klassenlehrer',$errors) ?>" value="<?= old('klassenlehrer','school') ?>" required>
            <?php if (isset($errors['klassenlehrer'])): ?><div class="text-danger small mt-1"><?= h($errors['klassenlehrer']) ?></div><?php endif; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label">E-Mail der Klassen-/DaZ-Lehrkraft</label>
            <input name="mail_lehrkraft" type="email" class="form-control<?= has_err('mail_lehrkraft',$errors) ?>" value="<?= old('mail_lehrkraft','school') ?>">
            <?php if (isset($errors['mail_lehrkraft'])): ?><div class="text-danger small mt-1"><?= h($errors['mail_lehrkraft']) ?></div><?php endif; ?>
          </div>

          <!-- Seit wann an der Schule? -> Monat/Jahr ODER Freitext -->
          <div class="col-md-6">
            <label class="form-label">Seit wann an der Schule?*</label>
            <div class="row g-2">
              <div class="col-5">
                <select name="seit_monat" class="form-select<?= has_err('seit_monat',$errors) ?>">
                  <?php
                    $malt = $_SESSION['form']['school']['seit_monat'] ?? ($_POST['seit_monat'] ?? '');
                    $months = ['' => 'Monat (MM)'] + array_combine(
                      array_map(fn($i)=>str_pad((string)$i,2,'0',STR_PAD_LEFT), range(1,12)),
                      array_map(fn($i)=>str_pad((string)$i,2,'0',STR_PAD_LEFT), range(1,12))
                    );
                    foreach ($months as $val=>$label) {
                      $sel = ($malt === $val) ? 'selected' : '';
                      echo '<option value="'.h($val).'" '.$sel.'>'.h($label).'</option>';
                    }
                  ?>
                </select>
              </div>
              <div class="col-7">
                <input
                  name="seit_jahr"
                  class="form-control<?= has_err('seit_jahr',$errors) ?>"
                  inputmode="numeric"
                  pattern="\d{4}"
                  placeholder="Jahr (JJJJ)"
                  value="<?= h($_SESSION['form']['school']['seit_jahr'] ?? ($_POST['seit_jahr'] ?? '')) ?>"
                >
              </div>
            </div>
            <div class="form-text">Entweder Monat+Jahr angeben <strong>oder</strong> das Freitextfeld nutzen.</div>
            <?php if(isset($errors['seit_monat'])): ?><div class="text-danger small mt-1"><?= h($errors['seit_monat']) ?></div><?php endif; ?>
            <?php if(isset($errors['seit_jahr'])): ?><div class="text-danger small mt-1"><?= h($errors['seit_jahr']) ?></div><?php endif; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label">Alternativ: Freitext (z. B. „seit Herbst 2023“)</label>
            <input
              name="seit_text"
              class="form-control<?= has_err('seit_text',$errors) ?>"
              value="<?= h($_SESSION['form']['school']['seit_text'] ?? ($_POST['seit_text'] ?? '')) ?>"
            >
            <?php if(isset($errors['seit_text'])): ?><div class="text-danger small mt-1"><?= h($errors['seit_text']) ?></div><?php endif; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label">Seit wie vielen Jahren sind Sie in Deutschland?*</label>
            <input name="jahre_in_de" inputmode="numeric" class="form-control<?= has_err('jahre_in_de',$errors) ?>" value="<?= old('jahre_in_de','school') ?>" required>
            <?php if (isset($errors['jahre_in_de'])): ?><div class="text-danger small mt-1"><?= h($errors['jahre_in_de']) ?></div><?php endif; ?>
          </div>

          <!-- Ja/Nein + Folgefeld direkt darunter -->
          <div class="col-md-6">
            <label class="form-label d-block">Haben Sie im Herkunftsland die Schule besucht?*</label>
            <div class="btn-group" role="group">
              <input type="radio" class="btn-check" name="schule_herkunft" id="s_j" value="ja" <?= $herk==='ja'?'checked':''; ?> required>
              <label class="btn btn-outline-primary" for="s_j">Ja</label>

              <input type="radio" class="btn-check" name="schule_herkunft" id="s_n" value="nein" <?= $herk==='nein'?'checked':''; ?>>
              <label class="btn btn-outline-primary" for="s_n">Nein</label>
            </div>
            <?php if (isset($errors['schule_herkunft'])): ?><div class="text-danger small mt-1"><?= h($errors['schule_herkunft']) ?></div><?php endif; ?>

            <div id="jahre_herkunft_wrap" class="mt-3" style="display:none;">
              <label class="form-label">Wenn ja: wie viele Jahre?</label>
              <input
                name="jahre_schule_herkunft"
                class="form-control<?= has_err('jahre_schule_herkunft',$errors) ?>"
                inputmode="numeric"
                value="<?= old('jahre_schule_herkunft','school') ?>"
              >
              <?php if(isset($errors['jahre_schule_herkunft'])): ?>
                <div class="invalid-feedback d-block"><?= h($errors['jahre_schule_herkunft']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Familiensprache / Erstsprache*</label>
            <input name="familiensprache" class="form-control<?= has_err('familiensprache',$errors) ?>" value="<?= old('familiensprache','school') ?>" required>
            <?php if (isset($errors['familiensprache'])): ?><div class="text-danger small mt-1"><?= h($errors['familiensprache']) ?></div><?php endif; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label">Welches Deutsch-Niveau?</label>
            <?php $niv = $_SESSION['form']['school']['deutsch_niveau'] ?? ($_POST['deutsch_niveau'] ?? ''); ?>
            <select name="deutsch_niveau" class="form-select<?= has_err('deutsch_niveau',$errors) ?>">
              <option value="">Bitte wählen …</option>
              <?php foreach ($GERMAN_LEVELS as $lvl): ?>
                <option value="<?= h($lvl) ?>" <?= $niv===$lvl ? 'selected' : '' ?>><?= h($lvl) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['deutsch_niveau'])): ?><div class="text-danger small mt-1"><?= h($errors['deutsch_niveau']) ?></div><?php endif; ?>
            <div class="form-text">Hinweis: Bei B1 könnte eine andere Beschulung in Frage kommen.</div>
          </div>

          <div class="col-12">
            <label class="form-label">Interessen (mind. 1, max. 2)</label>
            <div class="row">
              <?php $curInt = $_SESSION['form']['school']['interessen'] ?? array_keys(array_filter($_POST['interessen'] ?? [])); ?>
              <?php foreach ($INTERESSEN as $k=>$label): ?>
                <div class="col-sm-6 col-md-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="interessen[<?= h($k) ?>]" id="int_<?= h($k) ?>" value="1" <?= in_array($k,$curInt,true)?'checked':''; ?>>
                    <label class="form-check-label" for="int_<?= h($k) ?>"><?= h($label) ?></label>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <?php if (isset($errors['interessen'])): ?><div class="text-danger small mt-1"><?= h($errors['interessen']) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="/form_personal.php" class="btn btn-outline-secondary">Zurück</a>
          <button class="btn btn-primary">Weiter</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
<script>
// Anzeige des Folgefelds "Wenn ja: wie viele Jahre?"
function toggleHerkunftYears(){
  var yes  = document.getElementById('s_j');
  var wrap = document.getElementById('jahre_herkunft_wrap');
  if (wrap) wrap.style.display = (yes && yes.checked) ? '' : 'none';
}
document.addEventListener('change', function(e){
  if (e.target && (e.target.id === 's_j' || e.target.id === 's_n')) toggleHerkunftYears();
});
toggleHerkunftYears();
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
