<?php
declare(strict_types=1);

// Datei: public/admin/inc/header.php
// Erwartet Variablen (optional):
//   $pageTitle  (string)
//   $activeNav  (string) 'dashboard'|'applications'|'bbs'

$pageTitle = $pageTitle ?? 'Admin';
$activeNav = $activeNav ?? '';

$adminLabel = (string)($_SESSION['admin_username'] ?? $_SESSION['admin_name'] ?? 'Admin');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function nav_active(string $key, string $activeNav): string
{
    return $key === $activeNav ? 'active' : '';
}
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
<body class="admin-body admin-body--app">
<div class="admin-layout">

    <!-- Topbar -->
    <nav class="navbar navbar-dark bg-dark admin-topbar">
        <div class="container-fluid">
            <a class="navbar-brand fw-semibold" href="/admin/dashboard.php">
                Sprach-Portal · Admin
            </a>

            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary">
                    eingeloggt: <?php echo h($adminLabel); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="/admin/logout.php">Abmelden</a>
            </div>
        </div>
    </nav>

    <div class="admin-main">

        <!-- Sidebar -->
