<?php
declare(strict_types=1);

function current_user(): ?array
{
    $userId = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role'] ?? null;

    if (!is_int($userId) || !is_string($role) || $role === '') {
        return null;
    }

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

        redirect('login.php');
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
        exit('Forbidden');
    }

    return $user;
}
