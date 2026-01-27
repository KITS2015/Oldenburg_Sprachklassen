<?php
// public/form_review.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/db.php';
require_step('review');

// Readonly, wenn bereits eingereichte Bewerbung geladen wurde
$readonly = !empty($_SESSION['application_readonly']);

// UI Helper
function yesno(bool $b): string { return $b ? 'Ja' : 'Nein'; }
function show_val(string $v): string { return trim($v) !== '' ? h($v) : '–'; }
function norm_space(?string $v): string { return trim((string)$v); }

function dl_row(string $label, string $value, string $col = 'col-md-6'): string {
    return '
      <div class="'.$col.'">
        <div class="small text-muted">'.h($label).'</div>
        <div class="fw-semibold">'.($value !== '' ? $value : '–').'</div>
      </div>
    ';
}

// ===== POST-Verarbeitung (Bewerben / Zur Startseite / Neue Bewerbung) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

    $action = (string)($_POST['action'] ?? 'submit');

    // --- Session zurücksetzen & zur Startseite ---
    if ($action === 'reset') {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: /index.php');
        exit;
    }

    // --- Session zurücksetzen & neue Bewerbung starten ---
    if ($action === 'new_application') {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: /access_create.php');
        exit;
    }

    // --- Bewerbung final einreichen ---
    if ($action === 'submit') {
        // Bewerbung ist bereits eingereicht -> nicht noch einmal abschicken
        if ($readonly) {
            if (function_exists('flash_set')) {
                flash_set('info', 'Diese Bewerbung wurde bereits abgeschickt und kann nicht erneut eingereicht oder geändert werden.');
            }
            header('Location: /form_review.php');
            exit;
        }

        $pdo = db();

        // Form-Daten aus der Session holen
        $p = $_SESSION['form']['personal'] ?? [];
        $s = $_SESSION['form']['school']   ?? [];
        $u = $_SESSION['form']['upload']   ?? [];

        $token = current_access_token();
        if ($token === '') {
            error_log('form_review – kein Access-Token in Session');
            if (function_exists('flash_set')) {
                flash_set('danger', 'Kein gültiger Zugangscode. Bitte starten Sie den Vorgang neu.');
            }
            header('Location: /index.php');
            exit;
        }

        try {
            $pdo->beginTransaction();

            // ---------- applications: Datensatz laden ----------
            $st = $pdo->prepare("SELECT * FROM applications WHERE token = :t LIMIT 1");
            $st->execute([':t' => $token]);
            $appRow = $st->fetch(PDO::FETCH_ASSOC);
            if (!$appRow) {
                throw new RuntimeException('Bewerbung zu diesem Token nicht gefunden.');
            }
            $appId = (int)$appRow['id'];

            // ---------- E-Mail / DOB / Status aktualisieren ----------
            $dobSql = $appRow['dob'] ?? null;
            if (!$dobSql && !empty($p['geburtsdatum']) && function_exists('norm_date_dmy_to_ymd')) {
                $dobTmp = norm_date_dmy_to_ymd((string)$p['geburtsdatum']);
                if ($dobTmp !== '') {
                    $dobSql = $dobTmp;
                }
            }

            $accessEmail = trim((string)($_SESSION['access']['email'] ?? ''));
            $emailSql    = $accessEmail !== '' ? $accessEmail : ($appRow['email'] ?? null);

            $emailVerified = (int)($appRow['email_verified'] ?? 0);
            if (!empty($_SESSION['email_verified']) && $emailSql) {
                $emailVerified = 1;
            }

            $ipBin = null;
            if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
                $ipBin = inet_pton($_SERVER['REMOTE_ADDR']);
            }

            $upApp = $pdo->prepare("
                UPDATE applications
                SET email          = :e,
                    dob            = :d,
                    email_verified = :v,
                    status         = 'submitted',
                    submit_ip      = :ip
                WHERE id = :id
            ");
            $upApp->execute([
                ':e'  => $emailSql,
                ':d'  => $dobSql,
                ':v'  => $emailVerified,
                ':ip' => $ipBin,
                ':id' => $appId,
            ]);

            // ---------- personal ----------
            // Bewerber-E-Mail ist optional (kann leer sein)
            $applicantEmail = trim((string)($p['email'] ?? ''));

            $geburtsdatumDate = $dobSql;
            if (!$geburtsdatumDate && !empty($p['geburtsdatum']) && function_exists('norm_date_dmy_to_ymd')) {
                $dobTmp = norm_date_dmy_to_ymd((string)$p['geburtsdatum']);
                if ($dobTmp !== '') {
                    $geburtsdatumDate = $dobTmp;
                }
            }

            $insPers = $pdo->prepare("
                INSERT INTO personal (
                    application_id,
                    name,
                    vorname,
                    geschlecht,
                    geburtsdatum,
                    geburtsort_land,
                    staatsang,
                    strasse,
                    plz,
                    wohnort,
                    telefon,
                    email,
                    dsgvo_ok,
                    created_at,
                    updated_at
                ) VALUES (
                    :id,
                    :name,
                    :vorname,
                    :g,
                    :gebdat,
                    :ort,
                    :staat,
                    :strasse,
                    :plz,
                    :wohnort,
                    :tel,
                    :mail,
                    :dsgvo,
                    NOW(),
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    name            = VALUES(name),
                    vorname         = VALUES(vorname),
                    geschlecht      = VALUES(geschlecht),
                    geburtsdatum    = VALUES(geburtsdatum),
                    geburtsort_land = VALUES(geburtsort_land),
                    staatsang       = VALUES(staatsang),
                    strasse         = VALUES(strasse),
                    plz             = VALUES(plz),
                    wohnort         = VALUES(wohnort),
                    telefon         = VALUES(telefon),
                    email           = VALUES(email),
                    dsgvo_ok        = VALUES(dsgvo_ok),
                    updated_at      = NOW()
            ");

            $insPers->execute([
                ':id'      => $appId,
                ':name'    => (string)($p['name']            ?? ''),
                ':vorname' => (string)($p['vorname']         ?? ''),
                ':g'       => (string)($p['geschlecht']      ?? ''),
                ':gebdat'  => $geburtsdatumDate,
                ':ort'     => (string)($p['geburtsort_land'] ?? ''),
                ':staat'   => (string)($p['staatsang']       ?? ''),
                ':strasse' => (string)($p['strasse']         ?? ''),
                ':plz'     => (string)($p['plz']             ?? ''),
                ':wohnort' => (string)($p['wohnort']         ?? 'Oldenburg (Oldb)'),
                ':tel'     => (string)($p['telefon']         ?? ''),
                ':mail'    => $applicantEmail,
                ':dsgvo'   => (isset($p['dsgvo_ok']) && $p['dsgvo_ok'] === '1') ? 1 : 0,
            ]);

            // ---------- contacts ----------
            $pdo->prepare("DELETE FROM contacts WHERE application_id = :id")
                ->execute([':id' => $appId]);

            $contacts = $p['contacts'] ?? [];
            if (is_array($contacts)) {
                $insC = $pdo->prepare("
                    INSERT INTO contacts (application_id, rolle, name, tel, mail, notiz, created_at)
                    VALUES (:id, :rolle, :name, :tel, :mail, :notiz, NOW())
                ");
                foreach ($contacts as $c) {
                    $nameC = trim((string)($c['name'] ?? ''));
                    $telC  = trim((string)($c['tel']  ?? ''));
                    $mailC = trim((string)($c['mail'] ?? ''));
                    $notiz = trim((string)($c['notiz']?? ''));
                    $rolle = trim((string)($c['rolle']?? ''));

                    if ($rolle === '' && $nameC === '' && $telC === '' && $mailC === '' && $notiz === '') {
                        continue;
                    }

                    $insC->execute([
                        ':id'    => $appId,
                        ':rolle' => $rolle,
                        ':name'  => $nameC,
                        ':tel'   => $telC,
                        ':mail'  => $mailC,
                        ':notiz' => $notiz,
                    ]);
                }
            }

            // ---------- school ----------
            global $INTERESSEN;

            $schule_besucht = 1;

            $schule_jahre = null;
            if (($s['jahre_in_de'] ?? '') !== '' && ctype_digit((string)$s['jahre_in_de'])) {
                $schule_jahre = (int)$s['jahre_in_de'];
            }

            $seit_monat = null;
            if (($s['seit_monat'] ?? '') !== '' && ctype_digit((string)$s['seit_monat'])) {
                $seit_monat = (int)$s['seit_monat'];
            }
            $seit_jahr = null;
            if (($s['seit_jahr'] ?? '') !== '' && ctype_digit((string)$s['seit_jahr'])) {
                $seit_jahr = (int)$s['seit_jahr'];
            }

            $nivRaw = trim((string)($s['deutsch_niveau'] ?? ''));
            $allowedCodes = ['kein','A1','A2','B1','B2','C1','C2'];
            $deutsch_niveau = null;
            if ($nivRaw !== '') {
                if (in_array($nivRaw, $allowedCodes, true)) {
                    $deutsch_niveau = $nivRaw;
                } elseif (preg_match('/^(kein|A1|A2|B1|B2|C1|C2)\b/u', $nivRaw, $m)) {
                    $deutsch_niveau = $m[1];
                }
            }

            $deutsch_jahre = null;

            $interessenArr = $s['interessen'] ?? [];
            $interessenLabels = [];
            if (is_array($interessenArr)) {
                foreach ($interessenArr as $key) {
                    $keyStr = (string)$key;
                    $interessenLabels[] = $INTERESSEN[$keyStr] ?? $keyStr;
                }
            }
            $interessenStr = $interessenLabels ? implode(', ', $interessenLabels) : null;

            $insSchool = $pdo->prepare("
                INSERT INTO school (
                    application_id,
                    schule_besucht,
                    schule_jahre,
                    seit_monat,
                    seit_jahr,
                    deutsch_niveau,
                    deutsch_jahre,
                    interessen,
                    created_at,
                    updated_at
                ) VALUES (
                    :id,
                    :besucht,
                    :jahre,
                    :monat,
                    :jahr,
                    :niv,
                    :dj,
                    :ints,
                    NOW(),
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    schule_besucht = VALUES(schule_besucht),
                    schule_jahre   = VALUES(schule_jahre),
                    seit_monat     = VALUES(seit_monat),
                    seit_jahr      = VALUES(seit_jahr),
                    deutsch_niveau = VALUES(deutsch_niveau),
                    deutsch_jahre  = VALUES(deutsch_jahre),
                    interessen     = VALUES(interessen),
                    updated_at     = NOW()
            ");

            $insSchool->execute([
                ':id'      => $appId,
                ':besucht' => $schule_besucht,
                ':jahre'   => $schule_jahre,
                ':monat'   => $seit_monat,
                ':jahr'    => $seit_jahr,
                ':niv'     => $deutsch_niveau,
                ':dj'      => $deutsch_jahre,
                ':ints'    => $interessenStr,
            ]);

            // ---------- audit_log ----------
            $meta = [
                'ua'      => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            ];
            $insAudit = $pdo->prepare("
                INSERT INTO audit_log (application_id, event, meta_json, created_at)
                VALUES (:id, 'submitted', :meta, NOW())
            ");
            $insAudit->execute([
                ':id'   => $appId,
                ':meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            ]);

            $pdo->commit();

            // Session komplett leeren & zurück zur Startseite
            $_SESSION = [];
            session_regenerate_id(true);

            header('Location: /index.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('form_review – submit error: '.$e->getMessage());
            if (function_exists('flash_set')) {
                flash_set('danger', 'Beim Übermitteln ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.');
            }
        }
    }
}

// ===== Daten für Anzeige =====
$p = $_SESSION['form']['personal'] ?? [];
$s = $_SESSION['form']['school']   ?? [];
$u = $_SESSION['form']['upload']   ?? [];

// Upload-Status: erst aus Session, dann – falls möglich – aus DB
$hasZeugnis    = !empty($u['zeugnis'] ?? null);
$hasLebenslauf = !empty($u['lebenslauf'] ?? null);

try {
    $token = current_access_token()
        ?: (string)($_SESSION['access']['token'] ?? ($_SESSION['access_token'] ?? ''));

    if ($token !== '') {
        $pdo = db();
        $st  = $pdo->prepare("SELECT id FROM applications WHERE token = :t LIMIT 1");
        $st->execute([':t' => $token]);
        $appId = $st->fetchColumn();

        if ($appId) {
            $st2 = $pdo->prepare("SELECT typ FROM uploads WHERE application_id = :id");
            $st2->execute([':id' => $appId]);
            while ($row = $st2->fetch(PDO::FETCH_ASSOC)) {
                $typ = (string)($row['typ'] ?? '');
                if ($typ === 'zeugnis') {
                    $hasZeugnis = true;
                } elseif ($typ === 'lebenslauf') {
                    $hasLebenslauf = true;
                }
            }
        }
    }
} catch (Throwable $e) {
    error_log('form_review uploads-check: '.$e->getMessage());
}

// Header-Infos (Status/Token)
$saveInfo = $_SESSION['last_save'] ?? null;
$hdr = [
  'title'   => 'Schritt 4/4 – Zusammenfassung & Bewerbung',
  'status'  => ($saveInfo && ($saveInfo['ok'] ?? false)) ? 'success' : null,
  'message' => ($saveInfo && ($saveInfo['ok'] ?? false)) ? 'Daten gespeichert.' : null,
  'token'   => current_access_token() ?: null,
];
require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';

// Geschlecht-Label
$gMap = ['m' => 'männlich', 'w' => 'weiblich', 'd' => 'divers'];
$gKey = (string)($p['geschlecht'] ?? '');
$gLbl = $gMap[$gKey] ?? ($gKey !== '' ? $gKey : '–');

// Schule Anzeige (nutze Session-Label/Freitext)
$schoolDisplay = '–';
if (!empty($s['schule_label'])) {
    $schoolDisplay = (string)$s['schule_label'];
} elseif (!empty($s['schule_freitext'])) {
    $schoolDisplay = (string)$s['schule_freitext'];
} elseif (!empty($s['schule_aktuell'])) {
    $schoolDisplay = (string)$s['schule_aktuell'];
}

// Seit wann an Schule Anzeige
$sinceDisplay = '–';
if (!empty($s['seit_wann_schule'])) {
    $sinceDisplay = (string)$s['seit_wann_schule'];
} else {
    $parts = [];
    if (!empty($s['seit_monat'])) $parts[] = (string)$s['seit_monat'];
    if (!empty($s['seit_jahr']))  $parts[] = (string)$s['seit_jahr'];
    $sinceDisplay = $parts ? implode('.', $parts) : '–';
}

// Interessen Anzeige
$interessenLbls = [];
if (is_array($s['interessen'] ?? null)) {
    foreach (($s['interessen'] ?? []) as $k) {
        $kStr = (string)$k;
        if (isset($INTERESSEN[$kStr])) $interessenLbls[] = $INTERESSEN[$kStr];
        else $interessenLbls[] = $kStr;
    }
}
$interessenDisplay = $interessenLbls ? implode(', ', $interessenLbls) : '–';

// Neue Felder Schritt 1
$weitereAngaben = norm_space($p['weitere_angaben'] ?? '');

?>
<div class="container py-4">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
        <div>
          <h1 class="h4 mb-1">Schritt 4/4 – Zusammenfassung &amp; Bewerbung</h1>
          <div class="text-muted">Bitte prüfen Sie Ihre Angaben. Mit „Bewerben“ senden Sie die Daten ab.</div>
        </div>
      </div>

      <?php if ($readonly): ?>
        <div class="alert alert-warning">
          Diese Bewerbung wurde bereits abgeschickt. Die Angaben können nur noch angesehen,
          aber nicht mehr geändert oder erneut eingereicht werden.
        </div>
      <?php endif; ?>

      <div class="alert alert-info">
        <p class="mb-2">Liebe Schülerin, lieber Schüler,</p>
        <p class="mb-2">
          wenn Sie auf <strong>„bewerben“</strong> klicken, haben Sie sich für die
          <strong>BES Sprache und Integration</strong> an einer Oldenburger BBS beworben.
        </p>
        <p class="mb-2">
          Es handelt sich noch nicht um eine finale Anmeldung, sondern um eine <strong>Bewerbung</strong>.
          Nach dem <strong>20.02.</strong> erhalten Sie die Information, ob / an welcher BBS Sie aufgenommen werden.
          Bitte prüfen Sie regelmäßig Ihren Briefkasten und Ihr E-Mail-Postfach. Bitte achten Sie darauf, dass am
          Briefkasten Ihr Name sichtbar ist, damit Sie Briefe bekommen können.
        </p>
        <p class="mb-1">Sie erhalten mit der Zusage der Schule die Aufforderung, diese Dateien nachzureichen
          (falls Sie es heute noch nicht hochgeladen haben):</p>
        <ul class="mb-0"><li>letztes Halbjahreszeugnis</li></ul>
      </div>

      <div class="accordion" id="reviewAccordion">

        <!-- Persönliche Daten -->
        <div class="accordion-item">
          <h2 class="accordion-header" id="hPersonal">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#cPersonal" aria-expanded="true" aria-controls="cPersonal">
              Persönliche Daten
            </button>
          </h2>
          <div id="cPersonal" class="accordion-collapse collapse show" aria-labelledby="hPersonal" data-bs-parent="#reviewAccordion">
            <div class="accordion-body">
              <div class="row g-3">
                <?= dl_row('Name', show_val((string)($p['name'] ?? ''))) ?>
                <?= dl_row('Vorname', show_val((string)($p['vorname'] ?? ''))) ?>
                <?= dl_row('Geschlecht', h($gLbl)) ?>
                <?= dl_row('Geboren am', show_val((string)($p['geburtsdatum'] ?? ''))) ?>
                <?= dl_row('Geburtsort / Geburtsland', show_val((string)($p['geburtsort_land'] ?? ''))) ?>
                <?= dl_row('Staatsangehörigkeit', show_val((string)($p['staatsang'] ?? ''))) ?>
                <?= dl_row('Straße, Nr.', show_val((string)($p['strasse'] ?? ''))) ?>
                <?= dl_row('PLZ / Wohnort', show_val(trim((string)($p['plz'] ?? '') . ' ' . (string)($p['wohnort'] ?? 'Oldenburg (Oldb)')))) ?>
                <?= dl_row('Telefon', show_val((string)($p['telefon'] ?? ''))) ?>
                <?= dl_row('E-Mail (Schüler*in, optional)', show_val((string)($p['email'] ?? ''))) ?>

                <div class="col-12">
                  <div class="small text-muted">Weitere Angaben (z. B. Förderstatus)</div>
                  <div class="fw-semibold"><?= $weitereAngaben !== '' ? nl2br(h($weitereAngaben)) : '–' ?></div>
                </div>
              </div>

              <hr class="my-4">

              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="fw-semibold">Weitere Kontakte</div>
                <div class="text-muted small">optional</div>
              </div>

              <?php
                $contacts = $p['contacts'] ?? [];
                $hasContacts = is_array($contacts) && count($contacts) > 0;
              ?>
              <?php if ($hasContacts): ?>
                <div class="table-responsive">
                  <table class="table table-sm align-middle">
                    <thead class="table-light">
                      <tr>
                        <th style="width:14rem">Rolle</th>
                        <th style="width:22rem">Name / Einrichtung</th>
                        <th style="width:16rem">Telefon</th>
                        <th style="width:22rem">E-Mail</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($contacts as $c): ?>
                        <?php
                          $rolle = trim((string)($c['rolle'] ?? ''));
                          $name  = trim((string)($c['name']  ?? ''));
                          $tel   = trim((string)($c['tel']   ?? ''));
                          $mail  = trim((string)($c['mail']  ?? ''));
                          $notiz = trim((string)($c['notiz'] ?? ''));

                          if ($rolle === '' && $name === '' && $tel === '' && $mail === '' && $notiz === '') continue;
                        ?>
                        <tr>
                          <td><?= h($rolle) ?></td>
                          <td><?= h($name) ?></td>
                          <td><?= h($tel) ?></td>
                          <td><?= h($mail) ?></td>
                        </tr>
                        <tr class="table-light">
                          <td colspan="4">
                            <span class="text-muted small">Notiz:</span>
                            <span class="fw-semibold"><?= $notiz !== '' ? h($notiz) : '–' ?></span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="text-muted">–</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Schule & Interessen -->
        <div class="accordion-item">
          <h2 class="accordion-header" id="hSchool">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cSchool" aria-expanded="false" aria-controls="cSchool">
              Schule &amp; Interessen
            </button>
          </h2>
          <div id="cSchool" class="accordion-collapse collapse" aria-labelledby="hSchool" data-bs-parent="#reviewAccordion">
            <div class="accordion-body">
              <div class="row g-3">
                <?= dl_row('Aktuelle Schule', show_val($schoolDisplay), 'col-12') ?>
                <?= dl_row('Verantwortliche*r Lehrer*in', show_val((string)($s['klassenlehrer'] ?? ''))) ?>
                <?= dl_row('E-Mail Lehrkraft', show_val((string)($s['mail_lehrkraft'] ?? ''))) ?>
                <?= dl_row('Seit wann an der Schule', show_val($sinceDisplay)) ?>
                <?= dl_row('Jahre in Deutschland', show_val((string)($s['jahre_in_de'] ?? ''))) ?>
                <?= dl_row('Familiensprache / Erstsprache', show_val((string)($s['familiensprache'] ?? ''))) ?>
                <?= dl_row('Deutsch-Niveau', show_val((string)($s['deutsch_niveau'] ?? ''))) ?>
                <?= dl_row('Schule im Herkunftsland', show_val((string)($s['schule_herkunft'] ?? ''))) ?>

                <?php if (($s['schule_herkunft'] ?? '') === 'ja'): ?>
                  <?= dl_row('Jahre Schule im Herkunftsland', show_val((string)($s['jahre_schule_herkunft'] ?? ''))) ?>
                <?php endif; ?>

                <?= dl_row('Interessen', show_val($interessenDisplay), 'col-12') ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Unterlagen -->
        <div class="accordion-item">
          <h2 class="accordion-header" id="hUploads">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cUploads" aria-expanded="false" aria-controls="cUploads">
              Unterlagen
            </button>
          </h2>
          <div id="cUploads" class="accordion-collapse collapse" aria-labelledby="hUploads" data-bs-parent="#reviewAccordion">
            <div class="accordion-body">
              <div class="row g-3">
                <?= dl_row('Halbjahreszeugnis', $hasZeugnis ? '<span class="badge text-bg-success">hochgeladen</span>' : '<span class="badge text-bg-secondary">nicht hochgeladen</span>') ?>
                <?= dl_row('Lebenslauf', $hasLebenslauf ? '<span class="badge text-bg-success">hochgeladen</span>' : '<span class="badge text-bg-secondary">nicht hochgeladen</span>') ?>
                <?= dl_row('Später nachreichen', (($u['zeugnis_spaeter'] ?? '0') === '1') ? 'Ja' : 'Nein', 'col-12') ?>
              </div>
            </div>
          </div>
        </div>

      </div>

      <?php if ($readonly): ?>
        <div class="mt-4 d-flex gap-2 flex-wrap">
          <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="reset">
            <button class="btn btn-outline-secondary">Zur Startseite</button>
          </form>

          <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="new_application">
            <button class="btn btn-primary">Weitere Bewerbung einreichen</button>
          </form>
        </div>
      <?php else: ?>
        <form method="post" action="" class="mt-4 d-flex gap-2 flex-wrap">
          <?php csrf_field(); ?>
          <input type="hidden" name="action" value="submit">
          <a href="/form_upload.php" class="btn btn-outline-secondary">Zurück</a>
          <button class="btn btn-success">Bewerben</button>
        </form>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/partials/footer.php'; ?>
