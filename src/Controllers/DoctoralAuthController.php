<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

final class DoctoralAuthController extends Controller
{
    public function showLogin(Request $request): Response
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
            'login_action' => '/doktorant/login',
            'portal_title' => 'Doktorant kabinetiga kirish',
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
                'success' => null,
                'old_username' => (string) $request->input('username', ''),
                'login_action' => '/doktorant/login',
                'portal_title' => 'Doktorant kabinetiga kirish',
            ], 422);
        }

        $username = (string) $request->input('username');
        $password = (string) $request->input('password');

        if (!Auth::attempt($username, $password)) {
            return $this->view('auth.login', [
                'error' => 'Login yoki parol noto\'g\'ri.',
                'success' => null,
                'old_username' => $username,
                'login_action' => '/doktorant/login',
                'portal_title' => 'Doktorant kabinetiga kirish',
            ], 401);
        }

        if (Auth::role() !== 'doktorant') {
            Auth::logout();

            return $this->view('auth.login', [
                'error' => 'Bu sahifa faqat doktorantlar uchun.',
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
            ['portal' => 'doktorant'],
            Auth::id(),
            $request->ip()
        );

        return $this->redirect('/doktorant/dashboard');
    }
}
