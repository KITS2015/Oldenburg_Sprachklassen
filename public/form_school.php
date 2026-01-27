<?php
// public/form_school.php
declare(strict_types=1);

require __DIR__ . '/wizard/_common.php';
require_once __DIR__ . '/../app/functions_form.php';

// Schritt 1 (personal) muss vorhanden sein
require_step('personal');

/** -----------------------------
 * Datenquellen
 * ----------------------------- */
$SCHULEN = [
  '68457' => ['AGY Oldenburg', 'Theodor-Heuss-Str. 75', '26129', 'Oldenburg'],
  '74354' => ['BBS BZ f. T. u. G., OL', 'Straßburger Straße 2', '26123', 'Oldenburg'],
  '74378' => ['BbS Oldenburg Haarentor', 'Ammerländer Heerstr. 33-39', '26129', 'Oldenburg'],
  '74391' => ['BbS Oldenburg III', 'Maastrichter Str. 27', '26123', 'Oldenburg'],
  '75280' => ['BbS Oldenburg Wechloy', 'Am Heidbrook 10', '26129', 'Oldenburg'],
  '73428' => ['BFS Altenpf (HANSA), Oldenburg', 'Hansa- Ring 40-44', '26133', 'Oldenburg'],
  '78852' => ['BFS Altenpf (HANSA), Oldenburg', 'Hansa- Ring 40-44', '26133', 'Oldenburg'],
  '75838' => ['BFS Altenpflege WBS, Oldenburg', 'Ritterstraße 13-15', '26122', 'Oldenburg'],
  '78876' => ['BFS Altenpfl. (ev.) Oldenburg', 'Artillerieweg 37', '26129', 'Oldenburg'],
  '74445' => ['BFS Altenpfl. (ev.) Oldenburg', 'Artillerieweg 37', '26129', 'Oldenburg'],
  '74342' => ['BFS Berufe mit Zukunft Oldenburg', 'Am Wendehafen 10', '26135', 'Oldenburg'],
  '74494' => ['BFS Ergotherap, Oldenburg', 'Güterstraße 1', '26122', 'Oldenburg'],
  '74469' => ['BFS Euro-Sprachschule Oldenburg', 'Staulinie 11', '26122', 'Oldenburg'],
  '78520' => ['BFS Krk-Pfl Evangelisches', 'Steinweg 13-17', '26122', 'Oldenburg'],
  '78510' => ['BFS Krk-Pfl Klinikum Oldenburg', 'Rahel-Straus-Str. 10', '26133', 'Oldenburg'],
  '78530' => ['BFS Krk-Pfl Pius-Hospital', 'Georgstr. 12', '26121', 'Oldenburg'],
  '95941' => ['FöS-ES Oldenburg', 'Sandkruger Str. 119', '26133', 'Oldenburg'],
  '95333' => ['FöS-GB an der Kleiststr.', 'Kleiststraße 43', '26122', 'Oldenburg'],
  '95813' => ['FöS-HÖ Oldenburg', 'Lerigauweg 39', '26131', 'Oldenburg'],
  '95369' => ['FöS-KM Borchersweg', 'Borchersweg 80', '26135', 'Oldenburg'],
  '88043' => ['FWS Oldenburg', 'Blumenhof 9', '26135', 'Oldenburg'],
  '27194' => ['GS Alexandersfeld', 'Alexanderstr. 500', '26127', 'Oldenburg'],
  '39299' => ['GS auf der Wunderburg', 'Ekkardstr. 28', '26135', 'Oldenburg'],
  '39305' => ['GS Babenend', 'Babenend 15-17', '26127', 'Oldenburg'],
  '39329' => ['GS Bloherfelde', 'Schramperweg 57', '26129', 'Oldenburg'],
  '39342' => ['GS Bümmerstede', 'Bümmersteder Tredde', '26133', 'Oldenburg'],
  '39354' => ['GS Bürgeresch', 'Junkerstr. 17', '26123', 'Oldenburg'],
  '39378' => ['GS Dietrichsfeld', 'Liegnitzer Str. 37', '26127', 'Oldenburg'],
  '39391' => ['GS Donnerschwee', 'Donnerschweer Straße 262', '26123', 'Oldenburg'],
  '27224' => ['GS Drielake', 'Schulstr. 21', '26135', 'Oldenburg'],
  '27236' => ['GS Etzhorn', 'Butjadinger Str. 355', '26125', 'Oldenburg'],
  '27248' => ['GS Eversten (kath.)', 'Lerigauweg 58', '26131', 'Oldenburg'],
  '27261' => ['GS Haarentorschule', 'Schützenweg 25', '26129', 'Oldenburg'],
  '27455' => ['GS Harlinger Str. (kath.)', 'Harlingerstraße 14', '26121', 'Oldenburg'],
  '27273' => ['GS Heiligengeisttorschule', 'Ehnernstr. 8', '26121', 'Oldenburg'],
  '27297' => ['GS Hermann Ehlers', 'Feststraße 12', '26122', 'Oldenburg'],
  '27303' => ['GS Hogenkamp', 'Hogenkamp 10', '26131', 'Oldenburg'],
  '27376' => ['GS Klingenbergstraße', 'Klingenbergstraße 197', '26133', 'Oldenburg'],
  '45548' => ['GS Kreyenbrück', 'Breewaterweg 2', '26133', 'Oldenburg'],
  '27327' => ['GS Krusenbusch', 'Dießelweg 25', '26135', 'Oldenburg'],
  '27340' => ['GS Nadorst', 'Eßkamp 6-8', '26127', 'Oldenburg'],
  '05666' => ['GS Ofenerdiek', 'Lagerstr. 39', '26125', 'Oldenburg'],
  '27352' => ['GS Ohmstede', 'Rennplatzstr. 182', '26125', 'Oldenburg'],
  '27212' => ['GS Paul Maar', 'Bremer Heerstr. 250', '26135', 'Oldenburg'],
  '27364' => ['GS Röwekamp', 'Gertrudenstr. 25', '26121', 'Oldenburg'],
  '27406' => ['GS Staakenweg', 'Staakenweg 7', '26131', 'Oldenburg'],
  '27315' => ['GS Unter dem Regenbogen', 'Klingenbergstr. 19A', '26133', 'Oldenburg'],
  '27418' => ['GS Wallschule', 'Georgstraße 1', '26121', 'Oldenburg'],
  '27431' => ['GS Wechloy', 'Küpkersweg 16', '26129', 'Oldenburg'],
  '68354' => ['GY Altes Gymnasium', 'Theaterwall 11', '26122', 'Oldenburg'],
  '68391' => ['GY Cäcilienschule', 'Haarenufer 11', '26122', 'Oldenburg'],
  '68408' => ['GY Eversten', 'Theodor-Heuss-Str. 7', '26129', 'Oldenburg'],
  '68421' => ['GY Graf Anton Günther', 'Schleusenstr. 4', '26135', 'Oldenburg'],
  '68366' => ['GY Herbartgymnasium', 'Herbartstr. 4', '26122', 'Oldenburg'],
  '68482' => ['GY Liebfrauenschule', 'Auguststr. 31', '26121', 'Oldenburg'],
  '68433' => ['GY Neues Gymnasium', 'Alexanderstraße 90', '26121', 'Oldenburg'],
  '82715' => ['IGS Flötenteich', 'Hochheider Weg 169', '26125', 'Oldenburg'],
  '82703' => ['IGS Helene Lange', 'Marschweg 38', '26122', 'Oldenburg'],
  '80408' => ['IGS Kreyenbrück', 'Brandenburger Str.40', '26133', 'Oldenburg'],
  '05952' => ['IGS-GS Freie Schule Oldenburg', 'Burmesterstraße 5-7', '26135', 'Oldenburg'],
  '68470' => ['Kolleg Oldenburg', 'Theodor-Heuss-Str. 75', '26129', 'Oldenburg'],
  '45366' => ['OBS Alexanderstraße', 'Alexanderstr. 90', '26121', 'Oldenburg'],
  '45536' => ['OBS Eversten', 'Brandsweg 50', '26131', 'Oldenburg'],
  '39408' => ['OBS Ofenerdiek', 'Lagerstr. 32', '26125', 'Oldenburg'],
  '45470' => ['OBS Osternburg', 'Sophie-Schütte-Straße 10', '26135', 'Oldenburg'],
  '45524' => ['OBS Paulus', 'Margaretenstr. 46', '26121', 'Oldenburg'],
];

