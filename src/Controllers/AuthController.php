<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

/**
 * Autentifikatsiya: login, logout, parolni tiklash (forgot/reset).
 */
final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/dashboard');
        }
        return $this->view('auth.login', [
            'error' => Session::flash('error'),
            'success' => Session::flash('success'),
            'old_username' => '',
        ]);
    }

    public function login(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:191',
            'password' => 'required|string|max:191',
        ]);

        if ($validator->fails()) {
            return $this->view('auth.login', [
                'error' => $validator->firstError(),
                'old_username' => (string) $request->input('username', ''),
            ], 422);
        }

        $username = (string) $request->input('username');
        $password = (string) $request->input('password');

        // 2FA yoqilgan bo'lsa va foydalanuvchida twofa_secret bo'lsa — avval
        // parolni tekshirib, keyin TOTP bosqichiga o'tamiz (Auth::attempt
        // sessiyani darhol ochmasligi uchun alohida tekshiramiz).
        if (\App\Core\Config::get('security.twofa.enabled', false)) {
            $candidate = DB::selectOne(
                'SELECT * FROM users WHERE username = :u OR email = :u LIMIT 1',
                ['u' => $username]
            );
            if ($candidate !== null
                && (int) ($candidate['is_active'] ?? 1) === 1
                && (int) ($candidate['is_blocked'] ?? 0) === 0
                && Auth::verify($password, (string) $candidate['password_hash'])
                && !empty($candidate['twofa_secret'])
            ) {
                Session::set('_2fa_pending_user', (int) $candidate['id']);
                return $this->view('auth.twofa', ['error' => null]);
            }
        }

        if (!Auth::attempt($username, $password)) {
            // Muvaffaqiyatsiz urinishni audit qilamiz (foydalanuvchisiz).
            AuditLogger::log('login_failed', 'users', null, null, ['username' => $username], null, $request->ip());
            return $this->view('auth.login', [
                'error' => 'Login yoki parol noto\'g\'ri.',
                'old_username' => $username,
            ], 401);
        }

        // Muvaffaqiyatli login — audit yozuvi.
        AuditLogger::log('login', 'users', Auth::id(), null, null, Auth::id(), $request->ip());

        return $this->redirect('/dashboard');
    }

    /**
     * 2FA (TOTP) tasdiqlash bosqichi. Faqat parol tekshiruvidan o'tgan va
     * twofa_secret o'rnatilgan foydalanuvchi uchun (session _2fa_pending_user).
     */
    public function verifyTwofa(Request $request): Response
    {
        $pendingId = Session::get('_2fa_pending_user');
        if ($pendingId === null) {
            return $this->redirect('/login');
        }
        $user = DB::selectOne('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => (int) $pendingId]);
        $code = (string) $request->input('code', '');
        if ($user === null || empty($user['twofa_secret']) || !\App\Core\Totp::verify((string) $user['twofa_secret'], $code)) {
            AuditLogger::log('login_failed', 'users', (int) $pendingId, null, ['reason' => '2fa'], (int) $pendingId, $request->ip());
            return $this->view('auth.twofa', ['error' => 'Tasdiqlash kodi noto\'g\'ri.'], 401);
        }
        Session::remove('_2fa_pending_user');
        Auth::loginUser((int) $user['id']);
        AuditLogger::log('login', 'users', (int) $user['id'], null, null, (int) $user['id'], $request->ip());
        return $this->redirect('/dashboard');
    }

    public function logout(Request $request): Response
    {
        $userId = Auth::id();
        AuditLogger::log('logout', 'users', $userId, null, null, $userId, $request->ip());
        Auth::logout();
        Session::flash('success', 'Tizimdan chiqdingiz.');
        return $this->redirect('/login');
    }

    public function showForgot(Request $request): Response
    {
        return $this->view('auth.forgot-password', [
            'error' => Session::flash('error'),
            'success' => Session::flash('success'),
        ]);
    }

    /**
     * Parolni tiklash so'rovi. Foydalanuvchini oshkor qilmaslik uchun
     * har doim bir xil xabar qaytaradi. Token password_resets'ga yoziladi.
     */
    public function sendReset(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:191',
        ]);

      if ($validator->fails()) {
    return $this->view('auth.login', [
        'error' => $validator->firstError(),
        'old_username' => (string) $request->input('username', ''),
        'doctoral_login' => true,
        'login_action' => '/doktorant/login',
        'portal_title' => 'Doktorant kabinetiga kirish',
    ], 422);
}

        $email = (string) $request->input('email');
        $user = DB::selectOne('SELECT id FROM users WHERE email = :e LIMIT 1', ['e' => $email]);

        if ($user !== null) {
            $token = bin2hex(random_bytes(32));
            DB::insert('password_resets', [
                'user_id' => (int) $user['id'],
                'token' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                'used' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            AuditLogger::log('password_reset_requested', 'users', (int) $user['id'], null, null, (int) $user['id'], $request->ip());
            // Ishlab chiqarishda bu token email orqali yuboriladi. Oflayn
            // demo muhitida email xizmati yo'q.
        }

        Session::flash('success', 'Agar bunday email mavjud bo\'lsa, tiklash bo\'yicha ko\'rsatma yuborildi.');
        return $this->redirect('/forgot-password');
    }

    public function showReset(Request $request): Response
    {
        return $this->view('auth.reset-password', [
            'token' => (string) $request->query('token', ''),
            'error' => Session::flash('error'),
        ]);
    }

    public function reset(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            return $this->redirect('/reset-password?token=' . urlencode((string) $request->input('token', '')));
        }

        $tokenHash = hash('sha256', (string) $request->input('token'));
        $row = DB::selectOne(
            'SELECT * FROM password_resets WHERE token = :t AND used = 0 AND expires_at > :now LIMIT 1',
            ['t' => $tokenHash, 'now' => date('Y-m-d H:i:s')]
        );

        if ($row === null) {
            Session::flash('error', 'Tiklash havolasi yaroqsiz yoki muddati o\'tgan.');
            return $this->redirect('/forgot-password');
        }

        $hash = Auth::hash((string) $request->input('password'));
        DB::run('UPDATE users SET password_hash = :h, must_reset = 0, updated_at = :u WHERE id = :id', [
            'h' => $hash,
            'u' => date('Y-m-d H:i:s'),
            'id' => (int) $row['user_id'],
        ]);
        DB::run('UPDATE password_resets SET used = 1 WHERE id = :id', ['id' => (int) $row['id']]);
        AuditLogger::log('password_reset', 'users', (int) $row['user_id'], null, null, (int) $row['user_id'], $request->ip());

        Session::flash('success', 'Parol yangilandi. Endi tizimga kiring.');
        return $this->redirect('/login');
    }

