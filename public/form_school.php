<?php
// public/form_school.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/functions_form.php';

// Sicherstellen: Schritt 1 vorhanden
require_step('school');

$errors = [];

// ---------- POST: Validierung & Speichern ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

    // Pflichtfelder (ohne „seit wann“, hat Sonderlogik)
    $req = ['schule_aktuell','klassenlehrer','jahre_in_de','schule_herkunft','familiensprache'];
    foreach ($req as $f) {
        if (empty($_POST[$f])) $errors[$f] = 'Erforderlich.';
    }

    // Schule gültig?
    if (!array_key_exists($_POST['schule_aktuell'] ?? '', $SCHULEN)) {
        $errors['schule_aktuell'] = 'Bitte eine gültige Schule wählen.';
    }

    // Seit wann an der Schule? -> (Monat & Jahr) ODER Freitext
    $seit_monat = trim((string)($_POST['seit_monat'] ?? ''));
    $seit_jahr  = trim((string)($_POST['seit_jahr']  ?? ''));
    $seit_text  = trim((string)($_POST['seit_text']  ?? ''));

    if (($seit_monat === '' || $seit_jahr === '') && $seit_text === '') {
        $errors['seit_monat'] = 'Bitte Monat+Jahr oder Freitext angeben.';
        $errors['seit_jahr']  = 'Bitte Monat+Jahr oder Freitext angeben.';
    } else {
        if ($seit_monat !== '' && !preg_match('/^(0[1-9]|1[0-2])$/', $seit_monat)) {
            $errors['seit_monat'] = 'Monat muss 01–12 sein.';
        }
        if ($seit_jahr !== '' && (!preg_match('/^\d{4}$/', $seit_jahr) || (int)$seit_jahr < 1900 || (int)$seit_jahr > 2100)) {
            $errors['seit_jahr'] = 'Bitte gültiges Jahr (JJJJ) angeben.';
        }
    }

    // Jahre in Deutschland – Zahl
    if (!isset($errors['jahre_in_de']) && ($_POST['jahre_in_de'] ?? '') !== '') {
        if (!preg_match('/^\d{1,2}$/', (string)$_POST['jahre_in_de'])) {
            $errors['jahre_in_de'] = 'Bitte Zahl angeben.';
        }
    }

    // Schule im Herkunftsland? + Folgefeld
    $herkunft = (string)($_POST['schule_herkunft'] ?? '');
    if (!in_array($herkunft, ['ja','nein'], true)) {
        $errors['schule_herkunft'] = 'Bitte auswählen.';
    }
    if ($herkunft === 'ja') {
        if (empty($_POST['jahre_schule_herkunft']) || !preg_match('/^\d{1,2}$/', (string)$_POST['jahre_schule_herkunft'])) {
            $errors['jahre_schule_herkunft'] = 'Bitte Anzahl Jahre angeben.';
        }
    }

    // Deutsch-Niveau – optional, aber nur erlaubte Werte
    $niveau = (string)($_POST['deutsch_niveau'] ?? '');
    if ($niveau !== '' && !in_array($niveau, $GERMAN_LEVELS, true)) {
        $errors['deutsch_niveau'] = 'Ungültige Auswahl.';
    }

    // Interessen – min 1, max 2
    $chosen       = array_keys(array_filter($_POST['interessen'] ?? [], fn($v) => $v === '1'));
    $chosen_valid = array_values(array_intersect($chosen, array_keys($INTERESSEN)));
    if (count($chosen_valid) < 1) $errors['interessen'] = 'Bitte mindestens 1 Bereich wählen.';
    if (count($chosen_valid) > 2) $errors['interessen'] = 'Bitte höchstens 2 Bereiche wählen.';

    // Speichern & weiter
    if (!$errors) {
        $seit_wann_norm = ($seit_monat !== '' && $seit_jahr !== '')
            ? ($seit_monat . '.' . $seit_jahr)
            : $seit_text;

        $_SESSION['form']['school'] = [
            'schule_aktuell'        => (string)$_POST['schule_aktuell'],
            'klassenlehrer'         => trim((string)$_POST['klassenlehrer']),
            'mail_lehrkraft'        => trim((string)($_POST['mail_lehrkraft'] ?? '')),
            'seit_monat'            => $seit_monat,
            'seit_jahr'             => $seit_jahr,
            'seit_text'             => $seit_text,
            'seit_wann_schule'      => $seit_wann_norm,
            'jahre_in_de'           => (string)$_POST['jahre_in_de'],
            'schule_herkunft'       => $herkunft,
            'jahre_schule_herkunft' => trim((string)($_POST['jahre_schule_herkunft'] ?? '')),
            'familiensprache'       => trim((string)$_POST['familiensprache']),
            'deutsch_niveau'        => $niveau,
            'interessen'            => $chosen_valid,
        ];

        // Persistenz (DB wenn möglich; sonst Session)
        $save = save_scope_allow_noemail('school', $_SESSION['form']['school']);
        $_SESSION['last_save'] = $save;

        if (function_exists('flash_set')) {
            if ($save['ok'] ?? false) {
                flash_set('success', 'Daten gespeichert.');
            } else {
                $msg = $save['err'] ?? 'Zwischenspeicherung in der Session.';
                flash_set('info', $msg);
            }
        }

        header('Location: /form_upload.php');
        exit;
    }
}

// ---------- Header-Infos ----------
$title     = 'Schritt 2/4 – Schule & Interessen';
$html_lang = 'de';
$html_dir  = 'ltr';

require __DIR__ . '/partials/header.php'; // allgemeiner Header
require APP_APPDIR . '/header.php';       // App-Header (Status/Token)

// Vorauswahl für Radios etc.
$herk = $_SESSION['form']['school']['schule_herkunft'] ?? ($_POST['schule_herkunft'] ?? '');
?>
<div class="container py-4">
  <?php
  // Erfolgsmeldungen ausblenden (werden bereits im App-Header gezeigt)
  if (!empty($_SESSION['flash'])) {
      $_SESSION['flash'] = array_values(array_filter(
          $_SESSION['flash'],
          fn($f) => (($f['type'] ?? '') !== 'success')
      ));
  }
  if (function_exists('flash_render')) { flash_render(); }
  ?>


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
                <?php $malt = $_SESSION['form']['school']['seit_monat'] ?? ($_POST['seit_monat'] ?? ''); ?>
                <select name="seit_monat" class="form-select<?= has_err('seit_monat',$errors) ?>">
                  <option value="">Monat (MM)</option>
                  <?php for ($m=1; $m<=12; $m++):
                      $mm = str_pad((string)$m, 2, '0', STR_PAD_LEFT);
                  ?>
                    <option value="<?= $mm ?>" <?= $malt===$mm ? 'selected' : '' ?>><?= $mm ?></option>
                  <?php endfor; ?>
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
              <?php $herk = $_SESSION['form']['school']['schule_herkunft'] ?? ($_POST['schule_herkunft'] ?? ''); ?>
              <input type="radio" class="btn-check" name="schule_herkunft" id="s_j" value="ja"   <?= $herk==='ja'?'checked':''; ?> required>
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

<script>
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

<?php require __DIR__ . '/partials/footer.php'; ?>
