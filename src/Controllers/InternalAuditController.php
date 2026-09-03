<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\InternalAudit;

/**
 * Ichki akkreditatsiya auditi moduli (item 13).
 *
 * Har bir ixtisoslik bo'yicha "Ichki akkreditatsiya auditi" o'tkazadi va audit
 * yakunida kuchli tomonlar / kamchiliklar / bajarilmagan indikatorlar /
 * yetishmayotgan dalillar / xavf darajasi / tavsiyalar / chora-tadbirlar
 * rejasi / tayyorlik foizini avtomatik shakllantiradi (report-style page).
 */
final class InternalAuditController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('audits.index', [
            'user' => Auth::user(),
            'title' => 'Ichki audit',
            'active' => 'audits',
            'audits' => InternalAudit::all(),
            'specialties' => DB::select('SELECT id, code, name FROM specialties ORDER BY code, name'),
            'riskLabels' => InternalAudit::RISK_LABELS,
            'canAudit' => Auth::can('internal_audits.audit'),
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $audit = InternalAudit::findWithContext($id);
        if ($audit === null) {
            return $this->notFound();
        }
        // Audit natijasida shakllangan kamchiliklar (Deficiencies moduliga oqadi).
        $deficiencies = DB::select(
            'SELECT id, title, severity, status FROM deficiencies WHERE internal_audit_id = :aid ORDER BY id',
            ['aid' => $id]
        );
        return $this->view('audits.show', [
            'user' => Auth::user(),
            'title' => $audit['title'],
            'active' => 'audits',
            'audit' => $audit,
            'strengths' => InternalAudit::decode($audit['strengths'] ?? null),
            'weaknesses' => InternalAudit::decode($audit['weaknesses'] ?? null),
            'unmet' => InternalAudit::decode($audit['unmet_indicators'] ?? null),
            'missing' => InternalAudit::decode($audit['missing_evidence'] ?? null),
            'recommendations' => InternalAudit::decode($audit['recommendations'] ?? null),
            'deficiencies' => $deficiencies,
            'riskLabels' => InternalAudit::RISK_LABELS,
        ]);
    }

    /**
     * Ixtisoslik bo'yicha ichki auditni ishga tushiradi (item 13). RBAC:
     * internal_audits.audit. Natija saqlanadi, kamchiliklar Deficiencies
     * moduliga urug'lanadi va AuditLog yoziladi.
     */
    public function run(Request $request): Response
    {
        if (!Auth::can('internal_audits.audit')) {
            return $this->forbidden();
        }
        $specialtyId = (int) $request->input('specialty_id', 0);
        $spec = $specialtyId > 0
            ? DB::selectOne('SELECT * FROM specialties WHERE id = :id', ['id' => $specialtyId])
            : null;
        if ($spec === null) {
            Session::flash('error', 'Ixtisoslik tanlanmadi.');
            return $this->redirect('/audits');
        }

        $result = InternalAudit::run($specialtyId, Auth::id());
        AuditLogger::log('create', 'internal_audits', $result['audit_id'], null, [
            'specialty_id' => $specialtyId,
            'readiness_index' => $result['readiness'],
            'risk_level' => $result['risk'],
            'deficiencies_created' => count($result['deficiency_ids']),
        ]);
        Session::flash('success', 'Ichki akkreditatsiya auditi o\'tkazildi va hisobot shakllantirildi.');
        return $this->redirect('/audits/' . $result['audit_id']);
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
