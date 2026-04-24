<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/headers.php';
require_once __DIR__ . '/../includes/logger.php';
send_security_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_verify('../home.php');

$id = (int) ($_POST['id'] ?? 0);
if (!$id) {
    header('Location: ../home.php?error=lettre_invalide');
    exit;
}

$pdo  = db_cuddle();

// Vérifier que la lettre appartient à l'utilisateur connecté
$stmt = $pdo->prepare('SELECT user_id, photo_path FROM lettres WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$lettre = $stmt->fetch();

if (!$lettre || (int) $lettre['user_id'] !== CURRENT_USER_ID) {
    http_response_code(403);
    header('Location: ../home.php?error=non_autorise');
    exit;
}

// Supprimer le fichier photo
$photo = __DIR__ . '/../uploads/' . basename($lettre['photo_path']);
if (file_exists($photo)) {
    unlink($photo);
}

// Supprimer la ligne en base
$stmt = $pdo->prepare('DELETE FROM lettres WHERE id = :id');
$stmt->execute([':id' => $id]);
log_info('lettre_deleted', ['lettre_id' => $id]);

header('Location: ../home.php');
exit;