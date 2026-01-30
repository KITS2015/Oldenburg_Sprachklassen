<?php
// public/form_status.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/db.php';

// ========= POST: Reset / Neue Bewerbung =========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { http_response_code(400); exit(t('status.err.invalid_request')); }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'reset') {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: ' . url_with_lang('/index.php'));
        exit;
    }

    if ($action === 'new_application') {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: ' . url_with_lang('/index.php'));
        exit;
    }

    // unbekannte Aktion -> Startseite
    header('Location: ' . url_with_lang('/index.php'));
    exit;
}

// ========= Zugriffsschutz =========
$readonly  = !empty($_SESSION['application_readonly']);
$submitted = $_SESSION['application_submitted'] ?? null;

$token = current_access_token();
if (!$readonly || !$submitted || !$token) {
    header('Location: ' . url_with_lang('/index.php'));
    exit;
}

$appId = (int)($submitted['app_id'] ?? 0);
if ($appId <= 0) {
    header('Location: ' . url_with_lang('/index.php'));
    exit;
}

// ========= Status aus DB prüfen =========
try {
    $pdo = db();
    $st  = $pdo->prepare("
        SELECT status, created_at, updated_at
        FROM applications
        WHERE id = :id AND token = :t
        LIMIT 1
    ");
    $st->execute([':id' => $appId, ':t' => $token]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row || ($row['status'] ?? '') !== 'submitted') {
        header('Location: ' . url_with_lang('/index.php'));
        exit;
    }
} catch (Throwable $e) {
    error_log('form_status: ' . $e->getMessage());
    header('Location: ' . url_with_lang('/index.php'));
    exit;
}

// ========= Header-Infos (wie bei form_review) =========
$hdr = [
    'title'   => t('status.hdr_title'),
    'status'  => 'success',
    'message' => t('status.hdr_message'),
    'token'   => $token,
];

$title     = t('status.hdr_title');
$html_lang = html_lang();
$html_dir  = html_dir();

require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';

// kleines Helper für {id}
function tr_status(string $key, array $vars = []): string {
    $s = t($key);
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string)$v, $s);
    }
    return $s;
}
?>
<div class="container py-4">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">

      <h1 class="h4 mb-3"><?= h(t('status.h1')) ?></h1>

      <div class="alert alert-success">
        <div class="fw-semibold mb-1"><?= h(t('status.success.title')) ?></div>
        <div><?= h(t('status.success.body')) ?></div>
      </div>

      <!-- Kunden-Textbaustein -->
      <div class="alert alert-info">
        <div class="fw-semibold mb-1"><?= h(t('status.info.title')) ?></div>
        <div><?= t('status.info.body') /* enthält <em> */ ?></div>
      </div>

      <div class="d-flex flex-wrap gap-2 mt-4">
        <a class="btn btn-outline-primary" href="<?= h(url_with_lang('/application_pdf.php')) ?>" target="_blank" rel="noopener">
          <?= h(t('status.btn.pdf')) ?>
        </a>

        <form method="post" action="<?= h(url_with_lang('/form_status.php')) ?>" class="d-inline">
          <?php csrf_field(); ?>
          <input type="hidden" name="action" value="new_application">
          <button class="btn btn-primary"><?= h(t('status.btn.newapp')) ?></button>
        </form>

        <form method="post" action="<?= h(url_with_lang('/form_status.php')) ?>" class="d-inline">
          <?php csrf_field(); ?>
          <input type="hidden" name="action" value="reset">
          <button class="btn btn-outline-secondary"><?= h(t('status.btn.home')) ?></button>
        </form>
      </div>

      <div class="text-muted small mt-4">
        <?= h(tr_status('status.ref', ['id' => (string)$appId])) ?>
      </div>

    </div>
  </div>
</div>

<script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/partials/footer.php'; ?>
