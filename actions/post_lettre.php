<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$date_lettre = $_POST['date_lettre']   ?? '';
$legende     = trim($_POST['legende']  ?? '');
$texte       = trim($_POST['texte']    ?? '');

if (!$date_lettre || !$legende || !$texte || empty($_FILES['photo'])) {
    header('Location: /peluches/home.php?error=champs_manquants');
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_lettre)) {
    header('Location: /peluches/home.php?error=date_invalide');
    exit;
}

$file      = $_FILES['photo'];
$allowed   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$finfo     = new finfo(FILEINFO_MIME_TYPE);
$mime      = $finfo->file($file['tmp_name']);

if (!in_array($mime, $allowed, true)) {
    header('Location: /peluches/home.php?error=type_fichier_invalide');
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    header('Location: /peluches/home.php?error=fichier_trop_lourd');
    exit;
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
$dest     = __DIR__ . '/../uploads/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    header('Location: /peluches/home.php?error=upload_echoue');
    exit;
}

$pdo = db_cuddle();
$stmt = $pdo->prepare('
    INSERT INTO lettres (user_id, date_lettre, legende, texte, photo_path)
    VALUES (:user_id, :date_lettre, :legende, :texte, :photo_path)
');
$stmt->execute([
    ':user_id'     => CURRENT_USER_ID,
    ':date_lettre' => $date_lettre,
    ':legende'     => $legende,
    ':texte'       => $texte,
    ':photo_path'  => $filename,
]);

header('Location: /peluches/home.php');
exit;