$GERMAN_LEVELS = $GERMAN_LEVELS ?? ['kein','A1','A2','B1','B2','C1','C2'];

$INTERESSEN = [
  'wirtschaft'     => 'Wirtschaft',
  'handwerk'       => 'Handwerk (Holz, Metall)',
  'soziales'       => 'Soziales / Erzieher*in / Sozialassistent*in',
  'gesundheit'     => 'Gesundheit / Pflege / Medizin',
  'garten'         => 'Garten / Landwirtschaft',
  'hauswirtschaft' => 'Kochen / Hauswirtschaft / Gastronomie / Hotelfach',
  'friseur'        => 'Friseur / Friseurin',
  'design'         => 'Design',
  'verwaltung'     => 'Verwaltung',
  'verkauf'        => 'Verkauf / Handel',
  'lagerlogistik'  => 'Lagerlogistik',
  'it'             => 'IT',
  'maschinenbau'   => 'Maschinenbau / KFZ',
  'automechanik'   => 'Automechanik',
  'büro'           => 'Bürokauffrau / Bürokaufmann',
  'technik'        => 'Technik',
  'kunst'          => 'Kunst / Theater',
  'kosmetik'       => 'Kosmetik',
];

/** -----------------------------
 * Helper
 * ----------------------------- */
function toLowerSafe(string $s): string { return strtolower($s); }

