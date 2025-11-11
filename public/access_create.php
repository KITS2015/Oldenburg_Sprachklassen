<?php
// public/access_create.php
// Zugang mit E-Mail erstellen: E-Mail erfassen -> Code versenden -> Code prüfen
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';           // startet Session, h(), etc.
require_once __DIR__ . '/../app/email.php';        // send_verification_email()
require_once __DIR__ . '/../app/functions_form.php'; // ensure_record_email_only()

// ------------------------------------------------------------
// Sprache aus Cookie (wie index.php) + RTL bestimmen
$languages = [
  'de' => 'Deutsch','en' => 'English','fr' => 'Français','uk' => 'Українська',
  'ar' => 'العربية','ru' => 'Русский','tr' => 'Türkçe','fa' => 'فارسی',
];
$lang = strtolower($_COOKIE['lang'] ?? 'de');
if (!array_key_exists($lang, $languages)) { $lang = 'de'; }
$rtl  = in_array($lang, ['ar','fa'], true);
$dir  = $rtl ? 'rtl' : 'ltr';

// ------------------------------------------------------------
// Texte (DE/EN vollständig, restliche fallen auf EN zurück)
$t = [
  'de' => [
    'title'         => 'Zugang mit E-Mail erstellen',
    'lead'          => 'Wir senden Ihnen einen 6-stelligen Bestätigungscode an Ihre E-Mail-Adresse.',
    'email_label'   => 'E-Mail-Adresse',
    'consent_label' => 'Ich stimme zu, dass meine E-Mail für den Anmeldeprozess verwendet wird.',
    'send_btn'      => 'Code senden',
    'code_label'    => 'Bestätigungscode',
    'verify_btn'    => 'Code prüfen',
    'resend'        => 'Code erneut senden',
    'info_sent'     => 'Wir haben Ihnen einen Code gesendet. Bitte prüfen Sie auch den Spam-Ordner.',
    'error_email'   => 'Bitte geben Sie eine gültige E-Mail-Adresse an.',
    'error_consent' => 'Bitte stimmen Sie der Nutzung Ihrer E-Mail zu.',
    'error_rate'    => 'Zu viele Versuche. Bitte warten Sie kurz und versuchen Sie es erneut.',
    'error_code'    => 'Der Code ist ungültig oder abgelaufen.',
    'error_resend'  => 'Erneuter Versand nicht möglich. Starten Sie bitte erneut.',
    'ok_verified'   => 'E-Mail bestätigt. Sie werden weitergeleitet…',
    'back'          => 'Zurück',
    'cancel'        => 'Abbrechen',
  ],
  'en' => [
    'title'         => 'Create access with email',
    'lead'          => 'We will send a 6-digit verification code to your email address.',
    'email_label'   => 'Email address',
    'consent_label' => 'I agree that my email is used for the registration process.',
    'send_btn'      => 'Send code',
    'code_label'    => 'Verification code',
    'verify_btn'    => 'Verify code',
    'resend'        => 'Resend code',
    'info_sent'     => 'We sent you a code. Please also check the spam folder.',
    'error_email'   => 'Please provide a valid email address.',
    'error_consent' => 'Please accept the email usage consent.',
    'error_rate'    => 'Too many attempts. Please wait and try again.',
    'error_code'    => 'The code is invalid or expired.',
    'error_resend'  => 'Cannot resend. Please start over.',
    'ok_verified'   => 'Email verified. Redirecting…',
    'back'          => 'Back',
    'cancel'        => 'Cancel',
  ],
];
$text = $t[$lang] ?? $t['en'];

// ------------------------------------------------------------
// Hilfen: CSRF, Email-Validierung, Code
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
function csrf_check(): void {
  if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }
}
function is_valid_email(string $email): bool {
  return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}
function make_code(): string { return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT); }

// Rate-Limit (einfach): max 5 Aktionen in 10 Min.
// Für lokale Tests kann die Funktion auf `return true;` reduziert werden.
$_SESSION['rate'] = $_SESSION['rate'] ?? [];
function rate_ok(): bool { return true; } // nur für Tests
// function rate_ok(): bool {
//   $now = time();
//   $_SESSION['rate'] = array_values(array_filter($_SESSION['rate'], fn($t) => $t >= $now - 600));
//   if (count($_SESSION['rate']) >= 5) { return false; }
//   $_SESSION['rate'][] = $now; return true;
// }

// ------------------------------------------------------------
// Zustandsautomat: send (neu/erneut), resend (komfort), verify
$step   = $_POST['step'] ?? '';
$errors = [];
$info   = '';

if ($step === 'send') {
  csrf_check();
  if (!rate_ok()) { $errors[] = $text['error_rate']; }
  $email   = trim((string)($_POST['email'] ?? ''));
  $consent = isset($_POST['consent']);

  if (!isset($_SESSION['email_verify'])) {
    // Erster Versand: E-Mail + Consent prüfen
    if (!is_valid_email($email)) { $errors[] = $text['error_email']; }
    if (!$consent)               { $errors[] = $text['error_consent']; }
  } else {
    // Bereits im Verify-Schritt: vorhandene E-Mail nutzen
    $email = (string)($_SESSION['email_verify']['email'] ?? '');
    if (!is_valid_email($email)) { $errors[] = $text['error_resend']; }
  }

  if (!$errors) {
    $code = make_code();
    $_SESSION['email_verify'] = [
      'email' => $email,
      'hash'  => password_hash($code, PASSWORD_DEFAULT),
      'exp'   => time() + 600, // 10 Minuten
      'tries' => 0,
    ];
    if (send_verification_email($email, $code, $lang)) {
      $info = $text['info_sent'];
    } else {
      $errors[] = 'E-Mail-Versand fehlgeschlagen. Bitte später erneut versuchen.';
    }
  }
}

