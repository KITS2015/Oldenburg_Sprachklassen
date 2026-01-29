// Datei: public/admin/inc/auth.php
<?php
declare(strict_types=1);

function is_admin_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['is_admin'])
        && $_SESSION['user_id'] === 'admin'
        && $_SESSION['is_admin'] === true;
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}

