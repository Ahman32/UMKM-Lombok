<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('FRONTEND_PATH', ROOT_PATH . '/frontend');
define('VIEW_PATH', FRONTEND_PATH . '/view');
define('PUBLIC_PATH', ROOT_PATH . '/public');

require_once APP_PATH . '/ApiClient.php';
require_once APP_PATH . '/Auth.php';

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require CONFIG_PATH . '/app.php';
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

date_default_timezone_set((string) app_config('timezone', 'Asia/Makassar'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name((string) app_config('session_name', 'webumkm_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function app_name(): string
{
    return (string) app_config('name', 'UMKM Lombok Timur');
}

function url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');

    return $path === '/' ? '/' : $path;
}

function site_url(string $path = ''): string
{
    return url($path);
}

function asset_url(string $path = ''): string
{
    return url($path);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function request_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

function is_post(): bool
{
    return request_method() === 'POST';
}

function query(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

function post(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

function remember_input(array $input, array $exclude = ['password', 'current_password', 'new_password', 'confirm_password']): void
{
    $safe = [];

    foreach ($input as $key => $value) {
        if (in_array((string) $key, $exclude, true)) {
            continue;
        }

        if (is_scalar($value) || $value === null) {
            $safe[$key] = $value;
        }
    }

    $_SESSION['old_input'] = $safe;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old_input'][$key] ?? $default;
}

function clear_old_input(): void
{
    unset($_SESSION['old_input']);
}

function set_form_errors(array $errors): void
{
    $_SESSION['form_errors'] = $errors;
}

function pull_form_errors(): array
{
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['form_errors']);

    return is_array($errors) ? $errors : [];
}

function clear_form_errors(): void
{
    unset($_SESSION['form_errors']);
}

function form_error(array $errors, string $key): string
{
    return (string) ($errors[$key] ?? '');
}

function flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);

    return $messages;
}

function form_value(string $key, mixed $default = ''): mixed
{
    return old($key, $default);
}

function selected_attr(mixed $current, mixed $expected): string
{
    return (string) $current === (string) $expected ? 'selected' : '';
}

function checked_attr(bool $condition): string
{
    return $condition ? 'checked' : '';
}

function text_initial(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return 'U';
    }

    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        return mb_strtoupper(mb_substr($value, 0, 1));
    }

    return strtoupper(substr($value, 0, 1));
}

function text_excerpt(string $value, int $length = 140, string $suffix = '…'): string
{
    $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);

    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($value, 0, $length, $suffix);
    }

    if (strlen($value) <= $length) {
        return $value;
    }

    return rtrim(substr($value, 0, $length)) . $suffix;
}

function normalize_boolean(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value)) {
        return $value === 1;
    }

    $normalized = strtolower(trim((string) $value));

    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}

function current_auth(bool $refresh = true): ?array
{
    try {
        return Auth::current($refresh);
    } catch (Throwable $throwable) {
        return null;
    }
}

function current_user(bool $refresh = true): ?array
{
    $auth = current_auth($refresh);

    return $auth['user'] ?? null;
}

function current_umkm(bool $refresh = true): ?array
{
    $auth = current_auth($refresh);

    return $auth['umkm'] ?? null;
}

function user_role(?array $user): string
{
    return (string) ($user['role'] ?? '');
}

function user_name(?array $user): string
{
    return (string) ($user['name'] ?? 'Pengguna');
}

function user_initial(?array $user): string
{
    return text_initial(user_name($user));
}

function throwable_message(Throwable $throwable, string $fallback = 'Terjadi kesalahan.'): string
{
    $message = trim((string) $throwable->getMessage());

    return $message !== '' ? $message : $fallback;
}

function flash_exception(Throwable $throwable, string $fallback = 'Terjadi kesalahan.'): void
{
    flash('error', throwable_message($throwable, $fallback));
}

function back_link(string $fallback = '/index.php'): string
{
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');

    if ($referer !== '' && !str_contains($referer, '://') && !str_starts_with($referer, '//')) {
        return safe_redirect_target(parse_url($referer, PHP_URL_PATH) ?: '', $fallback);
    }

    return $fallback;
}

function query_string(array $params): string
{
    $filtered = [];

    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }

        $filtered[$key] = $value;
    }

    if (empty($filtered)) {
        return '';
    }

    return '?' . http_build_query($filtered);
}

