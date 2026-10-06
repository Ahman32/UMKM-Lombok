<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once VIEW_PATH . '/layout.php';

$auth = Auth::requireRole('admin');
$user = $auth['user'];
$token = Auth::token();

$stats = [
    'total_users' => 0,
    'total_owners' => 0,
    'total_umkm' => 0,
    'pending_umkm' => 0,
    'approved_umkm' => 0,
    'rejected_umkm' => 0,
];

try {
    $res = api()->get('/admin/stats', ['token' => $token]);
    $stats = $res['data'] ?? $stats;
} catch (Throwable $e) {
    flash('error', 'Gagal memuat data statistik admin.');
}

ob_start();
?>
<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 0.25rem;">Panel Dashboard Administrator</h1>
    <p style="color: var(--color-text-muted);">Kelola data UMKM Lombok Timur, moderasi verifikasi usaha, dan lihat daftar pengguna.</p>
</div>

<div class="stats-grid">
    <?= render_stat_card('Total UMKM Terdaftar', $stats['total_umkm']) ?>
    <?= render_stat_card('Menunggu Verifikasi', $stats['pending_umkm'], 'Perlu ditinjau', 'warning') ?>
    <?= render_stat_card('UMKM Disetujui', $stats['approved_umkm'], 'Tampil di publik', 'success') ?>
    <?= render_stat_card('Total Pemilik UMKM', $stats['total_owners'], 'Akun terdaftar') ?>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <div class="stat-card" style="display: flex; flex-direction: column; gap: 1rem;">
        <div>
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.25rem;">Manajemen & Verifikasi UMKM</h3>
            <p style="color: var(--color-text-muted); font-size: 0.9rem;">Tinjau pengajuan baru, setujui/tolak status UMKM, dan periksa data lokasi usaha.</p>
        </div>
        <div style="margin-top: auto;">
            <a href="/admin/umkm.php" class="btn btn--primary">Buka Data UMKM →</a>
        </div>
    </div>

    <div class="stat-card" style="display: flex; flex-direction: column; gap: 1rem;">
        <div>
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.25rem;">Daftar Pengguna Terdaftar</h3>
            <p style="color: var(--color-text-muted); font-size: 0.9rem;">Lihat semua akun admin dan pemilik UMKM yang terdaftar di dalam sistem.</p>
        </div>
        <div style="margin-top: auto;">
            <a href="/admin/users.php" class="btn btn--secondary">Buka Daftar Pengguna →</a>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
render_layout('Dashboard Admin', $content, ['active' => 'admin-dashboard', 'user' => $user]);
