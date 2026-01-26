<?php
// public/access_create.php
// Mit E-Mail fortfahren: Login (E-Mail+Passwort) ODER neuen Zugang erstellen (E-Mail verifizieren -> Passwort senden)
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/email.php';          // send_verification_email()
require_once __DIR__ . '/../app/functions_form.php'; // optional helper, falls vorhanden

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
    'title'            => 'Mit E-Mail fortfahren',
    'lead'             => 'Sie können sich mit Ihrem Zugang anmelden oder einen neuen Zugang erstellen.',
    'tabs_login'       => 'Anmelden',
    'tabs_register'    => 'Neuen Zugang erstellen',

    'login_title'      => 'Anmelden (bestehender Zugang)',
    'login_text'       => 'Bitte geben Sie Ihre E-Mail-Adresse und Ihr Passwort ein.',
    'email_label'      => 'E-Mail-Adresse',
    'pass_label'       => 'Passwort',
    'login_btn'        => 'Anmelden',
    'login_err'        => 'E-Mail/Passwort ist falsch oder der Zugang ist nicht verifiziert.',

    'reg_title'        => 'Neuen Zugang erstellen',
    'reg_text'         => 'Wir senden Ihnen einen 6-stelligen Bestätigungscode. Nach erfolgreicher Bestätigung erhalten Sie Ihr Passwort per E-Mail.',
    'consent_label'    => 'Ich stimme zu, dass meine E-Mail für den Anmeldeprozess verwendet wird.',
    'send_btn'         => 'Code senden',
    'code_label'       => 'Bestätigungscode',
    'verify_btn'       => 'Code prüfen',
    'resend'           => 'Code erneut senden',

    'info_sent'        => 'Wir haben Ihnen einen Code gesendet. Bitte prüfen Sie auch den Spam-Ordner.',
    'ok_verified'      => 'E-Mail bestätigt. Passwort wurde gesendet. Sie können sich jetzt anmelden.',
    'email_in_use'     => 'Diese E-Mail hat bereits einen Zugang. Bitte melden Sie sich an.',

    'error_email'      => 'Bitte geben Sie eine gültige E-Mail-Adresse an.',
    'error_consent'    => 'Bitte stimmen Sie der Nutzung Ihrer E-Mail zu.',
    'error_rate'       => 'Zu viele Versuche. Bitte warten Sie kurz und versuchen Sie es erneut.',
    'error_code'       => 'Der Code ist ungültig oder abgelaufen.',
    'error_resend'     => 'Erneuter Versand nicht möglich. Starten Sie bitte erneut.',
    'error_mail_send'  => 'E-Mail-Versand fehlgeschlagen. Bitte später erneut versuchen.',
    'back'             => 'Zurück',
    'cancel'           => 'Abbrechen',
  ],
  'en' => [
    'title'            => 'Continue with email',
    'lead'             => 'You can log in with your account or create a new one.',
    'tabs_login'       => 'Log in',
    'tabs_register'    => 'Create new account',

    'login_title'      => 'Log in (existing account)',
    'login_text'       => 'Please enter your email and password.',
    'email_label'      => 'Email address',
    'pass_label'       => 'Password',
    'login_btn'        => 'Log in',
    'login_err'        => 'Email/password is incorrect or the account is not verified.',

    'reg_title'        => 'Create new account',
    'reg_text'         => 'We will send a 6-digit code. After verification we will send your password by email.',
    'consent_label'    => 'I agree that my email is used for the registration process.',
    'send_btn'         => 'Send code',
    'code_label'       => 'Verification code',
    'verify_btn'       => 'Verify code',
    'resend'           => 'Resend code',

    'info_sent'        => 'We sent you a code. Please also check the spam folder.',
    'ok_verified'      => 'Email verified. Password has been sent. You can now log in.',
    'email_in_use'     => 'This email already has an account. Please log in.',

    'error_email'      => 'Please provide a valid email address.',
    'error_consent'    => 'Please accept the email usage consent.',
    'error_rate'       => 'Too many attempts. Please wait and try again.',
    'error_code'       => 'The code is invalid or expired.',
    'error_resend'     => 'Cannot resend. Please start over.',
    'error_mail_send'  => 'Email sending failed. Please try again later.',
    'back'             => 'Back',
    'cancel'           => 'Cancel',
  ],
];

$text = $t[$lang] ?? $t['en'];

// ------------------------------------------------------------
// Helpers
function is_valid_email(string $email): bool {
  return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}
function make_code(): string {
  return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}
function make_password(int $len = 12): string {
  // URL-safe, ohne Sonderzeichenprobleme
  $raw = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
  return substr($raw, 0, $len);
}
function rate_ok(): bool {
  // Für Produktion ggf. aktivieren (Session-basiert)
  return true;
}

