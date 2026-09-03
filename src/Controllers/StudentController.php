<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\FileStorage;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\DoctoralStudent;

/**
 * Doktorantlar moduli (item 4): to'liq elektron profil CRUD, fotosurat va
 * hujjat yuklash (FileStorage), qidiruv + filtr, faoliyat progress indikatori.
 *
 * RBAC: doktorant (doctoral_student) roli faqat O'Z kabinetini tahrirlaydi;
 * doktorantura bo'limi / super_admin barchani boshqaradi.
 */
final class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => (string) $request->query('type', ''),
            'specialty' => (string) $request->query('specialty', ''),
            'department' => (string) $request->query('department', ''),
            'status' => (string) $request->query('status', ''),
        ];
        $students = DoctoralStudent::search($filters);

        // Ro'yxatdagi har bir doktorant uchun faoliyat foizi.
        foreach ($students as &$s) {
            $s['activity_percent'] = DoctoralStudent::activityPercent((int) $s['id']);
        }
        unset($s);

        return $this->view('students.index', [
            'user' => Auth::user(),
            'title' => 'Doktorantlar',
            'active' => 'students',
            'students' => $students,
            'filters' => $filters,
            'specialties' => DB::select('SELECT id, name FROM specialties ORDER BY name'),
            'departments' => DB::select('SELECT id, name FROM departments ORDER BY name'),
            'types' => DoctoralStudent::TYPES,
            'statuses' => DoctoralStudent::STATUSES,
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $student = DoctoralStudent::findWithRelations($id);
        if ($student === null) {
            return $this->notFound();
        }
        if (!$this->canAccess($student)) {
            return $this->forbidden();
        }

        return $this->view('students.show', [
            'user' => Auth::user(),
            'title' => $student['full_name'],
            'active' => 'students',
            'student' => $student,
            'activityPercent' => DoctoralStudent::activityPercent($id),
            'related' => DoctoralStudent::profileData($id),
            'types' => DoctoralStudent::TYPES,
            'statuses' => DoctoralStudent::STATUSES,
            'canEdit' => $this->canEdit($student),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form(null);
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->param('id');
        $student = DoctoralStudent::find($id);
        if ($student === null) {
            return $this->notFound();
        }
        if (!$this->canEdit($student)) {
            return $this->forbidden();
        }
        return $this->form($student);
    }

    public function store(Request $request): Response
    {
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }

        // Fotosurat (ixtiyoriy).
        $photo = $request->file('photo');
        if ($photo !== null && ($photo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $stored = FileStorage::store($photo);
                $data['photo_path'] = $stored['path'];
            } catch (\RuntimeException $ex) {
                return $this->back($request, 'Fotosurat: ' . $ex->getMessage());
            }
        }

        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $id = DB::insert('doctoral_students', $data);

        AuditLogger::log('create', 'doctoral_students', $id, null, $data);
        $this->handleDocumentUpload($request, $id);

        Session::flash('success', 'Doktorant profili yaratildi.');
        return $this->redirect('/students/' . $id);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $student = DoctoralStudent::find($id);
        if ($student === null) {
            return $this->notFound();
        }
        if (!$this->canEdit($student)) {
            return $this->forbidden();
        }

        $data = $this->validated($request, $student);
        if ($data instanceof Response) {
            return $data;
        }

        $photo = $request->file('photo');
        if ($photo !== null && ($photo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $stored = FileStorage::store($photo);
                $data['photo_path'] = $stored['path'];
            } catch (\RuntimeException $ex) {
                return $this->back($request, 'Fotosurat: ' . $ex->getMessage());
            }
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $sets = implode(', ', array_map(static fn ($k) => "$k = :$k", array_keys($data)));
        DB::run("UPDATE doctoral_students SET $sets WHERE id = :id", array_merge($data, ['id' => $id]));

        AuditLogger::log('update', 'doctoral_students', $id, $student, $data);
        $this->handleDocumentUpload($request, $id);

        Session::flash('success', 'Doktorant profili yangilandi.');
        return $this->redirect('/students/' . $id);
    }

    // -----------------------------------------------------------------
    // Yordamchilar.
    // -----------------------------------------------------------------

    /**
     * Validatsiya + ruxsat etilgan maydonlarni ajratib olish.
     *
     * @return array<string,mixed>|Response
     */
    private function validated(Request $request, ?array $existing = null): array|Response
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'full_name' => 'required|string|max:191',
            'student_type' => 'required|in:' . implode(',', array_keys(DoctoralStudent::TYPES)),
            'enrollment_year' => 'integer',
            'course_stage' => 'integer',
            'dissertation_percent' => 'integer',
            'status' => 'in:' . implode(',', array_keys(DoctoralStudent::STATUSES)),
        ]);
        if ($validator->fails()) {
            return $this->back($request, $validator->firstError() ?? 'Kiritishda xatolik.');
        }

        $intOrNull = static fn ($v) => ($v === null || $v === '') ? null : (int) $v;
        $strOrNull = static fn ($v) => ($v === null || $v === '') ? null : (string) $v;

        return [
            'full_name' => (string) $input['full_name'],
            'student_type' => (string) $input['student_type'],
            'department_id' => $intOrNull($input['department_id'] ?? null),
            'specialty_id' => $intOrNull($input['specialty_id'] ?? null),
            'program_id' => $intOrNull($input['program_id'] ?? null),
            'supervisor_id' => $intOrNull($input['supervisor_id'] ?? null),
            'enrollment_year' => $intOrNull($input['enrollment_year'] ?? null),
            'course_stage' => $intOrNull($input['course_stage'] ?? null),
            'status' => $strOrNull($input['status'] ?? null) ?? 'active',
            'national_id' => $strOrNull($input['national_id'] ?? null),
            'dissertation_topic' => $strOrNull($input['dissertation_topic'] ?? null),
            'advisor_name' => $strOrNull($input['advisor_name'] ?? null),
            'admission_order' => $strOrNull($input['admission_order'] ?? null),
            'study_start_date' => $strOrNull($input['study_start_date'] ?? null),
            'study_end_date' => $strOrNull($input['study_end_date'] ?? null),
            'dissertation_percent' => $intOrNull($input['dissertation_percent'] ?? null),
            'scientific_results_summary' => $strOrNull($input['scientific_results_summary'] ?? null),
            'defense_readiness' => $strOrNull($input['defense_readiness'] ?? null),
        ];
    }

    /**
     * Yuklangan hujjatni (ixtiyoriy) FileStorage orqali saqlaydi va
     * documents jadvaliga yozadi.
     */
    private function handleDocumentUpload(Request $request, int $studentId): void
    {
        $doc = $request->file('document');
        if ($doc === null || ($doc['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return;
        }
        try {
            $stored = FileStorage::store($doc);
        } catch (\RuntimeException) {
            return;
        }
        $title = trim((string) $request->input('document_title', ''));
        $docId = DB::insert('documents', [
            'title' => $title !== '' ? $title : $stored['original_name'],
            'file_path' => $stored['path'],
            'mime_type' => $stored['mime'],
            'file_size' => $stored['size'],
            'doc_type' => 'doktorant',
            'uploaded_by' => Auth::id(),
            'student_id' => $studentId,
            'scientific_result_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        AuditLogger::log('upload', 'documents', $docId, null, ['student_id' => $studentId]);
    }

    private function form(?array $student): Response
    {
        return $this->view('students.form', [
            'user' => Auth::user(),
            'title' => $student === null ? 'Yangi doktorant' : 'Doktorantni tahrirlash',
            'active' => 'students',
            'student' => $student,
            'types' => DoctoralStudent::TYPES,
            'statuses' => DoctoralStudent::STATUSES,
            'specialties' => DB::select('SELECT id, name FROM specialties ORDER BY name'),
            'departments' => DB::select('SELECT id, name FROM departments ORDER BY name'),
            'programs' => DB::select('SELECT id, name, specialty_id FROM doctoral_programs ORDER BY name'),
            'supervisors' => DB::select('SELECT id, full_name FROM supervisors ORDER BY full_name'),
        ]);
    }

    /**
     * Ko'rish ruxsati: bo'lim/admin/rahbar barchani; doktorant faqat o'zini.
     */
    private function canAccess(array $student): bool
    {
        if (Auth::role() !== 'doctoral_student') {
            return true;
        }
        return (int) ($student['user_id'] ?? 0) === (int) Auth::id();
    }

    /**
     * Tahrirlash ruxsati: doctoral_students.edit ruxsati bor rollar barchani;
     * doktorant faqat O'Z kabinetini.
     */
    private function canEdit(array $student): bool
    {
        if (Auth::role() === 'doctoral_student') {
            return (int) ($student['user_id'] ?? 0) === (int) Auth::id();
        }
        return Auth::can('doctoral_students.edit') || Auth::can('doctoral_students.create');
    }

    private function back(Request $request, string $error): Response
    {
        Session::flash('error', $error);
        $ref = $request->header('Referer') ?? '/students';
        return $this->redirect($ref);
    }

    private function notFound(): Response
    {
        return Response::html(\App\Core\View::render('errors.404'), 404);
    }

    private function forbidden(): Response
    {
        return Response::html(\App\Core\View::render('errors.403'), 403);
    }
}
