<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\IndividualPlan;
use App\Models\PlanTask;
use App\Models\DoctoralStudent;

/**
 * Individual rejalar (item 5) — CRUD + tasdiqlash.
 */
final class PlanController extends Controller
{

    public function index(Request $request): Response
{
    $plans = [];

    if (Auth::role() === 'doctoral_student') {
        $student = DoctoralStudent::findByUser((int) Auth::id());

        if ($student === null) {
            return $this->redirect('/doktorant/dashboard');
        }

        $plans = IndividualPlan::forStudent((int) $student['id']);
    } else {
        $plans = IndividualPlan::all();
    }

    return $this->view('plans.index', [
        'user' => Auth::user(),
        'title' => 'Individual rejalar',
        'active' => 'plans',
        'plans' => $plans,
        'statuses' => IndividualPlan::STATUSES,
    ]);
}   // <-- MANA SHU } SHART

public function doctoral(Request $request): Response
{
    if (Auth::role() !== 'doctoral_student') {
        return $this->redirect('/dashboard');
    }

    $student = \App\Models\DoctoralStudent::findByUser((int) Auth::id());

    if ($student === null) {
        return $this->redirect('/doktorant/dashboard');
    }

    $plans = IndividualPlan::forStudent((int) $student['id']);

    if (empty($plans)) {
        return $this->view('plans.index', [
            'user' => Auth::user(),
            'title' => 'Individual reja',
            'active' => 'plans',
            'plans' => [],
            'statuses' => IndividualPlan::STATUSES,
        ]);
    }

    return $this->redirect('/plans/' . (int) $plans[0]['id']);
}
    
