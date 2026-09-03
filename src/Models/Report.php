<?php

namespace App\Models;

use App\Core\DB;
use App\Core\ScoringEngine;

/**
 * Hisobotlar (item 14).
 *
 * Har bir hisobot turi uchun sarlavhalar (headers) va satrlar (rows)
 * to'plamini beruvchi metod. Bir xil ma'lumot to'plami HTML print-view,
 * Excel (.xlsx) va PDF eksport yo'llarida qayta ishlatiladi.
 *
 * Barcha so'rovlar PDO prepared-statement orqali (SQLi himoyasi).
 */
final class Report
{
    /**
     * Barcha hisobot turlari: kalit => [o'zbekcha nom, qisqa izoh].
     *
     * @return array<string,array{title:string,desc:string}>
     */
    public static function types(): array
    {
        return [
            'doktorant_monitoring' => ['title' => 'Doktorant monitoring hisoboti', 'desc' => 'Barcha doktorantlar, ilmiy rahbar, faoliyat foizi va holati kesimida.'],
            'doktorant_yillik' => ['title' => 'Doktorantning yillik hisoboti', 'desc' => 'O\'quv yillari bo\'yicha reja, vazifalar va natijalar.'],
            'individual_reja' => ['title' => 'Individual reja bajarilishi', 'desc' => 'Reja vazifalari va bajarilish holati (5 bosqichli oqim).'],
            'kafedra_kesimi' => ['title' => 'Kafedra kesimidagi hisobot', 'desc' => 'Kafedralar bo\'yicha doktorantlar, rahbarlar va natijalar.'],
            'ixtisoslik' => ['title' => 'Ixtisoslik bo\'yicha hisobot', 'desc' => 'Ixtisosliklar bo\'yicha doktorantlar va akkreditatsiyaga tayyorlik.'],
            'ilmiy_rahbar' => ['title' => 'Ilmiy rahbar bo\'yicha hisobot', 'desc' => 'Rahbarlar, doktorantlar soni va samaradorlik ko\'rsatkichi.'],
            'ilmiy_natijalar' => ['title' => 'Ilmiy natijalar hisoboti', 'desc' => 'Barcha ilmiy natija turlari kesimida.'],
            'maqolalar' => ['title' => 'Maqolalar hisoboti', 'desc' => 'Nashrlar (OAK/Scopus/WoS va boshqalar) kesimida.'],
            'attestatsiya' => ['title' => 'Attestatsiya hisoboti', 'desc' => 'Doktorantlar attestatsiyasi natijalari.'],
            'akkreditatsiya_indikatorlari' => ['title' => 'Akkreditatsiya indikatorlari hisoboti', 'desc' => 'Barcha indikatorlar, RAG holati va ball.'],
            'bajarilmagan_indikatorlar' => ['title' => 'Bajarilmagan indikatorlar', 'desc' => 'Talabga mos emas (qizil) indikatorlar.'],
            'yetishmayotgan_dalillar' => ['title' => 'Yetishmayotgan dalillar', 'desc' => 'Tasdiqlovchi dalili yo\'q (baholanmagan) indikatorlar.'],
            'kamchiliklar_action_plan' => ['title' => 'Kamchiliklar va Action Plan', 'desc' => 'Kamchiliklar va ular bo\'yicha chora-tadbirlar.'],
            'ichki_baholash' => ['title' => 'Ichki baholash hisoboti', 'desc' => 'O\'tkazilgan ichki akkreditatsiya auditlari.'],
            'akkreditatsiyaga_tayyorlik' => ['title' => 'Akkreditatsiyaga tayyorlik hisoboti', 'desc' => 'Mezonlar bo\'yicha tayyorlik indeksi va umumiy holat.'],
        ];
    }

    public static function exists(string $type): bool
    {
        return array_key_exists($type, self::types());
    }

    public static function title(string $type): string
    {
        return self::types()[$type]['title'] ?? $type;
    }

