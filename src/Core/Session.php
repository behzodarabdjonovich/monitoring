<?php

namespace App\Core;

/**
 * Sessiya boshqaruvi (xavfsiz cookie sozlamalari bilan).
 */
final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        if (headers_sent()) {
            // CLI yoki test muhitida sarlavhalar allaqachon yuborilgan bo'lishi mumkin.
            return;
        }

        $cfg = Config::get('security.session', []);
        session_name($cfg['name'] ?? 'adpi_session');
        session_set_cookie_params([
            'lifetime' => (int) ($cfg['lifetime'] ?? 7200),
            'path' => '/',
            'httponly' => (bool) ($cfg['cookie_httponly'] ?? true),
            'samesite' => $cfg['cookie_samesite'] ?? 'Lax',
            'secure' => (bool) ($cfg['cookie_secure'] ?? false),
        ]);
        session_start();
        self::$started = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (!headers_sent()) {
                session_destroy();
            }
        }
        self::$started = false;
    }

    /**
     * Flash xabar: bir marta o'qilgach o'chiriladi.
     */
    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }
}