/** Vorbereitungen für Rendering */
$errors  = [];
$herk    = $_SESSION['form']['school']['schule_herkunft'] ?? ($_POST['schule_herkunft'] ?? '');
$curCode = $_SESSION['form']['school']['schule_aktuell']  ?? ($_POST['schule_aktuell']  ?? '');
$curFree = $_SESSION['form']['school']['schule_freitext'] ?? ($_POST['schule_freitext'] ?? '');
$curNiv  = $_SESSION['form']['school']['deutsch_niveau']  ?? ($_POST['deutsch_niveau']  ?? '');

/** Optionsliste serverseitig bauen (robust) */
$optionsHtml = '<option value="">Bitte wählen …</option>';
foreach ($SCHULEN as $code => $s) {
    $codeStr  = (string)$code; // <-- wichtig: immer String
    $name     = (string)$s[0];
    $strasse  = (string)$s[1];
    $plz      = (string)$s[2];
    $ort      = (string)$s[3];

    $display = $name . ' — ' . $strasse . ', ' . $ort . ' ' . $plz . ' (Nr. ' . $codeStr . ')';
    $sel     = ($curCode === $codeStr) ? ' selected' : '';
    $search  = htmlspecialchars(strtolower($display), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $optionsHtml .= '<option value="' . htmlspecialchars($codeStr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                    '" data-search="' . $search . '"' . $sel . '>' .
                    htmlspecialchars($display, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                    '</option>';
}
$optionsHtml .= '<option value="other"' . ($curCode==='other'?' selected':'') . '>Andere / nicht gelistet</option>';

/** -----------------------------
 * POST: Validierung & Speichern
 * ----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { http_response_code(400); exit('Ungültige Anfrage.'); }

    $req = ['schule_aktuell','klassenlehrer','jahre_in_de','schule_herkunft','familiensprache'];
    foreach ($req as $f) if (empty($_POST[$f])) $errors[$f] = 'Erforderlich.';

    // Schule: Code oder other + Freitext
    $schuleCode = (string)($_POST['schule_aktuell'] ?? '');
    $schuleFree = trim((string)($_POST['schule_freitext'] ?? ''));
    $isOther    = ($schuleCode === 'other');

    if ($isOther) {
        if ($schuleFree === '') $errors['schule_freitext'] = 'Bitte Schulname (Freitext) angeben.';
    } else {
        if (!array_key_exists($schuleCode, $SCHULEN)) $errors['schule_aktuell'] = 'Bitte gültige Schule wählen oder „Andere / nicht gelistet“.';
    }

    // Seit wann an der Schule: (MM & JJJJ) ODER Freitext
    $seit_monat = trim((string)($_POST['seit_monat'] ?? ''));
    $seit_jahr  = trim((string)($_POST['seit_jahr']  ?? ''));
    $seit_text  = trim((string)($_POST['seit_text']  ?? ''));

    if (($seit_monat === '' || $seit_jahr === '') && $seit_text === '') {
        $errors['seit_monat'] = 'Bitte Monat+Jahr oder Freitext angeben.';
        $errors['seit_jahr']  = 'Bitte Monat+Jahr oder Freitext angeben.';
    } else {
        if ($seit_monat !== '' && !preg_match('/^(0[1-9]|1[0-2])$/', $seit_monat)) $errors['seit_monat'] = 'Monat muss 01–12 sein.';
        if ($seit_jahr  !== '' && (!preg_match('/^\d{4}$/', $seit_jahr) || (int)$seit_jahr < 1900 || (int)$seit_jahr > 2100)) $errors['seit_jahr'] = 'Bitte gültiges Jahr.';
    }

    // Jahre in Deutschland
    if (!isset($errors['jahre_in_de']) && ($_POST['jahre_in_de'] ?? '') !== '') {
        if (!preg_match('/^\d{1,2}$/', (string)$_POST['jahre_in_de'])) $errors['jahre_in_de'] = 'Bitte Zahl angeben.';
    }

    // Herkunftsschule + Folgefeld
    $herkunft = (string)($_POST['schule_herkunft'] ?? '');
    if (!in_array($herkunft, ['ja','nein'], true)) $errors['schule_herkunft'] = 'Bitte auswählen.';
    if ($herkunft === 'ja') {
        if (empty($_POST['jahre_schule_herkunft']) || !preg_match('/^\d{1,2}$/', (string)$_POST['jahre_schule_herkunft'])) {
            $errors['jahre_schule_herkunft'] = 'Bitte Anzahl Jahre angeben.';
        }
    }

    // Deutsch-Niveau
    $niveau = (string)($_POST['deutsch_niveau'] ?? '');
    if ($niveau !== '' && !in_array($niveau, $GERMAN_LEVELS, true)) $errors['deutsch_niveau'] = 'Ungültige Auswahl.';

    // Interessen (1–2)
    $chosen       = array_keys(array_filter($_POST['interessen'] ?? [], fn($v) => $v === '1'));
    $chosen_valid = array_values(array_intersect($chosen, array_keys($INTERESSEN)));
    if (count($chosen_valid) < 1) $errors['interessen'] = 'Bitte mindestens 1 Bereich wählen.';
    if (count($chosen_valid) > 2) $errors['interessen'] = 'Bitte höchstens 2 Bereiche wählen.';

    if (!$errors) {
        $seit_wann_norm = ($seit_monat !== '' && $seit_jahr !== '') ? ($seit_monat . '.' . $seit_jahr) : $seit_text;
        $schule_label   = $isOther
            ? $schuleFree
            : ($SCHULEN[$schuleCode][0] . ' – ' . $SCHULEN[$schuleCode][1] . ', ' . $SCHULEN[$schuleCode][3] . ' ' . $SCHULEN[$schuleCode][2]);

        $_SESSION['form']['school'] = [
            'schule_aktuell'        => $schuleCode,
            'schule_freitext'       => $schuleFree,
            'schule_label'          => $schule_label,
            'klassenlehrer'         => trim((string)$_POST['klassenlehrer']),
            'mail_lehrkraft'        => trim((string)($_POST['mail_lehrkraft'] ?? '')),
            'seit_monat'            => $seit_monat,
            'seit_jahr'             => $seit_jahr,
            'seit_text'             => $seit_text,
            'seit_wann_schule'      => $seit_wann_norm,
            'jahre_in_de'           => (string)$_POST['jahre_in_de'],
            'schule_herkunft'       => $herkunft,
            'jahre_schule_herkunft' => trim((string)($_POST['jahre_schule_herkunft'] ?? '')),
            'familiensprache'       => trim((string)$_POST['familiensprache']),
            'deutsch_niveau'        => $niveau,
            'interessen'            => $chosen_valid,
        ];

        $save = save_scope_allow_noemail('school', $_SESSION['form']['school']);
        $_SESSION['last_save'] = $save;

        if (function_exists('flash_set')) {
            if ($save['ok'] ?? false) {
                flash_set('success', 'Daten gespeichert.');
            } else {
                $msg = $save['err'] ?? 'Zwischenspeicherung in der Session.';
                flash_set('info', $msg);
            }
        }

        header('Location: /form_upload.php');
        exit;
    }
}

/** -----------------------------
 * Header
 * ----------------------------- */
$title     = 'Schritt 2/4 – Schule & Interessen';
$html_lang = 'de';
$html_dir  = 'ltr';

require __DIR__ . '/partials/header.php';
require APP_APPDIR . '/header.php';

// Flash: Erfolgsmeldungen ausblenden (werden oben schon gezeigt)
if (!empty($_SESSION['flash'])) {
    $_SESSION['flash'] = array_values(array_filter($_SESSION['flash'], fn($f)=> (($f['type'] ?? '') !== 'success')));
}
if (function_exists('flash_render')) { flash_render(); }
?>
<div class="container py-4">

  <div class="alert alert-secondary">
    <strong>Hinweis:</strong> Sind Sie <u>mehr als 3 Jahre</u> in Deutschland oder sprechen Sie bereits Deutsch auf dem Niveau <u>B1</u> oder höher,
    können Sie nicht in der Sprachlernklasse der BBS aufgenommen werden. Bitte bewerben Sie sich für eine andere Klasse einer BBS hier:
    <a href="https://bbs-ol.de/" target="_blank" rel="noopener">https://bbs-ol.de/</a>.
  </div>

  <div id="autoHints" class="alert alert-warning d-none"></div>

  <div class="card shadow border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-3">Schritt 2/4 – Schule & Interessen</h1>
      <?php if ($errors): ?><div class="alert alert-danger">Bitte prüfen Sie die markierten Felder.</div><?php endif; ?>

      <form method="post" action="" novalidate id="schoolForm">
        <?php csrf_field(); ?>

        <div class="row g-4">
          <!-- Linke Spalte -->
          <div class="col-md-6">
            <div class="vstack gap-3">
              <div>
                <label class="form-label">Aktuelle Schule*</label>
                <input type="text" id="schuleSearch" class="form-control mb-2" placeholder="Schule suchen … (Name, Straße, PLZ)" autocomplete="off">
                <select name="schule_aktuell" id="schuleSelect" class="form-select<?= has_err('schule_aktuell',$errors) ?>" required>
                  <?= $optionsHtml ?>
                </select>
                <?php if (isset($errors['schule_aktuell'])): ?><div class="text-danger small mt-1"><?= h($errors['schule_aktuell']) ?></div><?php endif; ?>

                <div id="schuleOtherWrap" class="mt-2" style="display: <?= $curCode==='other'?'block':'none' ?>;">
                  <input type="text" name="schule_freitext" class="form-control<?= has_err('schule_freitext',$errors) ?>" placeholder="Schulname, Straße, Ort (Freitext)" value="<?= h($curFree) ?>">
                  <?php if (isset($errors['schule_freitext'])): ?><div class="text-danger small mt-1"><?= h($errors['schule_freitext']) ?></div><?php endif; ?>
                </div>
              </div>

              <div>
                <label class="form-label">verantwortliche*r Lehrer*in</label>
                <input name="klassenlehrer" class="form-control<?= has_err('klassenlehrer',$errors) ?>" value="<?= old('klassenlehrer','school') ?>" required>
                <?php if (isset($errors['klassenlehrer'])): ?><div class="text-danger small mt-1"><?= h($errors['klassenlehrer']) ?></div><?php endif; ?>
              </div>

              <div>
                <label class="form-label">E-Mail der Klassen-/DaZ-Lehrkraft</label>
                <input name="mail_lehrkraft" type="email" class="form-control<?= has_err('mail_lehrkraft',$errors) ?>" value="<?= old('mail_lehrkraft','school') ?>">
                <?php if (isset($errors['mail_lehrkraft'])): ?><div class="text-danger small mt-1"><?= h($errors['mail_lehrkraft']) ?></div><?php endif; ?>
              </div>

              <div>
                <label class="form-label d-block">Haben Sie im Herkunftsland die Schule besucht?*</label>
                <div class="btn-group" role="group">
                  <input type="radio" class="btn-check" name="schule_herkunft" id="s_j" value="ja"   <?= $herk==='ja'?'checked':''; ?> required>
                  <label class="btn btn-outline-primary" for="s_j">Ja</label>
                  <input type="radio" class="btn-check" name="schule_herkunft" id="s_n" value="nein" <?= $herk==='nein'?'checked':''; ?>>
                  <label class="btn btn-outline-primary" for="s_n">Nein</label>
                </div>
                <?php if (isset($errors['schule_herkunft'])): ?><div class="text-danger small mt-1"><?= h($errors['schule_herkunft']) ?></div><?php endif; ?>

                <div id="jahre_herkunft_wrap" class="mt-3" style="display:none;">
                  <label class="form-label">Wenn ja: wie viele Jahre?</label>
                  <input name="jahre_schule_herkunft" class="form-control<?= has_err('jahre_schule_herkunft',$errors) ?>" inputmode="numeric" value="<?= old('jahre_schule_herkunft','school') ?>">
                  <?php if(isset($errors['jahre_schule_herkunft'])): ?><div class="invalid-feedback d-block"><?= h($errors['jahre_schule_herkunft']) ?></div><?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Rechte Spalte -->
          <div class="col-md-6">
            <div class="vstack gap-3">
              <div>
                <label class="form-label">Seit wann an einer Schule in Deutschland?</label>
                <div class="row g-2">
                  <div class="col-5">
                    <?php $malt = $_SESSION['form']['school']['seit_monat'] ?? ($_POST['seit_monat'] ?? ''); ?>
                    <select name="seit_monat" class="form-select<?= has_err('seit_monat',$errors) ?>">
                      <option value="">Monat (MM)</option>
                      <?php for ($m=1; $m<=12; $m++): $mm = str_pad((string)$m, 2, '0', STR_PAD_LEFT); ?>
                        <option value="<?= $mm ?>" <?= $malt===$mm ? 'selected' : '' ?>><?= $mm ?></option>
                      <?php endfor; ?>
                    </select>
                  </div>
                  <div class="col-7">
                    <input name="seit_jahr" class="form-control<?= has_err('seit_jahr',$errors) ?>" inputmode="numeric" pattern="\d{4}" placeholder="Jahr (JJJJ)" value="<?= h($_SESSION['form']['school']['seit_jahr'] ?? ($_POST['seit_jahr'] ?? '')) ?>">
                  </div>
                </div>
                <div class="form-text">Entweder Monat+Jahr angeben <strong>oder</strong> das Freitextfeld nutzen.</div>
                <?php if(isset($errors['seit_monat'])): ?><div class="text-danger small mt-1"><?= h($errors['seit_monat']) ?></div><?php endif; ?>
                <?php if(isset($errors['seit_jahr'])): ?><div class="text-danger small mt-1"><?= h($errors['seit_jahr']) ?></div><?php endif; ?>
              </div>

              <div>
                <label class="form-label">Alternativ: Freitext (z. B. „seit Herbst 2023“)</label>
                <input name="seit_text" class="form-control<?= has_err('seit_text',$errors) ?>" value="<?= h($_SESSION['form']['school']['seit_text'] ?? ($_POST['seit_text'] ?? '')) ?>">
                <?php if(isset($errors['seit_text'])): ?><div class="text-danger small mt-1"><?= h($errors['seit_text']) ?></div><?php endif; ?>
              </div>

              <div>
                <label class="form-label">Seit wie vielen Jahren sind Sie in Deutschland?*</label>
                <input name="jahre_in_de" inputmode="numeric" class="form-control<?= has_err('jahre_in_de',$errors) ?>" value="<?= old('jahre_in_de','school') ?>" required>
                <?php if (isset($errors['jahre_in_de'])): ?><div class="text-danger small mt-1"><?= h($errors['jahre_in_de']) ?></div><?php endif; ?>
                <div class="form-text">Hinweis: &gt; 3 Jahre → Bitte reguläre BBS-Bewerbung über <a href="https://bbs-ol.de/" target="_blank" rel="noopener">bbs-ol.de</a>.</div>
              </div>

              <div>
                <label class="form-label">Familiensprache / Erstsprache*</label>
                <input name="familiensprache" class="form-control<?= has_err('familiensprache',$errors) ?>" value="<?= old('familiensprache','school') ?>" required>
                <?php if (isset($errors['familiensprache'])): ?><div class="text-danger small mt-1"><?= h($errors['familiensprache']) ?></div><?php endif; ?>
              </div>

              <div>
                <label class="form-label">Welches Deutsch-Niveau?</label>
                <select name="deutsch_niveau" id="deutsch_niveau" class="form-select<?= has_err('deutsch_niveau',$errors) ?>">
                  <option value="">Bitte wählen …</option>
                  <?php foreach ($GERMAN_LEVELS as $lvl): ?>
                    <option value="<?= h($lvl) ?>" <?= $curNiv===$lvl ? 'selected' : '' ?>><?= h($lvl) ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if (isset($errors['deutsch_niveau'])): ?><div class="text-danger small mt-1"><?= h($errors['deutsch_niveau']) ?></div><?php endif; ?>
                <div class="form-text">Hinweis: B1 oder höher → reguläre BBS-Bewerbung über <a href="https://bbs-ol.de/" target="_blank" rel="noopener">bbs-ol.de</a>.</div>
              </div>
            </div>
          </div>

          <!-- Interessen -->
          <div class="col-12">
            <label class="form-label mt-2">Interessen (mind. 1, max. 2)</label>
            <div class="row">
              <?php $curInt = $_SESSION['form']['school']['interessen'] ?? array_keys(array_filter($_POST['interessen'] ?? [])); ?>
              <?php foreach ($INTERESSEN as $k=>$label): ?>
                <div class="col-sm-6 col-md-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="interessen[<?= h($k) ?>]" id="int_<?= h($k) ?>" value="1" <?= in_array($k,$curInt,true)?'checked':''; ?>>
                    <label class="form-check-label" for="int_<?= h($k) ?>"><?= h($label) ?></label>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <?php if (isset($errors['interessen'])): ?><div class="text-danger small mt-1"><?= h($errors['interessen']) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="/form_personal.php" class="btn btn-outline-secondary">Zurück</a>
          <button class="btn btn-primary">Weiter</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Herkunftsjahre toggeln
function toggleHerkunftYears(){
  var yes  = document.getElementById('s_j');
  var wrap = document.getElementById('jahre_herkunft_wrap');
  if (wrap) wrap.style.display = (yes && yes.checked) ? '' : 'none';
}
document.addEventListener('change', function(e){
  if (e.target && (e.target.id === 's_j' || e.target.id === 's_n')) toggleHerkunftYears();
});
toggleHerkunftYears();

// Suche im Dropdown (nutzt data-search vom Server)
(function(){
  var input  = document.getElementById('schuleSearch');
  var select = document.getElementById('schuleSelect');
  var otherWrap = document.getElementById('schuleOtherWrap');

  function toLower(s){ return (s || '').toString().toLowerCase(); }

  function applyFilter(q){
    var query = toLower(q).trim();
    for (var i=0; i<select.options.length; i++){
      var opt = select.options[i];
      if (!opt.value || opt.value === 'other') { opt.hidden = false; continue; }
      var hay = opt.getAttribute('data-search') || toLower(opt.textContent || opt.innerText);
      opt.hidden = !(query === '' || hay.indexOf(query) !== -1);
    }
    var sel = select.selectedOptions && select.selectedOptions[0];
    if (query && sel && sel.hidden) { select.value = ''; }
  }

  if (input && select) {
    input.addEventListener('input', function(){ applyFilter(input.value); });
    select.addEventListener('change', function(){
      if (otherWrap) otherWrap.style.display = (select.value === 'other') ? 'block' : 'none';
    });
    applyFilter('');
  }
})();

// Live-Hinweise zu >3 Jahren oder B1+
(function(){
  var form = document.getElementById('schoolForm');
  var jahre = form.querySelector('input[name="jahre_in_de"]');
  var niveau = document.getElementById('deutsch_niveau');
  var hints = document.getElementById('autoHints');
  var link = '<a href="https://bbs-ol.de/" target="_blank" rel="noopener">bbs-ol.de</a>';

  function evaluate(){
    var years = parseInt((jahre.value || '').trim(), 10);
    var niv   = (niveau.value || '').toUpperCase();
    var msgs = [];
    if (!isNaN(years) && years > 3) msgs.push('Hinweis: Sie haben mehr als 3 Jahre in Deutschland. Bitte bewerben Sie sich über ' + link + '.');
    if (['B1','B2','C1','C2'].indexOf(niv) !== -1) msgs.push('Hinweis: Mit Deutsch-Niveau B1 oder höher bitte reguläre BBS-Bewerbung über ' + link + '.');
    if (msgs.length) { hints.innerHTML = msgs.map(function(m){return '<div>'+m+'</div>';}).join(''); hints.classList.remove('d-none'); }
    else { hints.innerHTML = ''; hints.classList.add('d-none'); }
  }
  if (jahre) jahre.addEventListener('input', evaluate);
  if (niveau) niveau.addEventListener('change', evaluate);
  evaluate();
})();
</script>

<?php require __DIR__ . '/partials/footer.php';
