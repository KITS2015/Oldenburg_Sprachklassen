<?php
// public/access_login.php
// Zugriff auf Bewerbung/en: Login mit Token + Geburtsdatum,
// optional: Token-Verlust ? Token per verifizierter E-Mail erneut zusenden.
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/functions_form.php'; // resend_token_if_verified()
require_once __DIR__ . '/../app/db.php';             // db():PDO

// ------------------------------------------------------------
// Sprache wie auf index.php bestimmen
$languages = [
    'de' => 'Deutsch',
    'en' => 'English',
    'fr' => 'Français',
    'uk' => '??????????',
    'ar' => '???????',
    'ru' => '???????',
    'tr' => 'Türkçe',
    'fa' => '?????',
];

$lang = strtolower($_COOKIE['lang'] ?? 'de');
if (!array_key_exists($lang, $languages)) {
    $lang = 'de';
}
$rtl = in_array($lang, ['ar','fa'], true);
$dir = $rtl ? 'rtl' : 'ltr';

// ------------------------------------------------------------
// Texte (DE/EN vollständig, andere Sprachen fallen auf EN zurück)
$t = [
    'de' => [
        'title'             => 'Zugriff auf Bewerbung/en',
        'lead'              => 'Hier können Sie eine bereits begonnene oder abgeschickte Bewerbung wieder aufrufen.',
        'login_box_title'   => 'Anmeldung mit Access-Token',
        'login_box_text'    => 'Geben Sie bitte Ihren persönlichen Zugangscode (Access-Token) und Ihr Geburtsdatum ein.',
        'token_label'       => 'Access-Token',
        'dob_label'         => 'Geburtsdatum (TT.MM.JJJJ)',
        'login_btn'         => 'Zugriff',
        'login_error'       => 'Kombination aus Access-Token und Geburtsdatum wurde nicht gefunden.',
        'login_error_token' => 'Bitte geben Sie einen gültigen Access-Token ein.',
        'login_error_dob'   => 'Bitte geben Sie ein gültiges Geburtsdatum im Format TT.MM.JJJJ ein.',
        'login_ok'          => 'Bewerbung wurde geladen.',
        'forgot_box_title'  => 'Access-Token vergessen?',
        'forgot_box_text'   => 'Wenn Sie Ihre E-Mail-Adresse verifiziert haben, können wir Ihnen den Access-Token erneut zusenden.',
        'email_label'       => 'Verifizierte E-Mail-Adresse',
        'resend_dob_label'  => 'Geburtsdatum (TT.MM.JJJJ)',
        'resend_btn'        => 'Access-Token zusenden',
        'resend_info'       => 'Wenn die E-Mail-Adresse verifiziert ist und zu einer Bewerbung passt, wird der Access-Token an diese Adresse gesendet.',
        'resend_ok'         => 'Falls eine passende verifizierte Bewerbung gefunden wurde, wurde der Token per E-Mail versendet.',
        'resend_error'      => 'Es wurde keine Bewerbung mit dieser Kombination aus E-Mail und Geburtsdatum gefunden oder die E-Mail ist nicht verifiziert.',
        'resend_error_email'=> 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
        'resend_error_dob'  => 'Bitte geben Sie ein gültiges Geburtsdatum im Format TT.MM.JJJJ ein.',
        'back'              => 'Zurück zur Startseite',
    ],
    'en' => [
        'title'             => 'Access your application(s)',
        'lead'              => 'Here you can access an application you already started or submitted.',
        'login_box_title'   => 'Login with access token',
        'login_box_text'    => 'Please enter your personal access token and your date of birth.',
        'token_label'       => 'Access token',
        'dob_label'         => 'Date of birth (DD.MM.YYYY)',
        'login_btn'         => 'Load application',
        'login_error'       => 'The combination of access token and date of birth was not found.',
        'login_error_token' => 'Please provide a valid access token.',
        'login_error_dob'   => 'Please provide a valid date of birth (DD.MM.YYYY).',
        'login_ok'          => 'Application loaded.',
        'forgot_box_title'  => 'Forgot your access token?',
        'forgot_box_text'   => 'If your email address has been verified, we can resend your access token to this address.',
        'email_label'       => 'Verified email address',
        'resend_dob_label'  => 'Date of birth (DD.MM.YYYY)',
        'resend_btn'        => 'Send access token',
        'resend_info'       => 'If a verified application is found, the access token will be sent to this address.',
        'resend_ok'         => 'If a matching verified application was found, the token has been sent by email.',
        'resend_error'      => 'No verified application was found for this email and date of birth.',
        'resend_error_email'=> 'Please provide a valid email address.',
        'resend_error_dob'  => 'Please provide a valid date of birth (DD.MM.YYYY).',
        'back'              => 'Back to start page',
    ],
];
$text = $t[$lang] ?? $t['en'];