function account_by_email(string $email): ?array {
  try {
    $pdo = db();
    $st = $pdo->prepare("SELECT * FROM email_accounts WHERE email = :e LIMIT 1");
    $st->execute([':e' => $email]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  } catch (Throwable $e) {
    error_log('access_create account_by_email: '.$e->getMessage());
    return null;
  }
}

function send_account_password_email_fallback(string $email, string $password, string $lang): bool {
  // Wenn ihr in app/email.php eine bessere Funktion habt, dort ergänzen und hier nutzen.
  if (function_exists('send_account_password_email')) {
    return (bool) send_account_password_email($email, $password, $lang);
  }

  // Fallback: simple mail()
  $subject = ($lang === 'de') ? 'Ihr Passwort für die Online-Anmeldung' : 'Your password for the online registration';
  $body = ($lang === 'de')
    ? "Ihr Zugang wurde erstellt.\n\nE-Mail: {$email}\nPasswort: {$password}\n\nBitte bewahren Sie das Passwort sicher auf."
    : "Your account has been created.\n\nEmail: {$email}\nPassword: {$password}\n\nPlease keep this password safe.";

  $headers = "Content-Type: text/plain; charset=UTF-8\r\n";
  return @mail($email, $subject, $body, $headers);
}

// ------------------------------------------------------------
// State
$mode   = (string)($_POST['mode'] ?? ($_GET['mode'] ?? 'login')); // login|register
$step   = (string)($_POST['step'] ?? '');                        // send|resend|verify|do_login
$errors = [];
$info   = '';

// ------------------------------------------------------------
// LOGIN: E-Mail + Passwort (keine Verifizierung mehr)
if ($step === 'do_login') {
  if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }
  if (!rate_ok()) { $errors[] = $text['error_rate']; }

  $email = trim((string)($_POST['email'] ?? ''));
  $pass  = trim((string)($_POST['password'] ?? ''));

  if (!is_valid_email($email)) $errors[] = $text['error_email'];
  if ($pass === '') $errors[] = $text['login_err'];

  if (!$errors) {
    $acc = account_by_email($email);
    $ok = false;
    if ($acc && (int)($acc['email_verified'] ?? 0) === 1) {
      $hash = (string)($acc['password_hash'] ?? '');
      if ($hash !== '' && password_verify($pass, $hash)) {
        $ok = true;
        try {
          $pdo = db();
          $pdo->prepare("UPDATE email_accounts SET last_login_at = NOW() WHERE id = :id")
              ->execute([':id' => (int)$acc['id']]);
        } catch (Throwable $e) {
          error_log('access_create update last_login_at: '.$e->getMessage());
        }
      }
    }

    if (!$ok) {
      $errors[] = $text['login_err'];
    } else {
      // Session: eingeloggt mit E-Mail-Account
      $_SESSION['email_account'] = [
        'id'      => (int)$acc['id'],
        'email'   => (string)$acc['email'],
        'created' => time(),
      ];

      // Access-Mode setzen (Token wird pro Bewerbung erzeugt – kommt später beim "Neue Bewerbung")
      $_SESSION['access'] = [
        'mode'    => 'email',
        'email'   => (string)$acc['email'],
        'token'   => '',       // pro Bewerbung
        'created' => time(),
      ];

      // Zielseite (kommt als nächstes: Liste Bewerbungen + "Neue Bewerbung")
      header('Location: /access_portal.php');
      exit;
    }
  }

  $mode = 'login';
}

// ------------------------------------------------------------
// REGISTER: E-Mail verifizieren -> Account anlegen -> Passwort senden
if ($step === 'send') {
  if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }
  if (!rate_ok()) { $errors[] = $text['error_rate']; }

  $email   = trim((string)($_POST['email'] ?? ''));
  $consent = isset($_POST['consent']);

  if (!is_valid_email($email)) $errors[] = $text['error_email'];
  if (!$consent)               $errors[] = $text['error_consent'];

  if (!$errors) {
    $acc = account_by_email($email);
    if ($acc && (int)($acc['email_verified'] ?? 0) === 1) {
      $errors[] = $text['email_in_use'];
    } else {
      $code = make_code();
      $_SESSION['email_verify'] = [
        'email' => $email,
        'hash'  => password_hash($code, PASSWORD_DEFAULT),
        'exp'   => time() + 600,
        'tries' => 0,
      ];

      if (send_verification_email($email, $code, $lang)) {
        $info = $text['info_sent'];
        $mode = 'register';
      } else {
        $errors[] = $text['error_mail_send'];
      }
    }
  } else {
    $mode = 'register';
  }
}

if ($step === 'resend') {
  if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }
  if (!rate_ok()) { $errors[] = $text['error_rate']; }

  $st = $_SESSION['email_verify'] ?? null;
  $email = (string)($st['email'] ?? '');

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
      $errors[] = $text['error_mail_send'];
    }
  }
  $mode = 'register';
}

