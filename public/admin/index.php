// Datei: public/admin/index.php
<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';

// Wenn bereits eingeloggt → Dashboard
if (is_admin_logged_in()) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Sonst → Login
header('Location: /admin/login.php');
exit;

