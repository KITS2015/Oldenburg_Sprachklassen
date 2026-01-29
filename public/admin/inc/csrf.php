<?php
declare(strict_types=1);

// Datei: public/admin/inc/csrf.php

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(string $token): bool
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        return false;
    }
    if ($token === '') {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
