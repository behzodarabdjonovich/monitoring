<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\ScoringEngine;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Accreditation;
use App\Models\Document;
use App\Models\Indicator;

/**
 * MAXSUS DAVLAT AKKREDITATSIYASI moduli (item 9-10) — tizimning ENG ASOSIY
 * moduli.
 *
 * Iyerarxiya: Akkreditatsiya -> Mezon -> Indikator -> Talab -> Dalil -> Baho
 * -> Kamchilik -> Chora-tadbir. Mezon/indikatorlar to'liq sozlanadi (data-
 * driven); seed qilinganlar NAMUNA (is_placeholder=1) — rasmiy qiymatlar bilan
 * almashtiriladi (uydirmadan qochish tamoyili).
 */
final class AccreditationController extends Controller
{
    // ---------------------------------------------------------------
    // Akkreditatsiya CRUD.
    // ---------------------------------------------------------------

    public function index(Request $request): Response
    {
        return $this->view('accreditations.index', [
            'user' => Auth::user(),
            'title' => 'Akkreditatsiya',
            'active' => 'accreditations',
            'accreditations' => Accreditation::allWithReadiness(),
            'canCreate' => Auth::can('accreditation.create'),
            'canConfigure' => Auth::can('accreditation.configure'),
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $acc = Accreditation::find($id);
        if ($acc === null) {
            return $this->notFound();
        }
        $assessment = ScoringEngine::assessAccreditation($id);
        return $this->view('accreditations.show', [
            'user' => Auth::user(),
            'title' => $acc['title'],
            'active' => 'accreditations',
            'accreditation' => $acc,
            'assessment' => $assessment,
            'criteria' => Accreditation::criteria($id),
            'specialties' => Accreditation::specialties($id),
            'canEdit' => Auth::can('accreditation.edit'),
            'canConfigure' => Auth::can('accreditation.configure'),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->accForm(null);
    }

    public function edit(Request $request): Response
    {
        $acc = Accreditation::find((int) $request->param('id'));
        if ($acc === null) {
            return $this->notFound();
        }
        return $this->accForm($acc);
    }

    public function store(Request $request): Response
    {
        $data = $this->validatedAcc($request);
        if ($data instanceof Response) {
            return $data;
        }
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $id = DB::insert('accreditations', $data);
        AuditLogger::log('create', 'accreditations', $id, null, $data);
        Session::flash('success', 'Akkreditatsiya sikli yaratildi.');
        return $this->redirect('/accreditations/' . $id);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $acc = Accreditation::find($id);
        if ($acc === null) {
            return $this->notFound();
        }
        $data = $this->validatedAcc($request);
        if ($data instanceof Response) {
            return $data;
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->updateRow('accreditations', $id, $data);
        AuditLogger::log('update', 'accreditations', $id, $acc, $data);
        Session::flash('success', 'Akkreditatsiya yangilandi.');
        return $this->redirect('/accreditations/' . $id);
    }

    // ---------------------------------------------------------------
    // Mezon (Criteria) CRUD.
    // ---------------------------------------------------------------

    public function storeCriterion(Request $request): Response
    {
        if (!Auth::can('accreditation.edit')) {
            return $this->forbidden();
        }
        $accId = (int) $request->param('id');
        $acc = Accreditation::find($accId);
        if ($acc === null) {
            return $this->notFound();
        }
        $input = $request->all();
        $v = Validator::make($input, ['name' => 'required|string|max:255']);
        if ($v->fails()) {
            return $this->back($request, $v->firstError() ?? 'Kiritishda xatolik.', '/accreditations/' . $accId);
        }
        $data = [
            'accreditation_id' => $accId,
            'code' => $this->str($input['code'] ?? null),
            'name' => (string) $input['name'],
            'weight' => $this->weight($input['weight'] ?? null),
            'display_order' => (int) ($input['display_order'] ?? 0),
            'is_placeholder' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $id = DB::insert('accreditation_criteria', $data);
        ScoringEngine::refreshAccreditation($accId);
        AuditLogger::log('create', 'accreditation_criteria', $id, null, $data);
        Session::flash('success', 'Mezon qo\'shildi.');
        return $this->redirect('/accreditations/' . $accId);
    }

    public function updateCriterion(Request $request): Response
    {
        if (!Auth::can('accreditation.edit')) {
            return $this->forbidden();
        }
        $critId = (int) $request->param('id');
        $crit = Accreditation::findCriterion($critId);
        if ($crit === null) {
            return $this->notFound();
        }
        $input = $request->all();
        $data = [
            'code' => $this->str($input['code'] ?? null),
            'name' => trim((string) ($input['name'] ?? $crit['name'])) ?: $crit['name'],
            'weight' => $this->weight($input['weight'] ?? $crit['weight']),
            'display_order' => (int) ($input['display_order'] ?? $crit['display_order']),
        ];
        $this->updateRow('accreditation_criteria', $critId, $data);
        ScoringEngine::refreshAccreditation((int) $crit['accreditation_id']);
        AuditLogger::log('update', 'accreditation_criteria', $critId, $crit, $data);
        Session::flash('success', 'Mezon yangilandi.');
        return $this->redirect('/accreditations/' . (int) $crit['accreditation_id']);
    }

    // ---------------------------------------------------------------
    // Indikator (Indicator) — karta ko'rinishi + CRUD + baho.
    // ---------------------------------------------------------------

    public function criterion(Request $request): Response
    {
        $critId = (int) $request->param('id');
        $crit = Accreditation::findCriterion($critId);
        if ($crit === null) {
            return $this->notFound();
        }
        return $this->view('accreditations.criterion', [
            'user' => Auth::user(),
            'title' => $crit['name'],
            'active' => 'accreditations',
            'criterion' => $crit,
            'indicators' => Indicator::forCriterion($critId),
            'ragLabels' => ScoringEngine::ragStateLabels(),
            'canEdit' => Auth::can('accreditation.edit'),
        ]);
    }

    public function indicator(Request $request): Response
    {
        $id = (int) $request->param('id');
        $ind = Indicator::findWithContext($id);
        if ($ind === null) {
            return $this->notFound();
        }
        return $this->view('accreditations.indicator', [
            'user' => Auth::user(),
            'title' => $ind['name'] ?: $ind['code'],
            'active' => 'accreditations',
            'indicator' => $ind,
            'evidence' => Document::forIndicator($id),
            'deficiencies' => Indicator::deficiencies($id),
            'ragLabels' => ScoringEngine::ragStateLabels(),
            'roles' => DB::select('SELECT id, title_uz FROM roles ORDER BY title_uz'),
            'canEdit' => Auth::can('accreditation.edit'),
            'canAssess' => Auth::can('accreditation.approve'),
        ]);
    }

    public function storeIndicator(Request $request): Response
    {
        if (!Auth::can('accreditation.edit')) {
            return $this->forbidden();
        }
        $critId = (int) $request->param('id');
        $crit = Accreditation::findCriterion($critId);
        if ($crit === null) {
            return $this->notFound();
        }
        $input = $request->all();
        $v = Validator::make($input, ['name' => 'required|string|max:255']);
        if ($v->fails()) {
            return $this->back($request, $v->firstError() ?? 'Kiritishda xatolik.', '/criteria/' . $critId);
        }
        $data = array_merge($this->indicatorFields($input), [
            'criteria_id' => $critId,
            'rag_status' => 'grey',
            'score' => null,
            'is_placeholder' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $id = DB::insert('accreditation_indicators', $data);
        ScoringEngine::refreshAccreditation((int) $crit['accreditation_id']);
        AuditLogger::log('create', 'accreditation_indicators', $id, null, $data);
        Session::flash('success', 'Indikator qo\'shildi.');
        return $this->redirect('/indicators/' . $id);
    }

    public function updateIndicator(Request $request): Response
    {
        if (!Auth::can('accreditation.edit')) {
            return $this->forbidden();
        }
        $id = (int) $request->param('id');
        $ind = Indicator::findWithContext($id);
        if ($ind === null) {
            return $this->notFound();
        }
        $data = $this->indicatorFields($request->all(), $ind);
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->updateRow('accreditation_indicators', $id, $data);
        ScoringEngine::refreshAccreditation((int) $ind['accreditation_id']);
        AuditLogger::log('update', 'accreditation_indicators', $id, $ind, $data);
        Session::flash('success', 'Indikator yangilandi.');
        return $this->redirect('/indicators/' . $id);
    }

    /**
     * Indikatorga RAG baho qo'yish (item 10). RBAC: accreditation.approve
     * (Ekspert / Ta'lim sifati / Ilmiy prorektor va h.k.). Baho o'zgargach
     * indikator RAG + akkreditatsiya tayyorlik indeksi qayta hisoblanadi.
     */
    public function assess(Request $request): Response
    {
        if (!Auth::can('accreditation.approve')) {
            return $this->forbidden();
        }
        $id = (int) $request->param('id');
        $ind = Indicator::findWithContext($id);
        if ($ind === null) {
            return $this->notFound();
        }
        $rag = (string) $request->input('rag_status', '');
        if (!in_array($rag, ScoringEngine::RAG_STATES, true)) {
            return $this->back($request, 'Yaroqsiz baho holati.', '/indicators/' . $id);
        }
        $expertNote = trim((string) $request->input('self_assessment_expert', ''));

        Indicator::setAssessment($id, $rag);
        if ($expertNote !== '') {
            DB::run(
                'UPDATE accreditation_indicators SET self_assessment = :sa, updated_at = :u WHERE id = :id',
                ['sa' => $expertNote, 'u' => date('Y-m-d H:i:s'), 'id' => $id]
            );
        }
        $newRag = (string) DB::scalar('SELECT rag_status FROM accreditation_indicators WHERE id = :id', ['id' => $id]);
        ScoringEngine::refreshAccreditation((int) $ind['accreditation_id']);
        AuditLogger::log('update', 'accreditation_indicators', $id, ['rag_status' => $ind['rag_status']], ['rag_status' => $newRag, 'assessment' => $rag]);

        if ($rag !== 'grey' && $newRag === 'grey') {
            Session::flash('error', 'Baho saqlandi, ammo indikatorda dalil (evidence) yo\'q — RAG kulrang bo\'lib qoladi. Avval dalil biriktiring.');
        } else {
            Session::flash('success', 'Indikator bahosi saqlandi.');
        }
        return $this->redirect('/indicators/' . $id);
    }

    /**
     * is_placeholder bayrog'ini tozalaydi (rasmiy qiymatlar tasdiqlangach).
     * Akkreditatsiya + uning barcha mezon/indikatorlari tozalanadi.
     */
    public function clearPlaceholder(Request $request): Response
    {
        if (!Auth::can('accreditation.configure')) {
            return $this->forbidden();
        }
        $id = (int) $request->param('id');
        $acc = Accreditation::find($id);
        if ($acc === null) {
            return $this->notFound();
        }
        DB::run('UPDATE accreditations SET is_placeholder = 0, updated_at = :u WHERE id = :id', ['u' => date('Y-m-d H:i:s'), 'id' => $id]);
        DB::run('UPDATE accreditation_criteria SET is_placeholder = 0 WHERE accreditation_id = :aid', ['aid' => $id]);
        DB::run(
            'UPDATE accreditation_indicators SET is_placeholder = 0
             WHERE criteria_id IN (SELECT id FROM accreditation_criteria WHERE accreditation_id = :aid)',
            ['aid' => $id]
        );
        AuditLogger::log('update', 'accreditations', $id, ['is_placeholder' => 1], ['is_placeholder' => 0]);
        Session::flash('success', 'NAMUNA (placeholder) bayrog\'i tozalandi — ma\'lumotlar rasmiy sifatida belgilandi.');
        return $this->redirect('/accreditations/' . $id);
    }

    // ---------------------------------------------------------------
    // Yordamchilar.
    // ---------------------------------------------------------------

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed>|null $existing
     * @return array<string,mixed>
     */
    private function indicatorFields(array $input, ?array $existing = null): array
    {
        $get = fn (string $k) => $existing !== null ? ($existing[$k] ?? null) : null;
        return [
            'code' => $this->str($input['code'] ?? $get('code')),
            'name' => trim((string) ($input['name'] ?? $get('name') ?? '')),
            'requirement' => $this->str($input['requirement'] ?? $get('requirement')),
            'description' => $this->str($input['description'] ?? $get('description')),
            'target_value' => $this->str($input['target_value'] ?? $get('target_value')),
            'actual_value' => $this->str($input['actual_value'] ?? $get('actual_value')),
            'weight' => $this->weight($input['weight'] ?? $get('weight')),
            'responsible_role_id' => $this->intOrNull($input['responsible_role_id'] ?? $get('responsible_role_id')),
            'responsible_dept' => $this->str($input['responsible_dept'] ?? $get('responsible_dept')),
            'responsible_person' => $this->str($input['responsible_person'] ?? $get('responsible_person')),
        ];
    }

    /**
     * @return array<string,mixed>|Response
     */
    private function validatedAcc(Request $request): array|Response
    {
        $input = $request->all();
        $v = Validator::make($input, ['title' => 'required|string|max:191']);
        if ($v->fails()) {
            Session::flash('error', $v->firstError() ?? 'Kiritishda xatolik.');
            return $this->redirect($request->header('Referer') ?? '/accreditations/create');
        }
        return [
            'title' => (string) $input['title'],
            'cycle_year' => $this->str($input['cycle_year'] ?? null),
            'scope' => $this->str($input['scope'] ?? null),
            'status' => in_array($input['status'] ?? '', ['planning', 'in_progress', 'submitted', 'completed'], true)
                ? (string) $input['status'] : 'planning',
        ];
    }

    private function accForm(?array $acc): Response
    {
        return $this->view('accreditations.form', [
            'user' => Auth::user(),
            'title' => $acc === null ? 'Yangi akkreditatsiya' : 'Akkreditatsiyani tahrirlash',
            'active' => 'accreditations',
            'accreditation' => $acc,
        ]);
    }

    private function updateRow(string $table, int $id, array $data): void
    {
        $sets = implode(', ', array_map(static fn ($k) => "$k = :$k", array_keys($data)));
        DB::run("UPDATE $table SET $sets WHERE id = :id", array_merge($data, ['id' => $id]));
    }

    private function str(mixed $v): ?string
    {
        return ($v === null || $v === '') ? null : (string) $v;
    }

    private function intOrNull(mixed $v): ?int
    {
        return ($v === null || $v === '') ? null : (int) $v;
    }

    private function weight(mixed $v): float
    {
        $w = ($v === null || $v === '') ? 1.0 : (float) $v;
        return $w > 0 ? $w : 1.0;
    }

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
