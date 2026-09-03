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
}
