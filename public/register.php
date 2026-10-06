<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once VIEW_PATH . '/layout.php';

if (is_authenticated()) {
    goto_home_for_current_user();
}

$errors = pull_form_errors();

if (is_post()) {
    verify_csrf();
    $name = trim((string) post('name', ''));
    $username = trim((string) post('username', ''));
    $password = (string) post('password', '');
    $passwordConfirm = (string) post('password_confirm', '');

    remember_input($_POST);

    if ($password !== $passwordConfirm) {
        flash('error', 'Konfirmasi password tidak cocok.');
        redirect('/register.php');
    }

    try {
        Auth::register($name, $username, $password);
        clear_old_input();
        clear_form_errors();
        flash('success', 'Pendaftaran akun berhasil! Silakan login untuk mendaftarkan UMKM Anda.');
        redirect('/login.php');
    } catch (Throwable $e) {
        api_errors_to_flash($e);
        redirect('/register.php');
    }
}

ob_start();
?>
<div class="form-card">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.75rem; font-weight: 800;">Daftar Akun Pemilik UMKM</h1>
        <p style="color: var(--color-text-muted); font-size: 0.95rem;">Daftarkan diri Anda untuk mulai mempromosikan UMKM di Lombok Timur</p>
    </div>

    <?= render_form_errors($errors) ?>

    <form method="post" action="/register.php">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="name">Nama Lengkap</label>
            <input type="text" id="name" name="name" class="form-control" value="<?= e(old('name')) ?>" required autofocus placeholder="Contoh: Ahmad Fauzi">
            <?= render_field_error($errors, 'name') ?>
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" value="<?= e(old('username')) ?>" required placeholder="Contoh: fauziumkm">
            <div class="form-help">Gunakan huruf kecil tanpa spasi.</div>
            <?= render_field_error($errors, 'username') ?>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required placeholder="Minimal 8 karakter">
            <?= render_field_error($errors, 'password') ?>
        </div>

        <div class="form-group">
            <label for="password_confirm">Konfirmasi Password</label>
            <input type="password" id="password_confirm" name="password_confirm" class="form-control" required placeholder="Ulangi password">
        </div>

        <button type="submit" class="btn btn--primary btn--block" style="margin-top: 1rem;">Daftar Sekarang</button>
    </form>

    <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--color-text-muted);">
        Sudah memiliki akun? <a href="/login.php">Masuk ke Akun</a>
    </div>
</div>
<?php
$content = ob_get_clean();
render_layout('Register', $content, ['active' => 'register']);
