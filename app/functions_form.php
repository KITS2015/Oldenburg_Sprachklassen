<?php
// app/functions_form.php
// UTF-8, no BOM
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Legt (falls nicht vorhanden) einen applications-Eintrag als 'draft' an,
 * erzeugt retrieval_token und gibt [application_id, retrieval_token] zurück.
 * Speichert beides in $_SESSION['application'].
 */
function ensure_application(string $email, string $geburtsdatum_yyyy_mm_dd): array {
    if (!isset($_SESSION['application']['id'], $_SESSION['application']['token'])) {
        $pdo = db();
        $token = random_hex32();

        // Kopf anlegen
        $stmt = $pdo->prepare("
            INSERT INTO applications (retrieval_token, email, geburtsdatum, status, submit_ip)
            VALUES (:tok, :email, :birth, 'draft', :ip)
        ");
        $stmt->execute([
            ':tok'   => $token,
            ':email' => $email,
            ':birth' => $geburtsdatum_yyyy_mm_dd,
            ':ip'    => ip_to_bin($_SERVER['REMOTE_ADDR'] ?? null),
        ]);

        $appId = (int)$pdo->lastInsertId();

        $_SESSION['application'] = [
            'id'    => $appId,
            'token' => $token,
        ];
    }
    return [ (int)$_SESSION['application']['id'], (string)$_SESSION['application']['token'] ];
}

/**
 * Speichert Schritt 1 (personal) – Upsert über PRIMARY KEY (application_id).
 * Erwartet $data aus validiertem Formular.
 * $data['geburtsdatum'] erwartet Format TT.MM.JJJJ.
 */
function save_personal(array $data): void {
    $pdo = db();

    // Falls noch kein application-Datensatz existiert, jetzt anlegen (draft).
    // Für ensure_application benötigen wir E-Mail und Geburtsdatum (ISO).
    $birth_iso = to_iso_date($data['geburtsdatum']); // 'YYYY-MM-DD'
    [$appId] = ensure_application($data['email'], $birth_iso);

    // Kopf (applications) ggf. aktualisieren (Email/Birth können sich geändert haben)
    $pdo->prepare("
        UPDATE applications
           SET email = :email, geburtsdatum = :birth
         WHERE id = :id
    ")->execute([
        ':email' => $data['email'],
        ':birth' => $birth_iso,
        ':id'    => $appId,
    ]);

    // Upsert personal
    $pdo->prepare("
        INSERT INTO personal (
            application_id, name, vorname, geschlecht, geburtsdatum,
            geburtsort_land, staatsang, strasse, plz, wohnort,
            telefon, email, dsgvo_ok
        ) VALUES (
            :id, :name, :vorname, :geschlecht, :birth,
            :ortland, :staatsang, :strasse, :plz, :wohnort,
            :telefon, :email, :dsgvo
        )
        ON DUPLICATE KEY UPDATE
            name=:name, vorname=:vorname, geschlecht=:geschlecht, geburtsdatum=:birth,
            geburtsort_land=:ortland, staatsang=:staatsang, strasse=:strasse, plz=:plz, wohnort=:wohnort,
            telefon=:telefon, email=:email, dsgvo_ok=:dsgvo
    ")->execute([
        ':id'        => $appId,
        ':name'      => $data['name'],
        ':vorname'   => $data['vorname'],
        ':geschlecht'=> $data['geschlecht'],
        ':birth'     => $birth_iso,
        ':ortland'   => $data['geburtsort_land'],
        ':staatsang' => $data['staatsang'],
        ':strasse'   => $data['strasse'],
        ':plz'       => $data['plz'],
        ':wohnort'   => $data['wohnort'] ?? 'Oldenburg (Oldb)',
        ':telefon'   => $data['telefon'],
        ':email'     => $data['email'],
        ':dsgvo'     => !empty($data['dsgvo_ok']) ? 1 : 0,
    ]);

    // Strukturierte Zusatzkontakte optional speichern (0..n)
    if (!empty($data['contacts']) && is_array($data['contacts'])) {
        // einfache Strategie: erst löschen, dann neu einfügen
        $pdo->prepare("DELETE FROM contacts WHERE application_id = :id")->execute([':id' => $appId]);

        $ins = $pdo->prepare("
            INSERT INTO contacts (application_id, rolle, name, tel, mail, notiz)
            VALUES (:id, :rolle, :name, :tel, :mail, :notiz)
        ");
        foreach ($data['contacts'] as $c) {
            if (empty($c['name'])) continue;
            $ins->execute([
                ':id'    => $appId,
                ':rolle' => trim((string)($c['rolle'] ?? '')),
                ':name'  => trim((string)$c['name']),
                ':tel'   => trim((string)($c['tel'] ?? '')),
                ':mail'  => trim((string)($c['mail'] ?? '')),
                ':notiz' => trim((string)($c['notiz'] ?? '')),
            ]);
        }
    }

    audit($appId, 'update_personal', ['name'=>$data['name'], 'vorname'=>$data['vorname']]);
}

