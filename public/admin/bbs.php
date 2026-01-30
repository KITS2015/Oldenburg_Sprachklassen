<?php
declare(strict_types=1);

// Datei: public/admin/bbs.php

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';

require_admin();

$pdo = admin_db();

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$flash = '';
$flashType = 'success';

// Token wird nur einmal angezeigt (nach Create/Rotate)
$showToken = (string)($_SESSION['bbs_new_token'] ?? '');
$showFor   = (string)($_SESSION['bbs_new_token_for'] ?? '');
unset($_SESSION['bbs_new_token'], $_SESSION['bbs_new_token_for']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (!csrf_verify($csrf)) {
        $flash = 'CSRF ungültig.';
        $flashType = 'danger';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'create') {
            $schulnr = trim((string)($_POST['bbs_schulnummer'] ?? ''));
            $bez     = trim((string)($_POST['bbs_bezeichnung'] ?? ''));

            if ($schulnr === '' || $bez === '') {
                $flash = 'Bitte Schulnummer und Bezeichnung ausfüllen.';
                $flashType = 'danger';
            } else {
                // Token generieren (32 bytes -> 64 hex Zeichen)
                $token = bin2hex(random_bytes(32));
                $hash  = hash('sha256', $token);

                try {
                    $st = $pdo->prepare("
                        INSERT INTO bbs (bbs_schulnummer, bbs_bezeichnung, rest_token_hash, is_active)
                        VALUES (?, ?, ?, 1)
                    ");
                    $st->execute([$schulnr, $bez, $hash]);

                    $newId = (string)$pdo->lastInsertId();

                    // Token einmalig anzeigen (Session)
                    $_SESSION['bbs_new_token'] = $token;
                    $_SESSION['bbs_new_token_for'] = $schulnr;

                    header('Location: /admin/bbs.php?created=' . urlencode($newId));
                    exit;
                } catch (Throwable $e) {
                    // z.B. Duplicate schulnummer
                    $flash = 'Konnte BBS nicht anlegen (evtl. Schulnummer bereits vorhanden).';
                    $flashType = 'danger';
                }
            }
        }

        if ($action === 'rotate') {
            $bbsId = (int)($_POST['bbs_id'] ?? 0);
            if ($bbsId <= 0) {
                $flash = 'Ungültige BBS-ID.';
                $flashType = 'danger';
            } else {
                $stGet = $pdo->prepare("SELECT bbs_schulnummer FROM bbs WHERE bbs_id=?");
                $stGet->execute([$bbsId]);
                $schulnr = (string)($stGet->fetchColumn() ?? '');

                if ($schulnr === '') {
                    $flash = 'BBS nicht gefunden.';
                    $flashType = 'danger';
                } else {
                    $token = bin2hex(random_bytes(32));
                    $hash  = hash('sha256', $token);

                    $st = $pdo->prepare("UPDATE bbs SET rest_token_hash=?, updated_at=NOW() WHERE bbs_id=?");
                    $st->execute([$hash, $bbsId]);

                    $_SESSION['bbs_new_token'] = $token;
                    $_SESSION['bbs_new_token_for'] = $schulnr;

                    header('Location: /admin/bbs.php?rotated=' . urlencode((string)$bbsId));
                    exit;
                }
            }
        }

        if ($action === 'toggle') {
            $bbsId = (int)($_POST['bbs_id'] ?? 0);
            $to    = (int)($_POST['to'] ?? 0); // 0 oder 1

            if ($bbsId <= 0 || ($to !== 0 && $to !== 1)) {
                $flash = 'Ungültige Aktion.';
                $flashType = 'danger';
            } else {
                $st = $pdo->prepare("UPDATE bbs SET is_active=?, updated_at=NOW() WHERE bbs_id=?");
                $st->execute([$to, $bbsId]);
                header('Location: /admin/bbs.php');
                exit;
            }
        }
    }
}

