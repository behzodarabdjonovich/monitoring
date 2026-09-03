<?php

namespace App\Core;

/**
 * CSRF token generatsiyasi va tekshiruvi.
 * Token sessiyada saqlanadi; o'zgartiruvchi (POST/PUT/PATCH/DELETE)
 * so'rovlarda majburiy (CsrfMiddleware orqali tekshiriladi).
 */
final class Csrf
{
    private static function sessionKey(): string
    {
        return Config::get('security.csrf.session_key', '_csrf_token');
    }

    public static function fieldName(): string
    {
        return Config::get('security.csrf.field_name', '_token');
    }

    public static function headerName(): string
    {
        return Config::get('security.csrf.header_name', 'X-CSRF-Token');
    }

    /**
     * Joriy sessiya tokenini oladi (bo'lmasa yaratadi).
     */
    public static function token(): string
    {
        Session::start();
        $key = self::sessionKey();
        $token = Session::get($key);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set($key, $token);
        }
        return $token;
    }

    /**
     * Yuborilgan tokenni sessiya tokeni bilan taqqoslaydi (timing-safe).
     */
    public static function verify(?string $token): bool
    {
        Session::start();
        $expected = Session::get(self::sessionKey());
        if (!is_string($expected) || $expected === '' || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($expected, $token);
    }

    /**
     * Yashirin input maydonini HTML sifatida qaytaradi.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="' . e(self::fieldName()) . '" value="' . e(self::token()) . '">';
    }
}