// ------------------------------------------------------------
// Hilfsfunktionen (Geburtsdatum-Check wie in form_personal)
function validate_dob_dmy(string $dmy): bool {
    if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $dmy)) {
        return false;
    }
    [$d,$m,$y] = explode('.', $dmy);
    return checkdate((int)$m, (int)$d, (int)$y);
}

// ------------------------------------------------------------
// POST-Handling
$login_errors  = [];
$resend_errors = [];
$login_info    = '';
$resend_info   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        http_response_code(400);
        exit('Ungültige Anfrage.');
    }

    $action = (string)($_POST['action'] ?? '');

    // --------- Aktion: Bewerbung mit Token + DOB laden ---------
    if ($action === 'login_token') {
        $token_raw = trim((string)($_POST['token'] ?? ''));
        $dob_raw   = trim((string)($_POST['dob'] ?? ''));

        if ($token_raw === '') {
            $login_errors['token'] = $text['login_error_token'] ?? 'Bitte Access-Token eingeben.';
        }
        if (!validate_dob_dmy($dob_raw)) {
            $login_errors['dob'] = $text['login_error_dob'] ?? 'Ungültiges Geburtsdatum.';
        }

        if (!$login_errors) {
            if (!function_exists('norm_date_dmy_to_ymd')) {
                $login_errors['general'] = 'Interner Fehler (Datumshilfsfunktion fehlt).';
            } else {
                $dob_sql = norm_date_dmy_to_ymd($dob_raw);
                if ($dob_sql === '') {
                    $login_errors['dob'] = $text['login_error_dob'] ?? 'Ungültiges Geburtsdatum.';
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
                            $login_errors['general'] = $text['login_error'] ?? 'Keine passende Bewerbung gefunden.';
                        } else {
                            $json    = (string)($row['data_json'] ?? '');
                            $decoded = json_decode($json, true);
                            if (!is_array($decoded)) {
                                $decoded = [];
                            }
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
                                flash_set('success', $text['login_ok'] ?? 'Bewerbung geladen.');
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
                        $login_errors['general'] = 'Beim Laden der Bewerbung ist ein Fehler aufgetreten.';
                    }
                }
            }
        }
    }

    // --------- Aktion: Token per E-Mail erneut zusenden ---------
    if ($action === 'resend_token') {
        $email_raw = trim((string)($_POST['email'] ?? ''));
        $dob_raw   = trim((string)($_POST['dob_resend'] ?? ''));

        if (!filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
            $resend_errors['email'] = $text['resend_error_email'] ?? 'Ungültige E-Mail-Adresse.';
        }
        if (!validate_dob_dmy($dob_raw)) {
            $resend_errors['dob'] = $text['resend_error_dob'] ?? 'Ungültiges Geburtsdatum.';
        }

        if (!$resend_errors) {
            $ok = resend_token_if_verified($email_raw, $dob_raw);
            if ($ok) {
                $resend_info = $text['resend_ok'] ?? 'Falls eine passende Bewerbung gefunden wurde, wurde der Token zugesendet.';
            } else {
                $resend_errors['general'] = $text['resend_error'] ?? 'Keine passende verifizierte Bewerbung gefunden.';
            }
        }
    }
}

