<?php

namespace App\Core;

/**
 * Fayllarni xavfsiz yuklash va himoyalangan olib berish.
 * - Kengaytma + MIME + hajm oq ro'yxati (allowlist) bo'yicha validatsiya.
 * - Fayllar webroot'dan TASHQARIDA storage/ ichida tasodifiy nom bilan saqlanadi.
 * - Olib berish faqat ushbu klass orqali (to'g'ridan-to'g'ri URL yo'q).
 */
final class FileStorage
{
    /**
     * Yuklangan faylni validatsiya qilib saqlaydi.
     *
     * @param array $file $_FILES['x'] ko'rinishidagi massiv
     * @return array{path:string,stored_name:string,original_name:string,mime:string,size:int}
     * @throws \RuntimeException validatsiya muvaffaqiyatsiz bo'lsa
     */
    public static function store(array $file): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new \RuntimeException('Yaroqsiz fayl yuklash parametrlari.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Fayl yuklashda xatolik (kod: ' . $file['error'] . ').');
        }

        $maxSize = (int) Config::get('security.uploads.max_size', 10 * 1024 * 1024);
        if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxSize) {
            throw new \RuntimeException('Fayl hajmi ruxsat etilgan chegaradan oshib ketdi.');
        }

        $original = (string) ($file['name'] ?? 'file');
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowedExt = (array) Config::get('security.uploads.allowed_extensions', []);
        if (!in_array($ext, $allowedExt, true)) {
            throw new \RuntimeException('Ruxsat etilmagan fayl kengaytmasi: ' . $ext);
        }

        $mime = self::detectMime($file['tmp_name']);
        $allowedMime = (array) Config::get('security.uploads.allowed_mime_types', []);
        if (!in_array($mime, $allowedMime, true)) {
            throw new \RuntimeException('Ruxsat etilmagan MIME turi: ' . $mime);
        }

        $dir = self::storagePath();
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Saqlash katalogini yaratib bo\'lmadi.');
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $target = $dir . '/' . $storedName;

        // Test/CLI muhitida is_uploaded_file/ move_uploaded_file ishlamasligi mumkin.
        $moved = false;
        if (is_uploaded_file($file['tmp_name'])) {
            $moved = move_uploaded_file($file['tmp_name'], $target);
        } elseif (is_file($file['tmp_name'])) {
            $moved = copy($file['tmp_name'], $target);
        }
        if (!$moved) {
            throw new \RuntimeException('Faylni saqlab bo\'lmadi.');
        }

        return [
            'path' => 'uploads/' . $storedName,
            'stored_name' => $storedName,
            'original_name' => $original,
            'mime' => $mime,
            'size' => (int) $file['size'],
        ];
    }

    /**
     * Saqlangan faylni himoyalangan tarzda o'qib qaytaradi (RBAC tekshiruvi
     * chaqiruvchi kontrollerda amalga oshiriladi).
     */
    public static function read(string $relativePath): ?string
    {
        $full = self::resolve($relativePath);
        if ($full === null || !is_file($full)) {
            return null;
        }
        $contents = file_get_contents($full);
        return $contents === false ? null : $contents;
    }

    public static function absolutePath(string $relativePath): ?string
    {
        return self::resolve($relativePath);
    }

    /**
     * Yo'lni storage ildizi ichida ekanligini tasdiqlaydi (path traversal
     * himoyasi).
     */
    private static function resolve(string $relativePath): ?string
    {
        $base = dirname(self::storagePath());
        $candidate = $base . '/' . ltrim($relativePath, '/');
        $realBase = realpath($base);
        $realCandidate = realpath($candidate);
        if ($realBase === false || $realCandidate === false) {
            return null;
        }
        if (!str_starts_with($realCandidate, $realBase . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $realCandidate;
    }

    private static function storagePath(): string
    {
        return rtrim((string) Config::get('security.uploads.storage_path', dirname(__DIR__, 2) . '/storage/uploads'), '/');
    }

    private static function detectMime(string $tmp): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $tmp);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }
        return 'application/octet-stream';
    }
}
