<?php
define('DB_HOST', 'caddy-db');
define('DB_PORT', '3306');

define('DB_CUDDLE', 'cuddlechat_base');

// Utilisateur restreint dédié à cuddlechat
// Injecter CUDDLECHAT_DB_USER et CUDDLECHAT_DB_PASSWORD via variables d'env dans compose
define('DB_CUDDLE_USER',    'app.cuddlechat');
define('DB_CUDDLE_PASSWORD','cuddlechat');