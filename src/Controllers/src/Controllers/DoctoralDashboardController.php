<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

final class DoctoralDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/doktorant/login');
        }

        if (Auth::role() !== 'doktorant') {
            return $this->redirect('/dashboard');
        }

        return $this->view('doctoral.dashboard', [
            'title' => 'Doktorant kabineti',
            'user' => Auth::user(),
        ]);
    }
}
