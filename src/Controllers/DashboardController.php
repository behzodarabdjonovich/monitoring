<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

/**
 * Dashboard (bosh sahifa) — bu bosqichda stub. To'liq KPI kartalar,
 * gauge va grafiklar keyingi bosqichda qo'shiladi (docs/05).
 */
final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('dashboard.index', [
            'user' => Auth::user(),
            'title' => 'Dashboard',
        ]);
    }
}
