<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

/**
 * Doktorantura dasturlari (item 8) — ixtisoslikka bog'liq PhD/DSc dasturlari.
 */
final class ProgramController extends Controller
{
    public function index(Request $request): Response
    {
        $programs = DB::select(
            'SELECT pr.*, sp.name AS specialty_name, sp.code AS specialty_code
             FROM doctoral_programs pr
             LEFT JOIN specialties sp ON sp.id = pr.specialty_id
             ORDER BY sp.name, pr.program_type'
        );
        return $this->view('programs.index', [
            'user' => Auth::user(),
            'title' => 'Doktorantura dasturlari',
            'active' => 'specialties',
            'programs' => $programs,
            'specialties' => DB::select('SELECT id, name FROM specialties ORDER BY name'),
        ]);
    }

    public function store(Request $request): Response
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required|string|max:191',
            'specialty_id' => 'required|integer',
            'program_type' => 'required|in:PhD,DSc',
        ]);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError() ?? 'Kiritishda xatolik.');
            return $this->redirect('/programs');
        }
        $id = DB::insert('doctoral_programs', [
            'specialty_id' => (int) $input['specialty_id'],
            'name' => (string) $input['name'],
            'program_type' => (string) $input['program_type'],
            'duration_years' => ($input['duration_years'] ?? '') === '' ? null : (int) $input['duration_years'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        AuditLogger::log('create', 'doctoral_programs', $id, null, ['name' => $input['name']]);
        Session::flash('success', 'Dastur yaratildi.');
        return $this->redirect('/programs');
    }
public function edit(Request $request): Response
{
    $id = (int) $request->param('id');

    $program = DB::selectOne(
        'SELECT * FROM doctoral_programs WHERE id = :id',
        ['id' => $id]
    );

    if ($program === null) {
        return $this->notFound();
    }

    return $this->view('programs.edit', [
        'user' => Auth::user(),
        'title' => 'Dastur tahrirlash',
        'active' => 'specialties',
        'program' => $program,
        'specialties' => DB::select('SELECT id, name FROM specialties ORDER BY name'),
    ]);
public function update(Request $request): Response
{
    $id = (int) $request->param('id');

    $program = DB::selectOne(
        'SELECT * FROM doctoral_programs WHERE id = :id',
        ['id' => $id]
    );

    if ($program === null) {
        return $this->notFound();
    }

    $input = $request->all();

    $validator = Validator::make($input, [
        'name' => 'required|string|max:191',
        'specialty_id' => 'required|integer',
        'program_type' => 'required|in:PhD,DSc',
    ]);

    if ($validator->fails()) {
        Session::flash('error', $validator->firstError() ?? 'Kiritishda xatolik.');
        return $this->redirect('/programs/' . $id . '/edit');
    }

    DB::run(
        'UPDATE doctoral_programs
         SET specialty_id = :specialty_id,
             name = :name,
             program_type = :program_type,
             duration_years = :duration_years
         WHERE id = :id',
        [
            'specialty_id' => (int) $input['specialty_id'],
            'name' => (string) $input['name'],
            'program_type' => (string) $input['program_type'],
            'duration_years' => ($input['duration_years'] ?? '') === ''
                ? null
                : (int) $input['duration_years'],
            'id' => $id,
        ]
    );

    AuditLogger::log(
        'update',
        'doctoral_programs',
        $id,
        $program,
        ['name' => $input['name']]
    );

    Session::flash('success', 'Dastur muvaffaqiyatli tahrirlandi.');

    return $this->redirect('/programs');
}
}
}
