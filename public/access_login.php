<?php
// public/access_login.php
// Zugriff auf Bewerbung/en: Login mit Token + Geburtsdatum
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/db.php'; // db():PDO

// ------------------------------------------------------------
// Sprache wie auf index.php bestimmen (Cookie) + RTL
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

// Mini helper: placeholder replace für i18n
function tr(string $key, array $vars = []): string {
    $s = t($key);
    foreach ($vars as $k => $v) {
        $s = str_replace('{'.$k.'}', (string)$v, $s);
    }
    return $s;
}

// ------------------------------------------------------------
// Hilfsfunktionen (Geburtsdatum-Check wie in form_personal)
function validate_dob_dmy(string $dmy): bool {
    if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $dmy)) return false;
    [$d,$m,$y] = explode('.', $dmy);
    return checkdate((int)$m, (int)$d, (int)$y);
}

// ------------------------------------------------------------
// POST-Handling
$login_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        http_response_code(400);
        exit(t('access_login.csrf_invalid'));
    }

    $action = (string)($_POST['action'] ?? '');

    // --------- Aktion: Bewerbung mit Token + DOB laden ---------
    if ($action === 'login_token') {
        $token_raw = trim((string)($_POST['token'] ?? ''));
        $dob_raw   = trim((string)($_POST['dob'] ?? ''));

        if ($token_raw === '') {
            $login_errors['token'] = t('access_login.login_error_token');
        }
        if (!validate_dob_dmy($dob_raw)) {
            $login_errors['dob'] = t('access_login.login_error_dob');
        }

        if (!$login_errors) {
            if (!function_exists('norm_date_dmy_to_ymd')) {
                $login_errors['general'] = t('access_login.internal_error');
            } else {
                $dob_sql = norm_date_dmy_to_ymd($dob_raw);
                if ($dob_sql === '') {
                    $login_errors['dob'] = t('access_login.login_error_dob');
                } else {
                    try {
                        $pdo = db();
                        $stmt = $pdo->prepare("
                            SELECT data_json, status, email
                            FROM applications
                            WHERE token = :t AND dob = :d
                            LIMIT 1
                        ");
                        $stmt->execute([':t' => $token_raw, ':d' => $dob_sql]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$row) {
                            $login_errors['general'] = t('access_login.login_error');
                        } else {
                            $json    = (string)($row['data_json'] ?? '');
                            $decoded = $json !== '' ? json_decode($json, true) : null;
                            if (!is_array($decoded)) $decoded = [];

                            if (isset($decoded['form']) && is_array($decoded['form'])) {
                                $_SESSION['form'] = $decoded['form'];
                            } else {
                                $_SESSION['form'] = $decoded;
                            }

                            $emailFromData = '';
                            if (!empty($_SESSION['form']['personal']['email'])) {
                                $emailFromData = (string)$_SESSION['form']['personal']['email'];
                            } elseif (!empty($row['email'])) {
                                $emailFromData = (string)$row['email'];
                            }

                            $_SESSION['access'] = [
                                'mode'    => ($emailFromData !== '' ? 'email' : 'noemail'),
                                'token'   => $token_raw,
                                'email'   => $emailFromData,
                                'created' => time(),
                            ];
                            $_SESSION['access_token']         = $token_raw;
                            $_SESSION['application_status']   = (string)($row['status'] ?? 'draft');
                            $_SESSION['application_readonly'] = ((string)($row['status'] ?? 'draft') === 'submitted');

                            if (function_exists('flash_set')) {
                                flash_set('success', t('access_login.login_ok'));
                            }

                            if (!empty($_SESSION['application_readonly'])) {
                                header('Location: /form_review.php');
                            } else {
                                header('Location: /form_personal.php');
                            }
                            exit;
                        }
                    } catch (Throwable $e) {
                        error_log('access_login login_token error: '.$e->getMessage());
                        $login_errors['general'] = t('access_login.load_error');
                    }
                }
            }
        }
    }
}

// ------------------------------------------------------------
// Rendering
$title     = t('access_login.title');
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
      <h1 class="h4 mb-3"><?= h(t('access_login.title')) ?></h1>
      <p class="mb-4"><?= h(t('access_login.lead')) ?></p>

      <h2 class="h5 mb-2"><?= h(t('access_login.login_box_title')) ?></h2>
      <p class="text-muted mb-3"><?= h(t('access_login.login_box_text')) ?></p>

      <?php if (!empty($login_errors['general'])): ?>
        <div class="alert alert-danger py-2 mb-3"><?= h($login_errors['general']) ?></div>
      <?php endif; ?>

      <form method="post" class="vstack gap-3">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="login_token">

        <div>
          <label for="token" class="form-label"><?= h(t('access_login.token_label')) ?></label>
          <input
            type="text"
            name="token"
            id="token"
            class="form-control<?= isset($login_errors['token']) ? ' is-invalid' : '' ?>"
            value="<?= h($_POST['token'] ?? '') ?>"
            autocomplete="off"
            required
          >
          <?php if (isset($login_errors['token'])): ?>
            <div class="invalid-feedback d-block"><?= h($login_errors['token']) ?></div>
          <?php endif; ?>
        </div>

        <div>
          <label for="dob" class="form-label"><?= h(t('access_login.dob_label')) ?></label>
          <input
            type="text"
            name="dob"
            id="dob"
            class="form-control<?= isset($login_errors['dob']) ? ' is-invalid' : '' ?>"
            placeholder="TT.MM.JJJJ"
            value="<?= h($_POST['dob'] ?? '') ?>"
            autocomplete="off"
            required
          >
          <?php if (isset($login_errors['dob'])): ?>
            <div class="invalid-feedback d-block"><?= h($login_errors['dob']) ?></div>
          <?php endif; ?>
        </div>

        <div class="d-flex gap-2 mt-2">
          <button type="submit" class="btn btn-primary">
            <?= h(t('access_login.login_btn')) ?>
          </button>
          <a href="/index.php" class="btn btn-outline-secondary">
            <?= h(t('access_login.back')) ?>
          </a>
        </div>
      </form>

    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
