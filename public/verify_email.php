<?php
// public/verify_email.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/email.php'; // send_verification_email()

// --- Einstellungen ---
const RESEND_COOLDOWN_SECONDS = 60;    // Cooldown für erneutes Senden
const CODE_TTL_SECONDS        = 3600;  // Code 1h gültig
const MAX_TRIES               = 6;

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
// Helper
function mask_email(string $email): string {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return $email;
    [$local, $domain] = explode('@', $email, 2);
    $l = mb_strlen($local);
    if ($l <= 2) return $local.'@'.$domain;
    return mb_substr($local, 0, 1) . str_repeat('•', max(1, $l - 2)) . mb_substr($local, -1) . '@' . $domain;
}

function send_verification_email_compat(string $to, string $code, string $lang): bool {
    if (!function_exists('send_verification_email')) return false;

    try {
        $rf = new ReflectionFunction('send_verification_email');
        $n  = $rf->getNumberOfParameters();
        if ($n >= 3) {
            return (bool)$rf->invoke($to, $code, $lang);
        }
        return (bool)$rf->invoke($to, $code);
    } catch (Throwable $e) {
        error_log('verify_email send_verification_email_compat: '.$e->getMessage());
        // Fallback ohne Reflection
        try {
            return (bool)@send_verification_email($to, $code);
        } catch (Throwable $e2) {
            error_log('verify_email send_verification_email fallback: '.$e2->getMessage());
            return false;
        }
    }
}

function make_code_6(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Session-State erwartet (neu):
// $_SESSION['email_verify'] = ['email'=>..., 'hash'=>..., 'exp'=>..., 'tries'=>...]
// $_SESSION['email_verify_last'] = unix_ts

$msgKey = '';
$msgVars = [];
$alert = 'info';

$entry = $_SESSION['email_verify'] ?? null;

$cooldownLeft = 0;
if (!empty($_SESSION['email_verify_last'])) {
    $since = time() - (int)$_SESSION['email_verify_last'];
    if ($since < RESEND_COOLDOWN_SECONDS) {
        $cooldownLeft = RESEND_COOLDOWN_SECONDS - $since;
    }
}

// ------------------------------------------------------------
// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        http_response_code(400);
        exit(t('verify_email.csrf_invalid'));
    }

    $action = (string)($_POST['action'] ?? 'verify');

    if ($action === 'verify') {
        $code  = trim((string)($_POST['code'] ?? ''));
        $entry = $_SESSION['email_verify'] ?? null;

        if (!$entry || empty($entry['hash']) || empty($entry['exp'])) {
            $msgKey = 'verify_email.error_no_session';
            $alert  = 'danger';
        } elseif (time() > (int)$entry['exp']) {
            $msgKey = 'verify_email.error_expired';
            $alert  = 'danger';
        } else {
            $_SESSION['email_verify']['tries'] = (int)($entry['tries'] ?? 0) + 1;

            if ((int)$_SESSION['email_verify']['tries'] > MAX_TRIES) {
                $msgKey = 'verify_email.error_rate';
                $alert  = 'warning';
            } elseif ($code === '' || !preg_match('/^\d{6}$/', $code)) {
                $msgKey = 'verify_email.error_code_format';
                $alert  = 'danger';
            } elseif (!password_verify($code, (string)$entry['hash'])) {
                $msgKey = 'verify_email.error_invalid';
                $alert  = 'danger';
            } else {
                $_SESSION['email_verified'] = true;
                $msgKey = 'verify_email.ok_verified';
                $alert  = 'success';

                // Optional: Verify-State wegwerfen (damit Code nicht erneut benutzt wird)
                unset($_SESSION['email_verify'], $_SESSION['email_verify_last']);

                // Optional: flash + zurück
                if (function_exists('flash_set')) {
                    flash_set('success', t('verify_email.ok_verified'));
                }
                header('Location: /form_personal.php');
                exit;
            }
        }

    } elseif ($action === 'resend') {
        // Empfänger ermitteln
        $to = '';
        $entry = $_SESSION['email_verify'] ?? null;

        if (!empty($entry['email']) && filter_var((string)$entry['email'], FILTER_VALIDATE_EMAIL)) {
            $to = (string)$entry['email'];
        } else {
            $to = trim((string)($_POST['email_fallback'] ?? ''));
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $msgKey = 'verify_email.error_email';
                $alert  = 'danger';
            }
        }

        if ($msgKey === '') {
            if ($cooldownLeft > 0) {
                $msgKey = 'verify_email.warn_cooldown';
                $alert  = 'warning';
            } else {
                $code = make_code_6();

                $_SESSION['email_verify'] = [
                    'email' => $to,
                    'hash'  => password_hash($code, PASSWORD_DEFAULT),
                    'exp'   => time() + CODE_TTL_SECONDS,
                    'tries' => 0,
                ];
                $_SESSION['email_verify_last'] = time();

                if (send_verification_email_compat($to, $code, $lang)) {
                    $msgKey  = 'verify_email.ok_sent';
                    $msgVars = ['email' => mask_email($to)];
                    $alert   = 'success';
                    $cooldownLeft = RESEND_COOLDOWN_SECONDS;
                } else {
                    $msgKey = 'verify_email.error_send';
                    $alert  = 'danger';
                }
            }
        }
    }
}

