<?php
/**
 * Valide, déplace et convertit en WebP un fichier uploadé.
 *
 * @param array  $file    Entrée $_FILES['photo']
 * @param string $legende Légende utilisée pour nommer le fichier
 * @param string $error   URL de redirection en cas d'erreur
 * @return string         Nom du fichier final (WebP)
 */
function handle_upload(array $file, string $legende, string $error_redirect): string {
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed, true)) {
        header('Location: ' . $error_redirect . '?error=type_fichier_invalide');
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        header('Location: ' . $error_redirect . '?error=fichier_trop_lourd');
        exit;
    }

    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $slug = strtolower($legende);
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');

    $filename = $slug . '-' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $dest     = __DIR__ . '/../uploads/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        header('Location: ' . $error_redirect . '?error=upload_echoue');
        exit;
    }

    // Conversion WebP
    $source = imagecreatefromstring(file_get_contents($dest));
    if ($source !== false) {
        $filename_webp = $slug . '-' . bin2hex(random_bytes(4)) . '.webp';
        $dest_webp     = __DIR__ . '/../uploads/' . $filename_webp;
        imagewebp($source, $dest_webp, 82);
        unlink($dest);
        $filename = $filename_webp;
    }

    return $filename;
}