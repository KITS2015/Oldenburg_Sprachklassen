<?php
// bin/init_db.php
// UTF-8, no BOM
declare(strict_types=1);

require __DIR__ . '/../app/config.php';

/**
 * Kleine Helpers
 */
function pdo_admin(): PDO {
    return pdo(DB_ADMIN_DSN, DB_ADMIN_USER, DB_ADMIN_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}
function pdo_app(string $dbName): PDO {
    return pdo("mysql:host=127.0.0.1;dbname=$dbName;port=3306;charset=utf8mb4", APP_DB_USER, APP_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ]);
}
function col_exists(PDO $pdo, string $db, string $table, string $col): bool {
    $q = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1";
    $st = $pdo->prepare($q); $st->execute([$db,$table,$col]);
    return (bool)$st->fetchColumn();
}
function idx_exists(PDO $pdo, string $db, string $table, string $idx): bool {
    $q = "SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1";
    $st = $pdo->prepare($q); $st->execute([$db,$table,$idx]);
    return (bool)$st->fetchColumn();
}
function table_exists(PDO $pdo, string $db, string $table): bool {
    $q = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? LIMIT 1";
    $st = $pdo->prepare($q); $st->execute([$db,$table]);
    return (bool)$st->fetchColumn();
}

try {
    $admin = pdo_admin();

    // 1) DB anlegen (idempotent)
    $dbName = APP_DB_NAME;
    $admin->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 2) App-User anlegen/Rechte (idempotent)
    $host = 'localhost';
    $admin->exec("CREATE USER IF NOT EXISTS '".APP_DB_USER."'@'$host' IDENTIFIED BY '".APP_DB_PASS."'");
    $admin->exec("GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX ON `$dbName`.* TO '".APP_DB_USER."'@'$host'");
    $admin->exec("FLUSH PRIVILEGES");

    // 3) Mit App-User verbinden
    $app = pdo_app($dbName);

    // ========= applications =========
    if (!table_exists($admin, $dbName, 'applications')) {
        // Neuinstallation: dob ist NULL-fähig (für E-Mail-Flow ohne DOB direkt nach Verify)
        $app->exec("
          CREATE TABLE IF NOT EXISTS applications (
            id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            token            CHAR(32) NOT NULL,
            email            VARCHAR(255) NULL,
            dob              DATE NULL,
            email_verified   TINYINT(1) NOT NULL DEFAULT 0,
            email_account_id BIGINT UNSIGNED NULL,
            data_json        JSON NULL,
            status           ENUM('draft','submitted','withdrawn') NOT NULL DEFAULT 'draft',
            created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            submit_ip        VARBINARY(16) NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_token (token),
            KEY idx_email (email),
            KEY idx_birth (dob),
            KEY idx_email_dob (email, dob),
            KEY idx_email_account_id (email_account_id)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } else {
        // Migrations/Ergänzungen ohne Datenverlust

        // a) retrieval_token -> token
        if (col_exists($admin,$dbName,'applications','retrieval_token') && !col_exists($admin,$dbName,'applications','token')) {
            $app->exec("ALTER TABLE applications CHANGE COLUMN retrieval_token token CHAR(32) NOT NULL");
        }
        // Token-Spalte erzeugen, falls komplett fehlt + UNIQUE
        if (!col_exists($admin,$dbName,'applications','token')) {
            $app->exec("ALTER TABLE applications ADD COLUMN token CHAR(32) NOT NULL AFTER id");
        }
        if (!idx_exists($admin,$dbName,'applications','uq_token')) {
            $app->exec("ALTER TABLE applications ADD UNIQUE KEY uq_token (token)");
        }

        // b) geburtsdatum -> dob
        if (col_exists($admin,$dbName,'applications','geburtsdatum') && !col_exists($admin,$dbName,'applications','dob')) {
            $app->exec("ALTER TABLE applications CHANGE COLUMN geburtsdatum dob DATE NULL");
        }
        if (!col_exists($admin,$dbName,'applications','dob')) {
            $app->exec("ALTER TABLE applications ADD COLUMN dob DATE NULL AFTER email");
        } else {
            $app->exec("ALTER TABLE applications MODIFY COLUMN dob DATE NULL");
        }

        // c) email (NULL erlauben)
        if (col_exists($admin,$dbName,'applications','email')) {
            $st = $app->query("SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=".$app->quote($dbName)." AND TABLE_NAME='applications' AND COLUMN_NAME='email'");
            $nullable = ($st->fetchColumn() === 'YES');
            if (!$nullable) {
                $app->exec("ALTER TABLE applications MODIFY COLUMN email VARCHAR(255) NULL");
            }
        } else {
            $app->exec("ALTER TABLE applications ADD COLUMN email VARCHAR(255) NULL AFTER token");
        }

        // d) email_verified
        if (!col_exists($admin,$dbName,'applications','email_verified')) {
            $app->exec("ALTER TABLE applications ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER dob");
        }

        // e) email_account_id (NEU)
        if (!col_exists($admin,$dbName,'applications','email_account_id')) {
            // Position: nach email_verified (logisch)
            $app->exec("ALTER TABLE applications ADD COLUMN email_account_id BIGINT UNSIGNED NULL AFTER email_verified");
        }
        if (!idx_exists($admin,$dbName,'applications','idx_email_account_id')) {
            $app->exec("ALTER TABLE applications ADD KEY idx_email_account_id (email_account_id)");
        }

        // f) data_json
        if (!col_exists($admin,$dbName,'applications','data_json')) {
            $app->exec("ALTER TABLE applications ADD COLUMN data_json JSON NULL AFTER email_account_id");
        }

        // g) status
        if (!col_exists($admin,$dbName,'applications','status')) {
            $app->exec("ALTER TABLE applications ADD COLUMN status ENUM('draft','submitted','withdrawn') NOT NULL DEFAULT 'draft' AFTER data_json");
        }

        // h) created_at / updated_at
        if (!col_exists($admin,$dbName,'applications','created_at')) {
            $app->exec("ALTER TABLE applications ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER status");
        }
        if (!col_exists($admin,$dbName,'applications','updated_at')) {
            $app->exec("ALTER TABLE applications ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        }

        // i) submit_ip
        if (!col_exists($admin,$dbName,'applications','submit_ip')) {
            $app->exec("ALTER TABLE applications ADD COLUMN submit_ip VARBINARY(16) NULL AFTER updated_at");
        }

        // j) Indizes
        if (!idx_exists($admin,$dbName,'applications','idx_email')) {
            $app->exec("ALTER TABLE applications ADD KEY idx_email (email)");
        }
        if (!idx_exists($admin,$dbName,'applications','idx_birth')) {
            $app->exec("ALTER TABLE applications ADD KEY idx_birth (dob)");
        }
        if (!idx_exists($admin,$dbName,'applications','idx_email_dob')) {
            $app->exec("ALTER TABLE applications ADD KEY idx_email_dob (email, dob)");
        }
    }

    // ========= email_accounts (NEU) =========
    if (!table_exists($admin, $dbName, 'email_accounts')) {
        $app->exec("
          CREATE TABLE IF NOT EXISTS email_accounts (
            id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email          VARCHAR(255) NOT NULL,
            password_hash  VARCHAR(255) NOT NULL,
            email_verified TINYINT(1) NOT NULL DEFAULT 0,
            max_tokens     INT NOT NULL DEFAULT 5,
            created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_email (email)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } else {
        // Migration: Spalten ergänzen falls Tabelle schon existiert (zukünftige Sicherheit)
        if (!col_exists($admin,$dbName,'email_accounts','email')) {
            $app->exec("ALTER TABLE email_accounts ADD COLUMN email VARCHAR(255) NOT NULL");
        }
        if (!idx_exists($admin,$dbName,'email_accounts','uq_email')) {
            $app->exec("ALTER TABLE email_accounts ADD UNIQUE KEY uq_email (email)");
        }
        if (!col_exists($admin,$dbName,'email_accounts','password_hash')) {
            // kein Default -> muss später gefüllt werden
            $app->exec("ALTER TABLE email_accounts ADD COLUMN password_hash VARCHAR(255) NULL");
        }
        if (!col_exists($admin,$dbName,'email_accounts','email_verified')) {
            $app->exec("ALTER TABLE email_accounts ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash");
        }
        if (!col_exists($admin,$dbName,'email_accounts','max_tokens')) {
            $app->exec("ALTER TABLE email_accounts ADD COLUMN max_tokens INT NOT NULL DEFAULT 5 AFTER email_verified");
        }
        if (!col_exists($admin,$dbName,'email_accounts','created_at')) {
            $app->exec("ALTER TABLE email_accounts ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }
        if (!col_exists($admin,$dbName,'email_accounts','updated_at')) {
            $app->exec("ALTER TABLE email_accounts ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }

        // Wenn password_hash NULL ist (durch spätere Migration), setzen wir ein Dummy-Hash,
        // damit später NOT NULL erzwungen werden kann, ohne bestehenden Betrieb zu brechen.
        // Hinweis: Besser: in einem separaten Migrationsschritt sauber füllen.
        $app->exec("
          UPDATE email_accounts
          SET password_hash = COALESCE(password_hash, '')
          WHERE password_hash IS NULL
        ");
    }

    // FK von applications.email_account_id -> email_accounts.id (optional, aber empfohlen)
    // Wir prüfen das über INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS ist möglich, aber aufwändig.
    // Einfacher: Versuch mit try/catch; falls existiert -> Fehler ignorieren.
    try {
        $app->exec("
          ALTER TABLE applications
          ADD CONSTRAINT fk_app_email_account
          FOREIGN KEY (email_account_id) REFERENCES email_accounts(id)
          ON DELETE SET NULL
        ");
    } catch (Throwable $e) {
        // ignorieren: Constraint existiert ggf. schon oder DB erlaubt es nicht
    }

    // ========= settings (Grundeinstellungen) =========
    $app->exec("
      CREATE TABLE IF NOT EXISTS settings (
        setting_key    VARCHAR(100) NOT NULL,
        setting_value  VARCHAR(255) NOT NULL,
        description    VARCHAR(255) NULL,
        updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (setting_key)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Standardwert für max. Tokens pro E-Mail (z. B. 5)
    $app->exec("
      INSERT INTO settings (setting_key, setting_value, description)
      VALUES ('max_tokens_per_email', '5', 'Maximale Anzahl an Bewerbungen (Access-Tokens) pro Login-E-Mail')
      ON DUPLICATE KEY UPDATE setting_value = setting_value
    ");

    // ========= personal =========
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
        email           VARCHAR(255) NULL,
        dsgvo_ok        TINYINT(1) NOT NULL DEFAULT 0,
        created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (application_id),
        KEY idx_personal_email (email),
        CONSTRAINT fk_personal_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    if (table_exists($admin, $dbName, 'personal')) {
        $app->exec("ALTER TABLE personal MODIFY COLUMN email VARCHAR(255) NULL");
    }

    // ========= contacts =========
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

    // ========= school =========
    $app->exec("
      CREATE TABLE IF NOT EXISTS school (
        application_id      BIGINT UNSIGNED NOT NULL,
        schule_besucht      TINYINT(1) NOT NULL DEFAULT 0,
        schule_jahre        TINYINT UNSIGNED NULL,
        seit_monat          TINYINT UNSIGNED NULL,
        seit_jahr           SMALLINT UNSIGNED NULL,
        deutsch_niveau      ENUM('kein','A1','A2','B1','B2','C1','C2') NULL,
        deutsch_jahre       DECIMAL(3,1) NULL,
        interessen          VARCHAR(500) NULL,
        created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (application_id),
        CONSTRAINT fk_school_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ========= uploads =========
    $app->exec("
      CREATE TABLE IF NOT EXISTS uploads (
        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        application_id BIGINT UNSIGNED NOT NULL,
        typ            ENUM('zeugnis','lebenslauf') NOT NULL,
        filename       VARCHAR(255) NOT NULL,
        mime           VARCHAR(100) NOT NULL,
        size_bytes     INT UNSIGNED NOT NULL,
        uploaded_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_uploads_app (application_id),
        UNIQUE KEY uq_app_typ (application_id, typ),
        CONSTRAINT fk_uploads_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ========= audit_log =========
    $app->exec("
      CREATE TABLE IF NOT EXISTS audit_log (
        id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        application_id BIGINT UNSIGNED NOT NULL,
        event          VARCHAR(100) NOT NULL,
        meta_json      JSON NULL,
        created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_audit_app (application_id),
        CONSTRAINT fk_audit_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "[OK] Datenbank ist aktuell (Tabellen/Spalten/Indizes ergänzt, nichts gelöscht).\n";

} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] ".$e->getMessage()."\n");
    exit(1);
}
