<?php
// public/form_upload.php
// Schritt 3/4 – Unterlagen (optional)
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/functions_form.php';

// Step-Guard
require_step('upload');

// Kleine JSON-Helfer
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Aktuelle Application-ID zu aktuellem Access-Token ermitteln
function current_application_id(): ?int {
    if (!function_exists('current_access_token')) {
        return null;
    }
    $token = current_access_token();
    if ($token === '') {
        return null;
    }
    try {
        $pdo = db();
        $st = $pdo->prepare("SELECT id FROM applications WHERE token = :t LIMIT 1");
        $st->execute([':t' => $token]);
        $id = $st->fetchColumn();
        return $id !== false ? (int)$id : null;
    } catch (Throwable $e) {
        error_log('current_application_id: '.$e->getMessage());
        return null;
    }
}

$appId = current_application_id();
if ($appId === null) {
    if (function_exists('flash_set')) {
        flash_set('warning', 'Kein gültiger Zugang gefunden. Bitte beginnen Sie die Anmeldung neu.');
    }
    header('Location: /index.php');
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

$types = [
    'zeugnis'    => 'Letztes Halbjahreszeugnis (PDF/JPG/PNG, max. 5 MB)',
    'lebenslauf' => 'Lebenslauf (PDF/JPG/PNG, max. 5 MB)',
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
        error_log('form_upload – select uploads: '.$e->getMessage());
    }
    return $existing;
}

