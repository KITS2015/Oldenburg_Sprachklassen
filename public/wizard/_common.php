<?php
// public/wizard/_common.php
mb_internal_encoding('UTF-8');
session_start();

// --- Konfiguration ---
$UPLOAD_DIR = __DIR__ . '/../../uploads'; // außerhalb von public besser; hier Demo
if (!is_dir($UPLOAD_DIR)) @mkdir($UPLOAD_DIR, 0775, true);

$SCHULEN = [
  '' => 'Bitte wählen …',
  'BBS_1' => 'BBS 1',
  'BBS_2' => 'BBS 2',
  'BBS_3' => 'BBS 3',
  'IGS_FALLBACK' => 'Andere Schule in Oldenburg',
];

$GERMAN_LEVELS = ['A0','A1','A2','B1'];

$INTERESSEN = [
  'wirtschaft' => 'Wirtschaft',
  'handwerk' => 'Handwerk (Holz, Metall)',
  'sozial' => 'Soziales / Erzieher*in / Sozialassistent*in',
  'gesundheit' => 'Gesundheit / Pflege / Medizin',
  'garten' => 'Garten / Landwirtschaft',
  'kochen' => 'Kochen / Hauswirtschaft / Gastronomie / Hotelfach',
  'friseur' => 'Friseur / Friseurin',
  'design' => 'Design',
  'verwaltung' => 'Verwaltung',
];

// --- Utils ---
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function old($k, $scope) { return h($_SESSION['form'][$scope][$k] ?? ($_POST[$k] ?? '')); }
function has_err($k, $errors){ return isset($errors[$k]) ? ' is-invalid' : ''; }

// CSRF
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function csrf_field(){ echo '<input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">'; }
function csrf_check(){ return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']); }

// Schritt-Navigation absichern
function require_step($stepKey){
  $order = ['personal','school','upload','review'];
  $idx = array_search($stepKey, $order, true);
  for ($i=0; $i<$idx; $i++){
    if (empty($_SESSION['form'][$order[$i]])) {
      header('Location: /form_'. $order[$i] .'.php'); exit;
    }
  }
}
