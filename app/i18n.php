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

// RTL-Sprachen
$GLOBALS['APP_RTL_LANGS'] = ['ar','fa'];

/**
 * Übersetzungen: Key => Text
 * Tipp: Keys nach Seite/Scope gruppieren, z.B. personal.*, school.*, review.*, status.*, common.*
 */
$GLOBALS['I18N'] = [
  'de' => [
    'common.required_hint' => 'Pflichtfelder sind blau am Rahmen hervorgehoben.',
    'common.cancel'        => 'Abbrechen',
    'common.next'          => 'Weiter',
    'common.back'          => 'Zurück',

    'personal.title'       => 'Schritt 1/4 – Persönliche Daten',
    'personal.headline'    => 'Schritt 1/4 – Persönliche Daten',
    'personal.name'        => 'Name',
    'personal.firstname'   => 'Vorname',
    'personal.gender'      => 'Geschlecht',
    'personal.dob'         => 'Geboren am',
    'personal.birthplace'  => 'Geburtsort / Geburtsland',
    'personal.nationality' => 'Staatsangehörigkeit',
    'personal.street'      => 'Straße, Nr.',
    'personal.zip'         => 'PLZ',
    'personal.city'        => 'Wohnort',
    'personal.phone'       => 'Telefonnummer',
    'personal.email_opt'   => 'E-Mail-Adresse der Schülerin / des Schülers (optional, keine IServ-Adresse)',
    'personal.more'        => 'Weitere Angaben (z. B. Förderstatus):',
    'personal.dsgvo'       => 'Ich habe die Datenschutzhinweise gelesen und bin einverstanden.',
  ],

  'fr' => [
    'common.required_hint' => 'Les champs obligatoires sont marqués par un cadre bleu.',
    'common.cancel'        => 'Annuler',
    'common.next'          => 'Continuer',
    'common.back'          => 'Retour',

    'personal.title'       => 'Étape 1/4 – Données personnelles',
    'personal.headline'    => 'Étape 1/4 – Données personnelles',
    'personal.name'        => 'Nom',
    'personal.firstname'   => 'Prénom',
    'personal.gender'      => 'Sexe',
    'personal.dob'         => 'Date de naissance',
    'personal.birthplace'  => 'Lieu / pays de naissance',
    'personal.nationality' => 'Nationalité',
    'personal.street'      => 'Rue, n°',
    'personal.zip'         => 'Code postal',
    'personal.city'        => 'Ville',
    'personal.phone'       => 'Numéro de téléphone',
    'personal.email_opt'   => "E-mail de l’élève (facultatif, pas d’adresse IServ)",
    'personal.more'        => 'Autres informations (p. ex. besoins de soutien) :',
    'personal.dsgvo'       => "J’ai lu les informations sur la protection des données et j’accepte.",
  ],
];

/**
 * t(): Übersetzung holen
 * - Fallback: de
 * - Optionaler Fallback-Text
 */
if (!function_exists('t')) {
  function t(string $key, string $fallback = ''): string {
    $lang = (string)($_SESSION['lang'] ?? 'de');
    $I18N = $GLOBALS['I18N'] ?? [];
    if (isset($I18N[$lang][$key])) return (string)$I18N[$lang][$key];
    if (isset($I18N['de'][$key]))  return (string)$I18N['de'][$key];
    return $fallback !== '' ? $fallback : $key;
  }
}