/**
 * Speichert Schritt 2 (school) – Upsert.
 * $data: schule_besucht (0/1), schule_jahre (int|null), seit_monat (1..12|null), seit_jahr (int|null),
 *        deutsch_niveau (enum), deutsch_jahre (decimal), interessen (string|null)
 */
function save_school(array $data): void {
    if (!isset($_SESSION['application']['id'])) return;
    $appId = (int)$_SESSION['application']['id'];
    $pdo = db();

    $pdo->prepare("
        INSERT INTO school (
            application_id, schule_besucht, schule_jahre, seit_monat, seit_jahr,
            deutsch_niveau, deutsch_jahre, interessen
        ) VALUES (
            :id, :besucht, :jahre, :monat, :jahr,
            :niveau, :djahre, :interessen
        )
        ON DUPLICATE KEY UPDATE
            schule_besucht=:besucht, schule_jahre=:jahre, seit_monat=:monat, seit_jahr=:jahr,
            deutsch_niveau=:niveau, deutsch_jahre=:djahre, interessen=:interessen
    ")->execute([
        ':id'        => $appId,
        ':besucht'   => !empty($data['schule_besucht']) ? 1 : 0,
        ':jahre'     => $data['schule_jahre'] !== '' ? (int)$data['schule_jahre'] : null,
        ':monat'     => $data['seit_monat']   !== '' ? (int)$data['seit_monat']   : null,
        ':jahr'      => $data['seit_jahr']    !== '' ? (int)$data['seit_jahr']    : null,
        ':niveau'    => $data['deutsch_niveau'] ?? null,
        ':djahre'    => ($data['deutsch_jahre'] ?? '') !== '' ? (float)$data['deutsch_jahre'] : null,
        ':interessen'=> $data['interessen'] ?? null,
    ]);

    audit($appId, 'update_school', []);
}

/**
 * Speichert/ersetzt Upload-Metadaten für einen Typ (zeugnis|lebenslauf).
 * Erwartet bereits erfolgreich gespeicherte Datei im uploads/-Verzeichnis.
 */
function save_upload_meta(string $typ, string $filename, string $mime, int $size): void {
    if (!isset($_SESSION['application']['id'])) return;
    $appId = (int)$_SESSION['application']['id'];
    $pdo = db();

    $pdo->prepare("
        INSERT INTO uploads (application_id, typ, filename, mime, size_bytes)
        VALUES (:id, :typ, :file, :mime, :size)
        ON DUPLICATE KEY UPDATE
            filename=:file, mime=:mime, size_bytes=:size, uploaded_at=NOW()
    ")->execute([
        ':id'   => $appId,
        ':typ'  => $typ,
        ':file' => $filename,
        ':mime' => $mime,
        ':size' => $size,
    ]);

    audit($appId, 'upload_'.$typ, ['file'=>$filename]);
}

/**
 * Als „Absenden“ markieren.
 */
function submit_application(): void {
    if (!isset($_SESSION['application']['id'])) return;
    $pdo = db();
    $pdo->prepare("
        UPDATE applications
           SET status='submitted', submit_ip=:ip
         WHERE id=:id
    ")->execute([
        ':id' => (int)$_SESSION['application']['id'],
        ':ip' => ip_to_bin($_SERVER['REMOTE_ADDR'] ?? null),
    ]);
    audit((int)$_SESSION['application']['id'], 'submit', []);
}

/**
 * Lädt alles zu einer Bewerbung (per token + geburtsdatum) und gibt ein Array
 * für Vorbelegung der Formulare zurück. Liefert null, wenn nichts gefunden.
 */