// ------------------------------------------------------------
// Rendering
$title     = $text['title'] ?? 'Zugriff auf Bewerbung/en';
$html_lang = $lang;
$html_dir  = $dir;

require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';
?>
<style>
<?php if ($rtl): ?>
body { text-align: right; direction: rtl; }
<?php endif; ?>
.card { border-radius: 1rem; }
</style>

<div class="container py-5">
  <div class="card shadow border-0">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-3"><?= h($text['title'] ?? 'Zugriff auf Bewerbung/en') ?></h1>
      <p class="mb-4"><?= h($text['lead'] ?? '') ?></p>

      <div class="row g-4">
        <!-- Linke Spalte: Login mit Token -->
        <div class="col-md-6">
          <h2 class="h5 mb-2"><?= h($text['login_box_title'] ?? '') ?></h2>
          <p class="text-muted mb-3"><?= h($text['login_box_text'] ?? '') ?></p>

          <?php if (!empty($login_errors['general'])): ?>
            <div class="alert alert-danger py-2 mb-3"><?= h($login_errors['general']) ?></div>
          <?php endif; ?>

          <form method="post" class="vstack gap-3">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="login_token">

            <div>
              <label for="token" class="form-label"><?= h($text['token_label'] ?? 'Access-Token') ?></label>
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
              <label for="dob" class="form-label"><?= h($text['dob_label'] ?? 'Geburtsdatum (TT.MM.JJJJ)') ?></label>
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
                <?= h($text['login_btn'] ?? 'Bewerbung laden') ?>
              </button>
              <a href="/index.php" class="btn btn-outline-secondary">
                <?= h($text['back'] ?? 'Zurück') ?>
              </a>
            </div>
          </form>
        </div>

        <!-- Rechte Spalte: Token per E-Mail zusenden -->
        <div class="col-md-6">
          <h2 class="h5 mb-2"><?= h($text['forgot_box_title'] ?? '') ?></h2>
          <p class="text-muted mb-3"><?= h($text['forgot_box_text'] ?? '') ?></p>

          <?php if ($resend_info): ?>
            <div class="alert alert-success py-2 mb-3"><?= h($resend_info) ?></div>
          <?php endif; ?>
          <?php if (!empty($resend_errors['general'])): ?>
            <div class="alert alert-danger py-2 mb-3"><?= h($resend_errors['general']) ?></div>
          <?php endif; ?>

          <form method="post" class="vstack gap-3">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="resend_token">

            <div>
              <label for="email" class="form-label"><?= h($text['email_label'] ?? 'E-Mail-Adresse') ?></label>
              <input
                type="email"
                name="email"
                id="email"
                class="form-control<?= isset($resend_errors['email']) ? ' is-invalid' : '' ?>"
                value="<?= h($_POST['email'] ?? '') ?>"
                autocomplete="off"
                required
              >
              <?php if (isset($resend_errors['email'])): ?>
                <div class="invalid-feedback d-block"><?= h($resend_errors['email']) ?></div>
              <?php endif; ?>
            </div>

            <div>
              <label for="dob_resend" class="form-label"><?= h($text['resend_dob_label'] ?? 'Geburtsdatum (TT.MM.JJJJ)') ?></label>
              <input
                type="text"
                name="dob_resend"
                id="dob_resend"
                class="form-control<?= isset($resend_errors['dob']) ? ' is-invalid' : '' ?>"
                placeholder="TT.MM.JJJJ"
                value="<?= h($_POST['dob_resend'] ?? '') ?>"
                autocomplete="off"
                required
              >
              <?php if (isset($resend_errors['dob'])): ?>
                <div class="invalid-feedback d-block"><?= h($resend_errors['dob']) ?></div>
              <?php endif; ?>
            </div>

            <div class="form-text mb-1"><?= h($text['resend_info'] ?? '') ?></div>

            <div class="d-flex gap-2 mt-2">
              <button class="btn btn-outline-primary" type="submit">
                <?= h($text['resend_btn'] ?? 'Access-Token zusenden') ?>
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
