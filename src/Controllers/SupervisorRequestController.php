<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\DoctoralStudent;
use App\Models\Supervisor;
use App\Models\SupervisorRequest;

final class SupervisorRequestController extends Controller
{
    /**
     * Doktorantning ilmiy rahbar sahifasi.
     */
    public function index(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/doktorant/login');
        }

        if (Auth::role() !== 'doctoral_student') {
            return $this->forbidden();
        }

        $student = DoctoralStudent::findByUser((int) Auth::id());

        if ($student === null) {
            return $this->redirect('/doktorant/dashboard');
        }

        $currentSupervisor = null;

        if (!empty($student['supervisor_id'])) {
            $currentSupervisor = Supervisor::findWithRelations(
                (int) $student['supervisor_id']
            );
        }

        return $this->view('doctoral.supervisor', [
            'user' => Auth::user(),
            'title' => 'Ilmiy rahbar',
            'active' => 'supervisor',
            'student' => $student,
            'currentSupervisor' => $currentSupervisor,
            'supervisors' => Supervisor::all(),
            'pendingRequest' => SupervisorRequest::pendingForStudent(
                (int) $student['id']
            ),
            'requests' => SupervisorRequest::forStudent(
                (int) $student['id']
            ),
        ]);
    }

    /**
     * Doktorant tomonidan ilmiy rahbar nomzodini yuborish.
     */
    public function store(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/doktorant/login');
        }

        if (Auth::role() !== 'doctoral_student') {
            return $this->forbidden();
        }

        $student = DoctoralStudent::findByUser((int) Auth::id());

        if ($student === null) {
            return $this->redirect('/doktorant/dashboard');
        }

        // Bir vaqtning o‘zida faqat bitta pending so‘rov.
        $pending = SupervisorRequest::pendingForStudent(
            (int) $student['id']
        );

        if ($pending !== null) {
            Session::flash(
                'error',
                'Sizda allaqachon ko‘rib chiqilayotgan ilmiy rahbar so‘rovi mavjud.'
            );

            return $this->redirect('/doktorant/ilmiy-rahbar');
        }

        $supervisorId = (int) $request->input('supervisor_id', 0);
        $studentNote = trim(
            (string) $request->input('student_note', '')
        );

        if ($supervisorId <= 0) {
            Session::flash('error', 'Ilmiy rahbarni tanlang.');
            return $this->redirect('/doktorant/ilmiy-rahbar');
        }

        // URL/form qiymatiga ishonmaymiz:
        // tanlangan rahbar bazada haqiqatan mavjud bo‘lishi kerak.
        $supervisor = Supervisor::find($supervisorId);

        if ($supervisor === null) {
            Session::flash('error', 'Tanlangan ilmiy rahbar topilmadi.');
            return $this->redirect('/doktorant/ilmiy-rahbar');
        }

        SupervisorRequest::create(
            (int) $student['id'],
            $supervisorId,
            $studentNote !== '' ? $studentNote : null
        );

        Session::flash(
            'success',
            'Ilmiy rahbar bo‘yicha so‘rovingiz ilmiy bo‘limga yuborildi.'
        );

        return $this->redirect('/doktorant/ilmiy-rahbar');
    }
}
