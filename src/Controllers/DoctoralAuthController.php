<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

/**
 * Doktorant portali uchun alohida autentifikatsiya.
 */
final class DoctoralAuthController extends Controller
{
    /**
     * Doktorant login sahifasini ko'rsatadi.
     */
    public function showLogin(Request $request): Response
    {
        if (Auth::check()) {
           if (Auth::role() === 'doctoral_student') {
                return $this->redirect('/doktorant/dashboard');
            }

            return $this->redirect('/dashboard');
        }

        return $this->view('auth.login', [
            'error' => Session::flash('error'),
            'success' => Session::flash('success'),
            'old_username' => '',
            'login_action' => '/doktorant/login',
            'portal_title' => 'Doktorant kabinetiga kirish',
        ]);
    }

    /**
     * Doktorant loginini tekshiradi.
     */
    public function login(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:191',
            'password' => 'required|string|max:191',
        ]);

        if ($validator->fails()) {
            return $this->view('auth.login', [
                'error' => $validator->firstError(),
                'success' => null,
                'old_username' => (string) $request->input('username', ''),
                'login_action' => '/doktorant/login',
                'portal_title' => 'Doktorant kabinetiga kirish',
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
                [
                    'username' => $username,
                    'portal' => 'doktorant',
                ],
                null,
                $request->ip()
            );

            return $this->view('auth.login', [
                'error' => 'Login yoki parol noto\'g\'ri.',
                'success' => null,
                'old_username' => $username,
                'login_action' => '/doktorant/login',
                'portal_title' => 'Doktorant kabinetiga kirish',
            ], 401);
        }

        // Faqat doktorant roliga ruxsat.
      if (Auth::role() !== 'doctoral_student') {
            $userId = Auth::id();

            AuditLogger::log(
                'login_failed',
                'users',
                $userId,
                null,
                [
                    'reason' => 'wrong_portal',
                    'portal' => 'doktorant',
                ],
                $userId,
                $request->ip()
            );

            Auth::logout();

            return $this->view('auth.login', [
                'error' => 'Bu kirish sahifasi faqat doktorantlar uchun.',
                'success' => null,
                'old_username' => $username,
                'login_action' => '/doktorant/login',
                'portal_title' => 'Doktorant kabinetiga kirish',
            ], 403);
        }

        AuditLogger::log(
            'login',
            'users',
            Auth::id(),
            null,
            [
                'portal' => 'doktorant',
            ],
            Auth::id(),
            $request->ip()
        );

        return $this->redirect('/doktorant/dashboard');
        /**
     * Doktorant kabinetidan chiqish.
     */
    public function logout(Request $request): Response
    {
        if (Auth::check()) {
            $userId = Auth::id();

            AuditLogger::log(
                'logout',
                'users',
                $userId,
                null,
                [
                    'portal' => 'doktorant',
                ],
                $userId,
                $request->ip()
            );
        }

        Auth::logout();

        return $this->redirect('/doktorant/login');
    }
}
