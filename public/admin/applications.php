<?php
declare(strict_types=1);

// Datei: public/admin/applications.php

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/helpers.php';

require_admin();

$pdo = admin_db();

/** BBS laden (robust gegen fehlende Spalte bbs_kurz in Alt-DBs) */
try {
    $bbsRows = $pdo->query("
        SELECT bbs_id, bbs_kurz, bbs_schulnummer, bbs_bezeichnung
        FROM bbs
        WHERE is_active = 1
        ORDER BY bbs_bezeichnung
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $bbsRows = $pdo->query("
        SELECT bbs_id, bbs_schulnummer, bbs_bezeichnung
        FROM bbs
        WHERE is_active = 1
        ORDER BY bbs_bezeichnung
    ")->fetchAll(PDO::FETCH_ASSOC);
}

$bbsMap = [];
foreach ($bbsRows as $b) {
    $bbsMap[(int)$b['bbs_id']] = (string)($b['bbs_bezeichnung'] ?? '');
}

/** POST: assign / lock / unlock / delete_selected (Admin hat immer volle Rechte) */
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

    // --- NEU: Bulk-Delete (ausgewählt) ---
    if ($action === 'delete_selected') {
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) $ids = [];

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, function($v){ return $v > 0; });

        if (!$ids) {
            header('Location: ' . $redirectUrl);
            exit;
        }

        // Transaktion: erst Children, dann applications
        $pdo->beginTransaction();
        try {
            $ph = implode(',', array_fill(0, count($ids), '?'));

            // Child-Tabellen (je nach DB-Design)
            $pdo->prepare("DELETE FROM uploads  WHERE application_id IN ($ph)")->execute($ids);
            $pdo->prepare("DELETE FROM contacts WHERE application_id IN ($ph)")->execute($ids);
            $pdo->prepare("DELETE FROM school   WHERE application_id IN ($ph)")->execute($ids);
            $pdo->prepare("DELETE FROM personal WHERE application_id IN ($ph)")->execute($ids);

            // Audit (optional, falls FK existiert)
            try {
                $pdo->prepare("DELETE FROM audit_log WHERE application_id IN ($ph)")->execute($ids);
            } catch (Throwable $e) {}

            // Parent
            $pdo->prepare("DELETE FROM applications WHERE id IN ($ph)")->execute($ids);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            // Optional: Fehler als Flash o.ä. – hier simpel:
            http_response_code(500);
            echo "Löschen fehlgeschlagen: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            exit;
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($appId <= 0 || !in_array($action, ['assign', 'lock', 'unlock'], true)) {
        header('Location: ' . $redirectUrl);
        exit;
    }

    $st = $pdo->prepare("
        SELECT id, assigned_bbs_id, locked_by_bbs_id, locked_at
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
    $lockedByBbsId = $cur['locked_by_bbs_id'] !== null ? (int)$cur['locked_by_bbs_id'] : 0;
    $isLocked      = ($lockedByBbsId > 0);

    if ($action === 'assign') {
        $posted = (int)($_POST['assigned_bbs_id'] ?? 0);
        $newBbsId = $posted > 0 ? $posted : null;

        if ($newBbsId !== null && !isset($bbsMap[(int)$newBbsId])) {
            header('Location: ' . $redirectUrl);
            exit;
        }

        // assigned_bbs_id setzen
        $stUp = $pdo->prepare("
            UPDATE applications
            SET assigned_bbs_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stUp->execute([$newBbsId, $appId]);

        // Wenn die Bewerbung gelockt ist "im Namen" der bisherigen Zuweisung (Admin-Lock),
        // dann Lock sauber mitziehen (oder lösen bei Zuweisung = NULL)
        if ($isLocked && $assignedBbsId > 0 && $lockedByBbsId === $assignedBbsId) {
            if ($newBbsId === null) {
                $pdo->prepare("
                    UPDATE applications
                    SET locked_by_bbs_id = NULL,
                        locked_at = NULL,
                        updated_at = NOW()
                    WHERE id = ?
                ")->execute([$appId]);
            } else {
                $pdo->prepare("
                    UPDATE applications
                    SET locked_by_bbs_id = ?,
                        locked_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ?
                ")->execute([(int)$newBbsId, $appId]);
            }
        }

        try {
            $meta = json_encode(['assigned_bbs_id' => $newBbsId], JSON_UNESCAPED_UNICODE);
            $stA = $pdo->prepare("INSERT INTO audit_log (application_id, event, meta_json) VALUES (?, 'assigned_bbs_changed', ?)");
            $stA->execute([$appId, $meta]);
        } catch (Throwable $e) {}

        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($action === 'lock') {
        // Lock nur möglich, wenn zugewiesen
        if ($assignedBbsId <= 0) {
            header('Location: ' . $redirectUrl);
            exit;
        }

        // Admin-Lock: locked_by_bbs_id = assigned_bbs_id
        $stUp = $pdo->prepare("
            UPDATE applications
            SET locked_by_bbs_id = ?,
                locked_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stUp->execute([$assignedBbsId, $appId]);

        try {
            $meta = json_encode(['locked_by_bbs_id' => $assignedBbsId], JSON_UNESCAPED_UNICODE);
            $stA = $pdo->prepare("INSERT INTO audit_log (application_id, event, meta_json) VALUES (?, 'locked', ?)");
            $stA->execute([$appId, $meta]);
        } catch (Throwable $e) {}

        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($action === 'unlock') {
        // Admin-Override: immer entsperren
        $stUp = $pdo->prepare("
            UPDATE applications
            SET locked_by_bbs_id = NULL,
                locked_at = NULL,
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

/** Filter / Suche / Paging */
$limit = 25;

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$status = trim((string)($_GET['status'] ?? ''));
$allowedStatus = ['', 'draft', 'submitted', 'withdrawn'];
if (!in_array($status, $allowedStatus, true)) $status = '';

$q = trim((string)($_GET['q'] ?? ''));
$q = mb_substr($q, 0, 200);

$sort = (string)($_GET['sort'] ?? 'id');
$dir  = strtolower((string)($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

// locked sort: nach "hat locked_by_bbs_id" + optional timestamp
$sortMap = [
    'id'     => 'a.id',
    'name'   => 'p.name',
    'locked' => '(a.locked_by_bbs_id IS NOT NULL) ',
];

if (!isset($sortMap[$sort])) $sort = 'id';
$orderBy = $sortMap[$sort] . ' ' . strtoupper($dir) . ', a.id ' . strtoupper($dir);

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

$st = $pdo->prepare("
    SELECT
        a.id,
        a.status,
        a.assigned_bbs_id,
        a.locked_by_bbs_id,
        a.locked_at,
        p.name,
        p.vorname
    FROM applications a
    LEFT JOIN personal p ON p.application_id = a.id
    $whereSql
    ORDER BY $orderBy
    LIMIT $limit OFFSET $offset
");
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$csrf = csrf_token();
$qs = build_query([]);
$postAction = '/admin/applications.php' . ($qs !== '' ? ('?' . $qs) : '');

$pageTitle = 'Admin – Bewerbungen';
$activeNav = 'applications';
require_once __DIR__ . '/inc/header.php';

/** colspan: Checkbox + ID + Name + Vorname + (Keine + N BBS) + Lock = 6 + N */
$colspan = 6 + count($bbsRows);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1">Bewerbungen</h1>
        <div class="text-muted">Gesamt: <?php echo (int)$total; ?> · Seite <?php echo (int)$page; ?> / <?php echo (int)$totalPages; ?></div>
    </div>
</div>

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
        <input class="form-control" type="text" name="q" value="<?php echo h($q); ?>" placeholder="Name, Vorname, E-Mail, Token, ID">
    </div>
    <div class="col-12 col-md-3 d-flex gap-2">
        <button class="btn btn-primary w-100" type="submit">Anwenden</button>
        <a class="btn btn-outline-secondary w-100" href="/admin/applications.php">Reset</a>
    </div>
</form>

<div class="d-flex flex-wrap gap-2 mb-2">
    <a class="btn btn-outline-primary btn-sm"
       href="/admin/export_csv.php?mode=all&<?php echo h(build_query(['page' => null])); ?>">
        CSV exportieren (alle Treffer)
    </a>

    <button class="btn btn-outline-primary btn-sm" type="submit" form="selectionForm" name="export_selected" value="1">
        CSV exportieren (ausgewählt)
    </button>

    <!-- NEU: Löschen (ausgewählt) -->
    <button class="btn btn-outline-danger btn-sm" type="button" id="btnDeleteSelected">
        Ausgewählte löschen
    </button>
</div>

<form id="selectionForm" method="post" action="/admin/export_csv.php">
    <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
    <input type="hidden" name="mode" value="selected">
</form>

<!-- NEU: Delete-Form (separat, damit export_csv.php nicht betroffen ist) -->
<form id="deleteForm" method="post" action="<?php echo h($postAction); ?>" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
    <input type="hidden" name="action" value="delete_selected">
</form>

<div class="card shadow-sm">
    <div class="admin-table-wrap">
        <table class="table table-sm table-hover align-middle mb-0 admin-table">
            <thead class="table-light">
            <tr>
                <th style="width:36px;"><input type="checkbox" id="checkAll"></th>
                <th><a href="<?php echo h(sort_link('id')); ?>">ID<?php echo h(sort_indicator('id')); ?></a></th>
                <th><a href="<?php echo h(sort_link('name')); ?>">Name<?php echo h(sort_indicator('name')); ?></a></th>
                <th>Vorname</th>

                <th class="bbs-col">Keine</th>
                <?php foreach ($bbsRows as $b): ?>
                    <?php
                    $label = trim((string)($b['bbs_kurz'] ?? ''));
                    if ($label === '') $label = trim((string)($b['bbs_schulnummer'] ?? ''));
                    if ($label === '') $label = (string)($b['bbs_bezeichnung'] ?? '');
                    ?>
                    <th class="bbs-col" title="<?php echo h((string)($b['bbs_bezeichnung'] ?? '')); ?>">
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
                    $assignedBbsId = $r['assigned_bbs_id'] !== null ? (int)$r['assigned_bbs_id'] : 0;

                    $lockedByBbsId = $r['locked_by_bbs_id'] !== null ? (int)$r['locked_by_bbs_id'] : 0;
                    $isLocked = ($lockedByBbsId > 0);

                    $lockedByLabel = '';
                    if ($isLocked) {
                        $lockedByLabel = $bbsMap[$lockedByBbsId] ?? ('BBS #' . $lockedByBbsId);
                    }

                    $lockedAt = $r['locked_at'] ? (string)$r['locked_at'] : '';

                    $formId = 'assignForm' . $appId;
                    $radioName = 'pick_bbs_' . $appId;
                    ?>
                    <tr>
                        <td>
                            <input class="form-check-input rowCheck"
                                   type="checkbox"
                                   value="<?php echo $appId; ?>"
                                   data-app-id="<?php echo $appId; ?>">
                        </td>

                        <td><?php echo $appId; ?></td>
                        <td><?php echo h((string)($r['name'] ?? '')); ?></td>
                        <td><?php echo h((string)($r['vorname'] ?? '')); ?></td>

                        <!-- Hidden Assign Form -->
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
                                   <?php echo $assignedBbsId === 0 ? 'checked' : ''; ?>>
                        </td>

                        <?php foreach ($bbsRows as $b): ?>
                            <?php $bid = (int)$b['bbs_id']; ?>
                            <td class="bbs-col">
                                <input type="radio"
                                       name="<?php echo h($radioName); ?>"
                                       value="<?php echo $bid; ?>"
                                       data-assign-form="<?php echo h($formId); ?>"
                                       <?php echo $assignedBbsId === $bid ? 'checked' : ''; ?>>
                            </td>
                        <?php endforeach; ?>

                        <td class="text-nowrap" style="min-width:190px;">
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

    // NEU: Bulk-Delete mit Bestätigung
    const btnDelete = document.getElementById('btnDeleteSelected');
    const deleteForm = document.getElementById('deleteForm');

    function getSelectedIds() {
        const ids = [];
        document.querySelectorAll('.rowCheck').forEach(cb => {
            if (cb.checked) {
                const id = parseInt(cb.getAttribute('data-app-id'), 10);
                if (!isNaN(id) && id > 0) ids.push(id);
            }
        });
        return ids;
    }

    if (btnDelete && deleteForm) {
        btnDelete.addEventListener('click', () => {
            const ids = getSelectedIds();
            if (!ids.length) {
                alert('Bitte mindestens einen Datensatz auswählen.');
                return;
            }

            const msg = 'Sollen die ausgewählten Datensätze wirklich gelöscht werden?\n\n'
                + 'Anzahl: ' + ids.length + '\n'
                + 'IDs: ' + ids.join(', ') + '\n\n'
                + 'Dieser Vorgang kann nicht rückgängig gemacht werden.';

            if (!confirm(msg)) return;

            // Hidden inputs ids[] ins Delete-Form einfügen
            // vorher leeren (außer csrf/action)
            deleteForm.querySelectorAll('input[name="ids[]"]').forEach(n => n.remove());

            ids.forEach(id => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = String(id);
                deleteForm.appendChild(inp);
            });

            deleteForm.submit();
        });
    }
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
