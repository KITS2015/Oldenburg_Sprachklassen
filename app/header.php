<?php
// /app/header.php
// Topbar innerhalb des Body mit Statuspfeil (grün/rot) und Access-Token.
declare(strict_types=1);

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$last   = $_SESSION['last_save'] ?? [];
$ok     = $last['ok']    ?? null;               // true/false/null (noch nichts gespeichert)
$token  = $last['token'] ?? ($_SESSION['access_token'] ?? '');
$err    = $last['err']   ?? '';

// Status-Darstellung
$statusText = 'Noch nichts gespeichert';
$statusClass = 'text-muted';
$arrowColor = '#adb5bd'; // grau

if ($ok === true) {
  $statusText = 'Daten gespeichert';
  $statusClass = 'text-success';
  $arrowColor = '#198754'; // bootstrap green
} elseif ($ok === false) {
  $statusText = 'Fehler beim Speichern';
  $statusClass = 'text-danger';
  $arrowColor = '#dc3545'; // bootstrap red
}
?>
<style>
  .app-topbar { background:#fff; }
  .app-topbar .arrow {
    width: 0.9rem; height: 0.9rem; vertical-align: -0.1rem; margin-right: .35rem;
  }
</style>

<div class="app-topbar border-bottom shadow-sm mb-3">
  <div class="container d-flex justify-content-between align-items-center py-2 small">
    <div class="<?= $statusClass ?>">
      <!-- Kleiner Pfeil (SVG), Farbe je nach Status -->
      <svg class="arrow" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path fill="<?= h($arrowColor) ?>" d="M2 9.5l4.5-4.5L11 9.5l-1.4 1.4L6.5 7.8l-3.1 3.1L2 9.5z"/>
      </svg>
      <?= h($statusText) ?>
      <?php if ($token): ?>
        &nbsp;|&nbsp; Access&nbsp;Token: <span class="token-badge"><code><?= h($token) ?></code></span>
      <?php endif; ?>
      <?php if ($ok === false && $err): ?>
        &nbsp;<span class="text-muted">(<?= h($err) ?>)</span>
      <?php endif; ?>
    </div>
    <div class="text-muted"><?= date('d.m.Y, H:i') ?></div>
  </div>
</div>
