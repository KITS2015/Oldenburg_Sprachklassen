<?php
// public/wizard/_common.php
declare(strict_types=1);

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Berlin');

// --- Sessions ---
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- Projektpfade ---
define('APP_BASE', realpath(__DIR__ . '/../../') ?: __DIR__ . '/../../');         // /var/www/oldenburg.anmeldung.schule
define('APP_PUBLIC', APP_BASE . '/public');                                       // /.../public
define('APP_UPLOADS', APP_BASE . '/uploads');                                     // /.../uploads
define('APP_APPDIR', APP_BASE . '/app');                                          // /.../app

// --- Upload-Verzeichnis sicherstellen ---
if (!is_dir(APP_UPLOADS)) {
    @mkdir(APP_UPLOADS, 0775, true);
}
if (!is_writable(APP_UPLOADS)) {
    // kein harter Abbruch – Formulare können eine Fehlermeldung setzen
}

// --- Externe App-Helfer (wenn vorhanden) ---
$__db_path = APP_APPDIR . '/db.php';
if (is_file($__db_path)) {
    require_once $__db_path; // muss eine Funktion db(): PDO bereitstellen
}
$__mail_path = APP_APPDIR . '/email.php';
if (is_file($__mail_path)) {
    require_once $__mail_path; // optional: send_verification_email(...)
}

// --- Domänenspezifische Auswahllisten (können später in DB wandern) ---
$SCHULEN = [
    ''            => 'Bitte wählen …',
    'BBS_1'       => 'BBS 1',
    'BBS_2'       => 'BBS 2',
    'BBS_3'       => 'BBS 3',
    'IGS_FALLBACK'=> 'Andere Schule in Oldenburg',
];
$GERMAN_LEVELS = ['A0','A1','A2','B1'];
$INTERESSEN = [
    'wirtschaft'  => 'Wirtschaft',
    'handwerk'    => 'Handwerk (Holz, Metall)',
    'sozial'      => 'Soziales / Erzieher*in / Sozialassistent*in',
    'gesundheit'  => 'Gesundheit / Pflege / Medizin',
    'garten'      => 'Garten / Landwirtschaft',
    'kochen'      => 'Kochen / Hauswirtschaft / Gastronomie / Hotelfach',
    'friseur'     => 'Friseur / Friseurin',
    'design'      => 'Design',
    'verwaltung'  => 'Verwaltung',
];

// =====================
//        Utils
// =====================
if (!function_exists('h')) {
    function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('old')) {
    // liest zuerst aus Session-Schritt, dann POST – schützt Ausgabe
    function old(string $key, string $scope = ''): string {
        if ($scope !== '' && isset($_SESSION['form'][$scope][$key])) {
            return h((string)$_SESSION['form'][$scope][$key]);
        }
        return h((string)($_POST[$key] ?? ''));
    }
}
if (!function_exists('has_err')) {
    function has_err(string $key, array $errors): string {
        return isset($errors[$key]) ? ' is-invalid' : '';
    }
}

// =====================
//       CSRF
// =====================
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
if (!function_exists('csrf_field')) {
    function csrf_field(): void {
        echo '<input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">';
    }
}
if (!function_exists('csrf_check')) {
    function csrf_check(): bool {
        return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    }
}

// =====================
//   Schritt-Absicherung
// =====================
if (!function_exists('require_step')) {
    /**
     * Sicherstellen, dass die vorherigen Schritte ausgefüllt sind.
     * Reihenfolge: personal -> school -> upload -> review
     */
    function require_step(string $stepKey): void {
        $order = ['personal','school','upload','review'];
        $idx = array_search($stepKey, $order, true);
        if ($idx === false) return;
        for ($i = 0; $i < $idx; $i++) {
            $need = $order[$i];
            if (empty($_SESSION['form'][$need])) {
                header('Location: /form_' . $need . '.php');
                exit;
            }
        }
    }
}

// =====================
//     Token / Access
// =====================
/**
 * Gibt aktuellen Access-Token aus der Session zurück (oder '').
 */
if (!function_exists('current_access_token')) {
    function current_access_token(): string {
        return (string)($_SESSION['access_token'] ?? '');
    }
}

/**
 * Erzeugt einen neuen Access-Token und legt ihn in der Session ab.
 * Gibt den Token zurück.
 */
if (!function_exists('issue_access_token')) {
    function issue_access_token(): string {
        $token = bin2hex(random_bytes(16));
        $_SESSION['access_token'] = $token;
        return $token;
    }
}

/**
 * Einfache Ausgabezeile für den Access-Token im Formularkopf.
 */
