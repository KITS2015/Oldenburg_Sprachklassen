<?php
// public/datenschutz.php
// Wichtig: Datei muss als UTF-8 (ohne BOM) gespeichert werden.
mb_internal_encoding('UTF-8');
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Datenschutz</title>
  <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/form.css">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h3 mb-3">Datenschutzhinweise für die Online-Bewerbung „BES Sprache und Integration“</h1>

      <h2 class="h5 mt-4">1. Verantwortliche Stelle</h2>
      <p>
        <strong>Stadt Oldenburg / Berufsbildende Schulen</strong><br>
        (Bitte genaue Dienststellen-/Schulbezeichnung, Anschrift, Telefon, E-Mail eintragen)
      </p>

      <h2 class="h5 mt-4">2. Datenschutzbeauftragte*r</h2>
      <p>(Kontaktdaten der/des behördlichen Datenschutzbeauftragten eintragen)</p>

      <h2 class="h5 mt-4">3. Zwecke der Verarbeitung</h2>
      <ul>
        <li>Entgegennahme und Bearbeitung Ihrer Bewerbung zur Aufnahme in die Sprachlernklasse („BES Sprache und Integration“)</li>
        <li>Kommunikation mit Ihnen (Rückfragen, Mitteilungen zur Aufnahmeentscheidung)</li>
        <li>Schulorganisatorische Planung (Zuweisung zu einer BBS)</li>
      </ul>

      <h2 class="h5 mt-4">4. Rechtsgrundlagen</h2>
      <ul>
        <li>Art. 6 Abs. 1 lit. e DSGVO i. V. m. den schulrechtlichen Vorschriften des Landes Niedersachsen</li>
        <li>Art. 6 Abs. 1 lit. c DSGVO (Erfüllung rechtlicher Verpflichtungen)</li>
        <li>Art. 6 Abs. 1 lit. a DSGVO (Einwilligung), soweit freiwillige Angaben/Uploads erfolgen</li>
      </ul>

      <h2 class="h5 mt-4">5. Kategorien personenbezogener Daten</h2>
      <ul>
        <li>Stammdaten (Name, Vorname, Geburtsdaten, Staatsangehörigkeit, Anschrift, Kontaktdaten)</li>
        <li>Schulische Informationen (aktuelle Schule, Sprachniveau, Interessen)</li>
        <li>Optionale Unterlagen (z. B. letztes Halbjahreszeugnis)</li>
        <li>Zusatzkontakte (Eltern/Betreuer/Einrichtungen)</li>
      </ul>

      <h2 class="h5 mt-4">6. Empfänger</h2>
      <p>
        Innerhalb der Zuständigkeit der Stadt Oldenburg und der berufsbildenden Schulen. Eine Übermittlung an Dritte erfolgt nur,
        soweit rechtlich erforderlich (z. B. Schulbehörden) oder mit Ihrer Einwilligung.
      </p>

      <h2 class="h5 mt-4">7. Drittlandübermittlung</h2>
      <p>Es findet keine Übermittlung in Drittländer statt.</p>

      <h2 class="h5 mt-4">8. Speicherdauer</h2>
      <p>
        Ihre Daten werden für die Dauer des Bewerbungs- bzw. Aufnahmeverfahrens und gemäß den gesetzlichen Aufbewahrungsfristen
        gespeichert und anschließend gelöscht.
      </p>

      <h2 class="h5 mt-4">9. Ihre Rechte</h2>
      <ul>
        <li>Auskunft (Art. 15 DSGVO), Berichtigung (Art. 16), Löschung (Art. 17), Einschränkung (Art. 18)</li>
        <li>Widerspruch (Art. 21) gegen Verarbeitungen im öffentlichen Interesse</li>
        <li>Widerruf erteilter Einwilligungen (Art. 7 Abs. 3) mit Wirkung für die Zukunft</li>
        <li>Beschwerderecht bei der Aufsichtsbehörde: Landesbeauftragte*r für den Datenschutz Niedersachsen</li>
      </ul>

      <h2 class="h5 mt-4">10. Hosting &amp; Protokolle</h2>
      <p>
        Die Anwendung wird auf Servern der Stadt bzw. im kommunalen Rechenzentrum betrieben. Es werden nur technisch notwendige Daten
        verarbeitet (z. B. Server-Logfiles zur Fehlersuche). Keine Einbindung externer CDNs.
        Es wird ausschließlich ein sprachbezogenes Cookie gesetzt.
      </p>

      <h2 class="h5 mt-4">11. Cookies</h2>
      <ul>
        <li><strong>lang</strong> – speichert die ausgewählte Sprache (Gültigkeit 12 Monate). Zweck: Benutzerfreundlichkeit.</li>
        <li>PHP-Session – technisch erforderlich für den Formular-Ablauf, wird beim Beenden der Sitzung gelöscht.</li>
      </ul>

      <p class="mt-4 small text-muted">Stand: <?= date('d.m.Y') ?> – Bitte prüfen Sie regelmäßig, ob sich Änderungen ergeben haben.</p>
      <a href="/" class="btn btn-outline-secondary mt-2">Zur Startseite</a>
    </div>
  </div>
</div>
<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
