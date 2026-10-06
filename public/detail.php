<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once VIEW_PATH . '/layout.php';

$id = (int) query('id', 0);
$item = null;
$errorMsg = null;

if ($id > 0) {
    try {
        $response = api()->get('/umkm/' . $id);
        $item = $response['data']['item'] ?? null;
    } catch (Throwable $e) {
        $errorMsg = api_error_message($e, 'Gagal memuat informasi UMKM.');
    }
}

if (!$item && !$errorMsg) {
    $errorMsg = 'UMKM tidak ditemukan atau belum disetujui.';
}

$currentUser = current_user();

ob_start();
?>
<div style="margin-bottom: 1.5rem;">
    <a href="/index.php#daftar-umkm" class="btn btn--ghost btn--sm">← Kembali ke Daftar UMKM</a>
</div>

<?php if ($errorMsg): ?>
    <div class="alert alert--error">
        <span class="alert__dot"></span>
        <div class="alert__body"><?= e($errorMsg) ?></div>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 2rem; max-width: 900px; margin: 0 auto;">

        <?php if (!empty($item['foto_url'])): ?>
            <div style="width: 100%; height: 350px; border-radius: var(--radius-md); overflow: hidden;">
                <img src="<?= e($item['foto_url']) ?>" alt="Foto <?= e($item['nama_umkm']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        <?php endif; ?>

        <div>
            <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
                <span class="badge badge--neutral"><?= e($item['kategori']) ?></span>
                <span class="badge badge--success">Terverifikasi</span>
            </div>

            <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;"><?= e($item['nama_umkm']) ?></h1>
            <p style="font-size: 1.1rem; color: var(--color-text-muted); margin-bottom: 1.5rem;">📍 <?= e($item['lokasi']) ?></p>

            <div style="margin-bottom: 2rem;">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;">Tentang UMKM</h3>
                <p style="line-height: 1.7; color: var(--color-text-main);"><?= nl2br(e($item['deskripsi'])) ?></p>
            </div>

            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.75rem;">Informasi & Kontak Usaha</h3>
            <?= render_info_list([
                ['label' => 'Alamat Lengkap', 'value' => $item['alamat_lengkap']],
                ['label' => 'Jam Operasional', 'value' => $item['jam_operasional'] ?: '-'],
                ['label' => 'Nomor Telepon', 'value' => $item['telepon'] ?: '-'],
                ['label' => 'WhatsApp', 'value' => $item['whatsapp'] ?: '-'],
                ['label' => 'Email', 'value' => $item['email'] ?: '-'],
                ['label' => 'Website', 'value' => $item['website'] ?: '-'],
                ['label' => 'Pemilik', 'value' => $item['owner_name'] ?: '-'],
            ]) ?>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
render_layout($item ? $item['nama_umkm'] : 'Detail UMKM', $content, ['user' => $currentUser]);
