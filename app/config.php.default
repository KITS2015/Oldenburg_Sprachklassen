<?php
// app/config.php
// UTF-8, no BOM
declare(strict_types=1);

// DB Admin-Konto NUR für init_db.php (kann danach entzogen werden)
const DB_ADMIN_DSN  = 'mysql:host=127.0.0.1;port=3306;charset=utf8mb4';
const DB_ADMIN_USER = 'root';
const DB_ADMIN_PASS = 'xxx';

// Ziel-Datenbank + App-User
const APP_DB_NAME   = 'xxx_app';
const APP_DB_USER   = 'xxx_user';
const APP_DB_PASS   = 'xxx';

// PDO Helper
function pdo(string $dsn, string $user, string $pass): PDO {
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
  ]);
  return $pdo;
}
