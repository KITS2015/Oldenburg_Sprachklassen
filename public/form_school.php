<?php
// public/form_school.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/functions_form.php'; // DB-/Save-Helper

require_step('school');

$errors = [];

// kleine Helper für i18n Platzhalter (z. B. {x})
function tr(string $key, array $vars = []): string {
    $s = t($key);
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string)$v, $s);
    }
    return $s;
}

function field_error(string $key, array $errors): string {
    if (empty($errors[$key])) return '';
    return '<div class="invalid-feedback d-block">' . h((string)$errors[$key]) . '</div>';
}

// Optionen
global $SCHULEN, $INTERESSEN;

// Deutsch-Niveau (erweitert; in review akzeptierst du auch B2/C1/C2/kein)
$GERMAN_LEVELS_EXT = [
    ''     => t('school.deutsch_choose'),
    'kein' => 'kein',
    'A0'   => 'A0',
    'A1'   => 'A1',
    'A2'   => 'A2',
    'B1'   => 'B1',
    'B2'   => 'B2',
    'C1'   => 'C1',
    'C2'   => 'C2',
];

// Monate/Jahre
$months = range(1, 12);
$yearNow = (int)date('Y');
$years = range($yearNow, $yearNow - 15); // reicht meist aus

// ---------- POST: Validierung & Speichern ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

    $schule_aktuell   = trim((string)($_POST['schule_aktuell'] ?? ''));
    $schule_freitext  = trim((string)($_POST['schule_freitext'] ?? ''));
    $klassenlehrer    = trim((string)($_POST['klassenlehrer'] ?? ''));
    $mail_lehrkraft   = trim((string)($_POST['mail_lehrkraft'] ?? ''));
    $seit_monat_raw   = (string)($_POST['seit_monat'] ?? '');
    $seit_jahr_raw    = (string)($_POST['seit_jahr'] ?? '');
    $jahre_in_de_raw  = trim((string)($_POST['jahre_in_de'] ?? ''));
    $familiensprache  = trim((string)($_POST['familiensprache'] ?? ''));
    $deutsch_niveau   = trim((string)($_POST['deutsch_niveau'] ?? ''));
    $schule_herkunft  = (string)($_POST['schule_herkunft'] ?? '');
    $jahre_sh_raw     = trim((string)($_POST['jahre_schule_herkunft'] ?? ''));
    $interessen_in    = $_POST['interessen'] ?? [];

    // Schule: muss gewählt werden
    if ($schule_aktuell === '' || !isset($SCHULEN[$schule_aktuell])) {
        $errors['schule_aktuell'] = t('val.school_required');
    }

    // Freitext nur wenn IGS_FALLBACK
    if ($schule_aktuell === 'IGS_FALLBACK') {
        if ($schule_freitext === '') {
            $errors['schule_freitext'] = t('val.school_freitext_required');
        }
    } else {
        $schule_freitext = '';
    }

    // Lehrer*in: Pflicht (laut Review-Seite vorhanden; falls bei dir optional sein soll, sag kurz Bescheid)
    if ($klassenlehrer === '') {
        $errors['klassenlehrer'] = t('val.teacher_name_required');
    }

    // E-Mail Lehrkraft optional
    if ($mail_lehrkraft !== '' && !filter_var($mail_lehrkraft, FILTER_VALIDATE_EMAIL)) {
        $errors['mail_lehrkraft'] = t('val.email_invalid'); // existiert schon bei dir
    }

    // Seit wann: Monat+Jahr Pflicht
    $seit_monat = null;
    $seit_jahr  = null;
    if ($seit_monat_raw === '' || $seit_jahr_raw === '') {
        $errors['seit'] = t('val.since_required');
    } else {
        if (ctype_digit($seit_monat_raw)) $seit_monat = (int)$seit_monat_raw;
        if (ctype_digit($seit_jahr_raw))  $seit_jahr  = (int)$seit_jahr_raw;

        if (!$seit_monat || $seit_monat < 1 || $seit_monat > 12 || !$seit_jahr || $seit_jahr < 1900 || $seit_jahr > $yearNow) {
            $errors['seit'] = t('val.since_required');
        }
    }

    // Jahre in Deutschland: Pflicht, Zahl
    if ($jahre_in_de_raw === '') {
        $errors['jahre_in_de'] = t('val.jahre_in_de_required');
    } elseif (!ctype_digit($jahre_in_de_raw)) {
        $errors['jahre_in_de'] = t('val.jahre_in_de_invalid');
    }
    $jahre_in_de = ($jahre_in_de_raw !== '' && ctype_digit($jahre_in_de_raw)) ? (int)$jahre_in_de_raw : null;

    // Familiensprache: Pflicht
    if ($familiensprache === '') {
        $errors['familiensprache'] = t('val.familiensprache_required');
    }

    // Deutsch-Niveau: Pflicht
    if ($deutsch_niveau === '' || !isset($GERMAN_LEVELS_EXT[$deutsch_niveau])) {
        $errors['deutsch_niveau'] = t('val.deutsch_niveau_required');
    }

    // Schule Herkunft: Pflicht ja/nein
    if (!in_array($schule_herkunft, ['ja','nein'], true)) {
        $errors['schule_herkunft'] = t('val.schule_herkunft_required');
    }

    // Jahre Schule Herkunft nur wenn ja
    $jahre_schule_herkunft = null;
    if ($schule_herkunft === 'ja') {
        if ($jahre_sh_raw === '') {
            $errors['jahre_schule_herkunft'] = t('val.jahre_schule_herkunft_required');
        } elseif (!ctype_digit($jahre_sh_raw)) {
            $errors['jahre_schule_herkunft'] = t('val.jahre_schule_herkunft_invalid');
        } else {
            $jahre_schule_herkunft = (int)$jahre_sh_raw;
        }
    }

    // Interessen: optional, aber nur erlaubte Keys speichern
    $interessen = [];
    if (is_array($interessen_in)) {
        foreach ($interessen_in as $k) {
            $k = (string)$k;
            if (isset($INTERESSEN[$k])) $interessen[] = $k;
        }
    }

    // Speichern
    if (!$errors) {
        $schule_label = $SCHULEN[$schule_aktuell] ?? '';

        $_SESSION['form']['school'] = [
            'schule_aktuell'        => $schule_aktuell,
            'schule_label'          => $schule_label,
            'schule_freitext'       => $schule_freitext,

            'klassenlehrer'         => $klassenlehrer,
            'mail_lehrkraft'        => $mail_lehrkraft,

            'seit_monat'            => $seit_monat,
            'seit_jahr'             => $seit_jahr,

            'jahre_in_de'           => $jahre_in_de,
            'familiensprache'       => $familiensprache,
            'deutsch_niveau'        => $deutsch_niveau,

            'schule_herkunft'       => $schule_herkunft,
            'jahre_schule_herkunft' => $jahre_schule_herkunft,

            'interessen'            => $interessen,
        ];

        $save = save_scope_allow_noemail('school', $_SESSION['form']['school']);
        $_SESSION['last_save'] = $save;

        if (function_exists('flash_set')) {
            if (!empty($save['ok'])) {
                flash_set('success', 'Daten gespeichert.');
            } else {
                flash_set('warning', 'Daten gespeichert (Session). Hinweis: ' . (string)($save['err'] ?? ''));
            }
        }

        header('Location: ' . url_with_lang('/form_upload.php'));
        exit;
    }
}

