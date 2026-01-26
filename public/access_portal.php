<?php
// public/access_portal.php
// Portal für E-Mail-Accounts: Bewerbungen anzeigen + neue Bewerbung starten
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/db.php';

if (empty($_SESSION['email_account']['email'])) {
    // Nicht eingeloggt -> zurück
    header('Location: /access_create.php?mode=login');
    exit;
}

$email = (string)$_SESSION['email_account']['email'];

// ------------------------------------------------------------
// Sprache wie index.php (Cookie) + RTL
$languages = [
  'de' => 'Deutsch','en' => 'English','fr' => 'Français','uk' => 'Українська',
  'ar' => 'العربية','ru' => 'Русский','tr' => 'Türkçe','fa' => 'فارسی',
];
$lang = strtolower($_COOKIE['lang'] ?? 'de');
if (!array_key_exists($lang, $languages)) { $lang = 'de'; }
$rtl  = in_array($lang, ['ar','fa'], true);
$dir  = $rtl ? 'rtl' : 'ltr';

$t = [
  'de' => [
    'title' => 'Meine Bewerbungen',
    'lead'  => 'Hier sehen Sie Ihre Bewerbungen. Sie können eine bestehende Bewerbung fortsetzen oder eine neue starten.',
    'btn_new' => 'Neue Bewerbung starten',
    'btn_open' => 'Öffnen',
    'btn_logout' => 'Abmelden',
    'th_ref' => 'ID',
    'th_status' => 'Status',
    'th_created' => 'Erstellt',
    'th_updated' => 'Aktualisiert',
    'th_token' => 'Token',
    'th_action' => 'Aktion',
    'status_draft' => 'Entwurf',
    'status_submitted' => 'Abgeschickt',
    'status_withdrawn' => 'Zurückgezogen',
    'limit_reached' => 'Sie haben die maximale Anzahl an Bewerbungen für diese E-Mail erreicht.',
    'no_apps' => 'Noch keine Bewerbungen vorhanden.',
    'err_generic' => 'Es ist ein Fehler aufgetreten.',
  ],
  'en' => [
    'title' => 'My applications',
    'lead'  => 'Here you can see your applications. Continue an existing one or start a new one.',
    'btn_new' => 'Start new application',
    'btn_open' => 'Open',
    'btn_logout' => 'Log out',
    'th_ref' => 'ID',
    'th_status' => 'Status',
    'th_created' => 'Created',
    'th_updated' => 'Updated',
    'th_token' => 'Token',
    'th_action' => 'Action',
    'status_draft' => 'Draft',
    'status_submitted' => 'Submitted',
    'status_withdrawn' => 'Withdrawn',
    'limit_reached' => 'You have reached the maximum number of applications for this email.',
    'no_apps' => 'No applications yet.',
    'err_generic' => 'An error occurred.',
  ],
];
$text = $t[$lang] ?? $t['en'];

// ------------------------------------------------------------
// Helpers
function get_setting(string $key, string $default = ''): string {
    try {
        $pdo = db();
        $st = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = :k LIMIT 1");
        $st->execute([':k' => $key]);
        $v = $st->fetchColumn();
        return ($v !== false) ? (string)$v : $default;
    } catch (Throwable $e) {
        error_log('access_portal get_setting: '.$e->getMessage());
        return $default;
    }
}

function issue_token32(): string {
    return bin2hex(random_bytes(16)); // 32 hex
}

function status_label(string $status, array $text): string {
    return match ($status) {
        'submitted' => $text['status_submitted'] ?? $status,
        'withdrawn' => $text['status_withdrawn'] ?? $status,
        default     => $text['status_draft'] ?? $status,
    };
}

