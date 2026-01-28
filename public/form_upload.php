<?php
// public/form_upload.php
// Schritt 3/4 – Unterlagen (optional)
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/functions_form.php';

// Step-Guard (personal + school müssen existieren)
require_step('upload');

// i18n helper: {var} Platzhalter ersetzen (Strings)
function tr(string $key, array $vars = []): string {
    $s = t($key);
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string)$v, $s);
    }
    return $s;
}

// Kleine JSON-Helfer
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Aktuelle Application-ID zu aktuellem Access-Token ermitteln
function current_application_id(): ?int {
    if (!function_exists('current_access_token')) return null;

    $token = current_access_token();
    if ($token === '') return null;

    try {
        $pdo = db();
        $st = $pdo->prepare("SELECT id FROM applications WHERE token = :t LIMIT 1");
        $st->execute([':t' => $token]);
        $id = $st->fetchColumn();
        return $id !== false ? (int)$id : null;
    } catch (Throwable $e) {
        error_log('current_application_id: ' . $e->getMessage());
        return null;
    }
}

$appId = current_application_id();
if ($appId === null) {
    if (function_exists('flash_set')) {
        flash_set('warning', t('upload.flash.no_access'));
    }
    header('Location: ' . url_with_lang('/index.php'));
    exit;
}

// Upload-Verzeichnis
$uploadDir = rtrim(APP_UPLOADS, '/');
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}

// Upload-Parameter
$allowedMime = [
    'application/pdf',
    'image/jpeg',
    'image/png',
];
$allowedExt = ['pdf','jpg','jpeg','png'];

$maxSizeBytes = 5 * 1024 * 1024; // 5 MB
$maxMb = 5;

// Upload-Typen (Keys bleiben DB-konform, Label via i18n)
$types = [
    'zeugnis'    => tr('upload.type.zeugnis') . ' ' . tr('upload.type_hint', ['max_mb' => $maxMb]),
    'lebenslauf' => tr('upload.type.lebenslauf') . ' ' . tr('upload.type_hint', ['max_mb' => $maxMb]),
];

// Bestehende Uploads laden
function load_existing_uploads(int $appId): array {
    $existing = [];
    try {
        $pdo = db();
        $st = $pdo->prepare("SELECT typ, filename, mime, size_bytes, uploaded_at FROM uploads WHERE application_id = :id");
        $st->execute([':id' => $appId]);
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $existing[$row['typ']] = $row;
        }
    } catch (Throwable $e) {
        error_log('form_upload – select uploads: ' . $e->getMessage());
    }
    return $existing;
}

