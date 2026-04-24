<?php
/**
 * Logger applicatif CuddleChat.
 * Écrit dans /var/log/php/cuddlechat.log
 */
function app_log(string $level, string $action, array $context = []): void {
    $log_file = '/var/log/php/cuddlechat.log';
    $date     = date('Y-m-d H:i:s');
    $user_id  = $_SESSION['id_user'] ?? 'guest';
    $ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ctx      = !empty($context) ? ' ' . json_encode($context) : '';

    $line = "[{$date}] [{$level}] [user:{$user_id}] [ip:{$ip}] {$action}{$ctx}" . PHP_EOL;

    error_log($line, 3, $log_file);
}

function log_info(string $action, array $context = []): void {
    app_log('INFO', $action, $context);
}

function log_warning(string $action, array $context = []): void {
    app_log('WARNING', $action, $context);
}

function log_error(string $action, array $context = []): void {
    app_log('ERROR', $action, $context);
}