if ($step === 'resend') {
  csrf_check();
  if (!rate_ok()) { $errors[] = $text['error_rate']; }
  $st = $_SESSION['email_verify'] ?? null;
  $email = $st['email'] ?? '';
  if (!$st || !is_valid_email($email)) {
    $errors[] = $text['error_resend'];
  } else {
    $code = make_code();
    $_SESSION['email_verify']['hash']  = password_hash($code, PASSWORD_DEFAULT);
    $_SESSION['email_verify']['exp']   = time() + 600;
    $_SESSION['email_verify']['tries'] = 0;
    if (send_verification_email($email, $code, $lang)) {
      $info = $text['info_sent'];
    } else {
      $errors[] = 'E-Mail-Versand fehlgeschlagen. Bitte später erneut versuchen.';
    }
  }
}

if ($step === 'verify') {
  csrf_check();
  if (!rate_ok()) { $errors[] = $text['error_rate']; }
  $code = trim((string)($_POST['code'] ?? ''));
  $st   = $_SESSION['email_verify'] ?? null;
  if (!$st || time() > (int)($st['exp'] ?? 0)) {
    $errors[] = $text['error_code'];
  } else {
    $_SESSION['email_verify']['tries'] = (int)$st['tries'] + 1;
    if ($_SESSION['email_verify']['tries'] > 6) { $errors[] = $text['error_rate']; }
    if (!$errors && !password_verify($code, (string)$st['hash'])) {
      $errors[] = $text['error_code'];
    }
    if (!$errors) {
      // Erfolg: E-Mail ist verifiziert
      $_SESSION['email_verified'] = true;

      // Token über eure Helper ermitteln/erzeugen
      $token = '';
      if (function_exists('current_access_token')) {
        $token = (string)current_access_token();
      }
      if ($token === '' && function_exists('issue_access_token')) {
        $token = (string)issue_access_token();
      }
      if ($token === '') {
        // Fallback: kryptischer Token (sollte i.d.R. nicht nötig sein)
        $token = bin2hex(random_bytes(16));
        $_SESSION['access_token'] = $token;
      }

      // Session-Access setzen
      $_SESSION['access'] = [
        'mode'    => 'email',
        'email'   => (string)$st['email'],
        'token'   => $token,
        'created' => time(),
      ];

      // Sofort-Persistenz: E-Mail verifiziert, DOB noch NULL
      // -> schreibt/aktualisiert applications(email, email_verified=1, token, dob=NULL, data_json[optional])
      ensure_record_email_only((string)$st['email']);

      // Aufräumen und weiter
      unset($_SESSION['email_verify']);
      header('Location: /form_personal.php?mode=email');
      exit;
    }
  }
}

// ------------------------------------------------------------
// Rendering
$title     = $text['title'];
$html_lang = $lang;
$html_dir  = $dir;
require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';
?>
<style>
<?php if ($rtl): ?> body { text-align:right; direction:rtl; } <?php endif; ?>
.card { border-radius: 1rem; }
</style>

<div class="container py-5">
  <div class="card shadow border-0">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-3"><?= h($text['title']) ?></h1>
      <p class="mb-4"><?= h($text['lead']) ?></p>

      <?php if ($errors): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) { echo '<div>'.h($e).'</div>'; } ?></div>
      <?php endif; ?>
      <?php if ($info): ?>
        <div class="alert alert-success"><?= h($info) ?></div>
      <?php endif; ?>

      <?php $hasSent = isset($_SESSION['email_verify']); ?>

      <?php if (!$hasSent): // Schritt 1: E-Mail erfassen ?>
      <form method="post" class="vstack gap-3">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
        <input type="hidden" name="step" value="send">

        <div>
          <label for="email" class="form-label"><?= h($text['email_label']) ?></label>
          <input type="email" class="form-control" name="email" id="email" required>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="1" id="consent" name="consent" required>
          <label class="form-check-label" for="consent"><?= h($text['consent_label']) ?></label>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-primary"><?= h($text['send_btn']) ?></button>
          <a href="/index.php" class="btn btn-outline-secondary"><?= h($text['back']) ?></a>
        </div>
      </form>

      <?php else: // Schritt 2: Code prüfen ?>
      <form method="post" class="vstack gap-3">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
        <input type="hidden" name="step" value="verify">
        <div>
          <label for="code" class="form-label"><?= h($text['code_label']) ?></label>
          <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control" name="code" id="code" required>
          <div class="form-text"><?= h($text['info_sent']) ?></div>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-primary"><?= h($text['verify_btn']) ?></button>

          <button type="submit"
                  formaction="/access_create.php"
                  formmethod="post"
                  name="step"
                  value="resend"
                  class="btn btn-outline-secondary"
                  formnovalidate>
            <?= h($text['resend']) ?>
          </button>

          <a href="/index.php" class="btn btn-outline-secondary"><?= h($text['cancel']) ?></a>
        </div>
      </form>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php';
