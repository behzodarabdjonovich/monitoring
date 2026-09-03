<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Deficiency;

/**
 * Kamchiliklar (Deficiencies) va chora-tadbirlar (Action Plan) moduli (item 12).
 *
 * To'liq zanjir: Muammo -> Sabab -> Chora-tadbir -> Mas'ul -> Boshlanish
 * sanasi -> Yakuniy muddat -> Dalil -> Natija. Chora-tadbir muddat holatiga
 * qarab ranglanadi (muddati yaqin => sariq, muddati o'tgan => qizil). Barcha
 * create/update/close amallari AuditLog yozadi.
 */
final class DeficiencyController extends Controller
{
    // ---------------------------------------------------------------
    // Kamchiliklar (Deficiencies) ro'yxati va kartasi.
    // ---------------------------------------------------------------

    public function index(Request $request): Response
    {
        $filters = [
            'status' => (string) $request->query('status', ''),
            'source' => (string) $request->query('source', ''),
        ];
        return $this->view('deficiencies.index', [
            'user' => Auth::user(),
            'title' => 'Kamchiliklar',
            'active' => 'deficiencies',
            'deficiencies' => Deficiency::all($filters),
            'filters' => $filters,
            'statusLabels' => Deficiency::STATUS_LABELS,
            'severityLabels' => Deficiency::SEVERITY_LABELS,
            'canCreate' => Auth::can('deficiencies.create'),
            'canEdit' => Auth::can('deficiencies.edit'),
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $def = Deficiency::findWithContext($id);
        if ($def === null) {
            return $this->notFound();
        }
        return $this->view('deficiencies.show', [
            'user' => Auth::user(),
            'title' => $def['title'],
            'active' => 'deficiencies',
            'deficiency' => $def,
            'actionPlans' => Deficiency::actionPlans($id),
            'statusLabels' => Deficiency::STATUS_LABELS,
            'severityLabels' => Deficiency::SEVERITY_LABELS,
            'users' => DB::select('SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name'),
            'documents' => DB::select('SELECT id, title FROM documents ORDER BY id DESC'),
            'canEdit' => Auth::can('deficiencies.edit'),
            'canPlan' => Auth::can('action_plans.create') || Auth::can('action_plans.edit'),
        ]);
    }

    public function store(Request $request): Response
    {
        if (!Auth::can('deficiencies.create')) {
            return $this->forbidden();
        }
        $input = $request->all();
        $v = Validator::make($input, ['title' => 'required|string|max:191']);
        if ($v->fails()) {
            return $this->back($request, $v->firstError() ?? 'Kiritishda xatolik.', '/deficiencies');
        }
        $now = date('Y-m-d H:i:s');
        $data = [
            'indicator_id' => $this->intOrNull($input['indicator_id'] ?? null),
            'internal_audit_id' => $this->intOrNull($input['internal_audit_id'] ?? null),
            'title' => (string) $input['title'],
            'description' => $this->str($input['description'] ?? null),
            'cause' => $this->str($input['cause'] ?? null),
            'result' => $this->str($input['result'] ?? null),
            'severity' => in_array($input['severity'] ?? '', ['low', 'medium', 'high'], true) ? (string) $input['severity'] : 'medium',
            'status' => 'open',
            'identified_by' => Auth::id(),
            'identified_at' => date('Y-m-d'),
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $id = DB::insert('deficiencies', $data);
        AuditLogger::log('create', 'deficiencies', $id, null, $data);
        Session::flash('success', 'Kamchilik qayd etildi.');
        return $this->redirect('/deficiencies/' . $id);
    }

    public function update(Request $request): Response
    {
        if (!Auth::can('deficiencies.edit')) {
            return $this->forbidden();
        }
        $id = (int) $request->param('id');
        $def = Deficiency::find($id);
        if ($def === null) {
            return $this->notFound();
        }
        $input = $request->all();
        $data = [
            'title' => trim((string) ($input['title'] ?? $def['title'])) ?: $def['title'],
            'description' => $this->str($input['description'] ?? $def['description']),
            'cause' => $this->str($input['cause'] ?? $def['cause']),
            'result' => $this->str($input['result'] ?? $def['result']),
            'severity' => in_array($input['severity'] ?? '', ['low', 'medium', 'high'], true) ? (string) $input['severity'] : $def['severity'],
            'status' => in_array($input['status'] ?? '', ['open', 'in_progress', 'resolved'], true) ? (string) $input['status'] : $def['status'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->updateRow('deficiencies', $id, $data);
        AuditLogger::log('update', 'deficiencies', $id, $def, $data);
        Session::flash('success', 'Kamchilik yangilandi.');
        return $this->redirect('/deficiencies/' . $id);
    }

    /**
     * Kamchilikni yopadi (Bartaraf etilgan). Alohida AuditLog (close/update).
     */
    public function close(Request $request): Response
    {
        if (!Auth::can('deficiencies.edit') && !Auth::can('deficiencies.approve')) {
            return $this->forbidden();
        }
        $id = (int) $request->param('id');
        $def = Deficiency::find($id);
        if ($def === null) {
            return $this->notFound();
        }
        $result = $this->str($request->input('result', $def['result']));
        $data = ['status' => 'resolved', 'result' => $result, 'updated_at' => date('Y-m-d H:i:s')];
        $this->updateRow('deficiencies', $id, $data);
        AuditLogger::log('close', 'deficiencies', $id, $def, $data);
        Session::flash('success', 'Kamchilik bartaraf etilgan deb belgilandi.');
        return $this->redirect('/deficiencies/' . $id);
    }

    // ---------------------------------------------------------------
    // Chora-tadbirlar (Action Plan).
    // ---------------------------------------------------------------

    /**
     * Barcha chora-tadbirlar ro'yxati (muddat holatlari bilan) — Action Plan
     * bo'limi. Muddati yaqin (sariq) va o'tgan (qizil) elementlar ajralib turadi.
     */
    public function plans(Request $request): Response
    {
        $rows = DB::select(
            'SELECT ap.*, u.full_name AS responsible_name, doc.title AS document_title,
                    d.title AS deficiency_title, d.id AS deficiency_id
             FROM action_plans ap
             LEFT JOIN users u ON u.id = ap.responsible_user_id
             LEFT JOIN documents doc ON doc.id = ap.document_id
             INNER JOIN deficiencies d ON d.id = ap.deficiency_id
             ORDER BY ap.due_date IS NULL, ap.due_date'
        );
        foreach ($rows as &$r) {
            $r['due_state'] = Deficiency::dueState($r);
        }
        unset($r);
        return $this->view('deficiencies.plans', [
            'user' => Auth::user(),
            'title' => 'Action Plan',
            'active' => 'action-plans',
            'plans' => $rows,
            'canEdit' => Auth::can('action_plans.edit'),
        ]);
    }

    public function storePlan(Request $request): Response
    {
        if (!Auth::can('action_plans.create') && !Auth::can('action_plans.edit')) {
            return $this->forbidden();
        }
        $defId = (int) $request->param('id');
        $def = Deficiency::find($defId);
        if ($def === null) {
            return $this->notFound();
        }
        $input = $request->all();
        $v = Validator::make($input, ['title' => 'required|string|max:191']);
        if ($v->fails()) {
            return $this->back($request, $v->firstError() ?? 'Kiritishda xatolik.', '/deficiencies/' . $defId);
        }
        $now = date('Y-m-d H:i:s');
        $data = [
            'deficiency_id' => $defId,
            'title' => (string) $input['title'],
            'description' => $this->str($input['description'] ?? null),
            'responsible_user_id' => $this->intOrNull($input['responsible_user_id'] ?? null),
            'start_date' => $this->str($input['start_date'] ?? null),
            'due_date' => $this->str($input['due_date'] ?? null),
            'document_id' => $this->intOrNull($input['document_id'] ?? null),
            'result' => $this->str($input['result'] ?? null),
            'status' => in_array($input['status'] ?? '', ['planned', 'in_progress', 'done'], true) ? (string) $input['status'] : 'planned',
            'completed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $id = DB::insert('action_plans', $data);
        AuditLogger::log('create', 'action_plans', $id, null, $data);
        Session::flash('success', 'Chora-tadbir qo\'shildi.');
        return $this->redirect('/deficiencies/' . $defId);
    }

    public function updatePlan(Request $request): Response
    {
        if (!Auth::can('action_plans.edit')) {
            return $this->forbidden();
        }
        $id = (int) $request->param('id');
        $plan = DB::selectOne('SELECT * FROM action_plans WHERE id = :id', ['id' => $id]);
        if ($plan === null) {
            return $this->notFound();
        }
        $input = $request->all();
        $status = in_array($input['status'] ?? '', ['planned', 'in_progress', 'done'], true) ? (string) $input['status'] : $plan['status'];
        $wasDone = in_array($plan['status'], Deficiency::DONE_STATUSES, true);
        $isDone = $status === 'done';
        $data = [
            'title' => trim((string) ($input['title'] ?? $plan['title'])) ?: $plan['title'],
            'description' => $this->str($input['description'] ?? $plan['description']),
            'responsible_user_id' => $this->intOrNull($input['responsible_user_id'] ?? $plan['responsible_user_id']),
            'start_date' => $this->str($input['start_date'] ?? $plan['start_date']),
            'due_date' => $this->str($input['due_date'] ?? $plan['due_date']),
            'document_id' => $this->intOrNull($input['document_id'] ?? $plan['document_id']),
            'result' => $this->str($input['result'] ?? $plan['result']),
            'status' => $status,
            'completed_at' => $isDone ? ($wasDone ? $plan['completed_at'] : date('Y-m-d H:i:s')) : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->updateRow('action_plans', $id, $data);
        // Chora-tadbir yopilishi (done) alohida "close" audit sifatida.
        $action = (!$wasDone && $isDone) ? 'close' : 'update';
        AuditLogger::log($action, 'action_plans', $id, $plan, $data);
        Session::flash('success', 'Chora-tadbir yangilandi.');
        return $this->redirect('/deficiencies/' . (int) $plan['deficiency_id']);
    }

    // ---------------------------------------------------------------
    // Yordamchilar.
    // ---------------------------------------------------------------

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
