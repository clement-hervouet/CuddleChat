<?php
header('Content-Type: application/json');
$dir   = __DIR__ . '/assets/static/stamps/';
$files = array_values(array_filter(
    scandir($dir),
    fn($f) => preg_match('/\.(png|jpg|jpeg|webp|svg)$/i', $f)
));
echo json_encode($files);