<?php
declare(strict_types=1);

// Datei: public/api/bob/assignment.php

require_once __DIR__ . '/../../../app/config.php';

date_default_timezone_set('Europe/Berlin');

// DB
$dsn = 'mysql:host=127.0.0.1;dbname=' . APP_DB_NAME . ';port=3306;charset=utf8mb4';
$pdo = pdo($dsn, APP_DB_USER, APP_DB_PASS);

function json_out(int $code, array $data): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bearer_token(): string
{
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($hdr === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $hdr = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', $hdr, $m)) {
        return trim($m[1]);
    }
    return '';
}

function require_bob(PDO $pdo): array
{
    $token = bearer_token();
    if ($token === '') {
        json_out(401, ['ok' => false, 'error' => 'missing_bearer_token']);
    }
    $hash = hash('sha256', $token);

    $st = $pdo->prepare("
        SELECT bbs_id, bbs_schulnummer, bbs_bezeichnung
        FROM bbs
        WHERE is_active = 1 AND rest_token_hash = ?
        LIMIT 1
    ");
    $st->execute([$hash]);
    $bbs = $st->fetch(PDO::FETCH_ASSOC);

    if (!$bbs) {
        json_out(401, ['ok' => false, 'error' => 'invalid_token']);
    }
    return $bbs;
}

$bbs = require_bob($pdo);
$myBbsId = (int)$bbs['bbs_id'];

$action = isset($_POST['action']) ? (string)$_POST['action'] : '';
$appId  = isset($_POST['app_id']) ? (int)$_POST['app_id'] : 0;

if (!in_array($action, ['assign', 'lock', 'unlock'], true) || $appId <= 0) {
    json_out(400, ['ok' => false, 'error' => 'bad_request']);
}

try {
    $pdo->beginTransaction();

    // Row lock, um Race-Conditions zu vermeiden
    $st = $pdo->prepare("
        SELECT id, assigned_bbs_id, locked_by_bbs_id, locked_at
        FROM applications
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $st->execute([$appId]);
    $cur = $st->fetch(PDO::FETCH_ASSOC);

    if (!$cur) {
        $pdo->rollBack();
        json_out(404, ['ok' => false, 'error' => 'not_found']);
    }

    $assigned = ($cur['assigned_bbs_id'] !== null) ? (int)$cur['assigned_bbs_id'] : 0;
    $lockedBy = ($cur['locked_by_bbs_id'] !== null) ? (int)$cur['locked_by_bbs_id'] : 0;

    $isLocked = ($lockedBy > 0);
    $lockedByMe = ($lockedBy === $myBbsId);

    // Wenn gelockt durch andere -> alles blocken
    if ($isLocked && !$lockedByMe) {
        $pdo->rollBack();
        json_out(409, ['ok' => false, 'error' => 'locked_by_other']);
    }

    if ($action === 'assign') {
        $posted = isset($_POST['assigned_bbs_id']) ? (int)$_POST['assigned_bbs_id'] : 0;

        // Erlaubt: 0 (NULL) oder eigene BBS
        if ($posted !== 0 && $posted !== $myBbsId) {
            $pdo->rollBack();
            json_out(403, ['ok' => false, 'error' => 'assign_not_allowed']);
        }

        $newVal = ($posted === 0) ? null : $myBbsId;

        // WICHTIG: Wenn auf "Keine" gestellt wird, muss ein ggf. gesetzter Lock (von mir) mit entfernt werden.
        if ($newVal === null) {
            $stUp = $pdo->prepare("
                UPDATE applications
                SET assigned_bbs_id = NULL,
                    locked_by_bbs_id = NULL,
                    locked_at = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stUp->execute([$appId]);

            try {
                $meta = json_encode(['assigned_bbs_id' => null, 'unlock' => true], JSON_UNESCAPED_UNICODE);
                $pdo->prepare("INSERT INTO audit_log (application_id, event, meta_json) VALUES (?, 'assigned_bbs_cleared', ?)")
                    ->execute([$appId, $meta]);
            } catch (Throwable $e) {
                // ignore
            }

            $pdo->commit();
            json_out(200, ['ok' => true, 'assigned_bbs_id' => null, 'unlocked' => true]);
        }

        // Zuweisung auf eigene BBS
        $stUp = $pdo->prepare("
            UPDATE applications
            SET assigned_bbs_id = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stUp->execute([$newVal, $appId]);

        try {
            $meta = json_encode(['assigned_bbs_id' => $newVal], JSON_UNESCAPED_UNICODE);
            $pdo->prepare("INSERT INTO audit_log (application_id, event, meta_json) VALUES (?, 'assigned_bbs_changed', ?)")
                ->execute([$appId, $meta]);
        } catch (Throwable $e) {
            // ignore
        }

        $pdo->commit();
        json_out(200, ['ok' => true, 'assigned_bbs_id' => (int)$newVal]);
    }

    if ($action === 'lock') {
        // Lock nur wenn assigned == eigene BBS
        if ($assigned !== $myBbsId) {
            $pdo->rollBack();
            json_out(409, ['ok' => false, 'error' => 'not_assigned_to_you']);
        }

        // Wenn schon gelockt (von mir), ist das idempotent ok
        if ($isLocked && $lockedByMe) {
            $pdo->commit();
            json_out(200, ['ok' => true, 'already_locked' => true]);
        }

        $stUp = $pdo->prepare("
            UPDATE applications
            SET locked_by_bbs_id = ?,
                locked_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stUp->execute([$myBbsId, $appId]);

        try {
            $meta = json_encode(['locked_by_bbs_id' => $myBbsId], JSON_UNESCAPED_UNICODE);
            $pdo->prepare("INSERT INTO audit_log (application_id, event, meta_json) VALUES (?, 'locked', ?)")
                ->execute([$appId, $meta]);
        } catch (Throwable $e) {
            // ignore
        }

        $pdo->commit();
        json_out(200, ['ok' => true]);
    }

    if ($action === 'unlock') {
        if (!$lockedByMe) {
            $pdo->rollBack();
            json_out(403, ['ok' => false, 'error' => 'not_lock_owner']);
        }

        $stUp = $pdo->prepare("
            UPDATE applications
            SET locked_by_bbs_id = NULL,
                locked_at = NULL,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stUp->execute([$appId]);

        try {
            $pdo->prepare("INSERT INTO audit_log (application_id, event, meta_json) VALUES (?, 'unlocked', NULL)")
                ->execute([$appId]);
        } catch (Throwable $e) {
            // ignore
        }

        $pdo->commit();
        json_out(200, ['ok' => true]);
    }

    $pdo->rollBack();
    json_out(400, ['ok' => false, 'error' => 'bad_request']);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_out(500, ['ok' => false, 'error' => 'server_error']);
}
