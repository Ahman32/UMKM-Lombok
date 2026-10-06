<?php

declare(strict_types=1);

function render_navigation(array $context = []): string
{
    $user = $context['user'] ?? null;
    $active = (string) ($context['active'] ?? '');
    $currentRole = is_array($user) ? (string) ($user['role'] ?? '') : '';
    $name = app_name();
    $links = [];

    if ($currentRole === 'admin') {
        $links = [
            ['label' => 'Beranda', 'url' => '/index.php', 'active' => 'home'],
            ['label' => 'Dashboard', 'url' => '/admin/index.php', 'active' => 'admin-dashboard'],
            ['label' => 'Data UMKM', 'url' => '/admin/umkm.php', 'active' => 'admin-umkm'],
            ['label' => 'Pengguna', 'url' => '/admin/users.php', 'active' => 'admin-users'],
            ['label' => 'Pengaturan', 'url' => '/setting.php', 'active' => 'setting'],
        ];
    } elseif ($currentRole === 'owner') {
        $links = [
            ['label' => 'Beranda', 'url' => '/index.php', 'active' => 'home'],
            ['label' => 'Dashboard', 'url' => '/dashboard.php', 'active' => 'dashboard'],
            ['label' => 'Pengaturan', 'url' => '/setting.php', 'active' => 'setting'],
        ];
    } else {
        $links = [
            ['label' => 'Beranda', 'url' => '/index.php', 'active' => 'home'],
            ['label' => 'Alur', 'url' => '/index.php#alur', 'active' => 'flow'],
            ['label' => 'Login', 'url' => '/login.php', 'active' => 'login'],
            ['label' => 'Register', 'url' => '/register.php', 'active' => 'register'],
        ];
    }

    ob_start();
    ?>
    <header class="site-header">
        <div class="site-header__inner">
            <a class="brand" href="/index.php">
                <span class="brand__mark">LT</span>
                <span class="brand__text">
                    <strong><?= e($name) ?></strong>
                    <small>Lombok Timur</small>
                </span>
            </a>

            <nav class="nav-links" aria-label="Navigasi utama">
                <?php foreach ($links as $link): ?>
                    <a class="nav-links__item <?= e(active_class((string) $link['active'], $active)) ?>" href="<?= e((string) $link['url']) ?>">
                        <?= e((string) $link['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="site-header__actions">
                <?php if ($currentRole !== ''): ?>
                    <div class="user-chip">
                        <span class="user-chip__avatar"><?= e(user_initial($user)) ?></span>
                        <div class="user-chip__meta">
                            <strong><?= e((string) ($user['name'] ?? 'Pengguna')) ?></strong>
                            <small><?= e(ucfirst($currentRole)) ?></small>
                        </div>
                    </div>
                    <form method="post" action="/logout.php" class="logout-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--ghost btn--sm">Logout</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn--ghost btn--sm" href="/login.php">Login</a>
                    <a class="btn btn--primary btn--sm" href="/register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <?php

    return trim((string) ob_get_clean());
}
