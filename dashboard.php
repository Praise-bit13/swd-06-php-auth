<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$authUser = require_login();

$statement = db()->prepare(
    'SELECT name, email, role, created_at, last_login_at FROM users WHERE id = :id LIMIT 1'
);
$statement->execute(['id' => $authUser['id']]);
$user = $statement->fetch();

if ($user === false) {
    $_SESSION = [];
    session_regenerate_id(true);
    flash('error', 'Your account could not be found. Please sign in again.');
    redirect('login.php');
}

$success = flash('success');
$error = flash('error');
$lastLogin = $user['last_login_at'] === null
    ? 'Not available'
    : (new DateTimeImmutable((string) $user['last_login_at']))->format('F j, Y g:i A');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<main class="auth-page">
    <section class="auth-card" aria-labelledby="page-title">
        <div class="auth-heading">
            <p class="eyebrow">SWD AUTH</p>
            <h1 id="page-title">Welcome, <?= e((string) $user['name']) ?></h1>
            <p class="muted">Your account details are below.</p>
        </div>
        <?php if (is_string($success) && $success !== ''): ?>
            <p class="notice notice-success" role="status"><?= e($success) ?></p>
        <?php endif; ?>
        <?php if (is_string($error) && $error !== ''): ?>
            <p class="notice notice-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <dl class="account-details">
            <div><dt>Name</dt><dd><?= e((string) $user['name']) ?></dd></div>
            <div><dt>Email</dt><dd><?= e((string) $user['email']) ?></dd></div>
            <div><dt>Role</dt><dd><?= e((string) $user['role']) ?></dd></div>
            <div><dt>Member since</dt><dd><?= e((new DateTimeImmutable((string) $user['created_at']))->format('F j, Y')) ?></dd></div>
            <div><dt>Last login</dt><dd><?= e($lastLogin) ?></dd></div>
        </dl>

        <form method="post" action="logout.php">
            <?= csrf_field() ?>
            <button type="submit">Sign out</button>
        </form>
    </section>
</main>
</body>
</html>