function load_by_token_and_birth(string $token, string $birth_yyyy_mm_dd): ?array {
    $pdo = db();

    $app = $pdo->prepare("
        SELECT a.id, a.retrieval_token, a.email, a.geburtsdatum, a.status
          FROM applications a
         WHERE a.retrieval_token = :tok
           AND a.geburtsdatum = :birth
         LIMIT 1
    ");
    $app->execute([':tok'=>$token, ':birth'=>$birth_yyyy_mm_dd]);
    $A = $app->fetch();
    if (!$A) return null;

    $id = (int)$A['id'];

    // personal
    $P = $pdo->prepare("SELECT * FROM personal WHERE application_id=:id LIMIT 1");
    $P->execute([':id'=>$id]);
    $personal = $P->fetch() ?: [];

    // contacts
    $C = $pdo->prepare("SELECT rolle,name,tel,mail,notiz FROM contacts WHERE application_id=:id ORDER BY id ASC");
    $C->execute([':id'=>$id]);
    $contacts = $C->fetchAll();

    // school
    $S = $pdo->prepare("SELECT * FROM school WHERE application_id=:id LIMIT 1");
    $S->execute([':id'=>$id]);
    $school = $S->fetch() ?: [];

    // uploads
    $U = $pdo->prepare("SELECT typ, filename, mime, size_bytes, uploaded_at FROM uploads WHERE application_id=:id");
    $U->execute([':id'=>$id]);
    $uploads = [];
    foreach ($U as $row) $uploads[$row['typ']] = $row;

    // Session für Wizard setzen
    $_SESSION['application'] = ['id'=>$id, 'token'=>$A['retrieval_token']];
    $_SESSION['form'] = [
        'personal' => [
            'name'            => $personal['name']            ?? '',
            'vorname'         => $personal['vorname']         ?? '',
            'geschlecht'      => $personal['geschlecht']      ?? '',
            'geburtsdatum'    => from_iso_date($personal['geburtsdatum'] ?? null), // zurück zu TT.MM.JJJJ
            'geburtsort_land' => $personal['geburtsort_land'] ?? '',
            'staatsang'       => $personal['staatsang']       ?? '',
            'strasse'         => $personal['strasse']         ?? '',
            'plz'             => $personal['plz']             ?? '',
            'wohnort'         => $personal['wohnort']         ?? 'Oldenburg (Oldb)',
            'telefon'         => $personal['telefon']         ?? '',
            'email'           => $personal['email']           ?? $A['email'],
            'dsgvo_ok'        => !empty($personal['dsgvo_ok']) ? '1' : '0',
            'contacts'        => $contacts,
        ],
        'school' => [
            'schule_besucht' => (string)($school['schule_besucht'] ?? '0'),
            'schule_jahre'   => $school['schule_jahre'] ?? '',
            'seit_monat'     => $school['seit_monat']   ?? '',
            'seit_jahr'      => $school['seit_jahr']    ?? '',
            'deutsch_niveau' => $school['deutsch_niveau'] ?? '',
            'deutsch_jahre'  => $school['deutsch_jahre']  ?? '',
            'interessen'     => $school['interessen']     ?? '',
        ],
        'upload' => [
            'zeugnis'        => $uploads['zeugnis']['filename']   ?? null,
            'lebenslauf'     => $uploads['lebenslauf']['filename']?? null,
            'zeugnis_spaeter'=> $_SESSION['form']['upload']['zeugnis_spaeter'] ?? '0',
        ],
    ];

    return [
        'application' => $A,
        'personal'    => $personal,
        'contacts'    => $contacts,
        'school'      => $school,
        'uploads'     => $uploads,
    ];
}

/** Audit-Helper */
function audit(int $application_id, string $event, array $meta = []): void {
    $pdo = db();
    $pdo->prepare("
        INSERT INTO audit_log (application_id, event, meta_json)
        VALUES (:id, :event, :meta)
    ")->execute([
        ':id'    => $application_id,
        ':event' => $event,
        ':meta'  => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
    ]);
}

/** TT.MM.JJJJ -> YYYY-MM-DD  (oder null wenn leer/ungültig) */
function to_iso_date(?string $de): ?string {
    if (!$de) return null;
    if (!preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $de, $m)) return null;
    [$all,$d,$mth,$y] = $m;
    if (!checkdate((int)$mth,(int)$d,(int)$y)) return null;
    return sprintf('%04d-%02d-%02d', $y, $mth, $d);
}

/** YYYY-MM-DD -> TT.MM.JJJJ (oder '') */
function from_iso_date(?string $iso): string {
    if (!$iso || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m)) return '';
    return $m[3].'.'.$m[2].'.'.$m[1];
}