    /**
     * Hisobot ma'lumotini (headers + rows) qaytaradi.
     *
     * @return array{headers:array<int,string>,rows:array<int,array<int,scalar|null>>}
     */
    public static function build(string $type): array
    {
        return match ($type) {
            'doktorant_monitoring' => self::doktorantMonitoring(),
            'doktorant_yillik' => self::doktorantYillik(),
            'individual_reja' => self::individualReja(),
            'kafedra_kesimi' => self::kafedraKesimi(),
            'ixtisoslik' => self::ixtisoslik(),
            'ilmiy_rahbar' => self::ilmiyRahbar(),
            'ilmiy_natijalar' => self::ilmiyNatijalar(),
            'maqolalar' => self::maqolalar(),
            'attestatsiya' => self::attestatsiya(),
            'akkreditatsiya_indikatorlari' => self::akkreditatsiyaIndikatorlari(),
            'bajarilmagan_indikatorlar' => self::indikatorlarByRag('red'),
            'yetishmayotgan_dalillar' => self::yetishmayotganDalillar(),
            'kamchiliklar_action_plan' => self::kamchiliklarActionPlan(),
            'ichki_baholash' => self::ichkiBaholash(),
            'akkreditatsiyaga_tayyorlik' => self::akkreditatsiyagaTayyorlik(),
            default => ['headers' => [], 'rows' => []],
        };
    }

