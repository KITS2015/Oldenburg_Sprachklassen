<?php
// app/header.php
// Status-/Token-Leiste (wird in Seiten direkt nach public/partials/header.php eingebunden)
declare(strict_types=1);

// Optional kann die Seite $hdr setzen:
//   $hdr = ['status'=>'success|warning|danger|null','message'=>'...','token'=>'...'];
$hdr = $hdr ?? null;

if (!function_exists('h')) {
    function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$last = $_SESSION['last_save'] ?? null;

$status  = $hdr['status']  ?? null;
$message = $hdr['message'] ?? null;
$token   = $hdr['token']   ?? null;

if ($status === null || $message === null || $token === null) {
    // Fallback aus Session ableiten
    $ok  = (bool)($last['ok'] ?? false);
    $err = (string)($last['err'] ?? '');
    $tok = (string)($last['token'] ?? ($_SESSION['access_token'] ?? ''));

    if ($status === null) {
        if ($ok) $status = 'success';
        elseif ($err === 'nur Session (DOB fehlt)') $status = 'warning';
        elseif ($last !== null) $status = 'danger';
        else $status = null;
    }

    if ($message === null) {
        if ($ok) $message = 'Daten gespeichert';
        elseif ($err === 'nur Session (DOB fehlt)') $message = 'Zwischengespeichert (noch nicht dauerhaft)';
        elseif ($last !== null) $message = 'Speichern nicht möglich';
        else $message = '';
    }

    if ($token === null) {
        $token = ($tok !== '') ? $tok : null;
    }
}

$icon = '';
$cls  = '';
if ($status === 'success') { $icon = '▶'; $cls = 'text-success'; }
elseif ($status === 'warning') { $icon = '▶'; $cls = 'text-warning'; }
elseif ($status === 'danger') { $icon = '▶'; $cls = 'text-danger'; }

$show = ($message !== '') || ($token !== null);
if (!$show) { return; }
?>
<div class="bg-white border-bottom">
  <div class="container py-2">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div class="small">
        <?php if ($icon !== ''): ?>
          <span class="<?= $cls ?> me-1" aria-hidden="true"><?= $icon ?></span>
        <?php endif; ?>
        <span class="<?= $cls ?>"><?= h($message) ?></span>
        <?php if ($token !== null): ?>
          <span class="text-muted mx-2">|</span>
          <span class="text-muted">Access Token:</span>
          <code class="ms-1"><?= h($token) ?></code>
        <?php endif; ?>
      </div>
      <div class="small text-muted"><?= date('d.m.Y, H:i') ?></div>
    </div>
  </div>
</div>