if (!function_exists('render_access_token_badge')) {
    function render_access_token_badge(): void {
        $tok = current_access_token();
        if ($tok !== '') {
            echo '<div class="mb-2 small text-muted">Access Token: <code>'.h($tok).'</code></div>';
        }
    }
}

/**
 * Hilfsfunktion: Datum (TT.MM.JJJJ) -> 'YYYY-MM-DD' oder ''.
 */
if (!function_exists('norm_date_dmy_to_ymd')) {
    function norm_date_dmy_to_ymd(string $dmy): string {
        if (!preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $dmy, $m)) return '';
        [$all,$d,$mth,$y] = $m;
        if (!checkdate((int)$mth,(int)$d,(int)$y)) return '';
        return sprintf('%04d-%02d-%02d', $y, $mth, $d);
    }
}

// ... in public/wizard/_common.php (nach den bestehenden Utils/Token-Funktionen) ...

/**
 * Upsert nur mit Token + DOB (ohne E-Mail). Email bleibt NULL/leer.
 * $payload wird in applications.data_json unter form.{scope} gemerged.
 */
function ensure_record_token_and_dob_only(string $dob_dmy, array $payload = []): array {
    $dob = norm_date_dmy_to_ymd($dob_dmy);
    if ($dob === '') return ['ok'=>false,'token'=>current_access_token(),'err'=>'Geburtsdatum ungültig'];

    $token = current_access_token();
    if ($token === '') $token = issue_access_token();

    if (!function_exists('db')) {
        return ['ok'=>false,'token'=>$token,'err'=>'DB nicht verfügbar'];
    }

    try {
        $pdo = db();
        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare("SELECT data_json FROM applications WHERE token = :t LIMIT 1");
        $stmt->execute([':t'=>$token]);
        $merged = $payload;

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $prev = json_decode((string)$row['data_json'], true);
            if (is_array($prev)) $merged = array_replace_recursive($prev, $payload);
            $u = $pdo->prepare("UPDATE applications
                                SET dob = :d, data_json = :j, updated_at = :u
                                WHERE token = :t");
            $u->execute([':d'=>$dob, ':j'=>json_encode($merged, JSON_UNESCAPED_UNICODE), ':u'=>$now, ':t'=>$token]);
        } else {
            $i = $pdo->prepare("INSERT INTO applications (token, email, dob, data_json, created_at, updated_at, email_verified)
                                VALUES (:t, NULL, :d, :j, :c, :u, 0)");
            $i->execute([
                ':t'=>$token, ':d'=>$dob, ':j'=>json_encode($merged, JSON_UNESCAPED_UNICODE),
                ':c'=>$now, ':u'=>$now
            ]);
        }
        return ['ok'=>true,'token'=>$token,'err'=>''];
    } catch (Throwable $e) {
        error_log('ensure_record_token_and_dob_only: '.$e->getMessage());
        return ['ok'=>false,'token'=>$token,'err'=>'DB-Fehler'];
    }
}

/**
 * Komfort-Speicherfunktion: 
 * - Falls verifizierte E-Mail + DOB vorhanden → speichert wie bisher (ensure_record_with_token).
 * - Sonst, wenn **DOB vorhanden** → speichert mit Token+DOB (ohne E-Mail).
 * - Sonst nur Session speichern.
 */
function save_scope_allow_noemail(string $scope, array $data): array {
    $_SESSION['form'][$scope] = $data;

    $email = (string)($_SESSION['form']['personal']['email'] ?? '');
    $dob   = (string)($_SESSION['form']['personal']['geburtsdatum'] ?? '');

    if (!empty($_SESSION['email_verified']) && $email !== '' && $dob !== '') {
        $payload = ['form' => [$scope => $data]];
        return ensure_record_with_token($email, $dob, $payload);
    }

    if ($dob !== '') {
        $payload = ['form' => [$scope => $data]];
        return ensure_record_token_and_dob_only($dob, $payload);
    }

    // Noch kein DOB → nur Session
    return ['ok'=>false,'token'=>current_access_token(),'err'=>'nur Session (DOB fehlt)'];
}

/**
 * Legt (sofort) einen Datensatz an/updated ihn, sobald verifizierte E-Mail und Geburtsdatum vorliegen.
 * - Erwartet: $email (verifiziert via Session-Flag), $geburtsdatum_dmy (TT.MM.JJJJ)
 * - Erzeugt bei Bedarf Token und speichert/aktualisiert in Tabelle `applications`.
 * - $payload ist ein assoziatives Array (z. B. Teilformulare), das als JSON gesichert wird.
 *
 * Rückgabe: ['ok'=>bool, 'token'=>string, 'err'=>string]
 */
if (!function_exists('ensure_record_with_token')) {
    function ensure_record_with_token(string $email, string $geburtsdatum_dmy, array $payload = []): array {
        // Voraussetzung: Mail verifiziert
        if (empty($_SESSION['email_verified'])) {
            return ['ok'=>false,'token'=>'','err'=>'E-Mail nicht verifiziert'];
        }
        $dob = norm_date_dmy_to_ymd($geburtsdatum_dmy);
        if ($dob === '') {
            return ['ok'=>false,'token'=>'','err'=>'Geburtsdatum ungültig'];
        }

        // Token aus Session oder neu
        $token = current_access_token();
        if ($token === '') {
            $token = issue_access_token();
        }

        // DB verfügbar?
        if (!function_exists('db')) {
            return ['ok'=>false,'token'=>$token,'err'=>'DB nicht verfügbar'];
        }

        try {
            $pdo = db();
            // upsert (MariaDB 10.5+): email+dob sind nicht zwingend unique – wir nehmen token als PK, und falls token existiert -> update
            $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

            // Payload zusammenführen (bestehendes JSON mergen)
            $stmt = $pdo->prepare("SELECT data_json FROM applications WHERE token = :t LIMIT 1");
            $stmt->execute([':t'=>$token]);
            $merged = $payload;
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $prev = json_decode((string)$row['data_json'], true);
                if (is_array($prev)) {
                    $merged = array_replace_recursive($prev, $payload);
                }
                $u = $pdo->prepare("UPDATE applications
                                    SET email = :e, dob = :d, data_json = :j, updated_at = :u
                                    WHERE token = :t");
                $u->execute([
                    ':e'=>$email, ':d'=>$dob, ':j'=>json_encode($merged, JSON_UNESCAPED_UNICODE),
                    ':u'=>$now, ':t'=>$token
                ]);
            } else {
                $i = $pdo->prepare("INSERT INTO applications (token, email, dob, data_json, created_at, updated_at, email_verified)
                                    VALUES (:t, :e, :d, :j, :c, :u, :v)");
                $i->execute([
                    ':t'=>$token, ':e'=>$email, ':d'=>$dob, ':j'=>json_encode($merged, JSON_UNESCAPED_UNICODE),
                    ':c'=>$now, ':u'=>$now, ':v'=>(int)!empty($_SESSION['email_verified'])
                ]);
            }
            return ['ok'=>true,'token'=>$token,'err'=>''];
        } catch (Throwable $e) {
            error_log('ensure_record_with_token error: '.$e->getMessage());
            return ['ok'=>false,'token'=>$token,'err'=>'DB-Fehler'];
        }
    }
}

/**
 * Komfort: Speichert einen Teil (Scope) im JSON-Feld `data_json` unter `form.{scope}`.
 * Ruft intern ensure_record_with_token() auf, wenn E-Mail verifiziert + DOB vorhanden ist.
 */
if (!function_exists('save_scope_if_possible')) {
    function save_scope_if_possible(string $scope, array $data, ?string $email = null, ?string $dob_dmy = null): array {
        // In Session vormerken
        $_SESSION['form'][$scope] = $data;

        $email  = $email   ?? (string)($_SESSION['form']['personal']['email'] ?? '');
        $dob    = $dob_dmy ?? (string)($_SESSION['form']['personal']['geburtsdatum'] ?? '');

        // Nur speichern, wenn verifizierte E-Mail + DOB gesetzt
        if (empty($_SESSION['email_verified']) || $email === '' || $dob === '') {
            return ['ok'=>false,'token'=>current_access_token(),'err'=>'noch keine verifizierte E-Mail oder Geburtsdatum'];
        }
        $payload = ['form' => [$scope => $data]];
        return ensure_record_with_token($email, $dob, $payload);
    }
}

// =====================
//   Ansichtshilfen
// =====================
/**
 * Ein kleines, neutrales Flash-System (optional verwendbar)
 */
if (!function_exists('flash_set')) {
    function flash_set(string $type, string $message): void {
        $_SESSION['flash'][] = ['type'=>$type, 'msg'=>$message];
    }
}
if (!function_exists('flash_render')) {
    function flash_render(): void {
        if (empty($_SESSION['flash'])) return;
        foreach ($_SESSION['flash'] as $f) {
            $type = h($f['type'] ?? 'info');
            $msg  = h($f['msg'] ?? '');
            echo '<div class="alert alert-'.$type.'">'.$msg.'</div>';
        }
        unset($_SESSION['flash']);
    }
}
