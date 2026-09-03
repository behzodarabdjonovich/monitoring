<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Notification;

/**
 * Bildirishnomalar (item 15) — foydalanuvchining shaxsiy kabineti.
 *
 * Qo'ng'iroq (bell) va bildirishnomalar sahifasi, o'qilgan deb belgilash
 * hamda joriy ma'lumotlardan bildirishnomalarni qayta generatsiya qilish
 * (on-request).
 */
final class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        return $this->view('notifications.index', [
            'user' => Auth::user(),
            'title' => 'Bildirishnomalar',
            'active' => 'notifications',
            'notifications' => Notification::forUser($userId),
            'unread' => Notification::unreadCount($userId),
        ]);
    }

    /**
     * Bitta bildirishnomani o'qilgan deb belgilaydi.
     */
    public function markRead(Request $request): Response
    {
        $userId = (int) Auth::id();
        $id = (int) $request->param('id');
        Notification::markRead($id, $userId);
        return $this->redirect($request->header('Referer') ?? '/notifications');
    }

    public function markAllRead(Request $request): Response
    {
        Notification::markAllRead((int) Auth::id());
        Session::flash('success', 'Barcha bildirishnomalar o\'qilgan deb belgilandi.');
        return $this->redirect('/notifications');
    }

    /**
     * Joriy ma'lumotlardan bildirishnomalarni qayta hisoblaydi (on-request
     * generator). Console (bin/console notify) bilan bir xil mantiq.
     */
    public function generate(Request $request): Response
    {
        $count = Notification::generate();
        Session::flash('success', $count > 0
            ? ($count . ' ta yangi bildirishnoma shakllantirildi.')
            : 'Yangi bildirishnoma yo\'q.');
        return $this->redirect('/notifications');
    }
}
