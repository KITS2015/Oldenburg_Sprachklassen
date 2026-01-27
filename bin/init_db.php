<?php
// bin/init_db.php
// UTF-8, no BOM
declare(strict_types=1);

require __DIR__ . '/../app/config.php';

/**
 * Helpers
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
    $st = $pdo->prepare($q);
    $st->execute([$db, $table, $col]);
    return (bool)$st->fetchColumn();
}

function idx_exists(PDO $pdo, string $db, string $table, string $idx): bool {
    $q = "SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1";
    $st = $pdo->prepare($q);
    $st->execute([$db, $table, $idx]);
    return (bool)$st->fetchColumn();
}

function table_exists(PDO $pdo, string $db, string $table): bool {
    $q = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? LIMIT 1";
    $st = $pdo->prepare($q);
    $st->execute([$db, $table]);
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

    // ============================
    // applications
    // ============================
    if (!table_exists($admin, $dbName, 'applications')) {
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
        // retrieval_token -> token
        if (col_exists($admin, $dbName, 'applications', 'retrieval_token') && !col_exists($admin, $dbName, 'applications', 'token')) {
            $app->exec("ALTER TABLE applications CHANGE COLUMN retrieval_token token CHAR(32) NOT NULL");
        }

        // token + unique
        if (!col_exists($admin, $dbName, 'applications', 'token')) {
            $app->exec("ALTER TABLE applications ADD COLUMN token CHAR(32) NOT NULL AFTER id");
        }
        if (!idx_exists($admin, $dbName, 'applications', 'uq_token')) {
            $app->exec("ALTER TABLE applications ADD UNIQUE KEY uq_token (token)");
        }

        // geburtsdatum -> dob
        if (col_exists($admin, $dbName, 'applications', 'geburtsdatum') && !col_exists($admin, $dbName, 'applications', 'dob')) {
            $app->exec("ALTER TABLE applications CHANGE COLUMN geburtsdatum dob DATE NULL");
        }
        if (!col_exists($admin, $dbName, 'applications', 'dob')) {
            $app->exec("ALTER TABLE applications ADD COLUMN dob DATE NULL AFTER email");
        } else {
            $app->exec("ALTER TABLE applications MODIFY COLUMN dob DATE NULL");
        }

        // email nullable
        if (col_exists($admin, $dbName, 'applications', 'email')) {
            $st = $app->query("
                SELECT IS_NULLABLE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA=".$app->quote($dbName)."
                  AND TABLE_NAME='applications'
                  AND COLUMN_NAME='email'
            ");
            if (($st->fetchColumn() ?? '') !== 'YES') {
                $app->exec("ALTER TABLE applications MODIFY COLUMN email VARCHAR(255) NULL");
            }
        } else {
            $app->exec("ALTER TABLE applications ADD COLUMN email VARCHAR(255) NULL AFTER token");
        }

        // email_verified
        if (!col_exists($admin, $dbName, 'applications', 'email_verified')) {
            $app->exec("ALTER TABLE applications ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER dob");
        }

        // email_account_id + index
        if (!col_exists($admin, $dbName, 'applications', 'email_account_id')) {
            $app->exec("ALTER TABLE applications ADD COLUMN email_account_id BIGINT UNSIGNED NULL AFTER email_verified");
        }
        if (!idx_exists($admin, $dbName, 'applications', 'idx_email_account_id')) {
            $app->exec("ALTER TABLE applications ADD KEY idx_email_account_id (email_account_id)");
        }

        // data_json
        if (!col_exists($admin, $dbName, 'applications', 'data_json')) {
            $app->exec("ALTER TABLE applications ADD COLUMN data_json JSON NULL AFTER email_account_id");
        }

        // status
        if (!col_exists($admin, $dbName, 'applications', 'status')) {
            $app->exec("ALTER TABLE applications ADD COLUMN status ENUM('draft','submitted','withdrawn') NOT NULL DEFAULT 'draft' AFTER data_json");
        }

        // created_at / updated_at
        if (!col_exists($admin, $dbName, 'applications', 'created_at')) {
            $app->exec("ALTER TABLE applications ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER status");
        }
        if (!col_exists($admin, $dbName, 'applications', 'updated_at')) {
            $app->exec("ALTER TABLE applications ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        }

        // submit_ip
        if (!col_exists($admin, $dbName, 'applications', 'submit_ip')) {
            $app->exec("ALTER TABLE applications ADD COLUMN submit_ip VARBINARY(16) NULL AFTER updated_at");
        }

        // indices
        if (!idx_exists($admin, $dbName, 'applications', 'idx_email')) {
            $app->exec("ALTER TABLE applications ADD KEY idx_email (email)");
        }
        if (!idx_exists($admin, $dbName, 'applications', 'idx_birth')) {
            $app->exec("ALTER TABLE applications ADD KEY idx_birth (dob)");
        }
        if (!idx_exists($admin, $dbName, 'applications', 'idx_email_dob')) {
            $app->exec("ALTER TABLE applications ADD KEY idx_email_dob (email, dob)");
        }
    }

    // ============================
    // email_accounts
    // ============================
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
        if (!col_exists($admin, $dbName, 'email_accounts', 'email')) {
            $app->exec("ALTER TABLE email_accounts ADD COLUMN email VARCHAR(255) NOT NULL");
        }
        if (!idx_exists($admin, $dbName, 'email_accounts', 'uq_email')) {
            $app->exec("ALTER TABLE email_accounts ADD UNIQUE KEY uq_email (email)");
        }
        if (!col_exists($admin, $dbName, 'email_accounts', 'password_hash')) {
            $app->exec("ALTER TABLE email_accounts ADD COLUMN password_hash VARCHAR(255) NULL");
        }
        if (!col_exists($admin, $dbName, 'email_accounts', 'email_verified')) {
            $app->exec("ALTER TABLE email_accounts ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash");
        }
        if (!col_exists($admin, $dbName, 'email_accounts', 'max_tokens')) {
            $app->exec("ALTER TABLE email_accounts ADD COLUMN max_tokens INT NOT NULL DEFAULT 5 AFTER email_verified");
        }
        if (!col_exists($admin, $dbName, 'email_accounts', 'created_at')) {
            $app->exec("ALTER TABLE email_accounts ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }
        if (!col_exists($admin, $dbName, 'email_accounts', 'updated_at')) {
            $app->exec("ALTER TABLE email_accounts ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }

        // safety for older installs
        $app->exec("
          UPDATE email_accounts
          SET password_hash = COALESCE(password_hash, '')
          WHERE password_hash IS NULL
        ");
    }

    // FK applications.email_account_id -> email_accounts.id (try/catch idempotent enough)
    try {
        $app->exec("
          ALTER TABLE applications
          ADD CONSTRAINT fk_app_email_account
          FOREIGN KEY (email_account_id) REFERENCES email_accounts(id)
          ON DELETE SET NULL
        ");
    } catch (Throwable $e) {
        // ignore
    }

    // ============================
    // settings
    // ============================
    $app->exec("
      CREATE TABLE IF NOT EXISTS settings (
        setting_key    VARCHAR(100) NOT NULL,
        setting_value  VARCHAR(255) NOT NULL,
        description    VARCHAR(255) NULL,
        updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (setting_key)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $app->exec("
      INSERT INTO settings (setting_key, setting_value, description)
      VALUES ('max_tokens_per_email', '5', 'Maximale Anzahl an Bewerbungen (Access-Tokens) pro Login-E-Mail')
      ON DUPLICATE KEY UPDATE setting_value = setting_value
    ");

    // ============================
    // personal (inkl. weitere_angaben)
    // ============================
    $app->exec("
      CREATE TABLE IF NOT EXISTS personal (
        application_id   BIGINT UNSIGNED NOT NULL,
        name             VARCHAR(200) NOT NULL,
        vorname          VARCHAR(200) NOT NULL,
        geschlecht       ENUM('m','w','d') NOT NULL,
        geburtsdatum     DATE NOT NULL,
        geburtsort_land  VARCHAR(200) NOT NULL,
        staatsang        VARCHAR(200) NOT NULL,
        strasse          VARCHAR(200) NOT NULL,
        plz              CHAR(5) NOT NULL,
        wohnort          VARCHAR(200) NOT NULL,
        telefon          VARCHAR(100) NOT NULL,
        email            VARCHAR(255) NULL,
        weitere_angaben  TEXT NULL,
        dsgvo_ok         TINYINT(1) NOT NULL DEFAULT 0,
        created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (application_id),
        KEY idx_personal_email (email),
        CONSTRAINT fk_personal_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // migrations/guarantees
    if (table_exists($admin, $dbName, 'personal')) {
        $app->exec("ALTER TABLE personal MODIFY COLUMN email VARCHAR(255) NULL");
        if (!col_exists($admin, $dbName, 'personal', 'weitere_angaben')) {
            $app->exec("ALTER TABLE personal ADD COLUMN weitere_angaben TEXT NULL AFTER email");
        } else {
            $app->exec("ALTER TABLE personal MODIFY COLUMN weitere_angaben TEXT NULL");
        }
    }

    // ============================
    // contacts
    // ============================
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

    // ============================
    // school (OHNE deutsch_jahre)
    // ============================
    $app->exec("
      CREATE TABLE IF NOT EXISTS school (
        application_id         BIGINT UNSIGNED NOT NULL,

        schule_aktuell         VARCHAR(50)  NULL,
        schule_freitext        VARCHAR(255) NULL,
        schule_label           VARCHAR(500) NULL,

        klassenlehrer          VARCHAR(200) NULL,
        mail_lehrkraft         VARCHAR(255) NULL,

        seit_monat             TINYINT UNSIGNED NULL,
        seit_jahr              SMALLINT UNSIGNED NULL,
        seit_text              VARCHAR(50) NULL,

        jahre_in_de            TINYINT UNSIGNED NULL,
        schule_herkunft        ENUM('ja','nein') NULL,
        jahre_schule_herkunft  TINYINT UNSIGNED NULL,
        familiensprache        VARCHAR(200) NULL,

        deutsch_niveau         ENUM('kein','A0','A1','A2','B1','B2','C1','C2') NULL,
        interessen             VARCHAR(500) NULL,

        created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (application_id),
        CONSTRAINT fk_school_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // migrations for school columns (idempotent)
    if (!col_exists($admin, $dbName, 'school', 'schule_aktuell')) {
        $app->exec("ALTER TABLE school ADD COLUMN schule_aktuell VARCHAR(50) NULL AFTER application_id");
    }
    if (!col_exists($admin, $dbName, 'school', 'schule_freitext')) {
        $app->exec("ALTER TABLE school ADD COLUMN schule_freitext VARCHAR(255) NULL AFTER schule_aktuell");
    }
    if (!col_exists($admin, $dbName, 'school', 'schule_label')) {
        $app->exec("ALTER TABLE school ADD COLUMN schule_label VARCHAR(500) NULL AFTER schule_freitext");
    }
    if (!col_exists($admin, $dbName, 'school', 'klassenlehrer')) {
        $app->exec("ALTER TABLE school ADD COLUMN klassenlehrer VARCHAR(200) NULL AFTER schule_label");
    }
    if (!col_exists($admin, $dbName, 'school', 'mail_lehrkraft')) {
        $app->exec("ALTER TABLE school ADD COLUMN mail_lehrkraft VARCHAR(255) NULL AFTER klassenlehrer");
    }
    if (!col_exists($admin, $dbName, 'school', 'seit_monat')) {
        $app->exec("ALTER TABLE school ADD COLUMN seit_monat TINYINT UNSIGNED NULL AFTER mail_lehrkraft");
    }
    if (!col_exists($admin, $dbName, 'school', 'seit_jahr')) {
        $app->exec("ALTER TABLE school ADD COLUMN seit_jahr SMALLINT UNSIGNED NULL AFTER seit_monat");
    }
    if (!col_exists($admin, $dbName, 'school', 'seit_text')) {
        $app->exec("ALTER TABLE school ADD COLUMN seit_text VARCHAR(50) NULL AFTER seit_jahr");
    }
    if (!col_exists($admin, $dbName, 'school', 'jahre_in_de')) {
        $app->exec("ALTER TABLE school ADD COLUMN jahre_in_de TINYINT UNSIGNED NULL AFTER seit_text");
    }
    if (!col_exists($admin, $dbName, 'school', 'schule_herkunft')) {
        $app->exec("ALTER TABLE school ADD COLUMN schule_herkunft ENUM('ja','nein') NULL AFTER jahre_in_de");
    }
    if (!col_exists($admin, $dbName, 'school', 'jahre_schule_herkunft')) {
        $app->exec("ALTER TABLE school ADD COLUMN jahre_schule_herkunft TINYINT UNSIGNED NULL AFTER schule_herkunft");
    }
    if (!col_exists($admin, $dbName, 'school', 'familiensprache')) {
        $app->exec("ALTER TABLE school ADD COLUMN familiensprache VARCHAR(200) NULL AFTER jahre_schule_herkunft");
    }
    if (!col_exists($admin, $dbName, 'school', 'deutsch_niveau')) {
        $app->exec("ALTER TABLE school ADD COLUMN deutsch_niveau ENUM('kein','A0','A1','A2','B1','B2','C1','C2') NULL AFTER familiensprache");
    }
    if (!col_exists($admin, $dbName, 'school', 'interessen')) {
        $app->exec("ALTER TABLE school ADD COLUMN interessen VARCHAR(500) NULL AFTER deutsch_niveau");
    }

    // remove legacy column deutsch_jahre if it exists? -> NO destructive changes in init_db
    // but make sure legacy installs accept A0
    try {
        $app->exec("ALTER TABLE school MODIFY COLUMN deutsch_niveau ENUM('kein','A0','A1','A2','B1','B2','C1','C2') NULL");
    } catch (Throwable $e) {
        // ignore
    }

    // legacy clean-up: if old schema had deutsch_jahre, we leave it (no destructive changes)

    // ============================
    // uploads
    // ============================
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

    // ============================
    // audit_log
    // ============================
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
