<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once VIEW_PATH . '/layout.php';

$auth = Auth::requireRole('owner');
$user = $auth['user'];
$token = Auth::token();

$umkm = null;
try {
    $res = api()->get('/umkm/me', ['token' => $token]);
    $umkm = $res['data']['umkm'] ?? null;
} catch (Throwable $e) {
    flash('error', 'Gagal memuat profil UMKM.');
}

$errors = pull_form_errors();

if (is_post()) {
    verify_csrf();
    remember_input($_POST);

    $payload = [
        'nama_umkm' => post('nama_umkm', ''),
        'kategori' => post('kategori', ''),
        'deskripsi' => post('deskripsi', ''),
        'lokasi' => post('lokasi', ''),
        'alamat_lengkap' => post('alamat_lengkap', ''),
        'telepon' => post('telepon', ''),
        'whatsapp' => post('whatsapp', ''),
        'email' => post('email', ''),
        'website' => post('website', ''),
        'jam_operasional' => post('jam_operasional', ''),
    ];

    $files = [];
    if (!empty($_FILES['foto_umkm']) && $_FILES['foto_umkm']['error'] === UPLOAD_ERR_OK) {
        $files['foto_umkm'] = $_FILES['foto_umkm'];
    }

    try {
        $apiRes = api()->post('/umkm/me', [
            'token' => $token,
            'body' => $payload,
            'files' => $files,
        ]);

        clear_old_input();
        clear_form_errors();
        flash('success', $apiRes['message'] ?? 'Data UMKM berhasil disimpan dan menunggu peninjauan Admin.');
        redirect('/dashboard.php');
    } catch (Throwable $e) {
        api_errors_to_flash($e);
        redirect('/dashboard.php');
    }
}

ob_start();
?>
<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 0.25rem;">Dashboard Pemilik UMKM</h1>
    <p style="color: var(--color-text-muted);">Kelola data usaha dan pantau status publikasi profil UMKM Anda di Lombok Timur.</p>
</div>

<?php if ($umkm): ?>
    <div class="stat-card" style="margin-bottom: 2rem; border-left: 4px solid var(--color-<?= status_variant($umkm['status']) ?>);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="stat-card__label">Status Verifikasi UMKM</span>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem;">
                    <?= render_badge(status_label($umkm['status']), status_variant($umkm['status'])) ?>
                    <strong style="font-size: 1.1rem;"><?= e($umkm['nama_umkm']) ?></strong>
                </div>
            </div>
            <?php if ($umkm['status'] === 'approved'): ?>
                <a href="/detail.php?id=<?= e($umkm['id']) ?>" class="btn btn--secondary btn--sm" target="_blank">Lihat di Web ↗</a>
            <?php endif; ?>
        </div>
        <?php if ($umkm['status'] === 'pending'): ?>
            <p style="margin-top: 0.75rem; font-size: 0.9rem; color: var(--color-text-muted);">Data UMKM Anda sedang ditinjau oleh Admin. Setelah disetujui, UMKM otomatis tampil di halaman utama.</p>
        <?php elseif ($umkm['status'] === 'rejected' && !empty($umkm['catatan_admin'])): ?>
            <p style="margin-top: 0.75rem; font-size: 0.9rem; color: var(--color-danger);"><strong>Catatan Admin:</strong> <?= e($umkm['catatan_admin']) ?></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="alert alert--warning" style="margin-bottom: 2rem;">
        <span class="alert__dot"></span>
        <div class="alert__body">
            <strong>UMKM Belum Didaftarkan!</strong> Silakan isi formulir di bawah ini untuk mendaftarkan UMKM Anda.
        </div>
    </div>
<?php endif; ?>