if ($step === 'verify') {
  if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }
  if (!rate_ok()) { $errors[] = $text['error_rate']; }

  $code = trim((string)($_POST['code'] ?? ''));
  $st   = $_SESSION['email_verify'] ?? null;

  if (!$st || time() > (int)($st['exp'] ?? 0)) {
    $errors[] = $text['error_code'];
  } else {
    $_SESSION['email_verify']['tries'] = (int)($st['tries'] ?? 0) + 1;
    if ($_SESSION['email_verify']['tries'] > 6) {
      $errors[] = $text['error_rate'];
    }
    if (!$errors && !password_verify($code, (string)$st['hash'])) {
      $errors[] = $text['error_code'];
    }

    if (!$errors) {
      $email = (string)$st['email'];

      // Account anlegen / aktualisieren + Passwort setzen
      $password = make_password(12);
      $hash = password_hash($password, PASSWORD_DEFAULT);

      try {
        $pdo = db();

        // Existiert bereits (unverified) -> updaten, sonst insert
        $acc = account_by_email($email);
        if ($acc) {
          $pdo->prepare("
            UPDATE email_accounts
               SET password_hash = :ph,
                   email_verified = 1,
                   updated_at = NOW()
             WHERE id = :id
          ")->execute([
            ':ph' => $hash,
            ':id' => (int)$acc['id'],
          ]);
        } else {
          $pdo->prepare("
            INSERT INTO email_accounts (email, password_hash, email_verified, created_at, updated_at)
            VALUES (:e, :ph, 1, NOW(), NOW())
          ")->execute([
            ':e'  => $email,
            ':ph' => $hash,
          ]);
        }

        // Passwort mailen
        if (!send_account_password_email_fallback($email, $password, $lang)) {
          // Account ist angelegt, aber Mail ging nicht raus -> klarer Hinweis
          $errors[] = $text['error_mail_send'];
        } else {
          $info = $text['ok_verified'];
          unset($_SESSION['email_verify']);
          // Danach direkt auf Login-Tab
          $mode = 'login';
        }
      } catch (Throwable $e) {
        error_log('access_create verify insert/update: '.$e->getMessage());
        $errors[] = 'Serverfehler (DB).';
        $mode = 'register';
      }
    } else {
      $mode = 'register';
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
.nav-pills .nav-link { border-radius: .75rem; }
</style>

<div class="container py-5">
  <div class="card shadow border-0">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-2"><?= h($text['title']) ?></h1>
      <p class="mb-4"><?= h($text['lead']) ?></p>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <?php foreach ($errors as $e) { echo '<div>'.h((string)$e).'</div>'; } ?>
        </div>
      <?php endif; ?>
      <?php if ($info): ?>
        <div class="alert alert-success"><?= h($info) ?></div>
      <?php endif; ?>

      <ul class="nav nav-pills mb-4" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link<?= $mode === 'login' ? ' active' : '' ?>" type="button"
                  onclick="location.href='?mode=login'">
            <?= h($text['tabs_login']) ?>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link<?= $mode === 'register' ? ' active' : '' ?>" type="button"
                  onclick="location.href='?mode=register'">
            <?= h($text['tabs_register']) ?>
          </button>
        </li>
      </ul>

      <?php if ($mode === 'login'): ?>
        <h2 class="h5 mb-2"><?= h($text['login_title']) ?></h2>
        <p class="text-muted mb-3"><?= h($text['login_text']) ?></p>

        <form method="post" class="vstack gap-3">
          <?php csrf_field(); ?>
          <input type="hidden" name="mode" value="login">
          <input type="hidden" name="step" value="do_login">

          <div>
            <label for="email" class="form-label"><?= h($text['email_label']) ?></label>
            <input type="email" class="form-control" name="email" id="email"
                   value="<?= h((string)($_POST['email'] ?? '')) ?>" required>
          </div>

          <div>
            <label for="password" class="form-label"><?= h($text['pass_label']) ?></label>
            <input type="password" class="form-control" name="password" id="password" required>
          </div>

          <div class="d-flex gap-2 mt-2">
            <button class="btn btn-primary"><?= h($text['login_btn']) ?></button>
            <a href="/index.php" class="btn btn-outline-secondary"><?= h($text['back']) ?></a>
          </div>
        </form>

      <?php else: ?>
        <?php $hasSent = isset($_SESSION['email_verify']); ?>

        <h2 class="h5 mb-2"><?= h($text['reg_title']) ?></h2>
        <p class="text-muted mb-3"><?= h($text['reg_text']) ?></p>

        <?php if (!$hasSent): ?>
          <form method="post" class="vstack gap-3">
            <?php csrf_field(); ?>
            <input type="hidden" name="mode" value="register">
            <input type="hidden" name="step" value="send">

            <div>
              <label for="email_r" class="form-label"><?= h($text['email_label']) ?></label>
              <input type="email" class="form-control" name="email" id="email_r" required>
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

        <?php else: ?>
          <form method="post" class="vstack gap-3">
            <?php csrf_field(); ?>
            <input type="hidden" name="mode" value="register">
            <input type="hidden" name="step" value="verify">

            <div>
              <label for="code" class="form-label"><?= h($text['code_label']) ?></label>
              <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                     class="form-control" name="code" id="code" required>
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
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
