<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\DoctoralStudent;

final class DoctoralDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/doktorant/login');
        }

       if (Auth::role() !== 'doctoral_student') {
            return $this->redirect('/dashboard');
        }

        $user = Auth::user();

        $student = DoctoralStudent::findByUser((int) Auth::id());

        if ($student !== null) {
            $student = DoctoralStudent::findWithRelations((int) $student['id']);

            $activityPercent = DoctoralStudent::activityPercent(
                (int) $student['id']
            );

           $profileData = DoctoralStudent::profileData((int) $student['id']);
        } else {
            $activityPercent = 0;
            $profileData = [];
        }

        return $this->view('doctoral.dashboard', [
            'title' => 'Doktorant kabineti',
            'user' => $user,
            'student' => $student,
            'activityPercent' => $activityPercent,
            'profileData' => $profileData,
            'active' => 'dashboard',
        ]);
    }
}
