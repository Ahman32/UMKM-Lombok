<?php

declare(strict_types=1);

require_once __DIR__ . '/partials/components.php';
require_once __DIR__ . '/partials/navigation.php';
require_once __DIR__ . '/partials/footer.php';

function render_layout(string $title, string $content, array $context = []): void
{
    $context = array_merge([
        'active' => '',
        'user' => null,
        'page_class' => '',
        'body_class' => '',
        'flashes' => pull_flashes(),
    ], $context);

    $pageTitle = trim($title) !== '' ? $title . ' · ' . app_name() : app_name();
    $bodyClass = trim((string) ($context['body_class'] ?? ''));
    $pageClass = trim((string) ($context['page_class'] ?? ''));

    ob_start();
    ?>
    <!doctype html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="<?= e((string) app_config('description', 'Portal UMKM Lombok Timur')) ?>">
        <title><?= e($pageTitle) ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= e(asset_url('/assets/css/app.css')) ?>">
    </head>
    <body class="<?= e($bodyClass) ?>">
        <?= render_navigation($context) ?>
        <main class="site-main <?= e($pageClass) ?>">
            <div class="site-main__inner">
                <?= render_alerts($context['flashes'] ?? []) ?>
                <?= $content ?>
            </div>
        </main>
        <?= render_footer($context) ?>
        <script src="<?= e(asset_url('/assets/js/app.js')) ?>" defer></script>
    </body>
    </html>
    <?php

    echo ob_get_clean();
}