public function showDoctoralLogin(Request $request): Response
{
    if (Auth::check()) {
        if (Auth::role() === 'doktorant') {
            return $this->redirect('/doktorant/dashboard');
        }

        return $this->redirect('/dashboard');
    }

   public function showDoctoralLogin(Request $request): Response
{
    if (Auth::check()) {
        if (Auth::role() === 'doktorant') {
            return $this->redirect('/doktorant/dashboard');
        }

        return $this->redirect('/dashboard');
    }

    return $this->view('auth.login', [
        'error' => Session::flash('error'),
        'success' => Session::flash('success'),
        'old_username' => '',
        'doctoral_login' => true,
        'login_action' => '/doktorant/login',
        'portal_title' => 'Doktorant kabinetiga kirish',
    ]);
}

public function doctoralLogin(Request $request): Response
{
    $validator = Validator::make($request->all(), [
        'username' => 'required|string|max:191',
        'password' => 'required|string|max:191',
    ]);

    if ($validator->fails()) {
        return $this->view('auth.login', [
            'error' => $validator->firstError(),
            'old_username' => (string) $request->input('username', ''),
            'doctoral_login' => true,
        ], 422);
    }

    $username = (string) $request->input('username');
    $password = (string) $request->input('password');

    if (!Auth::attempt($username, $password)) {
        AuditLogger::log(
            'login_failed',
            'users',
            null,
            null,
            ['username' => $username, 'portal' => 'doktorant'],
            null,
            $request->ip()
        );

       return $this->view('auth.login', [
    'error' => 'Login yoki parol noto\'g\'ri.',
    'old_username' => $username,
    'doctoral_login' => true,
    'login_action' => '/doktorant/login',
    'portal_title' => 'Doktorant kabinetiga kirish',
], 401);
    }

    // Faqat doktorant roliga ruxsat.
    if (Auth::role() !== 'doktorant') {
        $userId = Auth::id();

        AuditLogger::log(
            'login_failed',
            'users',
            $userId,
            null,
            ['reason' => 'wrong_portal', 'portal' => 'doktorant'],
            $userId,
            $request->ip()
        );

        Auth::logout();

        return $this->view('auth.login', [
    'error' => 'Bu kirish sahifasi faqat doktorantlar uchun.',
    'old_username' => $username,
    'doctoral_login' => true,
    'login_action' => '/doktorant/login',
    'portal_title' => 'Doktorant kabinetiga kirish',
], 403);
    }

    AuditLogger::log(
        'login',
        'users',
        Auth::id(),
        null,
        ['portal' => 'doktorant'],
        Auth::id(),
        $request->ip()
    );

return $this->redirect('/doktorant/dashboard');
    }
}
