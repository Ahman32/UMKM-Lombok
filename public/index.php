<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once VIEW_PATH . '/layout.php';

$q = trim((string) query('q', ''));
$lokasi = trim((string) query('lokasi', ''));
$kategori = trim((string) query('kategori', ''));

$umkmList = [];
$errorMsg = null;

try {
    $response = api()->get('/umkm', [
        'query' => [
            'q' => $q,
            'lokasi' => $lokasi,
            'kategori' => $kategori,
        ],
    ]);
    $umkmList = $response['data']['items'] ?? [];
} catch (Throwable $e) {
    $errorMsg = api_error_message($e, 'Gagal memuat data UMKM.');
}

$currentUser = current_user();

ob_start();
?>
<section class="hero">
    <h1>Portal Informasi UMKM Lombok Timur</h1>
    <p>Temukan produk, jasa, dan potensi terbaik dari pelaku usaha mikro, kecil, dan menengah di Kabupaten Lombok Timur.</p>
    <div class="hero__actions">
        <?php if (!$currentUser): ?>
            <a href="/register.php" class="btn btn--primary">Daftarkan UMKM Anda</a>
            <a href="#daftar-umkm" class="btn btn--secondary">Jelajahi UMKM</a>
        <?php elseif (user_role($currentUser) === 'owner'): ?>
            <a href="/dashboard.php" class="btn btn--primary">Kelola UMKM Saya</a>
        <?php elseif (user_role($currentUser) === 'admin'): ?>
            <a href="/admin/index.php" class="btn btn--primary">Dashboard Admin</a>
        <?php endif; ?>
    </div>
</section>

<section id="alur" style="margin-bottom: 3.5rem;">
    <?= render_section_title('Panduan', 'Alur Pendaftaran UMKM', 'Langkah mudah bagi pelaku usaha untuk menampilkan UMKM di direktori publik.') ?>
    <div class="flow-steps">
        <?= render_flow_step('1', 'Register & Login', 'Buat akun pemilik UMKM, lalu masuk ke sistem dengan akun yang telah dibuat.') ?>
        <?= render_flow_step('2', 'Lengkapi Data & Foto', 'Isi data lengkap profil UMKM, alamat di Lombok Timur, kontak, serta unggah foto usaha.') ?>
        <?= render_flow_step('3', 'Verifikasi & Tampil', 'Admin akan memverifikasi data Anda. Setelah disetujui, UMKM otomatis tampil di beranda.') ?>
    </div>
</section>

<section id="daftar-umkm">
    <div class="section-title">
        <div>
            <div class="section-title__eyebrow">Direktori</div>
            <h2 class="section-title__heading">Daftar UMKM Lombok Timur</h2>
            <p class="section-title__subtitle">Menampilkan UMKM terverifikasi dengan nama, lokasi, dan profil usaha.</p>
        </div>
    </div>

    <!-- Filter & Search Form -->
    <form method="get" action="/index.php#daftar-umkm" style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 2rem; background: var(--color-surface); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama, deskripsi..." class="form-control" style="flex: 2; min-width: 200px;">
        <input type="text" name="lokasi" value="<?= e($lokasi) ?>" placeholder="Filter lokasi (misal: Selong, Sikur)..." class="form-control" style="flex: 1; min-width: 150px;">
        <button type="submit" class="btn btn--primary">Cari UMKM</button>
        <?php if ($q !== '' || $lokasi !== '' || $kategori !== ''): ?>
            <a href="/index.php#daftar-umkm" class="btn btn--ghost">Reset</a>
        <?php endif; ?>
    </form>

    <?php if ($errorMsg): ?>
        <div class="alert alert--error">
            <span class="alert__dot"></span>
            <div class="alert__body"><?= e($errorMsg) ?></div>
        </div>
    <?php endif; ?>

    <?php if (empty($umkmList)): ?>
        <?= render_empty_state('Belum ada UMKM ditemukan', 'Coba gunakan kata kunci pencarian atau lokasi lain di Lombok Timur.') ?>
    <?php else: ?>
        <div class="cards-grid">
            <?php foreach ($umkmList as $item): ?>
                <?= render_umkm_card($item) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
$content = ob_get_clean();
render_layout('Beranda', $content, ['active' => 'home', 'user' => $currentUser]);
