<?php
// public/access_create.php
// Mit E-Mail fortfahren: Login (E-Mail+Passwort) ODER neuen Zugang erstellen (E-Mail verifizieren -> Passwort senden)
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/email.php';          // send_verification_email()
require_once __DIR__ . '/../app/functions_form.php'; // optional helper, falls vorhanden

// ------------------------------------------------------------
// Abbrechen: Session komplett zurücksetzen, damit man neu starten kann
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'cancel') {
    if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

    // Alles entfernen, was den Flow beeinflusst
    unset(
        $_SESSION['email_verify'],
        $_SESSION['email_verified'],
        $_SESSION['email_account'],
        $_SESSION['access'],
        $_SESSION['access_login'],
        $_SESSION['access_token'],
        $_SESSION['application_status'],
        $_SESSION['application_readonly'],
        $_SESSION['form'],
        $_SESSION['flash'],
        $_SESSION['rate']
    );

    // Session komplett zerstören
    $_SESSION = [];

    // Cookie löschen
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'] ?? '/',
            $p['domain'] ?? '',
            (bool)($p['secure'] ?? false),
            (bool)($p['httponly'] ?? true)
        );
    }

    session_destroy();

    header('Location: /access_create.php'); // neu starten
    exit;
}

// ------------------------------------------------------------
// Sprache aus Cookie (wie index.php) + RTL bestimmen
$languages = [
  'de' => 'Deutsch','en' => 'English','fr' => 'Français','uk' => 'Українська',
  'ar' => 'العربية','ru' => 'Русский','tr' => 'Türkçe','fa' => 'فارسی',
];
$lang = strtolower((string)($_COOKIE['lang'] ?? 'de'));
if (!array_key_exists($lang, $languages)) { $lang = 'de'; }
$rtl  = in_array($lang, ['ar','fa'], true);
$dir  = $rtl ? 'rtl' : 'ltr';

// ------------------------------------------------------------
// Helpers
function is_valid_email(string $email): bool {
  return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}
function make_code(): string {
  return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}
function make_password(int $len = 12): string {
  $raw = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
  return substr($raw, 0, $len);
}
function rate_ok(): bool {
  // Für Produktion ggf. aktivieren (Session-basiert)
  return true;
}

// Simple placeholder replace für i18n
function tr(string $key, array $vars = []): string {
  $s = t($key);
  foreach ($vars as $k => $v) {
    $s = str_replace('{'.$k.'}', (string)$v, $s);
  }
  return $s;
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
  $subject = t('access_create.mail_subject');
  $body    = tr('access_create.mail_body', ['email' => $email, 'password' => $password]);

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
  if (!rate_ok()) { $errors[] = t('access_create.error_rate'); }

  $email = trim((string)($_POST['email'] ?? ''));
  $pass  = trim((string)($_POST['password'] ?? ''));

  if (!is_valid_email($email)) $errors[] = t('access_create.error_email');
  if ($pass === '') $errors[] = t('access_create.login_err');

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
      $errors[] = t('access_create.login_err');
    } else {
      // Session: eingeloggt mit E-Mail-Account
      $_SESSION['email_account'] = [
        'id'      => (int)$acc['id'],
        'email'   => (string)$acc['email'],
        'created' => time(),
      ];

      // Access-Mode setzen (Token pro Bewerbung erzeugen)
      $_SESSION['access'] = [
        'mode'    => 'email',
        'email'   => (string)$acc['email'],
        'token'   => '',
        'created' => time(),
      ];

      // Zielseite (muss existieren): Liste Bewerbungen + "Neue Bewerbung"
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
  if (!rate_ok()) { $errors[] = t('access_create.error_rate'); }

  $email   = trim((string)($_POST['email'] ?? ''));
  $consent = isset($_POST['consent']);

  if (!is_valid_email($email)) $errors[] = t('access_create.error_email');
  if (!$consent)               $errors[] = t('access_create.error_consent');

  if (!$errors) {
    $acc = account_by_email($email);
    if ($acc && (int)($acc['email_verified'] ?? 0) === 1) {
      $errors[] = t('access_create.email_in_use');
    } else {
      $code = make_code();
      $_SESSION['email_verify'] = [
        'email' => $email,
        'hash'  => password_hash($code, PASSWORD_DEFAULT),
        'exp'   => time() + 600,
        'tries' => 0,
      ];

      if (send_verification_email($email, $code, $lang)) {
        $info = t('access_create.info_sent');
        $mode = 'register';
      } else {
        $errors[] = t('access_create.error_mail_send');
      }
    }
  } else {
    $mode = 'register';
  }
}

if ($step === 'resend') {
  if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }
  if (!rate_ok()) { $errors[] = t('access_create.error_rate'); }

  $st = $_SESSION['email_verify'] ?? null;
  $email = (string)($st['email'] ?? '');

  if (!$st || !is_valid_email($email)) {
    $errors[] = t('access_create.error_resend');
  } else {
    $code = make_code();
    $_SESSION['email_verify']['hash']  = password_hash($code, PASSWORD_DEFAULT);
    $_SESSION['email_verify']['exp']   = time() + 600;
    $_SESSION['email_verify']['tries'] = 0;

    if (send_verification_email($email, $code, $lang)) {
      $info = t('access_create.info_sent');
    } else {
      $errors[] = t('access_create.error_mail_send');
    }
  }
  $mode = 'register';
}

