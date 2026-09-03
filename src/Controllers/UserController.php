<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Totp;
use App\Core\View;

/**
 * Foydalanuvchilar boshqaruvi (item 19 — bloklash, parolni tiklash majburlash,
 * 2FA skafoldi). Faqat users.* ruxsatiga ega rollar (super_admin).
 *
 * Barcha o'zgartiruvchi amallar AuditLog yozadi (kim, qachon, oldingi/yangi
 * qiymat).
 */
final class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = DB::select(
            'SELECT u.*, r.title_uz AS role_title, r.name AS role_name
             FROM users u LEFT JOIN roles r ON r.id = u.role_id
             ORDER BY u.full_name'
        );
        return $this->view('users.index', [
            'user' => Auth::user(),
            'title' => 'Foydalanuvchilar',
            'active' => 'users',
            'users' => $users,
            'twofaEnabled' => (bool) config('security.twofa.enabled', false),
            'canManage' => Auth::can('users.edit'),
        ]);
    }

    /**
     * Foydalanuvchini bloklaydi (is_blocked=1) — login qila olmaydi.
     */
    public function block(Request $request): Response
    {
        if (!Auth::can('users.edit')) {
            return $this->forbidden();
        }
        $id = (int) $request->param('id');
        $target = $this->findUser($id);
        if ($target === null) {
            return $this->notFound();
        }
        if ($id === (int) Auth::id()) {
            Session::flash('error', 'O\'zingizni bloklab bo\'lmaydi.');
            return $this->redirect('/users');
        }
        DB::run('UPDATE users SET is_blocked = 1, updated_at = :u WHERE id = :id', [
            'u' => date('Y-m-d H:i:s'), 'id' => $id,
        ]);
        AuditLogger::log('block', 'users', $id, ['is_blocked' => (int) $target['is_blocked']], ['is_blocked' => 1]);
        Session::flash('success', $target['full_name'] . ' bloklandi.');
        return $this->redirect('/users');
    }

    public function unblock(Request $request): Response
    {
        if (!Auth::can('users.edit')) {
            return $this->forbidden();
        }
        $id = (int) $request->param('id');
        $target = $this->findUser($id);
        if ($target === null) {
            return $this->notFound();
        }
        DB::run('UPDATE users SET is_blocked = 0, updated_at = :u WHERE id = :id', [
            'u' => date('Y-m-d H:i:s'), 'id' => $id,
        ]);
        AuditLogger::log('unblock', 'users', $id, ['is_blocked' => (int) $target['is_blocked']], ['is_blocked' => 0]);
        Session::flash('success', $target['full_name'] . ' blokdan chiqarildi.');
        return $this->redirect('/users');
    }

    /**
     * Keyingi kirishda parolni yangilashni majburlaydi (must_reset=1).
     */
    public function forceReset(Request $request): Response
    {
        if (!Auth::can('users.edit')) {
            return $this->forbidden();
        }
        $id = (int) $request->param('id');
        $target = $this->findUser($id);
        if ($target === null) {
            return $this->notFound();
        }
        DB::run('UPDATE users SET must_reset = 1, updated_at = :u WHERE id = :id', [
            'u' => date('Y-m-d H:i:s'), 'id' => $id,
        ]);
        AuditLogger::log('update', 'users', $id, ['must_reset' => (int) $target['must_reset']], ['must_reset' => 1]);
        Session::flash('success', $target['full_name'] . ' uchun parolni yangilash majburlandi.');
        return $this->redirect('/users');
    }

    /**
     * 2FA (TOTP) maxfiy kalitini yaratadi/o'chiradi (skafold, faqat 2FA sozlamasi
     * yoqilganda amal qiladi).
     */
    public function toggleTwofa(Request $request): Response
    {
        if (!Auth::can('users.edit')) {
            return $this->forbidden();
        }
        if (!config('security.twofa.enabled', false)) {
            Session::flash('error', '2FA sozlamasi o\'chirilgan (config/security.php).');
            return $this->redirect('/users');
        }
        $id = (int) $request->param('id');
        $target = $this->findUser($id);
        if ($target === null) {
            return $this->notFound();
        }
        $enable = empty($target['twofa_secret']);
        $secret = $enable ? Totp::generateSecret() : null;
        DB::run('UPDATE users SET twofa_secret = :s, updated_at = :u WHERE id = :id', [
            's' => $secret, 'u' => date('Y-m-d H:i:s'), 'id' => $id,
        ]);
        AuditLogger::log('update', 'users', $id, ['twofa' => $enable ? 'off' : 'on'], ['twofa' => $enable ? 'on' : 'off']);
        Session::flash('success', $enable
            ? ($target['full_name'] . ' uchun 2FA yoqildi. Maxfiy kalit: ' . $secret)
            : ($target['full_name'] . ' uchun 2FA o\'chirildi.'));
        return $this->redirect('/users');
    }

    private function findUser(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM users WHERE id = :id', ['id' => $id]);
    }

    private function notFound(): Response
    {
        return Response::html(View::render('errors.404'), 404);
    }

    private function forbidden(): Response
    {
        return Response::html(View::render('errors.403'), 403);
    }
}
