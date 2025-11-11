<?php
// app/functions_form.php
// Persistenz-/DB-Funktionen für die Formulare.
// Achtung: Benötigt app/db.php und public/wizard/_common.php (für helpers & session).
// UTF-8, no BOM
declare(strict_types=1);

require_once __DIR__ . '/db.php'; // stellt db():PDO bereit

/**
 * Erwartetes Schema der Tabelle `applications` (Kurzfassung):
 *
 * CREATE TABLE applications (
 *   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *   token CHAR(32) NOT NULL UNIQUE,
 *   email VARCHAR(255) NULL,
 *   dob DATE NULL,
 *   email_verified TINYINT(1) NOT NULL DEFAULT 0,
 *   data_json JSON NULL,
 *   status ENUM('draft','submitted','withdrawn') NOT NULL DEFAULT 'draft',
 *   created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *   updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 *   submit_ip VARBINARY(16) NULL
 * );
 *
 * Falls deine alte Tabelle noch Spalten wie `retrieval_token` / `geburtsdatum` hat,
 * kannst du migrieren (einmalig):
 *
 *   ALTER TABLE applications
 *     CHANGE retrieval_token token CHAR(32) NOT NULL UNIQUE,
 *     CHANGE geburtsdatum dob DATE NULL,
 *     ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER dob,
 *     ADD COLUMN data_json JSON NULL AFTER email_verified;
 */

// -------------------------
// Hilfen
// -------------------------

/** Merged $payload in applications.data_json (JSON-Merge/Replace-Recursive). */
function merge_payload(PDO $pdo, string $token, array $payload): array {
    $stmt = $pdo->prepare("SELECT data_json FROM applications WHERE token = :t LIMIT 1");
    $stmt->execute([':t'=>$token]);
    $merged = $payload;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $prev = json_decode((string)$row['data_json'], true);
        if (is_array($prev)) {
            $merged = array_replace_recursive($prev, $payload);
        }
    }
    return $merged;
}

// -------------------------
// Upsert-Varianten
// -------------------------

/**
 * Upsert NUR mit Token + DOB (ohne E-Mail, nicht verifiziert).
 * - Erzeugt Token aus Session, falls nicht vorhanden.
 * - Setzt/aktualisiert `dob`, merged `$payload` in `data_json`.
 */
