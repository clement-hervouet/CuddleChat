<?php
define('DB_HOST', 'caddy-db');
define('DB_PORT', '3306');

define('DB_USERS',           'users_base');
define('DB_CUDDLE',          'cuddlechat_base');

// Utilisateur restreint dédié à cuddlechat
define('DB_CUDDLE_USER',     getenv('CUDDLECHAT_DB_USER')     ?: 'app.cuddlechat');
define('DB_CUDDLE_PASSWORD', getenv('CUDDLECHAT_DB_PASSWORD') ?: '');

// Destinataires notifications mail
define('MAIL_USER_6', getenv('CUDDLECHAT_MAIL_USER_6') ?: '');
define('MAIL_USER_7', getenv('CUDDLECHAT_MAIL_USER_7') ?: '');