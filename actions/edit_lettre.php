<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/headers.php';
require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/../includes/logger.php';
send_security_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_verify('../home.php');

$id          = (int) ($_POST['id']    ?? 0);
$date_lettre = $_POST['date_lettre']  ?? '';
$legende     = trim($_POST['legende'] ?? '');
$texte       = trim($_POST['texte']   ?? '');

if (!$id || !$date_lettre || !$legende || !$texte) {
    header('Location: ../home.php?error=champs_manquants');
    exit;
}

if (strlen($legende) < 2 || strlen($legende) > 255) {
    header('Location: ../home.php?error=legende_invalide');
    exit;
}

if (strlen($texte) < 2 || strlen($texte) > 5000) {
    header('Location: ../home.php?error=texte_invalide');
    exit;
}

$date_obj = DateTime::createFromFormat('Y-m-d', $date_lettre);
if (!$date_obj || $date_obj->format('Y-m-d') !== $date_lettre) {
    header('Location: ../home.php?error=date_invalide');
    exit;
}

$pdo  = db_cuddle();

// Vérifier propriété
$stmt = $pdo->prepare('SELECT user_id, photo_path, stamp FROM lettres WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$lettre = $stmt->fetch();

if (!$lettre || (int) $lettre['user_id'] !== CURRENT_USER_ID) {
    http_response_code(403);
    header('Location: ../home.php?error=non_autorise');
    exit;
}

$photo_path = $lettre['photo_path'];
$stamp      = $lettre['stamp'];

// Nouvelle photo (optionnel)
if (!empty($_FILES['photo']['tmp_name'])) {
    $old = __DIR__ . '/../uploads/' . basename($lettre['photo_path']);
    if (file_exists($old)) unlink($old);

    $photo_path = handle_upload($_FILES['photo'], $legende, '../home.php');
}

// Nouveau timbre (optionnel)
$stamps_dir  = __DIR__ . '/../assets/static/stamps/';
$stamp_files = array_values(array_filter(
    scandir($stamps_dir),
    fn($f) => preg_match('/\.(png|jpg|jpeg|webp|svg)$/i', $f)
));
$stamp_input = basename($_POST['stamp'] ?? '');
if ($stamp_input && in_array($stamp_input, $stamp_files, true)) {
    $stamp = $stamp_input;
}

$stmt = $pdo->prepare('
    UPDATE lettres
    SET date_lettre = :date_lettre,
        legende     = :legende,
        texte       = :texte,
        photo_path  = :photo_path,
        stamp       = :stamp
    WHERE id = :id
');

try {
    $stmt->execute([
        ':date_lettre' => $date_lettre,
        ':legende'     => $legende,
        ':texte'       => $texte,
        ':photo_path'  => $photo_path,
        ':stamp'       => $stamp,
        ':id'          => $id,
    ]);
    log_info('lettre_edited', ['lettre_id' => $id]);
} catch (\PDOException $e) {
    error_log($e->getMessage());
    header('Location: ../home.php?error=db_error');
    exit;
}

header('Location: ../home.php');
exit;