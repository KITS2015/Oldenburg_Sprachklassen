<?php
// public/datenschutz.php
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
$title     = t('privacy.title');
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

      <h1 class="h3 mb-3"><?= h(t('privacy.h1')) ?></h1>

      <h2 class="h5 mt-4"><?= h(t('privacy.s1_title')) ?></h2>
      <p><?= t('privacy.s1_body_html') ?></p>

      <h2 class="h5 mt-4"><?= h(t('privacy.s2_title')) ?></h2>
      <p><?= t('privacy.s2_body_html') ?></p>

      <h2 class="h5 mt-4"><?= h(t('privacy.s3_title')) ?></h2>
      <ul>
        <li><?= h(t('privacy.s3_li1')) ?></li>
        <li><?= h(t('privacy.s3_li2')) ?></li>
        <li><?= h(t('privacy.s3_li3')) ?></li>
      </ul>

      <h2 class="h5 mt-4"><?= h(t('privacy.s4_title')) ?></h2>
      <ul>
        <li><?= h(t('privacy.s4_li1')) ?></li>
        <li><?= h(t('privacy.s4_li2')) ?></li>
        <li><?= h(t('privacy.s4_li3')) ?></li>
      </ul>

      <h2 class="h5 mt-4"><?= h(t('privacy.s5_title')) ?></h2>
      <ul>
        <li><?= h(t('privacy.s5_li1')) ?></li>
        <li><?= h(t('privacy.s5_li2')) ?></li>
        <li><?= h(t('privacy.s5_li3')) ?></li>
        <li><?= h(t('privacy.s5_li4')) ?></li>
      </ul>

      <h2 class="h5 mt-4"><?= h(t('privacy.s6_title')) ?></h2>
      <p><?= h(t('privacy.s6_body')) ?></p>

      <h2 class="h5 mt-4"><?= h(t('privacy.s7_title')) ?></h2>
      <p><?= h(t('privacy.s7_body')) ?></p>

      <h2 class="h5 mt-4"><?= h(t('privacy.s8_title')) ?></h2>
      <p><?= h(t('privacy.s8_body')) ?></p>

      <h2 class="h5 mt-4"><?= h(t('privacy.s9_title')) ?></h2>
      <ul>
        <li><?= h(t('privacy.s9_li1')) ?></li>
        <li><?= h(t('privacy.s9_li2')) ?></li>
        <li><?= h(t('privacy.s9_li3')) ?></li>
        <li><?= h(t('privacy.s9_li4')) ?></li>
      </ul>

      <h2 class="h5 mt-4"><?= h(t('privacy.s10_title')) ?></h2>
      <p><?= h(t('privacy.s10_body')) ?></p>

      <h2 class="h5 mt-4"><?= h(t('privacy.s11_title')) ?></h2>
      <ul>
        <li><?= t('privacy.s11_li1_html') ?></li>
        <li><?= h(t('privacy.s11_li2')) ?></li>
      </ul>

      <p class="mt-4 small text-muted">
        <?= h(t('privacy.stand_label')) ?>: <?= h($today) ?> – <?= h(t('privacy.stand_hint')) ?>
      </p>

      <a href="/index.php" class="btn btn-outline-secondary mt-2">
        <?= h(t('privacy.back_home')) ?>
      </a>

    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
