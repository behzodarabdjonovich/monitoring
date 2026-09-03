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
    /** @var string[] Talab qilinadigan ruxsat kodlari (ANY — bittasi yetarli). */
    private array $permissions;

    /**
     * Bir yoki bir nechta ruxsat kodini qabul qiladi. Bir nechta berilsa
     * ANY (bittasi bo'lsa yetarli) semantikasi qo'llanadi — bu kontroller
     * ichidagi "documents.edit YOKI accreditation.edit" kabi tekshiruvlarni
     * marshrut darajasida ham aks ettiradi (guard drift'ining oldini oladi).
     */
    public function __construct(string ...$permissions)
    {
        $this->permissions = $permissions;
    }

    public function handle(Request $request): ?Response
    {
        if (!Auth::check()) {
            return Response::redirect('/login');
        }

        $allowed = false;
        foreach ($this->permissions as $permission) {
            if (Auth::can($permission)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            $html = View::render('errors.403', ['permission' => implode(' | ', $this->permissions)]);
            return Response::html($html, 403);
        }

        return null;
    }
}
