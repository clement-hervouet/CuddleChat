<?php
/**
 * Génère un token CSRF et le stocke en session.
 * Retourne le token.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Retourne un champ input hidden avec le token CSRF.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '"/>';
}

/**
 * Valide le token CSRF du POST.
 * Redirige avec erreur si invalide.
 */
function csrf_verify(string $redirect): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        header('Location: ' . $redirect . '?error=csrf');
        exit;
    }
}