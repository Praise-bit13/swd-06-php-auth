<?php
declare(strict_types=1);

$environmentValue = static function (string $name, string $default): string {
    $value = getenv($name);

    return $value === false ? $default : $value;
};

define('DB_HOST', $environmentValue('DB_HOST', '127.0.0.1'));
define('DB_PORT', $environmentValue('DB_PORT', '3306'));
define('DB_NAME', $environmentValue('DB_NAME', 'swd_auth'));
define('DB_USER', $environmentValue('DB_USER', 'root'));
define('DB_PASSWORD', $environmentValue('DB_PASSWORD', ''));
define('APP_ENV', $environmentValue('APP_ENV', 'development'));
