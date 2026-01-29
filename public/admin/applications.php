<?php
declare(strict_types=1);

// Datei: public/admin/applications.php

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';

require_admin();

$pdo = admin_db();

// ---- Input (Filter / Suche / Paging / Sort) ----
$limit = 25;

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) { $page = 1; }
$offset = ($page - 1) * $limit;

$status = trim((string)($_GET['status'] ?? ''));
$allowedStatus = ['', 'draft', 'submitted', 'withdrawn'];
if (!in_array($status, $allowedStatus, true)) {
    $status = '';
}

$q = trim((string)($_GET['q'] ?? ''));
$q = mb_substr($q, 0, 200); // Hard limit for safety

$sort = (string)($_GET['sort'] ?? 'updated_at');
$dir  = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

// Sort Whitelist (Mapping -> SQL)
$sortMap = [
    'id'         => 'a.id',
    'status'     => 'a.status',
    'created_at' => 'a.created_at',
    'updated_at' => 'a.updated_at',
    'email'      => 'COALESCE(p.email, a.email)',
    'name'       => 'p.name',
];

if (!isset($sortMap[$sort])) {
    $sort = 'updated_at';
}
$orderBy = $sortMap[$sort] . ' ' . strtoupper($dir);

// ---- WHERE bauen ----
$where = [];
$params = [];

if ($status !== '') {
    $where[] = 'a.status = ?';
    $params[] = $status;
}

if ($q !== '') {
    // Suche: email, name, vorname, token, id
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

// ---- Count für Pagination ----
$stCount = $pdo->prepare("
    SELECT COUNT(*)
    FROM applications a
    LEFT JOIN personal p ON p.application_id = a.id
    $whereSql
");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();
$totalPages = (int)max(1, (int)ceil($total / $limit));

// page clamp
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page - 1) * $limit;

// ---- Daten laden ----
$st = $pdo->prepare("
    SELECT
        a.id,
        a.token,
        a.status,
        a.created_at,
        a.updated_at,
        a.email AS app_email,
        a.dob AS app_dob,

        p.name,
        p.vorname,
        p.geburtsdatum,
        p.email AS personal_email

    FROM applications a
    LEFT JOIN personal p ON p.application_id = a.id
    $whereSql
    ORDER BY $orderBy
    LIMIT $limit OFFSET $offset
");
$st->execute($params);
$rows = $st->fetchAll();

// ---- Helpers ----
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function build_query(array $overrides = []): string
{
    $base = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) {
            unset($base[$k]);
        } else {
            $base[$k] = (string)$v;
        }
    }
    return http_build_query($base);
}

function sort_link(string $col): string
{
    $currentSort = (string)($_GET['sort'] ?? 'updated_at');
    $currentDir  = strtolower((string)($_GET['dir'] ?? 'desc'));
    $newDir = 'asc';
    if ($currentSort === $col && $currentDir === 'asc') {
        $newDir = 'desc';
    }
    return '/admin/applications.php?' . build_query(['sort' => $col, 'dir' => $newDir, 'page' => 1]);
}

function sort_indicator(string $col): string
{
    $currentSort = (string)($_GET['sort'] ?? 'updated_at');
    $currentDir  = strtolower((string)($_GET['dir'] ?? 'desc'));
    if ($currentSort !== $col) return '';
    return $currentDir === 'asc' ? ' ▲' : ' ▼';
}

?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Admin – Bewerbungen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/admin/admin.css">
</head>
<body class="admin-body admin-body--app">
<div class="container py-4 admin-container">

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

    <form id="selectionForm" method="post" action="/admin/export_csv.php">
        <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
        <input type="hidden" name="mode" value="selected">

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width:36px;">
                            <input type="checkbox" id="checkAll">
                        </th>
                        <th><a href="<?php echo h(sort_link('id')); ?>">ID<?php echo h(sort_indicator('id')); ?></a></th>
                        <th><a href="<?php echo h(sort_link('status')); ?>">Status<?php echo h(sort_indicator('status')); ?></a></th>
                        <th><a href="<?php echo h(sort_link('email')); ?>">E-Mail<?php echo h(sort_indicator('email')); ?></a></th>
                        <th><a href="<?php echo h(sort_link('name')); ?>">Name<?php echo h(sort_indicator('name')); ?></a></th>
                        <th>Vorname</th>
                        <th>Geburtsdatum</th>
                        <th><a href="<?php echo h(sort_link('updated_at')); ?>">Aktualisiert<?php echo h(sort_indicator('updated_at')); ?></a></th>
                        <th><a href="<?php echo h(sort_link('created_at')); ?>">Erstellt<?php echo h(sort_indicator('created_at')); ?></a></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="9" class="text-muted p-3">Keine Datensätze gefunden.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <?php
                            $email = (string)($r['personal_email'] ?? '');
                            if ($email === '') { $email = (string)($r['app_email'] ?? ''); }

                            $dob = (string)($r['geburtsdatum'] ?? '');
                            if ($dob === '') { $dob = (string)($r['app_dob'] ?? ''); }
                            ?>
                            <tr>
                                <td>
                                    <input class="form-check-input rowCheck" type="checkbox" name="ids[]" value="<?php echo (int)$r['id']; ?>">
                                </td>
                                <td><?php echo (int)$r['id']; ?></td>
                                <td><span class="badge bg-secondary"><?php echo h((string)$r['status']); ?></span></td>
                                <td><?php echo h($email); ?></td>
                                <td><?php echo h((string)($r['name'] ?? '')); ?></td>
                                <td><?php echo h((string)($r['vorname'] ?? '')); ?></td>
                                <td><?php echo h($dob); ?></td>
                                <td><?php echo h((string)$r['updated_at']); ?></td>
                                <td><?php echo h((string)$r['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>

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
            // Simple window
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

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
<script>
    // Check all
    const checkAll = document.getElementById('checkAll');
    const rowChecks = document.querySelectorAll('.rowCheck');

    if (checkAll) {
        checkAll.addEventListener('change', () => {
            rowChecks.forEach(cb => cb.checked = checkAll.checked);
        });
    }
</script>
</body>
</html>