<div class="form-card" style="max-width: 800px; margin: 0;">
    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem;">
        <?= $umkm ? 'Perbarui Data UMKM & Foto' : 'Formulir Pendaftaran UMKM' ?>
    </h2>

    <?= render_form_errors($errors) ?>

    <form method="post" action="/dashboard.php" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="nama_umkm">Nama UMKM *</label>
                <input type="text" id="nama_umkm" name="nama_umkm" class="form-control" value="<?= e(old('nama_umkm', $umkm['nama_umkm'] ?? '')) ?>" required placeholder="Contoh: Kopi Asli Sembalun">
                <?= render_field_error($errors, 'nama_umkm') ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="kategori">Kategori *</label>
                    <select id="kategori" name="kategori" class="form-control" required>
                        <option value="">Pilih Kategori</option>
                        <?php
                        $kategoris = ['Kuliner', 'Kerajinan', 'Fashion', 'Pertanian', 'Minuman', 'Jasa', 'Lainnya'];
                        $curKat = old('kategori', $umkm['kategori'] ?? '');
                        foreach ($kategoris as $k): ?>
                            <option value="<?= e($k) ?>" <?= selected_attr($curKat, $k) ?>><?= e($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= render_field_error($errors, 'kategori') ?>
                </div>

                <div class="form-group">
                    <label for="lokasi">Kecamatan / Lokasi di Lombok Timur *</label>
                    <input type="text" id="lokasi" name="lokasi" class="form-control" value="<?= e(old('lokasi', $umkm['lokasi'] ?? '')) ?>" required placeholder="Contoh: Selong, Sikur, Aikmel">
                    <?= render_field_error($errors, 'lokasi') ?>
                </div>
            </div>

            <div class="form-group">
                <label for="alamat_lengkap">Alamat Lengkap *</label>
                <textarea id="alamat_lengkap" name="alamat_lengkap" class="form-control" required placeholder="Alamat jalan, dusun, desa/kelurahan, RT/RW"><?= e(old('alamat_lengkap', $umkm['alamat_lengkap'] ?? '')) ?></textarea>
                <?= render_field_error($errors, 'alamat_lengkap') ?>
            </div>

            <div class="form-group">
                <label for="deskripsi">Deskripsi Usaha & Produk *</label>
                <textarea id="deskripsi" name="deskripsi" class="form-control" style="min-height: 120px;" required placeholder="Jelaskan mengenai produk, keunggulan, atau sejarah usaha Anda"><?= e(old('deskripsi', $umkm['deskripsi'] ?? '')) ?></textarea>
                <?= render_field_error($errors, 'deskripsi') ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="telepon">Nomor Telepon</label>
                    <input type="text" id="telepon" name="telepon" class="form-control" value="<?= e(old('telepon', $umkm['telepon'] ?? '')) ?>" placeholder="08xxxxxxxxxx">
                </div>

                <div class="form-group">
                    <label for="whatsapp">Nomor WhatsApp</label>
                    <input type="text" id="whatsapp" name="whatsapp" class="form-control" value="<?= e(old('whatsapp', $umkm['whatsapp'] ?? '')) ?>" placeholder="08xxxxxxxxxx">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="email">Email Usaha</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= e(old('email', $umkm['email'] ?? '')) ?>" placeholder="kontak@umkm.com">
                </div>

                <div class="form-group">
                    <label for="website">Website / Media Sosial</label>
                    <input type="text" id="website" name="website" class="form-control" value="<?= e(old('website', $umkm['website'] ?? '')) ?>" placeholder="https://instagram.com/umkm">
                </div>
            </div>

            <div class="form-group">
                <label for="jam_operasional">Jam Operasional</label>
                <input type="text" id="jam_operasional" name="jam_operasional" class="form-control" value="<?= e(old('jam_operasional', $umkm['jam_operasional'] ?? '')) ?>" placeholder="Contoh: Senin - Sabtu, 08.00 - 17.00 WITA">
            </div>

            <div class="form-group">
                <label for="foto_umkm">Foto UMKM <?= $umkm && !empty($umkm['foto_url']) ? '(Biarkan kosong jika tidak diganti)' : '' ?></label>
                <?php if ($umkm && !empty($umkm['foto_url'])): ?>
                    <div style="margin-bottom: 0.5rem; width: 120px; height: 80px; border-radius: var(--radius-sm); overflow: hidden;">
                        <img src="<?= e($umkm['foto_url']) ?>" alt="Foto saat ini" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                <?php endif; ?>
                <input type="file" id="foto_umkm" name="foto_umkm" class="form-control" accept="image/*">
                <div class="form-help">Format: JPG, PNG, WEBP. Maksimal 5 MB.</div>
            </div>
        </div>

        <button type="submit" class="btn btn--primary" style="margin-top: 1.5rem;">
            <?= $umkm ? 'Simpan Perubahan UMKM' : 'Daftarkan UMKM Sekarang' ?>
        </button>
    </form>
</div>

<?php
$content = ob_get_clean();
render_layout('Dashboard Owner', $content, ['active' => 'dashboard', 'user' => $user]);
