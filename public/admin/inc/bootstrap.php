<?php
declare(strict_types=1);

// Datei: public/admin/inc/bootstrap.php

session_start();

// Zentrale Konfiguration laden
$configFile = __DIR__ . '/../../../app/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo 'Fehler: app/config.php nicht gefunden.';
    exit;
}
require_once $configFile;

/**
 * Admin-DB Verbindung (App-DB)
 */
function admin_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=127.0.0.1;dbname=' . APP_DB_NAME . ';port=3306;charset=utf8mb4';
    $pdo = pdo($dsn, APP_DB_USER, APP_DB_PASS);
    return $pdo;
}
