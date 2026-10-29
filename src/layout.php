<?php
declare(strict_types=1);

function render_navbar(): void
{
    $user = current_user();

    if ($user === null) {
        return;
    }
    ?>
    <header class="site-header">
        <nav class="site-nav" aria-label="Main navigation">
            <a class="brand" href="<?= e(app_url('dashboard.php')) ?>">SWD AUTH</a>
            <div class="nav-actions">
                <a class="nav-link" href="<?= e(app_url('profile.php')) ?>">Profile</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a class="nav-link" href="<?= e(app_url('admin/users.php')) ?>">Admin</a>
                <?php endif; ?>
                <form method="post" action="<?= e(app_url('logout.php')) ?>">
                    <?= csrf_field() ?>
                    <button class="nav-button" type="submit">Sign out</button>
                </form>
            </div>
        </nav>
    </header>
    <?php
}
