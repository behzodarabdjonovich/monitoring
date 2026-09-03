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
