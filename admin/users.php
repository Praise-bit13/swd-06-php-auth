<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$admin = require_role('admin');
$message = flash('admin_message');
$error = flash('admin_error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $error = 'Your form session expired. No role changes were made.';
    } else {
        $targetId = filter_var(
            $_POST['user_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $newRole = $_POST['role'] ?? null;

        if (!is_int($targetId) || !in_array($newRole, ['user', 'admin'], true)) {
            $error = 'Select a valid user and role.';
        } elseif ($targetId === $admin['id'] && $newRole === 'user') {
            $error = 'You cannot demote your own account.';
        } else {
            $statement = db()->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $targetId]);

            if ($statement->fetchColumn() === false) {
                $error = 'That user could not be found.';
            } else {
                $statement = db()->prepare('UPDATE users SET role = :role WHERE id = :id');
                $statement->execute([
                    'role' => $newRole,
                    'id' => $targetId,
                ]);
                flash('admin_message', 'User role updated.');
                redirect('users.php');
            }
        }
    }
}

$users = db()->query(
    'SELECT id, name, email, role, created_at, last_login_at
     FROM users
     ORDER BY created_at DESC, id DESC'
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage users</title>
    <link rel="stylesheet" href="../assets/auth.css">
</head>
<body>
<?php render_navbar(); ?>
<main class="admin-page">
    <section class="admin-panel" aria-labelledby="page-title">
        <div class="auth-heading">
            <p class="eyebrow">ADMINISTRATION</p>
            <h1 id="page-title">Manage users</h1>
            <p class="muted">Review accounts and change their access role.</p>
        </div>

        <?php if (is_string($message) && $message !== ''): ?>
            <p class="notice notice-success" role="status"><?= e($message) ?></p>
        <?php endif; ?>
        <?php if (is_string($error) && $error !== ''): ?>
            <p class="notice notice-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <div class="table-wrap">
            <table class="user-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Last login</th>
                    <th>Manage role</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= e((string) $user['name']) ?></td>
                        <td><?= e((string) $user['email']) ?></td>
                        <td><?= e((string) $user['role']) ?></td>
                        <td><?= e((new DateTimeImmutable((string) $user['created_at']))->format('Y-m-d H:i')) ?></td>
                        <td>
                            <?= $user['last_login_at'] === null
                                ? 'Never'
                                : e((new DateTimeImmutable((string) $user['last_login_at']))->format('Y-m-d H:i')) ?>
                        </td>
                        <td>
                            <?php if ((int) $user['id'] === $admin['id']): ?>
                                <span class="muted">Your own role cannot be demoted.</span>
                            <?php else: ?>
                                <form class="role-form" method="post" action="users.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                    <select name="role" aria-label="Role for <?= e((string) $user['name']) ?>">
                                        <option value="user"<?= $user['role'] === 'user' ? ' selected' : '' ?>>User</option>
                                        <option value="admin"<?= $user['role'] === 'admin' ? ' selected' : '' ?>>Admin</option>
                                    </select>
                                    <button class="small-button" type="submit">Save</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
