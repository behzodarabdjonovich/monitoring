<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Attestation;.
use App\Models\DoctoralStudent;

/**
 * Attestatsiya moduli (item 4/21) — doktorantga bog'langan CRUD.
 */
final class AttestationController extends Controller
{
    public function index(Request $request): Response
    {
       $attestations = Attestation::all();

if (Auth::role() === 'doctoral_student') {
    $student = DoctoralStudent::findByUser((int) Auth::id());

    if ($student === null) {
        return $this->redirect('/doktorant/dashboard');
    }

    $attestations = array_values(array_filter(
        $attestations,
        static fn (array $attestation): bool =>
            (int) ($attestation['student_id'] ?? 0) === (int) $student['id']
    ));
}
        
        return $this->view('attestations.index', [
            'user' => Auth::user(),
            'title' => 'Attestatsiya',
            'active' => 'attestations',
          'attestations' => $attestations,
            'results' => Attestation::RESULTS,
            'students' => DB::select('SELECT id, full_name FROM doctoral_students ORDER BY full_name'),
            'canCreate' => Auth::can('attestations.create'),
            'canApprove' => Auth::can('attestations.approve'),
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $attestation = Attestation::findWithRelations($id);
        if ($attestation === null) {
            return Response::html(\App\Core\View::render('errors.404'), 404);
        }
        return $this->view('attestations.show', [
            'user' => Auth::user(),
            'title' => 'Attestatsiya',
            'active' => 'attestations',
            'attestation' => $attestation,
            'results' => Attestation::RESULTS,
            'canApprove' => Auth::can('attestations.approve'),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }
        $data['created_by'] = Auth::id();
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = DB::insert('attestations', $data);
        AuditLogger::log('create', 'attestations', $id, null, $data);
        Session::flash('success', 'Attestatsiya qo\'shildi.');
        return $this->redirect('/attestations');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $attestation = Attestation::find($id);
        if ($attestation === null) {
            return Response::html(\App\Core\View::render('errors.404'), 404);
        }
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }
        $sets = implode(', ', array_map(static fn ($k) => "$k = :$k", array_keys($data)));
        DB::run("UPDATE attestations SET $sets WHERE id = :id", array_merge($data, ['id' => $id]));
        AuditLogger::log('update', 'attestations', $id, $attestation, $data);
        Session::flash('success', 'Attestatsiya yangilandi.');
        return $this->redirect('/attestations/' . $id);
    }

    public function approve(Request $request): Response
    {
        $id = (int) $request->param('id');
        $attestation = Attestation::find($id);
        if ($attestation === null) {
            return Response::html(\App\Core\View::render('errors.404'), 404);
        }
        DB::run('UPDATE attestations SET result = :r WHERE id = :id', ['r' => 'ijobiy', 'id' => $id]);
        AuditLogger::log('approve', 'attestations', $id, ['result' => $attestation['result']], ['result' => 'ijobiy']);
        Session::flash('success', 'Attestatsiya tasdiqlandi (ijobiy).');
        return $this->redirect('/attestations/' . $id);
    }

    /**
     * @return array<string,mixed>|Response
     */
    private function validated(Request $request): array|Response
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'student_id' => 'required|integer',
            'period' => 'required|string|max:64',
            'result' => 'in:' . implode(',', array_keys(Attestation::RESULTS)),
        ]);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError() ?? 'Kiritishda xatolik.');
            return $this->redirect($request->header('Referer') ?? '/attestations');
        }
        $strOrNull = static fn ($v) => ($v === null || $v === '') ? null : (string) $v;
        return [
            'student_id' => (int) $input['student_id'],
            'period' => (string) $input['period'],
            'attestation_date' => $strOrNull($input['attestation_date'] ?? null),
            'result' => $strOrNull($input['result'] ?? null) ?? 'shartli',
            'commission_notes' => $strOrNull($input['commission_notes'] ?? null),
        ];
    }
}
