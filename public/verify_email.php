<?php
// public/verify_email.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/email.php'; // send_verification_email()

// --- Einstellungen ---
const RESEND_COOLDOWN_SECONDS = 60;   // Cooldown für erneutes Senden
const CODE_TTL_SECONDS        = 3600; // Code 1h gültig

$msg = '';
$alert = 'info';

$entry = $_SESSION['email_verify'] ?? null;   // ['code'=>..., 'ts'=>..., 'email'=>...]
$cooldownLeft = 0;
if (!empty($_SESSION['email_verify_last'])) {
  $since = time() - (int)$_SESSION['email_verify_last'];
  if ($since < RESEND_COOLDOWN_SECONDS) {
    $cooldownLeft = RESEND_COOLDOWN_SECONDS - $since;
  }
}

// kleine Helper
function mask_email(string $email): string {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return $email;
  [$local, $domain] = explode('@', $email, 2);
  $l = mb_strlen($local);
  if ($l <= 2) return $local.'@'.$domain;
  return mb_substr($local, 0, 1) . str_repeat('•', max(1, $l-2)) . mb_substr($local, -1) . '@' . $domain;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

  $action = $_POST['action'] ?? 'verify';

  if ($action === 'verify') {
    // Code prüfen
    $code  = trim((string)($_POST['code'] ?? ''));
    $entry = $_SESSION['email_verify'] ?? null;

    if ($entry && $code === ($entry['code'] ?? '') && time() - (int)($entry['ts'] ?? 0) < CODE_TTL_SECONDS) {
      $_SESSION['email_verified'] = true;
      $msg = 'E-Mail erfolgreich verifiziert.';
      $alert = 'success';
    } else {
      $msg = 'Code ungültig oder abgelaufen.';
      $alert = 'danger';
    }

  } elseif ($action === 'resend') {
    // Code erneut versenden
    $to = '';
    if (!empty($entry['email'])) {
      $to = (string)$entry['email'];
    } else {
      // Fallback: E-Mail manuell eingeben (wenn Session leer)
      $to = trim((string)($_POST['email_fallback'] ?? ''));
      if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        $alert = 'danger';
      }
    }

    if (!$msg) {
      if ($cooldownLeft > 0) {
        $msg = 'Bitte warten Sie kurz, bevor Sie den Code erneut anfordern.';
        $alert = 'warning';
      } else {
        // neuen Code erzeugen und senden
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['email_verify'] = ['code'=>$code, 'ts'=>time(), 'email'=>$to];
        $_SESSION['email_verify_last'] = time();

        if (send_verification_email($to, $code)) {
          $msg = 'Neuer Code wurde an ' . h(mask_email($to)) . ' gesendet.';
          $alert = 'success';
          $cooldownLeft = RESEND_COOLDOWN_SECONDS; // UI-Cooldown neu starten
        } else {
          $msg = 'Versand fehlgeschlagen. Bitte später erneut versuchen.';
          $alert = 'danger';
        }
      }
    }
  }
}

// Anzeige-Info
$hasSessionEmail = !empty($_SESSION['email_verify']['email']);
$masked = $hasSessionEmail ? mask_email((string)$_SESSION['email_verify']['email']) : null;
?>
<!doctype html>
<html lang="de"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>E-Mail verifizieren</title>
<link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-3">E-Mail verifizieren</h1>

      <?php if ($msg): ?>
        <div class="alert alert-<?= h($alert) ?>"><?= h($msg) ?></div>
      <?php endif; ?>

      <?php if ($hasSessionEmail): ?>
        <p class="text-muted">Wir haben einen Bestätigungscode an <strong><?= h($masked) ?></strong> gesendet.</p>
      <?php else: ?>
        <p class="text-muted">Bitte geben Sie den per E-Mail erhaltenen Bestätigungscode ein. Falls Sie keine E-Mail sehen, können Sie sich den Code an Ihre Adresse senden lassen.</p>
      <?php endif; ?>

      <div class="row g-4">
        <div class="col-md-6">
          <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="verify">
            <div class="mb-3">
              <label class="form-label">Bestätigungscode (6 Ziffern)</label>
              <input name="code" class="form-control" inputmode="numeric" pattern="\d{6}" required>
            </div>
            <div class="d-flex gap-2">
              <a class="btn btn-outline-secondary" href="/form_personal.php">Zurück</a>
              <button class="btn btn-primary">Bestätigen</button>
            </div>
          </form>
        </div>

        <div class="col-md-6">
          <form method="post" id="resend-form">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="resend">
            <?php if (!$hasSessionEmail): ?>
              <div class="mb-3">
                <label class="form-label">Ihre E-Mail-Adresse</label>
                <input type="email" name="email_fallback" class="form-control" placeholder="name@example.org" required>
              </div>
            <?php endif; ?>

            <button id="btn-resend" class="btn btn-outline-primary" <?= $cooldownLeft>0?'disabled':''; ?>>
              Code erneut senden
              <span id="cooldown-timer" class="ms-1 text-muted small"><?= $cooldownLeft>0 ? "($cooldownLeft s)" : "" ?></span>
            </button>
            <div class="form-text">Bitte prüfen Sie auch den Spam-Ordner.</div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
<script>
(function(){
  var left = <?= (int)$cooldownLeft ?>;
  if (left > 0) {
    var btn  = document.getElementById('btn-resend');
    var span = document.getElementById('cooldown-timer');
    var timer = setInterval(function(){
      left--;
      if (left <= 0) {
        clearInterval(timer);
        if (btn) btn.disabled = false;
        if (span) span.textContent = '';
      } else {
        if (span) span.textContent = '('+left+' s)';
      }
    }, 1000);
  }
})();
</script>
</body>
</html>
