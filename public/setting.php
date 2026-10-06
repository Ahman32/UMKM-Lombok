<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once VIEW_PATH . '/layout.php';

$auth = Auth::requireLogin();
$user = $auth['user'];
$errors = pull_form_errors();

if (is_post()) {
    verify_csrf();
    $name = trim((string) post('name', ''));
    $currentPassword = (string) post('current_password', '');
    $newPassword = (string) post('new_password', '');
    $newPasswordConfirm = (string) post('new_password_confirm', '');

    remember_input($_POST);

    $payload = [];
    if ($name !== '' && $name !== ($user['name'] ?? '')) {
        $payload['name'] = $name;
    }

    if ($newPassword !== '') {
        if ($newPassword !== $newPasswordConfirm) {
            flash('error', 'Konfirmasi password baru tidak cocok.');
            redirect('/setting.php');
        }
        $payload['current_password'] = $currentPassword;
        $payload['new_password'] = $newPassword;
    }

    if (empty($payload)) {
        flash('info', 'Tidak ada perubahan data yang disimpan.');
        redirect('/setting.php');
    }

    try {
        Auth::updateProfile($payload);
        clear_old_input();
        clear_form_errors();
        flash('success', 'Pengaturan akun Anda berhasil diperbarui.');
        redirect('/setting.php');
    } catch (Throwable $e) {
        api_errors_to_flash($e);
        redirect('/setting.php');
    }
}

ob_start();
?>
<div class="form-card">
    <div style="margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.25rem;">Pengaturan Akun</h1>
        <p style="color: var(--color-text-muted); font-size: 0.9rem;">Ubah nama tampilan atau ganti password login Anda.</p>
    </div>

    <?= render_form_errors($errors) ?>

    <form method="post" action="/setting.php">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" class="form-control" value="<?= e($user['username']) ?>" disabled style="background: var(--color-surface-subtle); cursor: not-allowed;">
            <div class="form-help">Username tidak dapat diubah.</div>
        </div>

        <div class="form-group">
            <label for="name">Nama Lengkap</label>
            <input type="text" id="name" name="name" class="form-control" value="<?= e(old('name', $user['name'])) ?>" required>
            <?= render_field_error($errors, 'name') ?>
        </div>

        <hr style="border: none; border-top: 1px solid var(--color-border); margin: 1.75rem 0;">

        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">Ganti Password (Opsional)</h3>

        <div class="form-group">
            <label for="current_password">Password Saat Ini</label>
            <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Isi hanya jika ingin mengganti password">
            <?= render_field_error($errors, 'current_password') ?>
        </div>

        <div class="form-group">
            <label for="new_password">Password Baru</label>
            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimal 8 karakter">
            <?= render_field_error($errors, 'new_password') ?>
        </div>

        <div class="form-group">
            <label for="new_password_confirm">Konfirmasi Password Baru</label>
            <input type="password" id="new_password_confirm" name="new_password_confirm" class="form-control" placeholder="Ulangi password baru">
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn--primary">Simpan Perubahan</button>
            <a href="<?= e(Auth::homePath($user['role'])) ?>" class="btn btn--secondary">Batal</a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
render_layout('Pengaturan Akun', $content, ['active' => 'setting', 'user' => $user]);
