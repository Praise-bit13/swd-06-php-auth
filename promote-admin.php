<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/db.php';

if ($argc !== 2 || !is_string($argv[1]) || !filter_var($argv[1], FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php promote-admin.php email@example.com\n");
    exit(2);
}

$email = strtolower(trim($argv[1]));

if (strlen($email) > 254) {
    fwrite(STDERR, "Email address must be no longer than 254 characters.\n");
    exit(2);
}

$statement = db()->prepare('UPDATE users SET role = :role WHERE email = :email');
$statement->execute([
    'role' => 'admin',
    'email' => $email,
]);

if ($statement->rowCount() > 0) {
    fwrite(STDOUT, sprintf("Promoted %s to admin.\n", $email));
    exit(0);
}

$statement = db()->prepare('SELECT role FROM users WHERE email = :email LIMIT 1');
$statement->execute(['email' => $email]);
$currentRole = $statement->fetchColumn();

if ($currentRole === false) {
    fwrite(STDERR, sprintf("No user found with email %s.\n", $email));
    exit(1);
}

fwrite(STDOUT, sprintf("%s is already an admin.\n", $email));
