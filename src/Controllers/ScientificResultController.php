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
use App\Models\Document;
use App\Models\ScientificResult;
use App\Models\DoctoralStudent;

/**
 * Ilmiy natijalar moduli (item 6).
 *
 * Barcha natija turlarini (ScientificResult::TYPES) yagona lookup sifatida
 * qo'llab-quvvatlaydi. Har natija doktorant va/yoki ilmiy rahbarga bog'lanadi
 * va tasdiqlovchi PDF/JPG/PNG fayl (FileStorage orqali documents) YOKI havola
 * (URL) biriktiriladi. Maqola/konferensiya turlari publications/conferences
 * specializatsiyalarini ham to'ldiradi (KPI'larga hissa).
 */
final class ScientificResultController extends Controller
{
    public function index(Request $request): Response
    {
    
    $filters = [
        'q' => trim((string) $request->query('q', '')),
        'type' => (string) $request->query('type', ''),
        'student' => (string) $request->query('student', ''),
    ];

    $students = DB::select(
        'SELECT id, full_name FROM doctoral_students ORDER BY full_name'
    );

    // Doktorant faqat o'z ilmiy natijalarini ko'radi.
    if (Auth::role() === 'doctoral_student') {
        $student = DoctoralStudent::findByUser((int) Auth::id());

        if ($student === null) {
            return $this->redirect('/doktorant/dashboard');
        }

        $filters['student'] = (string) $student['id'];
        $students = [$student];
    }

    return $this->view('results.index', [
        'user' => Auth::user(),
        'title' => 'Ilmiy natijalar',
        'active' => 'results',
        'results' => ScientificResult::search($filters),
        'filters' => $filters,
        'types' => ScientificResult::TYPES,
        'students' => $students,
        'canCreate' => Auth::can('scientific_results.create'),
    ]);
}

    public function create(Request $request): Response
{
    return $this->form(null);
}
    
public function store(Request $request): Response
{
    $data = $this->validated($request);

    if ($data instanceof Response) {
        return $data;
    }
    
    if (Auth::role() === 'doctoral_student') {
    $student = DoctoralStudent::findByUser((int) Auth::id());

    if ($student === null) {
        return $this->redirect('/doktorant/dashboard');
    }

    $data['student_id'] = (int) $student['id'];

    $data['supervisor_id'] = !empty($student['supervisor_id'])
    ? (int) $student['supervisor_id']
    : null;
}

// Tasdiqlovchi fayl (ixtiyoriy) - documents jadvaliga yoziladi.
$documentId = null;
        $file = $request->file('evidence_file');
        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $stored = FileStorage::store($file);
            } catch (\RuntimeException $ex) {
                return $this->back($request, 'Tasdiqlovchi fayl: ' . $ex->getMessage());
            }
            $documentId = DB::insert('documents', [
                'title' => $data['title'] !== '' ? $data['title'] : $stored['original_name'],
                'category' => 'maqolalar',
                'file_path' => $stored['path'],
                'original_name' => $stored['original_name'],
                'mime_type' => $stored['mime'],
                'file_size' => $stored['size'],
                'doc_type' => 'ilmiy_natija',
                'uploaded_by' => Auth::id(),
                'student_id' => $data['student_id'],
                'scientific_result_id' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            AuditLogger::log('upload', 'documents', $documentId, null, ['category' => 'maqolalar']);
        }

        $now = date('Y-m-d H:i:s');
        $insert = array_merge($data, [
            'document_id' => $documentId,
            'created_by' => Auth::id(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        // Maqola/konferensiya specializatsiyalarini to'ldiramiz (KPI).
        $insert = $this->attachSpecialization($insert, $data);

        $id = DB::insert('scientific_results', $insert);
        if ($documentId !== null) {
            DB::run('UPDATE documents SET scientific_result_id = :r WHERE id = :d', ['r' => $id, 'd' => $documentId]);
        }
        AuditLogger::log('create', 'scientific_results', $id, null, $insert);

        Session::flash('success', 'Ilmiy natija qo\'shildi.');
        return $this->redirect('/results');
    }

    // -----------------------------------------------------------------

    /**
     * @return array<string,mixed>|Response
     */
    private function validated(Request $request): array|Response
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'result_type' => 'required|in:' . implode(',', array_keys(ScientificResult::TYPES)),
            'title' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return $this->back($request, $validator->firstError() ?? 'Kiritishda xatolik.');
        }

        // Havola (URL) formati (ixtiyoriy) — bo'sh bo'lmasa yaroqli URL bo'lsin.
        $url = trim((string) ($input['url'] ?? ''));
        if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->back($request, 'Havola (URL) yaroqli manzil bo\'lishi kerak.');
        }

        $intOrNull = static fn ($v) => ($v === null || $v === '') ? null : (int) $v;
        $strOrNull = static fn ($v) => ($v === null || $v === '') ? null : (string) $v;

        return [
            'student_id' => $intOrNull($input['student_id'] ?? null),
            'supervisor_id' => $intOrNull($input['supervisor_id'] ?? null),
            'result_type' => (string) $input['result_type'],
            'title' => (string) $input['title'],
            'description' => $strOrNull($input['description'] ?? null),
            'achieved_at' => $strOrNull($input['achieved_at'] ?? null),
            'url' => $url === '' ? null : $url,
            'verified' => 0,
        ];
    }

