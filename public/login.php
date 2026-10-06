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
    $username = trim((string) post('username', ''));
    $password = (string) post('password', '');

    remember_input($_POST);

    if ($username === '' || $password === '') {
        flash('error', 'Username dan password wajib diisi.');
        redirect('/login.php');
    }

    try {
        Auth::login($username, $password);
        clear_old_input();
        clear_form_errors();
        flash('success', 'Selamat datang kembali!');
        goto_home_for_current_user();
    } catch (Throwable $e) {
        api_errors_to_flash($e);
        redirect('/login.php');
    }
}

ob_start();
?>
<div class="form-card">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.75rem; font-weight: 800;">Masuk ke Akun</h1>
        <p style="color: var(--color-text-muted); font-size: 0.95rem;">Akses dashboard UMKM Lombok Timur atau panel Admin</p>
    </div>

    <?= render_form_errors($errors) ?>

    <form method="post" action="/login.php">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" value="<?= e(old('username')) ?>" required autofocus placeholder="Masukkan username">
            <?= render_field_error($errors, 'username') ?>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required placeholder="Masukkan password">
            <?= render_field_error($errors, 'password') ?>
        </div>

        <button type="submit" class="btn btn--primary btn--block" style="margin-top: 1rem;">Masuk Sekarang</button>
    </form>

    <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--color-text-muted);">
        Belum memiliki akun pemilik UMKM? <a href="/register.php">Daftar Akun Baru</a>
    </div>
</div>
<?php
$content = ob_get_clean();
render_layout('Login', $content, ['active' => 'login']);
