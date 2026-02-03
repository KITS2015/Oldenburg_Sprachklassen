<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

require_admin();

$pageTitle = 'Admin – Dashboard';
$activeNav = 'dashboard';

require_once __DIR__ . '/inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Admin Dashboard</h1>
        <div class="text-muted">Sprach-Portal – interner Bereich</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Bewerbungen</h2>
                <p class="text-muted mb-3">Datensätze ansehen, zuweisen, locken und exportieren.</p>
                <a class="btn btn-primary btn-sm" href="/admin/applications.php">Öffnen</a>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">BBS / API-Clients</h2>
                <p class="text-muted mb-3">Berufsschulen anlegen, Kurzbezeichnung pflegen.</p>
                <a class="btn btn-primary btn-sm" href="/admin/bbs.php">Öffnen</a>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">System</h2>
                <p class="text-muted mb-3">Status, Konfiguration, Logs (später).</p>
                <a class="btn btn-outline-secondary btn-sm disabled" href="#" aria-disabled="true">Kommt später</a>
            </div>
        </div>
    </div>
</div>

<hr class="my-4">
<div class="text-muted small">
    Hinweis: Dieser Bereich ist nur intern/VPN erreichbar.
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
