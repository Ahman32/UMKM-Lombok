<?php

declare(strict_types=1);

class Auth
{
    private const SESSION_KEY = 'auth';

    public static function token(): string
    {
        return (string) (self::session()['token'] ?? '');
    }

    public static function session(): array
    {
        $session = $_SESSION[self::SESSION_KEY] ?? [];

        return is_array($session) ? $session : [];
    }

    public static function current(bool $refresh = true): ?array
    {
        $session = self::session();
        $token = (string) ($session['token'] ?? '');

        if ($token === '') {
            return null;
        }

        if ($refresh) {
            try {
                $response = api()->get('/auth/me', ['token' => $token]);
                $data = $response['data'] ?? [];
                self::store([
                    'token' => $token,
                    'session' => $session['session'] ?? null,
                    'user' => $data['user'] ?? null,
                    'umkm' => $data['umkm'] ?? null,
                ]);
            } catch (ApiException $exception) {
                if ($exception->getStatusCode() === 401) {
                    self::forget(false);
                    return null;
                }

                throw $exception;
            }
        }

        $session = self::session();
        if (empty($session['user']) || !is_array($session['user'])) {
            return null;
        }

        return $session;
    }

    public static function user(bool $refresh = true): ?array
    {
        $auth = self::current($refresh);

        return $auth['user'] ?? null;
    }

    public static function umkm(bool $refresh = true): ?array
    {
        $auth = self::current($refresh);

        return $auth['umkm'] ?? null;
    }

    public static function isLoggedIn(bool $refresh = true): bool
    {
        return self::current($refresh) !== null;
    }

    public static function isAdmin(bool $refresh = true): bool
    {
        $user = self::user($refresh);

        return is_array($user) && ($user['role'] ?? '') === 'admin';
    }

    public static function isOwner(bool $refresh = true): bool
    {
        $user = self::user($refresh);

        return is_array($user) && ($user['role'] ?? '') === 'owner';
    }

    public static function login(string $username, string $password): array
    {
        $response = api()->post('/auth/login', [
            'body' => [
                'username' => $username,
                'password' => $password,
            ],
        ]);

        $data = $response['data'] ?? [];
        self::store([
            'token' => $data['session']['token'] ?? '',
            'session' => $data['session'] ?? null,
            'user' => $data['user'] ?? null,
            'umkm' => $data['umkm'] ?? null,
        ]);
        session_regenerate_id(true);

        return self::session();
    }

    public static function register(string $name, string $username, string $password): array
    {
        return api()->post('/auth/register', [
            'body' => [
                'name' => $name,
                'username' => $username,
                'password' => $password,
            ],
        ]);
    }

    public static function updateProfile(array $payload): array
    {
        $token = self::token();
        if ($token === '') {
            throw new RuntimeException('Silakan login terlebih dahulu.');
        }

        $response = api()->put('/auth/me', [
            'token' => $token,
            'body' => $payload,
        ]);

        $data = $response['data'] ?? [];
        self::store([
            'token' => $token,
            'session' => $data['session'] ?? (self::session()['session'] ?? null),
            'user' => $data['user'] ?? null,
            'umkm' => $data['umkm'] ?? null,
        ]);

        return $response;
    }

    public static function logout(bool $notify = true): void
    {
        $token = self::token();

        if ($token !== '') {
            try {
                api()->post('/auth/logout', ['token' => $token]);
            } catch (Throwable $throwable) {
                // Abaikan kegagalan logout server agar sesi lokal tetap bisa dihapus.
            }
        }

        self::forget(false);

        if ($notify) {
            flash('success', 'Anda berhasil logout.');
        }
    }

    public static function requireLogin(string $fallback = '/login.php'): array
    {
        $auth = self::current();
        if ($auth === null) {
            flash('error', 'Silakan login terlebih dahulu.');
            redirect($fallback);
        }

        return $auth;
    }

    public static function requireRole(string|array $roles, string $fallback = '/login.php'): array
    {
        $auth = self::requireLogin($fallback);
        $roles = is_array($roles) ? $roles : [$roles];
        $role = (string) ($auth['user']['role'] ?? '');

        if (!in_array($role, $roles, true)) {
            flash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
            redirect(self::homePath($role));
        }

        return $auth;
    }

    public static function homePath(?string $role = null): string
    {
        return $role === 'admin' ? '/admin/index.php' : '/dashboard.php';
    }

    public static function store(array $data): void
    {
        $_SESSION[self::SESSION_KEY] = [
            'token' => (string) ($data['token'] ?? ''),
            'session' => $data['session'] ?? null,
            'user' => $data['user'] ?? null,
            'umkm' => $data['umkm'] ?? null,
        ];
    }

    public static function forget(bool $regenerate = true): void
    {
        unset($_SESSION[self::SESSION_KEY]);

        if ($regenerate) {
            session_regenerate_id(true);
        }
    }
}
