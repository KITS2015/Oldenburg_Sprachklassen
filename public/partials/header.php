<?php
// /public/partials/header.php
// Öffnet HTML-Dokument, bindet Bootstrap & form.css ein (kein Token/Status hier).
declare(strict_types=1);

// Optional: von der Seite setzen, sonst Defaults
$title = $title ?? 'Online-Anmeldung – Sprachklassen';
$lang  = $lang  ?? 'de';

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
?>
<!doctype html>
<html lang="<?= h($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($title) ?></title>

  <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/form.css">

  <style>
    /* Platz für seitenweite, generische Styles */
    .token-badge{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;}
  </style>
</head>
<body class="bg-light">
