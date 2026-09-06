<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\DashboardStats;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        if (Auth::role() === 'doctoral_student') {
            return $this->redirect('/doktorant/dashboard');
        }

        $filters = DashboardStats::sanitizeFilters($request->all());
        $data = DashboardStats::compute($filters);

        return $this->view('dashboard.index', [
            'user' => Auth::user(),
            'title' => 'Dashboard',
            'kpis' => $data['kpis'],
            'hero' => $data['hero'],
            'charts' => $data['charts'],
            'progress' => $data['progress'],
            'filters' => $filters,
            'filterOptions' => DashboardStats::filterOptions(),
        ]);
    }
}
