<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\FileStorage;
use App\Core\Request;
use App\Core\Response;
use App\Core\ScoringEngine;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Document;
use App\Models\DoctoralStudent;

/**
 * Dalillar (Evidence) bazasi moduli (item 11).
 *
 * Markazlashtirilgan yuklash/ro'yxat/ko'rish; fayllar storage/ ichida
 * (webroot'dan tashqarida) saqlanadi va faqat himoyalangan download() orqali
 * (RBAC tekshiruvi bilan) olib beriladi. Bitta hujjatni bir nechta
 * akkreditatsiya indikatoriga (M:N) bog'lash/uzish mumkin.
 */
final class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => (string) $request->query('category', ''),
        ];

        if (Auth::role() === 'doctoral_student') {
    $student = DoctoralStudent::findByUser((int) Auth::id());

    if ($student === null) {
        return $this->redirect('/doktorant/dashboard');
    }

    $filters['student_id'] = (int) $student['id'];
}
        
        return $this->view('documents.index', [
            'user' => Auth::user(),
            'title' => 'Dalillar bazasi',
            'active' => 'documents',
            'documents' => Document::search($filters),
            'filters' => $filters,
            'categories' => Document::CATEGORIES,
            'canUpload' => Auth::can('documents.upload'),
        ]);
    }

    public function store(Request $request): Response
    {
        if (!Auth::can('documents.upload')) {
            return $this->forbidden();
        }
        $input = $request->all();
        $validator = Validator::make($input, [
            'title' => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', array_keys(Document::CATEGORIES)),
        ]);
        if ($validator->fails()) {
            return $this->back($request, $validator->firstError() ?? 'Kiritishda xatolik.', '/documents');
        }

        $file = $request->file('file');
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->back($request, 'Fayl tanlanmadi.', '/documents');
        }
        try {
            $stored = FileStorage::store($file);
        } catch (\RuntimeException $ex) {
            return $this->back($request, $ex->getMessage(), '/documents');
        }

          $studentId = null;

if (Auth::role() === 'doctoral_student') {
    $student = DoctoralStudent::findByUser((int) Auth::id());

    if ($student === null) {
        return $this->redirect('/doktorant/dashboard');
    }

    $studentId = (int) $student['id'];
}