// Liste laden
$rows = $pdo->query("
    SELECT bbs_id, bbs_schulnummer, bbs_bezeichnung, is_active, created_at, updated_at,
           (rest_token_hash IS NOT NULL AND rest_token_hash <> '') AS has_token
    FROM bbs
    ORDER BY bbs_bezeichnung ASC
")->fetchAll();

?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Admin – BBS / BoB-Backends</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/admin/admin.css">
</head>
<body class="admin-body admin-body--app">
<div class="container py-4 admin-container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">BBS / BoB-Backends</h1>
            <div class="text-muted">API-Clients anlegen und Tokens verwalten</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="/admin/dashboard.php">← Dashboard</a>
            <a class="btn btn-outline-danger btn-sm" href="/admin/logout.php">Abmelden</a>
        </div>
    </div>

    <?php if ($flash !== ''): ?>
        <div class="alert alert-<?php echo h($flashType); ?>" role="alert">
            <?php echo h($flash); ?>
        </div>
    <?php endif; ?>

    <?php if ($showToken !== ''): ?>
        <div class="alert alert-warning" role="alert">
            <div class="fw-bold mb-1">Neuer REST-Token für BBS <?php echo h($showFor); ?></div>
            <div class="mb-2">Dieser Token wird nur einmal angezeigt. Bitte sicher im BoB-Backend hinterlegen.</div>
            <div class="p-2 bg-light border rounded" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace;">
                <?php echo h($showToken); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Neue BBS anlegen</h2>

            <form method="post" action="/admin/bbs.php" class="row g-2">
                <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                <input type="hidden" name="action" value="create">

                <div class="col-12 col-md-3">
                    <label class="form-label">Schulnummer</label>
                    <input class="form-control" name="bbs_schulnummer" required placeholder="z.B. 12345">
                </div>

                <div class="col-12 col-md-7">
                    <label class="form-label">Bezeichnung</label>
                    <input class="form-control" name="bbs_bezeichnung" required placeholder="z.B. BBS 3 Oldenburg">
                </div>

                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" type="submit">Anlegen + Token</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Schulnummer</th>
                    <th>Bezeichnung</th>
                    <th>Status</th>
                    <th>Token</th>
                    <th>Erstellt</th>
                    <th>Aktualisiert</th>
                    <th class="text-end">Aktionen</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="8" class="text-muted p-3">Noch keine BBS angelegt.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $active = (int)$r['is_active'] === 1;
                        $hasTok = (int)$r['has_token'] === 1;
                        ?>
                        <tr>
                            <td><?php echo (int)$r['bbs_id']; ?></td>
                            <td><?php echo h((string)$r['bbs_schulnummer']); ?></td>
                            <td><?php echo h((string)$r['bbs_bezeichnung']); ?></td>
                            <td>
                                <?php if ($active): ?>
                                    <span class="badge bg-success">aktiv</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">inaktiv</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $hasTok ? '<span class="badge bg-primary">gesetzt</span>' : '<span class="badge bg-warning text-dark">fehlt</span>'; ?>
                            </td>
                            <td><?php echo h((string)$r['created_at']); ?></td>
                            <td><?php echo h((string)$r['updated_at']); ?></td>
                            <td class="text-end">
                                <form method="post" action="/admin/bbs.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                                    <input type="hidden" name="action" value="rotate">
                                    <input type="hidden" name="bbs_id" value="<?php echo (int)$r['bbs_id']; ?>">
                                    <button class="btn btn-outline-primary btn-sm" type="submit">
                                        Token rotieren
                                    </button>
                                </form>

                                <?php if ($active): ?>
                                    <form method="post" action="/admin/bbs.php" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="bbs_id" value="<?php echo (int)$r['bbs_id']; ?>">
                                        <input type="hidden" name="to" value="0">
                                        <button class="btn btn-outline-secondary btn-sm" type="submit">
                                            Deaktivieren
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="/admin/bbs.php" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="bbs_id" value="<?php echo (int)$r['bbs_id']; ?>">
                                        <input type="hidden" name="to" value="1">
                                        <button class="btn btn-outline-success btn-sm" type="submit">
                                            Aktivieren
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