function format_datetime(?string $value, string $fallback = '-'): string
{
    if (!$value) {
        return $fallback;
    }

    try {
        $date = new DateTimeImmutable($value);
    } catch (Throwable $throwable) {
        return $fallback;
    }

    return $date->format('d M Y, H:i');
}

function status_variant(string $status): string
{
    return match (strtolower(trim($status))) {
        'approved' => 'success',
        'rejected' => 'danger',
        'pending' => 'warning',
        default => 'neutral',
    };
}

function status_label(string $status): string
{
    return match (strtolower(trim($status))) {
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'pending' => 'Menunggu',
        default => ucfirst(trim($status)),
    };
}

function asset_path(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function build_form_action(string $path, array $query = []): string
{
    $url = url($path);

    if (!empty($query)) {
        $url .= query_string($query);
    }

    return $url;
}

function request_uri_path(): string
{
    return parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
}

function is_current_path(string $path): bool
{
    return rtrim(request_uri_path(), '/') === rtrim(url($path), '/');
}

function path_to(string $path): string
{
    return url($path);
}

function api_token(): string
{
    return Auth::token();
}

function form_errors_from(Throwable $throwable): array
{
    if ($throwable instanceof ApiException) {
        $payload = $throwable->getPayload();
        $details = $payload['details'] ?? null;

        if (is_array($details)) {
            if (!empty($details['field']) && is_string($details['field'])) {
                return [$details['field'] => $throwable->getMessage()];
            }

            if (!empty($details['errors']) && is_array($details['errors'])) {
                $errors = [];
                foreach ($details['errors'] as $field => $message) {
                    $errors[(string) $field] = is_string($message) ? $message : (string) $throwable->getMessage();
                }

                if (!empty($errors)) {
                    return $errors;
                }
            }
        }
    }

    return ['general' => throwable_message($throwable)];
}

function api_error_message(Throwable $throwable, string $fallback = 'Terjadi kesalahan.'): string
{
    if ($throwable instanceof ApiException) {
        $payload = $throwable->getPayload();
        $message = trim((string) ($payload['message'] ?? $throwable->getMessage()));

        if ($message !== '') {
            return $message;
        }
    }

    return throwable_message($throwable, $fallback);
}

function api_errors_to_flash(Throwable $throwable): void
{
    $errors = form_errors_from($throwable);

    if (isset($errors['general'])) {
        flash('error', (string) $errors['general']);
        unset($errors['general']);
    }

    if (!empty($errors)) {
        set_form_errors($errors);
    }
}

function form_has_errors(array $errors): bool
{
    return !empty($errors);
}

function flash_api_exception(Throwable $throwable, string $fallback = 'Terjadi kesalahan.'): void
{
    flash('error', api_error_message($throwable, $fallback));
}

function route_is(string $path): bool
{
    return is_current_path($path);
}

function current_role(): string
{
    return user_role(current_user());
}

function current_username(): string
{
    $user = current_user();

    return (string) ($user['username'] ?? '');
}

function is_authenticated(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return current_role() === 'admin';
}

function is_owner(): bool
{
    return current_role() === 'owner';
}

function goto_home_for_current_user(): void
{
    $role = current_role();

    if ($role === 'admin') {
        redirect('/admin/index.php');
    }

    if ($role === 'owner') {
        redirect('/dashboard.php');
    }

    redirect('/index.php');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    if (!is_post()) {
        return;
    }

    $incoming = (string) ($_POST['_csrf'] ?? '');
    if ($incoming === '' || !hash_equals(csrf_token(), $incoming)) {
        throw new RuntimeException('Token keamanan tidak valid. Silakan muat ulang halaman.');
    }
}

function api(): ApiClient
{
    static $client = null;

    if ($client === null) {
        $client = new ApiClient((string) app_config('api_url', 'http://127.0.0.1:3000'));
    }

    return $client;
}

function active_class(string $path, string $current, string $class = 'active'): string
{
    return rtrim($path, '/') === rtrim($current, '/') ? $class : '';
}

function safe_redirect_target(?string $target, string $fallback): string
{
    $target = trim((string) $target);

    if ($target === '') {
        return $fallback;
    }

    if (str_contains($target, '://') || str_starts_with($target, '//')) {
        return $fallback;
    }

    if (!str_starts_with($target, '/')) {
        $target = '/' . $target;
    }

    return $target;
}