function load_application_into_session(string $token, string $email): bool {
    try {
        $pdo = db();
        $st = $pdo->prepare("
            SELECT token, email, status, data_json
            FROM applications
            WHERE token = :t AND email = :e
            LIMIT 1
        ");
        $st->execute([':t' => $token, ':e' => $email]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        $decoded = [];
        $json = (string)($row['data_json'] ?? '');
        if ($json !== '') {
            $tmp = json_decode($json, true);
            if (is_array($tmp)) $decoded = $tmp;
        }

        // In deiner DB liegt oft {form:{...}} – beide Varianten abfangen
        if (isset($decoded['form']) && is_array($decoded['form'])) {
            $_SESSION['form'] = $decoded['form'];
        } else {
            $_SESSION['form'] = $decoded;
        }

        $status = (string)($row['status'] ?? 'draft');

        $_SESSION['access_token'] = (string)$row['token'];
        $_SESSION['access'] = [
            'mode'    => 'email',
            'email'   => $email,
            'token'   => (string)$row['token'],
            'created' => time(),
        ];
        $_SESSION['application_status']   = $status;
        $_SESSION['application_readonly'] = ($status === 'submitted');

        return true;
    } catch (Throwable $e) {
        error_log('access_portal load_application: '.$e->getMessage());
        return false;
    }
}

// ------------------------------------------------------------
// POST Actions
$errors = [];
$info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

    $action = (string)($_POST['action'] ?? '');

    // Abmelden
    if ($action === 'logout') {
        unset(
            $_SESSION['email_verify'],
            $_SESSION['email_verified'],
            $_SESSION['email_account'],
            $_SESSION['access'],
            $_SESSION['access_login'],
            $_SESSION['access_token'],
            $_SESSION['application_status'],
            $_SESSION['application_readonly'],
            $_SESSION['form']
        );
        header('Location: /index.php');
        exit;
    }

    // Bestehende Bewerbung öffnen
    if ($action === 'open') {
        $token = trim((string)($_POST['token'] ?? ''));
        if ($token === '') {
            $errors[] = $text['err_generic'];
        } else {
            if (!load_application_into_session($token, $email)) {
                $errors[] = $text['err_generic'];
            } else {
                if (!empty($_SESSION['application_readonly'])) {
                    header('Location: /form_review.php');
                } else {
                    header('Location: /form_personal.php?mode=email');
                }
                exit;
            }
        }
    }

    // Neue Bewerbung starten (neuen Token erzeugen + applications row)
    if ($action === 'new') {
        try {
            $pdo = db();

            $max = (int)get_setting('max_tokens_per_email', '5');
            if ($max < 1) $max = 1;

            $stCount = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE email = :e");
            $stCount->execute([':e' => $email]);
            $count = (int)$stCount->fetchColumn();

            if ($count >= $max) {
                $errors[] = $text['limit_reached'];
            } else {
                // Token
                $token = issue_token32();

                // Neue application als draft, email_verified=1 (weil Account schon verifiziert)
                $stIns = $pdo->prepare("
                    INSERT INTO applications (token, email, dob, email_verified, data_json, status)
                    VALUES (:t, :e, NULL, 1, NULL, 'draft')
                ");
                $stIns->execute([':t' => $token, ':e' => $email]);

                // Session für diese neue Bewerbung vorbereiten
                $_SESSION['access_token'] = $token;
                $_SESSION['access'] = [
                    'mode'    => 'email',
                    'email'   => $email,
                    'token'   => $token,
                    'created' => time(),
                ];
                $_SESSION['application_status'] = 'draft';
                $_SESSION['application_readonly'] = false;

                // Neue Form-Session leeren (wichtig!)
                unset($_SESSION['form']);

                header('Location: /form_personal.php?mode=email');
                exit;
            }
        } catch (Throwable $e) {
            error_log('access_portal new application: '.$e->getMessage());
            $errors[] = $text['err_generic'];
        }
    }
}

// ------------------------------------------------------------
// Daten laden: Liste der Bewerbungen
$apps = [];
$maxTokens = (int)get_setting('max_tokens_per_email', '5');
if ($maxTokens < 1) $maxTokens = 1;

try {
    $pdo = db();
    $st = $pdo->prepare("
        SELECT id, token, status, created_at, updated_at
        FROM applications
        WHERE email = :e
        ORDER BY created_at DESC
    ");
    $st->execute([':e' => $email]);
    $apps = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('access_portal list applications: '.$e->getMessage());
    $errors[] = $text['err_generic'];
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
code.smalltoken { font-size: .85em; }
</style>

<div class="container py-5">
  <div class="card shadow border-0">
    <div class="card-body p-4 p-md-5">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
          <h1 class="h4 mb-1"><?= h($text['title']) ?></h1>
          <div class="text-muted"><?= h($text['lead']) ?></div>
          <div class="small text-muted mt-1">
            <?= h($email) ?> · max. <?= (int)$maxTokens ?> Bewerbungen
          </div>
        </div>
        <div class="d-flex gap-2">
          <form method="post" class="m-0">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="new">
            <button class="btn btn-primary">
              <?= h($text['btn_new']) ?>
            </button>
          </form>

          <form method="post" class="m-0">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="logout">
            <button class="btn btn-outline-secondary">
              <?= h($text['btn_logout']) ?>
            </button>
          </form>
        </div>
      </div>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <?php foreach ($errors as $e): ?>
            <div><?= h((string)$e) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (empty($apps)): ?>
        <div class="alert alert-info mb-0"><?= h($text['no_apps']) ?></div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th><?= h($text['th_ref']) ?></th>
                <th><?= h($text['th_status']) ?></th>
                <th><?= h($text['th_created']) ?></th>
                <th><?= h($text['th_updated']) ?></th>
                <th><?= h($text['th_token']) ?></th>
                <th><?= h($text['th_action']) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($apps as $a): ?>
                <tr>
                  <td><?= (int)$a['id'] ?></td>
                  <td><?= h(status_label((string)$a['status'], $text)) ?></td>
                  <td><?= h((string)$a['created_at']) ?></td>
                  <td><?= h((string)$a['updated_at']) ?></td>
                  <td><code class="smalltoken"><?= h((string)$a['token']) ?></code></td>
                  <td>
                    <form method="post" class="m-0">
                      <?php csrf_field(); ?>
                      <input type="hidden" name="action" value="open">
                      <input type="hidden" name="token" value="<?= h((string)$a['token']) ?>">
                      <button class="btn btn-sm btn-outline-primary">
                        <?= h($text['btn_open']) ?>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
