<?php
// bin/init_db.php
// UTF-8, no BOM
declare(strict_types=1);
require __DIR__ . '/../app/config.php';

try {
  $admin = pdo(DB_ADMIN_DSN, DB_ADMIN_USER, DB_ADMIN_PASS);

  // 1) DB anlegen
  $dbName = APP_DB_NAME;
  $admin->exec("
    CREATE DATABASE IF NOT EXISTS `$dbName`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci
  ");

  // 2) App-User anlegen (idempotent)
  // Hinweis: In manchen MariaDB-Versionen muss man vorher evtl. DROP USER IF EXISTS ausführen
  $host = 'localhost';
  $admin->exec("CREATE USER IF NOT EXISTS '".APP_DB_USER."'@'$host' IDENTIFIED BY '".APP_DB_PASS."'");
  $admin->exec("GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX ON `$dbName`.* TO '".APP_DB_USER."'@'$host'");
  $admin->exec("FLUSH PRIVILEGES");

  // 3) Tabellen erzeugen
  $app = pdo("mysql:host=127.0.0.1;dbname=$dbName;port=3306;charset=utf8mb4", APP_DB_USER, APP_DB_PASS);

  // applications: Kopf / Wiederherstellung
  $app->exec("
    CREATE TABLE IF NOT EXISTS applications (
      id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      retrieval_token CHAR(32) NOT NULL UNIQUE,     -- stabile Bewerbungs-ID (Hex, z.B. 32 Zeichen)
      email           VARCHAR(255) NOT NULL,        -- private E-Mail
      geburtsdatum    DATE NOT NULL,                -- für Wiederherstellung
      status          ENUM('draft','submitted','withdrawn') NOT NULL DEFAULT 'draft',
      created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      submit_ip       VARBINARY(16) NULL,           -- IP bei Absenden (IPv4/6)
      PRIMARY KEY (id),
      KEY idx_email (email),
      KEY idx_birth (geburtsdatum)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");

  // personal: Schritt 1
  $app->exec("
    CREATE TABLE IF NOT EXISTS personal (
      application_id  BIGINT UNSIGNED NOT NULL,
      name            VARCHAR(200) NOT NULL,
      vorname         VARCHAR(200) NOT NULL,
      geschlecht      ENUM('m','w','d') NOT NULL,
      geburtsdatum    DATE NOT NULL,
      geburtsort_land VARCHAR(200) NOT NULL,
      staatsang       VARCHAR(200) NOT NULL,
      strasse         VARCHAR(200) NOT NULL,
      plz             CHAR(5) NOT NULL,
      wohnort         VARCHAR(200) NOT NULL,
      telefon         VARCHAR(100) NOT NULL,
      email           VARCHAR(255) NOT NULL,
      dsgvo_ok        TINYINT(1) NOT NULL DEFAULT 0,
      created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (application_id),
      CONSTRAINT fk_personal_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
      KEY idx_personal_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");

  // contacts: strukturierte Zusatzkontakte (0..n)
  $app->exec("
    CREATE TABLE IF NOT EXISTS contacts (
      id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      application_id BIGINT UNSIGNED NOT NULL,
      rolle          VARCHAR(100) NULL,
      name           VARCHAR(200) NOT NULL,
      tel            VARCHAR(100) NULL,
      mail           VARCHAR(255) NULL,
      notiz          VARCHAR(500) NULL,
      created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_contacts_app (application_id),
      CONSTRAINT fk_contacts_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");

  // school: Schritt 2 – Schul-/Sprachangaben (passe Felder an eure finale Form an)
  $app->exec("
    CREATE TABLE IF NOT EXISTS school (
      application_id      BIGINT UNSIGNED NOT NULL,
      schule_besucht      TINYINT(1) NOT NULL DEFAULT 0,
      schule_jahre        TINYINT UNSIGNED NULL,
      seit_monat          TINYINT UNSIGNED NULL,  -- 1..12
      seit_jahr           SMALLINT UNSIGNED NULL, -- 1900..2100
      deutsch_niveau      ENUM('kein','A1','A2','B1','B2','C1','C2') NULL,
      deutsch_jahre       DECIMAL(3,1) NULL,
      interessen          VARCHAR(500) NULL,
      created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (application_id),
      CONSTRAINT fk_school_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");

  // uploads: Schritt 3 – Datei-Metadaten
  $app->exec("
    CREATE TABLE IF NOT EXISTS uploads (
      id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      application_id BIGINT UNSIGNED NOT NULL,
      typ            ENUM('zeugnis','lebenslauf') NOT NULL,
      filename       VARCHAR(255) NOT NULL,        -- tatsächlicher Dateiname im uploads/-Verzeichnis
      mime           VARCHAR(100) NOT NULL,
      size_bytes     INT UNSIGNED NOT NULL,
      uploaded_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_uploads_app (application_id),
      CONSTRAINT fk_uploads_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
      UNIQUE KEY uq_app_typ (application_id, typ) -- pro Typ max. 1 Datei
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");

  // review/audit: optional Audit-Log
  $app->exec("
    CREATE TABLE IF NOT EXISTS audit_log (
      id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      application_id BIGINT UNSIGNED NOT NULL,
      event          VARCHAR(100) NOT NULL,     -- e.g., 'create','update_personal','upload_zeugnis','submit'
      meta_json      JSON NULL,
      created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_audit_app (application_id),
      CONSTRAINT fk_audit_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");

  echo "[OK] Datenbank und Tabellen sind bereit.\n";

} catch (Throwable $e) {
  fwrite(STDERR, "[ERROR] ".$e->getMessage()."\n");
  exit(1);
}
