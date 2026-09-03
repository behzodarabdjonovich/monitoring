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

/**
 * Individual rejalar (item 5) — CRUD + tasdiqlash.
 */
final class PlanController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('plans.index', [
            'user' => Auth::user(),
            'title' => 'Individual rejalar',
            'active' => 'plans',
            'plans' => IndividualPlan::all(),
            'statuses' => IndividualPlan::STATUSES,
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $plan = IndividualPlan::findWithRelations($id);
        if ($plan === null) {
            return Response::html(\App\Core\View::render('errors.404'), 404);
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
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $sets = implode(', ', array_map(static fn ($k) => "$k = :$k", array_keys($data)));
        DB::run("UPDATE individual_plans SET $sets WHERE id = :id", array_merge($data, ['id' => $id]));
        AuditLogger::log('update', 'individual_plans', $id, $plan, $data);
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
        $validator = Validator::make($input, [
            'student_id' => 'required|integer',
            'academic_year' => 'required|string|max:32',
        ]);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError() ?? 'Kiritishda xatolik.');
            return $this->redirect($request->header('Referer') ?? '/plans/create');
        }
        $strOrNull = static fn ($v) => ($v === null || $v === '') ? null : (string) $v;
        return [
            'student_id' => (int) $input['student_id'],
            'supervisor_id' => ($input['supervisor_id'] ?? '') === '' ? null : (int) $input['supervisor_id'],
            'academic_year' => (string) $input['academic_year'],
            'start_date' => $strOrNull($input['start_date'] ?? null),
            'end_date' => $strOrNull($input['end_date'] ?? null),
            'status' => in_array($input['status'] ?? '', array_keys(IndividualPlan::STATUSES), true) ? (string) $input['status'] : 'draft',
        ];
    }

    private function form(?array $plan): Response
    {
        return $this->view('plans.form', [
            'user' => Auth::user(),
            'title' => $plan === null ? 'Yangi reja' : 'Rejani tahrirlash',
            'active' => 'plans',
            'plan' => $plan,
            'students' => DB::select('SELECT id, full_name FROM doctoral_students ORDER BY full_name'),
            'supervisors' => DB::select('SELECT id, full_name FROM supervisors ORDER BY full_name'),
            'statuses' => IndividualPlan::STATUSES,
        ]);
    }
}