   public function show(Request $request): Response
{
    $id = (int) $request->param('id');
    $plan = IndividualPlan::findWithRelations($id);

    if ($plan === null) {
        return Response::html(\App\Core\View::render('errors.404'), 404);
    }

    if (Auth::role() === 'doctoral_student') {
        $student = DoctoralStudent::findByUser((int) Auth::id());

        if (
            $student === null ||
            (int) ($plan['student_id'] ?? 0) !== (int) $student['id']
        ) {
            return Response::html(\App\Core\View::render('errors.403'), 403);
        }
    }

    $tasks = PlanTask::forPlan($id);
              // Har vazifa uchun overdue va navbatdagi mumkin bo'lgan o'tishlarni hisoblaymiz.
        $role = Auth::role();
        foreach ($tasks as &$t) {
            $t['is_overdue'] = PlanTask::isOverdue($t);
            $t['allowed_targets'] = [];
            foreach (PlanTask::transitions()[$t['status']] ?? [] as $target) {
                if (PlanTask::roleCanTransition($role, $t['status'], $target)) {
                    $t['allowed_targets'][] = $target;
                }
            }
        }
        unset($t);

        return $this->view('plans.show', [
            'user' => Auth::user(),
            'title' => 'Individual reja',
            'active' => 'plans',
            'plan' => $plan,
            'tasks' => $tasks,
            'completion' => PlanTask::planCompletionPercent($tasks),
            'statuses' => IndividualPlan::STATUSES,
            'taskLabels' => PlanTask::LABELS,
            'canEdit' => Auth::can('individual_plans.edit'),
            'canApprove' => Auth::can('individual_plans.approve'),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form(null);
    }

    public function edit(Request $request): Response
{
    $id = (int) $request->param('id');
    $plan = IndividualPlan::find($id);

    if ($plan === null) {
        return Response::html(\App\Core\View::render('errors.404'), 404);
    }

    if (!$this->canAccessPlan($plan)) {
        return Response::html(
            \App\Core\View::render('errors.403'),
            403
        );
    }

    return $this->form($plan);
}

    public function store(Request $request): Response
    {
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $id = DB::insert('individual_plans', $data);
        AuditLogger::log('create', 'individual_plans', $id, null, $data);
        Session::flash('success', 'Individual reja yaratildi.');
        return $this->redirect('/plans/' . $id);
    }

    public function update(Request $request): Response
{
    $id = (int) $request->param('id');
    $plan = IndividualPlan::find($id);

    if ($plan === null) {
        return Response::html(\App\Core\View::render('errors.404'), 404);
    }

    if (!$this->canAccessPlan($plan)) {
        return Response::html(
            \App\Core\View::render('errors.403'),
            403
        );
    }

    $data = $this->validated($request);

    if ($data instanceof Response) {
        return $data;
    }

    $data['updated_at'] = date('Y-m-d H:i:s');

    $sets = implode(
        ', ',
        array_map(
            static fn ($k) => "$k = :$k",
            array_keys($data)
        )
    );

    DB::run(
        "UPDATE individual_plans SET $sets WHERE id = :id",
        array_merge($data, ['id' => $id])
    );

    AuditLogger::log(
        'update',
        'individual_plans',
        $id,
        $plan,
        $data
    );

    Session::flash('success', 'Individual reja yangilandi.');

    return $this->redirect('/plans/' . $id);
}

    public function approve(Request $request): Response
    {
        $id = (int) $request->param('id');
        $plan = IndividualPlan::find($id);
        if ($plan === null) {
            return Response::html(\App\Core\View::render('errors.404'), 404);
        }
        DB::run(
            'UPDATE individual_plans SET status = :st, approved_by = :ab, updated_at = :u WHERE id = :id',
            ['st' => 'approved', 'ab' => Auth::id(), 'u' => date('Y-m-d H:i:s'), 'id' => $id]
        );
        AuditLogger::log('approve', 'individual_plans', $id, ['status' => $plan['status']], ['status' => 'approved']);
        Session::flash('success', 'Reja tasdiqlandi.');
        return $this->redirect('/plans/' . $id);
    }

       // -----------------------------------------------------------------

    /**
     * @return array<string,mixed>|Response
     */
    private function validated(Request $request): array|Response
    {
        $input = $request->all();

        // Doktorant uchun student_id POSTdan olinmaydi.
        if (Auth::role() === 'doctoral_student') {
            $student = DoctoralStudent::findByUser((int) Auth::id());

            if ($student === null) {
                return $this->redirect('/doktorant/dashboard');
            }

            $input['student_id'] = (int) $student['id'];
        }

        $validator = Validator::make($input, [
            'student_id' => 'required|integer',
            'academic_year' => 'required|integer',
        ]);

        if ($validator->fails()) {
            Session::flash(
                'error',
                $validator->firstError() ?? 'Kiritishda xatolik.'
            );

            return $this->redirect(
                $request->header('Referer') ?? '/plans/create'
            );
        }

        $strOrNull = static fn ($v) =>
            ($v === null || $v === '') ? null : (string) $v;

        if (Auth::role() === 'doctoral_student') {
            $student = DoctoralStudent::findByUser((int) Auth::id());

            $supervisorId = !empty($student['supervisor_id'])
                ? (int) $student['supervisor_id']
                : null;
        } else {
            $supervisorId = ($input['supervisor_id'] ?? '') === ''
                ? null
                : (int) $input['supervisor_id'];
        }

        return [
            'student_id' => (int) $input['student_id'],
            'supervisor_id' => $supervisorId,
            'academic_year' => (int) $input['academic_year'],
            'start_date' => $strOrNull($input['start_date'] ?? null),
            'end_date' => $strOrNull($input['end_date'] ?? null),
            'status' => in_array(
                $input['status'] ?? '',
                array_keys(IndividualPlan::STATUSES),
                true
            )
                ? (string) $input['status']
                : 'draft',
        ];
    }

    private function form(?array $plan): Response
    {
        if (Auth::role() === 'doctoral_student') {
            $student = DoctoralStudent::findByUser((int) Auth::id());

            if ($student === null) {
                return $this->redirect('/doktorant/dashboard');
            }

            $students = [[
                'id' => (int) $student['id'],
                'full_name' => (string) $student['full_name'],
            ]];
        } else {
            $students = DB::select(
                'SELECT id, full_name
                 FROM doctoral_students
                 ORDER BY full_name'
            );
        }

        return $this->view('plans.form', [
            'user' => Auth::user(),
            'title' => $plan === null
                ? 'Yangi reja'
                : 'Rejani tahrirlash',
            'active' => 'plans',
            'plan' => $plan,
            'students' => $students,
            'supervisors' => DB::select(
                'SELECT id, full_name
                 FROM supervisors
                 ORDER BY full_name'
            ),
            'statuses' => IndividualPlan::STATUSES,
        ]);
    }

    private function canAccessPlan(array $plan): bool
    {
        if (Auth::role() !== 'doctoral_student') {
            return true;
        }

        $student = DoctoralStudent::findByUser((int) Auth::id());

        if ($student === null) {
            return false;
        }

        return (int) ($plan['student_id'] ?? 0)
            === (int) $student['id'];
    }
public function delete(Request $request): Response
{
    $id = (int) $request->param('id');

    $plan = IndividualPlan::find($id);

    if ($plan === null) {
        return $this->notFound();
    }

    if (!IndividualPlan::canDelete($id)) {
        Session::flash(
            'error',
            'Individual reja o‘chirilmadi. Unda vazifalar mavjud.'
        );

        return $this->redirect('/plans');
    }

    IndividualPlan::delete($id);

    AuditLogger::log(
        'delete',
        'individual_plans',
        $id,
        $plan,
        null
    );

    Session::flash('success', 'Individual reja muvaffaqiyatli o‘chirildi.');

    return $this->redirect('/plans');
}
}
