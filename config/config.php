<?php
define('DB_HOST', 'caddy-db');
define('DB_PORT', '3306');

define('DB_USERS',    'users_base');
define('DB_CUDDLE',   'cuddlechat_base');
define('DB_USER',     getenv('MYSQL_USER'));
define('DB_PASSWORD', getenv('MYSQL_PASSWORD'));
