<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid form token.');
}

$_SESSION = [];
$cookie = session_get_cookie_params();

session_destroy();

if (ini_get('session.use_cookies') === '1') {
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'] ?? 'Lax',
    ]);
}

session_id('');
session_start();
flash('success', 'You have been signed out.');
redirect('login.php');
