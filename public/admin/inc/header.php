<?php
declare(strict_types=1);

/**
 * Datei: public/admin/inc/header.php
 * Erwartete Variablen (optional):
 * - $title  (string) Seitentitel
 * - $active (string) 'dashboard' | 'applications' | 'bbs'
 */

if (!function_exists('h')) {
    function h(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

$title  = isset($title)  ? (string)$title  : 'Admin';
$active = isset($active) ? (string)$active : '';

$adminLabel = (string)($_SESSION['admin_username'] ?? $_SESSION['admin_name'] ?? 'Admin');
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title><?php echo h($title); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/admin/admin.css">
</head>
<body class="admin-body admin-body--app">

<div class="admin-shell">

    <!-- Sidebar -->
    <aside class="admin-sidebar p-3">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="brand">Sprach-Portal</div>
            <span class="badge bg-secondary">Admin</span>
        </div>

        <nav class="nav flex-column gap-1">
            <a class="nav-link <?php echo $active==='dashboard'?'active':''; ?>" href="/admin/dashboard.php">
                Dashboard
            </a>
            <a class="nav-link <?php echo $active==='applications'?'active':''; ?>" href="/admin/applications.php">
                Bewerbungen
            </a>
            <a class="nav-link <?php echo $active==='bbs'?'active':''; ?>" href="/admin/bbs.php">
                BBS / API-Clients
            </a>
        </nav>

        <hr>

        <div class="small text-muted">
            Intern/VPN · Aktionen später via audit_log
        </div>
    </aside>

    <!-- Main -->
    <main class="admin-main">

        <!-- Topbar -->
        <div class="admin-topbar py-2 px-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="fw-semibold"><?php echo h($title); ?></div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary">eingeloggt: <?php echo h($adminLabel); ?></span>
                    <a class="btn btn-outline-danger btn-sm" href="/admin/logout.php">Abmelden</a>
                </div>
            </div>
        </div>

        <div class="admin-content">
