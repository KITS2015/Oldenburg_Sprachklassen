<?php
// public/form_upload.php
require __DIR__ . '/wizard/_common.php';
require_step('upload'); // Session-Step-Guard

// --- Hilfsfunktion: sicheres JSON-Antworten
function json_response($data, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

// --- AJAX-Endpunkte: Upload / Delete (ohne Seitenreload)
if (($_GET['ajax'] ?? '') === '1') {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false, 'error' => 'Invalid method'], 405);
  if (!csrf_check()) json_response(['ok' => false, 'error' => 'Ungültiges CSRF-Token'], 400);

  // Upload-Verzeichnis sicherstellen
  if (!is_dir($UPLOAD_DIR)) @mkdir($UPLOAD_DIR, 0770, true);
  if (!is_dir($UPLOAD_DIR) || !is_writable($UPLOAD_DIR)) {
    json_response(['ok' => false, 'error' => 'Upload-Verzeichnis nicht beschreibbar'], 500);
  }

  $action = $_POST['action'] ?? '';
  $field  = $_POST['field']  ?? '';
  if (!in_array($field, ['zeugnis','lebenslauf'], true)) {
    json_response(['ok' => false, 'error' => 'Ungültiges Feld'], 400);
  }

  // Bestehende Session-Struktur vorbereiten
  $_SESSION['form']['upload'] = $_SESSION['form']['upload'] ?? ['zeugnis'=>null, 'lebenslauf'=>null, 'zeugnis_spaeter'=>'0'];

  if ($action === 'upload') {
    if (!isset($_FILES['file'])) json_response(['ok'=>false,'error'=>'Keine Datei gesendet'], 400);

    $err = $_FILES['file']['error'];
    if ($err !== UPLOAD_ERR_OK) json_response(['ok'=>false,'error'=>'Upload fehlgeschlagen ('.$err.')'], 400);

    $tmp  = $_FILES['file']['tmp_name'];
    $name = $_FILES['file']['name'];
    $size = (int)$_FILES['file']['size'];

    // Validierung: MIME & Größe
    $allowed = ['application/pdf','image/jpeg','image/png'];
    $mime = @mime_content_type($tmp) ?: '';
    if (!in_array($mime, $allowed, true)) json_response(['ok'=>false,'error'=>'Nur PDF/JPG/PNG erlaubt'], 400);
    if ($size > 5*1024*1024)       json_response(['ok'=>false,'error'=>'Datei größer als 5 MB'], 400);

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf','jpg','jpeg','png'], true)) json_response(['ok'=>false,'error'=>'Ungültige Dateiendung'], 400);

    // Filename
    $fname = $field.'_'.date('Ymd_His').'_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest  = rtrim($UPLOAD_DIR,'/').'/'.$fname;

    if (!move_uploaded_file($tmp, $dest)) {
      json_response(['ok'=>false,'error'=>'Konnte Datei nicht speichern (Berechtigungen?)'], 500);
    }

    // Vorherige Datei dieses Feldes optional löschen (nur wenn existiert)
    $prev = $_SESSION['form']['upload'][$field] ?? null;
    if ($prev && is_file(rtrim($UPLOAD_DIR,'/').'/'.$prev)) {
      @unlink(rtrim($UPLOAD_DIR,'/').'/'.$prev);
    }

    // In Session merken
    $_SESSION['form']['upload'][$field] = $fname;

    json_response(['ok'=>true, 'filename'=>$fname]);
  }

  if ($action === 'delete') {
    $current = $_SESSION['form']['upload'][$field] ?? null;
    if ($current) {
      $path = rtrim($UPLOAD_DIR,'/').'/'.$current;
      if (is_file($path)) @unlink($path);
    }
    $_SESSION['form']['upload'][$field] = null;
    json_response(['ok'=>true]);
  }

  json_response(['ok'=>false,'error'=>'Unbekannte Aktion'], 400);
}

