<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

require_guest();

$error = flash('error');
$success = flash('success');
$loginError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailInput = $_POST['email'] ?? '';
    $passwordInput = $_POST['password'] ?? '';
    $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $loginError = 'Your form session expired. Please try again.';
    } else {
        $user = null;

        if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 254) {
            $statement = db()->prepare(
                'SELECT id, password_hash, role FROM users WHERE email = :email LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();
        }

        $verificationHash = is_array($user)
            ? (string) $user['password_hash']
            : '$2y$10$E7Y1QIBXPLuKwnSpsBSm4OjXuz6VQLrq4SE2yAAcl3OU4d4HHHSW.';
        $passwordMatches = password_verify($password, $verificationHash) && is_array($user);

        if (!$passwordMatches) {
            $loginError = 'Invalid email or password';
        } else {
            $userId = (int) $user['id'];
            $passwordHash = (string) $user['password_hash'];

            if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
                $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);

                if ($newPasswordHash === false) {
                    throw new RuntimeException('Unable to securely hash the password.');
                }

                $statement = db()->prepare(
                    'UPDATE users SET password_hash = :password_hash, last_login_at = CURRENT_TIMESTAMP WHERE id = :id'
                );
                $statement->execute([
                    'password_hash' => $newPasswordHash,
                    'id' => $userId,
                ]);
            } else {
                $statement = db()->prepare(
                    'UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id'
                );
                $statement->execute(['id' => $userId]);
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['role'] = (string) $user['role'];
            $_SESSION['last_activity'] = time();
            unset($_SESSION['_csrf_token']);

            $intendedUrl = safe_intended_url($_SESSION['intended_url'] ?? null);
            unset($_SESSION['intended_url']);
            redirect($intendedUrl ?? 'dashboard.php');
        }
    }

    $_SESSION['_old'] = ['email' => $email];
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<main class="auth-page">
    <section class="auth-card" aria-labelledby="page-title">
        <div class="auth-heading">
            <p class="eyebrow">SWD AUTH</p>
            <h1 id="page-title">Welcome back</h1>
            <p class="muted">Sign in to your account.</p>
        </div>

        <?php if (is_string($success) && $success !== ''): ?>
            <p class="notice notice-success" role="status"><?= e($success) ?></p>
        <?php endif; ?>

        <?php if (is_string($error) && $error !== ''): ?>
            <p class="notice notice-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>
        <?php if (is_string($loginError) && $loginError !== ''): ?>
            <p class="notice notice-error" role="alert"><?= e($loginError) ?></p>
        <?php endif; ?>

        <form method="post" action="login.php">
            <?= csrf_field() ?>
            <div class="form-field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="username" maxlength="254"
                       value="<?= e(old('email')) ?>" required>
            </div>

            <div class="form-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>

            <button type="submit">Sign in</button>
        </form>
    </section>
</main>
</body>
</html>
