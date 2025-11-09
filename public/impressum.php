<?php
// public/impressum.php
// Wichtig: Datei als UTF-8 ohne BOM speichern!
mb_internal_encoding('UTF-8');
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Impressum</title>
  <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/form.css">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">

      <h1 class="h3 mb-3">Impressum</h1>

      <h2 class="h5 mt-4">Diensteanbieter</h2>
      <p>
        <strong>Stadt ***</strong><br>
        Berufsbildende Schulen<br>
        (genaue Anschrift eintragen)<br>
        Telefon: (bitte ergänzen)<br>
        E-Mail: (bitte ergänzen)
      </p>

      <h2 class="h5 mt-4">Vertretungsberechtigt</h2>
      <p>
        (z. B. Oberbürgermeister/in der Stadt ****<br>
        oder Schulleitung der jeweiligen BBS)
      </p>

      <h2 class="h5 mt-4">Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV</h2>
      <p>(Name, Funktion, Kontakt, z. B. Schulleitung der BBS oder Pressestelle)</p>

      <h2 class="h5 mt-4">Umsatzsteuer-ID</h2>
      <p>(sofern vorhanden; ansonsten kann dieser Abschnitt entfallen)</p>

      <h2 class="h5 mt-4">Aufsichtsbehörde</h2>
      <p>(zuständige Kommunalaufsicht / Schulbehörde, z. B. Regionalabteilung der Landesschulbehörde)</p>

      <h2 class="h5 mt-4">Haftung für Inhalte</h2>
      <p>
        Die Inhalte unserer Seiten wurden mit größter Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit
        und Aktualität der Inhalte können wir jedoch keine Gewähr übernehmen. Als öffentliche Stelle sind wir
        gemäß § 7 Abs. 1 TMG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich.
      </p>

      <h2 class="h5 mt-4">Haftung für Links</h2>
      <p>
        Unser Angebot enthält keine externen Inhalte, die personenbezogene Daten an Dritte übertragen.
        Soweit wir auf Informationsangebote anderer öffentlicher Stellen verlinken, übernehmen wir keine
        Verantwortung für deren Inhalte.
      </p>

      <h2 class="h5 mt-4">Urheberrecht</h2>
      <p>
        Die durch die Stadt Oldenburg erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen
        Urheberrecht. Beiträge Dritter sind als solche gekennzeichnet. Die Vervielfältigung, Bearbeitung,
        Verbreitung und jede Art der Verwertung außerhalb der Grenzen des Urheberrechtes bedürfen der
        schriftlichen Zustimmung der Stadt Oldenburg oder des jeweiligen Rechteinhabers.
      </p>

      <h2 class="h5 mt-4">Barrierefreiheit</h2>
      <p>
        Wir sind bemüht, dieses Online-Angebot möglichst barrierefrei zu gestalten. Sollten Sie auf Barrieren
        stoßen oder Verbesserungsvorschläge haben, wenden Sie sich bitte an die im Impressum genannte Stelle.
      </p>

      <p class="mt-4 small text-muted">
        Stand: <?= date('d.m.Y') ?> – Diese Angaben gelten für das Online-Formular „BES Sprache und Integration“.
      </p>

      <a href="/" class="btn btn-outline-secondary mt-2">Zur Startseite</a>

    </div>
  </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
