<?php
declare(strict_types=1);

// Datei: public/admin/applications.php

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';

require_admin();

$pdo = admin_db();

// ---- Helpers ----
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function build_query(array $overrides = []): string
{
    $base = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) unset($base[$k]);
        else $base[$k] = (string)$v;
    }
    return http_build_query($base);
}

function sort_link(string $col): string
{
    $currentSort = (string)($_GET['sort'] ?? 'updated_at');
    $currentDir  = strtolower((string)($_GET['dir'] ?? 'desc'));
    $newDir = 'asc';
    if ($currentSort === $col && $currentDir === 'asc') $newDir = 'desc';
    return '/admin/applications.php?' . build_query(['sort' => $col, 'dir' => $newDir, 'page' => 1]);
}

function sort_indicator(string $col): string
{
    $currentSort = (string)($_GET['sort'] ?? 'updated_at');
    $currentDir  = strtolower((string)($_GET['dir'] ?? 'desc'));
    if ($currentSort !== $col) return '';
    return $currentDir === 'asc' ? ' ▲' : ' ▼';
}

function get_current_admin_user_id(): int
{
    return (int)($_SESSION['admin_user_id'] ?? 0);
}

function admin_has_role(PDO $pdo, int $userId, string $roleKey): bool
{
    if ($userId <= 0) return false;

    try {
        $st = $pdo->prepare("
            SELECT 1
            FROM admin_user_roles aur
            JOIN roles r ON r.id = aur.role_id
            WHERE aur.user_id = ? AND r.role_key = ?
            LIMIT 1
        ");
        $st->execute([$userId, $roleKey]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

// ---- Admin-Override (role admin darf alles) ----
$adminUserId = get_current_admin_user_id();
$isAdminRole = admin_has_role($pdo, $adminUserId, 'admin');
if ($adminUserId <= 0) $isAdminRole = true;

// ---- BBS-Liste + Map ----
$bbsRows = $pdo->query("
    SELECT bbs_id, bbs_kurz, bbs_schulnummer, bbs_bezeichnung
    FROM bbs
    WHERE is_active = 1
    ORDER BY bbs_bezeichnung
")->fetchAll(PDO::FETCH_ASSOC);

$bbsMap = [];
foreach ($bbsRows as $b) {
    $bbsMap[(int)$b['bbs_id']] = (string)$b['bbs_bezeichnung'];
}

// ---- POST: assign / lock / unlock ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        echo "Ungültiger CSRF-Token.";
        exit;
    }

    $action = (string)($_POST['action'] ?? '');
    $appId  = (int)($_POST['app_id'] ?? 0);

    $redirectUrl = '/admin/applications.php';
    $qs = build_query([]);
    if ($qs !== '') $redirectUrl .= '?' . $qs;

    if ($appId <= 0 || !in_array($action, ['assign', 'lock', 'unlock'], true)) {
        header('Location: ' . $redirectUrl);
        exit;
    }

    $st = $pdo->prepare("
        SELECT id, assigned_bbs_id, is_locked, locked_by_bbs_id, locked_at
        FROM applications
        WHERE id = ?
        LIMIT 1
    ");
    $st->execute([$appId]);
    $cur = $st->fetch(PDO::FETCH_ASSOC);

    if (!$cur) {
        header('Location: ' . $redirectUrl);
        exit;
    }

    $assignedBbsId = $cur['assigned_bbs_id'] !== null ? (int)$cur['assigned_bbs_id'] : 0;
    $isLocked      = (int)($cur['is_locked'] ?? 0);

    if ($action === 'assign') {
        $posted = (int)($_POST['assigned_bbs_id'] ?? 0);
        $newBbsId = $posted > 0 ? $posted : null;

        if ($isLocked && !$isAdminRole) {
            header('Location: ' . $redirectUrl);
            exit;
        }

        if ($newBbsId !== null && !isset($bbsMap[(int)$newBbsId])) {
            header('Location: ' . $redirectUrl);
            exit;
        }

        $stUp = $pdo->prepare("
            UPDATE applications
            SET assigned_bbs_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stUp->execute([$newBbsId, $appId]);

        try {
            $meta = json_encode(['assigned_bbs_id' => $newBbsId], JSON_UNESCAPED_UNICODE);
            $stA = $pdo->prepare("INSERT INTO audit_log (application_id, event, meta_json) VALUES (?, 'assigned_bbs_changed', ?)");
            $stA->execute([$appId, $meta]);
        } catch (Throwable $e) {}

        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($action === 'lock') {
        if ($isLocked) { header('Location: ' . $redirectUrl); exit; }
        if ($assignedBbsId <= 0) { header('Location: ' . $redirectUrl); exit; }

        $stUp = $pdo->prepare("
            UPDATE applications
            SET is_locked = 1,
                locked_at = NOW(),
                locked_by_bbs_id = NULL,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stUp->execute([$appId]);

        try {
            $stA = $pdo->prepare("INSERT INTO audit_log (application_id, event, meta_json) VALUES (?, 'locked', NULL)");
            $stA->execute([$appId]);
        } catch (Throwable $e) {}

        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($action === 'unlock') {
        if (!$isLocked) { header('Location: ' . $redirectUrl); exit; }

        $stUp = $pdo->prepare("
            UPDATE applications
            SET is_locked = 0,
                locked_at = NULL,
                locked_by_bbs_id = NULL,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stUp->execute([$appId]);

        try {
            $stA = $pdo->prepare("INSERT INTO audit_log (application_id, event, meta_json) VALUES (?, 'unlocked', NULL)");
            $stA->execute([$appId]);
        } catch (Throwable $e) {}

        header('Location: ' . $redirectUrl);
        exit;
    }

    header('Location: ' . $redirectUrl);
    exit;
}

// ---- Input (Filter / Suche / Paging / Sort) ----
$limit = 25;

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$status = trim((string)($_GET['status'] ?? ''));
$allowedStatus = ['', 'draft', 'submitted', 'withdrawn'];
if (!in_array($status, $allowedStatus, true)) $status = '';

$q = trim((string)($_GET['q'] ?? ''));
$q = mb_substr($q, 0, 200);

$sort = (string)($_GET['sort'] ?? 'updated_at');
$dir  = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

// (Auch wenn wir updated_at/status/etc. nicht anzeigen, dürfen sie fürs Sortieren/Filtern bleiben.)
$sortMap = [
    'id'         => 'a.id',
    'updated_at' => 'a.updated_at',
    'email'      => 'COALESCE(p.email, a.email)',
    'name'       => 'p.name',
    'locked'     => 'a.is_locked',
];
if (!isset($sortMap[$sort])) $sort = 'updated_at';
$orderBy = $sortMap[$sort] . ' ' . strtoupper($dir);

// ---- WHERE ----
$where = [];
$params = [];

if ($status !== '') {
    $where[] = 'a.status = ?';
    $params[] = $status;
}

if ($q !== '') {
    $where[] = '('
        . 'a.email LIKE ? OR '
        . 'COALESCE(p.email, "") LIKE ? OR '
        . 'COALESCE(p.name, "") LIKE ? OR '
        . 'COALESCE(p.vorname, "") LIKE ? OR '
        . 'a.token LIKE ? OR '
        . 'CAST(a.id AS CHAR) LIKE ?'
        . ')';
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---- Count ----
$stCount = $pdo->prepare("
    SELECT COUNT(*)
    FROM applications a
    LEFT JOIN personal p ON p.application_id = a.id
    $whereSql
");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();
$totalPages = (int)max(1, (int)ceil($total / $limit));

if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $limit;

// ---- Data ----
$st = $pdo->prepare("
    SELECT
        a.id,
        a.email AS app_email,
        a.assigned_bbs_id,
        a.is_locked,
        a.locked_by_bbs_id,
        a.locked_at,
        p.name,
        p.vorname,
        p.email AS personal_email
    FROM applications a
    LEFT JOIN personal p ON p.application_id = a.id
    $whereSql
    ORDER BY $orderBy
    LIMIT $limit OFFSET $offset
");
$st->execute($params);
$rows = $st->fetchAll();

// ---- Wichtig: Token + QS nur einmal ----
$csrf = csrf_token();
$qs = build_query([]);
$postAction = '/admin/applications.php' . ($qs !== '' ? ('?' . $qs) : '');

// Spalten: Check + ID + Email + Name + Vorname + (Keine + N*BBS) + Lock  => 7 + N
$colspan = 7 + count($bbsRows);
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Admin – Bewerbungen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/admin/admin.css">

    <style>
        .table-responsive { overflow-x: auto; }
        table.table { width: max-content; min-width: 1200px; } /* kleiner als vorher */
        th, td { white-space: nowrap; }

        th.bbs-col, td.bbs-col {
            text-align: center;
            vertical-align: middle;
            width: 78px; /* schmaler */
        }
        th.bbs-col { font-size: 0.9rem; }
    </style>
</head>
<body class="admin-body admin-body--app">
<div class="container-fluid py-4 admin-container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Bewerbungen</h1>
            <div class="text-muted">Gesamt: <?php echo (int)$total; ?> · Seite <?php echo (int)$page; ?> / <?php echo (int)$totalPages; ?></div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="/admin/dashboard.php">← Dashboard</a>
            <a class="btn btn-outline-danger btn-sm" href="/admin/logout.php">Abmelden</a>
        </div>
    </div>

    <!-- Filter / Suche -->
    <form class="row g-2 align-items-end mb-3" method="get" action="/admin/applications.php">
        <div class="col-12 col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="" <?php echo $status===''?'selected':''; ?>>Alle</option>
                <option value="draft" <?php echo $status==='draft'?'selected':''; ?>>draft</option>
                <option value="submitted" <?php echo $status==='submitted'?'selected':''; ?>>submitted</option>
                <option value="withdrawn" <?php echo $status==='withdrawn'?'selected':''; ?>>withdrawn</option>
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Suche</label>
            <input class="form-control" type="text" name="q" value="<?php echo h($q); ?>" placeholder="E-Mail, Name, Vorname, Token, ID">
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
            <button class="btn btn-primary w-100" type="submit">Anwenden</button>
            <a class="btn btn-outline-secondary w-100" href="/admin/applications.php">Reset</a>
        </div>
    </form>

    <!-- Export Buttons -->
    <div class="d-flex flex-wrap gap-2 mb-2">
        <a class="btn btn-outline-primary btn-sm"
           href="/admin/export_csv.php?mode=all&<?php echo h(build_query(['page' => null])); ?>">
            CSV exportieren (alle Treffer)
        </a>

        <button class="btn btn-outline-primary btn-sm" type="submit" form="selectionForm" name="export_selected" value="1">
            CSV exportieren (ausgewählt)
        </button>
    </div>

    <!-- Form für CSV-Auswahl -->
    <form id="selectionForm" method="post" action="/admin/export_csv.php">
        <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
        <input type="hidden" name="mode" value="selected">
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th style="width:36px;">
                        <input type="checkbox" id="checkAll">
                    </th>
                    <th><a href="<?php echo h(sort_link('id')); ?>">ID<?php echo h(sort_indicator('id')); ?></a></th>
                    <th><a href="<?php echo h(sort_link('email')); ?>">E-Mail<?php echo h(sort_indicator('email')); ?></a></th>
                    <th><a href="<?php echo h(sort_link('name')); ?>">Name<?php echo h(sort_indicator('name')); ?></a></th>
                    <th>Vorname</th>

                    <th class="bbs-col">Keine</th>
                    <?php foreach ($bbsRows as $b): ?>
                        <?php
                        $label = trim((string)($b['bbs_kurz'] ?? ''));
                        if ($label === '') $label = trim((string)($b['bbs_schulnummer'] ?? ''));
                        if ($label === '') $label = (string)($b['bbs_bezeichnung'] ?? '');
                        ?>
                        <th class="bbs-col" title="<?php echo h((string)$b['bbs_bezeichnung']); ?>">
                            <?php echo h($label); ?>
                        </th>
                    <?php endforeach; ?>

                    <th><a href="<?php echo h(sort_link('locked')); ?>">Lock<?php echo h(sort_indicator('locked')); ?></a></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="<?php echo (int)$colspan; ?>" class="text-muted p-3">Keine Datensätze gefunden.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $appId = (int)$r['id'];

                        $email = (string)($r['personal_email'] ?? '');
                        if ($email === '') $email = (string)($r['app_email'] ?? '');

                        $assignedBbsId = $r['assigned_bbs_id'] !== null ? (int)$r['assigned_bbs_id'] : 0;
                        $isLocked = (int)($r['is_locked'] ?? 0);

                        $lockedByLabel = '';
                        if ($isLocked) {
                            if ($r['locked_by_bbs_id'] === null) {
                                $lockedByLabel = 'Admin';
                            } else {
                                $bid = (int)$r['locked_by_bbs_id'];
                                $lockedByLabel = $bbsMap[$bid] ?? ('BBS #' . $bid);
                            }
                        }
                        $lockedAt = $r['locked_at'] ? (string)$r['locked_at'] : '';

                        $disableAssign = ($isLocked && !$isAdminRole) ? 'disabled' : '';

                        $formId = 'assignForm' . $appId;
                        $radioName = 'pick_bbs_' . $appId;
                        ?>
                        <tr>
                            <td>
                                <input class="form-check-input rowCheck"
                                       type="checkbox"
                                       name="ids[]"
                                       value="<?php echo $appId; ?>"
                                       form="selectionForm">
                            </td>

                            <td><?php echo $appId; ?></td>
                            <td><?php echo h($email); ?></td>
                            <td><?php echo h((string)($r['name'] ?? '')); ?></td>
                            <td><?php echo h((string)($r['vorname'] ?? '')); ?></td>

                            <!-- Assign Form (hidden) + Radio "Keine" -->
                            <td class="bbs-col">
                                <form id="<?php echo h($formId); ?>" method="post" action="<?php echo h($postAction); ?>" class="m-0">
                                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                                    <input type="hidden" name="action" value="assign">
                                    <input type="hidden" name="app_id" value="<?php echo $appId; ?>">
                                    <input type="hidden" name="assigned_bbs_id" value="<?php echo (int)$assignedBbsId; ?>">
                                </form>

                                <input type="radio"
                                       name="<?php echo h($radioName); ?>"
                                       value="0"
                                       data-assign-form="<?php echo h($formId); ?>"
                                       <?php echo $assignedBbsId === 0 ? 'checked' : ''; ?>
                                       <?php echo $disableAssign; ?>>
                            </td>

                            <?php foreach ($bbsRows as $b): ?>
                                <?php $bid = (int)$b['bbs_id']; ?>
                                <td class="bbs-col">
                                    <input type="radio"
                                           name="<?php echo h($radioName); ?>"
                                           value="<?php echo $bid; ?>"
                                           data-assign-form="<?php echo h($formId); ?>"
                                           <?php echo $assignedBbsId === $bid ? 'checked' : ''; ?>
                                           <?php echo $disableAssign; ?>>
                                </td>
                            <?php endforeach; ?>

                            <!-- Lock -->
                            <td class="text-nowrap" style="min-width:170px;">
                                <?php if ($isLocked): ?>
                                    <div class="d-flex gap-2 align-items-center">
                                        <span class="badge bg-success">LOCK</span>
                                        <small class="text-muted">
                                            <?php echo h($lockedByLabel); ?>
                                            <?php echo $lockedAt ? ' · ' . h($lockedAt) : ''; ?>
                                        </small>
                                        <form method="post" action="<?php echo h($postAction); ?>" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                                            <input type="hidden" name="action" value="unlock">
                                            <input type="hidden" name="app_id" value="<?php echo $appId; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Unlock</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <form method="post" action="<?php echo h($postAction); ?>" class="d-flex gap-2 align-items-center m-0">
                                        <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                                        <input type="hidden" name="action" value="lock">
                                        <input type="hidden" name="app_id" value="<?php echo $appId; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success" <?php echo $assignedBbsId > 0 ? '' : 'disabled'; ?>>
                                            Lock
                                        </button>
                                        <?php if ($assignedBbsId <= 0): ?>
                                            <small class="text-muted">erst zuweisen</small>
                                        <?php endif; ?>
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

    <!-- Pagination -->
    <nav class="mt-3">
        <ul class="pagination pagination-sm">
            <?php
            $prev = max(1, $page - 1);
            $next = min($totalPages, $page + 1);

            $prevUrl = '/admin/applications.php?' . h(build_query(['page' => $prev]));
            $nextUrl = '/admin/applications.php?' . h(build_query(['page' => $next]));
            ?>
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo $prevUrl; ?>">«</a>
            </li>

            <?php
            $start = max(1, $page - 3);
            $end   = min($totalPages, $page + 3);
            for ($p = $start; $p <= $end; $p++):
                $url = '/admin/applications.php?' . h(build_query(['page' => $p]));
            ?>
                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo $url; ?>"><?php echo (int)$p; ?></a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo $nextUrl; ?>">»</a>
            </li>
        </ul>
    </nav>

</div>

<script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    const checkAll = document.getElementById('checkAll');
    const rowChecks = document.querySelectorAll('.rowCheck');

    if (checkAll) {
        checkAll.addEventListener('change', () => {
            rowChecks.forEach(cb => cb.checked = checkAll.checked);
        });
    }

    // Auto-Save: Radio -> Hidden im Form setzen -> submit
    document.querySelectorAll('input[data-assign-form]').forEach(el => {
        el.addEventListener('change', () => {
            const formId = el.getAttribute('data-assign-form');
            const form = document.getElementById(formId);
            if (!form) return;

            const hidden = form.querySelector('input[name="assigned_bbs_id"]');
            if (!hidden) return;

            hidden.value = el.value;
            form.submit();
        });
    });
</script>
</body>
</html>