// --- Normale (nicht-AJAX) Weiter-Button → nur „Zusage später“-Checkbox & Redirect
$errors = [];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

  // hier werden keine Dateien mehr synchron hochgeladen – nur die Checkbox verarbeitet
  $_SESSION['form']['upload'] = array_merge([
    'zeugnis'=>$_SESSION['form']['upload']['zeugnis'] ?? null,
    'lebenslauf'=>$_SESSION['form']['upload']['lebenslauf'] ?? null,
    'zeugnis_spaeter'=>'0',
  ], [
    'zeugnis_spaeter' => isset($_POST['zeugnis_spaeter']) ? '1' : '0',
  ]);

  if (!$errors) { header('Location: /form_review.php'); exit; }
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Schritt 3/4 – Unterlagen</title>
  <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css"><link rel="stylesheet" href="/assets/form.css">
  <style>
    .upload-box { border:1px dashed #ced4da; border-radius:.75rem; padding:1rem; }
    .upload-disabled { opacity:.6; pointer-events:none; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="card shadow border-0 rounded-4"><div class="card-body p-4 p-md-5">
    <h1 class="h4 mb-3">Schritt 3/4 – Unterlagen (optional)</h1>
    <?php if ($errors): ?><div class="alert alert-danger">Bitte prüfen Sie die markierten Felder.</div><?php endif; ?>

    <form method="post" action="" novalidate>
      <?php csrf_field(); ?>
      <input type="hidden" id="csrf_token" value="<?= h($_SESSION['csrf'] ?? '') ?>">

      <div class="row g-4">

        <!-- Feld: Zeugnis -->
        <div class="col-md-6">
          <label class="form-label">Letztes Halbjahreszeugnis (PDF/JPG/PNG, max. 5 MB)</label>

          <div id="box-zeugnis" class="upload-box">
            <div class="d-flex align-items-center gap-2 mb-2">
              <input id="file-zeugnis" type="file" class="form-control"
                     <?= !empty($_SESSION['form']['upload']['zeugnis']) ? 'disabled' : '' ?>>
              <button id="btn-del-zeugnis" type="button" class="btn btn-outline-danger"
                      <?= empty($_SESSION['form']['upload']['zeugnis']) ? 'disabled' : '' ?>>
                Entfernen
              </button>
            </div>

            <div class="progress" style="height:8px;">
              <div id="prog-zeugnis" class="progress-bar" role="progressbar" style="width:0%"></div>
            </div>

            <div class="form-text mt-2" id="info-zeugnis">
              <?php if(!empty($_SESSION['form']['upload']['zeugnis'])): ?>
                Bereits gespeichert: <strong><?= h($_SESSION['form']['upload']['zeugnis']) ?></strong>
              <?php else: ?>
                Noch keine Datei hochgeladen.
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Feld: Lebenslauf -->
        <div class="col-md-6">
          <label class="form-label">Lebenslauf (PDF/JPG/PNG, max. 5 MB)</label>

          <div id="box-lebenslauf" class="upload-box">
            <div class="d-flex align-items-center gap-2 mb-2">
              <input id="file-lebenslauf" type="file" class="form-control"
                     <?= !empty($_SESSION['form']['upload']['lebenslauf']) ? 'disabled' : '' ?>>
              <button id="btn-del-lebenslauf" type="button" class="btn btn-outline-danger"
                      <?= empty($_SESSION['form']['upload']['lebenslauf']) ? 'disabled' : '' ?>>
                Entfernen
              </button>
            </div>

            <div class="progress" style="height:8px;">
              <div id="prog-lebenslauf" class="progress-bar" role="progressbar" style="width:0%"></div>
            </div>

            <div class="form-text mt-2" id="info-lebenslauf">
              <?php if(!empty($_SESSION['form']['upload']['lebenslauf'])): ?>
                Bereits gespeichert: <strong><?= h($_SESSION['form']['upload']['lebenslauf']) ?></strong>
              <?php else: ?>
                Noch keine Datei hochgeladen.
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-12">
          <div class="form-check">
            <?php $chk = $_SESSION['form']['upload']['zeugnis_spaeter'] ?? '0'; ?>
            <input class="form-check-input" type="checkbox" id="zeugnis_spaeter" name="zeugnis_spaeter" value="1" <?= $chk==='1'?'checked':''; ?>>
            <label class="form-check-label" for="zeugnis_spaeter">
              Ich reiche das Halbjahreszeugnis nach der Zusage nach.
            </label>
          </div>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <a href="/form_school.php" class="btn btn-outline-secondary">Zurück</a>
        <button class="btn btn-primary">Weiter</button>
      </div>
    </form>
  </div></div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const csrf = document.getElementById('csrf_token').value;

  function setup(field){
    const fileInput = document.getElementById('file-'+field);
    const delBtn    = document.getElementById('btn-del-'+field);
    const progBar   = document.getElementById('prog-'+field);
    const info      = document.getElementById('info-'+field);

    function setProgress(pct){
      progBar.style.width = pct + '%';
      progBar.setAttribute('aria-valuenow', pct);
    }

    function uploadFile(file){
      const formData = new FormData();
      formData.append('action', 'upload');
      formData.append('field', field);
      formData.append('csrf', csrf); // nur als Platzhalter, das echte Token prüft serverseitig via csrf_check()
      formData.append('file', file);

      const xhr = new XMLHttpRequest();
      xhr.open('POST', location.pathname + '?ajax=1', true);
      // CSRF Header mitsenden, damit csrf_check() Header berücksichtigen kann (falls ihr das so implementiert)
      xhr.setRequestHeader('X-CSRF-Token', csrf);

      xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) setProgress(Math.round(e.loaded * 100 / e.total));
      };

      xhr.onreadystatechange = () => {
        if (xhr.readyState !== 4) return;
        try {
          const res = JSON.parse(xhr.responseText);
          if (xhr.status === 200 && res.ok) {
            info.innerHTML = 'Bereits gespeichert: <strong>'+ (res.filename || file.name) +'</strong>';
            fileInput.value = '';
            fileInput.disabled = true;     // nur 1 Datei zulassen
            delBtn.disabled = false;       // Löschen wieder möglich
            setProgress(100);
          } else {
            alert(res.error || 'Upload fehlgeschlagen.');
            setProgress(0);
            fileInput.value = '';
          }
        } catch(e){
          alert('Unerwartete Antwort vom Server.');
          setProgress(0);
          fileInput.value = '';
        }
      };

      setProgress(0);
      xhr.send(formData);
    }

    fileInput?.addEventListener('change', () => {
      if (!fileInput.files || !fileInput.files[0]) return;
      uploadFile(fileInput.files[0]);
    });

    delBtn?.addEventListener('click', () => {
      if (!confirm('Hochgeladene Datei wirklich entfernen?')) return;
      const formData = new FormData();
      formData.append('action','delete');
      formData.append('field', field);
      formData.append('csrf', csrf);

      const xhr = new XMLHttpRequest();
      xhr.open('POST', location.pathname + '?ajax=1', true);
      xhr.setRequestHeader('X-CSRF-Token', csrf);
      xhr.onload = () => {
        try {
          const res = JSON.parse(xhr.responseText);
          if (xhr.status === 200 && res.ok) {
            // UI zurücksetzen
            document.getElementById('info-'+field).textContent = 'Noch keine Datei hochgeladen.';
            document.getElementById('file-'+field).disabled = false;
            document.getElementById('btn-del-'+field).disabled = true;
            setProgress(0);
          } else {
            alert(res.error || 'Löschen fehlgeschlagen.');
          }
        } catch(e){ alert('Unerwartete Antwort vom Server.'); }
      };
      xhr.send(formData);
    });
  }

  setup('zeugnis');
  setup('lebenslauf');
})();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
