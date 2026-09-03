<?php
/**
 * Xavfsizlik konfiguratsiyasi.
 */

$root = dirname(__DIR__);

return [
    // Sessiya sozlamalari (cookie xavfsizligi).
    'session' => [
        'name' => 'adpi_session',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        // Faqat HTTPS orqali cookie yuborish (production'da true qilib qo'ying).
        'cookie_secure' => (getenv('APP_ENV') ?: 'local') === 'production',
        // Sessiya harakatsizlik muddati (soniya) — 2 soat.
        'lifetime' => 7200,
    ],

    // CSRF token sessiya kaliti.
    'csrf' => [
        'session_key' => '_csrf_token',
        'field_name' => '_token',
        'header_name' => 'X-CSRF-Token',
    ],

    // Parol xesh algoritmi (PASSWORD_DEFAULT tavsiya etiladi).
    'password' => [
        'algo' => PASSWORD_DEFAULT,
    ],

    // Ikki bosqichli autentifikatsiya (2FA/TOTP) skafoldi. Standart holatda
    // o'chirilgan; yoqilsa, twofa_secret o'rnatilgan foydalanuvchilardan login
    // paytida TOTP kodi so'raladi (item 19). Muhit o'zgaruvchisi bilan yoqiladi.
    'twofa' => [
        'enabled' => (getenv('APP_2FA') ?: '0') === '1',
    ],

    // Fayl yuklash xavfsizligi (dalillar bazasi uchun).
    'uploads' => [
        // Fayllar webroot'dan TASHQARIDA saqlanadi.
        'storage_path' => $root . '/storage/uploads',
        // Ruxsat etilgan kengaytmalar (oq ro'yxat).
        'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
        // Ruxsat etilgan MIME turlari (oq ro'yxat).
        'allowed_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ],
        // Maksimal fayl hajmi (bayt) — 10 MB.
        'max_size' => 10 * 1024 * 1024,
    ],
];
