<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\DoctoralStudent;
use App\Models\Supervisor;
use App\Models\SupervisorRequest;
use App\Core\AuditLogger;
use App\Core\DB;

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

    /**
     * Ilmiy bo‘lim: barcha ilmiy rahbar so‘rovlarini ko‘rish.
     */
    public function officeIndex(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/login');
        }

        if (!in_array(Auth::role(), ['doctorate_office', 'research_vice_head', 'super_admin'], true)) {
            return $this->forbidden();
        }

        return $this->view('supervisor-requests.index', [
            'user' => Auth::user(),
            'title' => 'Ilmiy rahbar so‘rovlari',
            'active' => 'supervisor-requests',
            'requests' => SupervisorRequest::allWithRelations(),
        ]);
    }

    /**
     * Ilmiy bo‘lim: so‘rovni tasdiqlash.
     */
    public function approve(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/login');
        }

        if (!in_array(Auth::role(), ['doctorate_office', 'research_vice_head', 'super_admin'], true)) {
            return $this->forbidden();
        }

        $id = (int) $request->param('id');
        $reviewNote = trim((string) $request->input('review_note', ''));

        $supervisorRequest = SupervisorRequest::find($id);

        if ($supervisorRequest === null) {
            return $this->notFound();
        }

        if (($supervisorRequest['status'] ?? '') !== SupervisorRequest::PENDING) {
            Session::flash('error', 'Bu so‘rov avval ko‘rib chiqilgan.');
            return $this->redirect('/ilmiy-bolim/rahbar-sorovlari');
        }

        $student = DoctoralStudent::find((int) $supervisorRequest['student_id']);
        $supervisor = Supervisor::find((int) $supervisorRequest['supervisor_id']);

        if ($student === null || $supervisor === null) {
            Session::flash('error', 'Doktorant yoki ilmiy rahbar topilmadi.');
            return $this->redirect('/ilmiy-bolim/rahbar-sorovlari');
        }

        $oldSupervisorId = $student['supervisor_id'] ?? null;
        $now = date('Y-m-d H:i:s');

        DB::run(
            'UPDATE doctoral_students
             SET supervisor_id = :supervisor_id,
                 updated_at = :updated_at
             WHERE id = :id',
            [
                'supervisor_id' => (int) $supervisorRequest['supervisor_id'],
                'updated_at' => $now,
                'id' => (int) $student['id'],
            ]
        );

        SupervisorRequest::approve(
            $id,
            (int) Auth::id(),
            $reviewNote !== '' ? $reviewNote : null
        );

        AuditLogger::log(
            'update',
            'doctoral_students',
            (int) $student['id'],
            ['supervisor_id' => $oldSupervisorId],
            [
                'supervisor_id' => (int) $supervisorRequest['supervisor_id'],
                'supervisor_request_id' => $id,
            ]
        );

        Session::flash('success', 'Ilmiy rahbar so‘rovi tasdiqlandi.');

        return $this->redirect('/ilmiy-bolim/rahbar-sorovlari');
    }

    /**
     * Ilmiy bo‘lim: so‘rovni rad etish.
     */
    public function reject(Request $request): Response
    {
        if (!Auth::check()) {
            return $this->redirect('/login');
        }

        if (!in_array(Auth::role(), ['doctorate_office', 'research_vice_head', 'super_admin'], true)) {
            return $this->forbidden();
        }

        $id = (int) $request->param('id');
        $reviewNote = trim((string) $request->input('review_note', ''));

        $supervisorRequest = SupervisorRequest::find($id);

        if ($supervisorRequest === null) {
            return $this->notFound();
        }

        if (($supervisorRequest['status'] ?? '') !== SupervisorRequest::PENDING) {
            Session::flash('error', 'Bu so‘rov avval ko‘rib chiqilgan.');
            return $this->redirect('/ilmiy-bolim/rahbar-sorovlari');
        }

        if ($reviewNote === '') {
            Session::flash('error', 'Rad etish sababini kiriting.');
            return $this->redirect('/ilmiy-bolim/rahbar-sorovlari');
        }

        SupervisorRequest::reject(
            $id,
            (int) Auth::id(),
            $reviewNote
        );

        AuditLogger::log(
            'update',
            'supervisor_requests',
            $id,
            ['status' => SupervisorRequest::PENDING],
            [
                'status' => SupervisorRequest::REJECTED,
                'review_note' => $reviewNote,
            ]
        );

        Session::flash('success', 'Ilmiy rahbar so‘rovi rad etildi.');

        return $this->redirect('/ilmiy-bolim/rahbar-sorovlari');
    }
}
