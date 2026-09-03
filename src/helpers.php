<?php
/**
 * Global yordamchi funksiyalar (helpers).
 * PSR-4 orqali autoload'ning "files" bo'limida yuklanadi.
 */

if (!function_exists('e')) {
    /**
     * HTML-ekranlash (XSS himoyasi). BARCHA view'larda dinamik chiqish
     * shu funksiya orqali beriladi.
     */
    function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('config')) {
    /**
     * Konfiguratsiya qiymatini "fayl.kalit.ost_kalit" ko'rinishida oladi.
     */
    function config(string $key, mixed $default = null): mixed
    {
        return \App\Core\Config::get($key, $default);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $root = dirname(__DIR__);
        return $path === '' ? $root : $root . '/' . ltrim($path, '/');
    }
}
