<?php

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Autentifikatsiya tekshiruvi: sessiyada foydalanuvchi bo'lmasa
 * /login sahifasiga yo'naltiradi.
 */
final class AuthMiddleware implements Middleware
{
    public function handle(Request $request): ?Response
    {
        if (!Auth::check()) {
            Session::flash('error', 'Iltimos, tizimga kiring.');
            return Response::redirect('/login');
        }
        // Harakatsizlik muddati (idle timeout) — muddati o'tgan bo'lsa chiqarib
        // yuboriladi (item 19 — sessiya nazorati).
        if (!Auth::enforceIdleTimeout()) {
            Session::flash('error', 'Sessiya muddati tugadi. Iltimos, qayta kiring.');
            return Response::redirect('/login');
        }
        return null;
    }
}
