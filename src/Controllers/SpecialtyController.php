<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Specialty;

/**
 * Ixtisosliklar va ta'lim dasturlari moduli (item 8) — profil, akkreditatsiya
 * indikatorlari linki, akkreditatsiyaga tayyorlik foizi (ScoringEngine).
 */
final class SpecialtyController extends Controller
{
   public function index(Request $request): Response
{
    if (Auth::role() === 'doctoral_student') {
        $student = DB::selectOne(
            'SELECT specialty_id
             FROM doctoral_students
             WHERE user_id = :user_id
             LIMIT 1',
            ['user_id' => Auth::id()]
        );

        if ($student === null || empty($student['specialty_id'])) {
            $specialties = [];
        } else {
            $specialty = Specialty::find((int) $student['specialty_id']);
            $specialties = $specialty !== null ? [$specialty] : [];
        }
    } else {
        $specialties = Specialty::all();
    }

    foreach ($specialties as &$sp) {
    $id = (int) $sp['id'];

    $r = Specialty::accreditationReadiness($id);

    $sp['readiness_percent'] = $r['percent'];
    $sp['readiness_rag'] = $r['rag'];
    $sp['student_count'] = Specialty::studentCount($id);
    $sp['supervisor_count'] = count(Specialty::supervisors($id));
}
    unset($sp);

    return $this->view('specialties.index', [
        'user' => Auth::user(),
        'title' => 'Ixtisosliklar',
        'active' => 'specialties',
        'specialties' => $specialties,
    ]);
}

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $specialty = Specialty::findWithRelations($id);
        if ($specialty === null) {
            return Response::html(\App\Core\View::render('errors.404'), 404);
        }
        if (Auth::role() === 'doctoral_student') {
    $student = DB::selectOne(
        'SELECT specialty_id
         FROM doctoral_students
         WHERE user_id = :user_id
         LIMIT 1',
        ['user_id' => Auth::id()]
    );

    if (
        $student === null ||
        empty($student['specialty_id']) ||
        (int) $student['specialty_id'] !== $id
    ) {
        return Response::html(
            \App\Core\View::render('errors.403'),
            403
        );
    }
}
        return $this->view('specialties.show', [
            'user' => Auth::user(),
            'title' => $specialty['name'],
            'active' => 'specialties',
            'specialty' => $specialty,
            'programs' => Specialty::programs($id),
            'supervisors' => Specialty::supervisors($id),
            'studentCount' => Specialty::studentCount($id),
            'readiness' => Specialty::accreditationReadiness($id),
            'canEdit' => Auth::can('specialties.edit'),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form(null);
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->param('id');
        $specialty = Specialty::find($id);
        if ($specialty === null) {
            return Response::html(\App\Core\View::render('errors.404'), 404);
        }
        return $this->form($specialty);
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
        $id = DB::insert('specialties', $data);
        AuditLogger::log('create', 'specialties', $id, null, $data);
        Session::flash('success', 'Ixtisoslik yaratildi.');
        return $this->redirect('/specialties/' . $id);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $specialty = Specialty::find($id);
        if ($specialty === null) {
            return Response::html(\App\Core\View::render('errors.404'), 404);
        }
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $sets = implode(', ', array_map(static fn ($k) => "$k = :$k", array_keys($data)));
        DB::run("UPDATE specialties SET $sets WHERE id = :id", array_merge($data, ['id' => $id]));
        AuditLogger::log('update', 'specialties', $id, $specialty, $data);
        Session::flash('success', 'Ixtisoslik yangilandi.');
        return $this->redirect('/specialties/' . $id);
    }

    /**
     * @return array<string,mixed>|Response
     */
    private function validated(Request $request): array|Response
    {
        $input = $request->all();
        $validator = Validator::make($input, ['name' => 'required|string|max:191']);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError() ?? 'Kiritishda xatolik.');
            return $this->redirect($request->header('Referer') ?? '/specialties/create');
        }
        $strOrNull = static fn ($v) => ($v === null || $v === '') ? null : (string) $v;
        $intOrNull = static fn ($v) => ($v === null || $v === '') ? null : (int) $v;
      $departmentId = null;
$departmentName = trim((string) ($input['responsible_department_name'] ?? ''));

if ($departmentName !== '') {
    $department = DB::selectOne(
        'SELECT id FROM departments WHERE LOWER(name) = LOWER(:name) LIMIT 1',
        ['name' => $departmentName]
    );

    if ($department !== null) {
        $departmentId = (int) $department['id'];
    } else {
        $departmentId = DB::insert('departments', [
            'name' => $departmentName,
            'code' => null,
            'head_supervisor_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}  return [
            'code' => $strOrNull($input['code'] ?? null),
            'name' => (string) $input['name'],
            'branch' => $strOrNull($input['branch'] ?? null),
           'responsible_department_id' => $departmentId,
            'program_lead_supervisor_id' => $intOrNull($input['program_lead_supervisor_id'] ?? null),
            'scientific_potential' => $strOrNull($input['scientific_potential'] ?? null),
            'normative_docs' => $strOrNull($input['normative_docs'] ?? null),
            'material_base' => $strOrNull($input['material_base'] ?? null),
            'research_infrastructure' => $strOrNull($input['research_infrastructure'] ?? null),
            'international_cooperation' => $strOrNull($input['international_cooperation'] ?? null),
            'scientific_results' => $strOrNull($input['scientific_results'] ?? null),
            'accreditation_id' => $intOrNull($input['accreditation_id'] ?? null),
        ];
    }

    private function form(?array $specialty): Response
    {
        return $this->view('specialties.form', [
            'user' => Auth::user(),
            'title' => $specialty === null ? 'Yangi ixtisoslik' : 'Ixtisoslikni tahrirlash',
            'active' => 'specialties',
            'specialty' => $specialty,
            'departments' => DB::select('SELECT id, name FROM departments ORDER BY name'),
            'supervisors' => DB::select('SELECT id, full_name FROM supervisors ORDER BY full_name'),
            'accreditations' => DB::select('SELECT id, title FROM accreditations ORDER BY id'),
        ]);
    }
}
