<?php
declare(strict_types=1);

// Datei: public/admin/login.php

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';

// Wenn bereits eingeloggt → Dashboard
if (is_admin_logged_in()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $csrf     = (string)($_POST['csrf_token'] ?? '');

    $_SESSION['login_tries'] = (int)($_SESSION['login_tries'] ?? 0);

    if ($_SESSION['login_tries'] >= 10) {
        $error = 'Zu viele Fehlversuche. Bitte später erneut versuchen.';
    } elseif (!csrf_verify($csrf)) {
        $error = 'Ungültige Anfrage (CSRF).';
    } elseif (!defined('ADMIN_USER') || !defined('ADMIN_PASS_HASH')) {
        $error = 'Admin-Zugangsdaten sind nicht konfiguriert (ADMIN_USER/ADMIN_PASS_HASH).';
    } elseif ($username !== ADMIN_USER || !password_verify($password, ADMIN_PASS_HASH)) {
        $_SESSION['login_tries']++;
        $error = 'Benutzer oder Passwort ist falsch.';
    } else {
        // Erfolg
        $_SESSION['user_id'] = 'admin';
        $_SESSION['is_admin'] = true;
        $_SESSION['login_tries'] = 0;

        session_regenerate_id(true);

        header('Location: /admin/dashboard.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Sprach-Portal – Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap (liegt bei euch unter /public/assets/bootstrap/css) -->
    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">

    <!-- Admin CSS -->
    <link rel="stylesheet" href="/admin/admin.css">
</head>
<body class="admin-body">
    <main class="admin-login-wrapper">
        <div class="card admin-card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Admin Login</h1>
                <p class="text-muted mb-4">Sprach-Portal – interner Bereich</p>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="/admin/login.php" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="mb-3">
                        <label for="username" class="form-label">Benutzer</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Passwort</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Anmelden</button>
                </form>
            </div>
        </div>
    </main>

    <script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
