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
        return $this->view('results.index', [
            'user' => Auth::user(),
            'title' => 'Ilmiy natijalar',
            'active' => 'results',
            'results' => ScientificResult::search($filters),
            'filters' => $filters,
            'types' => ScientificResult::TYPES,
            'students' => DB::select('SELECT id, full_name FROM doctoral_students ORDER BY full_name'),
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

        // Tasdiqlovchi fayl (ixtiyoriy) — documents jadvaliga yoziladi.
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
        return $this->view('results.form', [
            'user' => Auth::user(),
            'title' => 'Yangi ilmiy natija',
            'active' => 'results',
            'result' => $result,
            'types' => ScientificResult::TYPES,
            'students' => DB::select('SELECT id, full_name FROM doctoral_students ORDER BY full_name'),
            'supervisors' => DB::select('SELECT id, full_name FROM supervisors ORDER BY full_name'),
        ]);
    }

    private function back(Request $request, string $error): Response
    {
        Session::flash('error', $error);
        return $this->redirect($request->header('Referer') ?? '/results');
    }
}