// --------------------------------------------------------
// AJAX-Handler: sofortiges Hochladen / Löschen
// --------------------------------------------------------
if (($_GET['ajax'] ?? '') === '1') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['ok' => false, 'error' => t('upload.ajax.invalid_method')], 405);
    }
    if (!csrf_check()) {
        json_response(['ok' => false, 'error' => t('upload.ajax.invalid_csrf')], 400);
    }
    if ($appId === null) {
        json_response(['ok' => false, 'error' => t('upload.ajax.no_access')], 400);
    }

    $action = (string)($_POST['action'] ?? '');
    $field  = (string)($_POST['field']  ?? '');

    if (!array_key_exists($field, $types)) {
        json_response(['ok' => false, 'error' => t('upload.ajax.invalid_field')], 400);
    }

    try {
        $pdo = db();

        // Datei hochladen
        if ($action === 'upload') {
            if (!isset($_FILES['file'])) {
                json_response(['ok' => false, 'error' => t('upload.ajax.no_file_sent')], 400);
            }
            $f = $_FILES['file'];

            if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                json_response(['ok' => false, 'error' => t('upload.ajax.no_file_selected')], 400);
            }
            if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $code = (int)$f['error'];
                json_response(['ok' => false, 'error' => tr('upload.ajax.upload_error', ['code' => $code])], 400);
            }

            $tmp  = (string)($f['tmp_name'] ?? '');
            $name = (string)($f['name'] ?? '');
            $size = (int)($f['size'] ?? 0);

            if ($size > $maxSizeBytes) {
                json_response(['ok' => false, 'error' => tr('upload.ajax.too_large', ['max_mb' => $maxMb])], 400);
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($tmp) ?: (string)($f['type'] ?? '');
            if ($mime && !in_array($mime, $allowedMime, true)) {
                json_response(['ok' => false, 'error' => t('upload.ajax.mime_only')], 400);
            }

            $ext = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
            if ($ext === '') {
                $ext = ($mime === 'application/pdf') ? 'pdf' : 'bin';
            }
            if (!in_array($ext, $allowedExt, true)) {
                json_response(['ok' => false, 'error' => t('upload.ajax.ext_only')], 400);
            }

            $safeExt = preg_replace('/[^a-z0-9]+/i', '', $ext) ?: 'bin';
            $now     = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
            $newName = 'app_' . $appId . '_' . $field . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;

            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $target = $uploadDir . '/' . $newName;

            if (!move_uploaded_file($tmp, $target)) {
                json_response(['ok' => false, 'error' => t('upload.ajax.cannot_save')], 500);
            }

            // Alte Datei gleichen Typs löschen
            $stOld = $pdo->prepare("SELECT filename FROM uploads WHERE application_id = :id AND typ = :typ LIMIT 1");
            $stOld->execute([':id' => $appId, ':typ' => $field]);
            if ($oldFn = $stOld->fetchColumn()) {
                $oldPath = $uploadDir . '/' . $oldFn;
                if (is_file($oldPath)) @unlink($oldPath);
            }

            // DB upsert
            $stUp = $pdo->prepare("
                INSERT INTO uploads (application_id, typ, filename, mime, size_bytes, uploaded_at)
                VALUES (:id, :typ, :fn, :mime, :size, :up)
                ON DUPLICATE KEY UPDATE
                    filename    = VALUES(filename),
                    mime        = VALUES(mime),
                    size_bytes  = VALUES(size_bytes),
                    uploaded_at = VALUES(uploaded_at)
            ");
            $stUp->execute([
                ':id'   => $appId,
                ':typ'  => $field,
                ':fn'   => $newName,
                ':mime' => $mime ?: 'application/octet-stream',
                ':size' => $size,
                ':up'   => $now,
            ]);

            $sizeKb = round($size / 1024, 1);
            $sizeKbStr = str_replace('.', ',', (string)$sizeKb);

            // Info-HTML serverseitig i18n-fest bauen (JS muss nicht basteln)
            $infoHtml = tr('upload.saved_html', [
                'filename'    => $newName,
                'size_kb'     => $sizeKbStr,
                'uploaded_at' => $now,
            ]);

            json_response([
                'ok'          => true,
                'filename'    => $newName,
                'size_kb'     => $sizeKb,
                'uploaded_at' => $now,
                'info_html'   => $infoHtml,
            ]);
        }

        // Datei löschen
        if ($action === 'delete') {
            $st = $pdo->prepare("SELECT filename FROM uploads WHERE application_id = :id AND typ = :typ LIMIT 1");
            $st->execute([':id' => $appId, ':typ' => $field]);
            if ($fn = $st->fetchColumn()) {
                $pdo->prepare("DELETE FROM uploads WHERE application_id = :id AND typ = :typ")
                    ->execute([':id' => $appId, ':typ' => $field]);

                $full = $uploadDir . '/' . $fn;
                if (is_file($full)) @unlink($full);
            }
            json_response(['ok' => true]);
        }

        json_response(['ok' => false, 'error' => t('upload.ajax.unknown_action')], 400);

    } catch (Throwable $e) {
        error_log('form_upload – ajax: ' . $e->getMessage());
        json_response(['ok' => false, 'error' => t('upload.ajax.server_error')], 500);
    }
}

// --------------------------------------------------------
// Normaler POST: „Weiter“-Button (Meta-Daten speichern)
// --------------------------------------------------------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        http_response_code(400);
        exit('Ungültige Anfrage.');
    }

    // Nur Meta-Infos speichern
    $uploadScope = [
        'zeugnis_spaeter' => isset($_POST['zeugnis_spaeter']) ? '1' : '0',
    ];
    $_SESSION['form']['upload'] = $uploadScope;

    $save = save_scope_allow_noemail('upload', $uploadScope);
    $_SESSION['last_save'] = $save;

    if (function_exists('flash_set')) {
        if ($save['ok'] ?? false) {
            flash_set('success', t('upload.flash.saved'));
        } else {
            $msg = $save['err'] ?? 'Zwischenspeicherung in der Session.';
            flash_set('info', $msg);
        }
    }

    header('Location: ' . url_with_lang('/form_review.php'));
    exit;
}

// Bestehende Uploads für Anzeige
$existing = load_existing_uploads($appId);

// Upload-Meta für Checkbox
$uploadMeta   = $_SESSION['form']['upload'] ?? [];
$zeugnisLater = ($uploadMeta['zeugnis_spaeter'] ?? '0') === '1';

// Header-Infos
$title     = t('upload.page_title');
$html_lang = html_lang();
$html_dir  = html_dir();

require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';

