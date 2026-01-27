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