$id = DB::insert('documents', [
    'title' => (string) $input['title'],
    'category' => (string) $input['category'],
    'file_path' => $stored['path'],
    'original_name' => $stored['original_name'],
    'mime_type' => $stored['mime'],
    'file_size' => $stored['size'],
    'doc_type' => 'dalil',
    'uploaded_by' => Auth::id(),
    'student_id' => $studentId,
    'scientific_result_id' => null,
    'created_at' => date('Y-m-d H:i:s'),
]);
        AuditLogger::log('upload', 'documents', $id, null, ['category' => (string) $input['category']]);

        Session::flash('success', 'Dalil hujjati yuklandi.');
        return $this->redirect('/documents/' . $id);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $doc = Document::findWithRelations($id);
        if ($doc === null) {
            return $this->notFound();
        }
        if (Auth::role() === 'doctoral_student') {
    $student = DoctoralStudent::findByUser((int) Auth::id());

    if (
        $student === null ||
        (int) ($doc['student_id'] ?? 0) !== (int) $student['id']
    ) {
        return Response::html(\App\Core\View::render('errors.403'), 403);
    }
}
        // Bog'lash uchun mavjud indikatorlar (allaqachon bog'langanlar bundan tashqari).
        $allIndicators = DB::select(
            'SELECT id, code, name FROM accreditation_indicators ORDER BY code'
        );
        return $this->view('documents.show', [
            'user' => Auth::user(),
            'title' => $doc['title'],
            'active' => 'documents',
            'document' => $doc,
            'categories' => Document::CATEGORIES,
            'linkedIndicators' => Document::linkedIndicators($id),
            'allIndicators' => $allIndicators,
            'canLink' => Auth::can('documents.edit') || Auth::can('accreditation.edit'),
        ]);
    }

    /**
     * Himoyalangan yuklab olish — RBAC tekshiruvi shu yerda amalga oshiriladi.
     * Faqat documents.view ruxsatiga ega rollar faylni ola oladi.
     */
    public function download(Request $request): Response
    {
        if (!Auth::check() || !Auth::can('documents.view')) {
            return $this->forbidden();
        }
        $id = (int) $request->param('id');
        $doc = Document::find($id);
        
        if ($doc === null) {
            return $this->notFound();
        }

        if (Auth::role() === 'doctoral_student') {
    $student = DoctoralStudent::findByUser((int) Auth::id());

    if (
        $student === null ||
        (int) ($doc['student_id'] ?? 0) !== (int) $student['id']
    ) {
        return Response::html(\App\Core\View::render('errors.403'), 403);
    }
}
        
        $contents = FileStorage::read((string) $doc['file_path']);
        if ($contents === null) {
            return $this->notFound();
        }
        AuditLogger::log('view', 'documents', $id, null, ['action' => 'download']);

        $name = (string) ($doc['original_name'] ?? 'document');
        return (new Response($contents, 200, [
            'Content-Type' => (string) ($doc['mime_type'] ?? 'application/octet-stream'),
            'Content-Disposition' => 'attachment; filename="' . str_replace('"', '', $name) . '"',
            'Content-Length' => (string) strlen($contents),
        ]));
    }

    /**
     * Hujjatni indikatorga bog'laydi (M:N). Bog'langach indikator RAG
     * holatini qayta hisoblaymiz (dalil kelgani uchun grey'dan chiqishi
     * mumkin).
     */
    public function link(Request $request): Response
    {
        if (!Auth::can('documents.edit') && !Auth::can('accreditation.edit')) {
            return $this->forbidden();
        }
        $id = (int) $request->param('id');
        $doc = Document::find($id);
        if ($doc === null) {
            return $this->notFound();
        }
        $indicatorId = (int) $request->input('indicator_id', 0);
        if ($indicatorId <= 0) {
            return $this->back($request, 'Indikator tanlanmadi.', '/documents/' . $id);
        }
        $note = trim((string) $request->input('note', ''));
        $linked = Document::linkToIndicator($id, $indicatorId, Auth::id(), $note !== '' ? $note : null);
        if ($linked) {
            ScoringEngine::refreshIndicator($indicatorId);
            AuditLogger::log('link', 'indicator_evidence', $indicatorId, null, ['document_id' => $id]);
            Session::flash('success', 'Hujjat indikatorga bog\'landi.');
        } else {
            Session::flash('error', 'Bu hujjat allaqachon shu indikatorga bog\'langan.');
        }
        return $this->redirect('/documents/' . $id);
    }

    /**
     * Hujjatni indikatordan uzadi (M:N). Uzilgach indikator RAG holatini
     * qayta hisoblaymiz (dalil qolmasa grey bo'ladi).
     */
    public function unlink(Request $request): Response
    {
        if (!Auth::can('documents.edit') && !Auth::can('accreditation.edit')) {
            return $this->forbidden();
        }
        $id = (int) $request->param('id');
        $indicatorId = (int) $request->input('indicator_id', 0);
        if (Document::unlinkFromIndicator($id, $indicatorId)) {
            ScoringEngine::refreshIndicator($indicatorId);
            AuditLogger::log('update', 'indicator_evidence', $indicatorId, ['document_id' => $id], ['unlinked' => true]);
            Session::flash('success', 'Bog\'lanish uzildi.');
        }
        return $this->redirect('/documents/' . $id);
    }

    // -----------------------------------------------------------------

    private function back(Request $request, string $error, string $fallback): Response
    {
        Session::flash('error', $error);
        return $this->redirect($request->header('Referer') ?? $fallback);
    }

    private function notFound(): Response
    {
        return Response::html(View::render('errors.404'), 404);
    }

    private function forbidden(): Response
    {
        return Response::html(View::render('errors.403'), 403);
    }
}
