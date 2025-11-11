<?php
// public/wizard/declare.php
declare(strict_types=1);
require __DIR__ . '/_common.php';
require_once __DIR__ . '/../../app/functions_form.php';
require_once __DIR__ . '/../../app/email.php'; // send_verification_email()

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); echo json_encode(['error'=>'method']); exit; }
if (!csrf_check_header()) { http_response_code(400); echo json_encode(['error'=>'csrf']); exit; }

$raw = file_get_contents('php://input');
$in = json_decode($raw, true) ?: [];
$email = trim((string)($in['email'] ?? ''));
$birth_de = trim((string)($in['geburtsdatum'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['error'=>'email']); exit; }
if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $birth_de)) { http_response_code(400); echo json_encode(['error'=>'birth']); exit; }

$birth_iso = to_iso_date($birth_de); // helper in functions_form.php
if (!$birth_iso) { http_response_code(400); echo json_encode(['error'=>'birth']); exit; }

// 1) ensure_application -> erzeugt retrieval_token + draft
[$appId, $token] = ensure_application($email, $birth_iso);

// 2) E-Mail-Verifikation triggern (nur, wenn noch nicht verifiziert oder Code nicht aktiv)
if (empty($_SESSION['email_verified'])) {
  $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  $_SESSION['email_verify'] = ['code'=>$code, 'ts'=>time(), 'email'=>$email];
  // Mail versenden (PHPMailer)
  send_verification_email($email, $code);
}

echo json_encode(['token'=>$token]);