    private static function doktorantMonitoring(): array
    {
        $rows = DB::select(
            "SELECT s.full_name, s.student_type, sp.name AS specialty, d.name AS department,
                    su.full_name AS supervisor, s.status, s.dissertation_percent
             FROM doctoral_students s
             LEFT JOIN specialties sp ON sp.id = s.specialty_id
             LEFT JOIN departments d ON d.id = s.department_id
             LEFT JOIN supervisors su ON su.id = s.supervisor_id
             ORDER BY s.full_name"
        );
        $types = DoctoralStudent::TYPES;
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['full_name'],
                $types[$r['student_type']] ?? $r['student_type'],
                $r['specialty'] ?? '—',
                $r['department'] ?? '—',
                $r['supervisor'] ?? '—',
                $r['status'] ?? '—',
                ($r['dissertation_percent'] ?? 0) . '%',
            ];
        }
        return ['headers' => ['F.I.Sh.', 'Turi', 'Ixtisoslik', 'Kafedra', 'Ilmiy rahbar', 'Holat', 'Dissertatsiya %'], 'rows' => $out];
    }

    private static function doktorantYillik(): array
    {
        $rows = DB::select(
            "SELECT s.full_name, p.academic_year, p.status AS plan_status,
                    (SELECT COUNT(*) FROM plan_tasks t WHERE t.plan_id = p.id) AS task_count,
                    (SELECT COUNT(*) FROM plan_tasks t WHERE t.plan_id = p.id AND t.status IN ('completed','supervisor_approved','finalized')) AS done_count
             FROM individual_plans p
             INNER JOIN doctoral_students s ON s.id = p.student_id
             ORDER BY s.full_name, p.academic_year"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['full_name'],
                $r['academic_year'] ?? '—',
                $r['plan_status'] ?? '—',
                (int) $r['task_count'],
                (int) $r['done_count'],
            ];
        }
        return ['headers' => ['F.I.Sh.', 'O\'quv yili', 'Reja holati', 'Vazifalar', 'Bajarilgan'], 'rows' => $out];
    }

    private static function individualReja(): array
    {
        $rows = DB::select(
            "SELECT s.full_name, t.title, t.task_type, t.due_date, t.progress_percent, t.status
             FROM plan_tasks t
             INNER JOIN individual_plans p ON p.id = t.plan_id
             INNER JOIN doctoral_students s ON s.id = p.student_id
             ORDER BY s.full_name, t.due_date"
        );
        $labels = PlanTask::LABELS;
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['full_name'],
                $r['title'],
                $r['task_type'] ?? '—',
                $r['due_date'] ?? '—',
                ((int) ($r['progress_percent'] ?? 0)) . '%',
                $labels[$r['status']] ?? $r['status'],
            ];
        }
        return ['headers' => ['Doktorant', 'Vazifa', 'Turi', 'Muddat', 'Bajarilish', 'Holat'], 'rows' => $out];
    }

    private static function kafedraKesimi(): array
    {
        $rows = DB::select(
            "SELECT d.name AS department,
                    (SELECT COUNT(*) FROM doctoral_students s WHERE s.department_id = d.id) AS students,
                    (SELECT COUNT(*) FROM supervisors su WHERE su.department_id = d.id) AS supervisors,
                    (SELECT COUNT(*) FROM specialties sp WHERE sp.responsible_department_id = d.id) AS specialties
             FROM departments d ORDER BY d.name"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = [$r['department'], (int) $r['students'], (int) $r['supervisors'], (int) $r['specialties']];
        }
        return ['headers' => ['Kafedra', 'Doktorantlar', 'Ilmiy rahbarlar', 'Ixtisosliklar'], 'rows' => $out];
    }

    private static function ixtisoslik(): array
    {
        $rows = DB::select('SELECT id, code, name FROM specialties ORDER BY code, name');
        $out = [];
        foreach ($rows as $r) {
            $readiness = Specialty::accreditationReadiness((int) $r['id']);
            $out[] = [
                $r['code'] ?? '—',
                $r['name'],
                (int) DB::scalar('SELECT COUNT(*) FROM doctoral_students WHERE specialty_id = :s', ['s' => (int) $r['id']]),
                (int) DB::scalar('SELECT COUNT(*) FROM supervisors WHERE specialty_id = :s', ['s' => (int) $r['id']]),
                $readiness['percent'] === null ? 'Baholanmagan' : round($readiness['percent']) . '%',
                $readiness['label'],
            ];
        }
        return ['headers' => ['Shifr', 'Ixtisoslik', 'Doktorantlar', 'Rahbarlar', 'Tayyorlik', 'Holat'], 'rows' => $out];
    }

    private static function ilmiyRahbar(): array
    {
        $rows = DB::select(
            "SELECT su.id, su.full_name, su.academic_degree, su.academic_title,
                    (SELECT COUNT(*) FROM doctoral_students s WHERE s.supervisor_id = su.id) AS students
             FROM supervisors su ORDER BY su.full_name"
        );
        $out = [];
        foreach ($rows as $r) {
            $eff = Supervisor::effectiveness((int) $r['id']);
            $out[] = [
                $r['full_name'],
                $r['academic_degree'] ?? '—',
                $r['academic_title'] ?? '—',
                (int) $r['students'],
                $eff === null ? '—' : round($eff) . '%',
            ];
        }
        return ['headers' => ['F.I.Sh.', 'Ilmiy daraja', 'Unvon', 'Doktorantlar', 'Samaradorlik'], 'rows' => $out];
    }

    private static function ilmiyNatijalar(): array
    {
        $rows = DB::select(
            "SELECT r.result_type, COUNT(*) AS cnt
             FROM scientific_results r GROUP BY r.result_type ORDER BY cnt DESC"
        );
        $types = ScientificResult::TYPES;
        $out = [];
        foreach ($rows as $r) {
            $out[] = [$types[$r['result_type']] ?? $r['result_type'], (int) $r['cnt']];
        }
        return ['headers' => ['Natija turi', 'Soni'], 'rows' => $out];
    }

    private static function maqolalar(): array
    {
        $rows = DB::select(
            "SELECT p.title, p.journal, p.publication_type, p.published_at, s.full_name AS author
             FROM publications p
             LEFT JOIN doctoral_students s ON s.id = p.student_id
             ORDER BY p.published_at DESC, p.id DESC"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['title'],
                $r['journal'] ?? '—',
                $r['publication_type'] ?? '—',
                $r['published_at'] ?? '—',
                $r['author'] ?? '—',
            ];
        }
        return ['headers' => ['Sarlavha', 'Jurnal', 'Turi', 'Sana', 'Muallif'], 'rows' => $out];
    }

    private static function attestatsiya(): array
    {
        $rows = DB::select(
            "SELECT s.full_name, a.period, a.attestation_date, a.result
             FROM attestations a
             INNER JOIN doctoral_students s ON s.id = a.student_id
             ORDER BY a.attestation_date DESC, a.id DESC"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = [$r['full_name'], $r['period'] ?? '—', $r['attestation_date'] ?? '—', $r['result'] ?? '—'];
        }
        return ['headers' => ['Doktorant', 'Davr', 'Sana', 'Natija'], 'rows' => $out];
    }

    private static function akkreditatsiyaIndikatorlari(): array
    {
        $rows = DB::select(
            "SELECT i.code, i.name, c.name AS criterion, i.rag_status, i.score,
                    (SELECT COUNT(*) FROM indicator_evidence ie WHERE ie.indicator_id = i.id) AS evidence
             FROM accreditation_indicators i
             INNER JOIN accreditation_criteria c ON c.id = i.criteria_id
             ORDER BY i.code, i.id"
        );
        $labels = ScoringEngine::ragStateLabels();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['code'] ?? '—',
                $r['name'] ?? '—',
                $r['criterion'] ?? '—',
                $labels[$r['rag_status']] ?? $r['rag_status'],
                $r['score'] === null ? '—' : round((float) $r['score']) . '%',
                (int) $r['evidence'],
            ];
        }
        return ['headers' => ['Kod', 'Indikator', 'Mezon', 'RAG holati', 'Ball', 'Dalillar'], 'rows' => $out];
    }

    private static function indikatorlarByRag(string $rag): array
    {
        $rows = DB::select(
            "SELECT i.code, i.name, c.name AS criterion, i.rag_status
             FROM accreditation_indicators i
             INNER JOIN accreditation_criteria c ON c.id = i.criteria_id
             WHERE i.rag_status = :rag
             ORDER BY i.code, i.id",
            ['rag' => $rag]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = [$r['code'] ?? '—', $r['name'] ?? '—', $r['criterion'] ?? '—'];
        }
        return ['headers' => ['Kod', 'Indikator', 'Mezon'], 'rows' => $out];
    }

    private static function yetishmayotganDalillar(): array
    {
        $rows = DB::select(
            "SELECT i.code, i.name, c.name AS criterion
             FROM accreditation_indicators i
             INNER JOIN accreditation_criteria c ON c.id = i.criteria_id
             WHERE NOT EXISTS (SELECT 1 FROM indicator_evidence ie WHERE ie.indicator_id = i.id)
             ORDER BY i.code, i.id"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = [$r['code'] ?? '—', $r['name'] ?? '—', $r['criterion'] ?? '—', 'Dalil yo\'q'];
        }
        return ['headers' => ['Kod', 'Indikator', 'Mezon', 'Holat'], 'rows' => $out];
    }

    private static function kamchiliklarActionPlan(): array
    {
        $rows = DB::select(
            "SELECT d.title AS deficiency, d.severity, d.status AS def_status,
                    ap.title AS action, ap.due_date, ap.status AS action_status,
                    u.full_name AS responsible
             FROM deficiencies d
             LEFT JOIN action_plans ap ON ap.deficiency_id = d.id
             LEFT JOIN users u ON u.id = ap.responsible_user_id
             ORDER BY d.id DESC, ap.id"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['deficiency'],
                $r['severity'] ?? '—',
                $r['def_status'] ?? '—',
                $r['action'] ?? '—',
                $r['responsible'] ?? '—',
                $r['due_date'] ?? '—',
                $r['action_status'] ?? '—',
            ];
        }
        return ['headers' => ['Kamchilik', 'Jiddiylik', 'Holat', 'Chora-tadbir', 'Mas\'ul', 'Muddat', 'Chora holati'], 'rows' => $out];
    }

    private static function ichkiBaholash(): array
    {
        $rows = DB::select(
            "SELECT ia.title, s.name AS specialty, ia.audit_date, ia.readiness_index, ia.risk_level, u.full_name AS auditor
             FROM internal_audits ia
             LEFT JOIN specialties s ON s.id = ia.specialty_id
             LEFT JOIN users u ON u.id = ia.auditor_id
             ORDER BY ia.id DESC"
        );
        $risk = InternalAudit::RISK_LABELS;
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['title'],
                $r['specialty'] ?? '—',
                $r['audit_date'] ?? '—',
                $r['readiness_index'] === null ? 'Baholanmagan' : round((float) $r['readiness_index']) . '%',
                $risk[$r['risk_level']] ?? ($r['risk_level'] ?? '—'),
                $r['auditor'] ?? '—',
            ];
        }
        return ['headers' => ['Audit', 'Ixtisoslik', 'Sana', 'Tayyorlik', 'Xavf', 'Auditor'], 'rows' => $out];
    }

    private static function akkreditatsiyagaTayyorlik(): array
    {
        $out = [];
        foreach (DashboardStats::criteriaProgress() as $c) {
            $out[] = [
                $c['name'],
                $c['percent'] === null ? 'Baholanmagan' : round($c['percent']) . '%',
                ScoringEngine::readinessLabel($c['percent'])['label'],
            ];
        }
        return ['headers' => ['Mezon', 'Tayyorlik', 'Holat'], 'rows' => $out];
    }
}
