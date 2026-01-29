<?php
// app/i18n.php
declare(strict_types=1);

/**
 * Zentrale i18n-Helfer
 * - Sprach-Erkennung: GET > Session > Cookie > Browser > de
 * - t(): String-Übersetzung
 * - t_arr(): Array-Übersetzung (Bullets etc.)
 * - i18n_languages(): verfügbare Sprachen (Anzeige)
 * - i18n_is_rtl(): RTL-Check
 * - i18n_dir(): 'rtl'|'ltr'
 * - i18n_url(): hängt ?lang=... an interne Links
 */

// Verfügbare Sprachen (Anzeige)
$GLOBALS['APP_LANGUAGES'] = [
    'de' => 'Deutsch',
    'en' => 'English',
    'fr' => 'Français',
    'uk' => 'Українська',
    'ar' => 'العربية',
    'ru' => 'Русский',
    'tr' => 'Türkçe',
    'fa' => 'فارسی',
    'vn' => 'Tiếng Việt',
];

function i18n_languages(): array {
    return (array)($GLOBALS['APP_LANGUAGES'] ?? []);
}

function i18n_is_rtl(string $lang): bool {
    return in_array($lang, ['ar', 'fa'], true);
}

function i18n_dir(?string $lang = null): string {
    $lang = $lang ?: (string)($_SESSION['lang'] ?? 'de');
    return i18n_is_rtl($lang) ? 'rtl' : 'ltr';
}

/**
 * Detect language: GET > Session > Cookie > Browser > de
 * Keeps session + cookie in sync.
 */
function i18n_detect_lang(): string {
    $langs = i18n_languages();

    $lang = strtolower((string)($_GET['lang'] ?? ''));
    if ($lang === '') $lang = strtolower((string)($_SESSION['lang'] ?? ''));
    if ($lang === '') $lang = strtolower((string)($_COOKIE['lang'] ?? ''));

    if ($lang === '' || !isset($langs[$lang])) {
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        foreach ($langs as $code => $_label) {
            if ($code !== '' && strpos($accept, $code) !== false) {
                $lang = $code;
                break;
            }
        }
    }

    if ($lang === '' || !isset($langs[$lang])) $lang = 'de';

    $_SESSION['lang'] = $lang;

    // Cookie synchron halten
    if (!isset($_COOKIE['lang']) || (string)$_COOKIE['lang'] !== $lang) {
        setcookie('lang', $lang, time() + 60 * 60 * 24 * 365, '/');
        $_COOKIE['lang'] = $lang; // im aktuellen Request verfügbar
    }

    return $lang;
}

/**
 * Interne URL mit aktueller Sprache (hängt lang= an, falls nicht vorhanden).
 */
function i18n_url(string $path, ?string $lang = null): string {
    $lang = $lang ?: (string)($_SESSION['lang'] ?? 'de');
    $sep  = (strpos($path, '?') !== false) ? '&' : '?';
    if (preg_match('/(?:\?|&)lang=/', $path)) return $path;
    return $path . $sep . 'lang=' . rawurlencode($lang);
}

/**
 * Translation dictionary (Index-Texte komplett, 1:1 aus deiner index.php)
 */
function i18n_dict(): array {
    return [
                'de' => [
            // =======================
            // Start: Index (DE)
            // =======================
            'index.title' => 'Willkommen zur Online-Anmeldung – Sprachklassen',
            'index.lead'  => 'Dieses Angebot richtet sich an neu zugewanderte Menschen in Oldenburg. Das Formular hilft uns, Kontakt aufzunehmen und passende Angebote zu finden.',
            'index.bullets' => [
                'Halten Sie bitte Kontaktdaten und Ausweisdokumente bereit (falls vorhanden).',
                'Die Angaben können in mehreren Sprachen ausgefüllt werden.',
                'Ihre Daten werden gemäß DSGVO vertraulich behandelt.',
            ],
            'index.info_p' => [
                'Liebe Schülerin, lieber Schüler,',
                'Hiermit bewerben Sie sich für einen Platz in der Sprachlernklasse „BES Sprache und Integration“ einer berufsbildenden Schule (BBS) in Oldenburg. Sie bewerben sich nicht für eine bestimmte BBS. Welche Schule Sie in der Sprachlernklasse aufnimmt, wird Ihnen nach dem 20. Februar mitgeteilt.',
                'Sie können nur unter folgenden Voraussetzungen aufgenommen werden:',
            ],
            'index.info_bullets' => [
                'Sie benötigen intensive Deutschförderung (Deutschkenntnisse unter B1).',
                'Sie sind zu Beginn des nächsten Schuljahres höchstens 3 Jahre in Deutschland.',
                'Sie sind am 30. September dieses Jahres mindestens 16 und höchstens 18 Jahre alt.',
                'Sie sind im nächsten Schuljahr schulpflichtig.',
            ],
            'index.access_title' => 'Datenschutz & Zugang',
            'index.access_intro' => 'Sie können mit oder ohne E-Mail-Adresse fortfahren. Der Zugriff auf gespeicherte Bewerbungen ist nur mit persönlichem Zugangscode (Token) und Geburtsdatum möglich.',
            'index.access_points' => [
                '<strong>Mit E-Mail:</strong> Sie erhalten einen Bestätigungscode und können mehrere Bewerbungen anlegen und später wieder aufrufen.',
                '<strong>Ohne E-Mail:</strong> Sie erhalten einen persönlichen Zugangscode (Access-Token). Bitte notieren/fotografieren Sie diesen – ohne verifizierte E-Mail ist keine Wiederherstellung möglich.',
            ],
            'index.btn_noemail' => 'Ohne E-Mail fortfahren',
            'index.btn_create'  => 'Mit E-Mail fortfahren',
            'index.btn_load'    => 'Zugriff auf Bewerbung/en',
            'index.lang_label'  => 'Sprache / Language:',

            // =======================
            // 1/4: PERSONAL (DE)
            // =======================
            'personal.page_title' => 'Schritt 1/4 – Persönliche Daten',
            'personal.h1' => 'Schritt 1/4 – Persönliche Daten',
            'personal.required_hint' => 'Pflichtfelder sind blau am Rahmen hervorgehoben.',
            'personal.form_error_hint' => 'Bitte prüfen Sie die markierten Felder.',
            'personal.alert_email_title' => 'E-Mail-Login aktiv:',
            'personal.alert_email_line1' => 'Angemeldet mit der E-Mail-Adresse {email}.',
            'personal.alert_email_line2' => 'Diese E-Mail wird nur für den Zugangscode (Access-Token) und zum Wiederfinden Ihrer Bewerbung verwendet.',
            'personal.alert_email_line3' => 'Unten können Sie eine E-Mail-Adresse der Schülerin / des Schülers angeben (falls vorhanden).',
            'personal.alert_noemail_title' => 'Hinweis (ohne E-Mail):',
            'personal.alert_noemail_body' => 'Bitte notieren/fotografieren Sie Ihren Zugangscode (Access-Token), den Sie nach dem Speichern auf dieser Seite angezeigt bekommen. Ohne verifizierte E-Mail ist eine Wiederherstellung nur mit Token + Geburtsdatum möglich.',
            'personal.label.name' => 'Name',
            'personal.label.vorname' => 'Vorname',
            'personal.label.geschlecht' => 'Geschlecht',
            'personal.gender.m' => 'männlich',
            'personal.gender.w' => 'weiblich',
            'personal.gender.d' => 'divers',
            'personal.label.geburtsdatum' => 'Geboren am',
            'personal.label.geburtsdatum_hint' => '(TT.MM.JJJJ)',
            'personal.placeholder.geburtsdatum' => 'TT.MM.JJJJ',
            'personal.age_hint' => 'Hinweis: Sind Sie am 30.09.{year} unter 16 oder über 18 Jahre alt, können Sie nicht in die Sprachlernklasse der BBS aufgenommen werden. Bitte bewerben Sie sich für eine andere Klasse hier:',
            'personal.age_redirect_msg' => "Hinweis: Sind Sie am 30.09.{year} unter 16 oder über 18 Jahre alt, können Sie nicht in die Sprachlernklasse der BBS aufgenommen werden. Bitte bewerben Sie sich für eine andere Klasse einer BBS hier:\n{url}",
            'personal.label.geburtsort_land' => 'Geburtsort / Geburtsland',
            'personal.label.staatsang' => 'Staatsangehörigkeit',
            'personal.label.strasse' => 'Straße, Nr.',
            'personal.label.plz' => 'PLZ',
            'personal.plz_choose' => '– bitte wählen –',
            'personal.plz_hint' => 'Nur Oldenburg (Oldb).',
            'personal.label.wohnort' => 'Wohnort',
            'personal.label.telefon' => 'Telefonnummer',
            'personal.label.telefon_vorwahl_help' => 'Vorwahl mit/ohne 0',
            'personal.label.telefon_nummer_help' => 'Rufnummer',
            'personal.placeholder.telefon_vorwahl' => '(0)441',
            'personal.placeholder.telefon_nummer' => '123456',
            'personal.label.email' => 'E-Mail-Adresse der Schülerin / des Schülers (optional, keine IServ-Adresse)',
            'personal.email_help' => 'Diese E-Mail gehört zur Schülerin / zum Schüler (falls vorhanden) und ist unabhängig von der E-Mail-Adresse für den Zugangscode.',
            'personal.placeholder.email' => 'name@example.org',
            'personal.label.kontakte' => 'Weitere Kontaktdaten',
            'personal.kontakte_hint' => '(z. B. Eltern, Betreuer, Einrichtung)',
            'personal.kontakte_error' => 'Bitte prüfen Sie die zusätzlichen Kontakte.',
            'personal.kontakte_add' => '+ Kontakt hinzufügen',
            'personal.kontakte_remove_title' => 'Kontakt entfernen',
            'personal.table.role' => 'Rolle',
            'personal.table.name' => 'Name / Einrichtung',
            'personal.table.tel'  => 'Telefon',
            'personal.table.mail' => 'E-Mail',
            'personal.table.note_header' => 'Notiz',
            'personal.placeholder.kontakt_name' => 'Name oder Bezeichnung',
            'personal.placeholder.kontakt_tel'  => '+49 …',
            'personal.placeholder.kontakt_note' => 'z. B. Erreichbarkeit, Sprache, Hinweise',
            'personal.contact_role.none' => '–',
            'personal.contact_role.mutter' => 'Mutter',
            'personal.contact_role.vater' => 'Vater',
            'personal.contact_role.elternteil' => 'Elternteil',
            'personal.contact_role.betreuer' => 'Betreuer*in',
            'personal.contact_role.einrichtung' => 'Einrichtung',
            'personal.contact_role.sonstiges' => 'Sonstiges',
            'personal.label.weitere_angaben' => 'Weitere Angaben (z. B. Förderstatus):',
            'personal.placeholder.weitere_angaben' => 'Hier können Sie z. B. besonderen Förderbedarf, sonderpädagogische Unterstützungsbedarfe oder weitere Hinweise angeben.',
            'personal.weitere_angaben_help' => 'Optional. Maximal 1500 Zeichen.',
            'personal.btn.cancel' => 'Abbrechen',
            'personal.btn.next' => 'Weiter',
            'personal.dsgvo_text_prefix' => 'Ich habe die',
            'personal.dsgvo_link_text' => 'Datenschutzhinweise',
            'personal.dsgvo_text_suffix' => 'gelesen und bin einverstanden.',

            // =====================
            // 2/4: SCHOOL (DE)
            // =====================
            'school.page_title' => 'Schritt 2/4 – Schule & Interessen',
            'school.h1' => 'Schritt 2/4 – Schule & Interessen',
            'school.required_hint' => 'Pflichtfelder sind blau am Rahmen hervorgehoben.',
            'school.form_error_hint' => 'Bitte prüfen Sie die markierten Felder.',
            'school.top_hint_title' => 'Hinweis:',
            'school.top_hint_body'  => 'Sind Sie <u>mehr als 3 Jahre</u> in Deutschland oder sprechen Sie bereits Deutsch auf dem Niveau <u>B1</u> oder höher, können Sie nicht in der Sprachlernklasse der BBS aufgenommen werden. Bitte bewerben Sie sich für eine andere Klasse einer BBS hier:',
            'school.bbs_link_label' => 'https://bbs-ol.de/',
            'school.autohints_title' => 'Hinweise',
            'school.label.schule_aktuell' => 'Aktuelle Schule',
            'school.search_placeholder'   => 'Schule suchen … (Name, Straße, PLZ)',
            'school.select_choose'        => 'Bitte wählen …',
            'school.option_other'         => 'Andere / nicht gelistet',
            'school.other_placeholder'    => 'Schulname, Straße, Ort (Freitext)',
            'school.label.teacher'        => 'verantwortliche*r Lehrer*in',
            'school.label.teacher_mail'   => 'E-Mail der verantwortliche*r Lehrer*in',
            'school.label.herkunft'       => 'Haben Sie im Herkunftsland die Schule besucht?',
            'school.yes'                  => 'Ja',
            'school.no'                   => 'Nein',
            'school.label.herkunft_years' => 'Wenn ja: wie viele Jahre?',
            'school.label.since'          => 'Seit wann an einer Schule in Deutschland?',
            'school.since_month'          => 'Monat (MM)',
            'school.since_year_ph'        => 'Jahr (JJJJ)',
            'school.since_help'           => 'Entweder Monat+Jahr angeben <strong>oder</strong> das Freitextfeld nutzen.',
            'school.label.since_text'     => 'Alternativ: Freitext (z. B. „seit Herbst 2023“)',
            'school.label.years_in_de'    => 'Seit wie vielen Jahren sind Sie in Deutschland?',
            'school.years_in_de_help'     => 'Hinweis: &gt; 3 Jahre → Bitte reguläre BBS-Bewerbung über {link}.',
            'school.label.family_lang'    => 'Familiensprache / Erstsprache',
            'school.label.level'          => 'Welches Deutsch-Niveau?',
            'school.level_choose'         => 'Bitte wählen …',
            'school.level_help'           => 'Hinweis: B1 oder höher → reguläre BBS-Bewerbung über {link}.',
            'school.label.interests'      => 'Interessen (mind. 1, max. 2)',
            'school.btn.back'             => 'Zurück',
            'school.btn.next'             => 'Weiter',

            // =====================
            // 3/4: UPLOAD (DE)
            // =====================
            'upload.page_title' => 'Schritt 3/4 – Unterlagen (optional)',
            'upload.h1'         => 'Schritt 3/4 – Unterlagen (optional)',
            'upload.intro'      => 'Sie können hier Unterlagen hochladen. Erlaubte Formate sind <strong>PDF</strong>, <strong>JPG</strong> und <strong>PNG</strong>. Die maximale Dateigröße beträgt <strong>{max_mb} MB</strong> pro Datei.',
            'upload.type.zeugnis'    => 'Letztes Halbjahreszeugnis',
            'upload.type.lebenslauf' => 'Lebenslauf',
            'upload.type_hint'       => '(PDF/JPG/PNG, max. {max_mb} MB)',
            'upload.btn.remove' => 'Entfernen',
            'upload.btn.back'   => 'Zurück',
            'upload.btn.next'   => 'Weiter',
            'upload.saved_prefix' => 'Bereits gespeichert:',
            'upload.empty'        => 'Noch keine Datei hochgeladen.',
            'upload.saved_html'   => 'Bereits gespeichert: <strong>{filename}</strong>, {size_kb} KB, hochgeladen am {uploaded_at}',
            'upload.checkbox.zeugnis_spaeter' => 'Ich reiche das Halbjahreszeugnis nach der Zusage nach.',
            'upload.flash.no_access' => 'Kein gültiger Zugang gefunden. Bitte beginnen Sie die Anmeldung neu.',
            'upload.flash.saved'     => 'Upload-Informationen gespeichert.',
            'upload.js.uploading'         => 'Upload wird durchgeführt …',
            'upload.js.unexpected'        => 'Unerwartete Antwort vom Server.',
            'upload.js.upload_failed'     => 'Upload fehlgeschlagen.',
            'upload.js.delete_confirm'    => 'Hochgeladene Datei wirklich entfernen?',
            'upload.js.delete_failed'     => 'Löschen fehlgeschlagen.',
            'upload.js.remove_confirm_btn'=> 'Datei entfernen?',
            'upload.ajax.invalid_method' => 'Ungültige Methode',
            'upload.ajax.invalid_csrf'   => 'Ungültiges CSRF-Token',
            'upload.ajax.no_access'      => 'Kein gültiger Zugang.',
            'upload.ajax.invalid_field'  => 'Ungültiges Feld',
            'upload.ajax.no_file_sent'   => 'Keine Datei gesendet',
            'upload.ajax.no_file_selected' => 'Keine Datei ausgewählt',
            'upload.ajax.upload_error'   => 'Upload-Fehler (Code {code})',
            'upload.ajax.too_large'      => 'Datei größer als {max_mb} MB',
            'upload.ajax.mime_only'      => 'Nur PDF, JPG oder PNG erlaubt',
            'upload.ajax.ext_only'       => 'Ungültige Dateiendung (nur pdf/jpg/jpeg/png)',
            'upload.ajax.cannot_save'    => 'Konnte Datei nicht speichern',
            'upload.ajax.unknown_action' => 'Unbekannte Aktion',
            'upload.ajax.server_error'   => 'Serverfehler beim Upload',

            // =====================
            // 4/4: REVIEW (DE)
            // =====================
            'review.page_title' => 'Schritt 4/4 – Zusammenfassung & Bewerbung',
            'review.h1'          => 'Schritt 4/4 – Zusammenfassung & Bewerbung',
            'review.subhead'     => 'Bitte prüfen Sie Ihre Angaben. Mit „Bewerben“ senden Sie die Daten ab.',
            'review.readonly_alert' => 'Diese Bewerbung wurde bereits abgeschickt. Die Angaben können nur noch angesehen, aber nicht mehr geändert oder erneut eingereicht werden.',
            'review.info.p1' => 'Liebe Schülerin, lieber Schüler,',
            'review.info.p2' => 'wenn Sie auf <strong>„bewerben“</strong> klicken, haben Sie sich für die <strong>BES Sprache und Integration</strong> an einer Oldenburger BBS beworben.',
            'review.info.p3' => 'Es handelt sich noch nicht um eine finale Anmeldung, sondern um eine <strong>Bewerbung</strong>. Nach dem <strong>20.02.</strong> erhalten Sie die Information, ob / an welcher BBS Sie aufgenommen werden. Bitte prüfen Sie regelmäßig Ihren Briefkasten und Ihr E-Mail-Postfach. Bitte achten Sie darauf, dass am Briefkasten Ihr Name sichtbar ist, damit Sie Briefe bekommen können.',
            'review.info.p4' => 'Sie erhalten mit der Zusage der Schule die Aufforderung, diese Dateien nachzureichen (falls Sie es heute noch nicht hochgeladen haben):',
            'review.info.li1' => 'letztes Halbjahreszeugnis',
            'review.acc.personal' => 'Persönliche Daten',
            'review.acc.school'   => 'Schule & Interessen',
            'review.acc.uploads'  => 'Unterlagen',
            'review.lbl.name'            => 'Name',
            'review.lbl.vorname'         => 'Vorname',
            'review.lbl.geschlecht'      => 'Geschlecht',
            'review.lbl.geburtsdatum'    => 'Geboren am',
            'review.lbl.geburtsort'      => 'Geburtsort / Geburtsland',
            'review.lbl.staatsang'       => 'Staatsangehörigkeit',
            'review.lbl.strasse'         => 'Straße, Nr.',
            'review.lbl.plz_ort'         => 'PLZ / Wohnort',
            'review.lbl.telefon'         => 'Telefon',
            'review.lbl.email'           => 'E-Mail (Schüler*in, optional)',
            'review.lbl.weitere_angaben' => 'Weitere Angaben (z. B. Förderstatus)',
            'review.contacts.title'   => 'Weitere Kontakte',
            'review.contacts.optional'=> 'optional',
            'review.contacts.none'    => '–',
            'review.contacts.th.role' => 'Rolle',
            'review.contacts.th.name' => 'Name / Einrichtung',
            'review.contacts.th.tel'  => 'Telefon',
            'review.contacts.th.mail' => 'E-Mail',
            'review.contacts.note'    => 'Notiz:',
            'review.lbl.school_current' => 'Aktuelle Schule',
            'review.lbl.klassenlehrer'  => 'Verantwortliche*r Lehrer*in',
            'review.lbl.mail_lehrkraft' => 'E-Mail Lehrkraft',
            'review.lbl.since'          => 'Seit wann an der Schule',
            'review.lbl.years_de'       => 'Jahre in Deutschland',
            'review.lbl.family_lang'    => 'Familiensprache / Erstsprache',
            'review.lbl.de_level'       => 'Deutsch-Niveau',
            'review.lbl.school_origin'  => 'Schule im Herkunftsland',
            'review.lbl.years_origin'   => 'Jahre Schule im Herkunftsland',
            'review.lbl.interests'      => 'Interessen',
            'review.lbl.zeugnis'      => 'Halbjahreszeugnis',
            'review.lbl.lebenslauf'   => 'Lebenslauf',
            'review.lbl.later'        => 'Später nachreichen',
            'review.badge.uploaded'   => 'hochgeladen',
            'review.badge.not_uploaded'=> 'nicht hochgeladen',
            'review.yes'              => 'Ja',
            'review.no'               => 'Nein',
            'review.btn.home'   => 'Zur Startseite',
            'review.btn.newapp' => 'Weitere Bewerbung einreichen',
            'review.btn.back'   => 'Zurück',
            'review.btn.submit' => 'Bewerben',
            'review.err.invalid_request' => 'Ungültige Anfrage.',
            'review.flash.already_submitted' => 'Diese Bewerbung wurde bereits abgeschickt und kann nicht erneut eingereicht oder geändert werden.',
            'review.flash.no_token'          => 'Kein gültiger Zugangscode. Bitte starten Sie den Vorgang neu.',
            'review.err.not_found_token'     => 'Bewerbung zu diesem Token nicht gefunden.',
            'review.flash.submit_error'      => 'Beim Übermitteln ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.',
            'review.gender.m' => 'männlich',
            'review.gender.w' => 'weiblich',
            'review.gender.d' => 'divers',
            'review.value.empty' => '–',

            // =====================
            // STATUS (DE)
            // =====================
            'status.hdr_title'   => 'Bewerbung erfolgreich gespeichert',
            'status.hdr_message' => 'Ihre Bewerbung wurde übermittelt.',
            'status.h1' => 'Ihre Bewerbung wurde erfolgreich gespeichert.',
            'status.success.title' => 'Vielen Dank!',
            'status.success.body'  => 'Ihre Bewerbung wurde übermittelt und wird nun bearbeitet.',
            'status.info.title' => 'Wichtiger Hinweis',
            'status.info.body'  => '„Geschafft, Sie haben sich für die "BES Sprache und Integration" an einer Oldenburger BBS beworben.

Es handelt sich noch nicht um eine finale Anmeldung, sondern um eine Bewerbung. Nach dem 20.02. erhalten Sie die Information, ob / an welcher BBS Sie aufgenommen werden. Bitte prüfen Sie regelmäßig Ihren Briefkasten und Ihr E-Mail Postfach. Bitte achten Sie darauf, dass am Briefkasten Ihr Name sichtbar ist, damit Sie Briefe bekommen können.

Sie erhalten mit der Zusage der Schule die Aufforderung, diese Dateien nachzureichen (falls Sie es heute noch nicht hochgeladen haben):
- letztes Halbjahreszeugnis“
',
            'status.btn.pdf'     => 'PDF herunterladen / drucken',
            'status.btn.newapp'  => 'Weitere Bewerbung starten',
            'status.btn.home'    => 'Zur Startseite',
            'status.ref' => 'Referenz: Bewerbung #{id}',
            'status.err.invalid_request' => 'Ungültige Anfrage.',

            // =====================
            // PDF (DE)
            // =====================
            'pdf.err.autoload_missing' => 'Composer Autoload nicht gefunden. Bitte "composer install" ausführen.',
            'pdf.err.no_token'         => 'Kein gültiger Zugangscode. Bitte beginnen Sie den Vorgang neu.',
            'pdf.err.not_found'        => 'Bewerbung nicht gefunden.',
            'pdf.err.server'           => 'Serverfehler beim Erzeugen des PDFs.',
            'pdf.header_title' => 'Bewerbung – Zusammenfassung',
            'pdf.footer_auto'  => 'Automatisch erzeugtes Dokument',
            'pdf.footer_page'  => 'Seite {cur} / {max}',
            'pdf.meta.ref'        => 'Bewerbung #{id}',
            'pdf.meta.created_at' => 'Erstellt am',
            'pdf.meta.status'     => 'Status',
            'pdf.top.title'       => 'Kurzübersicht',
            'pdf.top.name'        => 'Name',
            'pdf.top.reference'   => 'Referenz',
            'pdf.top.generated'   => 'Erstellt am',
            'pdf.top.hint'        => 'Hinweis',
            'pdf.top.keep_note'   => 'Bitte bewahren Sie dieses Dokument für Ihre Unterlagen auf.',
            'pdf.hint_placeholder'=> '[PLATZHALTER: Textbaustein vom Kunden folgt]',
            'pdf.sec1.title' => '1) Persönliche Daten',
            'pdf.sec2.title' => '2) Weitere Kontaktdaten',
            'pdf.sec3.title' => '3) Schule & Interessen',
            'pdf.sec4.title' => '4) Unterlagen',
            'pdf.lbl.name'            => 'Name',
            'pdf.lbl.vorname'         => 'Vorname',
            'pdf.lbl.gender'          => 'Geschlecht',
            'pdf.lbl.dob'             => 'Geburtsdatum',
            'pdf.lbl.birthplace'      => 'Geburtsort/Land',
            'pdf.lbl.nationality'     => 'Staatsangehörigkeit',
            'pdf.lbl.address'         => 'Adresse',
            'pdf.lbl.phone'           => 'Telefon',
            'pdf.lbl.email_optional'  => 'E-Mail (optional)',
            'pdf.lbl.more'            => 'Weitere Angaben',
            'pdf.lbl.school_current'  => 'Aktuelle Schule',
            'pdf.lbl.teacher'         => 'Lehrer*in',
            'pdf.lbl.teacher_email'   => 'E-Mail Lehrkraft',
            'pdf.lbl.since_school'    => 'Seit wann an der Schule',
            'pdf.lbl.years_in_de'     => 'Seit wann in Deutschland',
            'pdf.lbl.family_lang'     => 'Familiensprache',
            'pdf.lbl.de_level'        => 'Deutsch-Niveau',
            'pdf.lbl.school_origin'   => 'Schule im Herkunftsland',
            'pdf.lbl.years_origin'    => 'Jahre Schule im Herkunftsland',
            'pdf.lbl.interests'       => 'Interessen',
            'pdf.lbl.report'          => 'Halbjahreszeugnis',
            'pdf.lbl.cv'              => 'Lebenslauf',
            'pdf.lbl.report_later'    => 'Zeugnis später nachreichen',
            'pdf.uploaded'     => 'hochgeladen',
            'pdf.not_uploaded' => 'nicht hochgeladen',
            'pdf.contacts.none' => '–',
            'pdf.contacts.th.role' => 'Rolle',
            'pdf.contacts.th.name' => 'Name/Einrichtung',
            'pdf.contacts.th.tel'  => 'Telefon',
            'pdf.contacts.th.mail' => 'E-Mail',
            'pdf.contacts.th.note' => 'Notiz',
            'pdf.gender.m' => 'männlich',
            'pdf.gender.w' => 'weiblich',
            'pdf.gender.d' => 'divers',
            'pdf.yes' => 'Ja',
            'pdf.no'  => 'Nein',
            'pdf.sec4.note' => 'Dieses Dokument ist eine automatisch erzeugte Zusammenfassung der eingegebenen Daten.',
            'pdf.filename_prefix' => 'Bewerbung',

            // =====================
            // ACCESS CREATE (DE)
            // =====================
            'access_create.title'         => 'Mit E-Mail fortfahren',
            'access_create.lead'          => 'Sie können sich mit Ihrem Zugang anmelden oder einen neuen Zugang erstellen.',
            'access_create.tabs_login'    => 'Anmelden',
            'access_create.tabs_register' => 'Neuen Zugang erstellen',
            'access_create.login_title' => 'Anmelden (bestehender Zugang)',
            'access_create.login_text'  => 'Bitte geben Sie Ihre E-Mail-Adresse und Ihr Passwort ein.',
            'access_create.email_label' => 'E-Mail-Adresse',
            'access_create.pass_label'  => 'Passwort',
            'access_create.login_btn'   => 'Anmelden',
            'access_create.login_err'   => 'E-Mail/Passwort ist falsch oder der Zugang ist nicht verifiziert.',
            'access_create.reg_title'     => 'Neuen Zugang erstellen',
            'access_create.reg_text'      => 'Wir senden Ihnen einen 6-stelligen Bestätigungscode. Nach erfolgreicher Bestätigung erhalten Sie Ihr Passwort per E-Mail.',
            'access_create.consent_label' => 'Ich stimme zu, dass meine E-Mail für den Anmeldeprozess verwendet wird.',
            'access_create.send_btn'      => 'Code senden',
            'access_create.code_label'    => 'Bestätigungscode',
            'access_create.verify_btn'    => 'Code prüfen',
            'access_create.resend'        => 'Code erneut senden',
            'access_create.info_sent'    => 'Wir haben Ihnen einen Code gesendet. Bitte prüfen Sie auch den Spam-Ordner.',
            'access_create.ok_verified'  => 'E-Mail bestätigt. Passwort wurde gesendet. Sie können sich jetzt anmelden.',
            'access_create.email_in_use' => 'Diese E-Mail hat bereits einen Zugang. Bitte melden Sie sich an.',
            'access_create.error_email'     => 'Bitte geben Sie eine gültige E-Mail-Adresse an.',
            'access_create.error_consent'   => 'Bitte stimmen Sie der Nutzung Ihrer E-Mail zu.',
            'access_create.error_rate'      => 'Zu viele Versuche. Bitte warten Sie kurz und versuchen Sie es erneut.',
            'access_create.error_code'      => 'Der Code ist ungültig oder abgelaufen.',
            'access_create.error_resend'    => 'Erneuter Versand nicht möglich. Starten Sie bitte erneut.',
            'access_create.error_mail_send' => 'E-Mail-Versand fehlgeschlagen. Bitte später erneut versuchen.',
            'access_create.error_db'        => 'Serverfehler (DB).',
            'access_create.back'   => 'Zurück',
            'access_create.cancel' => 'Abbrechen',
            'access_create.mail_subject' => 'Ihr Passwort für die Online-Anmeldung',
            'access_create.mail_body'    => "Ihr Zugang wurde erstellt.\n\nE-Mail: {email}\nPasswort: {password}\n\nBitte bewahren Sie das Passwort sicher auf.",

            // =====================
            // ACCESS PORTAL (DE)
            // =====================
            'access_portal.title'         => 'Meine Bewerbungen',
            'access_portal.lead'          => 'Hier sehen Sie Ihre Bewerbungen. Sie können eine bestehende Bewerbung fortsetzen oder eine neue starten.',
            'access_portal.max_hint'      => '{email} · max. {max} Bewerbungen',
            'access_portal.btn_new'       => 'Neue Bewerbung starten',
            'access_portal.btn_open'      => 'Öffnen',
            'access_portal.btn_logout'    => 'Abmelden',
            'access_portal.th_ref'        => 'ID',
            'access_portal.th_status'     => 'Status',
            'access_portal.th_created'    => 'Erstellt',
            'access_portal.th_updated'    => 'Aktualisiert',
            'access_portal.th_token'      => 'Token',
            'access_portal.th_action'     => 'Aktion',
            'access_portal.status_draft'     => 'Entwurf',
            'access_portal.status_submitted' => 'Abgeschickt',
            'access_portal.status_withdrawn' => 'Zurückgezogen',
            'access_portal.limit_reached' => 'Sie haben die maximale Anzahl an Bewerbungen für diese E-Mail erreicht.',
            'access_portal.no_apps'       => 'Noch keine Bewerbungen vorhanden.',
            'access_portal.err_generic'   => 'Es ist ein Fehler aufgetreten.',
            'access_portal.csrf_invalid'  => 'Ungültige Anfrage.',

            // =====================
            // ACCESS LOGIN (DE)
            // =====================
            'access_login.title'             => 'Zugriff auf Bewerbung/en',
            'access_login.lead'              => 'Hier können Sie eine bereits begonnene oder abgeschickte Bewerbung wieder aufrufen.',
            'access_login.login_box_title'   => 'Anmeldung mit Access-Token',
            'access_login.login_box_text'    => 'Geben Sie bitte Ihren persönlichen Zugangscode (Access-Token) und Ihr Geburtsdatum ein.',
            'access_login.token_label'       => 'Access-Token',
            'access_login.dob_label'         => 'Geburtsdatum (TT.MM.JJJJ)',
            'access_login.login_btn'         => 'Zugriff',
            'access_login.back'              => 'Zurück zur Startseite',
            'access_login.login_ok'          => 'Bewerbung wurde geladen.',
            'access_login.login_error'       => 'Kombination aus Access-Token und Geburtsdatum wurde nicht gefunden.',
            'access_login.login_error_token' => 'Bitte geben Sie einen gültigen Access-Token ein.',
            'access_login.login_error_dob'   => 'Bitte geben Sie ein gültiges Geburtsdatum im Format TT.MM.JJJJ ein.',
            'access_login.csrf_invalid'      => 'Ungültige Anfrage.',
            'access_login.internal_error'    => 'Interner Fehler.',
            'access_login.load_error'        => 'Beim Laden der Bewerbung ist ein Fehler aufgetreten.',

            // =====================
            // PRIVACY (DE)
            // =====================
            'privacy.title' => 'Datenschutz',
            'privacy.h1' => 'Datenschutzhinweise für die Online-Bewerbung „BES Sprache und Integration“',
            'privacy.s1_title' => '1. Verantwortliche Stelle',
            'privacy.s1_body_html' => '<strong>Stadt Oldenburg / Berufsbildende Schulen</strong><br>(Bitte genaue Dienststellen-/Schulbezeichnung, Anschrift, Telefon, E-Mail eintragen)',
            'privacy.s2_title' => '2. Datenschutzbeauftragte*r',
            'privacy.s2_body_html' => '(Kontaktdaten der/des behördlichen Datenschutzbeauftragten eintragen)',
            'privacy.s3_title' => '3. Zwecke der Verarbeitung',
            'privacy.s3_li1' => 'Entgegennahme und Bearbeitung Ihrer Bewerbung zur Aufnahme in die Sprachlernklasse („BES Sprache und Integration“)',
            'privacy.s3_li2' => 'Kommunikation mit Ihnen (Rückfragen, Mitteilungen zur Aufnahmeentscheidung)',
            'privacy.s3_li3' => 'Schulorganisatorische Planung (Zuweisung zu einer BBS)',
            'privacy.s4_title' => '4. Rechtsgrundlagen',
            'privacy.s4_li1' => 'Art. 6 Abs. 1 lit. e DSGVO i. V. m. den schulrechtlichen Vorschriften des Landes Niedersachsen',
            'privacy.s4_li2' => 'Art. 6 Abs. 1 lit. c DSGVO (Erfüllung rechtlicher Verpflichtungen)',
            'privacy.s4_li3' => 'Art. 6 Abs. 1 lit. a DSGVO (Einwilligung), soweit freiwillige Angaben/Uploads erfolgen',
            'privacy.s5_title' => '5. Kategorien personenbezogener Daten',
            'privacy.s5_li1' => 'Stammdaten (Name, Vorname, Geburtsdaten, Staatsangehörigkeit, Anschrift, Kontaktdaten)',
            'privacy.s5_li2' => 'Schulische Informationen (aktuelle Schule, Sprachniveau, Interessen)',
            'privacy.s5_li3' => 'Optionale Unterlagen (z. B. letztes Halbjahreszeugnis)',
            'privacy.s5_li4' => 'Zusatzkontakte (Eltern/Betreuer/Einrichtungen)',
            'privacy.s6_title' => '6. Empfänger',
            'privacy.s6_body' => 'Innerhalb der Zuständigkeit der Stadt Oldenburg und der berufsbildenden Schulen. Eine Übermittlung an Dritte erfolgt nur, soweit rechtlich erforderlich (z. B. Schulbehörden) oder mit Ihrer Einwilligung.',
            'privacy.s7_title' => '7. Drittlandübermittlung',
            'privacy.s7_body' => 'Es findet keine Übermittlung in Drittländer statt.',
            'privacy.s8_title' => '8. Speicherdauer',
            'privacy.s8_body' => 'Ihre Daten werden für die Dauer des Bewerbungs- bzw. Aufnahmeverfahrens und gemäß den gesetzlichen Aufbewahrungsfristen gespeichert und anschließend gelöscht.',
            'privacy.s9_title' => '9. Ihre Rechte',
            'privacy.s9_li1' => 'Auskunft (Art. 15 DSGVO), Berichtigung (Art. 16), Löschung (Art. 17), Einschränkung (Art. 18)',
            'privacy.s9_li2' => 'Widerspruch (Art. 21) gegen Verarbeitungen im öffentlichen Interesse',
            'privacy.s9_li3' => 'Widerruf erteilter Einwilligungen (Art. 7 Abs. 3) mit Wirkung für die Zukunft',
            'privacy.s9_li4' => 'Beschwerderecht bei der Aufsichtsbehörde: Landesbeauftragte*r für den Datenschutz Niedersachsen',
            'privacy.s10_title' => '10. Hosting & Protokolle',
            'privacy.s10_body' => 'Die Anwendung wird auf Servern der Stadt bzw. im kommunalen Rechenzentrum betrieben. Es werden nur technisch notwendige Daten verarbeitet (z. B. Server-Logfiles zur Fehlersuche). Keine Einbindung externer CDNs. Es wird ausschließlich ein sprachbezogenes Cookie gesetzt.',
            'privacy.s11_title' => '11. Cookies',
            'privacy.s11_li1_html' => '<strong>lang</strong> – speichert die ausgewählte Sprache (Gültigkeit 12 Monate). Zweck: Benutzerfreundlichkeit.',
            'privacy.s11_li2' => 'PHP-Session – technisch erforderlich für den Formular-Ablauf, wird beim Beenden der Sitzung gelöscht.',
            'privacy.stand_label' => 'Stand',
            'privacy.stand_hint' => 'Bitte prüfen Sie regelmäßig, ob sich Änderungen ergeben haben.',
            'privacy.back_home' => 'Zur Startseite',

            // =====================
            // IMPRINT (DE)
            // =====================
            'imprint.title' => 'Impressum',
            'imprint.h1' => 'Impressum',
            'imprint.s1_title' => 'Diensteanbieter',
            'imprint.s1_body_html' => '<strong>Stadt ***</strong><br>Berufsbildende Schulen<br>(genaue Anschrift eintragen)<br>Telefon: (bitte ergänzen)<br>E-Mail: (bitte ergänzen)',
            'imprint.s2_title' => 'Vertretungsberechtigt',
            'imprint.s2_body_html' => '(z. B. Oberbürgermeister/in der Stadt ****<br>oder Schulleitung der jeweiligen BBS)',
            'imprint.s3_title' => 'Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV',
            'imprint.s3_body_html' => '(Name, Funktion, Kontakt, z. B. Schulleitung der BBS oder Pressestelle)',
            'imprint.s4_title' => 'Umsatzsteuer-ID',
            'imprint.s4_body_html' => '(sofern vorhanden; ansonsten kann dieser Abschnitt entfallen)',
            'imprint.s5_title' => 'Aufsichtsbehörde',
            'imprint.s5_body' => '(zuständige Kommunalaufsicht / Schulbehörde, z. B. Regionalabteilung der Landesschulbehörde)',
            'imprint.s6_title' => 'Haftung für Inhalte',
            'imprint.s6_body' => 'Die Inhalte unserer Seiten wurden mit größter Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte können wir jedoch keine Gewähr übernehmen. Als öffentliche Stelle sind wir gemäß § 7 Abs. 1 TMG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich.',
            'imprint.s7_title' => 'Haftung für Links',
            'imprint.s7_body' => 'Unser Angebot enthält keine externen Inhalte, die personenbezogene Daten an Dritte übertragen. Soweit wir auf Informationsangebote anderer öffentlicher Stellen verlinken, übernehmen wir keine Verantwortung für deren Inhalte.',
            'imprint.s8_title' => 'Urheberrecht',
            'imprint.s8_body' => 'Die durch die Stadt Oldenburg erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. Beiträge Dritter sind als solche gekennzeichnet. Die Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der Grenzen des Urheberrechtes bedürfen der schriftlichen Zustimmung der Stadt Oldenburg oder des jeweiligen Rechteinhabers.',
            'imprint.stand_label' => 'Stand',
            'imprint.stand_hint' => 'Diese Angaben gelten für das Online-Formular „BES Sprache und Integration“.',
            'imprint.back_home' => 'Zur Startseite',

            // =====================
            // VERIFY EMAIL (DE)
            // =====================
            'verify_email.title' => 'E-Mail verifizieren',
            'verify_email.h1' => 'E-Mail verifizieren',
            'verify_email.lead_sent' => 'Wir haben einen Bestätigungscode an {email} gesendet.',
            'verify_email.lead_generic' => 'Bitte geben Sie den per E-Mail erhaltenen Bestätigungscode ein. Falls Sie keine E-Mail sehen, können Sie sich den Code an Ihre Adresse senden lassen.',
            'verify_email.code_label' => 'Bestätigungscode (6 Ziffern)',
            'verify_email.email_label' => 'Ihre E-Mail-Adresse',
            'verify_email.btn_verify' => 'Bestätigen',
            'verify_email.btn_resend' => 'Code erneut senden',
            'verify_email.hint_spam' => 'Bitte prüfen Sie auch den Spam-Ordner.',
            'verify_email.back' => 'Zurück',
            'verify_email.csrf_invalid' => 'Ungültige Anfrage.',
            'verify_email.ok_verified' => 'E-Mail erfolgreich verifiziert.',
            'verify_email.ok_sent' => 'Neuer Code wurde an {email} gesendet.',
            'verify_email.warn_cooldown' => 'Bitte warten Sie kurz, bevor Sie den Code erneut anfordern.',
            'verify_email.error_send' => 'Versand fehlgeschlagen. Bitte später erneut versuchen.',
            'verify_email.error_email' => 'Bitte eine gültige E-Mail-Adresse eingeben.',
            'verify_email.error_no_session' => 'Kein aktiver Verifizierungsvorgang gefunden. Bitte fordern Sie einen neuen Code an.',
            'verify_email.error_expired' => 'Code ungültig oder abgelaufen.',
            'verify_email.error_invalid' => 'Code ungültig oder abgelaufen.',
            'verify_email.error_code_format' => 'Bitte geben Sie einen gültigen 6-stelligen Code ein.',
            'verify_email.error_rate' => 'Zu viele Versuche. Bitte fordern Sie einen neuen Code an.',

            // =====================
            // VALIDATION (DE) – gesammelt (damit keine Überschreibungen)
            // =====================
            'val.required' => 'Erforderlich.',
            'val.only_letters' => 'Bitte nur Buchstaben.',
            'val.gender_choose' => 'Bitte wählen Sie ein Geschlecht aus.',
            'val.date_format' => 'TT.MM.JJJJ',
            'val.date_invalid' => 'Ungültiges Datum.',
            'val.plz_whitelist' => 'Nur PLZ aus Oldenburg (26121–26135).',
            'val.phone_vorwahl' => 'Vorwahl 2–6 Ziffern.',
            'val.phone_nummer' => 'Rufnummer 3–12 Ziffern.',
            'val.email_invalid' => 'Ungültige E-Mail.',
            'val.email_no_iserv' => 'Bitte private E-Mail (keine IServ).',
            'val.max_1500' => 'Bitte maximal 1500 Zeichen.',
            'val.kontakt_row_name_missing' => 'Name/Bezeichnung fehlt',
            'val.kontakt_row_tel_or_mail'  => 'Telefon ODER E-Mail angeben',
            'val.kontakt_row_mail_invalid' => 'E-Mail ungültig',
            'val.kontakt_row_tel_invalid'  => 'Telefon ungültig',
            'val.school_free_required' => 'Bitte Schulname (Freitext) angeben.',
            'val.school_invalid'       => 'Bitte gültige Schule wählen oder „Andere / nicht gelistet“.',
            'val.since_required'       => 'Bitte Monat+Jahr oder Freitext angeben.',
            'val.month_invalid'        => 'Monat muss 01–12 sein.',
            'val.year_invalid'         => 'Bitte gültiges Jahr.',
            'val.number_required'      => 'Bitte Zahl angeben.',
            'val.choose'               => 'Bitte auswählen.',
            'val.herkunft_years'       => 'Bitte Anzahl Jahre angeben.',
            'val.level_invalid'        => 'Ungültige Auswahl.',
            'val.interests_min1'       => 'Bitte mindestens 1 Bereich wählen.',
            'val.interests_max2'       => 'Bitte höchstens 2 Bereiche wählen.',
            'js.hint_years_gt3'  => 'Hinweis: Sie haben mehr als 3 Jahre in Deutschland. Bitte bewerben Sie sich über {link}.',
            'js.hint_level_b1p'  => 'Hinweis: Mit Deutsch-Niveau B1 oder höher bitte reguläre BBS-Bewerbung über {link}.',
        ],

        // ============================================================
        // EN
        // ============================================================
        'en' => [
            'index.title' => 'Welcome to the Online Registration – Language Classes',
            'index.lead'  => 'This service is for newly arrived people in Oldenburg. The form helps us contact you and find suitable options.',
            'index.bullets' => [
                'Please have your contact details and ID/passport ready (if available).',
                'You can complete the form in multiple languages.',
                'Your data is handled confidentially under GDPR.',
            ],
            'index.info_p' => [
                'Dear student,',
                'With this application you apply for a place in the language learning class “BES Language and Integration” at a vocational school (BBS) in Oldenburg. You are not applying to a specific BBS. After 20 February you will be informed which school will accept you.',
                'You can be admitted only if all of the following conditions are met:',
            ],
            'index.info_bullets' => [
                'You need intensive German support (German level below B1).',
                'At the start of the next school year you have been in Germany for no more than 3 years.',
                'On 30 September of this year you are at least 16 and at most 18 years old.',
                'You are subject to compulsory schooling in the next school year.',
            ],
            'index.access_title' => 'Privacy & Access',
            'index.access_intro' => 'You can proceed with or without an email address. Access to saved applications is only possible with your personal access token and date of birth.',
            'index.access_points' => [
                '<strong>With email:</strong> You receive a verification code and can create multiple applications and open them later.',
                '<strong>Without email:</strong> You receive a personal access token. Please write it down or take a photo — without a verified email recovery is not possible.',
            ],
            'index.btn_noemail' => 'Proceed without email',
            'index.btn_create'  => 'Continue with email',
            'index.btn_load'    => 'Access application(s)',
            'index.lang_label'  => 'Language:',

            'personal.page_title' => 'Step 1/4 – Personal details',
            'personal.h1' => 'Step 1/4 – Personal details',
            'personal.required_hint' => 'Required fields are highlighted with a blue border.',
            'personal.form_error_hint' => 'Please check the highlighted fields.',
            'personal.alert_email_title' => 'Email login active:',
            'personal.alert_email_line1' => 'Signed in with the email address {email}.',
            'personal.alert_email_line2' => 'This email is only used for your access token and to find your application again.',
            'personal.alert_email_line3' => 'Below you can enter the student’s email address (if available).',
            'personal.alert_noemail_title' => 'Note (without email):',
            'personal.alert_noemail_body' => 'Please write down or take a photo of your access token shown after saving this page. Without a verified email, recovery is only possible with token + date of birth.',
            'personal.label.name' => 'Last name',
            'personal.label.vorname' => 'First name',
            'personal.label.geschlecht' => 'Gender',
            'personal.gender.m' => 'male',
            'personal.gender.w' => 'female',
            'personal.gender.d' => 'diverse',
            'personal.label.geburtsdatum' => 'Date of birth',
            'personal.label.geburtsdatum_hint' => '(DD.MM.YYYY)',
            'personal.placeholder.geburtsdatum' => 'DD.MM.YYYY',
            'personal.age_hint' => 'Note: If you are younger than 16 or older than 18 on 30 Sep {year}, you cannot be admitted to the language class. Please apply for another class here:',
            'personal.age_redirect_msg' => "Note: If you are younger than 16 or older than 18 on 30 Sep {year}, you cannot be admitted to the language class.\nPlease apply for another class here:\n{url}",
            'personal.label.geburtsort_land' => 'Place / country of birth',
            'personal.label.staatsang' => 'Nationality',
            'personal.label.strasse' => 'Street, no.',
            'personal.label.plz' => 'Postal code',
            'personal.plz_choose' => '– please choose –',
            'personal.plz_hint' => 'Oldenburg only.',
            'personal.label.wohnort' => 'City',
            'personal.label.telefon' => 'Phone number',
            'personal.label.telefon_vorwahl_help' => 'Area code with/without 0',
            'personal.label.telefon_nummer_help' => 'Number',
            'personal.placeholder.telefon_vorwahl' => '(0)441',
            'personal.placeholder.telefon_nummer' => '123456',
            'personal.label.email' => 'Student email address (optional, no IServ address)',
            'personal.email_help' => 'This email belongs to the student (if available) and is independent from the login email.',
            'personal.placeholder.email' => 'name@example.org',
            'personal.label.kontakte' => 'Additional contact details',
            'personal.kontakte_hint' => '(e.g. parents, caregiver, institution)',
            'personal.kontakte_error' => 'Please check the additional contacts.',
            'personal.kontakte_add' => '+ Add contact',
            'personal.kontakte_remove_title' => 'Remove contact',
            'personal.table.role' => 'Role',
            'personal.table.name' => 'Name / institution',
            'personal.table.tel'  => 'Phone',
            'personal.table.mail' => 'Email',
            'personal.table.note_header' => 'Note',
            'personal.placeholder.kontakt_name' => 'Name or description',
            'personal.placeholder.kontakt_tel'  => '+49 …',
            'personal.placeholder.kontakt_note' => 'e.g. availability, language, notes',
            'personal.contact_role.none' => '–',
            'personal.contact_role.mutter' => 'Mother',
            'personal.contact_role.vater' => 'Father',
            'personal.contact_role.elternteil' => 'Parent',
            'personal.contact_role.betreuer' => 'Caregiver',
            'personal.contact_role.einrichtung' => 'Institution',
            'personal.contact_role.sonstiges' => 'Other',
            'personal.label.weitere_angaben' => 'Other information (e.g. support needs):',
            'personal.placeholder.weitere_angaben' => 'Here you can add support needs, special education support, or other notes.',
            'personal.weitere_angaben_help' => 'Optional. Max 1500 characters.',
            'personal.btn.cancel' => 'Cancel',
            'personal.btn.next' => 'Next',
            'personal.dsgvo_text_prefix' => 'I have read the',
            'personal.dsgvo_link_text' => 'privacy notice',
            'personal.dsgvo_text_suffix' => 'and I agree.',

            'school.page_title' => 'Step 2/4 – School & interests',
            'school.h1' => 'Step 2/4 – School & interests',
            'school.required_hint' => 'Required fields are highlighted with a blue border.',
            'school.form_error_hint' => 'Please check the highlighted fields.',
            'school.top_hint_title' => 'Note:',
            'school.top_hint_body'  => 'If you have been in Germany for <u>more than 3 years</u> or already speak German at level <u>B1</u> or higher, you cannot be admitted to the language class. Please apply for another class at a BBS here:',
            'school.bbs_link_label' => 'https://bbs-ol.de/',
            'school.autohints_title' => 'Hints',
            'school.label.schule_aktuell' => 'Current school',
            'school.search_placeholder'   => 'Search school … (name, street, postal code)',
            'school.select_choose'        => 'Please choose …',
            'school.option_other'         => 'Other / not listed',
            'school.other_placeholder'    => 'School name, street, city (free text)',
            'school.label.teacher'        => 'responsible teacher',
            'school.label.teacher_mail'   => 'Email of responsible teacher',
            'school.label.herkunft'       => 'Did you attend school in your country of origin?',
            'school.yes'                  => 'Yes',
            'school.no'                   => 'No',
            'school.label.herkunft_years' => 'If yes: how many years?',
            'school.label.since'          => 'Since when at a school in Germany?',
            'school.since_month'          => 'Month (MM)',
            'school.since_year_ph'        => 'Year (YYYY)',
            'school.since_help'           => 'Enter month+year <strong>or</strong> use the free-text field.',
            'school.label.since_text'     => 'Alternative: free text (e.g. “since autumn 2023”)',
            'school.label.years_in_de'    => 'How many years have you been in Germany?',
            'school.years_in_de_help'     => 'Note: &gt; 3 years → Please use the regular BBS application via {link}.',
            'school.label.family_lang'    => 'Family language / first language',
            'school.label.level'          => 'What is your German level?',
            'school.level_choose'         => 'Please choose …',
            'school.level_help'           => 'Note: B1 or higher → regular BBS application via {link}.',
            'school.label.interests'      => 'Interests (min. 1, max. 2)',
            'school.btn.back'             => 'Back',
            'school.btn.next'             => 'Next',

            'upload.page_title' => 'Step 3/4 – Documents (optional)',
            'upload.h1'         => 'Step 3/4 – Documents (optional)',
            'upload.intro'      => 'You can upload documents here. Allowed formats: <strong>PDF</strong>, <strong>JPG</strong>, <strong>PNG</strong>. Maximum file size: <strong>{max_mb} MB</strong> per file.',
            'upload.type.zeugnis'    => 'Latest report card (last term)',
            'upload.type.lebenslauf' => 'CV / résumé',
            'upload.type_hint'       => '(PDF/JPG/PNG, max. {max_mb} MB)',
            'upload.btn.remove' => 'Remove',
            'upload.btn.back'   => 'Back',
            'upload.btn.next'   => 'Next',
            'upload.saved_prefix' => 'Already saved:',
            'upload.empty'        => 'No file uploaded yet.',
            'upload.saved_html'   => 'Already saved: <strong>{filename}</strong>, {size_kb} KB, uploaded on {uploaded_at}',
            'upload.checkbox.zeugnis_spaeter' => 'I will submit the report card after acceptance.',
            'upload.flash.no_access' => 'No valid access found. Please start again.',
            'upload.flash.saved'     => 'Upload information saved.',
            'upload.js.uploading'         => 'Uploading …',
            'upload.js.unexpected'        => 'Unexpected response from server.',
            'upload.js.upload_failed'     => 'Upload failed.',
            'upload.js.delete_confirm'    => 'Remove the uploaded file?',
            'upload.js.delete_failed'     => 'Delete failed.',
            'upload.js.remove_confirm_btn'=> 'Remove file?',
            'upload.ajax.invalid_method' => 'Invalid method',
            'upload.ajax.invalid_csrf'   => 'Invalid CSRF token',
            'upload.ajax.no_access'      => 'No valid access.',
            'upload.ajax.invalid_field'  => 'Invalid field',
            'upload.ajax.no_file_sent'   => 'No file sent',
            'upload.ajax.no_file_selected' => 'No file selected',
            'upload.ajax.upload_error'   => 'Upload error (code {code})',
            'upload.ajax.too_large'      => 'File larger than {max_mb} MB',
            'upload.ajax.mime_only'      => 'Only PDF, JPG or PNG allowed',
            'upload.ajax.ext_only'       => 'Invalid file extension (only pdf/jpg/jpeg/png)',
            'upload.ajax.cannot_save'    => 'Could not save file',
            'upload.ajax.unknown_action' => 'Unknown action',
            'upload.ajax.server_error'   => 'Server error during upload',

            'review.page_title' => 'Step 4/4 – Summary & submit application',
            'review.h1'          => 'Step 4/4 – Summary & submit application',
            'review.subhead'     => 'Please review your information. Clicking “Submit” will send your data.',
            'review.readonly_alert' => 'This application has already been submitted. It can only be viewed and cannot be changed or submitted again.',
            'review.info.p1' => 'Dear student,',
            'review.info.p2' => 'when you click <strong>“Submit”</strong>, you apply for <strong>BES Language and Integration</strong> at a vocational school (BBS) in Oldenburg.',
            'review.info.p3' => 'This is not a final enrollment, but an <strong>application</strong>. After <strong>20 Feb</strong> you will be informed whether / at which BBS you are accepted. Please check your mailbox and your email regularly. Make sure your name is visible on the mailbox so you can receive letters.',
            'review.info.p4' => 'With the acceptance from the school you will be asked to submit these files (if you have not uploaded them today):',
            'review.info.li1' => 'latest report card (last term)',
            'review.acc.personal' => 'Personal details',
            'review.acc.school'   => 'School & interests',
            'review.acc.uploads'  => 'Documents',
            'review.lbl.name'            => 'Last name',
            'review.lbl.vorname'         => 'First name',
            'review.lbl.geschlecht'      => 'Gender',
            'review.lbl.geburtsdatum'    => 'Date of birth',
            'review.lbl.geburtsort'      => 'Place / country of birth',
            'review.lbl.staatsang'       => 'Nationality',
            'review.lbl.strasse'         => 'Street, no.',
            'review.lbl.plz_ort'         => 'Postal code / city',
            'review.lbl.telefon'         => 'Phone',
            'review.lbl.email'           => 'Email (student, optional)',
            'review.lbl.weitere_angaben' => 'Other information (e.g. support needs)',
            'review.contacts.title'   => 'Additional contacts',
            'review.contacts.optional'=> 'optional',
            'review.contacts.none'    => '–',
            'review.contacts.th.role' => 'Role',
            'review.contacts.th.name' => 'Name / institution',
            'review.contacts.th.tel'  => 'Phone',
            'review.contacts.th.mail' => 'Email',
            'review.contacts.note'    => 'Note:',
            'review.lbl.school_current' => 'Current school',
            'review.lbl.klassenlehrer'  => 'Responsible teacher',
            'review.lbl.mail_lehrkraft' => 'Teacher email',
            'review.lbl.since'          => 'Since when at school',
            'review.lbl.years_de'       => 'Years in Germany',
            'review.lbl.family_lang'    => 'Family language / first language',
            'review.lbl.de_level'       => 'German level',
            'review.lbl.school_origin'  => 'School in country of origin',
            'review.lbl.years_origin'   => 'Years of schooling in country of origin',
            'review.lbl.interests'      => 'Interests',
            'review.lbl.zeugnis'      => 'Report card',
            'review.lbl.lebenslauf'   => 'CV / résumé',
            'review.lbl.later'        => 'Submit later',
            'review.badge.uploaded'   => 'uploaded',
            'review.badge.not_uploaded'=> 'not uploaded',
            'review.yes'              => 'Yes',
            'review.no'               => 'No',
            'review.btn.home'   => 'Home',
            'review.btn.newapp' => 'Submit another application',
            'review.btn.back'   => 'Back',
            'review.btn.submit' => 'Submit',
            'review.err.invalid_request' => 'Invalid request.',
            'review.flash.already_submitted' => 'This application has already been submitted and cannot be submitted again or edited.',
            'review.flash.no_token'          => 'No valid access token. Please start again.',
            'review.err.not_found_token'     => 'No application found for this token.',
            'review.flash.submit_error'      => 'An error occurred while submitting. Please try again later.',
            'review.gender.m' => 'male',
            'review.gender.w' => 'female',
            'review.gender.d' => 'diverse',
            'review.value.empty' => '–',

            'status.hdr_title'   => 'Application saved successfully',
            'status.hdr_message' => 'Your application has been submitted.',
            'status.h1' => 'Your application was saved successfully.',
            'status.success.title' => 'Thank you!',
            'status.success.body'  => 'Your application has been submitted and will now be processed.',
            'status.info.title' => 'Important note',
            'status.info.body'  => '<em>[PLACEHOLDER: customer text to follow]</em>',
            'status.btn.pdf'     => 'Download / print PDF',
            'status.btn.newapp'  => 'Start another application',
            'status.btn.home'    => 'Home',
            'status.ref' => 'Reference: Application #{id}',
            'status.err.invalid_request' => 'Invalid request.',

            'pdf.err.autoload_missing' => 'Composer autoload not found. Please run "composer install".',
            'pdf.err.no_token'         => 'No valid access token. Please start again.',
            'pdf.err.not_found'        => 'Application not found.',
            'pdf.err.server'           => 'Server error while generating PDF.',
            'pdf.header_title' => 'Application – Summary',
            'pdf.footer_auto'  => 'Automatically generated document',
            'pdf.footer_page'  => 'Page {cur} / {max}',
            'pdf.meta.ref'        => 'Application #{id}',
            'pdf.meta.created_at' => 'Created on',
            'pdf.meta.status'     => 'Status',
            'pdf.top.title'       => 'Quick overview',
            'pdf.top.name'        => 'Name',
            'pdf.top.reference'   => 'Reference',
            'pdf.top.generated'   => 'Created on',
            'pdf.top.hint'        => 'Note',
            'pdf.top.keep_note'   => 'Please keep this document for your records.',
            'pdf.hint_placeholder'=> '[PLACEHOLDER: customer text to follow]',
            'pdf.sec1.title' => '1) Personal details',
            'pdf.sec2.title' => '2) Additional contacts',
            'pdf.sec3.title' => '3) School & interests',
            'pdf.sec4.title' => '4) Documents',
            'pdf.lbl.name'            => 'Last name',
            'pdf.lbl.vorname'         => 'First name',
            'pdf.lbl.gender'          => 'Gender',
            'pdf.lbl.dob'             => 'Date of birth',
            'pdf.lbl.birthplace'      => 'Place/country of birth',
            'pdf.lbl.nationality'     => 'Nationality',
            'pdf.lbl.address'         => 'Address',
            'pdf.lbl.phone'           => 'Phone',
            'pdf.lbl.email_optional'  => 'Email (optional)',
            'pdf.lbl.more'            => 'Other information',
            'pdf.lbl.school_current'  => 'Current school',
            'pdf.lbl.teacher'         => 'Teacher',
            'pdf.lbl.teacher_email'   => 'Teacher email',
            'pdf.lbl.since_school'    => 'Since when at school',
            'pdf.lbl.years_in_de'     => 'Since when in Germany',
            'pdf.lbl.family_lang'     => 'Family language',
            'pdf.lbl.de_level'        => 'German level',
            'pdf.lbl.school_origin'   => 'School in country of origin',
            'pdf.lbl.years_origin'    => 'Years of schooling in country of origin',
            'pdf.lbl.interests'       => 'Interests',
            'pdf.lbl.report'          => 'Report card',
            'pdf.lbl.cv'              => 'CV / résumé',
            'pdf.lbl.report_later'    => 'Submit report card later',
            'pdf.uploaded'     => 'uploaded',
            'pdf.not_uploaded' => 'not uploaded',
            'pdf.contacts.none' => '–',
            'pdf.contacts.th.role' => 'Role',
            'pdf.contacts.th.name' => 'Name/institution',
            'pdf.contacts.th.tel'  => 'Phone',
            'pdf.contacts.th.mail' => 'Email',
            'pdf.contacts.th.note' => 'Note',
            'pdf.gender.m' => 'male',
            'pdf.gender.w' => 'female',
            'pdf.gender.d' => 'diverse',
            'pdf.yes' => 'Yes',
            'pdf.no'  => 'No',
            'pdf.sec4.note' => 'This document is an automatically generated summary of the entered data.',
            'pdf.filename_prefix' => 'Application',

            'access_create.title'         => 'Continue with email',
            'access_create.lead'          => 'Sign in with your account or create a new account.',
            'access_create.tabs_login'    => 'Sign in',
            'access_create.tabs_register' => 'Create account',
            'access_create.login_title' => 'Sign in (existing account)',
            'access_create.login_text'  => 'Please enter your email address and password.',
            'access_create.email_label' => 'Email address',
            'access_create.pass_label'  => 'Password',
            'access_create.login_btn'   => 'Sign in',
            'access_create.login_err'   => 'Email/password is incorrect or the account is not verified.',
            'access_create.reg_title'     => 'Create a new account',
            'access_create.reg_text'      => 'We will send you a 6-digit verification code. After successful verification, you will receive your password by email.',
            'access_create.consent_label' => 'I agree that my email will be used for the registration process.',
            'access_create.send_btn'      => 'Send code',
            'access_create.code_label'    => 'Verification code',
            'access_create.verify_btn'    => 'Verify code',
            'access_create.resend'        => 'Resend code',
            'access_create.info_sent'    => 'We have sent you a code. Please also check your spam folder.',
            'access_create.ok_verified'  => 'Email verified. Password sent. You can sign in now.',
            'access_create.email_in_use' => 'This email already has an account. Please sign in.',
            'access_create.error_email'     => 'Please enter a valid email address.',
            'access_create.error_consent'   => 'Please agree to the use of your email.',
            'access_create.error_rate'      => 'Too many attempts. Please wait a moment and try again.',
            'access_create.error_code'      => 'The code is invalid or expired.',
            'access_create.error_resend'    => 'Resending is not possible. Please start again.',
            'access_create.error_mail_send' => 'Sending email failed. Please try again later.',
            'access_create.error_db'        => 'Server error (DB).',
            'access_create.back'   => 'Back',
            'access_create.cancel' => 'Cancel',
            'access_create.mail_subject' => 'Your password for the online registration',
            'access_create.mail_body'    => "Your account has been created.\n\nEmail: {email}\nPassword: {password}\n\nPlease keep this password safe.",

            'access_portal.title'         => 'My applications',
            'access_portal.lead'          => 'Here you can see your applications. You can continue an existing application or start a new one.',
            'access_portal.max_hint'      => '{email} · max. {max} applications',
            'access_portal.btn_new'       => 'Start new application',
            'access_portal.btn_open'      => 'Open',
            'access_portal.btn_logout'    => 'Sign out',
            'access_portal.th_ref'        => 'ID',
            'access_portal.th_status'     => 'Status',
            'access_portal.th_created'    => 'Created',
            'access_portal.th_updated'    => 'Updated',
            'access_portal.th_token'      => 'Token',
            'access_portal.th_action'     => 'Action',
            'access_portal.status_draft'     => 'Draft',
            'access_portal.status_submitted' => 'Submitted',
            'access_portal.status_withdrawn' => 'Withdrawn',
            'access_portal.limit_reached' => 'You have reached the maximum number of applications for this email.',
            'access_portal.no_apps'       => 'No applications yet.',
            'access_portal.err_generic'   => 'An error occurred.',
            'access_portal.csrf_invalid'  => 'Invalid request.',

            'access_login.title'             => 'Access application(s)',
            'access_login.lead'              => 'Here you can open an application you started or already submitted.',
            'access_login.login_box_title'   => 'Sign in with access token',
            'access_login.login_box_text'    => 'Please enter your personal access token and your date of birth.',
            'access_login.token_label'       => 'Access token',
            'access_login.dob_label'         => 'Date of birth (DD.MM.YYYY)',
            'access_login.login_btn'         => 'Access',
            'access_login.back'              => 'Back to home',
            'access_login.login_ok'          => 'Application loaded.',
            'access_login.login_error'       => 'Combination of access token and date of birth was not found.',
            'access_login.login_error_token' => 'Please enter a valid access token.',
            'access_login.login_error_dob'   => 'Please enter a valid date of birth in format DD.MM.YYYY.',
            'access_login.csrf_invalid'      => 'Invalid request.',
            'access_login.internal_error'    => 'Internal error.',
            'access_login.load_error'        => 'An error occurred while loading the application.',

            'privacy.title' => 'Privacy',
            'privacy.h1' => 'Privacy notice for the online application “BES Language and Integration”',
            'privacy.s1_title' => '1. Controller',
            'privacy.s1_body_html' => '<strong>City of Oldenburg / Vocational Schools</strong><br>(Please enter the exact department/school name, address, phone, email)',
            'privacy.s2_title' => '2. Data protection officer',
            'privacy.s2_body_html' => '(Enter contact details of the official data protection officer)',
            'privacy.s3_title' => '3. Purposes of processing',
            'privacy.s3_li1' => 'Receiving and processing your application for admission to the language learning class (“BES Language and Integration”)',
            'privacy.s3_li2' => 'Communication with you (questions, notifications about admission decisions)',
            'privacy.s3_li3' => 'School organization planning (assignment to a BBS)',
            'privacy.s4_title' => '4. Legal bases',
            'privacy.s4_li1' => 'Art. 6(1)(e) GDPR in conjunction with school law provisions of the State of Lower Saxony',
            'privacy.s4_li2' => 'Art. 6(1)(c) GDPR (legal obligation)',
            'privacy.s4_li3' => 'Art. 6(1)(a) GDPR (consent), insofar as voluntary information/uploads are provided',
            'privacy.s5_title' => '5. Categories of personal data',
            'privacy.s5_li1' => 'Master data (name, date of birth, nationality, address, contact data)',
            'privacy.s5_li2' => 'School information (current school, language level, interests)',
            'privacy.s5_li3' => 'Optional documents (e.g. latest report card)',
            'privacy.s5_li4' => 'Additional contacts (parents/caregivers/institutions)',
            'privacy.s6_title' => '6. Recipients',
            'privacy.s6_body' => 'Within the City of Oldenburg and the vocational schools. Data is only shared with third parties where legally required (e.g. school authorities) or with your consent.',
            'privacy.s7_title' => '7. Transfers to third countries',
            'privacy.s7_body' => 'No transfers to third countries take place.',
            'privacy.s8_title' => '8. Storage period',
            'privacy.s8_body' => 'Your data is stored for the duration of the application/admission process and according to statutory retention periods and then deleted.',
            'privacy.s9_title' => '9. Your rights',
            'privacy.s9_li1' => 'Access (Art. 15 GDPR), rectification (Art. 16), erasure (Art. 17), restriction (Art. 18)',
            'privacy.s9_li2' => 'Objection (Art. 21) to processing in the public interest',
            'privacy.s9_li3' => 'Withdrawal of consent (Art. 7(3)) with effect for the future',
            'privacy.s9_li4' => 'Right to lodge a complaint with the supervisory authority: Data Protection Authority of Lower Saxony',
            'privacy.s10_title' => '10. Hosting & logs',
            'privacy.s10_body' => 'The application is hosted on city servers / municipal data center. Only technically necessary data is processed (e.g. server logs for troubleshooting). No external CDNs. Only a language cookie is set.',
            'privacy.s11_title' => '11. Cookies',
            'privacy.s11_li1_html' => '<strong>lang</strong> – stores the selected language (valid for 12 months). Purpose: usability.',
            'privacy.s11_li2' => 'PHP session – technically required for the form flow; deleted when the session ends.',
            'privacy.stand_label' => 'Version',
            'privacy.stand_hint' => 'Please check regularly whether changes have been made.',
            'privacy.back_home' => 'Home',

            'imprint.title' => 'Legal notice',
            'imprint.h1' => 'Legal notice',
            'imprint.s1_title' => 'Service provider',
            'imprint.s1_body_html' => '<strong>City ***</strong><br>Vocational Schools<br>(enter full address)<br>Phone: (add)<br>Email: (add)',
            'imprint.s2_title' => 'Represented by',
            'imprint.s2_body_html' => '(e.g. Mayor of the City ****<br>or management of the respective BBS)',
            'imprint.s3_title' => 'Responsible for content pursuant to § 18(2) MStV',
            'imprint.s3_body_html' => '(name, role, contact, e.g. school management or press office)',
            'imprint.s4_title' => 'VAT ID',
            'imprint.s4_body_html' => '(if available; otherwise this section can be omitted)',
            'imprint.s5_title' => 'Supervisory authority',
            'imprint.s5_body' => '(competent municipal supervision / school authority, e.g. regional division of the state school authority)',
            'imprint.s6_title' => 'Liability for content',
            'imprint.s6_body' => 'We have prepared the contents with due care. However, we cannot guarantee accuracy, completeness, or timeliness. As a public body we are responsible for our own content under applicable laws.',
            'imprint.s7_title' => 'Liability for links',
            'imprint.s7_body' => 'Our service does not include external content transferring personal data to third parties. Where we link to information from other public bodies, we assume no responsibility for their content.',
            'imprint.s8_title' => 'Copyright',
            'imprint.s8_body' => 'Content created by the City of Oldenburg is subject to German copyright law. Third-party contributions are marked as such. Any use beyond the limits of copyright requires written permission of the City of Oldenburg or the respective rights holder.',
            'imprint.stand_label' => 'Version',
            'imprint.stand_hint' => 'These details apply to the online form “BES Language and Integration”.',
            'imprint.back_home' => 'Home',

            'verify_email.title' => 'Verify email',
            'verify_email.h1' => 'Verify email',
            'verify_email.lead_sent' => 'We sent a verification code to {email}.',
            'verify_email.lead_generic' => 'Please enter the verification code you received by email. If you do not see an email, you can request the code again.',
            'verify_email.code_label' => 'Verification code (6 digits)',
            'verify_email.email_label' => 'Your email address',
            'verify_email.btn_verify' => 'Verify',
            'verify_email.btn_resend' => 'Resend code',
            'verify_email.hint_spam' => 'Please also check your spam folder.',
            'verify_email.back' => 'Back',
            'verify_email.csrf_invalid' => 'Invalid request.',
            'verify_email.ok_verified' => 'Email verified successfully.',
            'verify_email.ok_sent' => 'A new code was sent to {email}.',
            'verify_email.warn_cooldown' => 'Please wait a moment before requesting the code again.',
            'verify_email.error_send' => 'Sending failed. Please try again later.',
            'verify_email.error_email' => 'Please enter a valid email address.',
            'verify_email.error_no_session' => 'No active verification process found. Please request a new code.',
            'verify_email.error_expired' => 'Code invalid or expired.',
            'verify_email.error_invalid' => 'Code invalid or expired.',
            'verify_email.error_code_format' => 'Please enter a valid 6-digit code.',
            'verify_email.error_rate' => 'Too many attempts. Please request a new code.',

            // Validation / Errors
            'val.required' => 'Required.',
            'val.only_letters' => 'Letters only, please.',
            'val.gender_choose' => 'Please choose a gender.',
            'val.date_format' => 'DD.MM.YYYY',
            'val.date_invalid' => 'Invalid date.',
            'val.plz_whitelist' => 'Only postal codes from Oldenburg (26121–26135).',
            'val.phone_vorwahl' => 'Area code: 2–6 digits.',
            'val.phone_nummer' => 'Number: 3–12 digits.',
            'val.email_invalid' => 'Invalid email.',
            'val.email_no_iserv' => 'Please use a private email (no IServ).',
            'val.max_1500' => 'Max 1500 characters.',
            'val.kontakt_row_name_missing' => 'Name/description missing',
            'val.kontakt_row_tel_or_mail'  => 'Provide phone OR email',
            'val.kontakt_row_mail_invalid' => 'Invalid email',
            'val.kontakt_row_tel_invalid'  => 'Invalid phone',
            'val.school_free_required' => 'Please enter the school name (free text).',
            'val.school_invalid'       => 'Please select a valid school or “Other / not listed”.',
            'val.since_required'       => 'Please enter month+year or free text.',
            'val.month_invalid'        => 'Month must be 01–12.',
            'val.year_invalid'         => 'Please enter a valid year.',
            'val.number_required'      => 'Please enter a number.',
            'val.choose'               => 'Please choose.',
            'val.herkunft_years'       => 'Please enter number of years.',
            'val.level_invalid'        => 'Invalid selection.',
            'val.interests_min1'       => 'Please choose at least 1 area.',
            'val.interests_max2'       => 'Please choose at most 2 areas.',
            'js.hint_years_gt3'  => 'Note: You have been in Germany for more than 3 years. Please apply via {link}.',
            'js.hint_level_b1p'  => 'Note: With German level B1 or higher, please use the regular BBS application via {link}.',
        ],
        
        // =======================
        // FR: in 'fr' => [ ... ] einfügen (komplett)
        // =======================
        'fr' => [
        
            // =======================
            // STEP Start: Index (FR)
            // =======================
            'index.title' => 'Bienvenue – Inscription en ligne aux cours de langue',
            'index.lead'  => 'Ce service s’adresse aux personnes nouvellement arrivées à Oldenburg. Le formulaire nous aide à vous contacter et à trouver une offre adaptée.',
            'index.bullets' => [
                'Veuillez préparer vos coordonnées et une pièce d’identité (si disponible).',
                'Le formulaire peut être rempli dans plusieurs langues.',
                'Vos données sont traitées de manière confidentielle conformément au RGPD.',
            ],
            'index.info_p' => [
                'Chère élève, cher élève,',
                'Par la présente, vous posez votre candidature pour une place dans la classe d’apprentissage de la langue « BES Langue et Intégration » d’un établissement d’enseignement professionnel (BBS) à Oldenburg. Vous ne candidatez pas pour un BBS en particulier. Après le 20 février, vous serez informé·e de l’établissement qui vous accueillera dans la classe.',
                'Vous ne pouvez être admis·e que si les conditions suivantes sont remplies :',
            ],
            'index.info_bullets' => [
                'Vous avez besoin d’un soutien intensif en allemand (niveau inférieur à B1).',
                'Au début de la prochaine année scolaire, vous êtes en Allemagne depuis 3 ans au maximum.',
                'Au 30 septembre de cette année, vous avez au moins 16 ans et au plus 18 ans.',
                'Vous êtes soumis·e à l’obligation scolaire pour la prochaine année scolaire.',
            ],
            'index.access_title' => 'Protection des données & Accès',
            'index.access_intro' => 'Vous pouvez continuer avec ou sans adresse e-mail. L’accès aux candidatures enregistrées n’est possible qu’avec votre code d’accès personnel (token) et votre date de naissance.',
            'index.access_points' => [
                '<strong>Avec e-mail :</strong> vous recevez un code de confirmation et pouvez créer plusieurs candidatures et les retrouver plus tard.',
                '<strong>Sans e-mail :</strong> vous recevez un code d’accès personnel (Access-Token). Veuillez le noter/le photographier — sans e-mail vérifié, aucune récupération n’est possible.',
            ],
        
            'index.btn_noemail' => 'Continuer sans e-mail',
            'index.btn_create'  => 'Continuer avec e-mail',
            'index.btn_load'    => 'Accéder à la/aux candidature(s)',
            'index.lang_label'  => 'Langue :',
        
            // =======================
            // STEP 1/4: PERSONAL (FR)
            // =======================
            'personal.page_title' => 'Étape 1/4 – Données personnelles',
            'personal.h1' => 'Étape 1/4 – Données personnelles',
            'personal.required_hint' => 'Les champs obligatoires sont mis en évidence par une bordure bleue.',
            'personal.form_error_hint' => 'Veuillez vérifier les champs en surbrillance.',
        
            'personal.alert_email_title' => 'Connexion e-mail active :',
            'personal.alert_email_line1' => 'Connecté·e avec l’adresse e-mail {email}.',
            'personal.alert_email_line2' => 'Cet e-mail est utilisé uniquement pour le code d’accès (Access-Token) et pour retrouver votre candidature.',
            'personal.alert_email_line3' => 'Vous pouvez indiquer ci-dessous l’adresse e-mail de l’élève (si disponible).',
        
            'personal.alert_noemail_title' => 'Remarque (sans e-mail) :',
            'personal.alert_noemail_body' => 'Veuillez noter/photographier votre code d’accès (Access-Token) affiché après l’enregistrement de cette page. Sans e-mail vérifié, la récupération n’est possible qu’avec le token + la date de naissance.',
        
            'personal.label.name' => 'Nom',
            'personal.label.vorname' => 'Prénom',
            'personal.label.geschlecht' => 'Sexe',
            'personal.gender.m' => 'masculin',
            'personal.gender.w' => 'féminin',
            'personal.gender.d' => 'divers',
        
            'personal.label.geburtsdatum' => 'Né(e) le',
            'personal.label.geburtsdatum_hint' => '(JJ.MM.AAAA)',
            'personal.placeholder.geburtsdatum' => 'JJ.MM.AAAA',
        
            'personal.age_hint' => 'Remarque : si vous avez moins de 16 ans ou plus de 18 ans au 30.09.{year}, vous ne pouvez pas être admis·e dans la classe de langue du BBS. Veuillez postuler à une autre classe ici :',
            'personal.age_redirect_msg' => "Remarque : si vous avez moins de 16 ans ou plus de 18 ans au 30.09.{year}, vous ne pouvez pas être admis·e dans la classe de langue du BBS. Veuillez postuler à une autre classe d’un BBS ici :\n{url}",
        
            'personal.label.geburtsort_land' => 'Lieu / pays de naissance',
            'personal.label.staatsang' => 'Nationalité',
        
            'personal.label.strasse' => 'Rue, n°',
            'personal.label.plz' => 'Code postal',
            'personal.plz_choose' => '– veuillez choisir –',
            'personal.plz_hint' => 'Oldenburg uniquement.',
            'personal.label.wohnort' => 'Ville',
        
            'personal.label.telefon' => 'Numéro de téléphone',
            'personal.label.telefon_vorwahl_help' => 'Indicatif avec/sans 0',
            'personal.label.telefon_nummer_help' => 'Numéro',
            'personal.placeholder.telefon_vorwahl' => '(0)441',
            'personal.placeholder.telefon_nummer' => '123456',
        
            'personal.label.email' => 'Adresse e-mail de l’élève (optionnel, pas d’adresse IServ)',
            'personal.email_help' => 'Cet e-mail appartient à l’élève (si disponible) et est indépendant de l’adresse e-mail utilisée pour le code d’accès.',
            'personal.placeholder.email' => 'nom@example.org',
        
            'personal.label.kontakte' => 'Autres coordonnées',
            'personal.kontakte_hint' => '(p. ex. parents, tuteur·rice, structure)',
            'personal.kontakte_error' => 'Veuillez vérifier les contacts supplémentaires.',
            'personal.kontakte_add' => '+ Ajouter un contact',
            'personal.kontakte_remove_title' => 'Supprimer le contact',
        
            'personal.table.role' => 'Rôle',
            'personal.table.name' => 'Nom / structure',
            'personal.table.tel'  => 'Téléphone',
            'personal.table.mail' => 'E-mail',
            'personal.table.note_header' => 'Note',
            'personal.placeholder.kontakt_name' => 'Nom ou désignation',
            'personal.placeholder.kontakt_tel'  => '+49 …',
            'personal.placeholder.kontakt_note' => 'p. ex. disponibilité, langue, remarques',
        
            'personal.contact_role.none' => '–',
            'personal.contact_role.mutter' => 'Mère',
            'personal.contact_role.vater' => 'Père',
            'personal.contact_role.elternteil' => 'Parent',
            'personal.contact_role.betreuer' => 'Tuteur·rice',
            'personal.contact_role.einrichtung' => 'Structure',
            'personal.contact_role.sonstiges' => 'Autre',
        
            'personal.label.weitere_angaben' => 'Autres informations (p. ex. statut d’aide/mesures de soutien) :',
            'personal.placeholder.weitere_angaben' => 'Vous pouvez indiquer ici, par exemple, des besoins de soutien particuliers, des besoins éducatifs spécifiques ou d’autres remarques.',
            'personal.weitere_angaben_help' => 'Optionnel. Maximum 1500 caractères.',
            'personal.btn.cancel' => 'Annuler',
            'personal.btn.next' => 'Suivant',
        
            'personal.dsgvo_text_prefix' => "J’ai lu les",
            'personal.dsgvo_link_text' => 'informations de protection des données',
            'personal.dsgvo_text_suffix' => 'et je suis d’accord.',
        
            // =====================
            // STEP 2/4: SCHOOL (FR)
            // =====================
            'school.page_title' => 'Étape 2/4 – École & intérêts',
            'school.h1' => 'Étape 2/4 – École & intérêts',
            'school.required_hint' => 'Les champs obligatoires sont mis en évidence par une bordure bleue.',
            'school.form_error_hint' => 'Veuillez vérifier les champs en surbrillance.',
        
            'school.top_hint_title' => 'Remarque :',
            'school.top_hint_body'  => 'Si vous êtes en Allemagne depuis <u>plus de 3 ans</u> ou si vous parlez déjà allemand au niveau <u>B1</u> ou plus, vous ne pouvez pas être admis·e dans la classe de langue du BBS. Veuillez postuler à une autre classe d’un BBS ici :',
            'school.bbs_link_label' => 'https://bbs-ol.de/',
        
            'school.autohints_title' => 'Informations',
        
            'school.label.schule_aktuell' => 'École actuelle',
            'school.search_placeholder'   => 'Rechercher une école… (nom, rue, CP)',
            'school.select_choose'        => 'Veuillez choisir…',
            'school.option_other'         => 'Autre / non répertoriée',
            'school.other_placeholder'    => 'Nom de l’école, rue, ville (texte libre)',
        
            'school.label.teacher'        => 'Enseignant·e responsable',
            'school.label.teacher_mail'   => "E-mail de l’enseignant·e responsable",
        
            'school.label.herkunft'       => 'Avez-vous fréquenté l’école dans votre pays d’origine ?',
            'school.yes'                  => 'Oui',
            'school.no'                   => 'Non',
            'school.label.herkunft_years' => 'Si oui : combien d’années ?',
        
            'school.label.since'          => 'Depuis quand dans une école en Allemagne ?',
            'school.since_month'          => 'Mois (MM)',
            'school.since_year_ph'        => 'Année (AAAA)',
            'school.since_help'           => 'Indiquez soit mois+année <strong>ou</strong> utilisez le champ de texte libre.',
            'school.label.since_text'     => 'Alternative : texte libre (p. ex. « depuis l’automne 2023 »)',
        
            'school.label.years_in_de'    => 'Depuis combien d’années êtes-vous en Allemagne ?',
            'school.years_in_de_help'     => 'Remarque : &gt; 3 ans → candidature BBS régulière via {link}.',
        
            'school.label.family_lang'    => 'Langue familiale / langue maternelle',
        
            'school.label.level'          => 'Quel niveau d’allemand ?',
            'school.level_choose'         => 'Veuillez choisir…',
            'school.level_help'           => 'Remarque : B1 ou plus → candidature BBS régulière via {link}.',
        
            'school.label.interests'      => 'Intérêts (min. 1, max. 2)',
        
            'school.btn.back'             => 'Retour',
            'school.btn.next'             => 'Suivant',
        
            // ---------------------
            // Validierung / Errors
            // ---------------------
            'val.school_free_required' => 'Veuillez indiquer le nom de l’école (texte libre).',
            'val.school_invalid'       => 'Veuillez choisir une école valide ou « Autre / non répertoriée ».',
        
            'val.since_required'       => 'Veuillez indiquer mois+année ou un texte libre.',
            'val.month_invalid'        => 'Le mois doit être entre 01 et 12.',
            'val.year_invalid'         => 'Veuillez indiquer une année valide.',
            'val.number_required'      => 'Veuillez indiquer un nombre.',
            'val.choose'               => 'Veuillez sélectionner.',
            'val.herkunft_years'       => 'Veuillez indiquer le nombre d’années.',
        
            'val.level_invalid'        => 'Sélection invalide.',
        
            'val.interests_min1'       => 'Veuillez sélectionner au moins 1 domaine.',
            'val.interests_max2'       => 'Veuillez sélectionner au maximum 2 domaines.',
        
            // ---------------------
            // JS Live-Hinweise
            // ---------------------
            'js.hint_years_gt3'  => 'Remarque : vous êtes en Allemagne depuis plus de 3 ans. Veuillez postuler via {link}.',
            'js.hint_level_b1p'  => 'Remarque : avec un niveau d’allemand B1 ou plus, veuillez postuler via {link}.',
        
            // =========================
            // STEP 3/4: UPLOAD (FR)
            // =========================
            'upload.page_title' => 'Étape 3/4 – Documents (optionnel)',
            'upload.h1'         => 'Étape 3/4 – Documents (optionnel)',
        
            'upload.intro'      => 'Vous pouvez téléverser des documents ici. Formats autorisés : <strong>PDF</strong>, <strong>JPG</strong> et <strong>PNG</strong>. La taille maximale est de <strong>{max_mb} Mo</strong> par fichier.',
        
            'upload.type.zeugnis'    => 'Dernier bulletin semestriel',
            'upload.type.lebenslauf' => 'CV',
            'upload.type_hint'       => '(PDF/JPG/PNG, max. {max_mb} Mo)',
        
            'upload.btn.remove' => 'Supprimer',
            'upload.btn.back'   => 'Retour',
            'upload.btn.next'   => 'Suivant',
        
            'upload.saved_prefix' => 'Déjà enregistré :',
            'upload.empty'        => 'Aucun fichier téléversé pour le moment.',
            'upload.saved_html'   => 'Déjà enregistré : <strong>{filename}</strong>, {size_kb} Ko, téléversé le {uploaded_at}',
        
            'upload.checkbox.zeugnis_spaeter' => 'Je fournirai le bulletin semestriel après l’acceptation.',
        
            'upload.flash.no_access' => 'Aucun accès valide trouvé. Veuillez recommencer l’inscription.',
            'upload.flash.saved'     => 'Informations de téléversement enregistrées.',
        
            'upload.js.uploading'          => 'Téléversement en cours…',
            'upload.js.unexpected'         => 'Réponse inattendue du serveur.',
            'upload.js.upload_failed'      => 'Téléversement échoué.',
            'upload.js.delete_confirm'     => 'Supprimer le fichier téléversé ?',
            'upload.js.delete_failed'      => 'Suppression échouée.',
            'upload.js.remove_confirm_btn' => 'Supprimer le fichier ?',
        
            // AJAX / Fehlertexte
            'upload.ajax.invalid_method'     => 'Méthode invalide',
            'upload.ajax.invalid_csrf'       => 'Jeton CSRF invalide',
            'upload.ajax.no_access'          => 'Aucun accès valide.',
            'upload.ajax.invalid_field'      => 'Champ invalide',
            'upload.ajax.no_file_sent'       => 'Aucun fichier envoyé',
            'upload.ajax.no_file_selected'   => 'Aucun fichier sélectionné',
            'upload.ajax.upload_error'       => 'Erreur de téléversement (code {code})',
            'upload.ajax.too_large'          => 'Fichier supérieur à {max_mb} Mo',
            'upload.ajax.mime_only'          => 'Seuls PDF, JPG ou PNG sont autorisés',
            'upload.ajax.ext_only'           => 'Extension invalide (uniquement pdf/jpg/jpeg/png)',
            'upload.ajax.cannot_save'        => 'Impossible d’enregistrer le fichier',
            'upload.ajax.unknown_action'     => 'Action inconnue',
            'upload.ajax.server_error'       => 'Erreur serveur lors du téléversement',
        
            // =========================
            // STEP 4/4: REVIEW (FR)
            // =========================
            'review.page_title' => 'Étape 4/4 – Récapitulatif & candidature',
        
            'review.h1'      => 'Étape 4/4 – Récapitulatif & candidature',
            'review.subhead' => 'Veuillez vérifier vos informations. En cliquant sur « Postuler », vous envoyez les données.',
        
            'review.readonly_alert' => 'Cette candidature a déjà été envoyée. Les informations peuvent uniquement être consultées, mais ne peuvent plus être modifiées ni renvoyées.',
        
            'review.info.p1' => 'Chère élève, cher élève,',
            'review.info.p2' => 'en cliquant sur <strong>« Postuler »</strong>, vous avez posé votre candidature pour <strong>BES Langue et Intégration</strong> dans un BBS d’Oldenburg.',
            'review.info.p3' => 'Il ne s’agit pas encore d’une inscription définitive, mais d’une <strong>candidature</strong>. Après le <strong>20.02.</strong>, vous recevrez l’information indiquant si / dans quel BBS vous êtes accepté·e. Veuillez vérifier régulièrement votre boîte aux lettres et votre messagerie électronique. Assurez-vous que votre nom est visible sur la boîte aux lettres afin de recevoir le courrier.',
            'review.info.p4' => 'Avec l’acceptation de l’école, il vous sera demandé de fournir ces documents (si vous ne les avez pas encore téléversés aujourd’hui) :',
            'review.info.li1' => 'dernier bulletin semestriel',
        
            // Accordion Überschriften
            'review.acc.personal' => 'Données personnelles',
            'review.acc.school'   => 'École & intérêts',
            'review.acc.uploads'  => 'Documents',
        
            // Labels: Personal
            'review.lbl.name'            => 'Nom',
            'review.lbl.vorname'         => 'Prénom',
            'review.lbl.geschlecht'      => 'Sexe',
            'review.lbl.geburtsdatum'    => 'Né(e) le',
            'review.lbl.geburtsort'      => 'Lieu / pays de naissance',
            'review.lbl.staatsang'       => 'Nationalité',
            'review.lbl.strasse'         => 'Rue, n°',
            'review.lbl.plz_ort'         => 'Code postal / Ville',
            'review.lbl.telefon'         => 'Téléphone',
            'review.lbl.email'           => 'E-mail (élève, optionnel)',
            'review.lbl.weitere_angaben' => 'Autres informations (p. ex. mesures de soutien)',
        
            'review.contacts.title'    => 'Autres contacts',
            'review.contacts.optional' => 'optionnel',
            'review.contacts.none'     => '–',
        
            'review.contacts.th.role' => 'Rôle',
            'review.contacts.th.name' => 'Nom / structure',
            'review.contacts.th.tel'  => 'Téléphone',
            'review.contacts.th.mail' => 'E-mail',
            'review.contacts.note'    => 'Note :',
        
            // Labels: School
            'review.lbl.school_current' => 'École actuelle',
            'review.lbl.klassenlehrer'  => 'Enseignant·e responsable',
            'review.lbl.mail_lehrkraft' => 'E-mail enseignant·e',
            'review.lbl.since'          => 'Depuis quand à l’école',
            'review.lbl.years_de'       => 'Années en Allemagne',
            'review.lbl.family_lang'    => 'Langue familiale / langue maternelle',
            'review.lbl.de_level'       => 'Niveau d’allemand',
            'review.lbl.school_origin'  => 'École dans le pays d’origine',
            'review.lbl.years_origin'   => 'Années d’école dans le pays d’origine',
            'review.lbl.interests'      => 'Intérêts',
        
            // Uploads
            'review.lbl.zeugnis'        => 'Bulletin semestriel',
            'review.lbl.lebenslauf'     => 'CV',
            'review.lbl.later'          => 'À fournir plus tard',
            'review.badge.uploaded'     => 'téléversé',
            'review.badge.not_uploaded' => 'non téléversé',
            'review.yes'                => 'Oui',
            'review.no'                 => 'Non',
        
            // Buttons / Actions
            'review.btn.home'   => "Retour à l’accueil",
            'review.btn.newapp' => 'Soumettre une autre candidature',
            'review.btn.back'   => 'Retour',
            'review.btn.submit' => 'Postuler',
        
            // Errors / Flash / Systemtexte
            'review.err.invalid_request'        => 'Requête invalide.',
            'review.flash.already_submitted'    => 'Cette candidature a déjà été envoyée et ne peut pas être renvoyée ni modifiée.',
            'review.flash.no_token'             => 'Aucun code d’accès valide. Veuillez recommencer la procédure.',
            'review.err.not_found_token'        => 'Aucune candidature trouvée pour ce token.',
            'review.flash.submit_error'         => 'Une erreur est survenue lors de l’envoi. Veuillez réessayer plus tard.',
        
            // Gender fallback
            'review.gender.m' => 'masculin',
            'review.gender.w' => 'féminin',
            'review.gender.d' => 'divers',
        
            // Fallback Anzeige
            'review.value.empty' => '–',
        
            // =========================
            // STATUS (FR)
            // =========================
            'status.hdr_title'   => 'Candidature enregistrée avec succès',
            'status.hdr_message' => 'Votre candidature a été transmise.',
        
            'status.h1' => 'Votre candidature a été enregistrée avec succès.',
        
            'status.success.title' => 'Merci !',
            'status.success.body'  => 'Votre candidature a été transmise et va maintenant être traitée.',
        
            'status.info.title' => 'Information importante',
            'status.info.body'  => '<em>[PLACEHOLDER : texte fourni par le client]</em>',
        
            'status.btn.pdf'    => 'Télécharger / imprimer le PDF',
            'status.btn.newapp' => 'Démarrer une autre candidature',
            'status.btn.home'   => "Retour à l’accueil",
        
            'status.ref' => 'Référence : candidature #{id}',
        
            'status.err.invalid_request' => 'Requête invalide.',
        
            // =========================
            // PDF (FR)
            // =========================
            'pdf.err.autoload_missing' => 'Autoload Composer introuvable. Veuillez exécuter "composer install".',
            'pdf.err.no_token'         => 'Aucun code d’accès valide. Veuillez recommencer la procédure.',
            'pdf.err.not_found'        => 'Candidature introuvable.',
            'pdf.err.server'           => 'Erreur serveur lors de la création du PDF.',
        
            'pdf.header_title' => 'Candidature – Récapitulatif',
            'pdf.footer_auto'  => 'Document généré automatiquement',
            'pdf.footer_page'  => 'Page {cur} / {max}',
        
            'pdf.meta.ref'        => 'Candidature #{id}',
            'pdf.meta.created_at' => 'Créé le',
            'pdf.meta.status'     => 'Statut',
        
            'pdf.top.title'        => 'Aperçu',
            'pdf.top.name'         => 'Nom',
            'pdf.top.reference'    => 'Référence',
            'pdf.top.generated'    => 'Créé le',
            'pdf.top.hint'         => 'Remarque',
            'pdf.top.keep_note'    => 'Veuillez conserver ce document pour vos dossiers.',
            'pdf.hint_placeholder' => '[PLACEHOLDER : texte fourni par le client]',
        
            'pdf.sec1.title' => '1) Données personnelles',
            'pdf.sec2.title' => '2) Autres coordonnées',
            'pdf.sec3.title' => '3) École & intérêts',
            'pdf.sec4.title' => '4) Documents',
        
            'pdf.lbl.name'           => 'Nom',
            'pdf.lbl.vorname'        => 'Prénom',
            'pdf.lbl.gender'         => 'Sexe',
            'pdf.lbl.dob'            => 'Date de naissance',
            'pdf.lbl.birthplace'     => 'Lieu/pays de naissance',
            'pdf.lbl.nationality'    => 'Nationalité',
            'pdf.lbl.address'        => 'Adresse',
            'pdf.lbl.phone'          => 'Téléphone',
            'pdf.lbl.email_optional' => 'E-mail (optionnel)',
            'pdf.lbl.more'           => 'Autres informations',
        
            'pdf.lbl.school_current' => 'École actuelle',
            'pdf.lbl.teacher'        => 'Enseignant·e',
            'pdf.lbl.teacher_email'  => 'E-mail enseignant·e',
            'pdf.lbl.since_school'   => 'Depuis quand à l’école',
            'pdf.lbl.years_in_de'    => 'Depuis quand en Allemagne',
            'pdf.lbl.family_lang'    => 'Langue familiale',
            'pdf.lbl.de_level'       => 'Niveau d’allemand',
            'pdf.lbl.school_origin'  => 'École dans le pays d’origine',
            'pdf.lbl.years_origin'   => 'Années d’école dans le pays d’origine',
            'pdf.lbl.interests'      => 'Intérêts',
        
            'pdf.lbl.report'       => 'Bulletin semestriel',
            'pdf.lbl.cv'           => 'CV',
            'pdf.lbl.report_later' => 'Bulletin à fournir plus tard',
        
            'pdf.uploaded'     => 'téléversé',
            'pdf.not_uploaded' => 'non téléversé',
        
            'pdf.contacts.none'   => '–',
            'pdf.contacts.th.role'=> 'Rôle',
            'pdf.contacts.th.name'=> 'Nom/structure',
            'pdf.contacts.th.tel' => 'Téléphone',
            'pdf.contacts.th.mail'=> 'E-mail',
            'pdf.contacts.th.note'=> 'Note',
        
            'pdf.gender.m' => 'masculin',
            'pdf.gender.w' => 'féminin',
            'pdf.gender.d' => 'divers',
        
            'pdf.yes' => 'Oui',
            'pdf.no'  => 'Non',
        
            'pdf.sec4.note' => 'Ce document est un récapitulatif généré automatiquement des données saisies.',
        
            'pdf.filename_prefix' => 'Candidature',
        
            // =========================
            // ACCESS_CREATE (FR)
            // =========================
            'access_create.title'         => 'Continuer avec e-mail',
            'access_create.lead'          => 'Vous pouvez vous connecter avec votre accès ou créer un nouvel accès.',
            'access_create.tabs_login'    => 'Se connecter',
            'access_create.tabs_register' => 'Créer un nouvel accès',
        
            'access_create.login_title' => 'Se connecter (accès existant)',
            'access_create.login_text'  => 'Veuillez saisir votre adresse e-mail et votre mot de passe.',
            'access_create.email_label' => 'Adresse e-mail',
            'access_create.pass_label'  => 'Mot de passe',
            'access_create.login_btn'   => 'Se connecter',
            'access_create.login_err'   => 'E-mail/mot de passe incorrect ou accès non vérifié.',
        
            'access_create.reg_title'     => 'Créer un nouvel accès',
            'access_create.reg_text'      => 'Nous vous envoyons un code de confirmation à 6 chiffres. Après confirmation, vous recevrez votre mot de passe par e-mail.',
            'access_create.consent_label' => 'J’accepte que mon e-mail soit utilisé pour le processus d’inscription.',
            'access_create.send_btn'      => 'Envoyer le code',
            'access_create.code_label'    => 'Code de confirmation',
            'access_create.verify_btn'    => 'Vérifier le code',
            'access_create.resend'        => 'Renvoyer le code',
        
            'access_create.info_sent'    => 'Nous vous avons envoyé un code. Veuillez aussi vérifier le dossier spam.',
            'access_create.ok_verified'  => 'E-mail confirmé. Le mot de passe a été envoyé. Vous pouvez maintenant vous connecter.',
            'access_create.email_in_use' => 'Cette adresse e-mail possède déjà un accès. Veuillez vous connecter.',
        
            'access_create.error_email'     => 'Veuillez saisir une adresse e-mail valide.',
            'access_create.error_consent'   => 'Veuillez accepter l’utilisation de votre e-mail.',
            'access_create.error_rate'      => 'Trop de tentatives. Veuillez patienter un moment puis réessayer.',
            'access_create.error_code'      => 'Le code est invalide ou expiré.',
            'access_create.error_resend'    => 'Renvoi impossible. Veuillez recommencer.',
            'access_create.error_mail_send' => 'Échec de l’envoi de l’e-mail. Veuillez réessayer plus tard.',
            'access_create.error_db'        => 'Erreur serveur (BD).',
        
            'access_create.back'   => 'Retour',
            'access_create.cancel' => 'Annuler',
        
            'access_create.mail_subject' => 'Votre mot de passe pour l’inscription en ligne',
            'access_create.mail_body'    => "Votre accès a été créé.\n\nE-mail : {email}\nMot de passe : {password}\n\nVeuillez conserver ce mot de passe en lieu sûr.",
        
            // =========================
            // ACCESS_PORTAL (FR)
            // =========================
            'access_portal.title'    => 'Mes candidatures',
            'access_portal.lead'     => 'Vous voyez ici vos candidatures. Vous pouvez reprendre une candidature existante ou en démarrer une nouvelle.',
            'access_portal.max_hint' => '{email} · max. {max} candidatures',
        
            'access_portal.btn_new'    => 'Démarrer une nouvelle candidature',
            'access_portal.btn_open'   => 'Ouvrir',
            'access_portal.btn_logout' => 'Se déconnecter',
        
            'access_portal.th_ref'     => 'ID',
            'access_portal.th_status'  => 'Statut',
            'access_portal.th_created' => 'Créée',
            'access_portal.th_updated' => 'Mise à jour',
            'access_portal.th_token'   => 'Token',
            'access_portal.th_action'  => 'Action',
        
            'access_portal.status_draft'     => 'Brouillon',
            'access_portal.status_submitted' => 'Envoyée',
            'access_portal.status_withdrawn' => 'Retirée',
        
            'access_portal.limit_reached' => 'Vous avez atteint le nombre maximal de candidatures pour cet e-mail.',
            'access_portal.no_apps'       => 'Aucune candidature pour le moment.',
            'access_portal.err_generic'   => 'Une erreur est survenue.',
            'access_portal.csrf_invalid'  => 'Requête invalide.',
        
            // =========================
            // ACCESS_LOGIN (FR)
            // =========================
            'access_login.title'            => 'Accéder à la/aux candidature(s)',
            'access_login.lead'             => 'Vous pouvez ici rouvrir une candidature déjà commencée ou envoyée.',
        
            'access_login.login_box_title'  => 'Connexion avec Access-Token',
            'access_login.login_box_text'   => 'Veuillez saisir votre code d’accès personnel (Access-Token) et votre date de naissance.',
        
            'access_login.token_label'      => 'Access-Token',
            'access_login.dob_label'        => 'Date de naissance (JJ.MM.AAAA)',
        
            'access_login.login_btn'        => 'Accéder',
            'access_login.back'             => "Retour à l’accueil",
        
            'access_login.login_ok'         => 'La candidature a été chargée.',
            'access_login.login_error'      => 'La combinaison Access-Token et date de naissance est introuvable.',
            'access_login.login_error_token'=> 'Veuillez saisir un Access-Token valide.',
            'access_login.login_error_dob'  => 'Veuillez saisir une date de naissance valide au format JJ.MM.AAAA.',
        
            'access_login.csrf_invalid'     => 'Requête invalide.',
            'access_login.internal_error'   => 'Erreur interne.',
            'access_login.load_error'       => 'Une erreur est survenue lors du chargement de la candidature.',
        
            // =========================
            // PRIVACY (FR)
            // =========================
            'privacy.title' => 'Protection des données',
            'privacy.h1'    => 'Informations de protection des données pour la candidature en ligne « BES Langue et Intégration »',
        
            'privacy.s1_title'    => '1. Responsable du traitement',
            'privacy.s1_body_html'=> '<strong>Ville d’Oldenburg / Écoles professionnelles</strong><br>(Veuillez indiquer le service/l’établissement exact, l’adresse, le téléphone et l’e-mail)',
        
            'privacy.s2_title'    => '2. Délégué·e à la protection des données',
            'privacy.s2_body_html'=> '(Indiquer les coordonnées du/de la délégué·e à la protection des données)',
        
            'privacy.s3_title' => '3. Finalités du traitement',
            'privacy.s3_li1'   => 'Réception et traitement de votre candidature pour l’admission dans la classe de langue (« BES Langue et Intégration »)',
            'privacy.s3_li2'   => 'Communication avec vous (questions, informations sur la décision d’admission)',
            'privacy.s3_li3'   => 'Planification de l’organisation scolaire (affectation à un BBS)',
        
            'privacy.s4_title' => '4. Bases juridiques',
            'privacy.s4_li1'   => 'Art. 6, par. 1, point e) RGPD en lien avec la réglementation scolaire du Land de Basse-Saxe',
            'privacy.s4_li2'   => 'Art. 6, par. 1, point c) RGPD (respect d’une obligation légale)',
            'privacy.s4_li3'   => 'Art. 6, par. 1, point a) RGPD (consentement), dans la mesure où des informations/téléversements facultatifs sont fournis',
        
            'privacy.s5_title' => '5. Catégories de données à caractère personnel',
            'privacy.s5_li1'   => 'Données d’identification (nom, prénom, date de naissance, nationalité, adresse, coordonnées)',
            'privacy.s5_li2'   => 'Informations scolaires (école actuelle, niveau de langue, intérêts)',
            'privacy.s5_li3'   => 'Documents facultatifs (p. ex. dernier bulletin semestriel)',
            'privacy.s5_li4'   => 'Contacts supplémentaires (parents/tuteur·rice/structures)',
        
            'privacy.s6_title' => '6. Destinataires',
            'privacy.s6_body'  => 'Au sein de la Ville d’Oldenburg et des écoles professionnelles compétentes. Une transmission à des tiers n’a lieu que si la loi l’exige (p. ex. autorités scolaires) ou avec votre consentement.',
        
            'privacy.s7_title' => '7. Transfert vers des pays tiers',
            'privacy.s7_body'  => 'Aucun transfert vers des pays tiers n’a lieu.',
        
            'privacy.s8_title' => '8. Durée de conservation',
            'privacy.s8_body'  => 'Vos données sont conservées pendant la durée de la procédure de candidature/admission et conformément aux délais légaux de conservation, puis supprimées.',
        
            'privacy.s9_title' => '9. Vos droits',
            'privacy.s9_li1'   => 'Accès (art. 15 RGPD), rectification (art. 16), effacement (art. 17), limitation (art. 18)',
            'privacy.s9_li2'   => 'Opposition (art. 21) aux traitements réalisés dans l’intérêt public',
            'privacy.s9_li3'   => 'Retrait des consentements (art. 7, par. 3) avec effet pour l’avenir',
            'privacy.s9_li4'   => 'Droit de réclamation auprès de l’autorité de contrôle : Délégué·e régional·e à la protection des données de Basse-Saxe',
        
            'privacy.s10_title' => '10. Hébergement & journaux',
            'privacy.s10_body'  => 'L’application est exploitée sur des serveurs de la ville ou dans un centre informatique communal. Seules des données techniquement nécessaires sont traitées (p. ex. journaux serveur pour le diagnostic). Aucun CDN externe n’est utilisé. Seul un cookie lié à la langue est défini.',
        
            'privacy.s11_title'    => '11. Cookies',
            'privacy.s11_li1_html' => '<strong>lang</strong> – enregistre la langue sélectionnée (validité 12 mois). Objectif : convivialité.',
            'privacy.s11_li2'      => 'Session PHP – nécessaire au fonctionnement du formulaire, supprimée à la fin de la session.',
        
            'privacy.stand_label' => 'Version',
            'privacy.stand_hint'  => 'Veuillez vérifier régulièrement si des modifications ont été apportées.',
            'privacy.back_home'   => "Retour à l’accueil",
        
            // =========================
            // IMPRINT (FR)
            // =========================
            'imprint.title' => 'Mentions légales',
            'imprint.h1'    => 'Mentions légales',
        
            'imprint.s1_title'    => 'Fournisseur de services',
            'imprint.s1_body_html' => '<strong>Ville ***</strong><br>Écoles professionnelles<br>(indiquer l’adresse exacte)<br>Téléphone : (à compléter)<br>E-mail : (à compléter)',
        
            'imprint.s2_title'    => 'Représenté par',
            'imprint.s2_body_html'=> '(p. ex. maire de la ville ****<br>ou direction de l’établissement BBS)',
        
            'imprint.s3_title'    => 'Responsable du contenu selon § 18, al. 2 MStV',
            'imprint.s3_body_html'=> '(nom, fonction, contact, p. ex. direction du BBS ou service de presse)',
        
            'imprint.s4_title'    => 'N° de TVA',
            'imprint.s4_body_html'=> '(si disponible ; sinon cette section peut être supprimée)',
        
            'imprint.s5_title' => 'Autorité de surveillance',
            'imprint.s5_body'  => '(autorité communale compétente / autorité scolaire, p. ex. antenne régionale de l’autorité scolaire)',
        
            'imprint.s6_title' => 'Responsabilité du contenu',
            'imprint.s6_body'  => 'Nous avons apporté le plus grand soin à la création du contenu. Toutefois, nous ne pouvons garantir l’exactitude, l’exhaustivité et l’actualité. En tant qu’organisme public, nous sommes responsables de nos propres contenus conformément au § 7, al. 1 TMG.',
        
            'imprint.s7_title' => 'Responsabilité des liens',
            'imprint.s7_body'  => 'Notre offre ne contient pas de contenus externes transférant des données personnelles à des tiers. Dans la mesure où nous renvoyons vers des offres d’information d’autres organismes publics, nous n’assumons aucune responsabilité pour leur contenu.',
        
            'imprint.s8_title' => 'Droit d’auteur',
            'imprint.s8_body'  => 'Les contenus et œuvres créés par la Ville d’Oldenburg sont soumis au droit d’auteur allemand. Les contributions de tiers sont signalées comme telles. Toute reproduction, modification, diffusion ou exploitation en dehors des limites du droit d’auteur nécessite l’accord écrit de la Ville d’Oldenburg ou du titulaire des droits.',
        
            'imprint.stand_label' => 'Version',
            'imprint.stand_hint'  => 'Ces informations s’appliquent au formulaire en ligne « BES Langue et Intégration ».',
            'imprint.back_home'   => "Retour à l’accueil",
        
            // =========================
            // VERIFY_EMAIL (FR)
            // =========================
            'verify_email.title' => 'Vérifier l’e-mail',
            'verify_email.h1'    => 'Vérifier l’e-mail',
        
            'verify_email.lead_sent'    => 'Nous avons envoyé un code de confirmation à {email}.',
            'verify_email.lead_generic' => 'Veuillez saisir le code de confirmation reçu par e-mail. Si vous ne voyez pas d’e-mail, vous pouvez renvoyer le code à votre adresse.',
        
            'verify_email.code_label'  => 'Code de confirmation (6 chiffres)',
            'verify_email.email_label' => 'Votre adresse e-mail',
        
            'verify_email.btn_verify' => 'Confirmer',
            'verify_email.btn_resend' => 'Renvoyer le code',
            'verify_email.hint_spam'  => 'Veuillez aussi vérifier le dossier spam.',
        
            'verify_email.back' => 'Retour',
        
            'verify_email.csrf_invalid' => 'Requête invalide.',
            'verify_email.ok_verified'  => 'E-mail vérifié avec succès.',
            'verify_email.ok_sent'      => 'Un nouveau code a été envoyé à {email}.',
        
            'verify_email.warn_cooldown'    => 'Veuillez patienter un instant avant de redemander le code.',
            'verify_email.error_send'       => 'Échec de l’envoi. Veuillez réessayer plus tard.',
            'verify_email.error_email'      => 'Veuillez saisir une adresse e-mail valide.',
            'verify_email.error_no_session' => 'Aucun processus de vérification actif trouvé. Veuillez demander un nouveau code.',
            'verify_email.error_expired'    => 'Code invalide ou expiré.',
            'verify_email.error_invalid'    => 'Code invalide ou expiré.',
            'verify_email.error_code_format'=> 'Veuillez saisir un code valide à 6 chiffres.',
            'verify_email.error_rate'       => 'Trop de tentatives. Veuillez demander un nouveau code.',
        
            // =========================
            // VALIDATION (FR) – global
            // =========================
            'val.required' => 'Obligatoire.',
            'val.only_letters' => 'Veuillez utiliser uniquement des lettres.',
            'val.gender_choose' => 'Veuillez choisir un sexe.',
            'val.date_format' => 'JJ.MM.AAAA',
            'val.date_invalid' => 'Date invalide.',
            'val.plz_whitelist' => 'Uniquement codes postaux d’Oldenburg (26121–26135).',
            'val.phone_vorwahl' => 'Indicatif : 2–6 chiffres.',
            'val.phone_nummer' => 'Numéro : 3–12 chiffres.',
            'val.email_invalid' => 'E-mail invalide.',
            'val.email_no_iserv' => 'Veuillez utiliser un e-mail privé (pas IServ).',
            'val.max_1500' => 'Maximum 1500 caractères.',
            'val.kontakt_row_name_missing' => 'Nom/désignation manquant',
            'val.kontakt_row_tel_or_mail'  => 'Indiquer téléphone OU e-mail',
            'val.kontakt_row_mail_invalid' => 'E-mail invalide',
            'val.kontakt_row_tel_invalid'  => 'Téléphone invalide',
        ],


        // =======================
        // UK: in 'uk' => [ ... ] einfügen (komplett)
        // =======================
        'uk' => [
        
            // =======================
            // STEP Start: Index (UK)
            // =======================
            'index.title' => 'Ласкаво просимо до онлайн-реєстрації – мовні класи',
            'index.lead'  => 'Ця послуга призначена для людей, які нещодавно прибули до Ольденбурга. Форма допоможе нам зв’язатися з вами та підібрати відповідні пропозиції.',
            'index.bullets' => [
                'Будь ласка, підготуйте контактні дані та документ, що посвідчує особу (за наявності).',
                'Форму можна заповнювати кількома мовами.',
                'Ваші дані обробляються конфіденційно відповідно до GDPR.',
            ],
            'index.info_p' => [
                'Шановна ученице, шановний учню,',
                'Цією заявою ви подаєтеся на місце у мовному класі «BES Мова та інтеграція» у професійній школі (BBS) в Ольденбурзі. Ви подаєтеся не до конкретної BBS. Після 20 лютого вам повідомлять, яка школа прийме вас до мовного класу.',
                'Вас можуть зарахувати лише за таких умов:',
            ],
            'index.info_bullets' => [
                'Вам потрібна інтенсивна підтримка з німецької мови (рівень німецької нижче B1).',
                'На початок наступного навчального року ви перебуваєте в Німеччині не більше 3 років.',
                'Станом на 30 вересня цього року вам щонайменше 16 і не більше 18 років.',
                'У наступному навчальному році ви підлягаєте обов’язковому шкільному навчанню.',
            ],
            'index.access_title' => 'Захист даних і доступ',
            'index.access_intro' => 'Ви можете продовжити з електронною поштою або без неї. Доступ до збережених заяв можливий лише за допомогою особистого коду доступу (токена) та дати народження.',
            'index.access_points' => [
                '<strong>З e-mail:</strong> ви отримаєте код підтвердження і зможете створювати кілька заяв та відкривати їх пізніше.',
                '<strong>Без e-mail:</strong> ви отримаєте особистий код доступу (Access-Token). Будь ласка, запишіть/сфотографуйте його — без підтвердженого e-mail відновлення неможливе.',
            ],
        
            'index.btn_noemail' => 'Продовжити без e-mail',
            'index.btn_create'  => 'Продовжити з e-mail',
            'index.btn_load'    => 'Доступ до заявки/заяв',
            'index.lang_label'  => 'Мова:',
        
            // =======================
            // STEP 1/4: PERSONAL (UK)
            // =======================
            'personal.page_title' => 'Крок 1/4 – Особисті дані',
            'personal.h1' => 'Крок 1/4 – Особисті дані',
            'personal.required_hint' => 'Обов’язкові поля виділені синьою рамкою.',
            'personal.form_error_hint' => 'Будь ласка, перевірте позначені поля.',
        
            'personal.alert_email_title' => 'Вхід через e-mail активний:',
            'personal.alert_email_line1' => 'Ви увійшли з адресою e-mail {email}.',
            'personal.alert_email_line2' => 'Цей e-mail використовується лише для коду доступу (Access-Token) та для пошуку вашої заявки.',
            'personal.alert_email_line3' => 'Нижче ви можете вказати e-mail учениці / учня (за наявності).',
        
            'personal.alert_noemail_title' => 'Примітка (без e-mail):',
            'personal.alert_noemail_body' => 'Будь ласка, запишіть або сфотографуйте ваш код доступу (Access-Token), який буде показано після збереження цієї сторінки. Без підтвердженого e-mail відновлення можливе лише за токеном + датою народження.',
        
            'personal.label.name' => 'Прізвище',
            'personal.label.vorname' => 'Ім’я',
            'personal.label.geschlecht' => 'Стать',
            'personal.gender.m' => 'чоловіча',
            'personal.gender.w' => 'жіноча',
            'personal.gender.d' => 'інша',
        
            'personal.label.geburtsdatum' => 'Народився/лася',
            'personal.label.geburtsdatum_hint' => '(ДД.ММ.РРРР)',
            'personal.placeholder.geburtsdatum' => 'ДД.ММ.РРРР',
        
            'personal.age_hint' => 'Примітка: якщо станом на 30.09.{year} вам менше 16 або більше 18 років, вас не можна зарахувати до мовного класу BBS. Будь ласка, подайте заявку до іншого класу тут:',
            'personal.age_redirect_msg' => "Примітка: якщо станом на 30.09.{year} вам менше 16 або більше 18 років, вас не можна зарахувати до мовного класу BBS. Будь ласка, подайте заявку до іншого класу BBS тут:\n{url}",
        
            'personal.label.geburtsort_land' => 'Місце / країна народження',
            'personal.label.staatsang' => 'Громадянство',
        
            'personal.label.strasse' => 'Вулиця, №',
            'personal.label.plz' => 'Поштовий індекс',
            'personal.plz_choose' => '– будь ласка, оберіть –',
            'personal.plz_hint' => 'Лише Ольденбург (Oldb).',
            'personal.label.wohnort' => 'Місто',
        
            'personal.label.telefon' => 'Номер телефону',
            'personal.label.telefon_vorwahl_help' => 'Код міста/оператора з/без 0',
            'personal.label.telefon_nummer_help' => 'Номер',
            'personal.placeholder.telefon_vorwahl' => '(0)441',
            'personal.placeholder.telefon_nummer' => '123456',
        
            'personal.label.email' => 'E-mail адреса учениці / учня (необов’язково, не адреса IServ)',
            'personal.email_help' => 'Цей e-mail належить учениці / учню (за наявності) і не залежить від e-mail для коду доступу.',
            'personal.placeholder.email' => 'name@example.org',
        
            'personal.label.kontakte' => 'Додаткові контактні дані',
            'personal.kontakte_hint' => '(наприклад, батьки, опікун, установа)',
            'personal.kontakte_error' => 'Будь ласка, перевірте додаткові контакти.',
            'personal.kontakte_add' => '+ Додати контакт',
            'personal.kontakte_remove_title' => 'Видалити контакт',
        
            'personal.table.role' => 'Роль',
            'personal.table.name' => 'Ім’я / установа',
            'personal.table.tel'  => 'Телефон',
            'personal.table.mail' => 'E-mail',
            'personal.table.note_header' => 'Примітка',
            'personal.placeholder.kontakt_name' => 'Ім’я або назва',
            'personal.placeholder.kontakt_tel'  => '+49 …',
            'personal.placeholder.kontakt_note' => 'наприклад, доступність, мова, примітки',
        
            'personal.contact_role.none' => '–',
            'personal.contact_role.mutter' => 'Мати',
            'personal.contact_role.vater' => 'Батько',
            'personal.contact_role.elternteil' => 'Один із батьків',
            'personal.contact_role.betreuer' => 'Опікун/опікунка',
            'personal.contact_role.einrichtung' => 'Установа',
            'personal.contact_role.sonstiges' => 'Інше',
        
            'personal.label.weitere_angaben' => 'Додаткові відомості (наприклад, статус підтримки):',
            'personal.placeholder.weitere_angaben' => 'Тут ви можете вказати, наприклад, особливі потреби у підтримці, спеціальні освітні потреби або інші примітки.',
            'personal.weitere_angaben_help' => 'Необов’язково. Максимум 1500 символів.',
            'personal.btn.cancel' => 'Скасувати',
            'personal.btn.next' => 'Далі',
        
            'personal.dsgvo_text_prefix' => 'Я прочитав/прочитала',
            'personal.dsgvo_link_text' => 'повідомлення про захист даних',
            'personal.dsgvo_text_suffix' => 'і погоджуюся.',
        
            // =====================
            // STEP 2/4: SCHOOL (UK)
            // =====================
            'school.page_title' => 'Крок 2/4 – Школа та інтереси',
            'school.h1' => 'Крок 2/4 – Школа та інтереси',
            'school.required_hint' => 'Обов’язкові поля виділені синьою рамкою.',
            'school.form_error_hint' => 'Будь ласка, перевірте позначені поля.',
        
            'school.top_hint_title' => 'Примітка:',
            'school.top_hint_body'  => 'Якщо ви перебуваєте в Німеччині <u>понад 3 роки</u> або вже володієте німецькою на рівні <u>B1</u> чи вище, вас не можна зарахувати до мовного класу BBS. Будь ласка, подайте заявку до іншого класу BBS тут:',
            'school.bbs_link_label' => 'https://bbs-ol.de/',
        
            'school.autohints_title' => 'Підказки',
        
            'school.label.schule_aktuell' => 'Поточна школа',
            'school.search_placeholder'   => 'Пошук школи… (назва, вулиця, індекс)',
            'school.select_choose'        => 'Будь ласка, оберіть…',
            'school.option_other'         => 'Інша / немає у списку',
            'school.other_placeholder'    => 'Назва школи, вулиця, місто (вільний текст)',
        
            'school.label.teacher'        => 'відповідальний/а вчитель/ка',
            'school.label.teacher_mail'   => 'E-mail відповідального/ої вчителя/ки',
        
            'school.label.herkunft'       => 'Чи відвідували ви школу у країні походження?',
            'school.yes'                  => 'Так',
            'school.no'                   => 'Ні',
            'school.label.herkunft_years' => 'Якщо так: скільки років?',
        
            'school.label.since'          => 'З якого часу ви навчаєтесь у школі в Німеччині?',
            'school.since_month'          => 'Місяць (MM)',
            'school.since_year_ph'        => 'Рік (РРРР)',
            'school.since_help'           => 'Вкажіть місяць+рік <strong>або</strong> скористайтеся полем вільного тексту.',
            'school.label.since_text'     => 'Альтернатива: вільний текст (наприклад, «з осені 2023»)',
        
            'school.label.years_in_de'    => 'Скільки років ви в Німеччині?',
            'school.years_in_de_help'     => 'Примітка: &gt; 3 роки → звичайна заявка до BBS через {link}.',
        
            'school.label.family_lang'    => 'Сімейна мова / рідна мова',
        
            'school.label.level'          => 'Який рівень німецької?',
            'school.level_choose'         => 'Будь ласка, оберіть…',
            'school.level_help'           => 'Примітка: B1 або вище → звичайна заявка до BBS через {link}.',
        
            'school.label.interests'      => 'Інтереси (мін. 1, макс. 2)',
        
            'school.btn.back'             => 'Назад',
            'school.btn.next'             => 'Далі',
        
            // ---------------------
            // Validierung / Errors
            // ---------------------
            'val.school_free_required' => 'Будь ласка, вкажіть назву школи (вільний текст).',
            'val.school_invalid'       => 'Будь ласка, оберіть дійсну школу або «Інша / немає у списку».',
        
            'val.since_required'       => 'Будь ласка, вкажіть місяць+рік або вільний текст.',
            'val.month_invalid'        => 'Місяць має бути 01–12.',
            'val.year_invalid'         => 'Будь ласка, вкажіть дійсний рік.',
            'val.number_required'      => 'Будь ласка, вкажіть число.',
            'val.choose'               => 'Будь ласка, оберіть.',
            'val.herkunft_years'       => 'Будь ласка, вкажіть кількість років.',
        
            'val.level_invalid'        => 'Недійсний вибір.',
        
            'val.interests_min1'       => 'Будь ласка, оберіть щонайменше 1 напрям.',
            'val.interests_max2'       => 'Будь ласка, оберіть не більше 2 напрямів.',
        
            // ---------------------
            // JS Live-Hinweise
            // ---------------------
            'js.hint_years_gt3'  => 'Примітка: ви перебуваєте в Німеччині понад 3 роки. Будь ласка, подайте заявку через {link}.',
            'js.hint_level_b1p'  => 'Примітка: з рівнем німецької B1 або вище подавайте звичайну заявку до BBS через {link}.',
        
            // =========================
            // STEP 3/4: UPLOAD (UK)
            // =========================
            'upload.page_title' => 'Крок 3/4 – Документи (необов’язково)',
            'upload.h1'         => 'Крок 3/4 – Документи (необов’язково)',
        
            'upload.intro'      => 'Тут ви можете завантажити документи. Дозволені формати: <strong>PDF</strong>, <strong>JPG</strong> та <strong>PNG</strong>. Максимальний розмір файлу — <strong>{max_mb} МБ</strong> для кожного файлу.',
        
            'upload.type.zeugnis'    => 'Останнє семестрове табель/свідоцтво',
            'upload.type.lebenslauf' => 'Резюме (CV)',
            'upload.type_hint'       => '(PDF/JPG/PNG, макс. {max_mb} МБ)',
        
            'upload.btn.remove' => 'Видалити',
            'upload.btn.back'   => 'Назад',
            'upload.btn.next'   => 'Далі',
        
            'upload.saved_prefix' => 'Вже збережено:',
            'upload.empty'        => 'Файл ще не завантажено.',
            'upload.saved_html'   => 'Вже збережено: <strong>{filename}</strong>, {size_kb} КБ, завантажено {uploaded_at}',
        
            'upload.checkbox.zeugnis_spaeter' => 'Я подам семестрове свідоцтво після зарахування.',
        
            'upload.flash.no_access' => 'Дійсний доступ не знайдено. Будь ласка, почніть реєстрацію заново.',
            'upload.flash.saved'     => 'Інформацію про завантаження збережено.',
        
            'upload.js.uploading'          => 'Виконується завантаження…',
            'upload.js.unexpected'         => 'Неочікувана відповідь сервера.',
            'upload.js.upload_failed'      => 'Завантаження не вдалося.',
            'upload.js.delete_confirm'     => 'Справді видалити завантажений файл?',
            'upload.js.delete_failed'      => 'Видалення не вдалося.',
            'upload.js.remove_confirm_btn' => 'Видалити файл?',
        
            // AJAX / Fehlertexte
            'upload.ajax.invalid_method'       => 'Недійсний метод',
            'upload.ajax.invalid_csrf'         => 'Недійсний CSRF-токен',
            'upload.ajax.no_access'            => 'Немає дійсного доступу.',
            'upload.ajax.invalid_field'        => 'Недійсне поле',
            'upload.ajax.no_file_sent'         => 'Файл не надіслано',
            'upload.ajax.no_file_selected'     => 'Файл не вибрано',
            'upload.ajax.upload_error'         => 'Помилка завантаження (код {code})',
            'upload.ajax.too_large'            => 'Файл більший за {max_mb} МБ',
            'upload.ajax.mime_only'            => 'Дозволено лише PDF, JPG або PNG',
            'upload.ajax.ext_only'             => 'Недійсне розширення (лише pdf/jpg/jpeg/png)',
            'upload.ajax.cannot_save'          => 'Не вдалося зберегти файл',
            'upload.ajax.unknown_action'       => 'Невідома дія',
            'upload.ajax.server_error'         => 'Помилка сервера під час завантаження',
        
            // =========================
            // STEP 4/4: REVIEW (UK)
            // =========================
            'review.page_title' => 'Крок 4/4 – Підсумок та подання заявки',
        
            'review.h1'      => 'Крок 4/4 – Підсумок та подання заявки',
            'review.subhead' => 'Будь ласка, перевірте ваші дані. Натиснувши «Подати заявку», ви надішлете дані.',
        
            'review.readonly_alert' => 'Цю заявку вже надіслано. Дані можна лише переглядати — змінювати або повторно подавати не можна.',
        
            'review.info.p1' => 'Шановна ученице, шановний учню,',
            'review.info.p2' => 'натиснувши <strong>«Подати заявку»</strong>, ви подали заявку на <strong>BES Мова та інтеграція</strong> в одній із BBS Ольденбурга.',
            'review.info.p3' => 'Це ще не остаточна реєстрація, а <strong>заявка</strong>. Після <strong>20.02.</strong> ви отримаєте інформацію, чи / у якій BBS вас прийнято. Будь ласка, регулярно перевіряйте поштову скриньку та e-mail. Переконайтеся, що ваше ім’я видно на поштовій скриньці, щоб ви могли отримувати листи.',
            'review.info.p4' => 'Разом із підтвердженням від школи вас попросять подати ці документи (якщо ви ще не завантажили їх сьогодні):',
            'review.info.li1' => 'останнє семестрове свідоцтво',
        
            // Accordion Überschriften
            'review.acc.personal' => 'Особисті дані',
            'review.acc.school'   => 'Школа та інтереси',
            'review.acc.uploads'  => 'Документи',
        
            // Labels: Personal
            'review.lbl.name'            => 'Прізвище',
            'review.lbl.vorname'         => 'Ім’я',
            'review.lbl.geschlecht'      => 'Стать',
            'review.lbl.geburtsdatum'    => 'Народився/лася',
            'review.lbl.geburtsort'      => 'Місце / країна народження',
            'review.lbl.staatsang'       => 'Громадянство',
            'review.lbl.strasse'         => 'Вулиця, №',
            'review.lbl.plz_ort'         => 'Індекс / місто',
            'review.lbl.telefon'         => 'Телефон',
            'review.lbl.email'           => 'E-mail (учень/учениця, необов’язково)',
            'review.lbl.weitere_angaben' => 'Додаткові відомості (наприклад, статус підтримки)',
        
            'review.contacts.title'    => 'Додаткові контакти',
            'review.contacts.optional' => 'необов’язково',
            'review.contacts.none'     => '–',
        
            'review.contacts.th.role' => 'Роль',
            'review.contacts.th.name' => 'Ім’я / установа',
            'review.contacts.th.tel'  => 'Телефон',
            'review.contacts.th.mail' => 'E-mail',
            'review.contacts.note'    => 'Примітка:',
        
            // Labels: School
            'review.lbl.school_current' => 'Поточна школа',
            'review.lbl.klassenlehrer'  => 'Відповідальний/а вчитель/ка',
            'review.lbl.mail_lehrkraft' => 'E-mail вчителя/ки',
            'review.lbl.since'          => 'З якого часу у школі',
            'review.lbl.years_de'       => 'Років у Німеччині',
            'review.lbl.family_lang'    => 'Сімейна мова / рідна мова',
            'review.lbl.de_level'       => 'Рівень німецької',
            'review.lbl.school_origin'  => 'Школа у країні походження',
            'review.lbl.years_origin'   => 'Років навчання у країні походження',
            'review.lbl.interests'      => 'Інтереси',
        
            // Uploads
            'review.lbl.zeugnis'         => 'Семестрове свідоцтво',
            'review.lbl.lebenslauf'      => 'Резюме (CV)',
            'review.lbl.later'           => 'Подати пізніше',
            'review.badge.uploaded'      => 'завантажено',
            'review.badge.not_uploaded'  => 'не завантажено',
            'review.yes'                 => 'Так',
            'review.no'                  => 'Ні',
        
            // Buttons / Actions
            'review.btn.home'   => 'На головну',
            'review.btn.newapp' => 'Подати ще одну заявку',
            'review.btn.back'   => 'Назад',
            'review.btn.submit' => 'Подати заявку',
        
            // Errors / Flash / Systemtexte
            'review.err.invalid_request'     => 'Недійсний запит.',
            'review.flash.already_submitted' => 'Цю заявку вже надіслано — її не можна повторно подати або змінити.',
            'review.flash.no_token'          => 'Немає дійсного коду доступу. Будь ласка, почніть процес заново.',
            'review.err.not_found_token'     => 'Заявку за цим токеном не знайдено.',
            'review.flash.submit_error'      => 'Під час надсилання сталася помилка. Будь ласка, спробуйте пізніше.',
        
            // Gender fallback
            'review.gender.m' => 'чоловіча',
            'review.gender.w' => 'жіноча',
            'review.gender.d' => 'інша',
        
            // Fallback Anzeige
            'review.value.empty' => '–',
        
            // =========================
            // STATUS (UK)
            // =========================
            'status.hdr_title'   => 'Заявку успішно збережено',
            'status.hdr_message' => 'Вашу заявку надіслано.',
        
            'status.h1' => 'Вашу заявку успішно збережено.',
        
            'status.success.title' => 'Дякуємо!',
            'status.success.body'  => 'Вашу заявку надіслано, її буде опрацьовано найближчим часом.',
        
            'status.info.title' => 'Важлива примітка',
            'status.info.body'  => '<em>[ЗАГЛУШКА: текст від замовника буде пізніше]</em>',
        
            'status.btn.pdf'    => 'Завантажити / роздрукувати PDF',
            'status.btn.newapp' => 'Почати ще одну заявку',
            'status.btn.home'   => 'На головну',
        
            'status.ref' => 'Довідка: заявка #{id}',
        
            'status.err.invalid_request' => 'Недійсний запит.',
        
            // =========================
            // PDF (UK)
            // =========================
            'pdf.err.autoload_missing' => 'Composer Autoload не знайдено. Будь ласка, виконайте "composer install".',
            'pdf.err.no_token'         => 'Немає дійсного коду доступу. Будь ласка, почніть процес заново.',
            'pdf.err.not_found'        => 'Заявку не знайдено.',
            'pdf.err.server'           => 'Помилка сервера під час створення PDF.',
        
            'pdf.header_title' => 'Заявка – Підсумок',
            'pdf.footer_auto'  => 'Автоматично створений документ',
            'pdf.footer_page'  => 'Сторінка {cur} / {max}',
        
            'pdf.meta.ref'        => 'Заявка #{id}',
            'pdf.meta.created_at' => 'Створено',
            'pdf.meta.status'     => 'Статус',
        
            'pdf.top.title'        => 'Короткий огляд',
            'pdf.top.name'         => 'Прізвище',
            'pdf.top.reference'    => 'Довідка',
            'pdf.top.generated'    => 'Створено',
            'pdf.top.hint'         => 'Примітка',
            'pdf.top.keep_note'    => 'Будь ласка, збережіть цей документ для ваших записів.',
            'pdf.hint_placeholder' => '[ЗАГЛУШКА: текст від замовника буде пізніше]',
        
            'pdf.sec1.title' => '1) Особисті дані',
            'pdf.sec2.title' => '2) Додаткові контактні дані',
            'pdf.sec3.title' => '3) Школа та інтереси',
            'pdf.sec4.title' => '4) Документи',
        
            'pdf.lbl.name'           => 'Прізвище',
            'pdf.lbl.vorname'        => 'Ім’я',
            'pdf.lbl.gender'         => 'Стать',
            'pdf.lbl.dob'            => 'Дата народження',
            'pdf.lbl.birthplace'     => 'Місце/країна народження',
            'pdf.lbl.nationality'    => 'Громадянство',
            'pdf.lbl.address'        => 'Адреса',
            'pdf.lbl.phone'          => 'Телефон',
            'pdf.lbl.email_optional' => 'E-mail (необов’язково)',
            'pdf.lbl.more'           => 'Додаткові відомості',
        
            'pdf.lbl.school_current' => 'Поточна школа',
            'pdf.lbl.teacher'        => 'Вчитель/ка',
            'pdf.lbl.teacher_email'  => 'E-mail вчителя/ки',
            'pdf.lbl.since_school'   => 'З якого часу у школі',
            'pdf.lbl.years_in_de'    => 'З якого часу в Німеччині',
            'pdf.lbl.family_lang'    => 'Сімейна мова',
            'pdf.lbl.de_level'       => 'Рівень німецької',
            'pdf.lbl.school_origin'  => 'Школа у країні походження',
            'pdf.lbl.years_origin'   => 'Років навчання у країні походження',
            'pdf.lbl.interests'      => 'Інтереси',
        
            'pdf.lbl.report'       => 'Семестрове свідоцтво',
            'pdf.lbl.cv'           => 'Резюме (CV)',
            'pdf.lbl.report_later' => 'Свідоцтво подати пізніше',
        
            'pdf.uploaded'     => 'завантажено',
            'pdf.not_uploaded' => 'не завантажено',
        
            'pdf.contacts.none'   => '–',
            'pdf.contacts.th.role'=> 'Роль',
            'pdf.contacts.th.name'=> 'Ім’я/установа',
            'pdf.contacts.th.tel' => 'Телефон',
            'pdf.contacts.th.mail'=> 'E-mail',
            'pdf.contacts.th.note'=> 'Примітка',
        
            'pdf.gender.m' => 'чоловіча',
            'pdf.gender.w' => 'жіноча',
            'pdf.gender.d' => 'інша',
        
            'pdf.yes' => 'Так',
            'pdf.no'  => 'Ні',
        
            'pdf.sec4.note' => 'Цей документ є автоматично створеним підсумком введених даних.',
        
            'pdf.filename_prefix' => 'Заявка',
        
            // =========================
            // ACCESS_CREATE (UK)
            // =========================
            'access_create.title'         => 'Продовжити з e-mail',
            'access_create.lead'          => 'Ви можете увійти зі своїм доступом або створити новий доступ.',
            'access_create.tabs_login'    => 'Увійти',
            'access_create.tabs_register' => 'Створити новий доступ',
        
            'access_create.login_title' => 'Увійти (існуючий доступ)',
            'access_create.login_text'  => 'Будь ласка, введіть вашу адресу e-mail та пароль.',
            'access_create.email_label' => 'Адреса e-mail',
            'access_create.pass_label'  => 'Пароль',
            'access_create.login_btn'   => 'Увійти',
            'access_create.login_err'   => 'E-mail/пароль неправильні або доступ не підтверджено.',
        
            'access_create.reg_title'     => 'Створити новий доступ',
            'access_create.reg_text'      => 'Ми надішлемо вам 6-значний код підтвердження. Після успішного підтвердження ви отримаєте пароль на e-mail.',
            'access_create.consent_label' => 'Я погоджуюся, що мій e-mail буде використано для процесу реєстрації.',
            'access_create.send_btn'      => 'Надіслати код',
            'access_create.code_label'    => 'Код підтвердження',
            'access_create.verify_btn'    => 'Перевірити код',
            'access_create.resend'        => 'Надіслати код повторно',
        
            'access_create.info_sent'    => 'Ми надіслали вам код. Будь ласка, перевірте також папку «Спам».',
            'access_create.ok_verified'  => 'E-mail підтверджено. Пароль надіслано. Тепер ви можете увійти.',
            'access_create.email_in_use' => 'Ця адреса e-mail вже має доступ. Будь ласка, увійдіть.',
        
            'access_create.error_email'     => 'Будь ласка, введіть дійсну адресу e-mail.',
            'access_create.error_consent'   => 'Будь ласка, погодьтеся на використання вашого e-mail.',
            'access_create.error_rate'      => 'Забагато спроб. Будь ласка, зачекайте та спробуйте ще раз.',
            'access_create.error_code'      => 'Код недійсний або термін дії минув.',
            'access_create.error_resend'    => 'Повторне надсилання неможливе. Будь ласка, почніть спочатку.',
            'access_create.error_mail_send' => 'Не вдалося надіслати e-mail. Спробуйте пізніше.',
            'access_create.error_db'        => 'Помилка сервера (БД).',
        
            'access_create.back'   => 'Назад',
            'access_create.cancel' => 'Скасувати',
        
            'access_create.mail_subject' => 'Ваш пароль для онлайн-реєстрації',
            'access_create.mail_body'    => "Ваш доступ створено.\n\nE-mail: {email}\nПароль: {password}\n\nБудь ласка, зберігайте пароль у безпечному місці.",
        
            // =========================
            // ACCESS_PORTAL (UK)
            // =========================
            'access_portal.title'    => 'Мої заявки',
            'access_portal.lead'     => 'Тут ви бачите ваші заявки. Ви можете продовжити існуючу заявку або почати нову.',
            'access_portal.max_hint' => '{email} · макс. {max} заяв(и)',
        
            'access_portal.btn_new'    => 'Почати нову заявку',
            'access_portal.btn_open'   => 'Відкрити',
            'access_portal.btn_logout' => 'Вийти',
        
            'access_portal.th_ref'     => 'ID',
            'access_portal.th_status'  => 'Статус',
            'access_portal.th_created' => 'Створено',
            'access_portal.th_updated' => 'Оновлено',
            'access_portal.th_token'   => 'Токен',
            'access_portal.th_action'  => 'Дія',
        
            'access_portal.status_draft'     => 'Чернетка',
            'access_portal.status_submitted' => 'Надіслано',
            'access_portal.status_withdrawn' => 'Відкликано',
        
            'access_portal.limit_reached' => 'Ви досягли максимальної кількості заяв для цієї адреси e-mail.',
            'access_portal.no_apps'       => 'Заяв поки що немає.',
            'access_portal.err_generic'   => 'Сталася помилка.',
            'access_portal.csrf_invalid'  => 'Недійсний запит.',
        
            // =========================
            // ACCESS_LOGIN (UK)
            // =========================
            'access_login.title'            => 'Доступ до заявки/заяв',
            'access_login.lead'             => 'Тут ви можете знову відкрити вже розпочату або надіслану заявку.',
        
            'access_login.login_box_title'  => 'Вхід за Access-Token',
            'access_login.login_box_text'   => 'Будь ласка, введіть ваш особистий код доступу (Access-Token) та дату народження.',
        
            'access_login.token_label'      => 'Access-Token',
            'access_login.dob_label'        => 'Дата народження (ДД.ММ.РРРР)',
        
            'access_login.login_btn'        => 'Доступ',
            'access_login.back'             => 'Назад на головну',
        
            'access_login.login_ok'         => 'Заявку завантажено.',
            'access_login.login_error'      => 'Комбінацію Access-Token та дати народження не знайдено.',
            'access_login.login_error_token'=> 'Будь ласка, введіть дійсний Access-Token.',
            'access_login.login_error_dob'  => 'Будь ласка, введіть дійсну дату народження у форматі ДД.ММ.РРРР.',
        
            'access_login.csrf_invalid'     => 'Недійсний запит.',
            'access_login.internal_error'   => 'Внутрішня помилка.',
            'access_login.load_error'       => 'Під час завантаження заявки сталася помилка.',
        
            // =========================
            // PRIVACY (UK)
            // =========================
            'privacy.title' => 'Захист даних',
            'privacy.h1'    => 'Інформація про захист даних для онлайн-заявки «BES Мова та інтеграція»',
        
            'privacy.s1_title'     => '1. Відповідальна установа',
            'privacy.s1_body_html' => '<strong>Місто Ольденбург / Професійні школи</strong><br>(вкажіть точну назву установи/школи, адресу, телефон, e-mail)',
        
            'privacy.s2_title'     => '2. Уповноважений/а з питань захисту даних',
            'privacy.s2_body_html' => '(вкажіть контактні дані уповноваженого/ої з питань захисту даних)',
        
            'privacy.s3_title' => '3. Мета обробки',
            'privacy.s3_li1'   => 'Прийняття та опрацювання вашої заявки на зарахування до мовного класу («BES Мова та інтеграція»)',
            'privacy.s3_li2'   => 'Комунікація з вами (уточнення, повідомлення щодо рішення про зарахування)',
            'privacy.s3_li3'   => 'Планування організації навчання (розподіл до BBS)',
        
            'privacy.s4_title' => '4. Правові підстави',
            'privacy.s4_li1'   => 'Ст. 6 ч. 1 літ. e GDPR у поєднанні зі шкільним законодавством землі Нижня Саксонія',
            'privacy.s4_li2'   => 'Ст. 6 ч. 1 літ. c GDPR (виконання юридичного обов’язку)',
            'privacy.s4_li3'   => 'Ст. 6 ч. 1 літ. a GDPR (згода), якщо надаються добровільні відомості/завантаження',
        
            'privacy.s5_title' => '5. Категорії персональних даних',
            'privacy.s5_li1'   => 'Основні дані (прізвище, ім’я, дата народження, громадянство, адреса, контактні дані)',
            'privacy.s5_li2'   => 'Шкільна інформація (поточна школа, рівень мови, інтереси)',
            'privacy.s5_li3'   => 'Необов’язкові документи (наприклад, останнє семестрове свідоцтво)',
            'privacy.s5_li4'   => 'Додаткові контакти (батьки/опікун/установи)',
        
            'privacy.s6_title' => '6. Отримувачі',
            'privacy.s6_body'  => 'У межах компетенції міста Ольденбург та професійних шкіл. Передача третім особам здійснюється лише за потреби згідно із законом (наприклад, шкільним органам) або за вашою згодою.',
        
            'privacy.s7_title' => '7. Передача до третіх країн',
            'privacy.s7_body'  => 'Передача до третіх країн не здійснюється.',
        
            'privacy.s8_title' => '8. Термін зберігання',
            'privacy.s8_body'  => 'Ваші дані зберігаються протягом процедури подання/зарахування та відповідно до встановлених законом строків зберігання, а потім видаляються.',
        
            'privacy.s9_title' => '9. Ваші права',
            'privacy.s9_li1'   => 'Доступ (ст. 15 GDPR), виправлення (ст. 16), видалення (ст. 17), обмеження (ст. 18)',
            'privacy.s9_li2'   => 'Заперечення (ст. 21) проти обробки в суспільних інтересах',
            'privacy.s9_li3'   => 'Відкликання наданої згоди (ст. 7 ч. 3) на майбутнє',
            'privacy.s9_li4'   => 'Право на скаргу до наглядового органу: Уповноважений/а з питань захисту даних Нижньої Саксонії',
        
            'privacy.s10_title' => '10. Хостинг і журнали',
            'privacy.s10_body'  => 'Застосунок працює на серверах міста або в комунальному дата-центрі. Обробляються лише технічно необхідні дані (наприклад, серверні логи для пошуку помилок). Зовнішні CDN не використовуються. Встановлюється лише мовний cookie.',
        
            'privacy.s11_title'    => '11. Cookies',
            'privacy.s11_li1_html' => '<strong>lang</strong> – зберігає вибрану мову (діє 12 місяців). Мета: зручність користування.',
            'privacy.s11_li2'      => 'PHP-сесія – технічно необхідна для роботи форми, видаляється після завершення сесії.',
        
            'privacy.stand_label' => 'Стан',
            'privacy.stand_hint'  => 'Будь ласка, регулярно перевіряйте, чи є зміни.',
            'privacy.back_home'   => 'На головну',
        
            // =========================
            // IMPRINT (UK)
            // =========================
            'imprint.title' => 'Вихідні дані',
            'imprint.h1'    => 'Вихідні дані',
        
            'imprint.s1_title'     => 'Постачальник послуг',
            'imprint.s1_body_html' => '<strong>Місто ***</strong><br>Професійні школи<br>(вкажіть точну адресу)<br>Телефон: (доповнити)<br>E-mail: (доповнити)',
        
            'imprint.s2_title'     => 'Представництво',
            'imprint.s2_body_html' => '(наприклад, мер/мерка міста ****<br>або керівництво відповідної BBS)',
        
            'imprint.s3_title'     => 'Відповідальний/а за зміст згідно з § 18 абз. 2 MStV',
            'imprint.s3_body_html' => '(ім’я, посада, контакт, наприклад, керівництво BBS або пресслужба)',
        
            'imprint.s4_title'     => 'Ідентифікаційний номер ПДВ',
            'imprint.s4_body_html' => '(за наявності; інакше цей розділ можна пропустити)',
        
            'imprint.s5_title' => 'Наглядовий орган',
            'imprint.s5_body'  => '(компетентний комунальний нагляд / шкільний орган, наприклад, регіональний підрозділ земельної шкільної служби)',
        
            'imprint.s6_title' => 'Відповідальність за зміст',
            'imprint.s6_body'  => 'Зміст наших сторінок створено з максимальною ретельністю. Однак ми не можемо гарантувати правильність, повноту та актуальність. Як публічна установа ми відповідаємо за власний зміст згідно з § 7 абз. 1 TMG.',
        
            'imprint.s7_title' => 'Відповідальність за посилання',
            'imprint.s7_body'  => 'Наша пропозиція не містить зовнішнього вмісту, який передає персональні дані третім особам. Якщо ми посилаємося на інформаційні пропозиції інших публічних установ, ми не несемо відповідальності за їхній зміст.',
        
            'imprint.s8_title' => 'Авторське право',
            'imprint.s8_body'  => 'Вміст і твори, створені містом Ольденбург, охороняються німецьким авторським правом. Матеріали третіх сторін позначені відповідним чином. Відтворення, обробка, поширення та будь-яке використання поза межами авторського права потребують письмової згоди міста Ольденбург або відповідного правовласника.',
        
            'imprint.stand_label' => 'Стан',
            'imprint.stand_hint'  => 'Ці дані стосуються онлайн-форми «BES Мова та інтеграція».',
            'imprint.back_home'   => 'На головну',
        
            // =========================
            // VERIFY_EMAIL (UK)
            // =========================
            'verify_email.title' => 'Підтвердити e-mail',
            'verify_email.h1'    => 'Підтвердити e-mail',
        
            'verify_email.lead_sent'    => 'Ми надіслали код підтвердження на {email}.',
            'verify_email.lead_generic' => 'Будь ласка, введіть код підтвердження, отриманий електронною поштою. Якщо ви не бачите листа, можна надіслати код повторно на вашу адресу.',
        
            'verify_email.code_label'  => 'Код підтвердження (6 цифр)',
            'verify_email.email_label' => 'Ваша адреса e-mail',
        
            'verify_email.btn_verify' => 'Підтвердити',
            'verify_email.btn_resend' => 'Надіслати код повторно',
            'verify_email.hint_spam'  => 'Будь ласка, перевірте також папку «Спам».',
        
            'verify_email.back' => 'Назад',
        
            'verify_email.csrf_invalid' => 'Недійсний запит.',
            'verify_email.ok_verified'  => 'E-mail успішно підтверджено.',
            'verify_email.ok_sent'      => 'Новий код надіслано на {email}.',
        
            'verify_email.warn_cooldown'     => 'Будь ласка, зачекайте трохи перед повторним запитом коду.',
            'verify_email.error_send'        => 'Не вдалося надіслати. Спробуйте пізніше.',
            'verify_email.error_email'       => 'Будь ласка, введіть дійсну адресу e-mail.',
            'verify_email.error_no_session'  => 'Активний процес підтвердження не знайдено. Будь ласка, запросіть новий код.',
            'verify_email.error_expired'     => 'Код недійсний або термін дії минув.',
            'verify_email.error_invalid'     => 'Код недійсний або термін дії минув.',
            'verify_email.error_code_format' => 'Будь ласка, введіть дійсний 6-значний код.',
            'verify_email.error_rate'        => 'Забагато спроб. Будь ласка, запросіть новий код.',
        
            // =========================
            // VALIDATION (UK) – global
            // =========================
            'val.required' => 'Обов’язково.',
            'val.only_letters' => 'Будь ласка, лише літери.',
            'val.gender_choose' => 'Будь ласка, оберіть стать.',
            'val.date_format' => 'ДД.ММ.РРРР',
            'val.date_invalid' => 'Недійсна дата.',
            'val.plz_whitelist' => 'Лише поштові індекси Ольденбурга (26121–26135).',
            'val.phone_vorwahl' => 'Код: 2–6 цифр.',
            'val.phone_nummer' => 'Номер: 3–12 цифр.',
            'val.email_invalid' => 'Недійсний e-mail.',
            'val.email_no_iserv' => 'Будь ласка, приватний e-mail (не IServ).',
            'val.max_1500' => 'Максимум 1500 символів.',
            'val.kontakt_row_name_missing' => 'Відсутнє ім’я/назва',
            'val.kontakt_row_tel_or_mail'  => 'Вкажіть телефон АБО e-mail',
            'val.kontakt_row_mail_invalid' => 'Недійсний e-mail',
            'val.kontakt_row_tel_invalid'  => 'Недійсний телефон',
        ],

        // =======================
        // AR: in 'ar' => [ ... ] einfügen (komplett)
        // =======================
        'ar' => [
        
            // =======================
            // STEP Start: Index (AR)
            // =======================
            'index.title' => 'مرحبًا بكم في التسجيل الإلكتروني – صفوف اللغة',
            'index.lead'  => 'هذه الخدمة مخصّصة للوافدين الجدد إلى أولدنبورغ. يساعدنا النموذج على التواصل معكم واختيار العروض المناسبة.',
            'index.bullets' => [
                'يرجى تجهيز بيانات الاتصال ووثائق الهوية (إن وُجدت).',
                'يمكن تعبئة النموذج بعدة لغات.',
                'تُعالج بياناتكم بسرية وفقًا للائحة العامة لحماية البيانات (GDPR).',
            ],
            'index.info_p' => [
                'عزيزتي الطالبة، عزيزي الطالب،',
                'بهذه الطلبات تتقدّم/ين للحصول على مقعد في صف تعلّم اللغة «BES اللغة والاندماج» في إحدى المدارس المهنية (BBS) في أولدنبورغ. لا تتقدّم/ين إلى مدرسة بعينها. بعد 20 فبراير سيتم إبلاغك أي مدرسة ستقبلك في الصف.',
                'لا يمكن قبولك إلا إذا توفّرت الشروط التالية:',
            ],
            'index.info_bullets' => [
                'تحتاج/ين إلى دعم مكثّف في اللغة الألمانية (مستوى أقل من B1).',
                'عند بداية العام الدراسي القادم لا تتجاوز مدة إقامتك في ألمانيا 3 سنوات.',
                'في تاريخ 30 سبتمبر من هذا العام يكون عمرك بين 16 و18 عامًا.',
                'ستكون/ين خاضعًا/ة للتعليم الإلزامي في العام الدراسي القادم.',
            ],
            'index.access_title' => 'حماية البيانات والوصول',
            'index.access_intro' => 'يمكنك المتابعة مع أو بدون بريد إلكتروني. لا يمكن الوصول إلى الطلبات المحفوظة إلا باستخدام رمز الوصول الشخصي (Token) وتاريخ الميلاد.',
            'index.access_points' => [
                '<strong>مع البريد الإلكتروني:</strong> ستتلقى رمز تأكيد ويمكنك إنشاء عدة طلبات وفتحها لاحقًا.',
                '<strong>بدون بريد إلكتروني:</strong> ستحصل على رمز وصول شخصي (Access-Token). يرجى كتابته/تصويره — بدون بريد إلكتروني مُوثَّق لا يمكن الاستعادة.',
            ],
        
            'index.btn_noemail' => 'المتابعة دون بريد إلكتروني',
            'index.btn_create'  => 'المتابعة مع البريد الإلكتروني',
            'index.btn_load'    => 'الوصول إلى الطلب/الطلبات',
            'index.lang_label'  => 'اللغة:',
        
            // =======================
            // STEP 1/4: PERSONAL (AR)
            // =======================
            'personal.page_title' => 'الخطوة 1/4 – البيانات الشخصية',
            'personal.h1' => 'الخطوة 1/4 – البيانات الشخصية',
            'personal.required_hint' => 'الحقول الإلزامية مميزة بإطار أزرق.',
            'personal.form_error_hint' => 'يرجى التحقق من الحقول المحددة.',
        
            'personal.alert_email_title' => 'تسجيل الدخول بالبريد الإلكتروني مفعل:',
            'personal.alert_email_line1' => 'تم تسجيل الدخول باستخدام البريد الإلكتروني {email}.',
            'personal.alert_email_line2' => 'يُستخدم هذا البريد الإلكتروني فقط لرمز الوصول (Access-Token) وللعثور على طلبك مرة أخرى.',
            'personal.alert_email_line3' => 'يمكنك أدناه إدخال البريد الإلكتروني للطالبة/الطالب (إن وُجد).',
        
            'personal.alert_noemail_title' => 'ملاحظة (بدون بريد إلكتروني):',
            'personal.alert_noemail_body' => 'يرجى كتابة أو تصوير رمز الوصول (Access-Token) الذي سيظهر بعد حفظ هذه الصفحة. بدون بريد إلكتروني مُتحقق منه، لا يمكن الاسترجاع إلا باستخدام الرمز + تاريخ الميلاد.',
        
            'personal.label.name' => 'اسم العائلة',
            'personal.label.vorname' => 'الاسم الأول',
            'personal.label.geschlecht' => 'الجنس',
            'personal.gender.m' => 'ذكر',
            'personal.gender.w' => 'أنثى',
            'personal.gender.d' => 'متنوع',
        
            'personal.label.geburtsdatum' => 'تاريخ الميلاد',
            'personal.label.geburtsdatum_hint' => '(يوم.شهر.سنة)',
            'personal.placeholder.geburtsdatum' => 'يوم.شهر.سنة',
        
            'personal.age_hint' => 'ملاحظة: إذا كان عمرك في 30.09.{year} أقل من 16 أو أكثر من 18 عامًا، فلن يمكن قبولك في صف اللغة في BBS. يرجى التقديم لصف آخر هنا:',
            'personal.age_redirect_msg' => "ملاحظة: إذا كان عمرك في 30.09.{year} أقل من 16 أو أكثر من 18 عامًا، فلن يمكن قبولك في صف اللغة في BBS.\nيرجى التقديم لصف آخر في BBS هنا:\n{url}",
        
            'personal.label.geburtsort_land' => 'مكان / بلد الميلاد',
            'personal.label.staatsang' => 'الجنسية',
        
            'personal.label.strasse' => 'الشارع، رقم المنزل',
            'personal.label.plz' => 'الرمز البريدي',
            'personal.plz_choose' => '– الرجاء الاختيار –',
            'personal.plz_hint' => 'أولدنبرغ (Oldb) فقط.',
            'personal.label.wohnort' => 'المدينة',
        
            'personal.label.telefon' => 'رقم الهاتف',
            'personal.label.telefon_vorwahl_help' => 'رمز المنطقة مع/بدون 0',
            'personal.label.telefon_nummer_help' => 'الرقم',
            'personal.placeholder.telefon_vorwahl' => '(0)441',
            'personal.placeholder.telefon_nummer' => '123456',
        
            'personal.label.email' => 'البريد الإلكتروني للطالبة/الطالب (اختياري، ليس عنوان IServ)',
            'personal.email_help' => 'هذا البريد الإلكتروني يخص الطالبة/الطالب (إن وُجد) وهو مستقل عن البريد الإلكتروني لرمز الوصول.',
            'personal.placeholder.email' => 'name@example.org',
        
            'personal.label.kontakte' => 'بيانات اتصال إضافية',
            'personal.kontakte_hint' => '(مثل الوالدين، المشرف/ة، المؤسسة)',
            'personal.kontakte_error' => 'يرجى التحقق من جهات الاتصال الإضافية.',
            'personal.kontakte_add' => '+ إضافة جهة اتصال',
            'personal.kontakte_remove_title' => 'إزالة جهة الاتصال',
        
            'personal.table.role' => 'الدور',
            'personal.table.name' => 'الاسم / المؤسسة',
            'personal.table.tel'  => 'الهاتف',
            'personal.table.mail' => 'البريد الإلكتروني',
            'personal.table.note_header' => 'ملاحظة',
            'personal.placeholder.kontakt_name' => 'الاسم أو الوصف',
            'personal.placeholder.kontakt_tel'  => '+49 …',
            'personal.placeholder.kontakt_note' => 'مثل أوقات التوفر، اللغة، ملاحظات',
        
            'personal.contact_role.none' => '–',
            'personal.contact_role.mutter' => 'الأم',
            'personal.contact_role.vater' => 'الأب',
            'personal.contact_role.elternteil' => 'أحد الوالدين',
            'personal.contact_role.betreuer' => 'مشرف/ة',
            'personal.contact_role.einrichtung' => 'مؤسسة',
            'personal.contact_role.sonstiges' => 'أخرى',
        
            'personal.label.weitere_angaben' => 'معلومات إضافية (مثل حالة الدعم):',
            'personal.placeholder.weitere_angaben' => 'يمكنك هنا ذكر مثلًا احتياجات دعم خاصة أو احتياجات دعم تربوي خاص أو ملاحظات أخرى.',
            'personal.weitere_angaben_help' => 'اختياري. بحد أقصى 1500 حرف.',
            'personal.btn.cancel' => 'إلغاء',
            'personal.btn.next' => 'التالي',
        
            'personal.dsgvo_text_prefix' => 'لقد قرأت',
            'personal.dsgvo_link_text' => 'إشعار حماية البيانات',
            'personal.dsgvo_text_suffix' => 'وأوافق.',
        
            // =====================
            // STEP 2/4: SCHOOL (AR)
            // =====================
            'school.page_title' => 'الخطوة 2/4 – المدرسة والاهتمامات',
            'school.h1' => 'الخطوة 2/4 – المدرسة والاهتمامات',
            'school.required_hint' => 'الحقول الإلزامية مميزة بإطار أزرق.',
            'school.form_error_hint' => 'يرجى التحقق من الحقول المحددة.',
        
            'school.top_hint_title' => 'ملاحظة:',
            'school.top_hint_body'  => 'إذا كنت في ألمانيا <u>أكثر من 3 سنوات</u> أو كنت تتحدث الألمانية بالفعل بمستوى <u>B1</u> أو أعلى، فلن يمكن قبولك في صف اللغة في BBS. يرجى التقديم لصف آخر في BBS هنا:',
            'school.bbs_link_label' => 'https://bbs-ol.de/',
        
            'school.autohints_title' => 'ملاحظات',
        
            'school.label.schule_aktuell' => 'المدرسة الحالية',
            'school.search_placeholder'   => 'ابحث عن مدرسة… (الاسم، الشارع، الرمز البريدي)',
            'school.select_choose'        => 'يرجى الاختيار…',
            'school.option_other'         => 'أخرى / غير موجودة في القائمة',
            'school.other_placeholder'    => 'اسم المدرسة، الشارع، المدينة (نص حر)',
        
            'school.label.teacher'        => 'المعلّم/ة المسؤول/ة',
            'school.label.teacher_mail'   => 'بريد المعلّم/ة المسؤول/ة',
        
            'school.label.herkunft'       => 'هل درست في بلد المنشأ؟',
            'school.yes'                  => 'نعم',
            'school.no'                   => 'لا',
            'school.label.herkunft_years' => 'إذا نعم: كم عدد السنوات؟',
        
            'school.label.since'          => 'منذ متى تدرس في مدرسة في ألمانيا؟',
            'school.since_month'          => 'الشهر (MM)',
            'school.since_year_ph'        => 'السنة (YYYY)',
            'school.since_help'           => 'أدخل الشهر+السنة <strong>أو</strong> استخدم حقل النص الحر.',
            'school.label.since_text'     => 'بديل: نص حر (مثلًا: «منذ خريف 2023»)',
        
            'school.label.years_in_de'    => 'منذ كم سنة أنت في ألمانيا؟',
            'school.years_in_de_help'     => 'ملاحظة: &gt; 3 سنوات → يرجى التقديم بشكل اعتيادي عبر {link}.',
        
            'school.label.family_lang'    => 'لغة الأسرة / اللغة الأولى',
        
            'school.label.level'          => 'ما هو مستوى اللغة الألمانية؟',
            'school.level_choose'         => 'يرجى الاختيار…',
            'school.level_help'           => 'ملاحظة: B1 أو أعلى → التقديم الاعتيادي عبر {link}.',
        
            'school.label.interests'      => 'الاهتمامات (حد أدنى 1، حد أقصى 2)',
        
            'school.btn.back'             => 'رجوع',
            'school.btn.next'             => 'التالي',
        
            // ---------------------
            // Validation / Errors
            // ---------------------
            'val.school_free_required' => 'يرجى إدخال اسم المدرسة (نص حر).',
            'val.school_invalid'       => 'يرجى اختيار مدرسة صالحة أو «أخرى / غير موجودة في القائمة».',
        
            'val.since_required'       => 'يرجى إدخال الشهر+السنة أو نصًا حرًا.',
            'val.month_invalid'        => 'يجب أن يكون الشهر بين 01 و12.',
            'val.year_invalid'         => 'يرجى إدخال سنة صالحة.',
            'val.number_required'      => 'يرجى إدخال رقم.',
            'val.choose'               => 'يرجى الاختيار.',
            'val.herkunft_years'       => 'يرجى إدخال عدد السنوات.',
        
            'val.level_invalid'        => 'اختيار غير صالح.',
        
            'val.interests_min1'       => 'يرجى اختيار مجال واحد على الأقل.',
            'val.interests_max2'       => 'يرجى اختيار مجالين كحد أقصى.',
        
            // ---------------------
            // JS Live hints
            // ---------------------
            'js.hint_years_gt3'  => 'ملاحظة: أنت في ألمانيا منذ أكثر من 3 سنوات. يرجى التقديم عبر {link}.',
            'js.hint_level_b1p'  => 'ملاحظة: مع مستوى B1 أو أعلى يرجى التقديم الاعتيادي عبر {link}.',
        
            // =========================
            // STEP 3/4: UPLOAD (AR)
            // =========================
            'upload.page_title' => 'الخطوة 3/4 – المستندات (اختياري)',
            'upload.h1'         => 'الخطوة 3/4 – المستندات (اختياري)',
        
            'upload.intro'      => 'يمكنك هنا رفع المستندات. الصيغ المسموحة هي <strong>PDF</strong> و<strong>JPG</strong> و<strong>PNG</strong>. الحد الأقصى لحجم الملف هو <strong>{max_mb} MB</strong> لكل ملف.',
        
            'upload.type.zeugnis'    => 'آخر شهادة نصف سنوية',
            'upload.type.lebenslauf' => 'السيرة الذاتية (CV)',
            'upload.type_hint'       => '(PDF/JPG/PNG، بحد أقصى {max_mb} MB)',
        
            'upload.btn.remove' => 'إزالة',
            'upload.btn.back'   => 'رجوع',
            'upload.btn.next'   => 'التالي',
        
            'upload.saved_prefix' => 'تم الحفظ بالفعل:',
            'upload.empty'        => 'لم يتم رفع أي ملف بعد.',
            'upload.saved_html'   => 'تم الحفظ بالفعل: <strong>{filename}</strong>، {size_kb} KB، تم الرفع بتاريخ {uploaded_at}',
        
            'upload.checkbox.zeugnis_spaeter' => 'سأقوم بتقديم الشهادة نصف السنوية لاحقًا بعد القبول.',
        
            'upload.flash.no_access' => 'لم يتم العثور على وصول صالح. يرجى بدء التسجيل من جديد.',
            'upload.flash.saved'     => 'تم حفظ معلومات الرفع.',
        
            'upload.js.uploading'          => 'جارٍ تنفيذ الرفع…',
            'upload.js.unexpected'         => 'استجابة غير متوقعة من الخادم.',
            'upload.js.upload_failed'      => 'فشل الرفع.',
            'upload.js.delete_confirm'     => 'هل تريد حقًا إزالة الملف المرفوع؟',
            'upload.js.delete_failed'      => 'فشل الحذف.',
            'upload.js.remove_confirm_btn' => 'إزالة الملف؟',
        
            // AJAX / errors
            'upload.ajax.invalid_method'       => 'طريقة غير صالحة',
            'upload.ajax.invalid_csrf'         => 'رمز CSRF غير صالح',
            'upload.ajax.no_access'            => 'لا يوجد وصول صالح.',
            'upload.ajax.invalid_field'        => 'حقل غير صالح',
            'upload.ajax.no_file_sent'         => 'لم يتم إرسال ملف',
            'upload.ajax.no_file_selected'     => 'لم يتم اختيار ملف',
            'upload.ajax.upload_error'         => 'خطأ في الرفع (الرمز {code})',
            'upload.ajax.too_large'            => 'حجم الملف أكبر من {max_mb} MB',
            'upload.ajax.mime_only'            => 'يسمح فقط بـ PDF أو JPG أو PNG',
            'upload.ajax.ext_only'             => 'امتداد ملف غير صالح (فقط pdf/jpg/jpeg/png)',
            'upload.ajax.cannot_save'          => 'تعذر حفظ الملف',
            'upload.ajax.unknown_action'       => 'إجراء غير معروف',
            'upload.ajax.server_error'         => 'خطأ في الخادم أثناء الرفع',
        
            // =========================
            // STEP 4/4: REVIEW (AR)
            // =========================
            'review.page_title' => 'الخطوة 4/4 – الملخص وتقديم الطلب',
        
            'review.h1'      => 'الخطوة 4/4 – الملخص وتقديم الطلب',
            'review.subhead' => 'يرجى التحقق من بياناتك. عند الضغط على «تقديم»، سيتم إرسال البيانات.',
        
            'review.readonly_alert' => 'تم إرسال هذا الطلب بالفعل. يمكن عرض البيانات فقط ولا يمكن تعديلها أو إرسالها مرة أخرى.',
        
            'review.info.p1' => 'عزيزتي الطالبة، عزيزي الطالب،',
            'review.info.p2' => 'عند النقر على <strong>«تقديم»</strong> تكون قد تقدّمت بطلب إلى <strong>BES اللغة والاندماج</strong> في إحدى مدارس BBS في أولدنبورغ.',
            'review.info.p3' => 'هذا ليس تسجيلًا نهائيًا بعد، بل هو <strong>طلب</strong>. بعد <strong>20.02.</strong> ستتلقى معلومات عمّا إذا/وفي أي BBS تم قبولك. يرجى التحقق بانتظام من صندوق البريد والبريد الإلكتروني. يرجى التأكد من أن اسمك ظاهر على صندوق البريد لكي تصلك الرسائل.',
            'review.info.p4' => 'مع خطاب القبول من المدرسة ستتلقى طلبًا لتقديم هذه الملفات (إذا لم ترفعها اليوم):',
            'review.info.li1' => 'آخر شهادة نصف سنوية',
        
            // Accordion titles
            'review.acc.personal' => 'البيانات الشخصية',
            'review.acc.school'   => 'المدرسة والاهتمامات',
            'review.acc.uploads'  => 'المستندات',
        
            // Labels: Personal
            'review.lbl.name'            => 'اسم العائلة',
            'review.lbl.vorname'         => 'الاسم الأول',
            'review.lbl.geschlecht'      => 'الجنس',
            'review.lbl.geburtsdatum'    => 'تاريخ الميلاد',
            'review.lbl.geburtsort'      => 'مكان / بلد الميلاد',
            'review.lbl.staatsang'       => 'الجنسية',
            'review.lbl.strasse'         => 'الشارع، رقم المنزل',
            'review.lbl.plz_ort'         => 'الرمز البريدي / المدينة',
            'review.lbl.telefon'         => 'الهاتف',
            'review.lbl.email'           => 'البريد الإلكتروني (للطالبة/الطالب، اختياري)',
            'review.lbl.weitere_angaben' => 'معلومات إضافية (مثل حالة الدعم)',
        
            'review.contacts.title'    => 'جهات اتصال إضافية',
            'review.contacts.optional' => 'اختياري',
            'review.contacts.none'     => '–',
        
            'review.contacts.th.role' => 'الدور',
            'review.contacts.th.name' => 'الاسم / المؤسسة',
            'review.contacts.th.tel'  => 'الهاتف',
            'review.contacts.th.mail' => 'البريد الإلكتروني',
            'review.contacts.note'    => 'ملاحظة:',
        
            // Labels: School
            'review.lbl.school_current' => 'المدرسة الحالية',
            'review.lbl.klassenlehrer'  => 'المعلّم/ة المسؤول/ة',
            'review.lbl.mail_lehrkraft' => 'بريد المعلّم/ة',
            'review.lbl.since'          => 'منذ متى في المدرسة',
            'review.lbl.years_de'       => 'عدد السنوات في ألمانيا',
            'review.lbl.family_lang'    => 'لغة الأسرة / اللغة الأولى',
            'review.lbl.de_level'       => 'مستوى الألمانية',
            'review.lbl.school_origin'  => 'المدرسة في بلد المنشأ',
            'review.lbl.years_origin'   => 'سنوات الدراسة في بلد المنشأ',
            'review.lbl.interests'      => 'الاهتمامات',
        
            // Uploads
            'review.lbl.zeugnis'        => 'الشهادة نصف السنوية',
            'review.lbl.lebenslauf'     => 'السيرة الذاتية (CV)',
            'review.lbl.later'          => 'تقديم لاحقًا',
            'review.badge.uploaded'     => 'تم الرفع',
            'review.badge.not_uploaded' => 'لم يتم الرفع',
            'review.yes'                => 'نعم',
            'review.no'                 => 'لا',
        
            // Buttons / Actions
            'review.btn.home'   => 'إلى الصفحة الرئيسية',
            'review.btn.newapp' => 'تقديم طلب آخر',
            'review.btn.back'   => 'رجوع',
            'review.btn.submit' => 'تقديم',
        
            // Errors / Flash / System texts
            'review.err.invalid_request'     => 'طلب غير صالح.',
            'review.flash.already_submitted' => 'تم إرسال هذا الطلب بالفعل ولا يمكن إرساله مرة أخرى أو تعديله.',
            'review.flash.no_token'          => 'لا يوجد رمز وصول صالح. يرجى بدء العملية من جديد.',
            'review.err.not_found_token'     => 'لم يتم العثور على طلب لهذا الرمز.',
            'review.flash.submit_error'      => 'حدث خطأ أثناء الإرسال. يرجى المحاولة لاحقًا.',
        
            // Gender fallback
            'review.gender.m' => 'ذكر',
            'review.gender.w' => 'أنثى',
            'review.gender.d' => 'متنوع',
        
            // Fallback
            'review.value.empty' => '–',
        
            // =========================
            // STATUS (AR)
            // =========================
            'status.hdr_title'   => 'تم حفظ الطلب بنجاح',
            'status.hdr_message' => 'تم إرسال طلبك.',
        
            'status.h1' => 'تم حفظ طلبك بنجاح.',
        
            'status.success.title' => 'شكرًا لك!',
            'status.success.body'  => 'تم إرسال طلبك وسيتم معالجته الآن.',
        
            'status.info.title' => 'ملاحظة مهمة',
            'status.info.body'  => '<em>[عنصر نائب: سيتم توفير النص من العميل لاحقًا]</em>',
        
            'status.btn.pdf'    => 'تحميل / طباعة PDF',
            'status.btn.newapp' => 'بدء طلب جديد',
            'status.btn.home'   => 'إلى الصفحة الرئيسية',
        
            'status.ref' => 'المرجع: الطلب رقم #{id}',
        
            'status.err.invalid_request' => 'طلب غير صالح.',
        
            // =========================
            // PDF (AR)
            // =========================
            'pdf.err.autoload_missing' => 'لم يتم العثور على Composer Autoload. يرجى تشغيل "composer install".',
            'pdf.err.no_token'         => 'لا يوجد رمز وصول صالح. يرجى بدء العملية من جديد.',
            'pdf.err.not_found'        => 'لم يتم العثور على الطلب.',
            'pdf.err.server'           => 'خطأ في الخادم أثناء إنشاء ملف PDF.',
        
            'pdf.header_title' => 'الطلب – الملخص',
            'pdf.footer_auto'  => 'مستند مُنشأ تلقائيًا',
            'pdf.footer_page'  => 'الصفحة {cur} / {max}',
        
            'pdf.meta.ref'        => 'الطلب رقم #{id}',
            'pdf.meta.created_at' => 'تاريخ الإنشاء',
            'pdf.meta.status'     => 'الحالة',
        
            'pdf.top.title'        => 'نظرة سريعة',
            'pdf.top.name'         => 'الاسم',
            'pdf.top.reference'    => 'المرجع',
            'pdf.top.generated'    => 'تاريخ الإنشاء',
            'pdf.top.hint'         => 'ملاحظة',
            'pdf.top.keep_note'    => 'يرجى الاحتفاظ بهذا المستند ضمن أوراقك.',
            'pdf.hint_placeholder' => '[عنصر نائب: سيتم توفير النص من العميل لاحقًا]',
        
            'pdf.sec1.title' => '1) البيانات الشخصية',
            'pdf.sec2.title' => '2) بيانات اتصال إضافية',
            'pdf.sec3.title' => '3) المدرسة والاهتمامات',
            'pdf.sec4.title' => '4) المستندات',
        
            'pdf.lbl.name'           => 'اسم العائلة',
            'pdf.lbl.vorname'        => 'الاسم الأول',
            'pdf.lbl.gender'         => 'الجنس',
            'pdf.lbl.dob'            => 'تاريخ الميلاد',
            'pdf.lbl.birthplace'     => 'مكان/بلد الميلاد',
            'pdf.lbl.nationality'    => 'الجنسية',
            'pdf.lbl.address'        => 'العنوان',
            'pdf.lbl.phone'          => 'الهاتف',
            'pdf.lbl.email_optional' => 'البريد الإلكتروني (اختياري)',
            'pdf.lbl.more'           => 'معلومات إضافية',
        
            'pdf.lbl.school_current' => 'المدرسة الحالية',
            'pdf.lbl.teacher'        => 'المعلّم/ة',
            'pdf.lbl.teacher_email'  => 'بريد المعلّم/ة',
            'pdf.lbl.since_school'   => 'منذ متى في المدرسة',
            'pdf.lbl.years_in_de'    => 'منذ متى في ألمانيا',
            'pdf.lbl.family_lang'    => 'لغة الأسرة',
            'pdf.lbl.de_level'       => 'مستوى الألمانية',
            'pdf.lbl.school_origin'  => 'المدرسة في بلد المنشأ',
            'pdf.lbl.years_origin'   => 'سنوات الدراسة في بلد المنشأ',
            'pdf.lbl.interests'      => 'الاهتمامات',
        
            'pdf.lbl.report'       => 'الشهادة نصف السنوية',
            'pdf.lbl.cv'           => 'السيرة الذاتية (CV)',
            'pdf.lbl.report_later' => 'تقديم الشهادة لاحقًا',
        
            'pdf.uploaded'     => 'تم الرفع',
            'pdf.not_uploaded' => 'لم يتم الرفع',
        
            'pdf.contacts.none'    => '–',
            'pdf.contacts.th.role' => 'الدور',
            'pdf.contacts.th.name' => 'الاسم/المؤسسة',
            'pdf.contacts.th.tel'  => 'الهاتف',
            'pdf.contacts.th.mail' => 'البريد الإلكتروني',
            'pdf.contacts.th.note' => 'ملاحظة',
        
            'pdf.gender.m' => 'ذكر',
            'pdf.gender.w' => 'أنثى',
            'pdf.gender.d' => 'متنوع',
        
            'pdf.yes' => 'نعم',
            'pdf.no'  => 'لا',
        
            'pdf.sec4.note' => 'هذا المستند هو ملخص مُنشأ تلقائيًا للبيانات المُدخلة.',
            'pdf.filename_prefix' => 'الطلب',
        
            // =========================
            // ACCESS_CREATE (AR)
            // =========================
            'access_create.title'         => 'المتابعة مع البريد الإلكتروني',
            'access_create.lead'          => 'يمكنك تسجيل الدخول باستخدام وصولك أو إنشاء وصول جديد.',
            'access_create.tabs_login'    => 'تسجيل الدخول',
            'access_create.tabs_register' => 'إنشاء وصول جديد',
        
            'access_create.login_title' => 'تسجيل الدخول (وصول موجود)',
            'access_create.login_text'  => 'يرجى إدخال بريدك الإلكتروني وكلمة المرور.',
            'access_create.email_label' => 'البريد الإلكتروني',
            'access_create.pass_label'  => 'كلمة المرور',
            'access_create.login_btn'   => 'تسجيل الدخول',
            'access_create.login_err'   => 'البريد الإلكتروني/كلمة المرور غير صحيحة أو لم يتم التحقق من الوصول.',
        
            'access_create.reg_title'     => 'إنشاء وصول جديد',
            'access_create.reg_text'      => 'سنرسل لك رمز تأكيد من 6 أرقام. بعد التأكيد بنجاح ستتلقى كلمة المرور عبر البريد الإلكتروني.',
            'access_create.consent_label' => 'أوافق على استخدام بريدي الإلكتروني لعملية التسجيل.',
            'access_create.send_btn'      => 'إرسال الرمز',
            'access_create.code_label'    => 'رمز التأكيد',
            'access_create.verify_btn'    => 'تحقق من الرمز',
            'access_create.resend'        => 'إرسال الرمز مرة أخرى',
        
            'access_create.info_sent'    => 'لقد أرسلنا لك رمزًا. يرجى أيضًا التحقق من مجلد البريد العشوائي (Spam).',
            'access_create.ok_verified'  => 'تم تأكيد البريد الإلكتروني. تم إرسال كلمة المرور. يمكنك الآن تسجيل الدخول.',
            'access_create.email_in_use' => 'هذا البريد الإلكتروني لديه وصول بالفعل. يرجى تسجيل الدخول.',
        
            'access_create.error_email'     => 'يرجى إدخال بريد إلكتروني صالح.',
            'access_create.error_consent'   => 'يرجى الموافقة على استخدام بريدك الإلكتروني.',
            'access_create.error_rate'      => 'محاولات كثيرة جدًا. يرجى الانتظار قليلًا ثم المحاولة مرة أخرى.',
            'access_create.error_code'      => 'الرمز غير صالح أو انتهت صلاحيته.',
            'access_create.error_resend'    => 'لا يمكن إعادة الإرسال. يرجى البدء من جديد.',
            'access_create.error_mail_send' => 'فشل إرسال البريد. يرجى المحاولة لاحقًا.',
            'access_create.error_db'        => 'خطأ في الخادم (قاعدة البيانات).',
        
            'access_create.back'   => 'رجوع',
            'access_create.cancel' => 'إلغاء',
        
            'access_create.mail_subject' => 'كلمة المرور الخاصة بك للتسجيل الإلكتروني',
            'access_create.mail_body'    => "تم إنشاء وصولك.\n\nالبريد الإلكتروني: {email}\nكلمة المرور: {password}\n\nيرجى حفظ كلمة المرور في مكان آمن.",
        
            // =========================
            // ACCESS_PORTAL (AR)
            // =========================
            'access_portal.title'    => 'طلباتي',
            'access_portal.lead'     => 'هنا سترى طلباتك. يمكنك متابعة طلب موجود أو بدء طلب جديد.',
            'access_portal.max_hint' => '{email} · الحد الأقصى {max} طلب/طلبات',
        
            'access_portal.btn_new'    => 'بدء طلب جديد',
            'access_portal.btn_open'   => 'فتح',
            'access_portal.btn_logout' => 'تسجيل الخروج',
        
            'access_portal.th_ref'     => 'المعرّف',
            'access_portal.th_status'  => 'الحالة',
            'access_portal.th_created' => 'تاريخ الإنشاء',
            'access_portal.th_updated' => 'آخر تحديث',
            'access_portal.th_token'   => 'Token',
            'access_portal.th_action'  => 'إجراء',
        
            'access_portal.status_draft'     => 'مسودة',
            'access_portal.status_submitted' => 'تم الإرسال',
            'access_portal.status_withdrawn' => 'تم السحب',
        
            'access_portal.limit_reached' => 'لقد بلغت الحد الأقصى لعدد الطلبات لهذا البريد الإلكتروني.',
            'access_portal.no_apps'       => 'لا توجد طلبات بعد.',
            'access_portal.err_generic'   => 'حدث خطأ.',
            'access_portal.csrf_invalid'  => 'طلب غير صالح.',
        
            // =========================
            // ACCESS_LOGIN (AR)
            // =========================
            'access_login.title'            => 'الوصول إلى الطلب/الطلبات',
            'access_login.lead'             => 'هنا يمكنك فتح طلب بدأته أو أرسلته سابقًا.',
        
            'access_login.login_box_title'  => 'تسجيل الدخول باستخدام Access-Token',
            'access_login.login_box_text'   => 'يرجى إدخال رمز الوصول الشخصي (Access-Token) وتاريخ الميلاد.',
        
            'access_login.token_label'      => 'Access-Token',
            'access_login.dob_label'        => 'تاريخ الميلاد (TT.MM.YYYY)',
        
            'access_login.login_btn'        => 'وصول',
            'access_login.back'             => 'العودة إلى الصفحة الرئيسية',
        
            'access_login.login_ok'         => 'تم تحميل الطلب.',
            'access_login.login_error'      => 'لم يتم العثور على تطابق بين Access-Token وتاريخ الميلاد.',
            'access_login.login_error_token'=> 'يرجى إدخال Access-Token صالح.',
            'access_login.login_error_dob'  => 'يرجى إدخال تاريخ ميلاد صالح بصيغة TT.MM.YYYY.',
        
            'access_login.csrf_invalid'     => 'طلب غير صالح.',
            'access_login.internal_error'   => 'خطأ داخلي.',
            'access_login.load_error'       => 'حدث خطأ أثناء تحميل الطلب.',
        
            // =========================
            // PRIVACY (AR)
            // =========================
            'privacy.title' => 'حماية البيانات',
            'privacy.h1'    => 'إشعار حماية البيانات لطلب التسجيل الإلكتروني «BES اللغة والاندماج»',
        
            'privacy.s1_title'     => '1. الجهة المسؤولة',
            'privacy.s1_body_html' => '<strong>مدينة أولدنبورغ / المدارس المهنية</strong><br>(يرجى إدخال اسم الجهة/المدرسة بالتفصيل والعنوان والهاتف والبريد الإلكتروني)',
        
            'privacy.s2_title'     => '2. مسؤول/ة حماية البيانات',
            'privacy.s2_body_html' => '(يرجى إدخال بيانات التواصل لمسؤول/ة حماية البيانات)',
        
            'privacy.s3_title' => '3. أغراض المعالجة',
            'privacy.s3_li1'   => 'استلام ومعالجة طلبك للالتحاق بصف اللغة («BES اللغة والاندماج»)',
            'privacy.s3_li2'   => 'التواصل معك (استفسارات، إبلاغ بقرار القبول)',
            'privacy.s3_li3'   => 'التخطيط التنظيمي المدرسي (التوزيع على BBS)',
        
            'privacy.s4_title' => '4. الأسس القانونية',
            'privacy.s4_li1'   => 'المادة 6(1)(هـ) من GDPR بالاقتران مع أحكام قانون المدارس في ولاية سكسونيا السفلى',
            'privacy.s4_li2'   => 'المادة 6(1)(ج) من GDPR (الوفاء بالالتزامات القانونية)',
            'privacy.s4_li3'   => 'المادة 6(1)(أ) من GDPR (الموافقة)، بقدر ما يتم تقديم بيانات/مرفقات طوعية',
        
            'privacy.s5_title' => '5. فئات البيانات الشخصية',
            'privacy.s5_li1'   => 'البيانات الأساسية (الاسم، تاريخ الميلاد، الجنسية، العنوان، بيانات الاتصال)',
            'privacy.s5_li2'   => 'معلومات مدرسية (المدرسة الحالية، مستوى اللغة، الاهتمامات)',
            'privacy.s5_li3'   => 'مستندات اختيارية (مثل آخر شهادة نصف سنوية)',
            'privacy.s5_li4'   => 'جهات اتصال إضافية (الوالدان/المشرفون/المؤسسات)',
        
            'privacy.s6_title' => '6. المستلمون',
            'privacy.s6_body'  => 'ضمن نطاق اختصاص مدينة أولدنبورغ والمدارس المهنية. لا يتم إرسال البيانات لأطراف ثالثة إلا إذا كان ذلك مطلوبًا قانونيًا (مثل السلطات المدرسية) أو بموافقتك.',
        
            'privacy.s7_title' => '7. نقل البيانات إلى دول ثالثة',
            'privacy.s7_body'  => 'لا يتم نقل البيانات إلى دول ثالثة.',
        
            'privacy.s8_title' => '8. مدة التخزين',
            'privacy.s8_body'  => 'سيتم تخزين بياناتك طوال مدة إجراءات الطلب/القبول ووفقًا لفترات الاحتفاظ القانونية ثم حذفها.',
        
            'privacy.s9_title' => '9. حقوقك',
            'privacy.s9_li1'   => 'الاطلاع (المادة 15)، التصحيح (16)، الحذف (17)، تقييد المعالجة (18)',
            'privacy.s9_li2'   => 'الاعتراض (21) على المعالجات للمصلحة العامة',
            'privacy.s9_li3'   => 'سحب الموافقات (المادة 7(3)) بأثر مستقبلي',
            'privacy.s9_li4'   => 'حق تقديم شكوى لدى جهة الرقابة: مفوض/ة حماية البيانات في سكسونيا السفلى',
        
            'privacy.s10_title' => '10. الاستضافة والسجلات',
            'privacy.s10_body'  => 'يتم تشغيل التطبيق على خوادم المدينة أو في مركز بيانات بلدي. تتم معالجة البيانات التقنية الضرورية فقط (مثل سجلات الخادم للبحث عن الأخطاء). لا يتم تضمين CDN خارجي. يتم تعيين ملف تعريف ارتباط لغوي فقط.',
        
            'privacy.s11_title'    => '11. ملفات تعريف الارتباط',
            'privacy.s11_li1_html' => '<strong>lang</strong> – يحفظ اللغة المختارة (صالحة 12 شهرًا). الهدف: سهولة الاستخدام.',
            'privacy.s11_li2'      => 'جلسة PHP – ضرورية تقنيًا لسير النموذج، وتُحذف عند انتهاء الجلسة.',
        
            'privacy.stand_label' => 'التحديث',
            'privacy.stand_hint'  => 'يرجى التحقق بانتظام من وجود أي تغييرات.',
            'privacy.back_home'   => 'إلى الصفحة الرئيسية',
        
            // =========================
            // IMPRINT (AR)
            // =========================
            'imprint.title' => 'بيانات النشر',
            'imprint.h1'    => 'بيانات النشر',
        
            'imprint.s1_title'     => 'مزود الخدمة',
            'imprint.s1_body_html' => '<strong>مدينة ***</strong><br>المدارس المهنية<br>(يرجى إدخال العنوان بالتفصيل)<br>الهاتف: (يرجى الإضافة)<br>البريد الإلكتروني: (يرجى الإضافة)',
        
            'imprint.s2_title'     => 'الممثل القانوني',
            'imprint.s2_body_html' => '(مثلًا: عمدة المدينة ****<br>أو إدارة/قيادة BBS المعنية)',
        
            'imprint.s3_title'     => 'المسؤول عن المحتوى وفق § 18 الفقرة 2 من MStV',
            'imprint.s3_body_html' => '(الاسم، الوظيفة، التواصل، مثلًا: إدارة BBS أو المكتب الإعلامي)',
        
            'imprint.s4_title'     => 'رقم التعريف الضريبي (VAT)',
            'imprint.s4_body_html' => '(إن وُجد؛ وإلا يمكن حذف هذا القسم)',
        
            'imprint.s5_title' => 'جهة الإشراف',
            'imprint.s5_body'  => '(الجهة البلدية/المدرسية المختصة، مثلًا: القسم الإقليمي لهيئة التعليم الحكومية)',
        
            'imprint.s6_title' => 'المسؤولية عن المحتوى',
            'imprint.s6_body'  => 'تم إعداد محتوى صفحاتنا بعناية كبيرة. ومع ذلك لا يمكننا ضمان صحة المحتوى أو اكتماله أو حداثته. وبصفتنا جهة عامة، نتحمل مسؤولية محتوياتنا وفق § 7 الفقرة 1 من TMG.',
        
            'imprint.s7_title' => 'المسؤولية عن الروابط',
            'imprint.s7_body'  => 'لا يتضمن عرضنا محتوى خارجيًا ينقل بيانات شخصية إلى أطراف ثالثة. إذا قمنا بالربط مع عروض معلومات لجهات عامة أخرى، فلا نتحمل مسؤولية محتواها.',
        
            'imprint.s8_title' => 'حقوق النشر',
            'imprint.s8_body'  => 'تخضع المحتويات والأعمال التي أنشأتها مدينة أولدنبورغ لحقوق النشر الألمانية. يتم تمييز مساهمات الأطراف الثالثة على أنها كذلك. يتطلب النسخ أو المعالجة أو التوزيع أو أي استخدام خارج حدود حقوق النشر موافقة خطية من مدينة أولدنبورغ أو صاحب الحق.',
        
            'imprint.stand_label' => 'التحديث',
            'imprint.stand_hint'  => 'تنطبق هذه البيانات على النموذج الإلكتروني «BES اللغة والاندماج».',
            'imprint.back_home'   => 'إلى الصفحة الرئيسية',
        
            // =========================
            // VERIFY_EMAIL (AR)
            // =========================
            'verify_email.title' => 'تأكيد البريد الإلكتروني',
            'verify_email.h1'    => 'تأكيد البريد الإلكتروني',
        
            'verify_email.lead_sent'    => 'لقد أرسلنا رمز تأكيد إلى {email}.',
            'verify_email.lead_generic' => 'يرجى إدخال رمز التأكيد الذي وصل عبر البريد الإلكتروني. إذا لم تجد البريد، يمكنك إعادة إرسال الرمز إلى عنوانك.',
        
            'verify_email.code_label'  => 'رمز التأكيد (6 أرقام)',
            'verify_email.email_label' => 'عنوان بريدك الإلكتروني',
        
            'verify_email.btn_verify' => 'تأكيد',
            'verify_email.btn_resend' => 'إرسال الرمز مرة أخرى',
            'verify_email.hint_spam'  => 'يرجى أيضًا التحقق من مجلد البريد العشوائي (Spam).',
        
            'verify_email.back' => 'رجوع',
        
            'verify_email.csrf_invalid' => 'طلب غير صالح.',
            'verify_email.ok_verified'  => 'تم تأكيد البريد الإلكتروني بنجاح.',
            'verify_email.ok_sent'      => 'تم إرسال رمز جديد إلى {email}.',
        
            'verify_email.warn_cooldown'     => 'يرجى الانتظار قليلًا قبل طلب الرمز مرة أخرى.',
            'verify_email.error_send'        => 'فشل الإرسال. يرجى المحاولة لاحقًا.',
            'verify_email.error_email'       => 'يرجى إدخال بريد إلكتروني صالح.',
            'verify_email.error_no_session'  => 'لم يتم العثور على عملية تحقق نشطة. يرجى طلب رمز جديد.',
            'verify_email.error_expired'     => 'الرمز غير صالح أو انتهت صلاحيته.',
            'verify_email.error_invalid'     => 'الرمز غير صالح أو انتهت صلاحيته.',
            'verify_email.error_code_format' => 'يرجى إدخال رمز صالح مكوّن من 6 أرقام.',
            'verify_email.error_rate'        => 'محاولات كثيرة جدًا. يرجى طلب رمز جديد.',
        
            // =========================
            // VALIDATION (AR) – global
            // =========================
            'val.required' => 'مطلوب.',
            'val.only_letters' => 'يرجى استخدام أحرف فقط.',
            'val.gender_choose' => 'يرجى اختيار الجنس.',
            'val.date_format' => 'يوم.شهر.سنة',
            'val.date_invalid' => 'تاريخ غير صالح.',
            'val.plz_whitelist' => 'فقط الرموز البريدية في أولدنبورغ (26121–26135).',
            'val.phone_vorwahl' => 'رمز المنطقة: 2–6 أرقام.',
            'val.phone_nummer' => 'الرقم: 3–12 رقمًا.',
            'val.email_invalid' => 'بريد إلكتروني غير صالح.',
            'val.email_no_iserv' => 'يرجى استخدام بريد خاص (ليس IServ).',
            'val.max_1500' => 'بحد أقصى 1500 حرف.',
            'val.kontakt_row_name_missing' => 'الاسم/الوصف مفقود',
            'val.kontakt_row_tel_or_mail'  => 'أدخل الهاتف أو البريد الإلكتروني',
            'val.kontakt_row_mail_invalid' => 'بريد إلكتروني غير صالح',
            'val.kontakt_row_tel_invalid'  => 'هاتف غير صالح',
        ],

        // =======================
        // RU: in 'ru' => [ ... ] einfügen (komplett)
        // =======================
        'ru' => [
        
            // =======================
            // STEP Start: Index (RU)
            // =======================
            'index.title' => 'Добро пожаловать на онлайн-регистрацию – языковые классы',
            'index.lead'  => 'Этот сервис предназначен для недавно прибывших людей в Ольденбург. Форма помогает нам связаться с вами и подобрать подходящие предложения.',
            'index.bullets' => [
                'Пожалуйста, подготовьте контактные данные и документы, удостоверяющие личность (если есть).',
                'Анкету можно заполнить на нескольких языках.',
                'Ваши данные обрабатываются конфиденциально в соответствии с GDPR.',
            ],
            'index.info_p' => [
                'Уважаемая ученица, уважаемый ученик!',
                'Этой заявкой вы подаёте заявление на место в языковом учебном классе «BES Язык и интеграция» в одной из профессиональных школ (BBS) Ольденбурга. Вы подаёте заявление не в конкретную BBS. После 20 февраля вам сообщат, какая школа примет вас в языковой класс.',
                'Принятие возможно только при следующих условиях:',
            ],
            'index.info_bullets' => [
                'Вам необходима интенсивная поддержка по немецкому языку (уровень ниже B1).',
                'К началу следующего учебного года вы находитесь в Германии не более 3 лет.',
                'На 30 сентября текущего года вам не меньше 16 и не больше 18 лет.',
                'В следующем учебном году вы подлежите обязательному школьному обучению.',
            ],
            'index.access_title' => 'Защита данных и доступ',
            'index.access_intro' => 'Вы можете продолжить с e-mail или без него. Доступ к сохранённым заявлениям возможен только с личным кодом доступа (Token) и датой рождения.',
            'index.access_points' => [
                '<strong>С e-mail:</strong> вы получите код подтверждения и сможете создавать несколько заявлений и открывать их позже.',
                '<strong>Без e-mail:</strong> вы получите личный код доступа (Access-Token). Пожалуйста, запишите/сфотографируйте его — без подтверждённого e-mail восстановление невозможно.',
            ],
        
            'index.btn_noemail' => 'Продолжить без e-mail',
            'index.btn_create'  => 'Продолжить с e-mail',
            'index.btn_load'    => 'Доступ к заявлению/заявлениям',
            'index.lang_label'  => 'Язык:',
        
            // =======================
            // STEP 1/4: PERSONAL (RU)
            // =======================
            'personal.page_title' => 'Шаг 1/4 – Личные данные',
            'personal.h1' => 'Шаг 1/4 – Личные данные',
            'personal.required_hint' => 'Обязательные поля выделены синей рамкой.',
            'personal.form_error_hint' => 'Пожалуйста, проверьте отмеченные поля.',
        
            'personal.alert_email_title' => 'Вход по e-mail активен:',
            'personal.alert_email_line1' => 'Вы вошли с адресом e-mail {email}.',
            'personal.alert_email_line2' => 'Этот e-mail используется только для кода доступа (Access-Token) и для повторного поиска вашего заявления.',
            'personal.alert_email_line3' => 'Ниже вы можете указать e-mail ученицы/ученика (если есть).',
        
            'personal.alert_noemail_title' => 'Примечание (без e-mail):',
            'personal.alert_noemail_body' => 'Пожалуйста, запишите или сфотографируйте ваш код доступа (Access-Token), который будет показан после сохранения этой страницы. Без подтверждённого e-mail восстановление возможно только по токену + дате рождения.',
        
            'personal.label.name' => 'Фамилия',
            'personal.label.vorname' => 'Имя',
            'personal.label.geschlecht' => 'Пол',
            'personal.gender.m' => 'мужской',
            'personal.gender.w' => 'женский',
            'personal.gender.d' => 'другой',
        
            'personal.label.geburtsdatum' => 'Дата рождения',
            'personal.label.geburtsdatum_hint' => '(ДД.ММ.ГГГГ)',
            'personal.placeholder.geburtsdatum' => 'ДД.ММ.ГГГГ',
        
            'personal.age_hint' => 'Примечание: если на 30.09.{year} вам меньше 16 или больше 18 лет, вас нельзя принять в языковой класс BBS. Пожалуйста, подайте заявление в другой класс здесь:',
            'personal.age_redirect_msg' => "Примечание: если на 30.09.{year} вам меньше 16 или больше 18 лет, вас нельзя принять в языковой класс BBS. Пожалуйста, подайте заявление в другой класс BBS здесь:\n{url}",
        
            'personal.label.geburtsort_land' => 'Место / страна рождения',
            'personal.label.staatsang' => 'Гражданство',
        
            'personal.label.strasse' => 'Улица, дом',
            'personal.label.plz' => 'Почтовый индекс',
            'personal.plz_choose' => '– пожалуйста, выберите –',
            'personal.plz_hint' => 'Только Ольденбург (Oldb).',
            'personal.label.wohnort' => 'Город',
        
            'personal.label.telefon' => 'Номер телефона',
            'personal.label.telefon_vorwahl_help' => 'Код города/оператора с/без 0',
            'personal.label.telefon_nummer_help' => 'Номер',
            'personal.placeholder.telefon_vorwahl' => '(0)441',
            'personal.placeholder.telefon_nummer' => '123456',
        
            'personal.label.email' => 'E-mail ученицы/ученика (необязательно, не адрес IServ)',
            'personal.email_help' => 'Этот e-mail принадлежит ученице/ученику (если есть) и не зависит от e-mail для кода доступа.',
            'personal.placeholder.email' => 'name@example.org',
        
            'personal.label.kontakte' => 'Дополнительные контактные данные',
            'personal.kontakte_hint' => '(например, родители, опекун, учреждение)',
            'personal.kontakte_error' => 'Пожалуйста, проверьте дополнительные контакты.',
            'personal.kontakte_add' => '+ Добавить контакт',
            'personal.kontakte_remove_title' => 'Удалить контакт',
        
            'personal.table.role' => 'Роль',
            'personal.table.name' => 'Имя / учреждение',
            'personal.table.tel'  => 'Телефон',
            'personal.table.mail' => 'E-mail',
            'personal.table.note_header' => 'Примечание',
            'personal.placeholder.kontakt_name' => 'Имя или обозначение',
            'personal.placeholder.kontakt_tel'  => '+49 …',
            'personal.placeholder.kontakt_note' => 'например, доступность, язык, примечания',
        
            'personal.contact_role.none' => '–',
            'personal.contact_role.mutter' => 'Мать',
            'personal.contact_role.vater' => 'Отец',
            'personal.contact_role.elternteil' => 'Родитель',
            'personal.contact_role.betreuer' => 'Опекун/куратор',
            'personal.contact_role.einrichtung' => 'Учреждение',
            'personal.contact_role.sonstiges' => 'Другое',
        
            'personal.label.weitere_angaben' => 'Дополнительная информация (например, статус поддержки):',
            'personal.placeholder.weitere_angaben' => 'Здесь вы можете указать, например, особые потребности в поддержке, специальные образовательные потребности или другие примечания.',
            'personal.weitere_angaben_help' => 'Необязательно. Максимум 1500 символов.',
            'personal.btn.cancel' => 'Отмена',
            'personal.btn.next' => 'Далее',
        
            'personal.dsgvo_text_prefix' => 'Я прочитал(а)',
            'personal.dsgvo_link_text' => 'уведомление о защите данных',
            'personal.dsgvo_text_suffix' => 'и согласен/согласна.',
        
            // =====================
            // STEP 2/4: SCHOOL (RU)
            // =====================
            'school.page_title' => 'Шаг 2/4 – Школа и интересы',
            'school.h1' => 'Шаг 2/4 – Школа и интересы',
            'school.required_hint' => 'Обязательные поля выделены синей рамкой.',
            'school.form_error_hint' => 'Пожалуйста, проверьте отмеченные поля.',
        
            'school.top_hint_title' => 'Примечание:',
            'school.top_hint_body'  => 'Если вы находитесь в Германии <u>более 3 лет</u> или уже говорите по-немецки на уровне <u>B1</u> или выше, вас нельзя принять в языковой класс BBS. Пожалуйста, подайте обычную заявку в BBS здесь:',
            'school.bbs_link_label' => 'https://bbs-ol.de/',
        
            'school.autohints_title' => 'Подсказки',
        
            'school.label.schule_aktuell' => 'Текущая школа',
            'school.search_placeholder'   => 'Искать школу… (название, улица, индекс)',
            'school.select_choose'        => 'Пожалуйста, выберите…',
            'school.option_other'         => 'Другая / нет в списке',
            'school.other_placeholder'    => 'Название школы, улица, город (свободный текст)',
        
            'school.label.teacher'        => 'ответственный/ая учитель/учительница',
            'school.label.teacher_mail'   => 'E-mail ответственного/ой учителя/учительницы',
        
            'school.label.herkunft'       => 'Посещали ли вы школу в стране происхождения?',
            'school.yes'                  => 'Да',
            'school.no'                   => 'Нет',
            'school.label.herkunft_years' => 'Если да: сколько лет?',
        
            'school.label.since'          => 'С какого времени вы учитесь в школе в Германии?',
            'school.since_month'          => 'Месяц (MM)',
            'school.since_year_ph'        => 'Год (YYYY)',
            'school.since_help'           => 'Укажите месяц+год <strong>или</strong> используйте поле свободного текста.',
            'school.label.since_text'     => 'Альтернатива: свободный текст (например, «с осени 2023»)',
        
            'school.label.years_in_de'    => 'Сколько лет вы находитесь в Германии?',
            'school.years_in_de_help'     => 'Примечание: &gt; 3 лет → пожалуйста, обычная заявка через {link}.',
        
            'school.label.family_lang'    => 'Семейный язык / родной язык',
        
            'school.label.level'          => 'Какой уровень немецкого?',
            'school.level_choose'         => 'Пожалуйста, выберите…',
            'school.level_help'           => 'Примечание: B1 или выше → обычная заявка через {link}.',
        
            'school.label.interests'      => 'Интересы (минимум 1, максимум 2)',
        
            'school.btn.back'             => 'Назад',
            'school.btn.next'             => 'Далее',
        
            // ---------------------
            // Validation / Errors
            // ---------------------
            'val.school_free_required' => 'Пожалуйста, укажите название школы (свободный текст).',
            'val.school_invalid'       => 'Пожалуйста, выберите действительную школу или «Другая / нет в списке».',
        
            'val.since_required'       => 'Пожалуйста, укажите месяц+год или свободный текст.',
            'val.month_invalid'        => 'Месяц должен быть 01–12.',
            'val.year_invalid'         => 'Пожалуйста, укажите корректный год.',
            'val.number_required'      => 'Пожалуйста, укажите число.',
            'val.choose'               => 'Пожалуйста, выберите.',
            'val.herkunft_years'       => 'Пожалуйста, укажите количество лет.',
        
            'val.level_invalid'        => 'Недействительный выбор.',
        
            'val.interests_min1'       => 'Пожалуйста, выберите минимум одну область.',
            'val.interests_max2'       => 'Пожалуйста, выберите максимум две области.',
        
            // ---------------------
            // JS Live hints
            // ---------------------
            'js.hint_years_gt3'  => 'Примечание: вы находитесь в Германии более 3 лет. Пожалуйста, подайте заявку через {link}.',
            'js.hint_level_b1p'  => 'Примечание: при уровне B1 или выше подайте обычную заявку через {link}.',
        
            // =========================
            // STEP 3/4: UPLOAD (RU)
            // =========================
            'upload.page_title' => 'Шаг 3/4 – Документы (необязательно)',
            'upload.h1'         => 'Шаг 3/4 – Документы (необязательно)',
        
            'upload.intro'      => 'Здесь вы можете загрузить документы. Допустимые форматы: <strong>PDF</strong>, <strong>JPG</strong> и <strong>PNG</strong>. Максимальный размер файла — <strong>{max_mb} MB</strong> на файл.',
        
            'upload.type.zeugnis'    => 'Последний полугодовой табель/аттестат',
            'upload.type.lebenslauf' => 'Резюме (CV)',
            'upload.type_hint'       => '(PDF/JPG/PNG, макс. {max_mb} MB)',
        
            'upload.btn.remove' => 'Удалить',
            'upload.btn.back'   => 'Назад',
            'upload.btn.next'   => 'Далее',
        
            'upload.saved_prefix' => 'Уже сохранено:',
            'upload.empty'        => 'Файл ещё не загружен.',
            'upload.saved_html'   => 'Уже сохранено: <strong>{filename}</strong>, {size_kb} KB, загружено {uploaded_at}',
        
            'upload.checkbox.zeugnis_spaeter' => 'Я предоставлю полугодовой табель после получения подтверждения (зачисления).',
        
            'upload.flash.no_access' => 'Действительный доступ не найден. Пожалуйста, начните регистрацию заново.',
            'upload.flash.saved'     => 'Информация о загрузках сохранена.',
        
            'upload.js.uploading'          => 'Выполняется загрузка…',
            'upload.js.unexpected'         => 'Неожиданный ответ сервера.',
            'upload.js.upload_failed'      => 'Загрузка не удалась.',
            'upload.js.delete_confirm'     => 'Действительно удалить загруженный файл?',
            'upload.js.delete_failed'      => 'Удаление не удалось.',
            'upload.js.remove_confirm_btn' => 'Удалить файл?',
        
            // AJAX / Errors
            'upload.ajax.invalid_method'       => 'Недопустимый метод',
            'upload.ajax.invalid_csrf'         => 'Недействительный CSRF-токен',
            'upload.ajax.no_access'            => 'Нет действительного доступа.',
            'upload.ajax.invalid_field'        => 'Недопустимое поле',
            'upload.ajax.no_file_sent'         => 'Файл не отправлен',
            'upload.ajax.no_file_selected'     => 'Файл не выбран',
            'upload.ajax.upload_error'         => 'Ошибка загрузки (код {code})',
            'upload.ajax.too_large'            => 'Файл больше {max_mb} MB',
            'upload.ajax.mime_only'            => 'Разрешены только PDF, JPG или PNG',
            'upload.ajax.ext_only'             => 'Недопустимое расширение (только pdf/jpg/jpeg/png)',
            'upload.ajax.cannot_save'          => 'Не удалось сохранить файл',
            'upload.ajax.unknown_action'       => 'Неизвестное действие',
            'upload.ajax.server_error'         => 'Ошибка сервера при загрузке',
        
            // =========================
            // STEP 4/4: REVIEW (RU)
            // =========================
            'review.page_title' => 'Шаг 4/4 – Проверка и подача заявления',
        
            'review.h1'      => 'Шаг 4/4 – Проверка и подача заявления',
            'review.subhead' => 'Пожалуйста, проверьте введённые данные. Нажав «Подать», вы отправите заявление.',
        
            'review.readonly_alert' => 'Это заявление уже отправлено. Данные можно только просматривать — редактирование и повторная отправка невозможны.',
        
            'review.info.p1' => 'Уважаемая ученица, уважаемый ученик!',
            'review.info.p2' => 'нажав <strong>«подать»</strong>, вы подали заявление на <strong>BES Язык и интеграция</strong> в одной из BBS Ольденбурга.',
            'review.info.p3' => 'Это ещё не окончательная регистрация, а <strong>заявление</strong>. После <strong>20.02.</strong> вы получите информацию, будет ли/в какой BBS вы приняты. Пожалуйста, регулярно проверяйте почтовый ящик и e-mail. Убедитесь, что ваше имя видно на почтовом ящике, чтобы вы могли получать письма.',
            'review.info.p4' => 'Вместе с подтверждением от школы вы получите просьбу предоставить эти документы (если вы ещё не загрузили их сегодня):',
            'review.info.li1' => 'последний полугодовой табель/аттестат',
        
            // Accordion titles
            'review.acc.personal' => 'Личные данные',
            'review.acc.school'   => 'Школа и интересы',
            'review.acc.uploads'  => 'Документы',
        
            // Labels: Personal
            'review.lbl.name'            => 'Фамилия',
            'review.lbl.vorname'         => 'Имя',
            'review.lbl.geschlecht'      => 'Пол',
            'review.lbl.geburtsdatum'    => 'Дата рождения',
            'review.lbl.geburtsort'      => 'Место / страна рождения',
            'review.lbl.staatsang'       => 'Гражданство',
            'review.lbl.strasse'         => 'Улица, дом',
            'review.lbl.plz_ort'         => 'Индекс / город',
            'review.lbl.telefon'         => 'Телефон',
            'review.lbl.email'           => 'E-mail (ученица/ученик, необязательно)',
            'review.lbl.weitere_angaben' => 'Дополнительная информация (например, статус поддержки)',
        
            'review.contacts.title'    => 'Дополнительные контакты',
            'review.contacts.optional' => 'необязательно',
            'review.contacts.none'     => '–',
        
            'review.contacts.th.role' => 'Роль',
            'review.contacts.th.name' => 'Имя / учреждение',
            'review.contacts.th.tel'  => 'Телефон',
            'review.contacts.th.mail' => 'E-mail',
            'review.contacts.note'    => 'Примечание:',
        
            // Labels: School
            'review.lbl.school_current' => 'Текущая школа',
            'review.lbl.klassenlehrer'  => 'ответственный/ая учитель/учительница',
            'review.lbl.mail_lehrkraft' => 'E-mail учителя/учительницы',
            'review.lbl.since'          => 'С какого времени в школе',
            'review.lbl.years_de'       => 'Лет в Германии',
            'review.lbl.family_lang'    => 'Семейный язык / родной язык',
            'review.lbl.de_level'       => 'Уровень немецкого',
            'review.lbl.school_origin'  => 'Школа в стране происхождения',
            'review.lbl.years_origin'   => 'Лет обучения в стране происхождения',
            'review.lbl.interests'      => 'Интересы',
        
            // Uploads
            'review.lbl.zeugnis'        => 'Полугодовой табель/аттестат',
            'review.lbl.lebenslauf'     => 'Резюме (CV)',
            'review.lbl.later'          => 'предоставить позже',
            'review.badge.uploaded'     => 'загружено',
            'review.badge.not_uploaded' => 'не загружено',
            'review.yes'                => 'Да',
            'review.no'                 => 'Нет',
        
            // Buttons / Actions
            'review.btn.home'   => 'На главную',
            'review.btn.newapp' => 'Подать ещё одно заявление',
            'review.btn.back'   => 'Назад',
            'review.btn.submit' => 'Подать',
        
            // Errors / Flash / System texts
            'review.err.invalid_request'     => 'Недопустимый запрос.',
            'review.flash.already_submitted' => 'Это заявление уже отправлено и не может быть повторно отправлено или изменено.',
            'review.flash.no_token'          => 'Нет действительного кода доступа. Пожалуйста, начните процесс заново.',
            'review.err.not_found_token'     => 'Заявление по этому токену не найдено.',
            'review.flash.submit_error'      => 'При отправке произошла ошибка. Пожалуйста, попробуйте позже.',
        
            // Gender fallback
            'review.gender.m' => 'мужской',
            'review.gender.w' => 'женский',
            'review.gender.d' => 'другой',
        
            // Fallback
            'review.value.empty' => '–',
        
            // =========================
            // STATUS (RU)
            // =========================
            'status.hdr_title'   => 'Заявление успешно сохранено',
            'status.hdr_message' => 'Ваше заявление отправлено.',
        
            'status.h1' => 'Ваше заявление успешно сохранено.',
        
            'status.success.title' => 'Спасибо!',
            'status.success.body'  => 'Ваше заявление отправлено и сейчас обрабатывается.',
        
            'status.info.title' => 'Важная информация',
            'status.info.body'  => '<em>[ЗАГЛУШКА: текст от заказчика будет позже]</em>',
        
            'status.btn.pdf'    => 'Скачать / распечатать PDF',
            'status.btn.newapp' => 'Начать новое заявление',
            'status.btn.home'   => 'На главную',
        
            'status.ref' => 'Ссылка: заявление №{id}',
        
            'status.err.invalid_request' => 'Недопустимый запрос.',
        
            // =========================
            // PDF (RU)
            // =========================
            'pdf.err.autoload_missing' => 'Composer Autoload не найден. Пожалуйста, выполните "composer install".',
            'pdf.err.no_token'         => 'Нет действительного кода доступа. Пожалуйста, начните процесс заново.',
            'pdf.err.not_found'        => 'Заявление не найдено.',
            'pdf.err.server'           => 'Ошибка сервера при создании PDF.',
        
            'pdf.header_title' => 'Заявление – сводка',
            'pdf.footer_auto'  => 'Документ сформирован автоматически',
            'pdf.footer_page'  => 'Страница {cur} / {max}',
        
            'pdf.meta.ref'        => 'Заявление №{id}',
            'pdf.meta.created_at' => 'Создано',
            'pdf.meta.status'     => 'Статус',
        
            'pdf.top.title'        => 'Краткий обзор',
            'pdf.top.name'         => 'Имя',
            'pdf.top.reference'    => 'Ссылка',
            'pdf.top.generated'    => 'Создано',
            'pdf.top.hint'         => 'Примечание',
            'pdf.top.keep_note'    => 'Пожалуйста, сохраните этот документ для своих записей.',
            'pdf.hint_placeholder' => '[ЗАГЛУШКА: текст от заказчика будет позже]',
        
            'pdf.sec1.title' => '1) Личные данные',
            'pdf.sec2.title' => '2) Дополнительные контактные данные',
            'pdf.sec3.title' => '3) Школа и интересы',
            'pdf.sec4.title' => '4) Документы',
        
            'pdf.lbl.name'           => 'Фамилия',
            'pdf.lbl.vorname'        => 'Имя',
            'pdf.lbl.gender'         => 'Пол',
            'pdf.lbl.dob'            => 'Дата рождения',
            'pdf.lbl.birthplace'     => 'Место/страна рождения',
            'pdf.lbl.nationality'    => 'Гражданство',
            'pdf.lbl.address'        => 'Адрес',
            'pdf.lbl.phone'          => 'Телефон',
            'pdf.lbl.email_optional' => 'E-mail (необязательно)',
            'pdf.lbl.more'           => 'Дополнительно',
        
            'pdf.lbl.school_current' => 'Текущая школа',
            'pdf.lbl.teacher'        => 'Учитель/учительница',
            'pdf.lbl.teacher_email'  => 'E-mail учителя/учительницы',
            'pdf.lbl.since_school'   => 'С какого времени в школе',
            'pdf.lbl.years_in_de'    => 'С какого времени в Германии',
            'pdf.lbl.family_lang'    => 'Семейный язык',
            'pdf.lbl.de_level'       => 'Уровень немецкого',
            'pdf.lbl.school_origin'  => 'Школа в стране происхождения',
            'pdf.lbl.years_origin'   => 'Лет обучения в стране происхождения',
            'pdf.lbl.interests'      => 'Интересы',
        
            'pdf.lbl.report'       => 'Полугодовой табель/аттестат',
            'pdf.lbl.cv'           => 'Резюме (CV)',
            'pdf.lbl.report_later' => 'Табель предоставить позже',
        
            'pdf.uploaded'     => 'загружено',
            'pdf.not_uploaded' => 'не загружено',
        
            'pdf.contacts.none'    => '–',
            'pdf.contacts.th.role' => 'Роль',
            'pdf.contacts.th.name' => 'Имя/учреждение',
            'pdf.contacts.th.tel'  => 'Телефон',
            'pdf.contacts.th.mail' => 'E-mail',
            'pdf.contacts.th.note' => 'Примечание',
        
            'pdf.gender.m' => 'мужской',
            'pdf.gender.w' => 'женский',
            'pdf.gender.d' => 'другой',
        
            'pdf.yes' => 'Да',
            'pdf.no'  => 'Нет',
        
            'pdf.sec4.note' => 'Этот документ является автоматически созданной сводкой введённых данных.',
            'pdf.filename_prefix' => 'Заявление',
        
            // =========================
            // ACCESS_CREATE (RU)
            // =========================
            'access_create.title'         => 'Продолжить с e-mail',
            'access_create.lead'          => 'Вы можете войти в свой доступ или создать новый.',
            'access_create.tabs_login'    => 'Вход',
            'access_create.tabs_register' => 'Создать новый доступ',
        
            'access_create.login_title' => 'Вход (существующий доступ)',
            'access_create.login_text'  => 'Пожалуйста, введите e-mail и пароль.',
            'access_create.email_label' => 'E-mail',
            'access_create.pass_label'  => 'Пароль',
            'access_create.login_btn'   => 'Войти',
            'access_create.login_err'   => 'E-mail/пароль неверны или доступ не подтверждён.',
        
            'access_create.reg_title'     => 'Создать новый доступ',
            'access_create.reg_text'      => 'Мы отправим вам 6-значный код подтверждения. После успешного подтверждения вы получите пароль по e-mail.',
            'access_create.consent_label' => 'Я согласен/согласна, что мой e-mail будет использован для процесса регистрации.',
            'access_create.send_btn'      => 'Отправить код',
            'access_create.code_label'    => 'Код подтверждения',
            'access_create.verify_btn'    => 'Проверить код',
            'access_create.resend'        => 'Отправить код ещё раз',
        
            'access_create.info_sent'    => 'Мы отправили вам код. Пожалуйста, проверьте также папку «Спам».',
            'access_create.ok_verified'  => 'E-mail подтверждён. Пароль отправлен. Теперь вы можете войти.',
            'access_create.email_in_use' => 'Для этого e-mail уже создан доступ. Пожалуйста, войдите.',
        
            'access_create.error_email'     => 'Пожалуйста, введите действительный e-mail.',
            'access_create.error_consent'   => 'Пожалуйста, подтвердите согласие на использование e-mail.',
            'access_create.error_rate'      => 'Слишком много попыток. Пожалуйста, подождите немного и попробуйте снова.',
            'access_create.error_code'      => 'Код недействителен или истёк.',
            'access_create.error_resend'    => 'Повторная отправка невозможна. Пожалуйста, начните заново.',
            'access_create.error_mail_send' => 'Не удалось отправить e-mail. Пожалуйста, попробуйте позже.',
            'access_create.error_db'        => 'Ошибка сервера (БД).',
        
            'access_create.back'   => 'Назад',
            'access_create.cancel' => 'Отмена',
        
            'access_create.mail_subject' => 'Ваш пароль для онлайн-регистрации',
            'access_create.mail_body'    => "Ваш доступ создан.\n\nE-mail: {email}\nПароль: {password}\n\nПожалуйста, храните пароль в безопасном месте.",
        
            // =========================
            // ACCESS_PORTAL (RU)
            // =========================
            'access_portal.title'    => 'Мои заявления',
            'access_portal.lead'     => 'Здесь вы видите свои заявления. Вы можете продолжить существующее заявление или начать новое.',
            'access_portal.max_hint' => '{email} · макс. {max} заявлений',
        
            'access_portal.btn_new'    => 'Начать новое заявление',
            'access_portal.btn_open'   => 'Открыть',
            'access_portal.btn_logout' => 'Выйти',
        
            'access_portal.th_ref'     => 'ID',
            'access_portal.th_status'  => 'Статус',
            'access_portal.th_created' => 'Создано',
            'access_portal.th_updated' => 'Обновлено',
            'access_portal.th_token'   => 'Токен',
            'access_portal.th_action'  => 'Действие',
        
            'access_portal.status_draft'     => 'Черновик',
            'access_portal.status_submitted' => 'Отправлено',
            'access_portal.status_withdrawn' => 'Отозвано',
        
            'access_portal.limit_reached' => 'Вы достигли максимального количества заявлений для этого e-mail.',
            'access_portal.no_apps'       => 'Заявлений пока нет.',
            'access_portal.err_generic'   => 'Произошла ошибка.',
            'access_portal.csrf_invalid'  => 'Недопустимый запрос.',
        
            // =========================
            // ACCESS_LOGIN (RU)
            // =========================
            'access_login.title'             => 'Доступ к заявлению/заявлениям',
            'access_login.lead'              => 'Здесь вы можете открыть уже начатое или отправленное заявление.',
            'access_login.login_box_title'   => 'Вход по Access-Token',
            'access_login.login_box_text'    => 'Пожалуйста, введите ваш личный код доступа (Access-Token) и дату рождения.',
        
            'access_login.token_label'       => 'Access-Token',
            'access_login.dob_label'         => 'Дата рождения (ДД.ММ.ГГГГ)',
        
            'access_login.login_btn'         => 'Доступ',
            'access_login.back'              => 'Назад на главную',
        
            'access_login.login_ok'          => 'Заявление загружено.',
            'access_login.login_error'       => 'Комбинация Access-Token и даты рождения не найдена.',
            'access_login.login_error_token' => 'Пожалуйста, введите действительный Access-Token.',
            'access_login.login_error_dob'   => 'Пожалуйста, введите действительную дату рождения в формате ДД.ММ.ГГГГ.',
        
            'access_login.csrf_invalid'      => 'Недопустимый запрос.',
            'access_login.internal_error'    => 'Внутренняя ошибка.',
            'access_login.load_error'        => 'Произошла ошибка при загрузке заявления.',
        
            // =========================
            // PRIVACY (RU)
            // =========================
            'privacy.title' => 'Защита данных',
            'privacy.h1'    => 'Информация о защите данных для онлайн-заявления «BES Язык и интеграция»',
        
            'privacy.s1_title'     => '1. Ответственный орган',
            'privacy.s1_body_html' => '<strong>Город Ольденбург / профессиональные школы</strong><br>(указать точное название учреждения/школы, адрес, телефон, e-mail)',
        
            'privacy.s2_title'     => '2. Уполномоченный(ая) по защите данных',
            'privacy.s2_body_html' => '(указать контактные данные уполномоченного(ой) по защите данных)',
        
            'privacy.s3_title' => '3. Цели обработки',
            'privacy.s3_li1'   => 'Принятие и обработка вашего заявления на зачисление в языковой класс («BES Язык и интеграция»)',
            'privacy.s3_li2'   => 'Связь с вами (уточняющие вопросы, уведомления о решении по зачислению)',
            'privacy.s3_li3'   => 'Школьное планирование (распределение по BBS)',
        
            'privacy.s4_title' => '4. Правовые основания',
            'privacy.s4_li1'   => 'Ст. 6(1)(e) GDPR в сочетании со школьным законодательством земли Нижняя Саксония',
            'privacy.s4_li2'   => 'Ст. 6(1)(c) GDPR (исполнение юридических обязательств)',
            'privacy.s4_li3'   => 'Ст. 6(1)(a) GDPR (согласие) — в части добровольных данных/загрузок',
        
            'privacy.s5_title' => '5. Категории персональных данных',
            'privacy.s5_li1'   => 'Основные данные (фамилия, имя, дата рождения, гражданство, адрес, контакты)',
            'privacy.s5_li2'   => 'Школьные сведения (текущая школа, уровень языка, интересы)',
            'privacy.s5_li3'   => 'Необязательные документы (например, последний полугодовой табель)',
            'privacy.s5_li4'   => 'Дополнительные контакты (родители/опекуны/учреждения)',
        
            'privacy.s6_title' => '6. Получатели',
            'privacy.s6_body'  => 'В пределах компетенции города Ольденбург и профессиональных школ. Передача третьим лицам осуществляется только при наличии правовой необходимости (например, школьным органам) или с вашего согласия.',
        
            'privacy.s7_title' => '7. Передача в третьи страны',
            'privacy.s7_body'  => 'Передача данных в третьи страны не осуществляется.',
        
            'privacy.s8_title' => '8. Срок хранения',
            'privacy.s8_body'  => 'Ваши данные хранятся на время процедуры подачи/зачисления и в соответствии с установленными законом сроками хранения, после чего удаляются.',
        
            'privacy.s9_title' => '9. Ваши права',
            'privacy.s9_li1'   => 'Доступ (ст. 15 GDPR), исправление (ст. 16), удаление (ст. 17), ограничение (ст. 18)',
            'privacy.s9_li2'   => 'Возражение (ст. 21) против обработки в общественных интересах',
            'privacy.s9_li3'   => 'Отзыв согласий (ст. 7(3)) на будущее',
            'privacy.s9_li4'   => 'Право на жалобу в надзорный орган: Уполномоченный по защите данных Нижней Саксонии',
        
            'privacy.s10_title' => '10. Хостинг и протоколы',
            'privacy.s10_body'  => 'Приложение работает на серверах города или в муниципальном дата-центре. Обрабатываются только технически необходимые данные (например, серверные логи для поиска ошибок). Внешние CDN не используются. Устанавливается только языковой cookie.',
        
            'privacy.s11_title'    => '11. Cookies',
            'privacy.s11_li1_html' => '<strong>lang</strong> – сохраняет выбранный язык (срок 12 месяцев). Цель: удобство использования.',
            'privacy.s11_li2'      => 'PHP-сессия – технически необходима для работы формы, удаляется при завершении сессии.',
        
            'privacy.stand_label' => 'Версия',
            'privacy.stand_hint'  => 'Пожалуйста, регулярно проверяйте, нет ли изменений.',
            'privacy.back_home'   => 'На главную',
        
            // =========================
            // IMPRINT (RU)
            // =========================
            'imprint.title' => 'Выходные данные',
            'imprint.h1'    => 'Выходные данные',
        
            'imprint.s1_title'     => 'Поставщик услуг',
            'imprint.s1_body_html' => '<strong>Город ***</strong><br>Профессиональные школы<br>(указать точный адрес)<br>Телефон: (дополнить)<br>E-mail: (дополнить)',
        
            'imprint.s2_title'     => 'Представитель',
            'imprint.s2_body_html' => '(например, мэр города ****<br>или руководство соответствующей BBS)',
        
            'imprint.s3_title'     => 'Ответственный за содержание по § 18 абз. 2 MStV',
            'imprint.s3_body_html' => '(имя, должность, контакт, например руководство BBS или пресс-служба)',
        
            'imprint.s4_title'     => 'НДС-ID',
            'imprint.s4_body_html' => '(если имеется; иначе этот раздел можно удалить)',
        
            'imprint.s5_title' => 'Надзорный орган',
            'imprint.s5_body'  => '(коммунальный надзор / школьный орган, например региональное подразделение земельного школьного ведомства)',
        
            'imprint.s6_title' => 'Ответственность за содержание',
            'imprint.s6_body'  => 'Содержание наших страниц подготовлено с максимальной тщательностью. Однако мы не можем гарантировать точность, полноту и актуальность. Как публичный орган мы несем ответственность за собственное содержание в рамках общих законов.',
        
            'imprint.s7_title' => 'Ответственность за ссылки',
            'imprint.s7_body'  => 'Наше предложение не содержит внешнего контента, который передаёт персональные данные третьим лицам. Если мы даём ссылки на информационные предложения других публичных органов, мы не несём ответственности за их содержание.',
        
            'imprint.s8_title' => 'Авторское право',
            'imprint.s8_body'  => 'Содержание и произведения, созданные городом Ольденбург, подпадают под немецкое авторское право. Материалы третьих лиц отмечены как таковые. Любое использование за пределами авторского права требует письменного согласия города Ольденбург или соответствующего правообладателя.',
        
            'imprint.stand_label' => 'Версия',
            'imprint.stand_hint'  => 'Эти данные относятся к онлайн-форме «BES Язык и интеграция».',
            'imprint.back_home'   => 'На главную',
        
            // =========================
            // VERIFY_EMAIL (RU)
            // =========================
            'verify_email.title' => 'Подтвердить e-mail',
            'verify_email.h1'    => 'Подтвердить e-mail',
        
            'verify_email.lead_sent'    => 'Мы отправили код подтверждения на {email}.',
            'verify_email.lead_generic' => 'Пожалуйста, введите код подтверждения, полученный по e-mail. Если вы не видите письмо, вы можете отправить код повторно на свой адрес.',
        
            'verify_email.code_label'  => 'Код подтверждения (6 цифр)',
            'verify_email.email_label' => 'Ваш e-mail',
        
            'verify_email.btn_verify' => 'Подтвердить',
            'verify_email.btn_resend' => 'Отправить код ещё раз',
            'verify_email.hint_spam'  => 'Пожалуйста, проверьте также папку «Спам».',
        
            'verify_email.back' => 'Назад',
        
            'verify_email.csrf_invalid' => 'Недопустимый запрос.',
            'verify_email.ok_verified'  => 'E-mail успешно подтверждён.',
            'verify_email.ok_sent'      => 'Новый код отправлен на {email}.',
        
            'verify_email.warn_cooldown'     => 'Пожалуйста, подождите немного перед повторным запросом кода.',
            'verify_email.error_send'        => 'Отправка не удалась. Пожалуйста, попробуйте позже.',
            'verify_email.error_email'       => 'Пожалуйста, введите корректный e-mail.',
            'verify_email.error_no_session'  => 'Активный процесс подтверждения не найден. Пожалуйста, запросите новый код.',
            'verify_email.error_expired'     => 'Код недействителен или истёк.',
            'verify_email.error_invalid'     => 'Код недействителен или истёк.',
            'verify_email.error_code_format' => 'Пожалуйста, введите корректный 6-значный код.',
            'verify_email.error_rate'        => 'Слишком много попыток. Пожалуйста, запросите новый код.',
        
            // =========================
            // VALIDATION (RU) – global
            // =========================
            'val.required' => 'Обязательно.',
            'val.only_letters' => 'Пожалуйста, только буквы.',
            'val.gender_choose' => 'Пожалуйста, выберите пол.',
            'val.date_format' => 'ДД.ММ.ГГГГ',
            'val.date_invalid' => 'Недействительная дата.',
            'val.plz_whitelist' => 'Только индексы Ольденбурга (26121–26135).',
            'val.phone_vorwahl' => 'Код: 2–6 цифр.',
            'val.phone_nummer' => 'Номер: 3–12 цифр.',
            'val.email_invalid' => 'Недействительный e-mail.',
            'val.email_no_iserv' => 'Пожалуйста, используйте личный e-mail (не IServ).',
            'val.max_1500' => 'Максимум 1500 символов.',
            'val.kontakt_row_name_missing' => 'Имя/обозначение отсутствует',
            'val.kontakt_row_tel_or_mail'  => 'Укажите телефон ИЛИ e-mail',
            'val.kontakt_row_mail_invalid' => 'Недействительный e-mail',
            'val.kontakt_row_tel_invalid'  => 'Недействительный телефон',
        ],

        // =======================
        // TR: in 'tr' => [ ... ] einfügen (komplett)
        // =======================
        'tr' => [
        
            // =======================
            // STEP Start: Index (TR)
            // =======================
            'index.title' => 'Çevrim içi Başvuru – Dil Sınıfları',
            'index.lead'  => 'Bu hizmet, Oldenburg’a yeni göç eden kişiler içindir. Form, sizinle iletişime geçmemize ve uygun seçenekleri bulmamıza yardımcı olur.',
            'index.bullets' => [
                'Lütfen iletişim bilgilerinizi ve kimlik belgenizi (varsa) hazır bulundurun.',
                'Bilgileri birden fazla dilde doldurabilirsiniz.',
                'Verileriniz GDPR kapsamında gizli olarak işlenir.',
            ],
            'index.info_p' => [
                'Sevgili öğrenci,',
                'Bu başvuru ile Oldenburg’daki bir mesleki okulun (BBS) “BES Dil ve Entegrasyon” dil öğrenme sınıfına başvuruyorsunuz. Belirli bir BBS’e başvurmuyorsunuz. Dil sınıfına hangi okulun sizi alacağı 20 Şubat’tan sonra size bildirilecektir.',
                'Aşağıdaki koşullar sağlandığında kabul edilebilirsiniz:',
            ],
            'index.info_bullets' => [
                'Yoğun Almanca desteğine ihtiyacınız var (Almanca seviyeniz B1’in altında).',
                'Gelecek öğretim yılının başlangıcında Almanya’da en fazla 3 yıldır bulunuyorsunuz.',
                'Bu yılın 30 Eylül tarihinde en az 16, en fazla 18 yaşındasınız.',
                'Gelecek öğretim yılında zorunlu eğitime tabisiniz.',
            ],
            'index.access_title' => 'Veri Koruma ve Erişim',
            'index.access_intro' => 'E-posta adresi ile veya e-posta olmadan devam edebilirsiniz. Kaydedilmiş başvurulara erişim yalnızca kişisel erişim kodu (Token) ve doğum tarihi ile mümkündür.',
            'index.access_points' => [
                '<strong>E-posta ile:</strong> Bir doğrulama kodu alırsınız; birden fazla başvuru oluşturabilir ve daha sonra tekrar açabilirsiniz.',
                '<strong>E-posta olmadan:</strong> Kişisel bir erişim kodu (Access-Token) alırsınız. Lütfen not edin/fotoğraflayın — doğrulanmış e-posta olmadan kurtarma mümkün değildir.',
            ],
        
            'index.btn_noemail' => 'E-posta olmadan devam et',
            'index.btn_create'  => 'E-posta ile devam et',
            'index.btn_load'    => 'Başvuru(lar)a erişim',
            'index.lang_label'  => 'Dil / Language:',
        
            // =======================
            // STEP 1/4: PERSONAL (TR)
            // =======================
            'personal.page_title' => 'Adım 1/4 – Kişisel bilgiler',
            'personal.h1' => 'Adım 1/4 – Kişisel bilgiler',
            'personal.required_hint' => 'Zorunlu alanlar mavi çerçeve ile vurgulanır.',
            'personal.form_error_hint' => 'Lütfen işaretli alanları kontrol edin.',
        
            'personal.alert_email_title' => 'E-posta ile giriş aktif:',
            'personal.alert_email_line1' => '{email} e-posta adresiyle giriş yapıldı.',
            'personal.alert_email_line2' => 'Bu e-posta yalnızca erişim kodu (Access-Token) ve başvurunuzu tekrar bulmak için kullanılır.',
            'personal.alert_email_line3' => 'Aşağıda öğrencinin e-posta adresini (varsa) girebilirsiniz.',
        
            'personal.alert_noemail_title' => 'Not (e-posta olmadan):',
            'personal.alert_noemail_body' => 'Lütfen bu sayfayı kaydettikten sonra gösterilecek erişim kodunuzu (Access-Token) not edin veya fotoğrafını çekin. Doğrulanmış e-posta olmadan geri yükleme yalnızca token + doğum tarihi ile mümkündür.',
        
            'personal.label.name' => 'Soyadı',
            'personal.label.vorname' => 'Adı',
            'personal.label.geschlecht' => 'Cinsiyet',
            'personal.gender.m' => 'erkek',
            'personal.gender.w' => 'kadın',
            'personal.gender.d' => 'diğer',
        
            'personal.label.geburtsdatum' => 'Doğum tarihi',
            'personal.label.geburtsdatum_hint' => '(GG.AA.YYYY)',
            'personal.placeholder.geburtsdatum' => 'GG.AA.YYYY',
        
            'personal.age_hint' => 'Not: 30.09.{year} tarihinde 16 yaşından küçük veya 18 yaşından büyük iseniz BBS dil sınıfına kabul edilemezsiniz. Lütfen başka bir sınıfa buradan başvurun:',
            'personal.age_redirect_msg' => "Not: 30.09.{year} tarihinde 16 yaşından küçük veya 18 yaşından büyük iseniz BBS dil sınıfına kabul edilemezsiniz. Lütfen BBS’de başka bir sınıfa buradan başvurun:\n{url}",
        
            'personal.label.geburtsort_land' => 'Doğum yeri / doğum ülkesi',
            'personal.label.staatsang' => 'Uyruğu',
        
            'personal.label.strasse' => 'Sokak, No.',
            'personal.label.plz' => 'Posta kodu',
            'personal.plz_choose' => '– lütfen seçin –',
            'personal.plz_hint' => 'Sadece Oldenburg (Oldb).',
            'personal.label.wohnort' => 'Şehir',
        
            'personal.label.telefon' => 'Telefon numarası',
            'personal.label.telefon_vorwahl_help' => 'Alan kodu 0 ile/0’sız',
            'personal.label.telefon_nummer_help' => 'Numara',
            'personal.placeholder.telefon_vorwahl' => '(0)441',
            'personal.placeholder.telefon_nummer' => '123456',
        
            'personal.label.email' => 'Öğrencinin e-posta adresi (opsiyonel, IServ adresi değil)',
            'personal.email_help' => 'Bu e-posta öğrenciye aittir (varsa) ve erişim kodu için kullanılan giriş e-postasından bağımsızdır.',
            'personal.placeholder.email' => 'name@example.org',
        
            'personal.label.kontakte' => 'Ek iletişim bilgileri',
            'personal.kontakte_hint' => '(örn. ebeveynler, vasi, kurum)',
            'personal.kontakte_error' => 'Lütfen ek kişileri kontrol edin.',
            'personal.kontakte_add' => '+ Kişi ekle',
            'personal.kontakte_remove_title' => 'Kişiyi kaldır',
        
            'personal.table.role' => 'Rol',
            'personal.table.name' => 'İsim / kurum',
            'personal.table.tel'  => 'Telefon',
            'personal.table.mail' => 'E-posta',
            'personal.table.note_header' => 'Not',
            'personal.placeholder.kontakt_name' => 'İsim veya tanım',
            'personal.placeholder.kontakt_tel'  => '+49 …',
            'personal.placeholder.kontakt_note' => 'örn. ulaşılabilirlik, dil, notlar',
        
            'personal.contact_role.none' => '–',
            'personal.contact_role.mutter' => 'Anne',
            'personal.contact_role.vater' => 'Baba',
            'personal.contact_role.elternteil' => 'Ebeveyn',
            'personal.contact_role.betreuer' => 'Vasi/Sorumlu',
            'personal.contact_role.einrichtung' => 'Kurum',
            'personal.contact_role.sonstiges' => 'Diğer',
        
            'personal.label.weitere_angaben' => 'Diğer bilgiler (örn. destek durumu):',
            'personal.placeholder.weitere_angaben' => 'Buraya örn. özel destek ihtiyacı, özel eğitim desteği veya diğer notları yazabilirsiniz.',
            'personal.weitere_angaben_help' => 'Opsiyonel. En fazla 1500 karakter.',
            'personal.btn.cancel' => 'İptal',
            'personal.btn.next' => 'İleri',
        
            'personal.dsgvo_text_prefix' => '',
            'personal.dsgvo_link_text' => 'Gizlilik bilgilendirmesini',
            'personal.dsgvo_text_suffix' => 'okudum ve kabul ediyorum.',
        
            // =====================
            // STEP 2/4: SCHOOL (TR)
            // =====================
            'school.page_title' => 'Adım 2/4 – Okul ve ilgi alanları',
            'school.h1' => 'Adım 2/4 – Okul ve ilgi alanları',
            'school.required_hint' => 'Zorunlu alanlar mavi çerçeve ile vurgulanır.',
            'school.form_error_hint' => 'Lütfen işaretli alanları kontrol edin.',
        
            'school.top_hint_title' => 'Not:',
            'school.top_hint_body'  => 'Almanya’da <u>3 yıldan fazla</u> bulunuyorsanız veya Almanca seviyeniz <u>B1</u> ya da üzerindeyse, BBS dil sınıfına kabul edilemezsiniz. Lütfen normal BBS başvurusu için buradan başvurun:',
            'school.bbs_link_label' => 'https://bbs-ol.de/',
        
            'school.autohints_title' => 'Uyarılar',
        
            'school.label.schule_aktuell' => 'Mevcut okul',
            'school.search_placeholder'   => 'Okul ara… (ad, sokak, posta kodu)',
            'school.select_choose'        => 'Lütfen seçin…',
            'school.option_other'         => 'Diğer / listede yok',
            'school.other_placeholder'    => 'Okul adı, sokak, şehir (serbest metin)',
        
            'school.label.teacher'      => 'sorumlu öğretmen',
            'school.label.teacher_mail' => 'Sorumlu öğretmenin e-postası',
        
            'school.label.herkunft'       => 'Kendi ülkenizde okula gittiniz mi?',
            'school.yes'                  => 'Evet',
            'school.no'                   => 'Hayır',
            'school.label.herkunft_years' => 'Evet ise: kaç yıl?',
        
            'school.label.since'        => 'Almanya’da bir okula ne zamandan beri gidiyorsunuz?',
            'school.since_month'        => 'Ay (AA)',
            'school.since_year_ph'      => 'Yıl (YYYY)',
            'school.since_help'         => 'Ay+yıl belirtin <strong>veya</strong> serbest metin alanını kullanın.',
            'school.label.since_text'   => 'Alternatif: serbest metin (örn. “2023 sonbaharından beri”)',
        
            'school.label.years_in_de'  => 'Almanya’da kaç yıldır bulunuyorsunuz?',
            'school.years_in_de_help'   => 'Not: &gt; 3 yıl → lütfen {link} üzerinden normal BBS başvurusu.',
        
            'school.label.family_lang'  => 'Aile dili / ana dil',
        
            'school.label.level'        => 'Almanca seviyeniz nedir?',
            'school.level_choose'       => 'Lütfen seçin…',
            'school.level_help'         => 'Not: B1 veya üzeri → {link} üzerinden normal BBS başvurusu.',
        
            'school.label.interests'    => 'İlgi alanları (en az 1, en fazla 2)',
        
            'school.btn.back'           => 'Geri',
            'school.btn.next'           => 'İleri',
        
            // ---------------------
            // Validasyon / Hatalar
            // ---------------------
            'val.school_free_required' => 'Lütfen okul adını (serbest metin) girin.',
            'val.school_invalid'       => 'Lütfen geçerli bir okul seçin veya “Diğer / listede yok” seçeneğini kullanın.',
        
            'val.since_required'       => 'Lütfen ay+yıl veya serbest metin girin.',
            'val.month_invalid'        => 'Ay 01–12 olmalıdır.',
            'val.year_invalid'         => 'Lütfen geçerli bir yıl girin.',
            'val.number_required'      => 'Lütfen bir sayı girin.',
            'val.choose'               => 'Lütfen seçin.',
            'val.herkunft_years'       => 'Lütfen yıl sayısını girin.',
        
            'val.level_invalid'        => 'Geçersiz seçim.',
        
            'val.interests_min1'       => 'Lütfen en az 1 alan seçin.',
            'val.interests_max2'       => 'Lütfen en fazla 2 alan seçin.',
        
            // ---------------------
            // JS Canlı uyarılar
            // ---------------------
            'js.hint_years_gt3' => 'Not: Almanya’da 3 yıldan fazla bulunuyorsunuz. Lütfen {link} üzerinden başvurun.',
            'js.hint_level_b1p' => 'Not: Almanca seviyesi B1 veya üzeri ise lütfen {link} üzerinden normal BBS başvurusu yapın.',
        
            // =========================
            // STEP 3/4: UPLOAD (TR)
            // =========================
            'upload.page_title' => 'Adım 3/4 – Belgeler (opsiyonel)',
            'upload.h1'         => 'Adım 3/4 – Belgeler (opsiyonel)',
        
            'upload.intro'      => 'Buradan belge yükleyebilirsiniz. İzin verilen formatlar <strong>PDF</strong>, <strong>JPG</strong> ve <strong>PNG</strong>’dir. Dosya başına azami boyut <strong>{max_mb} MB</strong>’dır.',
        
            'upload.type.zeugnis'    => 'Son dönem karne belgesi',
            'upload.type.lebenslauf' => 'Özgeçmiş (CV)',
            'upload.type_hint'       => '(PDF/JPG/PNG, en fazla {max_mb} MB)',
        
            'upload.btn.remove' => 'Kaldır',
            'upload.btn.back'   => 'Geri',
            'upload.btn.next'   => 'İleri',
        
            'upload.saved_prefix' => 'Kaydedildi:',
            'upload.empty'        => 'Henüz dosya yüklenmedi.',
            'upload.saved_html'   => 'Kaydedildi: <strong>{filename}</strong>, {size_kb} KB, yükleme tarihi: {uploaded_at}',
        
            'upload.checkbox.zeugnis_spaeter' => 'Karnemi kabulden sonra teslim edeceğim.',
        
            'upload.flash.no_access' => 'Geçerli bir erişim bulunamadı. Lütfen başvuruyu yeniden başlatın.',
            'upload.flash.saved'     => 'Yükleme bilgileri kaydedildi.',
        
            'upload.js.uploading'          => 'Yükleniyor…',
            'upload.js.unexpected'         => 'Sunucudan beklenmeyen yanıt.',
            'upload.js.upload_failed'      => 'Yükleme başarısız.',
            'upload.js.delete_confirm'     => 'Yüklenen dosyayı gerçekten silmek istiyor musunuz?',
            'upload.js.delete_failed'      => 'Silme başarısız.',
            'upload.js.remove_confirm_btn' => 'Dosyayı kaldırılsın mı?',
        
            // AJAX / Hata metinleri
            'upload.ajax.invalid_method'   => 'Geçersiz yöntem',
            'upload.ajax.invalid_csrf'     => 'Geçersiz CSRF token',
            'upload.ajax.no_access'        => 'Geçerli erişim yok.',
            'upload.ajax.invalid_field'    => 'Geçersiz alan',
            'upload.ajax.no_file_sent'     => 'Dosya gönderilmedi',
            'upload.ajax.no_file_selected' => 'Dosya seçilmedi',
            'upload.ajax.upload_error'     => 'Yükleme hatası (kod {code})',
            'upload.ajax.too_large'        => 'Dosya {max_mb} MB’den büyük',
            'upload.ajax.mime_only'        => 'Sadece PDF, JPG veya PNG izinli',
            'upload.ajax.ext_only'         => 'Geçersiz dosya uzantısı (sadece pdf/jpg/jpeg/png)',
            'upload.ajax.cannot_save'      => 'Dosya kaydedilemedi',
            'upload.ajax.unknown_action'   => 'Bilinmeyen işlem',
            'upload.ajax.server_error'     => 'Yükleme sırasında sunucu hatası',
        
            // =========================
            // STEP 4/4: REVIEW (TR)
            // =========================
            'review.page_title' => 'Adım 4/4 – Özet ve Başvuru',
        
            'review.h1'      => 'Adım 4/4 – Özet ve Başvuru',
            'review.subhead' => 'Lütfen bilgilerinizi kontrol edin. “Başvur” ile verileri gönderirsiniz.',
        
            'review.readonly_alert' => 'Bu başvuru daha önce gönderildi. Bilgiler sadece görüntülenebilir; değiştirilemez veya tekrar gönderilemez.',
        
            'review.info.p1' => 'Sevgili öğrenci,',
            'review.info.p2' => '<strong>“başvur”</strong> butonuna tıklayarak Oldenburg’daki bir BBS’te <strong>BES Dil ve Entegrasyon</strong> programına başvurmuş olursunuz.',
            'review.info.p3' => 'Bu henüz kesin kayıt değil, bir <strong>başvurudur</strong>. <strong>20.02.</strong> tarihinden sonra kabul edilip edilmediğiniz / hangi BBS’e yerleştirildiğiniz size bildirilecektir. Lütfen posta kutunuzu ve e-posta gelen kutunuzu düzenli kontrol edin. Posta kutunuzda adınızın görünür olmasına dikkat edin.',
            'review.info.p4' => 'Okuldan kabul geldiğinde, bu belgeleri sonradan teslim etmeniz istenecektir (bugün henüz yüklemediyseniz):',
            'review.info.li1' => 'son dönem karne belgesi',
        
            // Accordion başlıkları
            'review.acc.personal' => 'Kişisel bilgiler',
            'review.acc.school'   => 'Okul ve ilgi alanları',
            'review.acc.uploads'  => 'Belgeler',
        
            // Labels: Personal
            'review.lbl.name'            => 'Soyadı',
            'review.lbl.vorname'         => 'Adı',
            'review.lbl.geschlecht'      => 'Cinsiyet',
            'review.lbl.geburtsdatum'    => 'Doğum tarihi',
            'review.lbl.geburtsort'      => 'Doğum yeri / doğum ülkesi',
            'review.lbl.staatsang'       => 'Uyruğu',
            'review.lbl.strasse'         => 'Sokak, No.',
            'review.lbl.plz_ort'         => 'Posta kodu / şehir',
            'review.lbl.telefon'         => 'Telefon',
            'review.lbl.email'           => 'E-posta (öğrenci, opsiyonel)',
            'review.lbl.weitere_angaben' => 'Diğer bilgiler (örn. destek durumu)',
        
            'review.contacts.title'    => 'Ek kişiler',
            'review.contacts.optional' => 'opsiyonel',
            'review.contacts.none'     => '–',
        
            'review.contacts.th.role' => 'Rol',
            'review.contacts.th.name' => 'İsim / kurum',
            'review.contacts.th.tel'  => 'Telefon',
            'review.contacts.th.mail' => 'E-posta',
            'review.contacts.note'    => 'Not:',
        
            // Labels: School
            'review.lbl.school_current' => 'Mevcut okul',
            'review.lbl.klassenlehrer'  => 'Sorumlu öğretmen',
            'review.lbl.mail_lehrkraft' => 'Öğretmenin e-postası',
            'review.lbl.since'          => 'Ne zamandan beri okulda',
            'review.lbl.years_de'       => 'Almanya’da yıl',
            'review.lbl.family_lang'    => 'Aile dili / ana dil',
            'review.lbl.de_level'       => 'Almanca seviyesi',
            'review.lbl.school_origin'  => 'Kendi ülkesinde okul',
            'review.lbl.years_origin'   => 'Kendi ülkesinde okul yılı',
            'review.lbl.interests'      => 'İlgi alanları',
        
            // Uploads
            'review.lbl.zeugnis'        => 'Karne belgesi',
            'review.lbl.lebenslauf'     => 'Özgeçmiş (CV)',
            'review.lbl.later'          => 'sonradan teslim',
            'review.badge.uploaded'     => 'yüklendi',
            'review.badge.not_uploaded' => 'yüklenmedi',
            'review.yes'                => 'Evet',
            'review.no'                 => 'Hayır',
        
            // Buttons / Actions
            'review.btn.home'   => 'Ana sayfa',
            'review.btn.newapp' => 'Yeni başvuru gönder',
            'review.btn.back'   => 'Geri',
            'review.btn.submit' => 'Başvur',
        
            // Errors / Flash / System texts
            'review.err.invalid_request'     => 'Geçersiz istek.',
            'review.flash.already_submitted' => 'Bu başvuru zaten gönderildi ve tekrar gönderilemez veya değiştirilemez.',
            'review.flash.no_token'          => 'Geçerli erişim kodu yok. Lütfen işlemi yeniden başlatın.',
            'review.err.not_found_token'     => 'Bu token ile başvuru bulunamadı.',
            'review.flash.submit_error'      => 'Gönderim sırasında bir hata oluştu. Lütfen daha sonra tekrar deneyin.',
        
            // Gender fallback
            'review.gender.m' => 'erkek',
            'review.gender.w' => 'kadın',
            'review.gender.d' => 'diğer',
        
            // Fallback
            'review.value.empty' => '–',
        
            // =========================
            // STATUS (TR)
            // =========================
            'status.hdr_title'   => 'Başvuru başarıyla kaydedildi',
            'status.hdr_message' => 'Başvurunuz iletildi.',
        
            'status.h1' => 'Başvurunuz başarıyla kaydedildi.',
        
            'status.success.title' => 'Teşekkürler!',
            'status.success.body'  => 'Başvurunuz iletildi ve şimdi işleniyor.',
        
            'status.info.title' => 'Önemli bilgi',
            'status.info.body'  => '<em>[YER TUTUCU: müşteriden metin gelecek]</em>',
        
            'status.btn.pdf'    => 'PDF indir / yazdır',
            'status.btn.newapp' => 'Yeni başvuru başlat',
            'status.btn.home'   => 'Ana sayfa',
        
            'status.ref' => 'Referans: Başvuru #{id}',
        
            'status.err.invalid_request' => 'Geçersiz istek.',
        
            // =========================
            // PDF (TR)
            // =========================
            'pdf.err.autoload_missing' => 'Composer Autoload bulunamadı. Lütfen "composer install" çalıştırın.',
            'pdf.err.no_token'         => 'Geçerli erişim kodu yok. Lütfen işlemi yeniden başlatın.',
            'pdf.err.not_found'        => 'Başvuru bulunamadı.',
            'pdf.err.server'           => 'PDF oluşturulurken sunucu hatası oluştu.',
        
            'pdf.header_title' => 'Başvuru – Özet',
            'pdf.footer_auto'  => 'Otomatik oluşturulan belge',
            'pdf.footer_page'  => 'Sayfa {cur} / {max}',
        
            'pdf.meta.ref'        => 'Başvuru #{id}',
            'pdf.meta.created_at' => 'Oluşturulma tarihi',
            'pdf.meta.status'     => 'Durum',
        
            'pdf.top.title'        => 'Kısa özet',
            'pdf.top.name'         => 'İsim',
            'pdf.top.reference'    => 'Referans',
            'pdf.top.generated'    => 'Oluşturulma tarihi',
            'pdf.top.hint'         => 'Not',
            'pdf.top.keep_note'    => 'Lütfen bu belgeyi saklayın.',
            'pdf.hint_placeholder' => '[YER TUTUCU: müşteriden metin gelecek]',
        
            'pdf.sec1.title' => '1) Kişisel bilgiler',
            'pdf.sec2.title' => '2) Ek iletişim bilgileri',
            'pdf.sec3.title' => '3) Okul ve ilgi alanları',
            'pdf.sec4.title' => '4) Belgeler',
        
            'pdf.lbl.name'           => 'Soyadı',
            'pdf.lbl.vorname'        => 'Adı',
            'pdf.lbl.gender'         => 'Cinsiyet',
            'pdf.lbl.dob'            => 'Doğum tarihi',
            'pdf.lbl.birthplace'     => 'Doğum yeri/ülke',
            'pdf.lbl.nationality'    => 'Uyruğu',
            'pdf.lbl.address'        => 'Adres',
            'pdf.lbl.phone'          => 'Telefon',
            'pdf.lbl.email_optional' => 'E-posta (opsiyonel)',
            'pdf.lbl.more'           => 'Diğer bilgiler',
        
            'pdf.lbl.school_current' => 'Mevcut okul',
            'pdf.lbl.teacher'        => 'Öğretmen',
            'pdf.lbl.teacher_email'  => 'Öğretmen e-postası',
            'pdf.lbl.since_school'   => 'Ne zamandan beri okulda',
            'pdf.lbl.years_in_de'    => 'Almanya’da ne zamandan beri',
            'pdf.lbl.family_lang'    => 'Aile dili',
            'pdf.lbl.de_level'       => 'Almanca seviyesi',
            'pdf.lbl.school_origin'  => 'Kendi ülkesinde okul',
            'pdf.lbl.years_origin'   => 'Kendi ülkesinde okul yılı',
            'pdf.lbl.interests'      => 'İlgi alanları',
        
            'pdf.lbl.report'       => 'Karne belgesi',
            'pdf.lbl.cv'           => 'Özgeçmiş (CV)',
            'pdf.lbl.report_later' => 'Karneyi sonra teslim et',
        
            'pdf.uploaded'     => 'yüklendi',
            'pdf.not_uploaded' => 'yüklenmedi',
        
            'pdf.contacts.none'    => '–',
            'pdf.contacts.th.role' => 'Rol',
            'pdf.contacts.th.name' => 'İsim/Kurum',
            'pdf.contacts.th.tel'  => 'Telefon',
            'pdf.contacts.th.mail' => 'E-posta',
            'pdf.contacts.th.note' => 'Not',
        
            'pdf.gender.m' => 'erkek',
            'pdf.gender.w' => 'kadın',
            'pdf.gender.d' => 'diğer',
        
            'pdf.yes' => 'Evet',
            'pdf.no'  => 'Hayır',
        
            'pdf.sec4.note' => 'Bu belge, girilen verilerin otomatik oluşturulmuş bir özetidir.',
            'pdf.filename_prefix' => 'Başvuru',
        
            // =========================
            // ACCESS_CREATE (TR)
            // =========================
            'access_create.title'         => 'E-posta ile devam et',
            'access_create.lead'          => 'Mevcut erişiminizle giriş yapabilir veya yeni bir erişim oluşturabilirsiniz.',
            'access_create.tabs_login'    => 'Giriş',
            'access_create.tabs_register' => 'Yeni erişim oluştur',
        
            'access_create.login_title' => 'Giriş (mevcut erişim)',
            'access_create.login_text'  => 'Lütfen e-posta adresinizi ve şifrenizi girin.',
            'access_create.email_label' => 'E-posta',
            'access_create.pass_label'  => 'Şifre',
            'access_create.login_btn'   => 'Giriş yap',
            'access_create.login_err'   => 'E-posta/şifre yanlış veya erişim doğrulanmamış.',
        
            'access_create.reg_title'     => 'Yeni erişim oluştur',
            'access_create.reg_text'      => 'Size 6 haneli bir doğrulama kodu göndereceğiz. Doğrulama başarılı olunca şifreniz e-posta ile gönderilecektir.',
            'access_create.consent_label' => 'E-postamın başvuru sürecinde kullanılmasını kabul ediyorum.',
            'access_create.send_btn'      => 'Kod gönder',
            'access_create.code_label'    => 'Doğrulama kodu',
            'access_create.verify_btn'    => 'Kodu doğrula',
            'access_create.resend'        => 'Kodu yeniden gönder',
        
            'access_create.info_sent'    => 'Size bir kod gönderdik. Lütfen spam klasörünü de kontrol edin.',
            'access_create.ok_verified'  => 'E-posta doğrulandı. Şifre gönderildi. Artık giriş yapabilirsiniz.',
            'access_create.email_in_use' => 'Bu e-posta için zaten bir erişim var. Lütfen giriş yapın.',
        
            'access_create.error_email'     => 'Lütfen geçerli bir e-posta adresi girin.',
            'access_create.error_consent'   => 'Lütfen e-postanın kullanılmasına onay verin.',
            'access_create.error_rate'      => 'Çok fazla deneme. Lütfen kısa süre bekleyip tekrar deneyin.',
            'access_create.error_code'      => 'Kod geçersiz veya süresi dolmuş.',
            'access_create.error_resend'    => 'Yeniden gönderim mümkün değil. Lütfen baştan başlayın.',
            'access_create.error_mail_send' => 'E-posta gönderimi başarısız. Lütfen daha sonra tekrar deneyin.',
            'access_create.error_db'        => 'Sunucu hatası (DB).',
        
            'access_create.back'   => 'Geri',
            'access_create.cancel' => 'İptal',
        
            'access_create.mail_subject' => 'Çevrim içi başvuru için şifreniz',
            'access_create.mail_body'    => "Erişiminiz oluşturuldu.\n\nE-posta: {email}\nŞifre: {password}\n\nLütfen şifrenizi güvenli şekilde saklayın.",
        
            // =========================
            // ACCESS_PORTAL (TR)
            // =========================
            'access_portal.title'    => 'Başvurularım',
            'access_portal.lead'     => 'Burada başvurularınızı görebilirsiniz. Mevcut bir başvuruyu devam ettirebilir veya yeni bir başvuru başlatabilirsiniz.',
            'access_portal.max_hint' => '{email} · en fazla {max} başvuru',
        
            'access_portal.btn_new'    => 'Yeni başvuru başlat',
            'access_portal.btn_open'   => 'Aç',
            'access_portal.btn_logout' => 'Çıkış yap',
        
            'access_portal.th_ref'     => 'ID',
            'access_portal.th_status'  => 'Durum',
            'access_portal.th_created' => 'Oluşturuldu',
            'access_portal.th_updated' => 'Güncellendi',
            'access_portal.th_token'   => 'Token',
            'access_portal.th_action'  => 'İşlem',
        
            'access_portal.status_draft'     => 'Taslak',
            'access_portal.status_submitted' => 'Gönderildi',
            'access_portal.status_withdrawn' => 'Geri çekildi',
        
            'access_portal.limit_reached' => 'Bu e-posta için azami başvuru sayısına ulaştınız.',
            'access_portal.no_apps'       => 'Henüz başvuru yok.',
            'access_portal.err_generic'   => 'Bir hata oluştu.',
            'access_portal.csrf_invalid'  => 'Geçersiz istek.',
        
            // =========================
            // ACCESS_LOGIN (TR)
            // =========================
            'access_login.title'             => 'Başvuru(lar)a erişim',
            'access_login.lead'              => 'Buradan daha önce başlatılmış veya gönderilmiş bir başvuruyu tekrar açabilirsiniz.',
        
            'access_login.login_box_title'   => 'Access-Token ile giriş',
            'access_login.login_box_text'    => 'Lütfen kişisel erişim kodunuzu (Access-Token) ve doğum tarihinizi girin.',
        
            'access_login.token_label'       => 'Access-Token',
            'access_login.dob_label'         => 'Doğum tarihi (GG.AA.YYYY)',
        
            'access_login.login_btn'         => 'Erişim',
            'access_login.back'              => 'Ana sayfaya dön',
        
            'access_login.login_ok'          => 'Başvuru yüklendi.',
            'access_login.login_error'       => 'Access-Token ve doğum tarihi kombinasyonu bulunamadı.',
            'access_login.login_error_token' => 'Lütfen geçerli bir Access-Token girin.',
            'access_login.login_error_dob'   => 'Lütfen doğum tarihinizi GG.AA.YYYY formatında girin.',
        
            'access_login.csrf_invalid'      => 'Geçersiz istek.',
            'access_login.internal_error'    => 'Dahili hata.',
            'access_login.load_error'        => 'Başvuru yüklenirken bir hata oluştu.',
        
            // =========================
            // PRIVACY (TR)
            // =========================
            'privacy.title' => 'Veri koruma',
            'privacy.h1'    => '“BES Dil ve Entegrasyon” çevrim içi başvurusu için veri koruma bilgilendirmesi',
        
            'privacy.s1_title'     => '1. Sorumlu kurum',
            'privacy.s1_body_html' => '<strong>Oldenburg Şehri / Mesleki Okullar</strong><br>(kurum/okul adı, adres, telefon, e-posta bilgileri eklenecek)',
        
            'privacy.s2_title'     => '2. Veri koruma görevlisi',
            'privacy.s2_body_html' => '(kurumsal veri koruma görevlisinin iletişim bilgileri eklenecek)',
        
            'privacy.s3_title' => '3. İşleme amaçları',
            'privacy.s3_li1'   => 'Dil sınıfına (“BES Dil ve Entegrasyon”) kabul için başvurunuzun alınması ve işlenmesi',
            'privacy.s3_li2'   => 'Sizinle iletişim (sorular, kabul kararıyla ilgili bildirimler)',
            'privacy.s3_li3'   => 'Okul organizasyonu (bir BBS’e yerleştirme)',
        
            'privacy.s4_title' => '4. Hukuki dayanaklar',
            'privacy.s4_li1'   => 'GDPR Madde 6(1)(e) ile Aşağı Saksonya eyaletinin okul mevzuatı',
            'privacy.s4_li2'   => 'GDPR Madde 6(1)(c) (hukuki yükümlülüklerin yerine getirilmesi)',
            'privacy.s4_li3'   => 'GDPR Madde 6(1)(a) (rıza) – gönüllü bilgiler/yüklemeler için',
        
            'privacy.s5_title' => '5. Kişisel veri kategorileri',
            'privacy.s5_li1'   => 'Kimlik/temel bilgiler (soyadı, adı, doğum bilgileri, uyruk, adres, iletişim)',
            'privacy.s5_li2'   => 'Okul bilgileri (mevcut okul, dil seviyesi, ilgi alanları)',
            'privacy.s5_li3'   => 'Opsiyonel belgeler (örn. son dönem karne belgesi)',
            'privacy.s5_li4'   => 'Ek kişiler (ebeveyn/vasi/kurum)',
        
            'privacy.s6_title' => '6. Alıcılar',
            'privacy.s6_body'  => 'Oldenburg şehri ve mesleki okulların yetki alanı içinde. Üçüncü kişilere aktarım yalnızca yasal olarak gerekli ise (örn. okul makamları) veya rızanızla yapılır.',
        
            'privacy.s7_title' => '7. Üçüncü ülkelere aktarım',
            'privacy.s7_body'  => 'Üçüncü ülkelere aktarım yapılmaz.',
        
            'privacy.s8_title' => '8. Saklama süresi',
            'privacy.s8_body'  => 'Verileriniz başvuru/kabul süreci boyunca ve yasal saklama sürelerine uygun şekilde saklanır, ardından silinir.',
        
            'privacy.s9_title' => '9. Haklarınız',
            'privacy.s9_li1'   => 'Bilgi alma (GDPR 15), düzeltme (16), silme (17), kısıtlama (18)',
            'privacy.s9_li2'   => 'Kamu yararı kapsamındaki işlemlere itiraz (21)',
            'privacy.s9_li3'   => 'Verilen rızayı geleceğe etkili olacak şekilde geri çekme (7(3))',
            'privacy.s9_li4'   => 'Denetim makamına şikâyet hakkı: Aşağı Saksonya Veri Koruma Yetkilisi',
        
            'privacy.s10_title' => '10. Barındırma ve kayıtlar',
            'privacy.s10_body'  => 'Uygulama şehir sunucularında veya belediye veri merkezinde çalıştırılır. Sadece teknik olarak gerekli veriler işlenir (örn. hata analizi için sunucu logları). Harici CDN kullanılmaz. Sadece dil çerezi ayarlanır.',
        
            'privacy.s11_title'    => '11. Çerezler',
            'privacy.s11_li1_html' => '<strong>lang</strong> – seçilen dili kaydeder (12 ay geçerli). Amaç: kullanım kolaylığı.',
            'privacy.s11_li2'      => 'PHP oturumu – form akışı için teknik olarak gereklidir, oturum bitince silinir.',
        
            'privacy.stand_label' => 'Sürüm',
            'privacy.stand_hint'  => 'Lütfen düzenli olarak değişiklik olup olmadığını kontrol edin.',
            'privacy.back_home'   => 'Ana sayfaya dön',
        
            // =========================
            // IMPRINT (TR)
            // =========================
            'imprint.title' => 'Künye',
            'imprint.h1'    => 'Künye',
        
            'imprint.s1_title'     => 'Hizmet sağlayıcı',
            'imprint.s1_body_html' => '<strong>Şehir ***</strong><br>Mesleki okullar<br>(tam adres eklenecek)<br>Telefon: (eklenecek)<br>E-posta: (eklenecek)',
        
            'imprint.s2_title'     => 'Temsil yetkilisi',
            'imprint.s2_body_html' => '(örn. şehir belediye başkanı<br>veya ilgili BBS yönetimi)',
        
            'imprint.s3_title'     => '§ 18 Abs. 2 MStV uyarınca içerikten sorumlu',
            'imprint.s3_body_html' => '(isim, görev, iletişim; örn. BBS yönetimi veya basın birimi)',
        
            'imprint.s4_title'     => 'KDV kimlik no',
            'imprint.s4_body_html' => '(varsa; yoksa bu bölüm kaldırılabilir)',
        
            'imprint.s5_title' => 'Denetim makamı',
            'imprint.s5_body'  => '(yetkili belediye denetimi / okul makamı, örn. eyalet okul idaresi bölge birimi)',
        
            'imprint.s6_title' => 'İçerik için sorumluluk',
            'imprint.s6_body'  => 'Sayfalarımızın içeriği büyük özenle hazırlanmıştır. Ancak doğruluk, eksiksizlik ve güncellik konusunda garanti veremeyiz. Kamu kurumu olarak kendi içeriklerimizden genel yasalara göre sorumluyuz.',
        
            'imprint.s7_title' => 'Bağlantılar için sorumluluk',
            'imprint.s7_body'  => 'Teklifimiz, kişisel verileri üçüncü kişilere aktaran harici içerik içermez. Diğer kamu kurumlarının bilgi sayfalarına bağlantı verirsek, içeriklerinden sorumlu değiliz.',
        
            'imprint.s8_title' => 'Telif hakkı',
            'imprint.s8_body'  => 'Oldenburg şehri tarafından oluşturulan içerikler ve eserler Alman telif hakkına tabidir. Üçüncü kişilere ait içerikler bu şekilde işaretlenmiştir. Telif hakkı sınırları dışında çoğaltma, işleme, dağıtım ve her türlü kullanım için yazılı izin gerekir.',
        
            'imprint.stand_label' => 'Sürüm',
            'imprint.stand_hint'  => 'Bu bilgiler “BES Dil ve Entegrasyon” çevrim içi formu için geçerlidir.',
            'imprint.back_home'   => 'Ana sayfaya dön',
        
            // =========================
            // VERIFY_EMAIL (TR)
            // =========================
            'verify_email.title' => 'E-postayı doğrula',
            'verify_email.h1'    => 'E-postayı doğrula',
        
            'verify_email.lead_sent'    => '{email} adresine bir doğrulama kodu gönderdik.',
            'verify_email.lead_generic' => 'Lütfen e-posta ile aldığınız doğrulama kodunu girin. E-posta görünmüyorsa kodu tekrar gönderebilirsiniz.',
        
            'verify_email.code_label'  => 'Doğrulama kodu (6 haneli)',
            'verify_email.email_label' => 'E-posta adresiniz',
        
            'verify_email.btn_verify' => 'Doğrula',
            'verify_email.btn_resend' => 'Kodu yeniden gönder',
            'verify_email.hint_spam'  => 'Lütfen spam klasörünü de kontrol edin.',
        
            'verify_email.back' => 'Geri',
        
            'verify_email.csrf_invalid' => 'Geçersiz istek.',
            'verify_email.ok_verified'  => 'E-posta başarıyla doğrulandı.',
            'verify_email.ok_sent'      => 'Yeni kod {email} adresine gönderildi.',
        
            'verify_email.warn_cooldown'     => 'Lütfen kodu tekrar istemeden önce kısa süre bekleyin.',
            'verify_email.error_send'        => 'Gönderim başarısız. Lütfen daha sonra tekrar deneyin.',
            'verify_email.error_email'       => 'Lütfen geçerli bir e-posta girin.',
            'verify_email.error_no_session'  => 'Aktif doğrulama süreci bulunamadı. Lütfen yeni kod isteyin.',
            'verify_email.error_expired'     => 'Kod geçersiz veya süresi dolmuş.',
            'verify_email.error_invalid'     => 'Kod geçersiz veya süresi dolmuş.',
            'verify_email.error_code_format' => 'Lütfen geçerli bir 6 haneli kod girin.',
            'verify_email.error_rate'        => 'Çok fazla deneme. Lütfen yeni bir kod isteyin.',
        
            // =========================
            // VALIDATION (TR) – global
            // =========================
            'val.required' => 'Zorunlu.',
            'val.only_letters' => 'Lütfen sadece harf kullanın.',
            'val.gender_choose' => 'Lütfen bir cinsiyet seçin.',
            'val.date_format' => 'GG.AA.YYYY',
            'val.date_invalid' => 'Geçersiz tarih.',
            'val.plz_whitelist' => 'Sadece Oldenburg posta kodları (26121–26135).',
            'val.phone_vorwahl' => 'Alan kodu: 2–6 hane.',
            'val.phone_nummer' => 'Numara: 3–12 hane.',
            'val.email_invalid' => 'Geçersiz e-posta.',
            'val.email_no_iserv' => 'Lütfen özel e-posta kullanın (IServ değil).',
            'val.max_1500' => 'En fazla 1500 karakter.',
            'val.kontakt_row_name_missing' => 'İsim/tanım eksik',
            'val.kontakt_row_tel_or_mail'  => 'Telefon VEYA e-posta girin',
            'val.kontakt_row_mail_invalid' => 'Geçersiz e-posta',
            'val.kontakt_row_tel_invalid'  => 'Geçersiz telefon',
        ],

        // =======================
        // FA: in 'fa' => [ ... ] einfügen (komplett)
        // =======================
        'fa' => [
        
            // =======================
            // STEP Start: Index (FA)
            // =======================
            'index.title' => 'ثبت‌نام آنلاین – کلاس‌های زبان',
            'index.lead'  => 'این خدمت برای افرادی است که تازه به اولدن‌بورگ آمده‌اند. این فرم به ما کمک می‌کند با شما تماس بگیریم و گزینه‌های مناسب را پیدا کنیم.',
            'index.bullets' => [
                'لطفاً اطلاعات تماس و مدارک شناسایی را (در صورت وجود) آماده داشته باشید.',
                'می‌توانید فرم را به چند زبان تکمیل کنید.',
                'داده‌های شما مطابق GDPR محرمانه پردازش می‌شود.',
            ],
            'index.info_p' => [
                'دانش‌آموز عزیز،',
                'با این درخواست برای یک جایگاه در کلاس یادگیری زبان «BES زبان و ادغام» در یکی از مدارس فنی‌وحرفه‌ای (BBS) در اولدن‌بورگ اقدام می‌کنید. شما برای یک BBS مشخص درخواست نمی‌دهید. بعد از ۲۰ فوریه به شما اطلاع داده می‌شود که کدام مدرسه شما را در این کلاس می‌پذیرد.',
                'پذیرش فقط در صورت داشتن شرایط زیر ممکن است:',
            ],
            'index.info_bullets' => [
                'به تقویت فشرده زبان آلمانی نیاز دارید (سطح زبان آلمانی زیر B1).',
                'در آغاز سال تحصیلی آینده حداکثر ۳ سال است که در آلمان هستید.',
                'در تاریخ ۳۰ سپتامبر امسال سن شما حداقل ۱۶ و حداکثر ۱۸ سال است.',
                'در سال تحصیلی آینده مشمول تحصیل اجباری هستید.',
            ],
            'index.access_title' => 'حریم خصوصی و دسترسی',
            'index.access_intro' => 'می‌توانید با ایمیل یا بدون ایمیل ادامه دهید. دسترسی به درخواست‌های ذخیره‌شده فقط با کُد دسترسی شخصی (Token) و تاریخ تولد امکان‌پذیر است.',
            'index.access_points' => [
                '<strong>با ایمیل:</strong> یک کُد تأیید دریافت می‌کنید و می‌توانید چند درخواست ایجاد کنید و بعداً دوباره آن‌ها را باز کنید.',
                '<strong>بدون ایمیل:</strong> یک کُد دسترسی شخصی (Access-Token) دریافت می‌کنید. لطفاً آن را یادداشت/عکس کنید — بدون ایمیل تأییدشده، بازیابی ممکن نیست.',
            ],
        
            'index.btn_noemail' => 'ادامه بدون ایمیل',
            'index.btn_create'  => 'ادامه با ایمیل',
            'index.btn_load'    => 'دسترسی به درخواست(ها)',
            'index.lang_label'  => 'زبان / Language:',
        
            // =======================
            // STEP 1/4: PERSONAL (FA)
            // =======================
            'personal.page_title' => 'مرحله ۱/۴ – اطلاعات شخصی',
            'personal.h1' => 'مرحله ۱/۴ – اطلاعات شخصی',
            'personal.required_hint' => 'فیلدهای اجباری با کادر آبی مشخص شده‌اند.',
            'personal.form_error_hint' => 'لطفاً فیلدهای علامت‌گذاری‌شده را بررسی کنید.',
        
            'personal.alert_email_title' => 'ورود با ایمیل فعال است:',
            'personal.alert_email_line1' => 'با آدرس ایمیل {email} وارد شده‌اید.',
            'personal.alert_email_line2' => 'این ایمیل فقط برای کُد دسترسی (Access-Token) و برای پیدا کردن دوباره درخواست شما استفاده می‌شود.',
            'personal.alert_email_line3' => 'در پایین می‌توانید ایمیل دانش‌آموز را (در صورت وجود) وارد کنید.',
        
            'personal.alert_noemail_title' => 'توجه (بدون ایمیل):',
            'personal.alert_noemail_body' => 'لطفاً کُد دسترسی (Access-Token) را که بعد از ذخیره این صفحه نمایش داده می‌شود یادداشت کنید یا از آن عکس بگیرید. بدون ایمیل تأییدشده، بازیابی فقط با توکن + تاریخ تولد ممکن است.',
        
            'personal.label.name' => 'نام خانوادگی',
            'personal.label.vorname' => 'نام',
            'personal.label.geschlecht' => 'جنسیت',
            'personal.gender.m' => 'مرد',
            'personal.gender.w' => 'زن',
            'personal.gender.d' => 'متنوع',
        
            'personal.label.geburtsdatum' => 'تاریخ تولد',
            'personal.label.geburtsdatum_hint' => '(روز.ماه.سال)',
            'personal.placeholder.geburtsdatum' => 'روز.ماه.سال',
        
            'personal.age_hint' => 'توجه: اگر در تاریخ 30.09.{year} کمتر از 16 سال یا بیشتر از 18 سال دارید، امکان پذیرش در کلاس زبان BBS وجود ندارد. لطفاً برای کلاس دیگری اینجا درخواست دهید:',
            'personal.age_redirect_msg' => "توجه: اگر در تاریخ 30.09.{year} کمتر از 16 سال یا بیشتر از 18 سال دارید، امکان پذیرش در کلاس زبان BBS وجود ندارد. لطفاً برای کلاس دیگری در BBS اینجا درخواست دهید:\n{url}",
        
            'personal.label.geburtsort_land' => 'محل / کشور تولد',
            'personal.label.staatsang' => 'تابعیت',
        
            'personal.label.strasse' => 'خیابان، شماره',
            'personal.label.plz' => 'کد پستی',
            'personal.plz_choose' => '– لطفاً انتخاب کنید –',
            'personal.plz_hint' => 'فقط اولدن‌بورگ (Oldb).',
            'personal.label.wohnort' => 'شهر',
        
            'personal.label.telefon' => 'شماره تلفن',
            'personal.label.telefon_vorwahl_help' => 'کد منطقه با/بدون 0',
            'personal.label.telefon_nummer_help' => 'شماره',
            'personal.placeholder.telefon_vorwahl' => '(0)441',
            'personal.placeholder.telefon_nummer' => '123456',
        
            'personal.label.email' => 'آدرس ایمیل دانش‌آموز (اختیاری، نه ایمیل IServ)',
            'personal.email_help' => 'این ایمیل متعلق به دانش‌آموز است (در صورت وجود) و مستقل از ایمیل ورود برای کُد دسترسی است.',
            'personal.placeholder.email' => 'name@example.org',
        
            'personal.label.kontakte' => 'اطلاعات تماس بیشتر',
            'personal.kontakte_hint' => '(مثلاً والدین، سرپرست، مؤسسه)',
            'personal.kontakte_error' => 'لطفاً اطلاعات تماس‌های اضافی را بررسی کنید.',
            'personal.kontakte_add' => '+ افزودن مخاطب',
            'personal.kontakte_remove_title' => 'حذف مخاطب',
        
            'personal.table.role' => 'نقش',
            'personal.table.name' => 'نام / مؤسسه',
            'personal.table.tel'  => 'تلفن',
            'personal.table.mail' => 'ایمیل',
            'personal.table.note_header' => 'یادداشت',
            'personal.placeholder.kontakt_name' => 'نام یا عنوان',
            'personal.placeholder.kontakt_tel'  => '+49 …',
            'personal.placeholder.kontakt_note' => 'مثلاً زمان دسترسی، زبان، توضیحات',
        
            'personal.contact_role.none' => '–',
            'personal.contact_role.mutter' => 'مادر',
            'personal.contact_role.vater' => 'پدر',
            'personal.contact_role.elternteil' => 'والد',
            'personal.contact_role.betreuer' => 'سرپرست',
            'personal.contact_role.einrichtung' => 'مؤسسه',
            'personal.contact_role.sonstiges' => 'سایر',
        
            'personal.label.weitere_angaben' => 'اطلاعات دیگر (مثلاً وضعیت حمایت):',
            'personal.placeholder.weitere_angaben' => 'اینجا می‌توانید مثلاً نیازهای حمایتی ویژه، نیازهای پشتیبانی آموزشی یا توضیحات دیگر را وارد کنید.',
            'personal.weitere_angaben_help' => 'اختیاری. حداکثر 1500 کاراکتر.',
            'personal.btn.cancel' => 'لغو',
            'personal.btn.next' => 'بعدی',
        
            'personal.dsgvo_text_prefix' => '',
            'personal.dsgvo_link_text' => 'اطلاعیه حریم خصوصی',
            'personal.dsgvo_text_suffix' => 'را خوانده‌ام و موافقم.',
        
            // =====================
            // STEP 2/4: SCHOOL (FA)
            // =====================
            'school.page_title' => 'مرحله ۲/۴ – مدرسه و علایق',
            'school.h1' => 'مرحله ۲/۴ – مدرسه و علایق',
            'school.required_hint' => 'فیلدهای اجباری با کادر آبی مشخص شده‌اند.',
            'school.form_error_hint' => 'لطفاً فیلدهای علامت‌گذاری‌شده را بررسی کنید.',
        
            'school.top_hint_title' => 'توجه:',
            'school.top_hint_body'  => 'اگر <u>بیش از ۳ سال</u> در آلمان هستید یا سطح زبان آلمانی شما <u>B1</u> یا بالاتر است، امکان پذیرش در کلاس زبان BBS وجود ندارد. لطفاً برای کلاس دیگر در BBS از اینجا اقدام کنید:',
            'school.bbs_link_label' => 'https://bbs-ol.de/',
        
            'school.autohints_title' => 'راهنماها',
        
            'school.label.schule_aktuell' => 'مدرسه فعلی',
            'school.search_placeholder'   => 'جستجوی مدرسه… (نام، خیابان، کد پستی)',
            'school.select_choose'        => 'لطفاً انتخاب کنید…',
            'school.option_other'         => 'دیگر / در لیست نیست',
            'school.other_placeholder'    => 'نام مدرسه، خیابان، شهر (متن آزاد)',
        
            'school.label.teacher'        => 'معلم مسئول',
            'school.label.teacher_mail'   => 'ایمیل معلم مسئول',
        
            'school.label.herkunft'       => 'آیا در کشور مبدأ به مدرسه رفته‌اید؟',
            'school.yes'                  => 'بله',
            'school.no'                   => 'خیر',
            'school.label.herkunft_years' => 'اگر بله: چند سال؟',
        
            'school.label.since'          => 'از چه زمانی در آلمان در مدرسه هستید؟',
            'school.since_month'          => 'ماه (MM)',
            'school.since_year_ph'        => 'سال (YYYY)',
            'school.since_help'           => 'یا ماه+سال را وارد کنید <strong>یا</strong> از فیلد متن آزاد استفاده کنید.',
            'school.label.since_text'     => 'جایگزین: متن آزاد (مثلاً «از پاییز 2023»)',
        
            'school.label.years_in_de'    => 'چند سال است که در آلمان هستید؟',
            'school.years_in_de_help'     => 'توجه: بیشتر از ۳ سال → لطفاً از طریق {link} برای کلاس‌های عادی BBS اقدام کنید.',
        
            'school.label.family_lang'    => 'زبان خانواده / زبان اول',
        
            'school.label.level'          => 'سطح زبان آلمانی شما چیست؟',
            'school.level_choose'         => 'لطفاً انتخاب کنید…',
            'school.level_help'           => 'توجه: B1 یا بالاتر → لطفاً از طریق {link} برای کلاس‌های عادی BBS اقدام کنید.',
        
            'school.label.interests'      => 'علایق (حداقل ۱، حداکثر ۲)',
        
            'school.btn.back'             => 'بازگشت',
            'school.btn.next'             => 'ادامه',
        
            // ---------------------
            // Validierung / Errors
            // ---------------------
            'val.school_free_required' => 'لطفاً نام مدرسه را (متن آزاد) وارد کنید.',
            'val.school_invalid'       => 'لطفاً یک مدرسه معتبر انتخاب کنید یا «دیگر / در لیست نیست» را انتخاب کنید.',
        
            'val.since_required'       => 'لطفاً ماه+سال یا متن آزاد را وارد کنید.',
            'val.month_invalid'        => 'ماه باید بین 01 تا 12 باشد.',
            'val.year_invalid'         => 'لطفاً یک سال معتبر وارد کنید.',
            'val.number_required'      => 'لطفاً یک عدد وارد کنید.',
            'val.choose'               => 'لطفاً انتخاب کنید.',
            'val.herkunft_years'       => 'لطفاً تعداد سال‌ها را وارد کنید.',
        
            'val.level_invalid'        => 'انتخاب نامعتبر است.',
        
            'val.interests_min1'       => 'لطفاً حداقل ۱ مورد را انتخاب کنید.',
            'val.interests_max2'       => 'لطفاً حداکثر ۲ مورد را انتخاب کنید.',
        
            // ---------------------
            // JS Live-Hinweise
            // ---------------------
            'js.hint_years_gt3'  => 'توجه: شما بیش از ۳ سال در آلمان هستید. لطفاً از طریق {link} اقدام کنید.',
            'js.hint_level_b1p'  => 'توجه: با سطح B1 یا بالاتر لطفاً از طریق {link} برای BBS معمولی اقدام کنید.',
        
            // =========================
            // STEP 3/4: UPLOAD (FA)
            // =========================
            'upload.page_title' => 'مرحله ۳/۴ – مدارک (اختیاری)',
            'upload.h1'         => 'مرحله ۳/۴ – مدارک (اختیاری)',
        
            'upload.intro'      => 'در اینجا می‌توانید مدارک را بارگذاری کنید. فرمت‌های مجاز <strong>PDF</strong>، <strong>JPG</strong> و <strong>PNG</strong> هستند. حداکثر حجم هر فایل <strong>{max_mb} MB</strong> است.',
        
            'upload.type.zeugnis'    => 'کارنامه آخر نیم‌سال',
            'upload.type.lebenslauf' => 'رزومه (CV)',
            'upload.type_hint'       => '(PDF/JPG/PNG، حداکثر {max_mb} MB)',
        
            'upload.btn.remove' => 'حذف',
            'upload.btn.back'   => 'بازگشت',
            'upload.btn.next'   => 'ادامه',
        
            'upload.saved_prefix' => 'قبلاً ذخیره شده:',
            'upload.empty'        => 'هنوز فایلی بارگذاری نشده است.',
            'upload.saved_html'   => 'قبلاً ذخیره شده: <strong>{filename}</strong>، {size_kb} KB، بارگذاری در {uploaded_at}',
        
            'upload.checkbox.zeugnis_spaeter' => 'کارنامه را بعد از پذیرش ارائه می‌دهم.',
        
            'upload.flash.no_access' => 'دسترسی معتبر یافت نشد. لطفاً فرایند را از ابتدا شروع کنید.',
            'upload.flash.saved'     => 'اطلاعات بارگذاری ذخیره شد.',
        
            'upload.js.uploading'          => 'در حال بارگذاری…',
            'upload.js.unexpected'         => 'پاسخ غیرمنتظره از سرور.',
            'upload.js.upload_failed'      => 'بارگذاری ناموفق بود.',
            'upload.js.delete_confirm'     => 'آیا واقعاً می‌خواهید فایل بارگذاری‌شده را حذف کنید؟',
            'upload.js.delete_failed'      => 'حذف ناموفق بود.',
            'upload.js.remove_confirm_btn' => 'حذف فایل؟',
        
            // AJAX / Fehlertexte
            'upload.ajax.invalid_method'   => 'روش نامعتبر',
            'upload.ajax.invalid_csrf'     => 'توکن CSRF نامعتبر',
            'upload.ajax.no_access'        => 'دسترسی معتبر وجود ندارد.',
            'upload.ajax.invalid_field'    => 'فیلد نامعتبر',
            'upload.ajax.no_file_sent'     => 'فایلی ارسال نشد',
            'upload.ajax.no_file_selected' => 'فایلی انتخاب نشده است',
            'upload.ajax.upload_error'     => 'خطای بارگذاری (کد {code})',
            'upload.ajax.too_large'        => 'فایل بزرگ‌تر از {max_mb} MB است',
            'upload.ajax.mime_only'        => 'فقط PDF، JPG یا PNG مجاز است',
            'upload.ajax.ext_only'         => 'پسوند فایل نامعتبر است (فقط pdf/jpg/jpeg/png)',
            'upload.ajax.cannot_save'      => 'ذخیره فایل ممکن نیست',
            'upload.ajax.unknown_action'   => 'عملیات ناشناخته',
            'upload.ajax.server_error'     => 'خطای سرور هنگام بارگذاری',
        
            // =========================
            // STEP 4/4: REVIEW (FA)
            // =========================
            'review.page_title' => 'مرحله ۴/۴ – خلاصه و ارسال درخواست',
        
            'review.h1'      => 'مرحله ۴/۴ – خلاصه و ارسال درخواست',
            'review.subhead' => 'لطفاً اطلاعات خود را بررسی کنید. با کلیک روی «ارسال درخواست»، داده‌ها ارسال می‌شوند.',
        
            'review.readonly_alert' => 'این درخواست قبلاً ارسال شده است. اطلاعات فقط قابل مشاهده است و نمی‌توان آن را تغییر داد یا دوباره ارسال کرد.',
        
            'review.info.p1' => 'دانش‌آموز عزیز،',
            'review.info.p2' => 'با کلیک روی <strong>«ارسال درخواست»</strong> شما برای <strong>BES زبان و ادغام</strong> در یکی از BBSهای اولدن‌بورگ درخواست داده‌اید.',
            'review.info.p3' => 'این هنوز ثبت‌نام نهایی نیست، بلکه یک <strong>درخواست</strong> است. بعد از <strong>20.02.</strong> به شما اطلاع داده می‌شود که آیا / در کدام BBS پذیرفته می‌شوید. لطفاً صندوق پستی و ایمیل خود را مرتب بررسی کنید. مطمئن شوید نام شما روی صندوق پستی قابل مشاهده است.',
            'review.info.p4' => 'با پذیرش مدرسه از شما خواسته می‌شود این مدارک را ارائه کنید (اگر امروز هنوز بارگذاری نکرده‌اید):',
            'review.info.li1' => 'کارنامه آخر نیم‌سال',
        
            // Accordion Überschriften
            'review.acc.personal' => 'اطلاعات شخصی',
            'review.acc.school'   => 'مدرسه و علایق',
            'review.acc.uploads'  => 'مدارک',
        
            // Labels: Personal
            'review.lbl.name'            => 'نام خانوادگی',
            'review.lbl.vorname'         => 'نام',
            'review.lbl.geschlecht'      => 'جنسیت',
            'review.lbl.geburtsdatum'    => 'تاریخ تولد',
            'review.lbl.geburtsort'      => 'محل / کشور تولد',
            'review.lbl.staatsang'       => 'تابعیت',
            'review.lbl.strasse'         => 'خیابان، شماره',
            'review.lbl.plz_ort'         => 'کد پستی / شهر',
            'review.lbl.telefon'         => 'تلفن',
            'review.lbl.email'           => 'ایمیل (دانش‌آموز، اختیاری)',
            'review.lbl.weitere_angaben' => 'اطلاعات دیگر (مثلاً وضعیت حمایت)',
        
            'review.contacts.title'    => 'مخاطبین بیشتر',
            'review.contacts.optional' => 'اختیاری',
            'review.contacts.none'     => '–',
        
            'review.contacts.th.role' => 'نقش',
            'review.contacts.th.name' => 'نام / مؤسسه',
            'review.contacts.th.tel'  => 'تلفن',
            'review.contacts.th.mail' => 'ایمیل',
            'review.contacts.note'    => 'یادداشت:',
        
            // Labels: School
            'review.lbl.school_current' => 'مدرسه فعلی',
            'review.lbl.klassenlehrer'  => 'معلم مسئول',
            'review.lbl.mail_lehrkraft' => 'ایمیل معلم',
            'review.lbl.since'          => 'از چه زمانی در مدرسه',
            'review.lbl.years_de'       => 'سال در آلمان',
            'review.lbl.family_lang'    => 'زبان خانواده / زبان اول',
            'review.lbl.de_level'       => 'سطح زبان آلمانی',
            'review.lbl.school_origin'  => 'مدرسه در کشور مبدأ',
            'review.lbl.years_origin'   => 'سال‌های مدرسه در کشور مبدأ',
            'review.lbl.interests'      => 'علایق',
        
            // Uploads
            'review.lbl.zeugnis'         => 'کارنامه نیم‌سال',
            'review.lbl.lebenslauf'      => 'رزومه (CV)',
            'review.lbl.later'           => 'بعداً ارائه می‌شود',
            'review.badge.uploaded'      => 'بارگذاری شد',
            'review.badge.not_uploaded'  => 'بارگذاری نشد',
            'review.yes'                 => 'بله',
            'review.no'                  => 'خیر',
        
            // Buttons / Actions
            'review.btn.home'   => 'صفحه اصلی',
            'review.btn.newapp' => 'ارسال درخواست جدید',
            'review.btn.back'   => 'بازگشت',
            'review.btn.submit' => 'ارسال درخواست',
        
            // Errors / Flash / Systemtexte
            'review.err.invalid_request'       => 'درخواست نامعتبر است.',
            'review.flash.already_submitted'   => 'این درخواست قبلاً ارسال شده و نمی‌تواند دوباره ارسال یا تغییر داده شود.',
            'review.flash.no_token'            => 'کُد دسترسی معتبر وجود ندارد. لطفاً فرایند را از ابتدا شروع کنید.',
            'review.err.not_found_token'       => 'درخواستی برای این توکن پیدا نشد.',
            'review.flash.submit_error'        => 'هنگام ارسال خطایی رخ داد. لطفاً بعداً دوباره تلاش کنید.',
        
            // Gender fallback
            'review.gender.m' => 'مرد',
            'review.gender.w' => 'زن',
            'review.gender.d' => 'متنوع',
        
            // Fallback Anzeige
            'review.value.empty' => '–',
        
            // =========================
            // STATUS (FA)
            // =========================
            'status.hdr_title'   => 'درخواست با موفقیت ذخیره شد',
            'status.hdr_message' => 'درخواست شما ارسال شد.',
        
            'status.h1' => 'درخواست شما با موفقیت ذخیره شد.',
        
            'status.success.title' => 'سپاسگزاریم!',
            'status.success.body'  => 'درخواست شما ارسال شد و اکنون در حال بررسی است.',
        
            'status.info.title' => 'نکته مهم',
            'status.info.body'  => '<em>[جای‌نگهدار: متن از مشتری خواهد آمد]</em>',
        
            'status.btn.pdf'    => 'دانلود / چاپ PDF',
            'status.btn.newapp' => 'شروع درخواست جدید',
            'status.btn.home'   => 'صفحه اصلی',
        
            'status.ref' => 'مرجع: درخواست #{id}',
        
            'status.err.invalid_request' => 'درخواست نامعتبر است.',
        
            // =========================
            // PDF (FA)
            // =========================
            'pdf.err.autoload_missing' => 'Composer Autoload پیدا نشد. لطفاً "composer install" را اجرا کنید.',
            'pdf.err.no_token'         => 'کُد دسترسی معتبر وجود ندارد. لطفاً فرایند را از ابتدا شروع کنید.',
            'pdf.err.not_found'        => 'درخواست پیدا نشد.',
            'pdf.err.server'           => 'خطای سرور هنگام تولید PDF.',
        
            'pdf.header_title' => 'درخواست – خلاصه',
            'pdf.footer_auto'  => 'سند تولیدشده به‌صورت خودکار',
            'pdf.footer_page'  => 'صفحه {cur} / {max}',
        
            'pdf.meta.ref'        => 'درخواست #{id}',
            'pdf.meta.created_at' => 'ایجاد شده در',
            'pdf.meta.status'     => 'وضعیت',
        
            'pdf.top.title'        => 'نمای کلی',
            'pdf.top.name'         => 'نام',
            'pdf.top.reference'    => 'مرجع',
            'pdf.top.generated'    => 'ایجاد شده در',
            'pdf.top.hint'         => 'توجه',
            'pdf.top.keep_note'    => 'لطفاً این سند را برای مدارک خود نگه دارید.',
            'pdf.hint_placeholder' => '[جای‌نگهدار: متن از مشتری خواهد آمد]',
        
            'pdf.sec1.title' => '1) اطلاعات شخصی',
            'pdf.sec2.title' => '2) اطلاعات تماس بیشتر',
            'pdf.sec3.title' => '3) مدرسه و علایق',
            'pdf.sec4.title' => '4) مدارک',
        
            'pdf.lbl.name'           => 'نام خانوادگی',
            'pdf.lbl.vorname'        => 'نام',
            'pdf.lbl.gender'         => 'جنسیت',
            'pdf.lbl.dob'            => 'تاریخ تولد',
            'pdf.lbl.birthplace'     => 'محل/کشور تولد',
            'pdf.lbl.nationality'    => 'تابعیت',
            'pdf.lbl.address'        => 'نشانی',
            'pdf.lbl.phone'          => 'تلفن',
            'pdf.lbl.email_optional' => 'ایمیل (اختیاری)',
            'pdf.lbl.more'           => 'اطلاعات دیگر',
        
            'pdf.lbl.school_current' => 'مدرسه فعلی',
            'pdf.lbl.teacher'        => 'معلم',
            'pdf.lbl.teacher_email'  => 'ایمیل معلم',
            'pdf.lbl.since_school'   => 'از چه زمانی در مدرسه',
            'pdf.lbl.years_in_de'    => 'از چه زمانی در آلمان',
            'pdf.lbl.family_lang'    => 'زبان خانواده',
            'pdf.lbl.de_level'       => 'سطح زبان آلمانی',
            'pdf.lbl.school_origin'  => 'مدرسه در کشور مبدأ',
            'pdf.lbl.years_origin'   => 'سال‌های مدرسه در کشور مبدأ',
            'pdf.lbl.interests'      => 'علایق',
        
            'pdf.lbl.report'       => 'کارنامه نیم‌سال',
            'pdf.lbl.cv'           => 'رزومه (CV)',
            'pdf.lbl.report_later' => 'کارنامه بعداً ارائه می‌شود',
        
            'pdf.uploaded'     => 'بارگذاری شد',
            'pdf.not_uploaded' => 'بارگذاری نشد',
        
            'pdf.contacts.none'    => '–',
            'pdf.contacts.th.role' => 'نقش',
            'pdf.contacts.th.name' => 'نام/مؤسسه',
            'pdf.contacts.th.tel'  => 'تلفن',
            'pdf.contacts.th.mail' => 'ایمیل',
            'pdf.contacts.th.note' => 'یادداشت',
        
            'pdf.gender.m' => 'مرد',
            'pdf.gender.w' => 'زن',
            'pdf.gender.d' => 'متنوع',
        
            'pdf.yes' => 'بله',
            'pdf.no'  => 'خیر',
        
            'pdf.sec4.note' => 'این سند خلاصه‌ای است که به‌صورت خودکار از داده‌های واردشده تولید شده است.',
            'pdf.filename_prefix' => 'درخواست',
        
            // =========================
            // ACCESS_CREATE (FA)
            // =========================
            'access_create.title'         => 'ادامه با ایمیل',
            'access_create.lead'          => 'می‌توانید با دسترسی موجود وارد شوید یا دسترسی جدید ایجاد کنید.',
            'access_create.tabs_login'    => 'ورود',
            'access_create.tabs_register' => 'ایجاد دسترسی جدید',
        
            'access_create.login_title' => 'ورود (دسترسی موجود)',
            'access_create.login_text'  => 'لطفاً آدرس ایمیل و رمز عبور خود را وارد کنید.',
            'access_create.email_label' => 'آدرس ایمیل',
            'access_create.pass_label'  => 'رمز عبور',
            'access_create.login_btn'   => 'ورود',
            'access_create.login_err'   => 'ایمیل/رمز عبور نادرست است یا دسترسی تأیید نشده است.',
        
            'access_create.reg_title'     => 'ایجاد دسترسی جدید',
            'access_create.reg_text'      => 'یک کُد تأیید ۶ رقمی برای شما ارسال می‌کنیم. پس از تأیید موفق، رمز عبور از طریق ایمیل ارسال می‌شود.',
            'access_create.consent_label' => 'موافقت می‌کنم که ایمیل من برای فرایند ثبت‌نام استفاده شود.',
            'access_create.send_btn'      => 'ارسال کُد',
            'access_create.code_label'    => 'کُد تأیید',
            'access_create.verify_btn'    => 'بررسی کُد',
            'access_create.resend'        => 'ارسال دوباره کُد',
        
            'access_create.info_sent'    => 'یک کُد برای شما ارسال کردیم. لطفاً پوشه اسپم را هم بررسی کنید.',
            'access_create.ok_verified'  => 'ایمیل تأیید شد. رمز عبور ارسال شد. اکنون می‌توانید وارد شوید.',
            'access_create.email_in_use' => 'برای این ایمیل قبلاً دسترسی ایجاد شده است. لطفاً وارد شوید.',
        
            'access_create.error_email'     => 'لطفاً یک آدرس ایمیل معتبر وارد کنید.',
            'access_create.error_consent'   => 'لطفاً با استفاده از ایمیل خود موافقت کنید.',
            'access_create.error_rate'      => 'تعداد تلاش‌ها زیاد است. لطفاً کمی صبر کنید و دوباره تلاش کنید.',
            'access_create.error_code'      => 'کُد نامعتبر است یا منقضی شده است.',
            'access_create.error_resend'    => 'ارسال مجدد ممکن نیست. لطفاً دوباره از ابتدا شروع کنید.',
            'access_create.error_mail_send' => 'ارسال ایمیل ناموفق بود. لطفاً بعداً دوباره تلاش کنید.',
            'access_create.error_db'        => 'خطای سرور (DB).',
        
            'access_create.back'   => 'بازگشت',
            'access_create.cancel' => 'لغو',
        
            'access_create.mail_subject' => 'رمز عبور شما برای ثبت‌نام آنلاین',
            'access_create.mail_body'    => "دسترسی شما ایجاد شد.\n\nایمیل: {email}\nرمز عبور: {password}\n\nلطفاً رمز عبور را در جای امن نگه دارید.",
        
            // =========================
            // ACCESS_PORTAL (FA)
            // =========================
            'access_portal.title'    => 'درخواست‌های من',
            'access_portal.lead'     => 'در اینجا درخواست‌های خود را می‌بینید. می‌توانید یک درخواست را ادامه دهید یا درخواست جدید شروع کنید.',
            'access_portal.max_hint' => '{email} · حداکثر {max} درخواست',
        
            'access_portal.btn_new'    => 'شروع درخواست جدید',
            'access_portal.btn_open'   => 'باز کردن',
            'access_portal.btn_logout' => 'خروج',
        
            'access_portal.th_ref'     => 'شناسه',
            'access_portal.th_status'  => 'وضعیت',
            'access_portal.th_created' => 'ایجاد شد',
            'access_portal.th_updated' => 'به‌روزرسانی شد',
            'access_portal.th_token'   => 'توکن',
            'access_portal.th_action'  => 'عملیات',
        
            'access_portal.status_draft'     => 'پیش‌نویس',
            'access_portal.status_submitted' => 'ارسال‌شده',
            'access_portal.status_withdrawn' => 'پس‌گرفته‌شده',
        
            'access_portal.limit_reached' => 'به حداکثر تعداد درخواست برای این ایمیل رسیده‌اید.',
            'access_portal.no_apps'       => 'هنوز درخواستی وجود ندارد.',
            'access_portal.err_generic'   => 'خطایی رخ داد.',
            'access_portal.csrf_invalid'  => 'درخواست نامعتبر است.',
        
            // =========================
            // ACCESS_LOGIN (FA)
            // =========================
            'access_login.title'             => 'دسترسی به درخواست(ها)',
            'access_login.lead'              => 'در اینجا می‌توانید درخواست شروع‌شده یا ارسال‌شده را دوباره باز کنید.',
        
            'access_login.login_box_title'   => 'ورود با Access-Token',
            'access_login.login_box_text'    => 'لطفاً کُد دسترسی شخصی (Access-Token) و تاریخ تولد خود را وارد کنید.',
        
            'access_login.token_label'       => 'Access-Token',
            'access_login.dob_label'         => 'تاریخ تولد (روز.ماه.سال)',
        
            'access_login.login_btn'         => 'دسترسی',
            'access_login.back'              => 'بازگشت به صفحه اصلی',
        
            'access_login.login_ok'          => 'درخواست بارگذاری شد.',
            'access_login.login_error'       => 'ترکیب Access-Token و تاریخ تولد پیدا نشد.',
            'access_login.login_error_token' => 'لطفاً یک Access-Token معتبر وارد کنید.',
            'access_login.login_error_dob'   => 'لطفاً تاریخ تولد را با فرمت روز.ماه.سال وارد کنید.',
        
            'access_login.csrf_invalid'      => 'درخواست نامعتبر است.',
            'access_login.internal_error'    => 'خطای داخلی.',
            'access_login.load_error'        => 'هنگام بارگذاری درخواست خطایی رخ داد.',
        
            // =========================
            // PRIVACY (FA)
            // =========================
            'privacy.title' => 'حریم خصوصی',
            'privacy.h1'    => 'اطلاعیه حریم خصوصی برای درخواست آنلاین «BES زبان و ادغام»',
        
            'privacy.s1_title'     => '1. نهاد مسئول',
            'privacy.s1_body_html' => '<strong>شهر اولدن‌بورگ / مدارس فنی‌وحرفه‌ای</strong><br>(نام دقیق اداره/مدرسه، نشانی، تلفن، ایمیل وارد شود)',
        
            'privacy.s2_title'     => '2. مسئول حفاظت از داده‌ها',
            'privacy.s2_body_html' => '(اطلاعات تماس مسئول حفاظت از داده‌ها وارد شود)',
        
            'privacy.s3_title' => '3. اهداف پردازش',
            'privacy.s3_li1'   => 'دریافت و بررسی درخواست شما برای پذیرش در کلاس زبان («BES زبان و ادغام»)',
            'privacy.s3_li2'   => 'ارتباط با شما (سؤالات، اطلاع‌رسانی درباره تصمیم پذیرش)',
            'privacy.s3_li3'   => 'برنامه‌ریزی سازمانی مدرسه (تخصیص به یک BBS)',
        
            'privacy.s4_title' => '4. مبانی حقوقی',
            'privacy.s4_li1'   => 'GDPR ماده 6 بند 1 (e) همراه با مقررات مدرسه‌ای ایالت نیدرزاکسن',
            'privacy.s4_li2'   => 'GDPR ماده 6 بند 1 (c) (انجام تعهدات قانونی)',
            'privacy.s4_li3'   => 'GDPR ماده 6 بند 1 (a) (رضایت) در صورت ارائه اطلاعات/آپلودهای داوطلبانه',
        
            'privacy.s5_title' => '5. دسته‌های داده‌های شخصی',
            'privacy.s5_li1'   => 'اطلاعات پایه (نام خانوادگی، نام، تاریخ تولد، تابعیت، نشانی، اطلاعات تماس)',
            'privacy.s5_li2'   => 'اطلاعات مدرسه‌ای (مدرسه فعلی، سطح زبان، علایق)',
            'privacy.s5_li3'   => 'مدارک اختیاری (مثلاً کارنامه آخر نیم‌سال)',
            'privacy.s5_li4'   => 'مخاطبین اضافی (والدین/سرپرست/مؤسسات)',
        
            'privacy.s6_title' => '6. گیرندگان',
            'privacy.s6_body'  => 'در حوزه مسئولیت شهر اولدن‌بورگ و مدارس فنی‌وحرفه‌ای. انتقال به اشخاص ثالث فقط در صورت الزام قانونی (مثلاً مراجع آموزشی) یا با رضایت شما انجام می‌شود.',
        
            'privacy.s7_title' => '7. انتقال به کشورهای ثالث',
            'privacy.s7_body'  => 'هیچ انتقالی به کشورهای ثالث انجام نمی‌شود.',
        
            'privacy.s8_title' => '8. مدت نگهداری',
            'privacy.s8_body'  => 'داده‌های شما در طول فرایند درخواست/پذیرش و مطابق مهلت‌های قانونی نگهداری و سپس حذف می‌شود.',
        
            'privacy.s9_title' => '9. حقوق شما',
            'privacy.s9_li1'   => 'دسترسی (ماده 15)، اصلاح (16)، حذف (17)، محدودسازی (18)',
            'privacy.s9_li2'   => 'اعتراض (ماده 21) نسبت به پردازش در منافع عمومی',
            'privacy.s9_li3'   => 'پس‌گرفتن رضایت (ماده 7 بند 3) با اثر برای آینده',
            'privacy.s9_li4'   => 'حق شکایت نزد مرجع نظارتی: نماینده حفاظت از داده‌های ایالت نیدرزاکسن',
        
            'privacy.s10_title' => '10. میزبانی و لاگ‌ها',
            'privacy.s10_body'  => 'این برنامه روی سرورهای شهر یا در مرکز داده شهرداری اجرا می‌شود. فقط داده‌های فنی لازم پردازش می‌شوند (مثلاً لاگ‌های سرور برای رفع خطا). هیچ CDN خارجی استفاده نمی‌شود. فقط یک کوکی مربوط به زبان تنظیم می‌شود.',
        
            'privacy.s11_title'    => '11. کوکی‌ها',
            'privacy.s11_li1_html' => '<strong>lang</strong> – زبان انتخاب‌شده را ذخیره می‌کند (اعتبار ۱۲ ماه). هدف: سهولت استفاده.',
            'privacy.s11_li2'      => 'نشست PHP – برای روند فرم از نظر فنی ضروری است و با پایان نشست حذف می‌شود.',
        
            'privacy.stand_label' => 'نسخه',
            'privacy.stand_hint'  => 'لطفاً به‌طور منظم بررسی کنید که آیا تغییری ایجاد شده است یا خیر.',
            'privacy.back_home'   => 'بازگشت به صفحه اصلی',
        
            // =========================
            // IMPRINT (FA)
            // =========================
            'imprint.title' => 'اطلاعات ناشر',
            'imprint.h1'    => 'اطلاعات ناشر',
        
            'imprint.s1_title'     => 'ارائه‌دهنده خدمات',
            'imprint.s1_body_html' => '<strong>شهر ***</strong><br>مدارس فنی‌وحرفه‌ای<br>(نشانی دقیق وارد شود)<br>تلفن: (تکمیل شود)<br>ایمیل: (تکمیل شود)',
        
            'imprint.s2_title'     => 'نماینده قانونی',
            'imprint.s2_body_html' => '(مثلاً شهردار شهر ****<br>یا مدیریت هر BBS)',
        
            'imprint.s3_title'     => 'مسئول محتوا طبق § 18 Abs. 2 MStV',
            'imprint.s3_body_html' => '(نام، سمت، تماس؛ مثلاً مدیریت BBS یا روابط عمومی)',
        
            'imprint.s4_title'     => 'شماره شناسه مالیات بر ارزش افزوده',
            'imprint.s4_body_html' => '(در صورت وجود؛ در غیر این صورت این بخش می‌تواند حذف شود)',
        
            'imprint.s5_title' => 'مرجع ناظر',
            'imprint.s5_body'  => '(نهاد ناظر شهرداری / مرجع آموزشی ذی‌صلاح)',
        
            'imprint.s6_title' => 'مسئولیت محتوا',
            'imprint.s6_body'  => 'محتوای صفحات با دقت بسیار تهیه شده است. با این حال نمی‌توانیم مسئولیت کامل درستی، کامل بودن و به‌روز بودن را بپذیریم. به‌عنوان نهاد عمومی، طبق قوانین عمومی مسئول محتوای خود هستیم.',
        
            'imprint.s7_title' => 'مسئولیت لینک‌ها',
            'imprint.s7_body'  => 'این ارائه شامل محتوای خارجی که داده‌های شخصی را به اشخاص ثالث منتقل کند نیست. اگر به منابع اطلاعاتی دیگر نهادهای عمومی لینک داده شود، مسئولیتی در قبال محتوای آن‌ها نداریم.',
        
            'imprint.s8_title' => 'حقوق نشر',
            'imprint.s8_body'  => 'محتوا و آثار ایجادشده توسط شهر اولدن‌بورگ تحت حقوق نشر آلمان است. مشارکت‌های اشخاص ثالث مشخص شده‌اند. هرگونه تکثیر/پردازش/توزیع خارج از حدود حقوق نشر نیازمند اجازه کتبی است.',
        
            'imprint.stand_label' => 'نسخه',
            'imprint.stand_hint'  => 'این اطلاعات برای فرم آنلاین «BES زبان و ادغام» معتبر است.',
            'imprint.back_home'   => 'بازگشت به صفحه اصلی',
        
            // =========================
            // VERIFY_EMAIL (FA)
            // =========================
            'verify_email.title' => 'تأیید ایمیل',
            'verify_email.h1'    => 'تأیید ایمیل',
        
            'verify_email.lead_sent'    => 'یک کُد تأیید به {email} ارسال کردیم.',
            'verify_email.lead_generic' => 'لطفاً کُد تأییدی را که از طریق ایمیل دریافت کرده‌اید وارد کنید. اگر ایمیلی نمی‌بینید، می‌توانید کُد را دوباره ارسال کنید.',
        
            'verify_email.code_label'  => 'کُد تأیید (۶ رقمی)',
            'verify_email.email_label' => 'آدرس ایمیل شما',
        
            'verify_email.btn_verify' => 'تأیید',
            'verify_email.btn_resend' => 'ارسال دوباره کُد',
            'verify_email.hint_spam'  => 'لطفاً پوشه اسپم را هم بررسی کنید.',
        
            'verify_email.back' => 'بازگشت',
        
            'verify_email.csrf_invalid' => 'درخواست نامعتبر است.',
            'verify_email.ok_verified'  => 'ایمیل با موفقیت تأیید شد.',
            'verify_email.ok_sent'      => 'کُد جدید به {email} ارسال شد.',
        
            'verify_email.warn_cooldown'     => 'لطفاً قبل از درخواست مجدد کُد کمی صبر کنید.',
            'verify_email.error_send'        => 'ارسال ناموفق بود. لطفاً بعداً دوباره تلاش کنید.',
            'verify_email.error_email'       => 'لطفاً یک ایمیل معتبر وارد کنید.',
            'verify_email.error_no_session'  => 'فرایند تأیید فعالی یافت نشد. لطفاً کُد جدید درخواست کنید.',
            'verify_email.error_expired'     => 'کُد نامعتبر است یا منقضی شده است.',
            'verify_email.error_invalid'     => 'کُد نامعتبر است یا منقضی شده است.',
            'verify_email.error_code_format' => 'لطفاً یک کُد ۶ رقمی معتبر وارد کنید.',
            'verify_email.error_rate'        => 'تعداد تلاش‌ها زیاد است. لطفاً کُد جدید درخواست کنید.',
        
            // =========================
            // VALIDATION (FA) – global
            // =========================
            'val.required' => 'الزامی است.',
            'val.only_letters' => 'لطفاً فقط از حروف استفاده کنید.',
            'val.gender_choose' => 'لطفاً جنسیت را انتخاب کنید.',
            'val.date_format' => 'روز.ماه.سال',
            'val.date_invalid' => 'تاریخ نامعتبر است.',
            'val.plz_whitelist' => 'فقط کدهای پستی اولدن‌بورگ (26121–26135).',
            'val.phone_vorwahl' => 'کد منطقه: 2–6 رقم.',
            'val.phone_nummer' => 'شماره: 3–12 رقم.',
            'val.email_invalid' => 'ایمیل نامعتبر است.',
            'val.email_no_iserv' => 'لطفاً از ایمیل شخصی استفاده کنید (نه IServ).',
            'val.max_1500' => 'حداکثر 1500 کاراکتر.',
            'val.kontakt_row_name_missing' => 'نام/عنوان وارد نشده',
            'val.kontakt_row_tel_or_mail'  => 'تلفن یا ایمیل وارد کنید',
            'val.kontakt_row_mail_invalid' => 'ایمیل نامعتبر است',
            'val.kontakt_row_tel_invalid'  => 'تلفن نامعتبر است',
        ],

        // =======================
        // VN: in 'vn' => [ ... ] einfügen (komplett)
        // =======================
        'vn' => [
        
            // =======================
            // STEP Start: Index (VN)
            // =======================
            'index.title' => 'Chào mừng đến với đăng ký trực tuyến – Lớp học tiếng',
            'index.lead'  => 'Dịch vụ này dành cho những người mới đến Oldenburg. Biểu mẫu giúp chúng tôi liên hệ với bạn và tìm các lựa chọn phù hợp.',
            'index.bullets' => [
                'Vui lòng chuẩn bị thông tin liên hệ và giấy tờ tùy thân (nếu có).',
                'Bạn có thể điền biểu mẫu bằng nhiều ngôn ngữ.',
                'Dữ liệu của bạn được xử lý bảo mật theo GDPR.',
            ],
            'index.info_p' => [
                'Em học sinh thân mến,',
                'Với đơn này, bạn đăng ký một chỗ trong lớp học tiếng “BES Ngôn ngữ và Hội nhập” tại một trường dạy nghề (BBS) ở Oldenburg. Bạn không đăng ký vào một BBS cụ thể. Sau ngày 20 tháng 2, bạn sẽ được thông báo trường nào sẽ tiếp nhận bạn vào lớp.',
                'Bạn chỉ có thể được nhận nếu đáp ứng các điều kiện sau:',
            ],
            'index.info_bullets' => [
                'Bạn cần được hỗ trợ tiếng Đức chuyên sâu (trình độ tiếng Đức dưới B1).',
                'Vào đầu năm học tới, bạn ở Đức không quá 3 năm.',
                'Vào ngày 30/09 năm nay, bạn ít nhất 16 tuổi và không quá 18 tuổi.',
                'Trong năm học tới, bạn thuộc diện bắt buộc đi học.',
            ],
            'index.access_title' => 'Bảo mật & Truy cập',
            'index.access_intro' => 'Bạn có thể tiếp tục có hoặc không có địa chỉ email. Việc truy cập các đơn đã lưu chỉ có thể thực hiện bằng mã truy cập cá nhân (Token) và ngày sinh.',
            'index.access_points' => [
                '<strong>Có email:</strong> Bạn nhận mã xác nhận và có thể tạo nhiều đơn và mở lại sau.',
                '<strong>Không có email:</strong> Bạn nhận mã truy cập cá nhân (Access-Token). Vui lòng ghi lại/chụp ảnh mã này — nếu không có email đã xác minh thì không thể khôi phục.',
            ],
        
            'index.btn_noemail' => 'Tiếp tục không dùng email',
            'index.btn_create'  => 'Tiếp tục với email',
            'index.btn_load'    => 'Truy cập đơn đăng ký',
            'index.lang_label'  => 'Ngôn ngữ / Language:',
        
            // =======================
            // STEP 1/4: PERSONAL (VN)
            // =======================
            'personal.page_title' => 'Bước 1/4 – Thông tin cá nhân',
            'personal.h1' => 'Bước 1/4 – Thông tin cá nhân',
            'personal.required_hint' => 'Các trường bắt buộc được đánh dấu bằng viền màu xanh.',
            'personal.form_error_hint' => 'Vui lòng kiểm tra các trường được đánh dấu.',
        
            'personal.alert_email_title' => 'Đăng nhập bằng email đang hoạt động:',
            'personal.alert_email_line1' => 'Đã đăng nhập bằng địa chỉ email {email}.',
            'personal.alert_email_line2' => 'Email này chỉ được dùng cho mã truy cập (Access-Token) và để tìm lại đơn đăng ký của bạn.',
            'personal.alert_email_line3' => 'Bên dưới bạn có thể nhập email của học sinh (nếu có).',
        
            'personal.alert_noemail_title' => 'Lưu ý (không có email):',
            'personal.alert_noemail_body' => 'Vui lòng ghi lại/chụp ảnh mã truy cập (Access-Token) sẽ hiển thị sau khi lưu trang này. Nếu không có email đã xác minh, chỉ có thể khôi phục bằng token + ngày sinh.',
        
            'personal.label.name' => 'Họ',
            'personal.label.vorname' => 'Tên',
            'personal.label.geschlecht' => 'Giới tính',
            'personal.gender.m' => 'nam',
            'personal.gender.w' => 'nữ',
            'personal.gender.d' => 'khác',
        
            'personal.label.geburtsdatum' => 'Ngày sinh',
            'personal.label.geburtsdatum_hint' => '(DD.MM.YYYY)',
            'personal.placeholder.geburtsdatum' => 'DD.MM.YYYY',
        
            'personal.age_hint' => 'Lưu ý: Nếu vào ngày 30.09.{year} bạn dưới 16 tuổi hoặc trên 18 tuổi, bạn không thể được nhận vào lớp học tiếng của BBS. Vui lòng đăng ký lớp khác tại đây:',
            'personal.age_redirect_msg' => "Lưu ý: Nếu vào ngày 30.09.{year} bạn dưới 16 tuổi hoặc trên 18 tuổi, bạn không thể được nhận vào lớp học tiếng của BBS.\nVui lòng đăng ký một lớp khác tại BBS tại đây:\n{url}",
        
            'personal.label.geburtsort_land' => 'Nơi sinh / Quốc gia sinh',
            'personal.label.staatsang' => 'Quốc tịch',
        
            'personal.label.strasse' => 'Đường, số nhà',
            'personal.label.plz' => 'Mã bưu chính',
            'personal.plz_choose' => '– vui lòng chọn –',
            'personal.plz_hint' => 'Chỉ Oldenburg (Oldb).',
            'personal.label.wohnort' => 'Thành phố',
        
            'personal.label.telefon' => 'Số điện thoại',
            'personal.label.telefon_vorwahl_help' => 'Mã vùng có/không có số 0',
            'personal.label.telefon_nummer_help' => 'Số',
            'personal.placeholder.telefon_vorwahl' => '(0)441',
            'personal.placeholder.telefon_nummer' => '123456',
        
            'personal.label.email' => 'Địa chỉ email của học sinh (tùy chọn, không phải email IServ)',
            'personal.email_help' => 'Email này thuộc về học sinh (nếu có) và độc lập với email dùng cho mã truy cập.',
            'personal.placeholder.email' => 'name@example.org',
        
            'personal.label.kontakte' => 'Thông tin liên hệ bổ sung',
            'personal.kontakte_hint' => '(ví dụ: bố mẹ, người giám hộ, tổ chức)',
            'personal.kontakte_error' => 'Vui lòng kiểm tra các liên hệ bổ sung.',
            'personal.kontakte_add' => '+ Thêm liên hệ',
            'personal.kontakte_remove_title' => 'Xóa liên hệ',
        
            'personal.table.role' => 'Vai trò',
            'personal.table.name' => 'Tên / tổ chức',
            'personal.table.tel'  => 'Điện thoại',
            'personal.table.mail' => 'Email',
            'personal.table.note_header' => 'Ghi chú',
            'personal.placeholder.kontakt_name' => 'Tên hoặc mô tả',
            'personal.placeholder.kontakt_tel'  => '+49 …',
            'personal.placeholder.kontakt_note' => 'ví dụ: thời gian liên hệ, ngôn ngữ, ghi chú',
        
            'personal.contact_role.none' => '–',
            'personal.contact_role.mutter' => 'Mẹ',
            'personal.contact_role.vater' => 'Bố',
            'personal.contact_role.elternteil' => 'Phụ huynh',
            'personal.contact_role.betreuer' => 'Người giám hộ',
            'personal.contact_role.einrichtung' => 'Tổ chức',
            'personal.contact_role.sonstiges' => 'Khác',
        
            'personal.label.weitere_angaben' => 'Thông tin khác (ví dụ: nhu cầu hỗ trợ):',
            'personal.placeholder.weitere_angaben' => 'Tại đây bạn có thể ghi nhu cầu hỗ trợ đặc biệt, nhu cầu hỗ trợ giáo dục đặc biệt hoặc các ghi chú khác.',
            'personal.weitere_angaben_help' => 'Tùy chọn. Tối đa 1500 ký tự.',
            'personal.btn.cancel' => 'Hủy',
            'personal.btn.next' => 'Tiếp',
        
            'personal.dsgvo_text_prefix' => 'Tôi đã đọc',
            'personal.dsgvo_link_text' => 'thông tin bảo vệ dữ liệu',
            'personal.dsgvo_text_suffix' => 'và tôi đồng ý.',
        
            // =====================
            // STEP 2/4: SCHOOL (VN)
            // =====================
            'school.page_title' => 'Bước 2/4 – Trường học & Sở thích',
            'school.h1' => 'Bước 2/4 – Trường học & Sở thích',
            'school.required_hint' => 'Các trường bắt buộc được đánh dấu bằng viền màu xanh.',
            'school.form_error_hint' => 'Vui lòng kiểm tra các trường được đánh dấu.',
        
            'school.top_hint_title' => 'Lưu ý:',
            'school.top_hint_body'  => 'Nếu bạn ở Đức <u>hơn 3 năm</u> hoặc đã có trình độ tiếng Đức <u>B1</u> trở lên, bạn không thể được nhận vào lớp học tiếng của BBS. Vui lòng đăng ký một lớp khác của BBS tại đây:',
            'school.bbs_link_label' => 'https://bbs-ol.de/',
        
            'school.autohints_title' => 'Gợi ý',
        
            'school.label.schule_aktuell' => 'Trường hiện tại',
            'school.search_placeholder'   => 'Tìm trường… (tên, đường, mã bưu chính)',
            'school.select_choose'        => 'Vui lòng chọn…',
            'school.option_other'         => 'Khác / không có trong danh sách',
            'school.other_placeholder'    => 'Tên trường, đường, thành phố (tự nhập)',
        
            'school.label.teacher'        => 'Giáo viên phụ trách',
            'school.label.teacher_mail'   => 'Email giáo viên phụ trách',
        
            'school.label.herkunft'       => 'Bạn có đi học ở nước xuất xứ không?',
            'school.yes'                  => 'Có',
            'school.no'                   => 'Không',
            'school.label.herkunft_years' => 'Nếu có: bao nhiêu năm?',
        
            'school.label.since'          => 'Bạn học ở một trường tại Đức từ khi nào?',
            'school.since_month'          => 'Tháng (MM)',
            'school.since_year_ph'        => 'Năm (YYYY)',
            'school.since_help'           => 'Nhập tháng+năm <strong>hoặc</strong> dùng ô nhập tự do.',
            'school.label.since_text'     => 'Hoặc: nhập tự do (ví dụ: “từ mùa thu 2023”)',
        
            'school.label.years_in_de'    => 'Bạn ở Đức được bao nhiêu năm?',
            'school.years_in_de_help'     => 'Lưu ý: &gt; 3 năm → Vui lòng nộp đơn BBS thông thường qua {link}.',
        
            'school.label.family_lang'    => 'Ngôn ngữ gia đình / tiếng mẹ đẻ',
        
            'school.label.level'          => 'Trình độ tiếng Đức của bạn là gì?',
            'school.level_choose'         => 'Vui lòng chọn…',
            'school.level_help'           => 'Lưu ý: B1 trở lên → vui lòng nộp đơn BBS thông thường qua {link}.',
        
            'school.label.interests'      => 'Sở thích (ít nhất 1, tối đa 2)',
        
            'school.btn.back'             => 'Quay lại',
            'school.btn.next'             => 'Tiếp',
        
            // ---------------------
            // Validation / Errors
            // ---------------------
            'val.school_free_required' => 'Vui lòng nhập tên trường (tự nhập).',
            'val.school_invalid'       => 'Vui lòng chọn trường hợp lệ hoặc “Khác / không có trong danh sách”.',
        
            'val.since_required'       => 'Vui lòng nhập tháng+năm hoặc nội dung tự do.',
            'val.month_invalid'        => 'Tháng phải từ 01–12.',
            'val.year_invalid'         => 'Vui lòng nhập năm hợp lệ.',
            'val.number_required'      => 'Vui lòng nhập số.',
            'val.choose'               => 'Vui lòng chọn.',
            'val.herkunft_years'       => 'Vui lòng nhập số năm.',
        
            'val.level_invalid'        => 'Lựa chọn không hợp lệ.',
        
            'val.interests_min1'       => 'Vui lòng chọn ít nhất 1 lĩnh vực.',
            'val.interests_max2'       => 'Vui lòng chọn tối đa 2 lĩnh vực.',
        
            // ---------------------
            // JS Live hints
            // ---------------------
            'js.hint_years_gt3'  => 'Lưu ý: Bạn ở Đức hơn 3 năm. Vui lòng đăng ký qua {link}.',
            'js.hint_level_b1p'  => 'Lưu ý: Với trình độ B1 trở lên, vui lòng nộp đơn BBS thông thường qua {link}.',
        
            // =========================
            // STEP 3/4: UPLOAD (VN)
            // =========================
            'upload.page_title' => 'Bước 3/4 – Tài liệu (tùy chọn)',
            'upload.h1'         => 'Bước 3/4 – Tài liệu (tùy chọn)',
        
            'upload.intro'      => 'Bạn có thể tải tài liệu lên tại đây. Định dạng cho phép: <strong>PDF</strong>, <strong>JPG</strong> và <strong>PNG</strong>. Kích thước tối đa <strong>{max_mb} MB</strong> cho mỗi tệp.',
            'upload.type.zeugnis'    => 'Bảng điểm học kỳ gần nhất',
            'upload.type.lebenslauf' => 'Sơ yếu lý lịch (CV)',
            'upload.type_hint'       => '(PDF/JPG/PNG, tối đa {max_mb} MB)',
        
            'upload.btn.remove' => 'Xóa',
            'upload.btn.back'   => 'Quay lại',
            'upload.btn.next'   => 'Tiếp',
        
            'upload.saved_prefix' => 'Đã lưu:',
            'upload.empty'        => 'Chưa tải tệp nào lên.',
            'upload.saved_html'   => 'Đã lưu: <strong>{filename}</strong>, {size_kb} KB, tải lên lúc {uploaded_at}',
        
            'upload.checkbox.zeugnis_spaeter' => 'Tôi sẽ nộp bảng điểm học kỳ sau khi được nhận.',
        
            'upload.flash.no_access' => 'Không tìm thấy quyền truy cập hợp lệ. Vui lòng bắt đầu lại.',
            'upload.flash.saved'     => 'Đã lưu thông tin tải lên.',
        
            'upload.js.uploading'          => 'Đang tải lên…',
            'upload.js.unexpected'         => 'Phản hồi không mong đợi từ máy chủ.',
            'upload.js.upload_failed'      => 'Tải lên thất bại.',
            'upload.js.delete_confirm'     => 'Bạn có chắc muốn xóa tệp đã tải lên không?',
            'upload.js.delete_failed'      => 'Xóa thất bại.',
            'upload.js.remove_confirm_btn' => 'Xóa tệp?',
        
            'upload.ajax.invalid_method'   => 'Phương thức không hợp lệ',
            'upload.ajax.invalid_csrf'     => 'CSRF token không hợp lệ',
            'upload.ajax.no_access'        => 'Không có quyền truy cập hợp lệ.',
            'upload.ajax.invalid_field'    => 'Trường không hợp lệ',
            'upload.ajax.no_file_sent'     => 'Không có tệp được gửi',
            'upload.ajax.no_file_selected' => 'Chưa chọn tệp',
            'upload.ajax.upload_error'     => 'Lỗi tải lên (mã {code})',
            'upload.ajax.too_large'        => 'Tệp lớn hơn {max_mb} MB',
            'upload.ajax.mime_only'        => 'Chỉ cho phép PDF, JPG hoặc PNG',
            'upload.ajax.ext_only'         => 'Đuôi tệp không hợp lệ (chỉ pdf/jpg/jpeg/png)',
            'upload.ajax.cannot_save'      => 'Không thể lưu tệp',
            'upload.ajax.unknown_action'   => 'Hành động không xác định',
            'upload.ajax.server_error'     => 'Lỗi máy chủ khi tải lên',
        
            // =========================
            // STEP 4/4: REVIEW (VN)
            // =========================
            'review.page_title' => 'Bước 4/4 – Tóm tắt & Nộp đơn',
        
            'review.h1'      => 'Bước 4/4 – Tóm tắt & Nộp đơn',
            'review.subhead' => 'Vui lòng kiểm tra thông tin. Nhấn “Nộp đơn” để gửi dữ liệu.',
        
            'review.readonly_alert' => 'Đơn này đã được gửi. Thông tin chỉ có thể xem, không thể chỉnh sửa hoặc gửi lại.',
        
            'review.info.p1' => 'Em học sinh thân mến,',
            'review.info.p2' => 'khi bạn nhấn <strong>“Nộp đơn”</strong>, bạn đã nộp đơn vào <strong>BES Ngôn ngữ và Hội nhập</strong> tại một BBS ở Oldenburg.',
            'review.info.p3' => 'Đây chưa phải là đăng ký cuối cùng mà là một <strong>đơn dự tuyển</strong>. Sau ngày <strong>20.02.</strong> bạn sẽ nhận thông tin có/ở BBS nào bạn được nhận. Vui lòng thường xuyên kiểm tra hộp thư và email. Hãy đảm bảo tên của bạn hiển thị trên hòm thư để nhận thư.',
            'review.info.p4' => 'Khi được trường chấp nhận, bạn sẽ được yêu cầu nộp bổ sung các tài liệu sau (nếu hôm nay bạn chưa tải lên):',
            'review.info.li1' => 'bảng điểm học kỳ gần nhất',
        
            'review.acc.personal' => 'Thông tin cá nhân',
            'review.acc.school'   => 'Trường học & Sở thích',
            'review.acc.uploads'  => 'Tài liệu',
        
            'review.lbl.name'            => 'Họ',
            'review.lbl.vorname'         => 'Tên',
            'review.lbl.geschlecht'      => 'Giới tính',
            'review.lbl.geburtsdatum'    => 'Ngày sinh',
            'review.lbl.geburtsort'      => 'Nơi sinh / Quốc gia sinh',
            'review.lbl.staatsang'       => 'Quốc tịch',
            'review.lbl.strasse'         => 'Đường, số nhà',
            'review.lbl.plz_ort'         => 'Mã bưu chính / Thành phố',
            'review.lbl.telefon'         => 'Điện thoại',
            'review.lbl.email'           => 'Email (học sinh, tùy chọn)',
            'review.lbl.weitere_angaben' => 'Thông tin khác (ví dụ: nhu cầu hỗ trợ)',
        
            'review.contacts.title'    => 'Liên hệ khác',
            'review.contacts.optional' => 'tùy chọn',
            'review.contacts.none'     => '–',
        
            'review.contacts.th.role' => 'Vai trò',
            'review.contacts.th.name' => 'Tên / tổ chức',
            'review.contacts.th.tel'  => 'Điện thoại',
            'review.contacts.th.mail' => 'Email',
            'review.contacts.note'    => 'Ghi chú:',
        
            'review.lbl.school_current' => 'Trường hiện tại',
            'review.lbl.klassenlehrer'  => 'Giáo viên phụ trách',
            'review.lbl.mail_lehrkraft' => 'Email giáo viên',
            'review.lbl.since'          => 'Học tại trường từ khi nào',
            'review.lbl.years_de'       => 'Số năm ở Đức',
            'review.lbl.family_lang'    => 'Ngôn ngữ gia đình / tiếng mẹ đẻ',
            'review.lbl.de_level'       => 'Trình độ tiếng Đức',
            'review.lbl.school_origin'  => 'Trường ở nước xuất xứ',
            'review.lbl.years_origin'   => 'Số năm học ở nước xuất xứ',
            'review.lbl.interests'      => 'Sở thích',
        
            'review.lbl.zeugnis'        => 'Bảng điểm học kỳ',
            'review.lbl.lebenslauf'     => 'Sơ yếu lý lịch (CV)',
            'review.lbl.later'          => 'Nộp sau',
            'review.badge.uploaded'     => 'đã tải lên',
            'review.badge.not_uploaded' => 'chưa tải lên',
            'review.yes'                => 'Có',
            'review.no'                 => 'Không',
        
            'review.btn.home'   => 'Về trang chủ',
            'review.btn.newapp' => 'Nộp thêm một đơn',
            'review.btn.back'   => 'Quay lại',
            'review.btn.submit' => 'Nộp đơn',
        
            'review.err.invalid_request'       => 'Yêu cầu không hợp lệ.',
            'review.flash.already_submitted'   => 'Đơn này đã được gửi và không thể gửi lại hoặc chỉnh sửa.',
            'review.flash.no_token'            => 'Không có mã truy cập hợp lệ. Vui lòng bắt đầu lại.',
            'review.err.not_found_token'       => 'Không tìm thấy đơn với token này.',
            'review.flash.submit_error'        => 'Có lỗi khi gửi. Vui lòng thử lại sau.',
        
            'review.gender.m' => 'nam',
            'review.gender.w' => 'nữ',
            'review.gender.d' => 'khác',
        
            'review.value.empty' => '–',
        
            // =========================
            // STATUS (VN)
            // =========================
            'status.hdr_title'   => 'Đã lưu đơn thành công',
            'status.hdr_message' => 'Đơn của bạn đã được gửi.',
        
            'status.h1' => 'Đơn của bạn đã được lưu thành công.',
        
            'status.success.title' => 'Cảm ơn!',
            'status.success.body'  => 'Đơn của bạn đã được gửi và đang được xử lý.',
        
            'status.info.title' => 'Lưu ý quan trọng',
            'status.info.body'  => '<em>[CHỖ TRỐNG: Văn bản từ khách hàng sẽ được bổ sung]</em>',
        
            'status.btn.pdf'    => 'Tải / In PDF',
            'status.btn.newapp' => 'Bắt đầu đơn mới',
            'status.btn.home'   => 'Về trang chủ',
        
            'status.ref' => 'Tham chiếu: Đơn #{id}',
        
            'status.err.invalid_request' => 'Yêu cầu không hợp lệ.',
        
            // =========================
            // PDF (VN)
            // =========================
            'pdf.err.autoload_missing' => 'Không tìm thấy Composer Autoload. Vui lòng chạy "composer install".',
            'pdf.err.no_token'         => 'Không có mã truy cập hợp lệ. Vui lòng bắt đầu lại.',
            'pdf.err.not_found'        => 'Không tìm thấy đơn.',
            'pdf.err.server'           => 'Lỗi máy chủ khi tạo PDF.',
        
            'pdf.header_title' => 'Đơn đăng ký – Tóm tắt',
            'pdf.footer_auto'  => 'Tài liệu được tạo tự động',
            'pdf.footer_page'  => 'Trang {cur} / {max}',
        
            'pdf.meta.ref'        => 'Đơn #{id}',
            'pdf.meta.created_at' => 'Tạo lúc',
            'pdf.meta.status'     => 'Trạng thái',
        
            'pdf.top.title'        => 'Tổng quan',
            'pdf.top.name'         => 'Tên',
            'pdf.top.reference'    => 'Tham chiếu',
            'pdf.top.generated'    => 'Tạo lúc',
            'pdf.top.hint'         => 'Lưu ý',
            'pdf.top.keep_note'    => 'Vui lòng lưu tài liệu này cho hồ sơ của bạn.',
            'pdf.hint_placeholder' => '[CHỖ TRỐNG: Văn bản từ khách hàng sẽ được bổ sung]',
        
            'pdf.sec1.title' => '1) Thông tin cá nhân',
            'pdf.sec2.title' => '2) Thông tin liên hệ khác',
            'pdf.sec3.title' => '3) Trường học & Sở thích',
            'pdf.sec4.title' => '4) Tài liệu',
        
            'pdf.lbl.name'           => 'Họ',
            'pdf.lbl.vorname'        => 'Tên',
            'pdf.lbl.gender'         => 'Giới tính',
            'pdf.lbl.dob'            => 'Ngày sinh',
            'pdf.lbl.birthplace'     => 'Nơi sinh / Quốc gia sinh',
            'pdf.lbl.nationality'    => 'Quốc tịch',
            'pdf.lbl.address'        => 'Địa chỉ',
            'pdf.lbl.phone'          => 'Điện thoại',
            'pdf.lbl.email_optional' => 'Email (tùy chọn)',
            'pdf.lbl.more'           => 'Thông tin khác',
        
            'pdf.lbl.school_current' => 'Trường hiện tại',
            'pdf.lbl.teacher'        => 'Giáo viên',
            'pdf.lbl.teacher_email'  => 'Email giáo viên',
            'pdf.lbl.since_school'   => 'Học tại trường từ khi nào',
            'pdf.lbl.years_in_de'    => 'Ở Đức từ khi nào',
            'pdf.lbl.family_lang'    => 'Ngôn ngữ gia đình',
            'pdf.lbl.de_level'       => 'Trình độ tiếng Đức',
            'pdf.lbl.school_origin'  => 'Trường ở nước xuất xứ',
            'pdf.lbl.years_origin'   => 'Số năm học ở nước xuất xứ',
            'pdf.lbl.interests'      => 'Sở thích',
        
            'pdf.lbl.report'       => 'Bảng điểm học kỳ',
            'pdf.lbl.cv'           => 'Sơ yếu lý lịch (CV)',
            'pdf.lbl.report_later' => 'Nộp bảng điểm sau',
        
            'pdf.uploaded'     => 'đã tải lên',
            'pdf.not_uploaded' => 'chưa tải lên',
        
            'pdf.contacts.none'    => '–',
            'pdf.contacts.th.role' => 'Vai trò',
            'pdf.contacts.th.name' => 'Tên / tổ chức',
            'pdf.contacts.th.tel'  => 'Điện thoại',
            'pdf.contacts.th.mail' => 'Email',
            'pdf.contacts.th.note' => 'Ghi chú',
        
            'pdf.gender.m' => 'nam',
            'pdf.gender.w' => 'nữ',
            'pdf.gender.d' => 'khác',
        
            'pdf.yes' => 'Có',
            'pdf.no'  => 'Không',
        
            'pdf.sec4.note' => 'Tài liệu này là bản tóm tắt tự động được tạo từ dữ liệu đã nhập.',
            'pdf.filename_prefix' => 'DonDangKy',
        
            // =========================
            // ACCESS_CREATE (VN)
            // =========================
            'access_create.title'         => 'Tiếp tục với email',
            'access_create.lead'          => 'Bạn có thể đăng nhập bằng tài khoản hiện có hoặc tạo tài khoản mới.',
            'access_create.tabs_login'    => 'Đăng nhập',
            'access_create.tabs_register' => 'Tạo tài khoản mới',
        
            'access_create.login_title' => 'Đăng nhập (tài khoản đã có)',
            'access_create.login_text'  => 'Vui lòng nhập email và mật khẩu.',
            'access_create.email_label' => 'Địa chỉ email',
            'access_create.pass_label'  => 'Mật khẩu',
            'access_create.login_btn'   => 'Đăng nhập',
            'access_create.login_err'   => 'Email/mật khẩu sai hoặc tài khoản chưa được xác minh.',
        
            'access_create.reg_title'     => 'Tạo tài khoản mới',
            'access_create.reg_text'      => 'Chúng tôi sẽ gửi mã xác nhận 6 chữ số. Sau khi xác nhận thành công, mật khẩu sẽ được gửi qua email.',
            'access_create.consent_label' => 'Tôi đồng ý rằng email của tôi được sử dụng cho quy trình đăng ký.',
            'access_create.send_btn'      => 'Gửi mã',
            'access_create.code_label'    => 'Mã xác nhận',
            'access_create.verify_btn'    => 'Xác minh mã',
            'access_create.resend'        => 'Gửi lại mã',
        
            'access_create.info_sent'    => 'Chúng tôi đã gửi mã. Vui lòng kiểm tra cả thư mục spam.',
            'access_create.ok_verified'  => 'Email đã được xác minh. Mật khẩu đã được gửi. Bạn có thể đăng nhập ngay.',
            'access_create.email_in_use' => 'Email này đã có tài khoản. Vui lòng đăng nhập.',
        
            'access_create.error_email'     => 'Vui lòng nhập địa chỉ email hợp lệ.',
            'access_create.error_consent'   => 'Vui lòng đồng ý việc sử dụng email.',
            'access_create.error_rate'      => 'Quá nhiều lần thử. Vui lòng đợi và thử lại.',
            'access_create.error_code'      => 'Mã không hợp lệ hoặc đã hết hạn.',
            'access_create.error_resend'    => 'Không thể gửi lại. Vui lòng bắt đầu lại.',
            'access_create.error_mail_send' => 'Gửi email thất bại. Vui lòng thử lại sau.',
            'access_create.error_db'        => 'Lỗi máy chủ (DB).',
        
            'access_create.back'   => 'Quay lại',
            'access_create.cancel' => 'Hủy',
        
            'access_create.mail_subject' => 'Mật khẩu của bạn cho đăng ký trực tuyến',
            'access_create.mail_body'    => "Tài khoản của bạn đã được tạo.\n\nEmail: {email}\nMật khẩu: {password}\n\nVui lòng giữ mật khẩu an toàn.",
        
            // =========================
            // ACCESS_PORTAL (VN)
            // =========================
            'access_portal.title'    => 'Đơn của tôi',
            'access_portal.lead'     => 'Tại đây bạn thấy các đơn của mình. Bạn có thể tiếp tục một đơn hoặc bắt đầu đơn mới.',
            'access_portal.max_hint' => '{email} · tối đa {max} đơn',
        
            'access_portal.btn_new'    => 'Bắt đầu đơn mới',
            'access_portal.btn_open'   => 'Mở',
            'access_portal.btn_logout' => 'Đăng xuất',
        
            'access_portal.th_ref'     => 'ID',
            'access_portal.th_status'  => 'Trạng thái',
            'access_portal.th_created' => 'Tạo lúc',
            'access_portal.th_updated' => 'Cập nhật lúc',
            'access_portal.th_token'   => 'Token',
            'access_portal.th_action'  => 'Hành động',
        
            'access_portal.status_draft'     => 'Bản nháp',
            'access_portal.status_submitted' => 'Đã gửi',
            'access_portal.status_withdrawn' => 'Đã rút',
        
            'access_portal.limit_reached' => 'Bạn đã đạt số lượng đơn tối đa cho email này.',
            'access_portal.no_apps'       => 'Chưa có đơn nào.',
            'access_portal.err_generic'   => 'Đã xảy ra lỗi.',
            'access_portal.csrf_invalid'  => 'Yêu cầu không hợp lệ.',
        
            // =========================
            // ACCESS_LOGIN (VN)
            // =========================
            'access_login.title'             => 'Truy cập đơn đăng ký',
            'access_login.lead'              => 'Tại đây bạn có thể mở lại đơn đã bắt đầu hoặc đã gửi.',
        
            'access_login.login_box_title'   => 'Đăng nhập bằng Access-Token',
            'access_login.login_box_text'    => 'Vui lòng nhập mã truy cập cá nhân (Access-Token) và ngày sinh.',
        
            'access_login.token_label'       => 'Access-Token',
            'access_login.dob_label'         => 'Ngày sinh (DD.MM.YYYY)',
        
            'access_login.login_btn'         => 'Truy cập',
            'access_login.back'              => 'Về trang chủ',
        
            'access_login.login_ok'          => 'Đã tải đơn.',
            'access_login.login_error'       => 'Không tìm thấy kết hợp Access-Token và ngày sinh.',
            'access_login.login_error_token' => 'Vui lòng nhập Access-Token hợp lệ.',
            'access_login.login_error_dob'   => 'Vui lòng nhập ngày sinh hợp lệ theo định dạng DD.MM.YYYY.',
        
            'access_login.csrf_invalid'      => 'Yêu cầu không hợp lệ.',
            'access_login.internal_error'    => 'Lỗi nội bộ.',
            'access_login.load_error'        => 'Đã xảy ra lỗi khi tải đơn.',
        
            // =========================
            // PRIVACY (VN)
            // =========================
            'privacy.title' => 'Bảo vệ dữ liệu',
            'privacy.h1'    => 'Thông tin bảo vệ dữ liệu cho đơn đăng ký trực tuyến “BES Ngôn ngữ và Hội nhập”',
        
            'privacy.s1_title'     => '1. Đơn vị chịu trách nhiệm',
            'privacy.s1_body_html' => '<strong>Thành phố Oldenburg / Trường dạy nghề</strong><br>(Vui lòng điền tên đơn vị/trường, địa chỉ, điện thoại, email)',
        
            'privacy.s2_title'     => '2. Cán bộ bảo vệ dữ liệu',
            'privacy.s2_body_html' => '(Vui lòng điền thông tin liên hệ của cán bộ bảo vệ dữ liệu)',
        
            'privacy.s3_title' => '3. Mục đích xử lý',
            'privacy.s3_li1'   => 'Tiếp nhận và xử lý đơn xin vào lớp học tiếng (“BES Ngôn ngữ và Hội nhập”)',
            'privacy.s3_li2'   => 'Liên hệ với bạn (hỏi thêm thông tin, thông báo kết quả)',
            'privacy.s3_li3'   => 'Lập kế hoạch tổ chức trường học (phân bổ tới một BBS)',
        
            'privacy.s4_title' => '4. Cơ sở pháp lý',
            'privacy.s4_li1'   => 'Điều 6(1)(e) GDPR kết hợp với quy định giáo dục của bang Niedersachsen',
            'privacy.s4_li2'   => 'Điều 6(1)(c) GDPR (thực hiện nghĩa vụ pháp lý)',
            'privacy.s4_li3'   => 'Điều 6(1)(a) GDPR (đồng ý), khi cung cấp thông tin/tải lên tự nguyện',
        
            'privacy.s5_title' => '5. Loại dữ liệu cá nhân',
            'privacy.s5_li1'   => 'Thông tin cơ bản (họ tên, ngày sinh, quốc tịch, địa chỉ, liên hệ)',
            'privacy.s5_li2'   => 'Thông tin trường học (trường hiện tại, trình độ ngôn ngữ, sở thích)',
            'privacy.s5_li3'   => 'Tài liệu tùy chọn (ví dụ: bảng điểm học kỳ gần nhất)',
            'privacy.s5_li4'   => 'Liên hệ bổ sung (phụ huynh/người giám hộ/tổ chức)',
        
            'privacy.s6_title' => '6. Người nhận',
            'privacy.s6_body'  => 'Trong phạm vi thẩm quyền của thành phố Oldenburg và các trường dạy nghề. Chỉ chuyển cho bên thứ ba khi bắt buộc theo pháp luật (ví dụ: cơ quan giáo dục) hoặc khi bạn đồng ý.',
        
            'privacy.s7_title' => '7. Chuyển dữ liệu ra nước thứ ba',
            'privacy.s7_body'  => 'Không có việc chuyển dữ liệu ra nước thứ ba.',
        
            'privacy.s8_title' => '8. Thời hạn lưu trữ',
            'privacy.s8_body'  => 'Dữ liệu của bạn được lưu trong thời gian xử lý đơn/tiếp nhận theo quy định và sẽ được xóa sau đó.',
        
            'privacy.s9_title' => '9. Quyền của bạn',
            'privacy.s9_li1'   => 'Quyền truy cập (Điều 15), chỉnh sửa (16), xóa (17), hạn chế (18)',
            'privacy.s9_li2'   => 'Quyền phản đối (Điều 21) đối với xử lý vì lợi ích công',
            'privacy.s9_li3'   => 'Rút lại sự đồng ý (Điều 7(3)) có hiệu lực cho tương lai',
            'privacy.s9_li4'   => 'Quyền khiếu nại tới cơ quan giám sát: Cơ quan bảo vệ dữ liệu bang Niedersachsen',
        
            'privacy.s10_title' => '10. Lưu trữ & nhật ký',
            'privacy.s10_body'  => 'Ứng dụng chạy trên máy chủ của thành phố hoặc trung tâm dữ liệu công. Chỉ xử lý dữ liệu kỹ thuật cần thiết (ví dụ: log máy chủ để tìm lỗi). Không dùng CDN bên ngoài. Chỉ đặt cookie liên quan đến ngôn ngữ.',
        
            'privacy.s11_title'    => '11. Cookie',
            'privacy.s11_li1_html' => '<strong>lang</strong> – lưu ngôn ngữ đã chọn (hiệu lực 12 tháng). Mục đích: thuận tiện sử dụng.',
            'privacy.s11_li2'      => 'PHP-Session – cần thiết cho quy trình biểu mẫu, sẽ xóa khi kết thúc phiên.',
        
            'privacy.stand_label' => 'Cập nhật',
            'privacy.stand_hint'  => 'Vui lòng kiểm tra định kỳ xem có thay đổi hay không.',
            'privacy.back_home'   => 'Về trang chủ',
        
            // =========================
            // IMPRINT (VN)
            // =========================
            'imprint.title' => 'Thông tin pháp lý',
            'imprint.h1'    => 'Thông tin pháp lý',
        
            'imprint.s1_title'     => 'Nhà cung cấp dịch vụ',
            'imprint.s1_body_html' => '<strong>Thành phố ***</strong><br>Trường dạy nghề<br>(Vui lòng điền địa chỉ chính xác)<br>Điện thoại: (bổ sung)<br>Email: (bổ sung)',
        
            'imprint.s2_title'     => 'Đại diện pháp lý',
            'imprint.s2_body_html' => '(ví dụ: Thị trưởng thành phố ****<br>hoặc Ban giám hiệu của BBS)',
        
            'imprint.s3_title'     => 'Chịu trách nhiệm nội dung theo § 18 Abs. 2 MStV',
            'imprint.s3_body_html' => '(Tên, chức vụ, liên hệ; ví dụ: Ban giám hiệu BBS hoặc phòng báo chí)',
        
            'imprint.s4_title'     => 'Mã số thuế VAT',
            'imprint.s4_body_html' => '(nếu có; nếu không có, có thể bỏ phần này)',
        
            'imprint.s5_title' => 'Cơ quan giám sát',
            'imprint.s5_body'  => '(cơ quan giám sát của thành phố / cơ quan giáo dục có thẩm quyền)',
        
            'imprint.s6_title' => 'Trách nhiệm nội dung',
            'imprint.s6_body'  => 'Nội dung được soạn thảo cẩn thận. Tuy nhiên, chúng tôi không thể đảm bảo tính chính xác, đầy đủ và cập nhật. Là cơ quan công, chúng tôi chịu trách nhiệm về nội dung của mình theo pháp luật chung.',
        
            'imprint.s7_title' => 'Trách nhiệm liên kết',
            'imprint.s7_body'  => 'Dịch vụ không chứa nội dung bên ngoài truyền dữ liệu cá nhân cho bên thứ ba. Nếu liên kết tới thông tin của cơ quan công khác, chúng tôi không chịu trách nhiệm về nội dung của họ.',
        
            'imprint.s8_title' => 'Bản quyền',
            'imprint.s8_body'  => 'Nội dung và tác phẩm do thành phố Oldenburg tạo ra thuộc bản quyền Đức. Nội dung của bên thứ ba được đánh dấu. Mọi sao chép/xử lý/phân phối ngoài phạm vi bản quyền cần có sự đồng ý bằng văn bản của thành phố Oldenburg hoặc chủ sở hữu quyền.',
        
            'imprint.stand_label' => 'Cập nhật',
            'imprint.stand_hint'  => 'Thông tin này áp dụng cho biểu mẫu trực tuyến “BES Ngôn ngữ và Hội nhập”.',
        
            'imprint.back_home' => 'Về trang chủ',
        
            // =========================
            // VERIFY_EMAIL (VN)
            // =========================
            'verify_email.title' => 'Xác minh email',
            'verify_email.h1'    => 'Xác minh email',
        
            'verify_email.lead_sent'    => 'Chúng tôi đã gửi mã xác nhận tới {email}.',
            'verify_email.lead_generic' => 'Vui lòng nhập mã xác nhận nhận được qua email. Nếu bạn không thấy email, bạn có thể yêu cầu gửi lại mã.',
        
            'verify_email.code_label'  => 'Mã xác nhận (6 chữ số)',
            'verify_email.email_label' => 'Địa chỉ email của bạn',
        
            'verify_email.btn_verify' => 'Xác nhận',
            'verify_email.btn_resend' => 'Gửi lại mã',
            'verify_email.hint_spam'  => 'Vui lòng kiểm tra cả thư mục spam.',
        
            'verify_email.back' => 'Quay lại',
        
            'verify_email.csrf_invalid' => 'Yêu cầu không hợp lệ.',
            'verify_email.ok_verified'  => 'Email đã được xác minh thành công.',
            'verify_email.ok_sent'      => 'Mã mới đã được gửi tới {email}.',
        
            'verify_email.warn_cooldown'     => 'Vui lòng đợi một chút trước khi yêu cầu lại mã.',
            'verify_email.error_send'        => 'Gửi thất bại. Vui lòng thử lại sau.',
            'verify_email.error_email'       => 'Vui lòng nhập email hợp lệ.',
            'verify_email.error_no_session'  => 'Không tìm thấy phiên xác minh đang hoạt động. Vui lòng yêu cầu mã mới.',
            'verify_email.error_expired'     => 'Mã không hợp lệ hoặc đã hết hạn.',
            'verify_email.error_invalid'     => 'Mã không hợp lệ hoặc đã hết hạn.',
            'verify_email.error_code_format' => 'Vui lòng nhập mã 6 chữ số hợp lệ.',
            'verify_email.error_rate'        => 'Quá nhiều lần thử. Vui lòng yêu cầu mã mới.',
        
            // =========================
            // VALIDATION (VN) – global
            // =========================
            'val.required' => 'Bắt buộc.',
            'val.only_letters' => 'Vui lòng chỉ dùng chữ cái.',
            'val.gender_choose' => 'Vui lòng chọn giới tính.',
            'val.date_format' => 'DD.MM.YYYY',
            'val.date_invalid' => 'Ngày không hợp lệ.',
            'val.plz_whitelist' => 'Chỉ mã bưu chính Oldenburg (26121–26135).',
            'val.phone_vorwahl' => 'Mã vùng: 2–6 chữ số.',
            'val.phone_nummer' => 'Số: 3–12 chữ số.',
            'val.email_invalid' => 'Email không hợp lệ.',
            'val.email_no_iserv' => 'Vui lòng dùng email cá nhân (không phải IServ).',
            'val.max_1500' => 'Tối đa 1500 ký tự.',
            'val.kontakt_row_name_missing' => 'Thiếu tên/mô tả',
            'val.kontakt_row_tel_or_mail'  => 'Nhập điện thoại HOẶC email',
            'val.kontakt_row_mail_invalid' => 'Email không hợp lệ',
            'val.kontakt_row_tel_invalid'  => 'Điện thoại không hợp lệ',
        ],

    ];
}

/**
 * t('key') returns string; fallback: DE; then key.
 */
function t(string $key, ?string $lang = null): string {
    $lang = $lang ?: (string)($_SESSION['lang'] ?? 'de');
    $dict = i18n_dict();

    if (isset($dict[$lang][$key]) && is_string($dict[$lang][$key])) {
        return (string)$dict[$lang][$key];
    }
    if (isset($dict['de'][$key]) && is_string($dict['de'][$key])) {
        return (string)$dict['de'][$key];
    }
    return $key;
}

/**
 * t_arr('key') returns array; fallback: DE; else [].
 */
function t_arr(string $key, ?string $lang = null): array {
    $lang = $lang ?: (string)($_SESSION['lang'] ?? 'de');
    $dict = i18n_dict();

    if (isset($dict[$lang][$key]) && is_array($dict[$lang][$key])) {
        return (array)$dict[$lang][$key];
    }
    if (isset($dict['de'][$key]) && is_array($dict['de'][$key])) {
        return (array)$dict['de'][$key];
    }
    return [];
}
