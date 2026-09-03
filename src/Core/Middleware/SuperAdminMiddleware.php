<?php

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

/**
 * Faqat Super Admin kirishi mumkin bo'lgan marshrutlar uchun (item 17 —
 * audit jurnalini faqat Super Admin ko'ra oladi).
 */
final class SuperAdminMiddleware implements Middleware
{
    public function handle(Request $request): ?Response
    {
        if (!Auth::check()) {
            return Response::redirect('/login');
        }
        if (Auth::role() !== 'super_admin') {
            return Response::html(View::render('errors.403', ['permission' => 'super_admin']), 403);
        }
        return null;
    }
}
