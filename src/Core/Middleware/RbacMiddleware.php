<?php

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

/**
 * RBAC (rol asosidagi kirish nazorati) tekshiruvi.
 * Marshrutga biriktirilgan ruxsat kodini (masalan
 * "accreditation.configure") joriy foydalanuvchi roli role_permission
 * orqali egaligini tekshiradi; bo'lmasa 403 qaytaradi.
 */
final class RbacMiddleware implements Middleware
{
    private string $permission;

    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

    public function handle(Request $request): ?Response
    {
        if (!Auth::check()) {
            return Response::redirect('/login');
        }

        if (!Auth::can($this->permission)) {
            $html = View::render('errors.403', ['permission' => $this->permission]);
            return Response::html($html, 403);
        }

        return null;
    }
}