// Anzeige-Info
$hasSessionEmail = !empty($_SESSION['email_verify']['email']);
$masked = $hasSessionEmail ? mask_email((string)$_SESSION['email_verify']['email']) : null;

// Rendering
$title     = t('verify_email.title');
$html_lang = $lang;
$html_dir  = $dir;

require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';
?>
<style>
<?php if ($rtl): ?> body { text-align:right; direction:rtl; } <?php endif; ?>
.card { border-radius: 1rem; }
</style>

<div class="container py-4">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-3"><?= h(t('verify_email.h1')) ?></h1>

      <?php if ($msgKey): ?>
        <div class="alert alert-<?= h($alert) ?>">
          <?php
            // einfache Platzhalter-Substitution
            $s = t($msgKey);
            foreach ($msgVars as $k => $v) {
              $s = str_replace('{'.$k.'}', (string)$v, $s);
            }
            echo h($s);
          ?>
        </div>
      <?php endif; ?>

      <?php if ($hasSessionEmail): ?>
        <p class="text-muted">
          <?= h(str_replace('{email}', (string)$masked, t('verify_email.lead_sent'))) ?>
        </p>
      <?php else: ?>
        <p class="text-muted"><?= h(t('verify_email.lead_generic')) ?></p>
      <?php endif; ?>

      <div class="row g-4">
        <div class="col-md-6">
          <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="verify">

            <div class="mb-3">
              <label class="form-label"><?= h(t('verify_email.code_label')) ?></label>
              <input name="code" class="form-control" inputmode="numeric" pattern="\d{6}" maxlength="6" required>
            </div>

            <div class="d-flex gap-2">
              <a class="btn btn-outline-secondary" href="/form_personal.php"><?= h(t('verify_email.back')) ?></a>
              <button class="btn btn-primary"><?= h(t('verify_email.btn_verify')) ?></button>
            </div>
          </form>
        </div>

        <div class="col-md-6">
          <form method="post" id="resend-form">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="resend">

            <?php if (!$hasSessionEmail): ?>
              <div class="mb-3">
                <label class="form-label"><?= h(t('verify_email.email_label')) ?></label>
                <input type="email" name="email_fallback" class="form-control" placeholder="name@example.org" required>
              </div>
            <?php endif; ?>

            <button id="btn-resend" class="btn btn-outline-primary" <?= $cooldownLeft > 0 ? 'disabled' : '' ?>>
              <?= h(t('verify_email.btn_resend')) ?>
              <span id="cooldown-timer" class="ms-1 text-muted small">
                <?= $cooldownLeft > 0 ? '('.(int)$cooldownLeft.' s)' : '' ?>
              </span>
            </button>

            <div class="form-text"><?= h(t('verify_email.hint_spam')) ?></div>
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

<?php require __DIR__ . '/partials/footer.php'; ?>
