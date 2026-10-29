<?php
declare(strict_types=1);

function current_user(): ?array
{
    $userId = $_SESSION['user_id'] ?? null;

    if (!is_int($userId)) {
        return null;
    }

    $statement = db()->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $userId]);
    $role = $statement->fetchColumn();

    if (!is_string($role) || $role === '') {
        unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['last_activity']);

        return null;
    }

    $_SESSION['role'] = $role;

    return [
        'id' => $userId,
        'role' => $role,
    ];
}

function safe_intended_url(mixed $url): ?string
{
    if (
        !is_string($url)
        || $url === ''
        || str_contains($url, '\\')
        || preg_match('/[\x00-\x1F\x7F]/', $url) === 1
    ) {
        return null;
    }

    $parts = parse_url($url);

    if (
        $parts === false
        || isset($parts['scheme'])
        || isset($parts['host'])
        || isset($parts['user'])
        || isset($parts['pass'])
        || isset($parts['port'])
        || isset($parts['fragment'])
        || !isset($parts['path'])
        || !str_starts_with($parts['path'], '/')
        || str_starts_with($parts['path'], '//')
    ) {
        return null;
    }

    return $parts['path'] . (isset($parts['query']) ? '?' . $parts['query'] : '');
}

function require_login(): array
{
    $user = current_user();

    if ($user === null) {
        $intendedUrl = safe_intended_url($_SERVER['REQUEST_URI'] ?? null);

        if ($intendedUrl !== null) {
            $_SESSION['intended_url'] = $intendedUrl;
        }

        redirect(app_url('login.php'));
    }

    return $user;
}

function require_guest(): void
{
    if (current_user() !== null) {
        redirect('dashboard.php');
    }
}

function require_role(string $role = 'admin'): array
{
    $user = require_login();

    if ($user['role'] !== $role) {
        http_response_code(403);
        ?>
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Access denied</title>
            <link rel="stylesheet" href="<?= e(app_url('assets/auth.css')) ?>">
        </head>
        <body>
        <main class="auth-page">
            <section class="auth-card" aria-labelledby="page-title">
                <div class="auth-heading">
                    <p class="eyebrow">403 · ACCESS DENIED</p>
                    <h1 id="page-title">You can’t access this page</h1>
                    <p class="muted">This area is only available to administrators.</p>
                </div>
                <a class="button-link" href="<?= e(app_url('dashboard.php')) ?>">Return to dashboard</a>
            </section>
        </main>
        </body>
        </html>
        <?php
        exit;
    }

    return $user;
}

function login_throttle_minutes(string $email, string $ipAddress): ?int
{
    $pdo = db();
    $blockedMinutes = [];

    if ($email !== '') {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) AS attempt_count,
                    GREATEST(
                        0,
                        TIMESTAMPDIFF(
                            SECOND,
                            CURRENT_TIMESTAMP,
                            DATE_ADD(MIN(attempted_at), INTERVAL 15 MINUTE)
                        )
                    ) AS remaining_seconds
             FROM login_attempts
             WHERE email = :email
               AND attempted_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 15 MINUTE)'
        );
        $statement->execute(['email' => $email]);
        $attempts = $statement->fetch();

        if ((int) $attempts['attempt_count'] >= 5) {
            $blockedMinutes[] = max(1, (int) ceil((int) $attempts['remaining_seconds'] / 60));
        }
    }

    $statement = $pdo->prepare(
        'SELECT COUNT(*) AS attempt_count,
                GREATEST(
                    0,
                    TIMESTAMPDIFF(
                        SECOND,
                        CURRENT_TIMESTAMP,
                        DATE_ADD(MIN(attempted_at), INTERVAL 15 MINUTE)
                    )
                ) AS remaining_seconds
         FROM login_attempts
         WHERE ip = :ip
           AND attempted_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 15 MINUTE)'
    );
    $statement->execute(['ip' => $ipAddress]);
    $attempts = $statement->fetch();

    if ((int) $attempts['attempt_count'] >= 5) {
        $blockedMinutes[] = max(1, (int) ceil((int) $attempts['remaining_seconds'] / 60));
    }

    return $blockedMinutes === [] ? null : max($blockedMinutes);
}

function record_login_failure(string $email, string $ipAddress): void
{
    $statement = db()->prepare(
        'INSERT INTO login_attempts (email, ip) VALUES (:email, :ip)'
    );
    $statement->execute([
        'email' => $email,
        'ip' => $ipAddress,
    ]);
}

function clear_login_attempts(string $email, string $ipAddress): void
{
    $statement = db()->prepare(
        'DELETE FROM login_attempts WHERE email = :email OR ip = :ip'
    );
    $statement->execute([
        'email' => $email,
        'ip' => $ipAddress,
    ]);
}
