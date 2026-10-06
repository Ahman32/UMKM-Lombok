<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once VIEW_PATH . '/layout.php';

$auth = Auth::requireRole('admin');
$user = $auth['user'];
$token = Auth::token();

$items = [];
try {
    $res = api()->get('/admin/users', ['token' => $token]);
    $items = $res['data']['items'] ?? [];
} catch (Throwable $e) {
    flash('error', 'Gagal memuat daftar pengguna.');
}

ob_start();
?>
<div class="section-title">
    <div>
        <div class="section-title__eyebrow">Panel Admin</div>
        <h1 class="section-title__heading">Daftar Pengguna Sistem</h1>
        <p class="section-title__subtitle">Daftar akun admin dan pemilik UMKM yang terdaftar di aplikasi.</p>
    </div>
</div>

<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Nama Pengguna</th>
                <th>Username</th>
                <th>Role / Peran</th>
                <th>Jumlah UMKM Terdaftar</th>
                <th>Tanggal Bergabung</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $u): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="user-chip__avatar" style="width: 1.75rem; height: 1.75rem; font-size: 0.75rem;"><?= e(user_initial($u)) ?></span>
                            <strong><?= e($u['name']) ?></strong>
                        </div>
                    </td>
                    <td><?= e($u['username']) ?></td>
                    <td>
                        <?= render_badge(ucfirst($u['role']), $u['role'] === 'admin' ? 'primary' : 'neutral') ?>
                    </td>
                    <td>
                        <?php if ($u['role'] === 'owner'): ?>
                            <strong><?= e((string) $u['umkm_count']) ?></strong> UMKM
                        <?php else: ?>
                            <span style="color: var(--color-text-muted);">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="color: var(--color-text-muted); font-size: 0.9rem;">
                        <?= e(format_datetime($u['created_at'])) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
render_layout('Daftar Pengguna Admin', $content, ['active' => 'admin-users', 'user' => $user]);
