<?php
/**
 * Headers de sécurité HTTP.
 * À inclure en tout début de chaque page affichée.
 */
function send_security_headers(): void {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: "
        . "default-src 'self'; "
        . "style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; "
        . "font-src 'self' https://fonts.gstatic.com; "
        . "img-src 'self' data:; "
        . "script-src 'self' 'unsafe-inline'; "
        . "connect-src 'self';"
    );
}