function ensure_record_token_and_dob_only(string $dob_dmy, array $payload = []): array {
    if (!function_exists('norm_date_dmy_to_ymd')) {
        return ['ok'=>false,'token'=>'','err'=>'Helper fehlt'];
    }
    $dob = norm_date_dmy_to_ymd($dob_dmy);
    if ($dob === '') return ['ok'=>false,'token'=>current_access_token(),'err'=>'Geburtsdatum ungültig'];

    // Token aus Session oder neu
    if (!function_exists('current_access_token') || !function_exists('issue_access_token')) {
        return ['ok'=>false,'token'=>'','err'=>'Token-Helper fehlen'];
    }
    $token = current_access_token();
    if ($token === '') $token = issue_access_token();

    try {
        $pdo = db();
        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        // Payload mergen
        $merged = merge_payload($pdo, $token, $payload);

        // Existiert Datensatz?
        $check = $pdo->prepare("SELECT 1 FROM applications WHERE token = :t LIMIT 1");
        $check->execute([':t'=>$token]);
        if ($check->fetchColumn()) {
            $u = $pdo->prepare("UPDATE applications
                                SET dob = :d, data_json = :j, updated_at = :u
                                WHERE token = :t");
            $u->execute([
                ':d'=>$dob,
                ':j'=>json_encode($merged, JSON_UNESCAPED_UNICODE),
                ':u'=>$now,
                ':t'=>$token
            ]);
        } else {
            $i = $pdo->prepare("INSERT INTO applications (token, email, dob, email_verified, data_json, created_at, updated_at, status)
                                VALUES (:t, NULL, :d, 0, :j, :c, :u, 'draft')");
            $i->execute([
                ':t'=>$token,
                ':d'=>$dob,
                ':j'=>json_encode($merged, JSON_UNESCAPED_UNICODE),
                ':c'=>$now,
                ':u'=>$now
            ]);
        }
        return ['ok'=>true,'token'=>$token,'err'=>''];
    } catch (Throwable $e) {
        error_log('ensure_record_token_and_dob_only: '.$e->getMessage());
        return ['ok'=>false,'token'=>$token,'err'=>'DB-Fehler'];
    }
}

/**
 * Upsert mit verifizierter E-Mail + DOB (Standardweg).
 * Voraussetzung: $_SESSION['email_verified'] === true
 */
function ensure_record_with_token(string $email, string $geburtsdatum_dmy, array $payload = []): array {
    if (empty($_SESSION['email_verified'])) {
        return ['ok'=>false,'token'=>'','err'=>'E-Mail nicht verifiziert'];
    }
    if (!function_exists('norm_date_dmy_to_ymd')) {
        return ['ok'=>false,'token'=>'','err'=>'Helper fehlt'];
    }
    $dob = norm_date_dmy_to_ymd($geburtsdatum_dmy);
    if ($dob === '') {
        return ['ok'=>false,'token'=>'','err'=>'Geburtsdatum ungültig'];
    }

    if (!function_exists('current_access_token') || !function_exists('issue_access_token')) {
        return ['ok'=>false,'token'=>'','err'=>'Token-Helper fehlen'];
    }
    $token = current_access_token();
    if ($token === '') $token = issue_access_token();

    try {
        $pdo = db();
        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        $merged = merge_payload($pdo, $token, $payload);

        $check = $pdo->prepare("SELECT 1 FROM applications WHERE token = :t LIMIT 1");
        $check->execute([':t'=>$token]);
        if ($check->fetchColumn()) {
            $u = $pdo->prepare("UPDATE applications
                                SET email = :e, dob = :d, email_verified = 1,
                                    data_json = :j, updated_at = :u
                                WHERE token = :t");
            $u->execute([
                ':e'=>$email,
                ':d'=>$dob,
                ':j'=>json_encode($merged, JSON_UNESCAPED_UNICODE),
                ':u'=>$now,
                ':t'=>$token
            ]);
        } else {
            $i = $pdo->prepare("INSERT INTO applications (token, email, dob, email_verified, data_json, created_at, updated_at, status)
                                VALUES (:t, :e, :d, 1, :j, :c, :u, 'draft')");
            $i->execute([
                ':t'=>$token,
                ':e'=>$email,
                ':d'=>$dob,
                ':j'=>json_encode($merged, JSON_UNESCAPED_UNICODE),
                ':c'=>$now,
                ':u'=>$now
            ]);
        }
        return ['ok'=>true,'token'=>$token,'err'=>''];
    } catch (Throwable $e) {
        error_log('ensure_record_with_token: '.$e->getMessage());
        return ['ok'=>false,'token'=>$token,'err'=>'DB-Fehler'];
    }
}

/**
 * Komfort: Speichert einen Teil (Scope) in data_json unter form.{scope}.
 * - Wenn verifizierte E-Mail + DOB vorhanden → ensure_record_with_token()
 * - sonst, wenn DOB vorhanden → ensure_record_token_and_dob_only()
 * - sonst nur Session (kein DB-Write)
 */
function save_scope_allow_noemail(string $scope, array $data): array {
    $_SESSION['form'][$scope] = $data;

    $email = (string)($_SESSION['form']['personal']['email'] ?? '');
    $dob   = (string)($_SESSION['form']['personal']['geburtsdatum'] ?? '');

    $payload = ['form' => [$scope => $data]];

    if (!empty($_SESSION['email_verified']) && $email !== '' && $dob !== '') {
        return ensure_record_with_token($email, $dob, $payload);
    }
    if ($dob !== '') {
        return ensure_record_token_and_dob_only($dob, $payload);
    }
    return ['ok'=>false,'token'=>current_access_token(),'err'=>'nur Session (DOB fehlt)'];
}

// -------------------------
// Spezifischer Helfer: Schritt 1 (personal)
// -------------------------

/**
 * Nimmt bereits validierte POST-Felder entgegen und speichert sie über save_scope_allow_noemail().
 * $contacts: strukturierte Liste (rolle,name,tel,mail,notiz)
 */
function save_personal_block(array $post, array $contacts): array {
    $data = [
        'name'            => trim((string)($post['name']            ?? '')),
        'vorname'         => trim((string)($post['vorname']         ?? '')),
        'geschlecht'      => (string)($post['geschlecht']           ?? ''),
        'geburtsdatum'    => (string)($post['geburtsdatum']         ?? ''), // TT.MM.JJJJ (UI)
        'geburtsort_land' => trim((string)($post['geburtsort_land'] ?? '')),
        'staatsang'       => trim((string)($post['staatsang']       ?? '')),
        'strasse'         => trim((string)($post['strasse']         ?? '')),
        'plz'             => trim((string)($post['plz']             ?? '')),
        'wohnort'         => (string)($post['wohnort']              ?? 'Oldenburg (Oldb)'),
        'telefon'         => trim((string)($post['telefon']         ?? '')),
        'email'           => trim((string)($post['email']           ?? '')),
        'contacts'        => $contacts,
        'dsgvo_ok'        => (isset($post['dsgvo_ok']) && $post['dsgvo_ok'] === '1') ? '1' : '0',
    ];
    return save_scope_allow_noemail('personal', $data);
}

// -------------------------
// Laden für "Bewerbung laden"
// -------------------------

/**
 * Lädt den JSON-Inhalt (data_json) zu Token + DOB. Gibt assoc-Array oder null.
 */
function load_application_by_token_and_dob(string $token, string $dob_dmy): ?array {
    $dob = (function_exists('norm_date_dmy_to_ymd') ? norm_date_dmy_to_ymd($dob_dmy) : '');
    if ($token === '' || $dob === '') return null;
    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT data_json FROM applications WHERE token = :t AND dob = :d LIMIT 1");
        $stmt->execute([':t'=>$token, ':d'=>$dob]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $json = (string)$row['data_json'];
            $data = json_decode($json, true);
            return is_array($data) ? $data : [];
        }
        return null;
    } catch (Throwable $e) {
        error_log('load_application_by_token_and_dob: '.$e->getMessage());
        return null;
    }
}

/**
 * Optional: Token per E-Mail erneut zusenden (wenn verifiziert).
 * Gibt true zurück, wenn eine Mail versucht/gesendet wurde.
 */
function resend_token_if_verified(string $email, string $dob_dmy): bool {
    if (empty($_SESSION['email_verified'])) return false;
    $dob = (function_exists('norm_date_dmy_to_ymd') ? norm_date_dmy_to_ymd($dob_dmy) : '');
    if ($email === '' || $dob === '') return false;

    try {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT token FROM applications WHERE email = :e AND dob = :d AND email_verified = 1 LIMIT 1");
        $stmt->execute([':e'=>$email, ':d'=>$dob]);
        if ($tok = $stmt->fetchColumn()) {
            if (function_exists('send_verification_email')) {
                // Re-Use Mailer: einfacher Hinweis mit Token
                @send_verification_email($email, "Ihr Zugangstoken: ".$tok);
                return true;
            }
        }
    } catch (Throwable $e) {
        error_log('resend_token_if_verified: '.$e->getMessage());
    }
    return false;
}
