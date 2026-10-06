<?php

declare(strict_types=1);

function render_alerts(array $flashes = []): string
{
    if (empty($flashes)) {
        return '';
    }

    ob_start();
    ?>
    <div class="alerts">
        <?php foreach ($flashes as $flash): ?>
            <?php
            $type = (string) ($flash['type'] ?? 'info');
            $message = (string) ($flash['message'] ?? '');
            ?>
            <div class="alert alert--<?= e($type) ?>" data-auto-dismiss="1">
                <span class="alert__dot"></span>
                <div class="alert__body"><?= e($message) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php

    return trim((string) ob_get_clean());
}

function render_badge(string $label, string $variant = 'neutral'): string
{
    ob_start();
    ?>
    <span class="badge badge--<?= e($variant) ?>"><?= e($label) ?></span>
    <?php

    return trim((string) ob_get_clean());
}

function render_stat_card(string $label, string|int $value, string $hint = '', string $variant = 'primary'): string
{
    ob_start();
    ?>
    <article class="stat-card stat-card--<?= e($variant) ?>">
        <div class="stat-card__label"><?= e($label) ?></div>
        <div class="stat-card__value"><?= e((string) $value) ?></div>
        <?php if ($hint !== ''): ?>
            <div class="stat-card__hint"><?= e($hint) ?></div>
        <?php endif; ?>
    </article>
    <?php

    return trim((string) ob_get_clean());
}

function render_section_title(string $eyebrow, string $title, string $subtitle = '', array $actions = []): string
{
    ob_start();
    ?>
    <div class="section-title">
        <div>
            <div class="section-title__eyebrow"><?= e($eyebrow) ?></div>
            <h2 class="section-title__heading"><?= e($title) ?></h2>
            <?php if ($subtitle !== ''): ?>
                <p class="section-title__subtitle"><?= e($subtitle) ?></p>
            <?php endif; ?>
        </div>
        <?php if (!empty($actions)): ?>
            <div class="section-title__actions">
                <?php foreach ($actions as $action): ?>
                    <?php if (!empty($action['url'])): ?>
                        <a class="btn <?= e($action['class'] ?? 'btn--secondary') ?>" href="<?= e((string) $action['url']) ?>">
                            <?= e((string) ($action['label'] ?? 'Aksi')) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php

    return trim((string) ob_get_clean());
}

function render_empty_state(string $title, string $description, string $actionLabel = '', string $actionUrl = ''): string
{
    ob_start();
    ?>
    <div class="empty-state">
        <div class="empty-state__icon">◎</div>
        <h3><?= e($title) ?></h3>
        <p><?= e($description) ?></p>
        <?php if ($actionLabel !== '' && $actionUrl !== ''): ?>
            <a class="btn btn--primary" href="<?= e($actionUrl) ?>"><?= e($actionLabel) ?></a>
        <?php endif; ?>
    </div>
    <?php

    return trim((string) ob_get_clean());
}

function render_umkm_card(array $umkm, array $options = []): string
{
    $link = (string) ($options['link'] ?? '/detail.php?id=' . ($umkm['id'] ?? ''));
    $showStatus = (bool) ($options['show_status'] ?? false);
    $showOwner = (bool) ($options['show_owner'] ?? false);
    $compact = (bool) ($options['compact'] ?? false);
    $title = (string) ($umkm['nama_umkm'] ?? 'Nama UMKM');
    $location = (string) ($umkm['lokasi'] ?? 'Lombok Timur');
    $category = (string) ($umkm['kategori'] ?? 'UMKM');
    $description = trim((string) ($umkm['deskripsi'] ?? ''));
    $image = (string) ($umkm['foto_url'] ?? '');
    $status = (string) ($umkm['status'] ?? 'approved');

    ob_start();
    ?>
    <article class="umkm-card <?= $compact ? 'umkm-card--compact' : '' ?>">
        <a class="umkm-card__media" href="<?= e($link) ?>">
            <?php if ($image !== ''): ?>
                <img src="<?= e($image) ?>" alt="Foto <?= e($title) ?>">
            <?php else: ?>
                <div class="umkm-card__placeholder">
                    <span><?= e(text_initial($title)) ?></span>
                </div>
            <?php endif; ?>
        </a>
        <div class="umkm-card__body">
            <div class="umkm-card__topline">
                <div class="umkm-card__category"><?= e($category) ?></div>
                <?php if ($showStatus): ?>
                    <?= render_badge(ucfirst($status), $status === 'approved' ? 'success' : ($status === 'rejected' ? 'danger' : 'warning')) ?>
                <?php endif; ?>
                <?php if ($compact && $showStatus): ?>
                    <div class="umkm-card__statusline"><?= e(ucfirst($status)) ?></div>
                <?php endif; ?>
            </div>
            <h3 class="umkm-card__title">
                <a href="<?= e($link) ?>"><?= e($title) ?></a>
            </h3>
            <div class="umkm-card__location">📍 <?= e($location) ?></div>
            <?php if ($showOwner && !empty($umkm['owner_name'])): ?>
                <div class="umkm-card__meta">Pemilik: <?= e((string) $umkm['owner_name']) ?></div>
            <?php endif; ?>
            <?php if (!$compact && $description !== ''): ?>
                <p class="umkm-card__description"><?= e(text_excerpt($description, 140)) ?></p>
            <?php endif; ?>
            <div class="umkm-card__actions">
                <a class="btn btn--secondary btn--sm" href="<?= e($link) ?>">Lihat detail</a>
            </div>
        </div>
    </article>
    <?php

    return trim((string) ob_get_clean());
}

function render_flow_step(string $number, string $title, string $description): string
{
    ob_start();
    ?>
    <article class="flow-step">
        <div class="flow-step__number"><?= e($number) ?></div>
        <h3><?= e($title) ?></h3>
        <p><?= e($description) ?></p>
    </article>
    <?php

    return trim((string) ob_get_clean());
}

function render_info_list(array $items): string
{
    ob_start();
    ?>
    <ul class="info-list">
        <?php foreach ($items as $item): ?>
            <li class="info-list__item">
                <span class="info-list__label"><?= e((string) ($item['label'] ?? '')) ?></span>
                <span class="info-list__value"><?= e((string) ($item['value'] ?? '')) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php

    return trim((string) ob_get_clean());
}

function render_form_errors(array $errors): string
{
    if (empty($errors)) {
        return '';
    }

    ob_start();
    ?>
    <div class="form-errors" role="alert">
        <div class="form-errors__title">Periksa kembali isian berikut:</div>
        <ul class="form-errors__list">
            <?php foreach ($errors as $field => $message): ?>
                <li><strong><?= e((string) $field) ?>:</strong> <?= e((string) $message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php

    return trim((string) ob_get_clean());
}

function render_field_error(array $errors, string $key): string
{
    $message = trim((string) form_error($errors, $key));

    if ($message === '') {
        return '';
    }

    return '<div class="field-error">' . e($message) . '</div>';
}
