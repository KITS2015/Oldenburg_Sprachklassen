<?php
// public/form_review.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_step('review');

// ===== Wenn „Bewerben“ gedrückt wurde =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

  // TODO: Hier echte Persistierung/Benachrichtigung einbauen
  // save_to_db($_SESSION['form']);
  // send_mail($_SESSION['form']);

  // Referenz erzeugen, Session aufräumen
  $ref = 'ANM-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
  $_SESSION['last_ref'] = $ref;
  unset($_SESSION['form']);

  // Header-Infos (Bestätigungsseite)
  $hdr = [
    'title'   => 'Bestätigung',
    'status'  => 'success',
    'message' => 'Bewerbung übermittelt',
    'token'   => current_access_token() ?: null,
  ];
  require __DIR__ . '/partials/header.php';
  ?>
  <div class="container py-5">
    <div class="card shadow border-0 rounded-4">
      <div class="card-body p-4 p-md-5">
        <h1 class="h4 text-success mb-3">Vielen Dank! Ihre Bewerbung wurde übermittelt.</h1>
        <p class="mb-3">Referenz: <strong><?= h($ref) ?></strong></p>
        <a class="btn btn-primary" href="/index.php">Zur Startseite</a>
      </div>
    </div>
  </div>
  <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
  <?php include __DIR__ . '/partials/footer.php'; ?>
  </body></html>
  <?php
  exit;
}

// ===== Daten für Anzeige =====
$p = $_SESSION['form']['personal'] ?? [];
$s = $_SESSION['form']['school']   ?? [];
$u = $_SESSION['form']['upload']   ?? [];

// Header-Infos (oben die grüne Statusleiste + Token-Badge)
$saveInfo = $_SESSION['last_save'] ?? null;
$hdr = [
  'title'   => 'Schritt 4/4 – Zusammenfassung & Bewerbung',
  'status'  => ($saveInfo && ($saveInfo['ok'] ?? false)) ? 'success' : null,
  'message' => ($saveInfo && ($saveInfo['ok'] ?? false)) ? 'Daten gespeichert.' : null,
  'token'   => current_access_token() ?: null,
];
require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';       // App-Header (Status/Token)
?>

<div class="container py-4">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-3">Schritt 4/4 – Zusammenfassung &amp; Bewerbung</h1>

      <!-- Hinweistext -->
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

      <p class="text-muted">Bitte prüfen Sie Ihre Angaben. Mit „Bewerben“ senden Sie die Daten ab.</p>

      <!-- Zusammenfassung aller Eingaben -->
      <div class="row g-3">
        <div class="col-12"><strong>Persönliche Daten</strong></div>
        <div class="col-md-6">Name: <?= h($p['name'] ?? '') ?></div>
        <div class="col-md-6">Vorname: <?= h($p['vorname'] ?? '') ?></div>
        <div class="col-md-6">Geschlecht: <?= h($p['geschlecht'] ?? '') ?></div>
        <div class="col-md-6">Geboren am: <?= h($p['geburtsdatum'] ?? '') ?></div>
        <div class="col-md-6">Geburtsort/Land: <?= h($p['geburtsort_land'] ?? '') ?></div>
        <div class="col-md-6">Staatsangehörigkeit: <?= h($p['staatsang'] ?? '') ?></div>
        <div class="col-md-8">Straße, Nr.: <?= h($p['strasse'] ?? '') ?></div>
        <div class="col-md-4">PLZ: <?= h($p['plz'] ?? '') ?></div>
        <div class="col-md-6">Wohnort: <?= h($p['wohnort'] ?? 'Oldenburg (Oldb)') ?></div>
        <div class="col-md-6">Telefon: <?= h($p['telefon'] ?? '') ?></div>
        <div class="col-md-6">E-Mail: <?= h($p['email'] ?? '') ?></div>

        <div class="col-12">
          <div class="mt-2"><em>Weitere Kontakte:</em></div>
          <?php
            $contacts = $p['contacts'] ?? [];
            if ($contacts && is_array($contacts)):
          ?>
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Rolle</th><th>Name / Einrichtung</th><th>Telefon</th><th>E-Mail</th><th>Notiz</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($contacts as $c): ?>
                  <tr>
                    <td><?= h($c['rolle'] ?? '') ?></td>
                    <td><?= h($c['name']  ?? '') ?></td>
                    <td><?= h($c['tel']   ?? '') ?></td>
                    <td><?= h($c['mail']  ?? '') ?></td>
                    <td><?= h($c['notiz'] ?? '') ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div>-</div>
          <?php endif; ?>
        </div>

        <div class="col-12 mt-3"><strong>Schule &amp; Interessen</strong></div>
        <div class="col-md-6">
          Aktuelle Schule:
          <?php
            $curSchoolKey = $s['schule_aktuell'] ?? '';
            echo h($SCHULEN[$curSchoolKey] ?? $curSchoolKey);
          ?>
        </div>
        <div class="col-md-6">Klassenlehrer*in: <?= h($s['klassenlehrer'] ?? '') ?></div>
        <div class="col-md-6">E-Mail Lehrkraft: <?= h($s['mail_lehrkraft'] ?? '') ?></div>
        <div class="col-md-6">
          Seit wann an der Schule:
          <?php
            if (!empty($s['seit_wann_schule'])) {
              echo h($s['seit_wann_schule']);
            } else {
              $parts = [];
              if (!empty($s['seit_monat'])) $parts[] = $s['seit_monat'];
              if (!empty($s['seit_jahr']))  $parts[] = $s['seit_jahr'];
              echo $parts ? h(implode('.', $parts)) : '-';
            }
          ?>
        </div>
        <div class="col-md-6">Jahre in Deutschland: <?= h($s['jahre_in_de'] ?? '') ?></div>
        <div class="col-md-6">Schule im Herkunftsland: <?= h($s['schule_herkunft'] ?? '') ?></div>
        <?php if (($s['schule_herkunft'] ?? '') === 'ja'): ?>
          <div class="col-md-6">Jahre Schule im Herkunftsland: <?= h($s['jahre_schule_herkunft'] ?? '') ?></div>
        <?php endif; ?>
        <div class="col-md-6">Familiensprache: <?= h($s['familiensprache'] ?? '') ?></div>
        <div class="col-md-6">Deutsch-Niveau: <?= h($s['deutsch_niveau'] ?? '') ?></div>
        <div class="col-12">
          Interessen:
          <?php
            $lbls = [];
            foreach (($s['interessen'] ?? []) as $k) $lbls[] = $INTERESSEN[$k] ?? $k;
            echo $lbls ? h(implode(', ', $lbls)) : '-';
          ?>
        </div>

        <div class="col-12 mt-3"><strong>Unterlagen</strong></div>
        <div class="col-md-6">Halbjahreszeugnis: <?= !empty($u['zeugnis']) ? 'hochgeladen' : 'nicht hochgeladen' ?></div>
        <div class="col-md-6">Lebenslauf: <?= !empty($u['lebenslauf']) ? 'hochgeladen' : 'nicht hochgeladen' ?></div>
        <div class="col-12">Später nachreichen: <?= (($u['zeugnis_spaeter'] ?? '0') === '1') ? 'Ja' : 'Nein' ?></div>
      </div>

      <form method="post" action="" class="mt-4 d-flex gap-2">
        <?php csrf_field(); ?>
        <a href="/form_upload.php" class="btn btn-outline-secondary">Zurück</a>
        <button class="btn btn-success">Bewerben</button>
      </form>
    </div>
  </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/partials/footer.php'; ?>