    /**
     * Maqola/konferensiya turlari uchun publications/conferences yozuvini
     * yaratib, result'ga bog'laydi (specializatsiya feed).
     *
     * @param array<string,mixed> $insert
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function attachSpecialization(array $insert, array $data): array
    {
        $now = date('Y-m-d H:i:s');
        $type = (string) $data['result_type'];
        $studentId = $data['student_id'];
        $insert['publication_id'] = null;
        $insert['conference_id'] = null;
        if ($studentId === null) {
            return $insert;
        }
        if (in_array($type, ScientificResult::PUBLICATION_TYPES, true)) {
            $pubType = match ($type) {
                'scopus_maqola' => 'scopus',
                'wos_maqola' => 'wos',
                'oak_maqola' => 'milliy',
                default => 'boshqa',
            };
            $insert['publication_id'] = DB::insert('publications', [
                'student_id' => $studentId,
                'title' => (string) $data['title'],
                'journal' => null,
                'publication_type' => $pubType,
                'published_at' => $data['achieved_at'],
                'doi' => null,
                'created_at' => $now,
            ]);
        } elseif (in_array($type, ScientificResult::CONFERENCE_TYPES, true)) {
            $level = $type === 'xalqaro_konferensiya' ? 'xalqaro' : 'respublika';
            $insert['conference_id'] = DB::insert('conferences', [
                'student_id' => $studentId,
                'title' => (string) $data['title'],
                'conference_name' => null,
                'level' => $level,
                'location' => null,
                'event_date' => $data['achieved_at'],
                'created_at' => $now,
            ]);
        }
        return $insert;
    }

    private function form(?array $result): Response
{
    $students = DB::select(
        'SELECT id, full_name FROM doctoral_students ORDER BY full_name'
    );

    $supervisors = DB::select(
        'SELECT id, full_name FROM supervisors ORDER BY full_name'
    );

    if (Auth::role() === 'doctoral_student') {
        $student = DoctoralStudent::findByUser((int) Auth::id());

        if ($student === null) {
            return $this->redirect('/doktorant/dashboard');
        }

        $students = [$student];

        if (!empty($student['supervisor_id'])) {
            $supervisors = DB::select(
                'SELECT id, full_name FROM supervisors WHERE id = :id',
                ['id' => (int) $student['supervisor_id']]
            );
        } else {
            $supervisors = [];
        }
    }

    return $this->view('results.form', [
        'user' => Auth::user(),
        'title' => 'Yangi ilmiy natija',
        'active' => 'results',
        'result' => $result,
        'types' => ScientificResult::TYPES,
        'students' => $students,
        'supervisors' => $supervisors,
    ]);
}
    private function back(Request $request, string $error): Response
    {
        Session::flash('error', $error);

        return $this->redirect(
            $request->header('Referer') ?? '/results'
        );
    }

    public function verify(Request $request): Response
{
    if (!Auth::check()) {
        return $this->redirect('/login');
    }

    if (!in_array(Auth::role(), [
        'doctorate_office',
        'research_vice_head',
        'super_admin',
    ], true)) {
        return $this->forbidden();
    }

    $id = (int) $request->param('id');
    $result = ScientificResult::find($id);

    if ($result === null) {
        return $this->notFound();
    }

    if (($result['status'] ?? 'pending') !== 'pending') {
        Session::flash(
            'error',
            'Bu ilmiy natija allaqachon ko‘rib chiqilgan.'
        );

        return $this->redirect('/results');
    }

    DB::run(
        "UPDATE scientific_results
         SET status = 'approved',
             verified = 1,
             updated_at = :updated_at
         WHERE id = :id",
        [
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]
    );

    AuditLogger::log(
        'approve',
        'scientific_results',
        $id,
        [
            'status' => $result['status'] ?? 'pending',
            'verified' => $result['verified'] ?? 0,
        ],
        [
            'status' => 'approved',
            'verified' => 1,
        ]
    );

    Session::flash(
        'success',
        'Ilmiy natija tasdiqlandi.'
    );

    return $this->redirect('/results');
}

public function reject(Request $request): Response
{
    if (!Auth::check()) {
        return $this->redirect('/login');
    }

    if (!in_array(Auth::role(), [
        'doctorate_office',
        'research_vice_head',
        'super_admin',
    ], true)) {
        return $this->forbidden();
    }

    $id = (int) $request->param('id');
    $result = ScientificResult::find($id);

    if ($result === null) {
        return $this->notFound();
    }

    if (($result['status'] ?? 'pending') !== 'pending') {
        Session::flash(
            'error',
            'Bu ilmiy natija allaqachon ko‘rib chiqilgan.'
        );

        return $this->redirect('/results');
    }

    $rejectionReason = trim(
        (string) $request->input('rejection_reason', '')
    );

    if ($rejectionReason === '') {
        Session::flash(
            'error',
            'Rad etish sababini kiriting.'
        );

        return $this->redirect('/results');
    }

    if (mb_strlen($rejectionReason) > 500) {
        Session::flash(
            'error',
            'Rad etish sababi 500 belgidan oshmasligi kerak.'
        );

        return $this->redirect('/results');
    }

    DB::run(
        "UPDATE scientific_results
         SET status = 'rejected',
             verified = 0,
             rejection_reason = :rejection_reason,
             updated_at = :updated_at
         WHERE id = :id",
        [
            'rejection_reason' => $rejectionReason,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]
    );

    AuditLogger::log(
        'reject',
        'scientific_results',
        $id,
        [
            'status' => $result['status'] ?? 'pending',
            'verified' => $result['verified'] ?? 0,
            'rejection_reason' => $result['rejection_reason'] ?? null,
        ],
        [
            'status' => 'rejected',
            'verified' => 0,
            'rejection_reason' => $rejectionReason,
        ]
    );

    Session::flash(
        'success',
        'Ilmiy natija rad etildi.'
    );

    return $this->redirect('/results');
}
