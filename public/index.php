<?php
// public/index.php
// Mehrsprachiger Einstieg + 3 Buttons (ohne E-Mail / mit E-Mail / Zugriff)
// Texte liegen zentral in app/i18n.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php'; // Session, h(), i18n_detect_lang() etc.

// Sprache ist durch _common.php/i18n.php bereits ermittelt
$lang      = (string)($_SESSION['lang'] ?? 'de');
$languages = function_exists('i18n_languages') ? i18n_languages() : ['de' => 'Deutsch'];

$rtl = function_exists('i18n_is_rtl') ? i18n_is_rtl($lang) : in_array($lang, ['ar', 'fa'], true);
$dir = $rtl ? 'rtl' : 'ltr';

// Seitentitel & HTML-Parameter für Header
$title     = t('index.title');
$html_lang = $lang;
$html_dir  = $dir;

// Header: allgemeiner Seiten-Header + App-Topbar (Status/Token)
require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';

// Links (lang beibehalten)
$href_noemail = function_exists('i18n_url') ? i18n_url('/form_personal.php?mode=noemail', $lang) : '/form_personal.php?mode=noemail';
$href_create  = function_exists('i18n_url') ? i18n_url('/access_create.php', $lang) : '/access_create.php';
$href_load    = function_exists('i18n_url') ? i18n_url('/access_login.php', $lang) : '/access_login.php';

?>
<style>
  .lang-switch { gap: .5rem; }
  .card { border-radius: 1rem; }
  <?php if ($rtl): ?> body { text-align: right; } <?php endif; ?>
</style>

<div class="container py-5">

  <!-- Sprachwahl -->
  <div class="d-flex lang-switch justify-content-end mb-3">
    <form method="get" action="" class="d-flex lang-switch">
      <label class="me-2 fw-semibold" for="lang"><?= h(t('index.lang_label')) ?></label>
      <select class="form-select form-select-sm" name="lang" id="lang" onchange="this.form.submit()" style="max-width: 220px;">
        <?php foreach ($languages as $code => $label): ?>
          <option value="<?= h((string)$code) ?>" <?= ((string)$code === $lang) ? 'selected' : ''; ?>>
            <?= h((string)$label) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <noscript><button class="btn btn-sm btn-primary ms-2">OK</button></noscript>
    </form>
  </div>

  <div class="card shadow border-0">
    <div class="card-body p-4 p-md-5">
      <h1 class="h3 mb-3"><?= h(t('index.title')) ?></h1>
      <p class="lead mb-4"><?= h(t('index.lead')) ?></p>

      <?php $bullets = t_arr('index.bullets'); ?>
      <?php if (!empty($bullets)): ?>
        <ul class="mb-4">
          <?php foreach ($bullets as $li): ?>
            <li><?= h((string)$li) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php $infoP = t_arr('index.info_p'); ?>
      <?php $infoB = t_arr('index.info_bullets'); ?>
      <?php if (!empty($infoP) || !empty($infoB)): ?>
        <div class="alert alert-info mb-4">
          <?php foreach ($infoP as $p): ?>
            <p class="mb-2"><?= h((string)$p) ?></p>
          <?php endforeach; ?>

          <?php if (!empty($infoB)): ?>
            <ul class="mb-0">
              <?php foreach ($infoB as $li): ?>
                <li><?= h((string)$li) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Zugang/DSGVO -->
      <div class="alert alert-secondary mb-4">
        <h2 class="h5 mb-2"><?= h(t('index.access_title')) ?></h2>
        <p class="mb-2"><?= h(t('index.access_intro')) ?></p>

        <?php $accessPoints = t_arr('index.access_points'); ?>
        <?php if (!empty($accessPoints)): ?>
          <ul class="mb-0">
            <?php foreach ($accessPoints as $li): ?>
              <li><?= $li /* enthält <strong> bewusst unge-escaped */ ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <!-- Aktionen -->
      <div class="d-flex flex-column flex-md-row gap-2">
        <a href="<?= h($href_noemail) ?>" class="btn btn-primary flex-fill">
          <?= h(t('index.btn_noemail')) ?>
        </a>

        <a href="<?= h($href_create) ?>" class="btn btn-outline-primary flex-fill">
          <?= h(t('index.btn_create')) ?>
        </a>

        <a href="<?= h($href_load) ?>" class="btn btn-outline-secondary flex-fill">
          <?= h(t('index.btn_load')) ?>
        </a>
      </div>

    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
