<?php
declare(strict_types=1);

/**
 * Datei: public/admin/inc/header.php
 * Erwartet (optional):
 *  - $pageTitle (string)
 *  - $activeNav (string)  // dashboard | applications | bbs
 */

$pageTitle = $pageTitle ?? 'Admin';
$activeNav = $activeNav ?? '';
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title><?php echo h($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/admin/admin.css">
</head>
<body class="admin-shell">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark admin-topbar">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="/admin/dashboard.php">Sprach-Portal Admin</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminTopNav"
                aria-controls="adminTopNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminTopNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/admin/logout.php">Abmelden</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="admin-layout">
    <aside class="admin-sidebar bg-white border-end">
        <div class="p-3">
            <div class="text-uppercase small text-muted mb-2">Navigation</div>

            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action <?php echo $activeNav==='dashboard'?'active':''; ?>"
                   href="/admin/dashboard.php">Dashboard</a>

                <a class="list-group-item list-group-item-action <?php echo $activeNav==='applications'?'active':''; ?>"
                   href="/admin/applications.php">Bewerbungen</a>

                <a class="list-group-item list-group-item-action <?php echo $activeNav==='bbs'?'active':''; ?>"
                   href="/admin/bbs.php">BBS / API-Clients</a>
            </div>
        </div>
    </aside>

    <main class="admin-content">
        <div class="container-fluid py-4">