// --------------------------------------------------------
// AJAX-Handler: sofortiges Hochladen / Löschen
// --------------------------------------------------------
if (($_GET['ajax'] ?? '') === '1') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['ok' => false, 'error' => 'Ungültige Methode'], 405);
    }
    if (!csrf_check()) {
        json_response(['ok' => false, 'error' => 'Ungültiges CSRF-Token'], 400);
    }

    if ($appId === null) {
        json_response(['ok' => false, 'error' => 'Kein gültiger Zugang.'], 400);
    }

    $action = $_POST['action'] ?? '';
    $field  = $_POST['field']  ?? '';

    if (!in_array($field, array_keys($types), true)) {
        json_response(['ok' => false, 'error' => 'Ungültiges Feld'], 400);
    }

    try {
        $pdo = db();

        // Datei hochladen
        if ($action === 'upload') {
            if (!isset($_FILES['file'])) {
                json_response(['ok' => false, 'error' => 'Keine Datei gesendet'], 400);
            }
            $f = $_FILES['file'];

            if ($f['error'] === UPLOAD_ERR_NO_FILE) {
                json_response(['ok' => false, 'error' => 'Keine Datei ausgewählt'], 400);
            }
            if ($f['error'] !== UPLOAD_ERR_OK) {
                json_response(['ok' => false, 'error' => 'Upload-Fehler (Code '.$f['error'].')'], 400);
            }

            $tmp  = $f['tmp_name'];
            $name = $f['name'] ?? '';
            $size = (int)$f['size'];

            if ($size > $maxSizeBytes) {
                json_response(['ok' => false, 'error' => 'Datei größer als 5 MB'], 400);
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($tmp) ?: ($f['type'] ?? '');
            if ($mime && !in_array($mime, $allowedMime, true)) {
                json_response(['ok' => false, 'error' => 'Nur PDF, JPG oder PNG erlaubt'], 400);
            }

            $ext = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
            if ($ext === '') {
                $ext = ($mime === 'application/pdf') ? 'pdf' : 'bin';
            }
            if (!in_array($ext, $allowedExt, true)) {
                json_response(['ok' => false, 'error' => 'Ungültige Dateiendung (nur pdf/jpg/jpeg/png)'], 400);
            }

            $safeExt = preg_replace('/[^a-z0-9]+/i', '', $ext) ?: 'bin';
            $now     = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
            $newName = 'app_'.$appId.'_'.$field.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$safeExt;

            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $target = $uploadDir . '/' . $newName;

            if (!move_uploaded_file($tmp, $target)) {
                json_response(['ok' => false, 'error' => 'Konnte Datei nicht speichern'], 500);
            }

            // Alte Datei gleichen Typs löschen
            $stOld = $pdo->prepare("SELECT filename FROM uploads WHERE application_id = :id AND typ = :typ LIMIT 1");
            $stOld->execute([':id' => $appId, ':typ' => $field]);
            if ($oldFn = $stOld->fetchColumn()) {
                $oldPath = $uploadDir . '/' . $oldFn;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
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

            json_response([
                'ok'          => true,
                'filename'    => $newName,
                'size_kb'     => round($size / 1024, 1),
                'uploaded_at' => $now,
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
                if (is_file($full)) {
                    @unlink($full);
                }
            }
            json_response(['ok' => true]);
        }

        json_response(['ok' => false, 'error' => 'Unbekannte Aktion'], 400);

    } catch (Throwable $e) {
        error_log('form_upload – ajax: '.$e->getMessage());
        json_response(['ok' => false, 'error' => 'Serverfehler beim Upload'], 500);
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

    // Nur Meta-Infos (z.B. Zeugnis später) speichern
    $uploadScope = [
        'zeugnis_spaeter' => isset($_POST['zeugnis_spaeter']) ? '1' : '0',
    ];
    $_SESSION['form']['upload'] = $uploadScope;

    $save = save_scope_allow_noemail('upload', $uploadScope);
    $_SESSION['last_save'] = $save;

    if (function_exists('flash_set')) {
        if ($save['ok'] ?? false) {
            flash_set('success', 'Upload-Informationen gespeichert.');
        } else {
            $msg = $save['err'] ?? 'Zwischenspeicherung in der Session.';
            flash_set('info', $msg);
        }
    }

    header('Location: /form_review.php');
    exit;
}

// Bestehende Uploads für Anzeige
$existing = load_existing_uploads($appId);

// Upload-Meta für Checkbox
$uploadMeta   = $_SESSION['form']['upload'] ?? [];
$zeugnisLater = ($uploadMeta['zeugnis_spaeter'] ?? '0') === '1';

// Header-Infos
$title     = 'Schritt 3/4 – Unterlagen (optional)';
$html_lang = 'de';
$html_dir  = 'ltr';

require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';
?>

<div class="container py-4">
  <?php if (function_exists('flash_render')) { flash_render(); } ?>

  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-3">Schritt 3/4 – Unterlagen (optional)</h1>
      <?php if ($errors): ?>
        <div class="alert alert-danger">Bitte prüfen Sie die markierten Felder.</div>
      <?php endif; ?>

      <p class="mb-3">
        Bitte laden Sie Ihre wichtigsten Unterlagen hoch. Erlaubte Formate sind
        <strong>PDF</strong>, <strong>JPG</strong> und <strong>PNG</strong>. Die maximale Dateigröße
        beträgt <strong>5&nbsp;MB</strong> pro Datei.
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
                  >
                  <button
                    id="btn-del-<?= h($typ) ?>"
                    type="button"
                    class="btn btn-outline-danger"
                    <?= $row ? '' : 'disabled' ?>
                  >
                    Entfernen
                  </button>
                </div>
                <div class="progress" style="height:8px;">
                  <div id="prog-<?= h($typ) ?>" class="progress-bar" role="progressbar" style="width:0%"></div>
                </div>
                <div class="form-text mt-2" id="info-<?= h($typ) ?>">
                  <?php if ($row): ?>
                    Bereits gespeichert:
                    <strong><?= h($row['filename']) ?></strong>,
                    <?= number_format((int)$row['size_bytes'] / 1024, 1, ',', '.') ?> KB,
                    hochgeladen am <?= h($row['uploaded_at']) ?>
                  <?php else: ?>
                    Noch keine Datei hochgeladen.
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
              Ich reiche das Halbjahreszeugnis nach der Zusage nach.
            </label>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="/form_school.php" class="btn btn-outline-secondary">Zurück</a>
          <button class="btn btn-primary">Weiter</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  const csrf = document.getElementById('csrf_token').value;

  function setup(field){
    const fileInput = document.getElementById('file-'+field);
    const delBtn    = document.getElementById('btn-del-'+field);
    const progBar   = document.getElementById('prog-'+field);
    const info      = document.getElementById('info-'+field);

    if (!fileInput || !delBtn || !progBar || !info) return;

    function setProgress(pct){
      progBar.style.width = pct + '%';
      progBar.setAttribute('aria-valuenow', pct);
    }

    function showError(msg){
      info.textContent = msg;
      info.classList.add('text-danger');
    }

    function showInfo(html){
      info.innerHTML = html;
      info.classList.remove('text-danger');
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
            const size = res.size_kb ? res.size_kb.toString().replace('.', ',') : '';
            const date = res.uploaded_at || '';
            showInfo(
              'Bereits gespeichert: <strong>' +
              (res.filename || file.name) +
              '</strong>' +
              (size ? ', ' + size + ' KB' : '') +
              (date ? ', hochgeladen am ' + date : '')
            );
            fileInput.value = '';
            fileInput.disabled = true;
            delBtn.disabled = false;
            setProgress(100);
          } else {
            showError(res.error || 'Upload fehlgeschlagen.');
            setProgress(0);
            fileInput.value = '';
          }
        } catch(e){
          showError('Unerwartete Antwort vom Server.');
          setProgress(0);
          fileInput.value = '';
        }
      };

      setProgress(0);
      xhr.send(formData);
    }

    fileInput.addEventListener('change', () => {
      if (!fileInput.files || !fileInput.files[0]) return;
      showInfo('Upload wird durchgeführt …');
      uploadFile(fileInput.files[0]);
    });

    delBtn.addEventListener('click', () => {
      if (!confirm('Hochgeladene Datei wirklich entfernen?')) return;

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
            showInfo('Noch keine Datei hochgeladen.');
            fileInput.disabled = false;
            delBtn.disabled = true;
            setProgress(0);
            fileInput.value = '';
          } else {
            showError(res.error || 'Löschen fehlgeschlagen.');
          }
        } catch(e){
          showError('Unerwartete Antwort vom Server.');
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
