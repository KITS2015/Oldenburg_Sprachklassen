<?php
declare(strict_types=1);

// Datei: public/admin/dashboard.php

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';

require_admin();

// Optional: einfache Anzeige
$adminName = 'Admin';
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Sprach-Portal – Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/admin/admin.css">
</head>
<body class="admin-body admin-body--app">
    <div class="container py-4 admin-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h4 mb-1">Admin Dashboard</h1>
                <div class="text-muted">Sprach-Portal – interner Bereich</div>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-secondary align-self-center">eingeloggt: <?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></span>
                <a class="btn btn-outline-danger btn-sm" href="/admin/logout.php">Abmelden</a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6">Bewerbungen</h2>
                        <p class="text-muted mb-3">Datensätze ansehen, filtern und später exportieren.</p>
                        <a class="btn btn-primary btn-sm disabled" href="#" aria-disabled="true">Öffnen (kommt als nächstes)</a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6">BBS / API-Clients</h2>
                        <p class="text-muted mb-3">Berufsschulen anlegen, Token/Keys verwalten.</p>
                        <a class="btn btn-primary btn-sm disabled" href="#" aria-disabled="true">Öffnen (kommt als nächstes)</a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6">System</h2>
                        <p class="text-muted mb-3">Status, Konfiguration, Logs (später).</p>
                        <a class="btn btn-primary btn-sm disabled" href="#" aria-disabled="true">Öffnen (kommt später)</a>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="text-muted small">
            Hinweis: Dieser Bereich ist nur intern/VPN erreichbar. Alle Aktionen werden später protokolliert (audit_log).
        </div>
    </div>

    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
