<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$errors = flash('errors');
$errors = is_array($errors) ? $errors : [];
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nameInput = $_POST['name'] ?? '';
    $emailInput = $_POST['email'] ?? '';
    $passwordInput = $_POST['password'] ?? '';
    $confirmationInput = $_POST['password_confirmation'] ?? '';

    $name = is_string($nameInput) ? trim($nameInput) : '';
    $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';
    $confirmation = is_string($confirmationInput) ? $confirmationInput : '';

    $csrfValid = csrf_verify($_POST['csrf_token'] ?? null);

    if (!$csrfValid) {
        $errors[] = 'Your form session expired. Please try again.';
    }

    $nameLength = preg_match_all('/./us', $name);

    if ($nameLength === false || $nameLength < 1 || $nameLength > 120) {
        $errors[] = 'Enter a name between 1 and 120 characters long.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $errors[] = 'Enter a valid email address (maximum 254 characters).';
    } elseif ($csrfValid) {
        $statement = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);

        if ($statement->fetch() !== false) {
            $errors[] = 'An account with that email address already exists.';
        }
    }

    $passwordLength = preg_match_all('/./us', $password);

    if ($passwordLength === false || $passwordLength < 10) {
        $errors[] = 'Password must be at least 10 characters long.';
    }

    if (!preg_match('/\p{L}/u', $password) || !preg_match('/\p{N}/u', $password)) {
        $errors[] = 'Password must contain at least one letter and one number.';
    }

    if ($password !== $confirmation) {
        $errors[] = 'Passwords do not match.';
    }

    if ($errors === []) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new RuntimeException('Unable to securely hash the password.');
        }

        try {
            $statement = db()->prepare(
                'INSERT INTO users (name, email, password_hash, last_login_at)
                 VALUES (:name, :email, :password_hash, CURRENT_TIMESTAMP)'
            );
            $statement->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
            ]);
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) !== 1062) {
                throw $exception;
            }

            $errors[] = 'An account with that email address already exists.';
        }

        if ($errors === []) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) db()->lastInsertId();
            $_SESSION['role'] = 'user';
            $_SESSION['last_activity'] = time();
            unset($_SESSION['_csrf_token']);
            flash('success', 'Your account has been created.');
            redirect('dashboard.php');
        }
    }

    $_SESSION['_old'] = [
        'name' => $name,
        'email' => $email,
    ];
    flash('errors', $errors);
    redirect('register.php');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<main class="auth-page">
    <section class="auth-card" aria-labelledby="page-title">
        <div class="auth-heading">
            <p class="eyebrow">SWD AUTH</p>
            <h1 id="page-title">Create your account</h1>
            <p class="muted">Get started with a few details.</p>
        </div>

        <?php if (is_string($success) && $success !== ''): ?>
            <p class="notice notice-success" role="status"><?= e($success) ?></p>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="notice notice-error" role="alert">
                <p>Please correct the following:</p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <?php if (is_string($error)): ?>
                            <li><?= e($error) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="register.php">
            <?= csrf_field() ?>
            <div class="form-field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" autocomplete="name" maxlength="120"
                       value="<?= e(old('name')) ?>" required>
            </div>

            <div class="form-field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email" maxlength="254"
                       value="<?= e(old('email')) ?>" required>
            </div>

            <div class="form-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
                <small>At least 10 characters, including a letter and a number.</small>
            </div>

            <div class="form-field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       autocomplete="new-password" required>
            </div>

            <button type="submit">Create account</button>
        </form>
    </section>
</main>
</body>
</html>