if ($step === 'verify') {
  if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }
  if (!rate_ok()) { $errors[] = t('access_create.error_rate'); }

  $code = trim((string)($_POST['code'] ?? ''));
  $st   = $_SESSION['email_verify'] ?? null;

  if (!$st || time() > (int)($st['exp'] ?? 0)) {
    $errors[] = t('access_create.error_code');
  } else {
    $_SESSION['email_verify']['tries'] = (int)($st['tries'] ?? 0) + 1;
    if ($_SESSION['email_verify']['tries'] > 6) {
      $errors[] = t('access_create.error_rate');
    }
    if (!$errors && !password_verify($code, (string)$st['hash'])) {
      $errors[] = t('access_create.error_code');
    }

    if (!$errors) {
      $email = (string)$st['email'];

      $password = make_password(12);
      $hash = password_hash($password, PASSWORD_DEFAULT);

      try {
        $pdo = db();

        // Existiert bereits (unverified) -> updaten, sonst insert
        $acc = account_by_email($email);
        if ($acc) {
          $pdo->prepare("
            UPDATE email_accounts
               SET password_hash  = :ph,
                   email_verified = 1,
                   updated_at     = NOW()
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
          $errors[] = t('access_create.error_mail_send');
          $mode = 'register';
        } else {
          $info = t('access_create.ok_verified');
          unset($_SESSION['email_verify']);
          $mode = 'login';
        }
      } catch (Throwable $e) {
        error_log('access_create verify insert/update: '.$e->getMessage());
        $errors[] = t('access_create.error_db');
        $mode = 'register';
      }
    } else {
      $mode = 'register';
    }
  }
}

// ------------------------------------------------------------
// Rendering
$title     = t('access_create.title');
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
      <h1 class="h4 mb-2"><?= h(t('access_create.title')) ?></h1>
      <p class="mb-4"><?= h(t('access_create.lead')) ?></p>

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
            <?= h(t('access_create.tabs_login')) ?>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link<?= $mode === 'register' ? ' active' : '' ?>" type="button"
                  onclick="location.href='?mode=register'">
            <?= h(t('access_create.tabs_register')) ?>
          </button>
        </li>
      </ul>

      <?php if ($mode === 'login'): ?>
        <h2 class="h5 mb-2"><?= h(t('access_create.login_title')) ?></h2>
        <p class="text-muted mb-3"><?= h(t('access_create.login_text')) ?></p>

        <form method="post" class="vstack gap-3">
          <?php csrf_field(); ?>
          <input type="hidden" name="mode" value="login">
          <input type="hidden" name="step" value="do_login">

          <div>
            <label for="email" class="form-label"><?= h(t('access_create.email_label')) ?></label>
            <input type="email" class="form-control" name="email" id="email"
                   value="<?= h((string)($_POST['email'] ?? '')) ?>" required>
          </div>

          <div>
            <label for="password" class="form-label"><?= h(t('access_create.pass_label')) ?></label>
            <input type="password" class="form-control" name="password" id="password" required>
          </div>

          <div class="d-flex gap-2 mt-2">
            <button class="btn btn-primary"><?= h(t('access_create.login_btn')) ?></button>
            <a href="/index.php" class="btn btn-outline-secondary"><?= h(t('access_create.back')) ?></a>

            <button type="submit"
                    name="action"
                    value="cancel"
                    class="btn btn-outline-danger"
                    formnovalidate>
              <?= h(t('access_create.cancel')) ?>
            </button>
          </div>
        </form>

      <?php else: ?>
        <?php $hasSent = isset($_SESSION['email_verify']); ?>

        <h2 class="h5 mb-2"><?= h(t('access_create.reg_title')) ?></h2>
        <p class="text-muted mb-3"><?= h(t('access_create.reg_text')) ?></p>

        <?php if (!$hasSent): ?>
          <form method="post" class="vstack gap-3">
            <?php csrf_field(); ?>
            <input type="hidden" name="mode" value="register">
            <input type="hidden" name="step" value="send">

            <div>
              <label for="email_r" class="form-label"><?= h(t('access_create.email_label')) ?></label>
              <input type="email" class="form-control" name="email" id="email_r"
                     value="<?= h((string)($_POST['email'] ?? '')) ?>"
                     required>
            </div>

            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="consent" name="consent" required>
              <label class="form-check-label" for="consent"><?= h(t('access_create.consent_label')) ?></label>
            </div>

            <div class="d-flex gap-2">
              <button class="btn btn-primary"><?= h(t('access_create.send_btn')) ?></button>
              <a href="/index.php" class="btn btn-outline-secondary"><?= h(t('access_create.back')) ?></a>

              <button type="submit"
                      name="action"
                      value="cancel"
                      class="btn btn-outline-danger"
                      formnovalidate>
                <?= h(t('access_create.cancel')) ?>
              </button>
            </div>
          </form>

        <?php else: ?>
          <form method="post" class="vstack gap-3">
            <?php csrf_field(); ?>
            <input type="hidden" name="mode" value="register">
            <input type="hidden" name="step" value="verify">

            <div>
              <label for="code" class="form-label"><?= h(t('access_create.code_label')) ?></label>
              <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                     class="form-control" name="code" id="code" required>
              <div class="form-text"><?= h(t('access_create.info_sent')) ?></div>
            </div>

            <div class="d-flex gap-2">
              <button class="btn btn-primary"><?= h(t('access_create.verify_btn')) ?></button>

              <button type="submit"
                      name="step"
                      value="resend"
                      class="btn btn-outline-secondary"
                      formnovalidate>
                <?= h(t('access_create.resend')) ?>
              </button>

              <button type="submit"
                      name="action"
                      value="cancel"
                      class="btn btn-outline-danger"
                      formnovalidate>
                <?= h(t('access_create.cancel')) ?>
              </button>
            </div>
          </form>
        <?php endif; ?>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