// ---------- Header ----------
$title     = t('school.page_title');
$html_lang = html_lang();
$html_dir  = html_dir();

require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';

// Prefill
$prev = $_SESSION['form']['school'] ?? [];

$schule_aktuell = (string)($prev['schule_aktuell'] ?? ($_POST['schule_aktuell'] ?? ''));
$schule_freitext = (string)($prev['schule_freitext'] ?? ($_POST['schule_freitext'] ?? ''));
$klassenlehrer = (string)($prev['klassenlehrer'] ?? ($_POST['klassenlehrer'] ?? ''));
$mail_lehrkraft = (string)($prev['mail_lehrkraft'] ?? ($_POST['mail_lehrkraft'] ?? ''));

$seit_monat = (string)($prev['seit_monat'] ?? ($_POST['seit_monat'] ?? ''));
$seit_jahr  = (string)($prev['seit_jahr']  ?? ($_POST['seit_jahr']  ?? ''));

$jahre_in_de = (string)($prev['jahre_in_de'] ?? ($_POST['jahre_in_de'] ?? ''));
$familiensprache = (string)($prev['familiensprache'] ?? ($_POST['familiensprache'] ?? ''));
$deutsch_niveau  = (string)($prev['deutsch_niveau'] ?? ($_POST['deutsch_niveau'] ?? ''));

$schule_herkunft = (string)($prev['schule_herkunft'] ?? ($_POST['schule_herkunft'] ?? ''));
$jahre_sh = (string)($prev['jahre_schule_herkunft'] ?? ($_POST['jahre_schule_herkunft'] ?? ''));

