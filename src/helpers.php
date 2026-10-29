<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

function flash(string $key, mixed $value = null): mixed
{
    if (func_num_args() > 1) {
        $_SESSION['_flash'][$key] = $value;

        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $message;
}

function old(string $key, string $default = ''): string
{
    if (isset($_SESSION['_old'][$key]) && is_string($_SESSION['_old'][$key])) {
        $value = $_SESSION['_old'][$key];
        unset($_SESSION['_old'][$key]);

        return $value;
    }

    $value = $_POST[$key] ?? null;

    return is_string($value) ? $value : $default;
}
