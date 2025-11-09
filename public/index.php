<?php /* Keine externen Ressourcen, alles lokal */ ?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8" />
  <title>Online-Anmeldung Oldenburg</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Lokales Bootstrap CSS -->
  <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css" />

  <!-- Euer lokales Form-Branding -->
  <link rel="stylesheet" href="/assets/form.css" />
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="card shadow border-0 rounded-4">
      <div class="card-body p-4 p-md-5">
        <h1 class="h3 text-center mb-4">Anmeldung – Sprachklassen</h1>

        <form method="post" action="/submit.php" novalidate>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="vorname" class="form-label">Vorname</label>
              <input id="vorname" name="vorname" type="text" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label for="nachname" class="form-label">Nachname</label>
              <input id="nachname" name="nachname" type="text" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label for="email" class="form-label">E-Mail</label>
              <input id="email" name="email" type="email" class="form-control" autocomplete="email" />
            </div>
            <div class="col-md-6">
              <label for="telefon" class="form-label">Telefon</label>
              <input id="telefon" name="telefon" type="tel" class="form-control" />
            </div>
            <div class="col-12">
              <label for="nachricht" class="form-label">Nachricht</label>
              <textarea id="nachricht" name="nachricht" rows="4" class="form-control"></textarea>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="dsgvo" name="dsgvo" required>
                <label class="form-check-label" for="dsgvo">
                  Ich habe die Datenschutzhinweise gelesen und bin einverstanden.
                </label>
              </div>
            </div>
            <div class="col-12">
              <button class="btn btn-primary w-100" type="submit">Absenden</button>
            </div>
          </div>
        </form>

      </div>
    </div>
  </div>

  <!-- Lokales Bootstrap JS (enthält Popper) -->
  <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
