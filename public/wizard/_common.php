<?php
// public/wizard/_common.php
// Basis-Helfer für alle Wizard-Seiten (keine DB-Logik!)
// UTF-8, no BOM
declare(strict_types=1);

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Berlin');

// -------------------------
// Sessions
// -------------------------
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

// -------------------------
// Sprache (zentral)
// -------------------------
if (!function_exists('available_languages')) {
    function available_languages(): array {
        return [
            'de' => 'Deutsch',
            'en' => 'English',
            'fr' => 'Français',
            'uk' => 'Українська',
            'ar' => 'العربية',
            'ru' => 'Русский',
            'tr' => 'Türkçe',
            'fa' => 'فارسی',
        ];
    }
}

if (!function_exists('is_rtl_lang')) {
    function is_rtl_lang(string $lang): bool {
        return in_array($lang, ['ar','fa'], true);
    }
}

/**
 * Sprache ermitteln: Session > GET > Cookie > Browser > de
 * und Session/Cookie konsistent halten.
 *
 * Hinweis: GET wird hier akzeptiert, damit lang-Wechsel auch im Wizard möglich ist,
 * wenn du später z.B. ?lang=fr an Links hängen willst.
 */
if (!function_exists('current_lang')) {
    function current_lang(): string {
        $langs = available_languages();

        // 1) Session
        $lang = strtolower((string)($_SESSION['lang'] ?? ''));

        // 2) GET (optional)
        if ($lang === '' && isset($_GET['lang'])) {
            $lang = strtolower((string)$_GET['lang']);
        }

        // 3) Cookie
        if ($lang === '') {
            $lang = strtolower((string)($_COOKIE['lang'] ?? ''));
        }

        // 4) Browser Accept-Language
        if ($lang === '' || !array_key_exists($lang, $langs)) {
            $accept = strtolower((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
            foreach ($langs as $code => $_label) {
                if ($code !== '' && strpos($accept, $code) !== false) {
                    $lang = $code;
                    break;
                }
            }
        }

        if ($lang === '' || !array_key_exists($lang, $langs)) {
            $lang = 'de';
        }

        // Session setzen
        $_SESSION['lang'] = $lang;

        // Cookie nachziehen (damit es überall gilt)
        if (!isset($_COOKIE['lang']) || (string)$_COOKIE['lang'] !== $lang) {
            setcookie('lang', $lang, time() + 60*60*24*365, '/');
        }

        return $lang;
    }
}

// Initialisiere Sprache früh (damit alle Seiten gleich reagieren)
current_lang();

// -------------------------
// Projektpfade
// -------------------------
define('APP_BASE',   realpath(__DIR__ . '/../../') ?: __DIR__ . '/../../');
define('APP_PUBLIC', APP_BASE . '/public');
define('APP_UPLOADS',APP_BASE . '/uploads');
define('APP_APPDIR', APP_BASE . '/app');

// Upload-Verzeichnis sicherstellen
if (!is_dir(APP_UPLOADS)) {
    @mkdir(APP_UPLOADS, 0775, true);
}

// Optionale App-Helfer laden (DB, Mail) – hier nur verfügbar machen.
// Die eigentliche DB-Logik liegt NICHT in dieser Datei.
$__db_path   = APP_APPDIR . '/db.php';
$__mail_path = APP_APPDIR . '/email.php';
if (is_file($__db_path))   require_once $__db_path;
if (is_file($__mail_path)) require_once $__mail_path;

// -------------------------
// Domänenspezifische Auswahllisten
// -------------------------
$SCHULEN = [
    ''             => 'Bitte wählen …',
    'BBS_1'        => 'BBS 1',
    'BBS_2'        => 'BBS 2',
    'BBS_3'        => 'BBS 3',
    'IGS_FALLBACK' => 'Andere Schule in Oldenburg',
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

// -------------------------
// Utils (UI-Helfer, KEINE DB!)
// -------------------------
if (!function_exists('h')) {
    function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('old')) {
    // liest zuerst aus Session-Schritt, dann POST – Ausgabe ist escaped
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

// -------------------------
// CSRF
// -------------------------
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

// -------------------------
// Schritt-Absicherung
// -------------------------
if (!function_exists('require_step')) {
    /**
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

// -------------------------
// Token / Access (Session)
// -------------------------
if (!function_exists('current_access_token')) {
    function current_access_token(): string {
        return (string)($_SESSION['access_token'] ?? '');
    }
}
if (!function_exists('issue_access_token')) {
    function issue_access_token(): string {
        $token = bin2hex(random_bytes(16)); // 32 Hex
        $_SESSION['access_token'] = $token;
        return $token;
    }
}
if (!function_exists('render_access_token_badge')) {
    function render_access_token_badge(): void {
        $tok = current_access_token();
        if ($tok !== '') {
            echo '<div class="mb-2 small text-muted">Access Token: <code>'.h($tok).'</code></div>';
        }
    }
}

// -------------------------
// Datums-Helfer
// -------------------------
if (!function_exists('norm_date_dmy_to_ymd')) {
    // 'TT.MM.JJJJ' -> 'YYYY-MM-DD' oder ''
    function norm_date_dmy_to_ymd(string $dmy): string {
        if (!preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $dmy, $m)) return '';
        [, $d, $mth, $y] = $m;
        if (!checkdate((int)$mth,(int)$d,(int)$y)) return '';
        return sprintf('%04d-%02d-%02d', (int)$y, (int)$mth, (int)$d);
    }
}

// -------------------------
// Flash (optional)
// -------------------------
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
