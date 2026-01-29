// Datei: public/admin/inc/bootstrap.php
<?php
declare(strict_types=1);

session_start();

// Zentrale Konfiguration laden
// Pfad: public/admin/inc -> public -> (Projektroot) -> app/config.php
$configFile = __DIR__ . '/../../../app/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo 'Fehler: app/config.php nicht gefunden.';
    exit;
}
require_once $configFile;

// Optional: Wenn ihr eine zentrale DB-Datei habt, könnt ihr sie hier einbinden.
// Beispiel (nur aktivieren, wenn vorhanden):
// $dbFile = __DIR__ . '/../../../app/db.php';
// if (file_exists($dbFile)) {
//     require_once $dbFile;
// }
