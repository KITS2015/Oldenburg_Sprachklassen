<?php
// public/partials/header.php
declare(strict_types=1);

// Usage:
//   $title     = '...';             // optional
//   $html_lang = 'de';              // optional
//   $html_dir  = 'ltr';             // optional
//   $extra_css = ['/assets/x.css']; // optional array of hrefs

$title     = $title     ?? 'Online-Anmeldung – Sprachklassen';
$html_lang = $html_lang ?? 'de';
$html_dir  = $html_dir  ?? 'ltr';
$extra_css = $extra_css ?? [];
?>
<!doctype html>
<html lang="<?= htmlspecialchars($html_lang, ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars($html_dir, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>

  <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/form.css">
  <link rel="icon" href="/favicon.ico" sizes="any">
<?php foreach ($extra_css as $href): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars((string)$href, ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>

  <style>
    .card { border-radius: 1rem; }
  </style>
</head>
<body class="bg-light">

<?php
// Sprachdaten (falls i18n nicht verfügbar ist, fallback)
$lang      = (string)($_SESSION['lang'] ?? ($html_lang ?? 'de'));
$languages = function_exists('i18n_languages') ? i18n_languages() : ['de' => 'Deutsch'];

// aktuelle URL ermitteln (Pfad + Query), dann lang in Query setzen
$path  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$query = [];
parse_str((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_QUERY) ?? ''), $query);

// Optional: Label-Text
$langLabel = function_exists('t') ? t('index.lang_label') : 'Sprache';
?>

<header class="border-bottom bg-white">
  <div class="container py-2">
    <div class="d-flex justify-content-end">
        <form method="get"
              action="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>"
              class="d-flex flex-nowrap align-items-center gap-2">
        <?php
        // alle bestehenden Query-Parameter als hidden übernehmen (außer lang)
        foreach ($query as $k => $v) {
          if ($k === 'lang') continue;
          if (is_array($v)) continue; // simpel halten; falls du Arrays brauchst, sag Bescheid
          echo '<input type="hidden" name="'.htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8').'" value="'.htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8').'">';
        }
        ?>

          <label class="fw-semibold mb-0 text-nowrap" for="lang">
            <?= htmlspecialchars((string)$langLabel, ENT_QUOTES, 'UTF-8') ?>
          </label>

        <select
          class="form-select form-select-sm"
          name="lang"
          id="lang"
          onchange="this.form.submit()"
          style="max-width: 220px;"
        >
          <?php foreach ($languages as $code => $label): ?>
            <option value="<?= htmlspecialchars((string)$code, ENT_QUOTES, 'UTF-8') ?>" <?= ((string)$code === $lang) ? 'selected' : '' ?>>
              <?= htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>

        <noscript>
          <button class="btn btn-sm btn-primary">OK</button>
        </noscript>
      </form>
    </div>
  </div>
</header>