$interessen_prev = $prev['interessen'] ?? ($_POST['interessen'] ?? []);
if (!is_array($interessen_prev)) $interessen_prev = [];

?>
<div class="container py-4">
  <?php if (function_exists('flash_render')) { flash_render(); } ?>

  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-2"><?= h(t('school.h1')) ?></h1>
      <div class="text-muted small mb-3"><?= h(t('school.required_hint')) ?></div>

      <?php if ($errors): ?>
        <div class="alert alert-danger"><?= h(t('school.form_error_hint')) ?></div>
      <?php endif; ?>

      <form method="post" action="" novalidate class="mt-3">
        <?php csrf_field(); ?>

        <div class="row g-3">
          <div class="col-12">
            <label class="form-label"><?= h(t('school.label.schule')) ?></label>
            <select name="schule_aktuell" class="form-select is-required<?= has_err('schule_aktuell',$errors) ?>" required onchange="toggleFreitext(this.value)">
              <option value=""><?= h(t('school.schule_choose')) ?></option>
              <?php foreach ($SCHULEN as $code => $label): ?>
                <?php if ($code === '') continue; ?>
                <option value="<?= h((string)$code) ?>" <?= ((string)$code === $schule_aktuell) ? 'selected' : '' ?>>
                  <?= h((string)$label) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?= field_error('schule_aktuell', $errors) ?>
          </div>

          <div class="col-12" id="schule-freitext-wrap" style="<?= $schule_aktuell === 'IGS_FALLBACK' ? '' : 'display:none;' ?>">
            <label class="form-label"><?= h(t('school.label.schule_freitext')) ?></label>
            <input
              name="schule_freitext"
              class="form-control<?= has_err('schule_freitext',$errors) ?>"
              value="<?= h($schule_freitext) ?>"
              placeholder="<?= h(t('school.placeholder.schule_freitext')) ?>">
            <?= field_error('schule_freitext', $errors) ?>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label"><?= h(t('school.label.klassenlehrer')) ?></label>
            <input
              name="klassenlehrer"
              class="form-control is-required<?= has_err('klassenlehrer',$errors) ?>"
              value="<?= h($klassenlehrer) ?>"
              placeholder="<?= h(t('school.placeholder.klassenlehrer')) ?>"
              required>
            <?= field_error('klassenlehrer', $errors) ?>
          </div>

          <div class="col-md-6">
            <label class="form-label"><?= h(t('school.label.mail_lehrkraft')) ?></label>
            <input
              name="mail_lehrkraft"
              type="email"
              class="form-control<?= has_err('mail_lehrkraft',$errors) ?>"
              value="<?= h($mail_lehrkraft) ?>"
              placeholder="<?= h(t('school.placeholder.mail_lehrkraft')) ?>">
            <?= field_error('mail_lehrkraft', $errors) ?>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label d-block"><?= h(t('school.label.since')) ?></label>

            <?php if (!empty($errors['seit'])): ?>
              <div class="text-danger small mb-2"><?= h((string)$errors['seit']) ?></div>
            <?php endif; ?>

            <div class="row g-2">
              <div class="col-6">
                <select name="seit_monat" class="form-select is-required<?= !empty($errors['seit']) ? ' is-invalid' : '' ?>" required>
                  <option value=""><?= h(t('school.since_choose')) ?></option>
                  <?php foreach ($months as $m): ?>
                    <option value="<?= (int)$m ?>" <?= ((string)$m === (string)$seit_monat) ? 'selected' : '' ?>><?= (int)$m ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text"><?= h(t('school.since_month')) ?></div>
              </div>

              <div class="col-6">
                <select name="seit_jahr" class="form-select is-required<?= !empty($errors['seit']) ? ' is-invalid' : '' ?>" required>
                  <option value=""><?= h(t('school.since_choose')) ?></option>
                  <?php foreach ($years as $y): ?>
                    <option value="<?= (int)$y ?>" <?= ((string)$y === (string)$seit_jahr) ? 'selected' : '' ?>><?= (int)$y ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text"><?= h(t('school.since_year')) ?></div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label"><?= h(t('school.label.jahre_in_de')) ?></label>
            <input
              name="jahre_in_de"
              class="form-control is-required<?= has_err('jahre_in_de',$errors) ?>"
              inputmode="numeric"
              pattern="^\d+$"
              value="<?= h($jahre_in_de) ?>"
              placeholder="<?= h(t('school.placeholder.jahre_in_de')) ?>"
              required>
            <?= field_error('jahre_in_de', $errors) ?>
            <div class="form-text"><?= h(t('school.hint.jahre_in_de')) ?></div>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label"><?= h(t('school.label.familiensprache')) ?></label>
            <input
              name="familiensprache"
              class="form-control is-required<?= has_err('familiensprache',$errors) ?>"
              value="<?= h($familiensprache) ?>"
              placeholder="<?= h(t('school.placeholder.familiensprache')) ?>"
              required>
            <?= field_error('familiensprache', $errors) ?>
          </div>

          <div class="col-md-6">
            <label class="form-label"><?= h(t('school.label.deutsch_niveau')) ?></label>
            <select name="deutsch_niveau" class="form-select is-required<?= has_err('deutsch_niveau',$errors) ?>" required>
              <?php foreach ($GERMAN_LEVELS_EXT as $code => $lbl): ?>
                <option value="<?= h((string)$code) ?>" <?= ((string)$code === (string)$deutsch_niveau) ? 'selected' : '' ?>>
                  <?= h((string)$lbl) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?= field_error('deutsch_niveau', $errors) ?>
            <div class="form-text"><?= h(t('school.deutsch_note')) ?></div>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-md-6">
            <label class="form-label d-block"><?= h(t('school.label.schule_herkunft')) ?></label>

            <?php $herErr = !empty($errors['schule_herkunft']); ?>
            <div class="<?= $herErr ? 'border border-danger rounded p-2' : 'is-required-group' ?>">
              <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="schule_herkunft" id="sh_ja" value="ja" <?= $schule_herkunft==='ja'?'checked':''; ?> required>
                <label class="btn btn-outline-primary" for="sh_ja"><?= h(t('school.schule_herkunft_yes')) ?></label>

                <input type="radio" class="btn-check" name="schule_herkunft" id="sh_nein" value="nein" <?= $schule_herkunft==='nein'?'checked':''; ?>>
                <label class="btn btn-outline-primary" for="sh_nein"><?= h(t('school.schule_herkunft_no')) ?></label>
              </div>
            </div>
            <?= field_error('schule_herkunft', $errors) ?>
          </div>

          <div class="col-md-6" id="jahre-herkunft-wrap" style="<?= $schule_herkunft==='ja' ? '' : 'display:none;' ?>">
            <label class="form-label"><?= h(t('school.label.jahre_schule_herkunft')) ?></label>
            <input
              name="jahre_schule_herkunft"
              class="form-control<?= has_err('jahre_schule_herkunft',$errors) ?>"
              inputmode="numeric"
              pattern="^\d+$"
              value="<?= h($jahre_sh) ?>"
              placeholder="<?= h(t('school.placeholder.jahre_schule_herkunft')) ?>">
            <?= field_error('jahre_schule_herkunft', $errors) ?>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-12">
            <label class="form-label"><?= h(t('school.label.interessen')) ?></label>
            <div class="text-muted small mb-2"><?= h(t('school.interessen_hint')) ?></div>

            <div class="row g-2">
              <?php foreach ($INTERESSEN as $k => $lbl): ?>
                <?php $checked = in_array((string)$k, array_map('strval',$interessen_prev), true); ?>
                <div class="col-12 col-md-6 col-lg-4">
                  <label class="form-check">
                    <input class="form-check-input" type="checkbox" name="interessen[]" value="<?= h((string)$k) ?>" <?= $checked ? 'checked' : '' ?>>
                    <span class="form-check-label"><?= h((string)$lbl) ?></span>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2 flex-wrap">
          <a href="<?= h(url_with_lang('/form_personal.php')) ?>" class="btn btn-outline-secondary">
            <?= h(t('school.btn.back')) ?>
          </a>

          <a href="<?= h(url_with_lang('/index.php')) ?>" class="btn btn-outline-secondary">
            <?= h(t('school.btn.cancel')) ?>
          </a>

          <button class="btn btn-primary">
            <?= h(t('school.btn.next')) ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleFreitext(val){
  const wrap = document.getElementById('schule-freitext-wrap');
  if (!wrap) return;
  wrap.style.display = (val === 'IGS_FALLBACK') ? '' : 'none';
}

(function(){
  const wrap = document.getElementById('jahre-herkunft-wrap');
  const radios = document.querySelectorAll('input[name="schule_herkunft"]');
  function update(){
    let v = '';
    radios.forEach(r => { if (r.checked) v = r.value; });
    if (wrap) wrap.style.display = (v === 'ja') ? '' : 'none';
  }
  radios.forEach(r => r.addEventListener('change', update));
  update();
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
