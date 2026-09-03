<?php

namespace App\Core;

/**
 * Autentifikatsiya va avtorizatsiya yordamchisi.
 * - Parol xesh/tekshiruv (password_hash / password_verify).
 * - Sessiya asosidagi login (login vaqtida session_regenerate_id).
 * - Joriy foydalanuvchi va uning rol/ruxsatlariga kirish.
 */
final class Auth
{
    private const SESSION_USER_ID = '_auth_user_id';

    private static ?array $cachedUser = null;
    private static ?array $cachedPermissions = null;

    /**
     * Parolni xavfsiz xeshlaydi.
     */
    public static function hash(string $password): string
    {
        return password_hash($password, Config::get('security.password.algo', PASSWORD_DEFAULT));
    }

    /**
     * Parolni xesh bilan tekshiradi.
     */
    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Login/parol bilan urinish. Muvaffaqiyatli bo'lsa foydalanuvchini
     * sessiyaga yozadi va session id'ni yangilaydi.
     */
    public static function attempt(string $username, string $password): bool
    {
        $user = DB::selectOne(
            'SELECT * FROM users WHERE username = :u OR email = :u LIMIT 1',
            ['u' => $username]
        );

        if ($user === null) {
            return false;
        }
        if ((int) ($user['is_active'] ?? 1) !== 1) {
            return false;
        }
        if ((int) ($user['is_blocked'] ?? 0) === 1) {
            return false;
        }
        if (!self::verify($password, (string) $user['password_hash'])) {
            return false;
        }

        self::loginUser((int) $user['id']);
        return true;
    }

    /**
     * Foydalanuvchini id bo'yicha tizimga kiritadi (sessiya fixation'ga
     * qarshi session id yangilanadi).
     */
    public static function loginUser(int $userId): void
    {
        Session::start();
        Session::regenerate();
        Session::set(self::SESSION_USER_ID, $userId);
        self::$cachedUser = null;
        self::$cachedPermissions = null;
    }

    public static function logout(): void
    {
        Session::start();
        Session::remove(self::SESSION_USER_ID);
        Session::regenerate();
        self::$cachedUser = null;
        self::$cachedPermissions = null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function id(): ?int
    {
        Session::start();
        $id = Session::get(self::SESSION_USER_ID);
        return $id === null ? null : (int) $id;
    }

    /**
     * Joriy foydalanuvchi yozuvi (rol nomi bilan birga).
     */
    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }
        $id = self::id();
        if ($id === null) {
            return null;
        }
        $user = DB::selectOne(
            'SELECT u.*, r.name AS role_name, r.title_uz AS role_title
             FROM users u LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id LIMIT 1',
            ['id' => $id]
        );
        self::$cachedUser = $user;
        return $user;
    }

    /**
     * Joriy foydalanuvchi rol nomini qaytaradi (masalan super_admin).
     */
    public static function role(): ?string
    {
        $user = self::user();
        return $user['role_name'] ?? null;
    }

    /**
     * Joriy foydalanuvchi ruxsat kodlari ro'yxati (role_permission orqali).
     *
     * @return string[]
     */
    public static function permissions(): array
    {
        if (self::$cachedPermissions !== null) {
            return self::$cachedPermissions;
        }
        $user = self::user();
        if ($user === null || $user['role_id'] === null) {
            return self::$cachedPermissions = [];
        }
        $rows = DB::select(
            'SELECT p.code FROM permissions p
             INNER JOIN role_permission rp ON rp.permission_id = p.id
             WHERE rp.role_id = :rid',
            ['rid' => (int) $user['role_id']]
        );
        return self::$cachedPermissions = array_map(static fn ($r) => $r['code'], $rows);
    }

    /**
     * Joriy foydalanuvchi berilgan ruxsatga egami?
     */
    public static function can(string $permission): bool
    {
        return in_array($permission, self::permissions(), true);
    }

    /**
     * Test / boshqa kontekstda keshni tozalash uchun.
     */
    public static function flushCache(): void
    {
        self::$cachedUser = null;
        self::$cachedPermissions = null;
    }
}
