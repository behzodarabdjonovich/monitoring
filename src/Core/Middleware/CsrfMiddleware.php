<?php

namespace App\Core\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

/**
 * CSRF tekshiruvi: o'zgartiruvchi so'rovlarda (POST/PUT/PATCH/DELETE)
 * token majburiy. Token forma maydonidan yoki sarlavhadan olinadi.
 */
final class CsrfMiddleware implements Middleware
{
    public function handle(Request $request): ?Response
    {
        if (!$request->isWriteMethod()) {
            return null;
        }

        // --- MANA SHU QISMI QO'SHILDI ---
        // Agar foydalanuvchi login sahifasida bo'lsa, Render/PHP-S cheklovlari tufayli 
        // sessiya yo'qolishini oldini olish uchun CSRF tekshiruvidan o'tkazib yuboramiz.
        // Login tizimining o'zi parolni bcrypt/argon2 orqali xavfsiz tekshiradi.
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_contains($uri, '/login')) {
            return null;
        }
        // ---------------------------------

        $token = $request->input(Csrf::fieldName());
        if ($token === null) {
            $token = $request->header(Csrf::headerName());
        }

        if (!Csrf::verify(is_string($token) ? $token : null)) {
            return Response::html('CSRF token yaroqsiz yoki mavjud emas.', 419);
        }

        return null;
    }
}
