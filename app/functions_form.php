<?php
// app/functions_form.php
// UTF-8, no BOM
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/** DD.MM.YYYY -> YYYY-MM-DD (oder '' bei Ungültigkeit) */
function norm_date_dmy_to_ymd(string $dmy): string {
    $dmy = trim($dmy);
    if (!preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $dmy, $m)) return '';
    [$all, $d, $mth, $y] = $m;
    if (!checkdate((int)$mth, (int)$d, (int)$y)) return '';
    return sprintf('%04d-%02d-%02d', (int)$y, (int)$mth, (int)$d);
}

/**
 * Sorgt dafür, dass in `applications` ein Datensatz für (token, geburtsdatum) existiert.
 * - email: kann leerer String sein (No-Email-Mode) oder eine gültige E-Mail.
 * - Gibt ['ok'=>true, 'id'=>application_id, 'token'=>..., 'created'=>bool] zurück.
 */
function ensure_application_token_dob(?string $email, string $geburtsdatum_ymd, string $token): array {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // 1) vorhandene Application suchen
        $sel = $pdo->prepare("SELECT id, email, geburtsdatum FROM applications WHERE retrieval_token = :t LIMIT 1");
        $sel->execute([':t' => $token]);
        $row = $sel->fetch();

        $emailToStore = trim((string)$email);
        // NOT NULL Constraint im Schema -> bei fehlender Mail leerer String
        if ($emailToStore === '') $emailToStore = '';

        if ($row) {
            $appId = (int)$row['id'];
            // ggf. Geburtsdatum/E-Mail aktualisieren (falls noch leer oder geändert)
            $upd = $pdo->prepare("
                UPDATE applications
                SET email = :e, geburtsdatum = :dob, updated_at = NOW()
                WHERE id = :id
            ");
            $upd->execute([
                ':e'   => $emailToStore,
                ':dob' => $geburtsdatum_ymd,
                ':id'  => $appId,
            ]);
            $pdo->commit();
            return ['ok'=>true, 'id'=>$appId, 'token'=>$token, 'created'=>false];
        } else {
            // neu anlegen
            $ins = $pdo->prepare("
                INSERT INTO applications (retrieval_token, email, geburtsdatum, status, created_at, updated_at)
                VALUES (:t, :e, :dob, 'draft', NOW(), NOW())
            ");
            $ins->execute([
                ':t'   => $token,
                ':e'   => $emailToStore,
                ':dob' => $geburtsdatum_ymd,
            ]);
            $appId = (int)$pdo->lastInsertId();
            $pdo->commit();
            return ['ok'=>true, 'id'=>$appId, 'token'=>$token, 'created'=>true];
        }
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('ensure_application_token_dob: '.$e->getMessage());
        return ['ok'=>false, 'err'=>$e->getMessage()];
    }
}

/**
 * Persistiert Schritt "personal" + "contacts".
 * Erwartet:
 *  - $payload['name'], ['vorname'], ['geschlecht'], ['geburtsdatum'] (TT.MM.JJJJ oder YYYY-MM-DD),
 *    ['geburtsort_land'], ['staatsang'], ['strasse'], ['plz'], ['wohnort'], ['telefon'], ['email'], ['dsgvo_ok'],
 *    ['contacts'] = array von ['rolle','name','tel','mail','notiz']
 */
function save_personal_block(int $applicationId, array $payload): array {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Datum normalisieren
        $dob_in = (string)($payload['geburtsdatum'] ?? '');
        $dob = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob_in) ? $dob_in : norm_date_dmy_to_ymd($dob_in);
        if ($dob === '') {
            throw new \RuntimeException('Ungültiges Datum im personal-Block.');
        }

        // upsert personal
        $exists = $pdo->prepare("SELECT application_id FROM personal WHERE application_id = :id");
        $exists->execute([':id'=>$applicationId]);
        $has = (bool)$exists->fetchColumn();

        if ($has) {
            $upd = $pdo->prepare("
                UPDATE personal
                SET name=:name, vorname=:vorname, geschlecht=:geschlecht,
                    geburtsdatum=:dob, geburtsort_land=:ort,
                    staatsang=:staatsang, strasse=:strasse, plz=:plz, wohnort=:wohnort,
                    telefon=:telefon, email=:email, dsgvo_ok=:dsgvo, updated_at=NOW()
                WHERE application_id=:id
            ");
            $upd->execute([
                ':name'      => (string)$payload['name'],
                ':vorname'   => (string)$payload['vorname'],
                ':geschlecht'=> (string)$payload['geschlecht'],
                ':dob'       => $dob,
                ':ort'       => (string)$payload['geburtsort_land'],
                ':staatsang' => (string)$payload['staatsang'],
                ':strasse'   => (string)$payload['strasse'],
                ':plz'       => (string)$payload['plz'],
                ':wohnort'   => (string)$payload['wohnort'],
                ':telefon'   => (string)$payload['telefon'],
                ':email'     => (string)$payload['email'],
                ':dsgvo'     => !empty($payload['dsgvo_ok']) ? 1 : 0,
                ':id'        => $applicationId,
            ]);
        } else {
            $ins = $pdo->prepare("
                INSERT INTO personal
                    (application_id, name, vorname, geschlecht, geburtsdatum, geburtsort_land, staatsang, strasse, plz, wohnort, telefon, email, dsgvo_ok, created_at, updated_at)
                VALUES
                    (:id, :name, :vorname, :geschlecht, :dob, :ort, :staatsang, :strasse, :plz, :wohnort, :telefon, :email, :dsgvo, NOW(), NOW())
            ");
            $ins->execute([
                ':id'        => $applicationId,
                ':name'      => (string)$payload['name'],
                ':vorname'   => (string)$payload['vorname'],
                ':geschlecht'=> (string)$payload['geschlecht'],
                ':dob'       => $dob,
                ':ort'       => (string)$payload['geburtsort_land'],
                ':staatsang' => (string)$payload['staatsang'],
                ':strasse'   => (string)$payload['strasse'],
                ':plz'       => (string)$payload['plz'],
                ':wohnort'   => (string)$payload['wohnort'],
                ':telefon'   => (string)$payload['telefon'],
                ':email'     => (string)$payload['email'],
                ':dsgvo'     => !empty($payload['dsgvo_ok']) ? 1 : 0,
            ]);
        }

        // contacts neu schreiben (einfachste Form: löschen + einfügen)
        $del = $pdo->prepare("DELETE FROM contacts WHERE application_id = :id");
        $del->execute([':id'=>$applicationId]);

        $contacts = $payload['contacts'] ?? [];
        if (is_array($contacts) && count($contacts) > 0) {
            $insC = $pdo->prepare("
                INSERT INTO contacts (application_id, rolle, name, tel, mail, notiz, created_at)
                VALUES (:id, :rolle, :name, :tel, :mail, :notiz, NOW())
            ");
            foreach ($contacts as $c) {
                $insC->execute([
                    ':id'    => $applicationId,
                    ':rolle' => (string)($c['rolle'] ?? ''),
                    ':name'  => (string)($c['name'] ?? ''),
                    ':tel'   => (string)($c['tel'] ?? ''),
                    ':mail'  => (string)($c['mail'] ?? ''),
                    ':notiz' => (string)($c['notiz'] ?? ''),
                ]);
            }
        }

        $pdo->commit();
        return ['ok'=>true];
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('save_personal_block: '.$e->getMessage());
        return ['ok'=>false, 'err'=>$e->getMessage()];
    }
}

/**
 * High-level Save für "personal" (wie in form_personal.php verwendet).
 * - No-Email-Mode: schreibt in DB erst, wenn ein valides DOB vorliegt.
 * - Mit E-Mail (später evtl. verifiziert): gleiches Vorgehen, aber E-Mail wird mit gespeichert.
 *
 * Rückgabe:
 *  - ['ok'=>true, 'token'=>..., 'app_id'=>int] bei Erfolg
 *  - ['ok'=>false, 'err'=>'nur Session (DOB fehlt)'] wenn noch nicht in DB gespeichert wurde
 */
function save_scope_allow_noemail(string $scope, array $payload): array {
    if ($scope !== 'personal') {
        return ['ok'=>false, 'err'=>'unsupported scope'];
    }

    // Access-Token sicherstellen
    $token = current_access_token();
    if ($token === '') $token = issue_access_token();

    // DOB benötigt, um in DB zu speichern
    $dob_ymd = norm_date_dmy_to_ymd((string)($payload['geburtsdatum'] ?? ''));
    if ($dob_ymd === '') {
        // Noch kein persistentes Speichern – nur Session.
        return ['ok'=>false, 'err'=>'nur Session (DOB fehlt)', 'token'=>$token];
    }

    // E-Mail (darf leer sein – dann speichern wir '')
    $email = (string)($payload['email'] ?? '');
    // applications-Eintrag sicherstellen/aktualisieren
    $ens = ensure_application_token_dob($email, $dob_ymd, $token);
    if (!($ens['ok'] ?? false)) {
        return ['ok'=>false, 'err'=>'applications: '.$ens['err']];
    }
    $appId = (int)$ens['id'];

    // personal & contacts schreiben
    $p = save_personal_block($appId, $payload);
    if (!($p['ok'] ?? false)) {
        return ['ok'=>false, 'err'=>'personal: '.$p['err']];
    }

    return ['ok'=>true, 'token'=>$token, 'app_id'=>$appId];
}
