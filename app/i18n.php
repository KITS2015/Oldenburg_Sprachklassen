<?php
// app/i18n.php
declare(strict_types=1);

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

function i18n_is_rtl(string $lang): bool {
    return in_array($lang, ['ar', 'fa'], true);
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
    if (!isset($_COOKIE['lang']) || (string)$_COOKIE['lang'] !== $lang) {
        setcookie('lang', $lang, time() + 60*60*24*365, '/');
        $_COOKIE['lang'] = $lang;
    }

    return $lang;
}

/**
 * Translation dictionary
 * (Hier kommen jetzt deine Index-Texte rein.)
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

        // TODO: uk, ar, ru, tr, fa kannst du 1:1 aus index.php übertragen.
    ];
}

/**
 * t('key') returns a string; falls back to DE; then key.
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
 * t_arr('key') returns array (e.g. bullets). Falls back to DE; else [].
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
