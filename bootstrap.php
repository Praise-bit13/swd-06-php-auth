<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'development' ? '1' : '0');
ini_set('display_startup_errors', APP_ENV === 'development' ? '1' : '0');

require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/csrf.php';
require_once __DIR__ . '/src/auth.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_name('SWDSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $lastActivity = $_SESSION['last_activity'] ?? null;

    if (!is_int($lastActivity) || time() - $lastActivity >= 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('error', 'Your session expired due to inactivity. Please sign in again.');
    } else {
        $_SESSION['last_activity'] = time();
    }
}
