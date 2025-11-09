<?php
// public/form_review.php
require __DIR__ . '/wizard/_common.php';
require_step('review');

// Wenn „Bewerben“ gedrrückt wurde:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

  // TODO: Speicherung (DB) / E-Mail (PHPMailer) / Ticket-System
  // save_to_db($_SESSION['form']);
  // send_mail($_SESSION['form']);

  // Session aufräumen (optional: Referenznummer generieren)
  $ref = 'ANM-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)),0,6);
  $_SESSION['last_ref'] = $ref;
  unset($_SESSION['form']);

  header('Content-Type: text/html; charset=UTF-8');
  ?>
  <!doctype html>
  <html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bestätigung</title>
    <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/form.css">
  </head>
  <body class="bg-light">
    <div class="container py-5">
      <div class="card shadow border-0 rounded-4">
        <div class="card-body p-4 p-md-5">
          <h1 class="h4 text-success mb-3">Vielen Dank! Ihre Bewerbung wurde übermittelt.</h1>
          <p class="mb-3">Referenz: <strong><?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?></strong></p>
          <a class="btn btn-primary" href="/index.php">Zur Startseite</a>
        </div>
      </div>
    </div>
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
  </body>
  </html>
  <?php
  exit;
}

// Daten für Anzeige
$p = $_SESSION['form']['personal'] ?? [];
$s = $_SESSION['form']['school']   ?? [];
$u = $_SESSION['form']['upload']   ?? [];
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Schritt 4/4 – Zusammenfassung & Bewerbung</title>
  <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/form.css">
  <style>.card{border-radius:1rem}</style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-3">Schritt 4/4 – Zusammenfassung & Bewerbung</h1>

      <!-- Hinweistext (dein gewünschter Inhalt) -->
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
        <ul class="mb-0">
          <li>letztes Halbjahreszeugnis</li>
        </ul>
      </div>

      <p class="text-muted">Bitte prüfen Sie Ihre Angaben. Mit „Bewerben“ senden Sie die Daten ab.</p>

      <!-- Zusammenfassung aller Eingaben -->
      <div class="row g-3">
        <div class="col-12"><strong>Persönliche Daten</strong></div>
        <div class="col-md-6">Name: <?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-6">Vorname: <?= htmlspecialchars($p['vorname'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-6">Geschlecht: <?= htmlspecialchars($p['geschlecht'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-6">Geboren am: <?= htmlspecialchars($p['geburtsdatum'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-6">Geburtsort/Land: <?= htmlspecialchars($p['geburtsort_land'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-6">Staatsangehörigkeit: <?= htmlspecialchars($p['staatsang'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-8">Straße, Nr.: <?= htmlspecialchars($p['strasse'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-4">PLZ: <?= htmlspecialchars($p['plz'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-6">Wohnort: <?= htmlspecialchars($p['wohnort'] ?? 'Oldenburg (Oldb)', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-6">Telefon: <?= htmlspecialchars($p['telefon'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-6">E-Mail: <?= htmlspecialchars($p['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-12">Weitere Kontakte: <?= nl2br(htmlspecialchars($p['kontakt'] ?? '', ENT_QUOTES, 'UTF-8')) ?></div>

        <div class="col-12 mt-3"><strong>Schule & Interessen</strong></div>
        <div class="col-md-6">
          Aktuelle Schule:
          <?php
            $curSchoolKey = $s['schule_aktuell'] ?? '';
            echo htmlspecialchars($SCHULEN[$curSchoolKey] ?? $curSchoolKey, ENT_QUOTES, 'UTF-8');
          ?>
        </div>
        <div class="col-md-6">Klassenlehrer*in: <?= htmlspecialchars($s['klassenlehrer'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-6">E-Mail Lehrkraft: <?= htmlspecialchars($s['mail_lehrkraft'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-6">
          Seit wann an der Schule:
          <?php
            // Bevorzugt das normalisierte Feld, sonst Rekonstruktion
            if (!empty($s['seit_wann_schule'])) {
              echo htmlspecialchars($s['seit_wann_schule'], ENT_QUOTES, 'UTF-8');
            } else {
              $parts = [];
              if (!empty($s['seit_monat'])) $parts[] = $s['seit_monat'];
              if (!empty($s['seit_jahr']))  $parts[] = $s['seit_jahr'];
              echo $parts ? htmlspecialchars(implode('.', $parts), ENT_QUOTES, 'UTF-8') : '-';
            }
          ?>
        </div>
        <div class="col-md-6">Jahre in Deutschland: <?= htmlspecialchars($s['jahre_in_de'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-6">Schule im Herkunftsland: <?= htmlspecialchars($s['schule_herkunft'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <?php if (($s['schule_herkunft'] ?? '') === 'ja'): ?>
          <div class="col-md-6">Jahre Schule im Herkunftsland: <?= htmlspecialchars($s['jahre_schule_herkunft'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="col-md-6">Familiensprache: <?= htmlspecialchars($s['familiensprache'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-md-6">Deutsch-Niveau: <?= htmlspecialchars($s['deutsch_niveau'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="col-12">
          Interessen:
          <?php
            $lbls = [];
            foreach (($s['interessen'] ?? []) as $k) {
              $lbls[] = $INTERESSEN[$k] ?? $k;
            }
            echo $lbls ? htmlspecialchars(implode(', ', $lbls), ENT_QUOTES, 'UTF-8') : '-';
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

</body>
</html>
