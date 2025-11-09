<?php
// app/db.php
// UTF-8, no BOM
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * App-PDO mit eingeschränktem DB-User.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=127.0.0.1;dbname=' . APP_DB_NAME . ';port=3306;charset=utf8mb4',
            APP_DB_USER,
            APP_DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]
        );
    }
    return $pdo;
}

/** IPv4/IPv6 in binär für DB speichern (VARBINARY(16)). */
function ip_to_bin(?string $ip): ?string {
    if (!$ip) return null;
    $bin = @inet_pton($ip);
    return $bin === false ? null : $bin;
}

/** Zufälliger hex-Token (32 Zeichen). */
function random_hex32(): string {
    return bin2hex(random_bytes(16));
}
