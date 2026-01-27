<?php
// public/form_status.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/db.php';

// Diese Seite soll nur nach einem Submit erreichbar sein
$readonly  = !empty($_SESSION['application_readonly']);
$submitted = $_SESSION['application_submitted'] ?? null;

$token = current_access_token();
if (!$readonly || !$submitted || !$token) {
    // Fallback: wenn jemand direkt aufruft -> Startseite
    header('Location: /index.php');
    exit;
}

$appId = (int)($submitted['app_id'] ?? 0);
if ($appId <= 0) {
    header('Location: /index.php');
    exit;
}

// Status aus DB prüfen (optional, aber sinnvoll)
try {
    $pdo = db();
    $st  = $pdo->prepare("SELECT status, created_at, updated_at FROM applications WHERE id = :id AND token = :t LIMIT 1");
    $st->execute([':id' => $appId, ':t' => $token]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row || ($row['status'] ?? '') !== 'submitted') {
        header('Location: /index.php');
        exit;
    }
} catch (Throwable $e) {
    error_log('form_status: '.$e->getMessage());
    header('Location: /index.php');
    exit;
}

$title     = 'Bewerbung erfolgreich gespeichert';
$html_lang = 'de';
$html_dir  = 'ltr';

require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';
?>
<div class="container py-4">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">

      <h1 class="h4 mb-3">Ihre Bewerbung wurde erfolgreich gespeichert.</h1>

      <div class="alert alert-success">
        <div class="fw-semibold mb-1">Vielen Dank!</div>
        <div>Ihre Bewerbung wurde übermittelt und wird nun bearbeitet.</div>
      </div>

      <!-- Platzhalter für Kunden-Textbaustein -->
      <div class="alert alert-info">
        <div class="fw-semibold mb-1">Wichtiger Hinweis</div>
        <div>
          <em>[PLATZHALTER: Textbaustein vom Kunden folgt]</em>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mt-4">
        <a class="btn btn-outline-primary" href="/application_pdf.php">
          PDF herunterladen / drucken
        </a>

        <form method="post" action="/form_review.php" class="d-inline">
          <?php csrf_field(); ?>
          <input type="hidden" name="action" value="new_application">
          <button class="btn btn-primary">Weitere Bewerbung starten</button>
        </form>

        <form method="post" action="/form_review.php" class="d-inline">
          <?php csrf_field(); ?>
          <input type="hidden" name="action" value="reset">
          <button class="btn btn-outline-secondary">Zur Startseite</button>
        </form>
      </div>

      <div class="text-muted small mt-4">
        Referenz: Bewerbung #<?= (int)$appId ?>
      </div>

    </div>
  </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/partials/footer.php'; ?>
