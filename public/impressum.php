<?php
// public/impressum.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';

// ------------------------------------------------------------
// Sprache wie index.php (Cookie) + RTL
$languages = [
    'de' => 'Deutsch',
    'en' => 'English',
    'fr' => 'Français',
    'uk' => 'Українська',
    'ar' => 'العربية',
    'ru' => 'Русский',
    'tr' => 'Türkçe',
    'fa' => 'فارسی',
];

$lang = strtolower((string)($_COOKIE['lang'] ?? 'de'));
if (!array_key_exists($lang, $languages)) { $lang = 'de'; }
$rtl = in_array($lang, ['ar','fa'], true);
$dir = $rtl ? 'rtl' : 'ltr';

// ------------------------------------------------------------
// Rendering
$title     = t('imprint.title');
$html_lang = $lang;
$html_dir  = $dir;

require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';

$today = (new DateTimeImmutable('now'))->format('d.m.Y');
?>
<style>
<?php if ($rtl): ?> body { text-align:right; direction:rtl; } <?php endif; ?>
.card { border-radius: 1rem; }
</style>

<div class="container py-5">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">

      <h1 class="h3 mb-3"><?= h(t('imprint.h1')) ?></h1>

      <h2 class="h5 mt-4"><?= h(t('imprint.s1_title')) ?></h2>
      <p><?= t('imprint.s1_body_html') ?></p>

      <h2 class="h5 mt-4"><?= h(t('imprint.s2_title')) ?></h2>
      <p><?= t('imprint.s2_body_html') ?></p>

      <h2 class="h5 mt-4"><?= h(t('imprint.s3_title')) ?></h2>
      <p><?= t('imprint.s3_body_html') ?></p>

      <h2 class="h5 mt-4"><?= h(t('imprint.s4_title')) ?></h2>
      <p><?= t('imprint.s4_body_html') ?></p>

      <h2 class="h5 mt-4"><?= h(t('imprint.s5_title')) ?></h2>
      <p><?= h(t('imprint.s5_body')) ?></p>

      <h2 class="h5 mt-4"><?= h(t('imprint.s6_title')) ?></h2>
      <p><?= h(t('imprint.s6_body')) ?></p>

      <h2 class="h5 mt-4"><?= h(t('imprint.s7_title')) ?></h2>
      <p><?= h(t('imprint.s7_body')) ?></p>

      <h2 class="h5 mt-4"><?= h(t('imprint.s8_title')) ?></h2>
      <p><?= h(t('imprint.s8_body')) ?></p>

      <p class="mt-4 small text-muted">
        <?= h(t('imprint.stand_label')) ?>: <?= h($today) ?> – <?= h(t('imprint.stand_hint')) ?>
      </p>

      <a href="/index.php" class="btn btn-outline-secondary mt-2">
        <?= h(t('imprint.back_home')) ?>
      </a>

    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
