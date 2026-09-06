<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\DashboardStats;

/**
 * Dashboard (bosh sahifa) — item 3 analitik dashboard.
 *
 * KPI kartalar, HERO tayyorlik ko'rsatkichi (ScoringEngine), inline-SVG
 * grafiklar, progress barlar va yetti filtrli panel. Barcha raqamlar
 * DashboardStats orqali PDO prepared-statement so'rovlaridan olinadi.
 */
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
