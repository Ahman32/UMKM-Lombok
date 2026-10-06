<?php

declare(strict_types=1);

function render_footer(array $context = []): string
{
    $year = (int) date('Y');
    $supportRegion = (string) app_config('support_region', 'Lombok Timur');

    ob_start();
    ?>
    <footer class="site-footer">
        <div class="site-footer__inner">
            <div>
                <strong><?= e(app_name()) ?></strong>
                <p>Portal informasi UMKM <?= e($supportRegion) ?> yang menampilkan lokasi, profil, dan foto usaha.</p>
            </div>
            <div class="site-footer__meta">
                <span>&copy; <?= e((string) $year) ?></span>
                <span>PHP frontend · Node.js API · SQLite</span>
            </div>
        </div>
    </footer>
    <?php

    return trim((string) ob_get_clean());
}
