<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once VIEW_PATH . '/layout.php';

$auth = Auth::requireRole('admin');
$user = $auth['user'];
$token = Auth::token();

$status = trim((string) query('status', ''));
$q = trim((string) query('q', ''));

if (is_post()) {
    verify_csrf();
    $umkmId = (int) post('umkm_id', 0);
    $actionStatus = (string) post('status', '');
    $catatan = trim((string) post('catatan_admin', ''));

    if ($umkmId > 0 && in_array($actionStatus, ['approved', 'rejected', 'pending'], true)) {
        try {
            api()->patch('/admin/umkm/' . $umkmId . '/status', [
                'token' => $token,
                'body' => [
                    'status' => $actionStatus,
                    'catatan_admin' => $catatan,
                ],
            ]);
            flash('success', 'Status UMKM berhasil diperbarui.');
        } catch (Throwable $e) {
            flash_api_exception($e, 'Gagal memperbarui status UMKM.');
        }
    }
    redirect('/admin/umkm.php' . query_string(['status' => $status, 'q' => $q]));
}

$items = [];
try {
    $res = api()->get('/admin/umkm', [
        'token' => $token,
        'query' => [
            'status' => $status,
            'q' => $q,
        ],
    ]);
    $items = $res['data']['items'] ?? [];
} catch (Throwable $e) {
    flash('error', 'Gagal memuat data UMKM.');
}

ob_start();
?>
<div class="section-title">
    <div>
        <div class="section-title__eyebrow">Panel Admin</div>
        <h1 class="section-title__heading">Kelola & Verifikasi UMKM</h1>
        <p class="section-title__subtitle">Daftar seluruh UMKM yang masuk ke sistem untuk ditinjau dan dipublikasikan.</p>
    </div>
</div>

<!-- Filter Box -->
<form method="get" action="/admin/umkm.php" style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.5rem; background: var(--color-surface); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama UMKM, pemilik..." class="form-control" style="flex: 2; min-width: 200px;">
    <select name="status" class="form-control" style="flex: 1; min-width: 150px;">
        <option value="">Semua Status</option>
        <option value="pending" <?= selected_attr($status, 'pending') ?>>Menunggu Verifikasi</option>
        <option value="approved" <?= selected_attr($status, 'approved') ?>>Disetujui (Publik)</option>
        <option value="rejected" <?= selected_attr($status, 'rejected') ?>>Ditolak</option>
    </select>
    <button type="submit" class="btn btn--primary">Terapkan Filter</button>
    <?php if ($status !== '' || $q !== ''): ?>
        <a href="/admin/umkm.php" class="btn btn--ghost">Reset</a>
    <?php endif; ?>
</form>

<?php if (empty($items)): ?>
    <?= render_empty_state('Tidak ada data UMKM', 'Tidak ditemukan data UMKM yang sesuai dengan kriteria filter saat ini.') ?>
<?php else: ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>UMKM & Foto</th>
                    <th>Pemilik & Kontak</th>
                    <th>Lokasi Lombok Timur</th>
                    <th>Status</th>
                    <th>Aksi Verifikasi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="max-width: 250px;">
                            <div style="display: flex; gap: 0.75rem; align-items: center;">
                                <?php if (!empty($item['foto_url'])): ?>
                                    <img src="<?= e($item['foto_url']) ?>" alt="" style="width: 50px; height: 50px; border-radius: var(--radius-sm); object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; border-radius: var(--radius-sm); background: var(--color-surface-subtle); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--color-text-muted);">
                                        <?= e(text_initial($item['nama_umkm'])) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <strong style="display: block; font-size: 0.95rem;"><?= e($item['nama_umkm']) ?></strong>
                                    <span class="badge badge--neutral" style="margin-top: 0.2rem;"><?= e($item['kategori']) ?></span>
                                </div>
                            </div>
                            <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--color-text-muted);">
                                <?= e(text_excerpt($item['deskripsi'], 80)) ?>
                            </div>
                        </td>
                        <td>
                            <strong><?= e($item['owner_name']) ?></strong>
                            <div style="font-size: 0.85rem; color: var(--color-text-muted);">@<?= e($item['owner_username']) ?></div>
                            <div style="font-size: 0.85rem; margin-top: 0.25rem;">
                                <?= $item['telepon'] ? '📞 ' . e($item['telepon']) : '' ?>
                                <?= $item['whatsapp'] ? '<br>💬 ' . e($item['whatsapp']) : '' ?>
                            </div>
                        </td>
                        <td>
                            <strong>📍 <?= e($item['lokasi']) ?></strong>
                            <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-top: 0.25rem;"><?= e($item['alamat_lengkap']) ?></div>
                        </td>
                        <td>
                            <?= render_badge(status_label($item['status']), status_variant($item['status'])) ?>
                            <?php if (!empty($item['catatan_admin'])): ?>
                                <div style="font-size: 0.75rem; color: var(--color-danger); margin-top: 0.25rem;">
                                    Catatan: <?= e($item['catatan_admin']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="min-width: 200px;">
                            <form method="post" action="/admin/umkm.php<?= query_string(['status' => $status, 'q' => $q]) ?>" style="display: flex; flex-direction: column; gap: 0.35rem;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="umkm_id" value="<?= e((string) $item['id']) ?>">

                                <div style="display: flex; gap: 0.25rem;">
                                    <?php if ($item['status'] !== 'approved'): ?>
                                        <button type="submit" name="status" value="approved" class="btn btn--success btn--sm" style="flex: 1;">Setujui</button>
                                    <?php endif; ?>
                                    <?php if ($item['status'] !== 'rejected'): ?>
                                        <button type="submit" name="status" value="rejected" class="btn btn--danger btn--sm" style="flex: 1;">Tolak</button>
                                    <?php endif; ?>
                                    <?php if ($item['status'] !== 'pending'): ?>
                                        <button type="submit" name="status" value="pending" class="btn btn--secondary btn--sm" style="flex: 1;">Tinjau Ulang</button>
                                    <?php endif; ?>
                                </div>

                                <input type="text" name="catatan_admin" value="<?= e($item['catatan_admin'] ?? '') ?>" placeholder="Catatan (opsional)" class="form-control" style="font-size: 0.8rem; padding: 0.3rem 0.5rem;">
                            </form>

                            <?php if ($item['status'] === 'approved'): ?>
                                <div style="margin-top: 0.5rem; text-align: right;">
                                    <a href="/detail.php?id=<?= e((string) $item['id']) ?>" target="_blank" style="font-size: 0.8rem;">Lihat Tampilan Publik ↗</a>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
render_layout('Kelola UMKM Admin', $content, ['active' => 'admin-umkm', 'user' => $user]);
