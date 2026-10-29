<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$authUser = require_login();
$statement = db()->prepare(
    'SELECT name, email, password_hash FROM users WHERE id = :id LIMIT 1'
);
$statement->execute(['id' => $authUser['id']]);
$user = $statement->fetch();

if ($user === false) {
    $_SESSION = [];
    session_regenerate_id(true);
    flash('error', 'Your account could not be found. Please sign in again.');
    redirect(app_url('login.php'));
}

$errors = [];
$success = flash('profile_success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? null;

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your form session expired. Please try again.';
    } elseif ($form === 'profile') {
        $nameInput = $_POST['name'] ?? '';
        $emailInput = $_POST['email'] ?? '';
        $name = is_string($nameInput) ? trim($nameInput) : '';
        $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
        $nameLength = preg_match_all('/./us', $name);

        if ($nameLength === false || $nameLength < 1 || $nameLength > 120) {
            $errors[] = 'Enter a name between 1 and 120 characters long.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors[] = 'Enter a valid email address (maximum 254 characters).';
        } else {
            $statement = db()->prepare(
                'SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1'
            );
            $statement->execute([
                'email' => $email,
                'id' => $authUser['id'],
            ]);

            if ($statement->fetch() !== false) {
                $errors[] = 'That email address is already associated with another account.';
            }
        }

        if ($errors === []) {
            try {
                $statement = db()->prepare(
                    'UPDATE users SET name = :name, email = :email WHERE id = :id'
                );
                $statement->execute([
                    'name' => $name,
                    'email' => $email,
                    'id' => $authUser['id'],
                ]);
            } catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) !== 1062) {
                    throw $exception;
                }

                $errors[] = 'That email address is already associated with another account.';
            }

            if ($errors === []) {
                flash('profile_success', 'Your profile has been updated.');
                redirect('profile.php');
            }
        }
    } elseif ($form === 'password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';
        $currentPassword = is_string($currentPassword) ? $currentPassword : '';
        $newPassword = is_string($newPassword) ? $newPassword : '';
        $confirmation = is_string($confirmation) ? $confirmation : '';

        if (!password_verify($currentPassword, (string) $user['password_hash'])) {
            $errors[] = 'The current password is incorrect.';
        }

        $passwordLength = preg_match_all('/./us', $newPassword);

        if ($passwordLength === false || $passwordLength < 10) {
            $errors[] = 'New password must be at least 10 characters long.';
        }

        if (!preg_match('/\p{L}/u', $newPassword) || !preg_match('/\p{N}/u', $newPassword)) {
            $errors[] = 'New password must contain at least one letter and one number.';
        }

        if ($newPassword !== $confirmation) {
            $errors[] = 'New passwords do not match.';
        }

        if ($errors === []) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            if ($passwordHash === false) {
                throw new RuntimeException('Unable to securely hash the password.');
            }

            $statement = db()->prepare(
                'UPDATE users SET password_hash = :password_hash WHERE id = :id'
            );
            $statement->execute([
                'password_hash' => $passwordHash,
                'id' => $authUser['id'],
            ]);

            session_regenerate_id(true);
            $_SESSION['last_activity'] = time();
            unset($_SESSION['_csrf_token']);
            flash('profile_success', 'Your password has been changed.');
            redirect('profile.php');
        }
    } else {
        $errors[] = 'Choose a valid profile action.';
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your profile</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<?php render_navbar(); ?>
<main class="auth-page">
    <section class="auth-card" aria-labelledby="page-title">
        <div class="auth-heading">
            <p class="eyebrow">ACCOUNT SETTINGS</p>
            <h1 id="page-title">Your profile</h1>
            <p class="muted">Update your account details or change your password.</p>
        </div>

        <?php if (is_string($success) && $success !== ''): ?>
            <p class="notice notice-success" role="status"><?= e($success) ?></p>
        <?php endif; ?>
        <?php if ($errors !== []): ?>
            <div class="notice notice-error" role="alert">
                <p>Please correct the following:</p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <h2 class="section-title">Profile details</h2>
        <form method="post" action="profile.php">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="profile">
            <div class="form-field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" autocomplete="name" maxlength="120"
                       value="<?= e(old('name', (string) $user['name'])) ?>" required>
            </div>
            <div class="form-field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email" maxlength="254"
                       value="<?= e(old('email', (string) $user['email'])) ?>" required>
            </div>
            <button type="submit">Save profile</button>
        </form>

        <h2 class="section-title section-divider">Change password</h2>
        <form method="post" action="profile.php">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="password">
            <div class="form-field">
                <label for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password"
                       autocomplete="current-password" required>
            </div>
            <div class="form-field">
                <label for="new_password">New password</label>
                <input id="new_password" name="new_password" type="password"
                       autocomplete="new-password" required>
                <small>At least 10 characters, including a letter and a number.</small>
            </div>
            <div class="form-field">
                <label for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       autocomplete="new-password" required>
            </div>
            <button type="submit">Change password</button>
        </form>
    </section>
</main>
</body>
</html>
