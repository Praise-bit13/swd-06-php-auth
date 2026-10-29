<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$userId = $_SESSION['user_id'] ?? null;

if (!is_int($userId)) {
    redirect('register.php');
}

$statement = db()->prepare('SELECT name, email FROM users WHERE id = :id LIMIT 1');
$statement->execute(['id' => $userId]);
$user = $statement->fetch();

if ($user === false) {
    unset($_SESSION['user_id']);
    redirect('register.php');
}

$success = flash('success');
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
            <p class="muted">You are signed in as <?= e((string) $user['email']) ?>.</p>
        </div>
        <?php if (is_string($success) && $success !== ''): ?>
            <p class="notice notice-success" role="status"><?= e($success) ?></p>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
