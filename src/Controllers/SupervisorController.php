<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Supervisor;

/**
 * Ilmiy rahbarlar moduli (item 7) — profil, rahbarlik qilayotgan doktorantlar
 * relation, umumiy samaradorlik ko'rsatkichi.
 */
final class SupervisorController extends Controller
{
    public function index(Request $request): Response
    {
        $supervisors = Supervisor::all();
        foreach ($supervisors as &$s) {
            $s['effectiveness'] = Supervisor::effectiveness((int) $s['id']);
        }
        unset($s);

        return $this->view('supervisors.index', [
            'user' => Auth::user(),
            'title' => 'Ilmiy rahbarlar',
            'active' => 'supervisors',
            'supervisors' => $supervisors,
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $supervisor = Supervisor::findWithRelations($id);
        if ($supervisor === null) {
            return Response::html(\App\Core\View::render('errors.404'), 404);
        }
        return $this->view('supervisors.show', [
            'user' => Auth::user(),
            'title' => $supervisor['full_name'],
            'active' => 'supervisors',
            'supervisor' => $supervisor,
            'students' => Supervisor::students($id),
            'effectiveness' => Supervisor::effectiveness($id),
            'canEdit' => Auth::can('supervisors.edit'),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form(null);
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->param('id');
        $supervisor = Supervisor::find($id);
        if ($supervisor === null) {
            return Response::html(\App\Core\View::render('errors.404'), 404);
        }
        return $this->form($supervisor);
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
        $id = DB::insert('supervisors', $data);
        AuditLogger::log('create', 'supervisors', $id, null, $data);
        Session::flash('success', 'Ilmiy rahbar profili yaratildi.');
        return $this->redirect('/supervisors/' . $id);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $supervisor = Supervisor::find($id);
        if ($supervisor === null) {
            return Response::html(\App\Core\View::render('errors.404'), 404);
        }
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $sets = implode(', ', array_map(static fn ($k) => "$k = :$k", array_keys($data)));
        DB::run("UPDATE supervisors SET $sets WHERE id = :id", array_merge($data, ['id' => $id]));
        AuditLogger::log('update', 'supervisors', $id, $supervisor, $data);
        Session::flash('success', 'Ilmiy rahbar profili yangilandi.');
        return $this->redirect('/supervisors/' . $id);
    }

    /**
     * @return array<string,mixed>|Response
     */
    private function validated(Request $request): array|Response
    {
        $input = $request->all();
        $validator = Validator::make($input, ['full_name' => 'required|string|max:191']);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError() ?? 'Kiritishda xatolik.');
            return $this->redirect($request->header('Referer') ?? '/supervisors/create');
        }
        $strOrNull = static fn ($v) => ($v === null || $v === '') ? null : (string) $v;
        $intOrNull = static fn ($v) => ($v === null || $v === '') ? null : (int) $v;
        return [
            'full_name' => (string) $input['full_name'],
            'academic_degree' => $strOrNull($input['academic_degree'] ?? null),
            'academic_title' => $strOrNull($input['academic_title'] ?? null),
            'department_id' => $intOrNull($input['department_id'] ?? null),
            'specialty_id' => $intOrNull($input['specialty_id'] ?? null),
            'research_field' => $strOrNull($input['research_field'] ?? null),
            'meetings_note' => $strOrNull($input['meetings_note'] ?? null),
            'assignments_note' => $strOrNull($input['assignments_note'] ?? null),
            'approvals_note' => $strOrNull($input['approvals_note'] ?? null),
        ];
    }

    private function form(?array $supervisor): Response
    {
        return $this->view('supervisors.form', [
            'user' => Auth::user(),
            'title' => $supervisor === null ? 'Yangi ilmiy rahbar' : 'Ilmiy rahbarni tahrirlash',
            'active' => 'supervisors',
            'supervisor' => $supervisor,
            'departments' => DB::select('SELECT id, name FROM departments ORDER BY name'),
            'specialties' => DB::select('SELECT id, name FROM specialties ORDER BY name'),
        ]);
    }
}