// JS-Strings (i18n) als sichere Konstanten
$JS = [
    'uploading'      => t('upload.js.uploading'),
    'unexpected'     => t('upload.js.unexpected'),
    'upload_failed'  => t('upload.js.upload_failed'),
    'delete_confirm' => t('upload.js.delete_confirm'),
    'delete_failed'  => t('upload.js.delete_failed'),
    'empty'          => t('upload.empty'),
];
?>
<div class="container py-4">
  <?php if (function_exists('flash_render')) { flash_render(); } ?>

  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-3"><?= h(t('upload.h1')) ?></h1>

      <p class="mb-3">
        <?= tr('upload.intro', ['max_mb' => $maxMb]) /* enthält <strong> bewusst unge-escaped */ ?>
      </p>

      <form method="post" action="" novalidate>
        <?php csrf_field(); ?>
        <input type="hidden" id="csrf_token" value="<?= h($_SESSION['csrf'] ?? '') ?>">

        <div class="row g-4">
          <?php foreach ($types as $typ => $label): ?>
            <?php $row = $existing[$typ] ?? null; ?>
            <div class="col-md-6">
              <label class="form-label"><?= h($label) ?></label>
              <div id="box-<?= h($typ) ?>" class="upload-box border rounded-3 p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <input
                    id="file-<?= h($typ) ?>"
                    type="file"
                    class="form-control"
                    <?= $row ? 'disabled' : '' ?>
                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                  >
                  <button
                    id="btn-del-<?= h($typ) ?>"
                    type="button"
                    class="btn btn-outline-danger"
                    <?= $row ? '' : 'disabled' ?>
                  >
                    <?= h(t('upload.btn.remove')) ?>
                  </button>
                </div>
                <div class="progress" style="height:8px;">
                  <div id="prog-<?= h($typ) ?>" class="progress-bar" role="progressbar" style="width:0%"></div>
                </div>

                <div class="form-text mt-2" id="info-<?= h($typ) ?>">
                  <?php if ($row): ?>
                    <?php
                      $sizeKb = number_format((int)$row['size_bytes'] / 1024, 1, ',', '.');
                      echo tr('upload.saved_html', [
                        'filename'    => (string)$row['filename'],
                        'size_kb'     => $sizeKb,
                        'uploaded_at' => (string)$row['uploaded_at'],
                      ]);
                    ?>
                  <?php else: ?>
                    <?= h(t('upload.empty')) ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="mt-4">
          <div class="form-check">
            <input
              class="form-check-input"
              type="checkbox"
              id="zeugnis_spaeter"
              name="zeugnis_spaeter"
              value="1"
              <?= $zeugnisLater ? 'checked' : '' ?>
            >
            <label class="form-check-label" for="zeugnis_spaeter">
              <?= h(t('upload.checkbox.zeugnis_spaeter')) ?>
            </label>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="<?= h(url_with_lang('/form_school.php')) ?>" class="btn btn-outline-secondary"><?= h(t('upload.btn.back')) ?></a>
          <button class="btn btn-primary"><?= h(t('upload.btn.next')) ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  const csrf = document.getElementById('csrf_token').value;

  const I18N = <?= json_encode($JS, JSON_UNESCAPED_UNICODE) ?>;

  function setup(field){
    const fileInput = document.getElementById('file-'+field);
    const delBtn    = document.getElementById('btn-del-'+field);
    const progBar   = document.getElementById('prog-'+field);
    const info      = document.getElementById('info-'+field);
    const box       = document.getElementById('box-'+field);

    if (!fileInput || !delBtn || !progBar || !info || !box) return;

    function setProgress(pct){
      progBar.style.width = pct + '%';
      progBar.setAttribute('aria-valuenow', pct);
    }

    function showError(msg){
      info.textContent = msg;
      info.classList.add('text-danger');
      box.classList.add('border-danger');
      box.classList.remove('border');
    }

    function showInfo(html){
      info.innerHTML = html;
      info.classList.remove('text-danger');
      box.classList.remove('border-danger');
      box.classList.add('border');
    }

    function uploadFile(file){
      const formData = new FormData();
      formData.append('action','upload');
      formData.append('field', field);
      formData.append('csrf', csrf);
      formData.append('file', file);

      const xhr = new XMLHttpRequest();
      xhr.open('POST', location.pathname + '?ajax=1', true);

      xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) setProgress(Math.round(e.loaded * 100 / e.total));
      };

      xhr.onreadystatechange = () => {
        if (xhr.readyState !== 4) return;
        try {
          const res = JSON.parse(xhr.responseText);
          if (xhr.status === 200 && res.ok) {
            showInfo(res.info_html || I18N.upload_failed);
            fileInput.value = '';
            fileInput.disabled = true;
            delBtn.disabled = false;
            setProgress(100);
          } else {
            showError(res.error || I18N.upload_failed);
            setProgress(0);
            fileInput.value = '';
          }
        } catch(e){
          showError(I18N.unexpected);
          setProgress(0);
          fileInput.value = '';
        }
      };

      setProgress(0);
      showInfo(I18N.uploading);
      xhr.send(formData);
    }

    fileInput.addEventListener('change', () => {
      if (!fileInput.files || !fileInput.files[0]) return;
      uploadFile(fileInput.files[0]);
    });

    delBtn.addEventListener('click', () => {
      if (!confirm(I18N.delete_confirm)) return;

      const formData = new FormData();
      formData.append('action','delete');
      formData.append('field', field);
      formData.append('csrf', csrf);

      const xhr = new XMLHttpRequest();
      xhr.open('POST', location.pathname + '?ajax=1', true);
      xhr.onload = () => {
        try {
          const res = JSON.parse(xhr.responseText);
          if (xhr.status === 200 && res.ok) {
            showInfo(I18N.empty);
            fileInput.disabled = false;
            delBtn.disabled = true;
            setProgress(0);
            fileInput.value = '';
          } else {
            showError(res.error || I18N.delete_failed);
          }
        } catch(e){
          showError(I18N.unexpected);
        }
      };
      xhr.send(formData);
    });
  }

  setup('zeugnis');
  setup('lebenslauf');
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
