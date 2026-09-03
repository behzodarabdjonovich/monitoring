<?php

namespace App\Core\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Xavfsizlik sarlavhalari middleware'i (item 19).
 *
 * Har bir javobga himoya sarlavhalarini biriktiradi. Middleware quvurida
 * to'g'ridan-to'g'ri Response'ga sarlavha qo'sha olmaganligi sababli (u null
 * qaytaradi va quvurni davom ettiradi), sarlavhalar Router tomonidan
 * applyHeaders() orqali yakuniy Response'ga qo'llaniladi.
 *
 * CSP faqat o'z (self) resurslarga ruxsat beradi — tashqi CDN/JS/CSS bloklanadi
 * (oflayn talab bilan mos). inline <style>/<svg> ishlatilmaydi, ammo topbar/
 * sidebar inline stil atributlariga ega bo'lgani uchun style-src 'unsafe-inline'
 * qo'shiladi (faqat inline atributlar uchun; tashqi manba yo'q).
 */
final class SecurityHeadersMiddleware implements Middleware
{
    /**
     * @return array<string,string>
     */
    public static function headers(): array
    {
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);

        return [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
            'X-XSS-Protection' => '1; mode=block',
            'Content-Security-Policy' => $csp,
        ];
    }

    /**
     * Sarlavhalarni javobga qo'llaydi (mavjudlarini qayta yozmasdan).
     */
    public static function apply(Response $response): Response
    {
        $existing = $response->headers();
        foreach (self::headers() as $name => $value) {
            if (!isset($existing[$name])) {
                $response->withHeader($name, $value);
            }
        }
        return $response;
    }

    public function handle(Request $request): ?Response
    {
        // Sarlavhalar yakuniy Response'ga Router::dispatch orqali qo'llaniladi.
        return null;
    }
}
