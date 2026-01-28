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
            
            // Validierungs-/Fehlertexte
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
        ],

        'en' => [
            'index.title' => 'Welcome to the Online Registration – Language Classes',
            'index.lead'  => 'This service is for newly arrived people in Oldenburg. The form helps us contact you and find suitable language class options.',
            'index.bullets' => [
                'Please have your contact details and ID/passport ready (if available).',
                'You can fill in the form in different languages.',
                'Your data is handled confidentially under GDPR.',
            ],
            'index.info_p' => [
                'Dear student,',
                'With this application you are applying for a place in the language learning class “BES Language and Integration” at a vocational school (BBS) in Oldenburg. You are not applying to a specific BBS. After 20 February you will be informed which school will accept you into the class.',
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
                '<strong>With email:</strong> You receive a verification code and can create multiple applications and access them later.',
                '<strong>Without email:</strong> You get a personal access token. Please write it down or take a photo—without a verified email there is no recovery.',
            ],
            'index.btn_noemail' => 'Proceed without email',
            'index.btn_create'  => 'Continue with email',
            'index.btn_load'    => 'Access your application(s)',
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
        ],

        'fr' => [
            'index.title' => 'Bienvenue – Inscription en ligne aux cours de langue',
            'index.lead'  => 'Ce service s’adresse aux personnes récemment arrivées à Oldenburg. Le formulaire nous aide à vous contacter et à proposer une offre adaptée.',
            'index.bullets' => [
                'Préparez vos coordonnées et un document d’identité (si disponible).',
                'Le formulaire peut être rempli dans plusieurs langues.',
                'Vos données sont traitées de manière confidentielle (RGPD).',
            ],
            'index.info_p' => [
                'Chère élève, cher élève,',
                'Par la présente, vous posez votre candidature pour une place dans la classe d’apprentissage de la langue « BES Langue et Intégration » d’un établissement professionnel (BBS) à Oldenburg. Vous ne candidatez pas pour un établissement précis. Après le 20 février, vous serez informé·e de l’établissement qui vous accueillera.',
                'Vous ne pouvez être admis·e que si toutes les conditions suivantes sont remplies :',
            ],
            'index.info_bullets' => [
                'Vous avez besoin d’un soutien intensif en allemand (niveau inférieur à B1).',
                'Au début de la prochaine année scolaire, vous êtes en Allemagne depuis au plus 3 ans.',
                'Au 30 septembre de cette année, vous avez au moins 16 ans et au plus 18 ans.',
                'Vous êtes soumis·e à l’obligation scolaire pour la prochaine année scolaire.',
            ],
            'index.access_title' => 'Confidentialité & Accès',
            'index.access_intro' => 'Vous pouvez continuer avec ou sans adresse e-mail. L’accès aux candidatures enregistrées n’est possible qu’avec votre jeton d’accès personnel et votre date de naissance.',
            'index.access_points' => [
                '<strong>Avec e-mail :</strong> vous recevez un code de vérification et pouvez reprendre votre candidature plus tard.',
                '<strong>Sans e-mail :</strong> vous recevez un jeton d’accès personnel. Veuillez le noter/photographier — sans e-mail vérifié, aucune récupération n’est possible.',
            ],
            'index.btn_noemail' => 'Continuer sans e-mail',
            'index.btn_create'  => 'Créer un accès avec e-mail',
            'index.btn_load'    => 'Charger la candidature',
            'index.lang_label'  => 'Langue:',
        ],

        'uk' => [
            'index.title' => 'Ласкаво просимо до онлайн-реєстрації – мовні класи',
            'index.lead'  => 'Ця послуга для людей, які нещодавно прибули до Ольденбурга. Форма допоможе нам зв’язатися з вами та підібрати відповідні курси.',
            'index.bullets' => [
                'Підготуйте контактні дані та документ, що посвідчує особу (за наявності).',
                'Форму можна заповнювати різними мовами.',
                'Ваші дані обробляються конфіденційно відповідно до GDPR.',
            ],
            'index.info_p' => [
                'Шановна ученице, шановний учню!',
                'Ви подаєте заявку на місце у класі вивчення мови «BES Мова та інтеграція» у професійній школі (BBS) міста Ольденбург. Ви не подаєтеся до конкретної школи. Після 20 лютого вам повідомлять, яка школа зарахує вас до класу.',
                'Вас можуть зарахувати лише за таких умов:',
            ],
            'index.info_bullets' => [
                'вам потрібна інтенсивна підтримка з німецької мови (рівень нижче B1);',
                'на початок наступного навчального року ви перебуваєте в Німеччині не більше 3 років;',
                'станом на 30 вересня цього року вам щонайменше 16 і не більше 18 років;',
                'у наступному навчальному році ви підлягаєте обов’язковому шкільному навчанню.',
            ],
            'index.access_title' => 'Конфіденційність та доступ',
            'index.access_intro' => 'Ви можете продовжити з електронною поштою або без неї. Доступ до збережених заяв можливий лише за допомогою особистого токена доступу та дати народження.',
            'index.access_points' => [
                '<strong>З e-mail:</strong> ви отримаєте код підтвердження і зможете пізніше продовжити заповнення.',
                '<strong>Без e-mail:</strong> ви отримаєте особистий токен доступу. Занотуйте/сфотографуйте його — без підтвердженої e-mail відновлення неможливе.',
            ],
            'index.btn_noemail' => 'Продовжити без e-mail',
            'index.btn_create'  => 'Створити доступ за e-mail',
            'index.btn_load'    => 'Завантажити заявку',
            'index.lang_label'  => 'Мова:',

            'personal.page_title' => 'Étape 1/4 – Données personnelles',
            'personal.h1' => 'Étape 1/4 – Données personnelles',
            'personal.required_hint' => 'Les champs obligatoires sont mis en évidence par une bordure bleue.',
            'personal.form_error_hint' => 'Veuillez vérifier les champs en surbrillance.',
            
            'personal.alert_email_title' => 'Connexion e-mail active :',
            'personal.alert_email_line1' => 'Connecté·e avec l’adresse e-mail {email}.',
            'personal.alert_email_line2' => 'Cet e-mail est utilisé uniquement pour le jeton d’accès (Access-Token) et pour retrouver votre candidature.',
            'personal.alert_email_line3' => 'Vous pouvez indiquer ci-dessous l’e-mail de l’élève (si disponible).',
            
            'personal.alert_noemail_title' => 'Remarque (sans e-mail) :',
            'personal.alert_noemail_body' => 'Veuillez noter/photographier votre jeton d’accès affiché après l’enregistrement. Sans e-mail vérifié, la récupération n’est possible qu’avec jeton + date de naissance.',
            
            'personal.label.name' => 'Nom',
            'personal.label.vorname' => 'Prénom',
            'personal.label.geschlecht' => 'Sexe',
            'personal.gender.m' => 'masculin',
            'personal.gender.w' => 'féminin',
            'personal.gender.d' => 'divers',
            
            'personal.label.geburtsdatum' => 'Date de naissance',
            'personal.label.geburtsdatum_hint' => '(JJ.MM.AAAA)',
            'personal.placeholder.geburtsdatum' => 'JJ.MM.AAAA',
            
            'personal.age_hint' => 'Remarque : si vous avez moins de 16 ans ou plus de 18 ans au 30/09/{year}, vous ne pouvez pas être admis·e. Veuillez postuler à une autre classe ici :',
            'personal.age_redirect_msg' => "Remarque : si vous avez moins de 16 ans ou plus de 18 ans au 30/09/{year}, vous ne pouvez pas être admis·e.\nVeuillez postuler à une autre classe ici :\n{url}",
            
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
            'personal.email_help' => 'Cet e-mail appartient à l’élève (si disponible) et est indépendant de l’e-mail de connexion.',
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
            
            'personal.label.weitere_angaben' => 'Autres informations (p. ex. besoins de soutien) :',
            'personal.placeholder.weitere_angaben' => 'Vous pouvez indiquer ici des besoins de soutien, des besoins éducatifs particuliers ou d’autres remarques.',
            'personal.weitere_angaben_help' => 'Optionnel. Maximum 1500 caractères.',
            'personal.btn.cancel' => 'Annuler',
            'personal.btn.next' => 'Suivant',
            
            'personal.dsgvo_text_prefix' => "J’ai lu les",
            'personal.dsgvo_link_text' => 'informations de protection des données',
            'personal.dsgvo_text_suffix' => 'et je suis d’accord.',
            
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

        'ar' => [
            'index.title' => 'مرحبًا بكم في التسجيل الإلكتروني – صفوف اللغة',
            'index.lead'  => 'هذه الخدمة مخصّصة للوافدين الجدد إلى أولدنبورغ. يساعدنا النموذج على التواصل معكم واختيار الدورة المناسبة.',
            'index.bullets' => [
                'يرجى تجهيز بيانات الاتصال ووثيقة الهوية إن وُجدت.',
                'يمكن تعبئة النموذج بعدة لغات.',
                'تُعالج بياناتكم بسرية وفق اللائحة العامة لحماية البيانات (GDPR).',
            ],
            'index.info_p' => [
                'عزيزتي الطالبة، عزيزي الطالب،',
                'بهذا التقديم تتقدّم/ين للحصول على مقعد في صف تعلّم اللغة «BES اللغة والاندماج» في إحدى المدارس المهنية (BBS) في أولدنبورغ. لا تتقدّم/ين إلى مدرسة بعينها. بعد 20 فبراير سيتم إبلاغك بأي مدرسة ستقبلك في الصف.',
                'لا يمكن قبولك إلا إذا توفّرت الشروط التالية جميعها:',
            ],
            'index.info_bullets' => [
                'تحتاج/ين إلى دعم مكثّف في اللغة الألمانية (مستوى أقل من B1).',
                'عند بداية العام الدراسي القادم لا تتجاوز مدة إقامتك في ألمانيا 3 سنوات.',
                'في تاريخ 30 سبتمبر من هذا العام يكون عمرك بين 16 و18 عامًا.',
                'تكون/ين خاضعًا/ة للتعليم الإلزامي في العام الدراسي القادم.',
            ],
            'index.access_title' => 'الخصوصية والوصول',
            'index.access_intro' => 'يمكنك المتابعة مع عنوان بريد إلكتروني أو بدونه. لا يمكن الوصول إلى الطلبات المحفوظة إلا باستخدام رمز الوصول الشخصي وتاريخ الميلاد.',
            'index.access_points' => [
                '<strong>مع البريد الإلكتروني:</strong> ستتلقى رمز تحقق ويمكنك متابعة طلبك لاحقًا.',
                '<strong>بدون بريد إلكتروني:</strong> ستحصل على رمز وصول شخصي. يُرجى حفظه/تصويره — بدون بريد إلكتروني مُوثَّق لا يمكن الاستعادة.',
            ],
            'index.btn_noemail' => 'المتابعة دون بريد إلكتروني',
            'index.btn_create'  => 'إنشاء وصول عبر البريد',
            'index.btn_load'    => 'تحميل الطلب',
            'index.lang_label'  => 'اللغة:',
        ],

        'ru' => [
            'index.title' => 'Добро пожаловать на онлайн-регистрацию – языковые курсы',
            'index.lead'  => 'Сервис для недавно прибывших в Ольденбург. Эта форма помогает связаться с вами и подобрать подходящие варианты.',
            'index.bullets' => [
                'Подготовьте контактные данные и документ, удостоверяющий личность (если есть).',
                'Форму можно заполнить на разных языках.',
                'Ваши данные обрабатываются конфиденциально в соответствии с GDPR.',
            ],
            'index.info_p' => [
                'Уважаемая ученица, уважаемый ученик!',
                'Этой заявкой вы подаётесь на место в языковом классе «BES Язык и интеграция» профессиональной школы (BBS) в Ольденбурге. Вы не подаётесь в конкретную школу. После 20 февраля вам сообщат, какая школа примет вас в класс.',
                'Поступить можно только при выполнении всех следующих условий:',
            ],
            'index.info_bullets' => [
                'вам требуется интенсивная поддержка по немецкому языку (уровень ниже B1);',
                'к началу следующего учебного года вы находитесь в Германии не более 3 лет;',
                'на 30 сентября текущего года вам не менее 16 и не более 18 лет;',
                'в следующем учебном году на вас распространяется обязанность школьного обучения.',
            ],
            'index.access_title' => 'Конфиденциальность и доступ',
            'index.access_intro' => 'Можно продолжить с электронной почтой или без неё. Доступ к сохранённым заявлениям возможен только с личным токеном доступа и датой рождения.',
            'index.access_points' => [
                '<strong>С e-mail:</strong> вы получите код подтверждения и сможете позже продолжить.',
                '<strong>Без e-mail:</strong> вы получите личный токен доступа. Запишите/сфотографируйте его — без подтверждённого e-mail восстановление невозможно.',
            ],
            'index.btn_noemail' => 'Продолжить без e-mail',
            'index.btn_create'  => 'Создать доступ через e-mail',
            'index.btn_load'    => 'Загрузить заявление',
            'index.lang_label'  => 'Язык:',
        ],

        'tr' => [
            'index.title' => 'Çevrimiçi Kayıt – Dil Kursları',
            'index.lead'  => 'Bu hizmet, Oldenburg’a yeni gelen kişiler içindir. Form, sizinle iletişim kurmamıza ve uygun kurs seçeneklerini bulmamıza yardımcı olur.',
            'index.bullets' => [
                'Lütfen iletişim bilgilerinizi ve kimlik belgenizi (varsa) hazırlayın.',
                'Formu birden fazla dilde doldurabilirsiniz.',
                'Verileriniz GDPR kapsamında gizli tutulur.',
            ],
            'index.info_p' => [
                'Sevgili öğrenci,',
                'Bu başvuru ile Oldenburg’daki bir mesleki okulda (BBS) “BES Dil ve Uyum” dil öğrenme sınıfına başvuruyorsunuz. Belirli bir BBS’e başvurmuyorsunuz. 20 Şubat’tan sonra hangi okulun sizi kabul edeceği bildirilecektir.',
                'Aşağıdaki koşulların tümü sağlandığında kabul edilebilirsiniz:',
            ],
            'index.info_bullets' => [
                'Yoğun Almanca desteğine ihtiyacınız var (Almanca seviyeniz B1’in altında).',
                'Gelecek öğretim yılının başlangıcında Almanya’da en fazla 3 yıldır bulunuyorsunuz.',
                'Bu yıl 30 Eylül tarihi itibarıyla yaşınız en az 16, en fazla 18’dir.',
                'Gelecek öğretim yılında okul zorunluluğuna tabisiniz.',
            ],
            'index.access_title' => 'Gizlilik ve Erişim',
            'index.access_intro' => 'E-posta ile veya e-posta olmadan devam edebilirsiniz. Kaydedilmiş başvurulara erişim yalnızca kişisel erişim kodu ve doğum tarihi ile mümkündür.',
            'index.access_points' => [
                '<strong>E-postayla:</strong> Doğrulama kodu alır ve başvurunuza daha sonra devam edebilirsiniz.',
                '<strong>E-posta olmadan:</strong> Kişisel bir erişim kodu alırsınız. Lütfen not edin/fotoğraflayın — doğrulanmış e-posta olmadan kurtarma yoktur.',
            ],
            'index.btn_noemail' => 'E-posta olmadan devam et',
            'index.btn_create'  => 'E-posta ile erişim oluştur',
            'index.btn_load'    => 'Başvuruyu yükle',
            'index.lang_label'  => 'Dil:',
        ],

        'fa' => [
            'index.title' => 'ثبت‌نام آنلاین – کلاس‌های زبان',
            'index.lead'  => 'این خدمت برای افراد تازه‌وارد به اولدن‌بورگ است. این فرم به ما کمک می‌کند با شما تماس بگیریم و گزینه‌های مناسب را بیابیم.',
            'index.bullets' => [
                'لطفاً اطلاعات تماس و در صورت امکان مدرک هویتی را آماده داشته باشید.',
                'می‌توانید فرم را به چند زبان تکمیل کنید.',
                'داده‌های شما مطابق مقررات GDPR محرمانه نگه‌داری می‌شود.',
            ],
            'index.info_p' => [
                'دانش‌آموز گرامی،',
                'با این درخواست برای یک جایگاه در کلاس یادگیری زبان «BES زبان و ادغام» در یکی از مدارس فنی‌وحرفه‌ای (BBS) اولدن‌بورگ اقدام می‌کنید. شما برای یک مدرسه مشخص اقدام نمی‌کنید. پس از ۲۰ فوریه به شما اطلاع داده می‌شود که کدام مدرسه شما را در کلاس می‌پذیرد.',
                'پذیرش تنها در صورت برآورده شدن همه شرایط زیر ممکن است:',
            ],
            'index.info_bullets' => [
                'به پشتیبانی فشرده زبان آلمانی نیاز دارید (سطح زیر B1).',
                'در آغاز سال تحصیلی آینده حداکثر ۳ سال است که در آلمان هستید.',
                'در تاریخ ۳۰ سپتامبر امسال سن شما حداقل ۱۶ و حداکثر ۱۸ سال است.',
                'در سال تحصیلی آینده مشمول تحصیل اجباری هستید.',
            ],
            'index.access_title' => 'حریم خصوصی و دسترسی',
            'index.access_intro' => 'می‌توانید با ایمیل یا بدون آن ادامه دهید. دسترسی به درخواست‌های ذخیره‌شده فقط با کُد دسترسی شخصی و تاریخ تولد امکان‌پذیر است.',
            'index.access_points' => [
                '<strong>با ایمیل:</strong> یک کُد تأیید دریافت می‌کنید و می‌توانید بعداً ادامه دهید.',
                '<strong>بدون ایمیل:</strong> یک کُد دسترسی شخصی دریافت می‌کنید. لطفاً آن را یادداشت/تصویر کنید — بدون ایمیل تأیید شده، بازیابی ممکن نیست.',
            ],
            'index.btn_noemail' => 'ادامه بدون ایمیل',
            'index.btn_create'  => 'ایجاد دسترسی با ایمیل',
            'index.btn_load'    => 'بارگذاری درخواست',
            'index.lang_label'  => 'زبان:',
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
