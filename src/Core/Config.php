<?php

namespace App\Core;

/**
 * Konfiguratsiya yuklovchi. config/*.php fayllarini o'qiydi va
 * "fayl.kalit.ost_kalit" nuqtali sintaksis orqali kirishni ta'minlaydi.
 */
final class Config
{
    private static array $items = [];
    private static ?string $configPath = null;

    public static function setPath(string $path): void
    {
        self::$configPath = rtrim($path, '/');
        self::$items = [];
    }

    private static function path(): string
    {
        return self::$configPath ?? dirname(__DIR__, 2) . '/config';
    }

    private static function load(string $file): array
    {
        if (!isset(self::$items[$file])) {
            $full = self::path() . '/' . $file . '.php';
            self::$items[$file] = is_file($full) ? (array) require $full : [];
        }
        return self::$items[$file];
    }

    /**
     * @param string $key "app.name" yoki "database.connections.sqlite.driver"
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);
        $value = self::load($file);

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }
}
