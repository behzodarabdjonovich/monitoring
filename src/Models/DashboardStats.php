<?php

namespace App\Models;

use App\Core\DB;
use App\Core\ScoringEngine;

/**
 * Dashboard KPI hisoblovchisi.
 *
 * Item 3'dagi BARCHA ko'rsatkichlarni seed qilingan ma'lumotlar ustidan
 * PDO prepared-statement so'rovlari orqali hisoblaydi. Filtrlar (o'quv yili,
 * ixtisoslik, kafedra, doktorantura turi, kurs/bosqich, ilmiy rahbar,
 * akkreditatsiya holati) GET parametrlaridan qabul qilinadi va doktorantlar
 * to'plamini toraytiradi; barcha bog'liq KPI'lar shu to'plam bo'yicha
 * qayta hisoblanadi.
 *
 * Barcha SQL prepared-statement (SQLi himoyasi). Portativ SQL (SQLite/pgsql/
 * mysql uchun mos): faqat COUNT/SUM/JOIN/WHERE ishlatiladi.
 */
final class DashboardStats
{
    /** Ruxsat etilgan filtr kalitlari. */
    private const FILTER_KEYS = [
        'academic_year', 'specialty', 'department', 'dtype', 'course', 'supervisor', 'acc_status',
    ];

    /**
     * Filtrlarni GET massividan tozalab oladi (faqat kutilgan kalitlar,
     * bo'sh qiymatlar tashlanadi).
     *
     * @param array<string,mixed> $query
     * @return array<string,string>
     */
    public static function sanitizeFilters(array $query): array
    {
        $out = [];
        foreach (self::FILTER_KEYS as $key) {
            $val = $query[$key] ?? null;
            if ($val === null) {
                continue;
            }
            $val = trim((string) $val);
            if ($val === '' || strtolower($val) === 'all') {
                continue;
            }
            $out[$key] = $val;
        }
        return $out;
    }

    /**
     * Doktorantlar to'plamiga tegishli WHERE bo'lagi va bog'lamalarni quradi.
     * academic_year individual_plans jadvalidan (EXISTS orqali) tekshiriladi.
     *
     * @param array<string,string> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private static function studentWhere(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if (isset($filters['specialty'])) {
            $where[] = 's.specialty_id = :f_spec';
            $params['f_spec'] = (int) $filters['specialty'];
        }
        if (isset($filters['department'])) {
            $where[] = 's.department_id = :f_dep';
            $params['f_dep'] = (int) $filters['department'];
        }
        if (isset($filters['dtype'])) {
            $where[] = 's.student_type = :f_type';
            $params['f_type'] = $filters['dtype'];
        }
        if (isset($filters['course'])) {
            $where[] = 's.course_stage = :f_course';
            $params['f_course'] = (int) $filters['course'];
        }
        if (isset($filters['supervisor'])) {
            $where[] = 's.supervisor_id = :f_sup';
            $params['f_sup'] = (int) $filters['supervisor'];
        }
        if (isset($filters['academic_year'])) {
            $where[] = 'EXISTS (SELECT 1 FROM individual_plans p WHERE p.student_id = s.id AND p.academic_year = :f_ay)';
            $params['f_ay'] = $filters['academic_year'];
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * Filtrlangan doktorant id'lari (KPI'larni shu to'plam bilan cheklash uchun).
     *
     * @param array<string,string> $filters
     * @return int[]
     */
    private static function studentIds(array $filters): array
    {
        [$where, $params] = self::studentWhere($filters);
        $rows = DB::select("SELECT s.id FROM doctoral_students s WHERE $where", $params);
        return array_map(static fn ($r) => (int) $r['id'], $rows);
    }

    /**
     * IN (...) uchun nomlangan placeholderlar hosil qiladi.
     *
     * @param int[] $ids
     * @return array{0:string,1:array<string,int>}
     */
    private static function inClause(array $ids, string $prefix): array
    {
        if ($ids === []) {
            return ['(NULL)', []];
        }
        $names = [];
        $params = [];
        foreach (array_values($ids) as $i => $id) {
            $key = $prefix . $i;
            $names[] = ':' . $key;
            $params[$key] = $id;
        }
        return ['(' . implode(', ', $names) . ')', $params];
    }

    /**
     * Barcha KPI'larni + grafik ma'lumotlarini + hero tayyorligini hisoblaydi.
     *
     * @param array<string,string> $filters
     * @return array<string,mixed>
     */
    public static function compute(array $filters = []): array
    {
        [$where, $params] = self::studentWhere($filters);
        $studentIds = self::studentIds($filters);
        [$in, $inParams] = self::inClause($studentIds, 'sid');

        $base = "FROM doctoral_students s WHERE $where";

        // --- Doktorantlar bo'yicha KPI'lar ---
        $total = (int) DB::scalar("SELECT COUNT(*) $base", $params);
        $phd = (int) DB::scalar("SELECT COUNT(*) $base AND s.student_type = 'tayanch_doktorant'", $params);
        $dsc = (int) DB::scalar("SELECT COUNT(*) $base AND s.student_type = 'doktorant'", $params);
        $independent = (int) DB::scalar("SELECT COUNT(*) $base AND s.student_type = 'mustaqil_izlanuvchi'", $params);

        // Ixtisosliklar / ilmiy rahbarlar (filtrga qarab toraytirilgan doktorantlar
        // orqali aniqlanadi; filtrsiz — umumiy ro'yxat).
        if ($filters === []) {
            $totalSpecialties = (int) DB::scalar('SELECT COUNT(*) FROM specialties');
            $totalSupervisors = (int) DB::scalar('SELECT COUNT(*) FROM supervisors');
        } else {
            $totalSpecialties = (int) DB::scalar("SELECT COUNT(DISTINCT s.specialty_id) $base", $params);
            $totalSupervisors = (int) DB::scalar("SELECT COUNT(DISTINCT s.supervisor_id) $base", $params);
        }

        // Himoyaga tayyor doktorantlar: oxirgi kursda va aktiv (namunaviy mezon).
        $readyToDefend = (int) DB::scalar(
            "SELECT COUNT(*) $base AND s.status = 'active' AND s.course_stage >= 3",
            $params
        );

        // Individual rejasini to'liq bajarganlar: barcha reja vazifalari 'completed'
        // bo'lgan doktorantlar (kamida bitta reja/vazifa mavjud).
        $planFullyDone = self::countPlanFullyCompleted($studentIds);
        // Rejadan ortda qolayotganlar: kamida bitta 'overdue' vazifasi bor.
        $behindSchedule = self::countBehind($studentIds);

        // --- Ilmiy natijalar ---
        [$pubTotal, $pubIntl] = self::publicationCounts($studentIds);
        $conferences = self::countIn('conferences', $studentIds);
        $defenses = self::countResultType($studentIds, 'dissertatsiya_himoyasi');

        // --- Akkreditatsiya KPI'lari (filtrdan mustaqil — institut darajasi) ---
        $accReadySpecialties = self::accreditationReadySpecialties();
        $problemIndicators = (int) DB::scalar(
            "SELECT COUNT(*) FROM accreditation_indicators WHERE rag_status = 'red'"
        );
        $missingDocs = (int) DB::scalar(
            'SELECT COUNT(*) FROM accreditation_indicators i
             WHERE NOT EXISTS (SELECT 1 FROM indicator_evidence e WHERE e.indicator_id = i.id)'
        );

        // --- HERO: umumiy tayyorlik (ScoringEngine, ixtisosliklar/indikatorlar ustidan) ---
        $hero = self::heroReadiness($filters);

        $kpis = [
            'total_students' => $total,
            'phd' => $phd,
            'dsc' => $dsc,
            'independent' => $independent,
            'specialties' => $totalSpecialties,
            'supervisors' => $totalSupervisors,
            'ready_to_defend' => $readyToDefend,
            'plan_fully_done' => $planFullyDone,
            'behind_schedule' => $behindSchedule,
            'publications' => $pubTotal,
            'publications_intl' => $pubIntl,
            'conferences' => $conferences,
            'defenses' => $defenses,
            'acc_ready_specialties' => $accReadySpecialties,
            'problem_indicators' => $problemIndicators,
            'missing_docs' => $missingDocs,
        ];

        return [
            'kpis' => $kpis,
            'hero' => $hero,
            'charts' => self::chartData($filters, $studentIds, $params, $base),
            'progress' => self::criteriaProgress(),
        ];
    }

    /**
     * Reja vazifalarining hammasi bajarilgan doktorantlar soni.
     *
     * @param int[] $studentIds
     */
    private static function countPlanFullyCompleted(array $studentIds): int
    {
        [$in, $p] = self::inClause($studentIds, 'sid');
        $sql = "SELECT COUNT(*) FROM (
                    SELECT s.id
                    FROM doctoral_students s
                    INNER JOIN individual_plans pl ON pl.student_id = s.id
                    INNER JOIN plan_tasks t ON t.plan_id = pl.id
                    WHERE s.id IN $in
                    GROUP BY s.id
                    HAVING SUM(CASE WHEN t.status <> 'completed' THEN 1 ELSE 0 END) = 0
                ) x";
        return (int) DB::scalar($sql, $p);
    }

    /**
     * Kamida bitta muddati o'tgan (overdue) vazifasi bor doktorantlar soni.
     *
     * Overdue ikki ko'rinishda bo'ladi va ikkalasi ham hisobga olinadi
     * (PlanTask::isOverdue() bilan bir xil mantiq):
     *   1) legacy/seed 'overdue' holatida saqlangan vazifa;
     *   2) due_date o'tgan VA holat completed/supervisor_approved/finalized
     *      EMAS bo'lgan vazifa (prezentatsiyadan olinadigan overdue).
     * ISO (Y-m-d) sanalar leksikografik taqqoslanadi — sqlite/pgsql/mysql'da
     * portativ.
     *
     * @param int[] $studentIds
     */
    private static function countBehind(array $studentIds): int
    {
        [$in, $p] = self::inClause($studentIds, 'sid');
        $p['today'] = date('Y-m-d');
        $sql = "SELECT COUNT(DISTINCT s.id)
                FROM doctoral_students s
                INNER JOIN individual_plans pl ON pl.student_id = s.id
                INNER JOIN plan_tasks t ON t.plan_id = pl.id
                WHERE s.id IN $in
                  AND (
                        t.status = 'overdue'
                     OR (
                            t.due_date IS NOT NULL AND t.due_date <> ''
                        AND t.due_date < :today
                        AND t.status NOT IN ('completed', 'supervisor_approved', 'finalized')
                     )
                  )";
        return (int) DB::scalar($sql, $p);
    }

    /**
     * @param int[] $studentIds
     * @return array{0:int,1:int} [jami, xalqaro (scopus/wos)]
     */
    private static function publicationCounts(array $studentIds): array
    {
        [$in, $p] = self::inClause($studentIds, 'sid');
        $total = (int) DB::scalar("SELECT COUNT(*) FROM publications WHERE student_id IN $in", $p);
        $intl = (int) DB::scalar(
            "SELECT COUNT(*) FROM publications WHERE student_id IN $in AND publication_type IN ('scopus', 'wos')",
            $p
        );
        return [$total, $intl];
    }

    /**
     * @param int[] $studentIds
     */
    private static function countIn(string $table, array $studentIds): int
    {
        [$in, $p] = self::inClause($studentIds, 'sid');
        return (int) DB::scalar("SELECT COUNT(*) FROM $table WHERE student_id IN $in", $p);
    }

    /**
     * @param int[] $studentIds
     */
    private static function countResultType(array $studentIds, string $type): int
    {
        [$in, $p] = self::inClause($studentIds, 'sid');
        $p['rt'] = $type;
        return (int) DB::scalar(
            "SELECT COUNT(*) FROM scientific_results WHERE student_id IN $in AND result_type = :rt",
            $p
        );
    }

    /**
     * Akkreditatsiyaga tayyor ixtisosliklar soni (namunaviy mezon: umumiy
     * tayyorlik indeksi yashil chegaradan yuqori bo'lsa tayyor deb hisoblanadi).
     * Hozircha institut darajasidagi bitta akkreditatsiya sikli bor, shuning
     * uchun indeks green bo'lsa — ixtisosliklar tayyor deb belgilanadi.
     */
    private static function accreditationReadySpecialties(): int
    {
        $t = ScoringEngine::thresholds();
        $rows = DB::select('SELECT id FROM accreditations');
        $ready = 0;
        foreach ($rows as $r) {
            $a = ScoringEngine::assessAccreditation((int) $r['id']);
            if ($a['readiness_index'] !== null && $a['readiness_index'] >= $t['green']) {
                $ready++;
            }
        }
        return $ready;
    }

    /**
     * HERO uchun umumiy tayyorlik foizi + RAG holati.
     *
     * @param array<string,string> $filters
     * @return array{percent:?float,rag:string,status:?string,cycle:?string,is_placeholder:bool}
     */
    public static function heroReadiness(array $filters = []): array
    {
        // Institut darajasidagi (birinchi) akkreditatsiya siklini olamiz.
        $acc = DB::selectOne('SELECT * FROM accreditations ORDER BY id LIMIT 1');
        if ($acc === null) {
            return ['percent' => null, 'rag' => 'grey', 'label' => 'Baholanmagan', 'status' => null, 'cycle' => null, 'is_placeholder' => false];
        }
        // Agar akkreditatsiya holati filtri berilgan bo'lsa va mos kelmasa —
        // hero'ni grey qilib ko'rsatamiz (natija filtrga bo'ysunishini isbotlaydi).
        if (isset($filters['acc_status']) && $filters['acc_status'] !== (string) $acc['status']) {
            return [
                'percent' => null,
                'rag' => 'grey',
                'label' => 'Baholanmagan',
                'status' => $acc['status'],
                'cycle' => $acc['cycle_year'],
                'is_placeholder' => (int) $acc['is_placeholder'] === 1,
            ];
        }
        $assessment = ScoringEngine::assessAccreditation((int) $acc['id']);
        return [
            'percent' => $assessment['readiness_index'],
            'rag' => $assessment['rag_status'],
            'label' => $assessment['label'],
            'status' => $acc['status'],
            'cycle' => $acc['cycle_year'],
            'is_placeholder' => (int) $acc['is_placeholder'] === 1,
        ];
    }

    /**
     * Grafiklar uchun ma'lumot to'plamlari.
     *
     * @param array<string,string> $filters
     * @param int[] $studentIds
     * @param array<string,mixed> $params
     */
    private static function chartData(array $filters, array $studentIds, array $params, string $base): array
    {
        // 1) Doktorantura turi taqsimoti (donut).
        $byType = [
            ['label' => 'PhD (tayanch)', 'value' => (float) DB::scalar("SELECT COUNT(*) $base AND s.student_type = 'tayanch_doktorant'", $params), 'color' => '#2E75B6'],
            ['label' => 'DSc', 'value' => (float) DB::scalar("SELECT COUNT(*) $base AND s.student_type = 'doktorant'", $params), 'color' => '#1F4E79'],
            ['label' => 'Mustaqil izlanuvchi', 'value' => (float) DB::scalar("SELECT COUNT(*) $base AND s.student_type = 'mustaqil_izlanuvchi'", $params), 'color' => '#2E9E5B'],
        ];

        // 2) Natijalar turi bo'yicha (bar): maqola / xalqaro / konferensiya / himoya.
        [$in, $p] = self::inClause($studentIds, 'sid');
        $pubTotal = (int) DB::scalar("SELECT COUNT(*) FROM publications WHERE student_id IN $in", $p);
        $pubIntl = (int) DB::scalar("SELECT COUNT(*) FROM publications WHERE student_id IN $in AND publication_type IN ('scopus', 'wos')", $p);
        $conf = (int) DB::scalar("SELECT COUNT(*) FROM conferences WHERE student_id IN $in", $p);
        $def = self::countResultType($studentIds, 'dissertatsiya_himoyasi');
        $byResult = [
            ['label' => 'Maqolalar', 'value' => (float) $pubTotal, 'color' => '#2E75B6'],
            ['label' => 'Xalqaro (Scopus/WoS)', 'value' => (float) $pubIntl, 'color' => '#2E9E5B'],
            ['label' => 'Konferensiya', 'value' => (float) $conf, 'color' => '#E0A800'],
            ['label' => 'Himoyalar', 'value' => (float) $def, 'color' => '#7A5FB0'],
        ];

        // 3) Ixtisoslik bo'yicha doktorantlar soni (bar) — filtrga bo'ysunadi.
        $specRows = DB::select(
            "SELECT sp.name AS name, COUNT(s.id) AS cnt
             FROM specialties sp
             LEFT JOIN doctoral_students s ON s.specialty_id = sp.id AND s.id IN $in
             GROUP BY sp.id, sp.name ORDER BY sp.id",
            $p
        );
        $bySpecialty = array_map(static fn ($r) => [
            'label' => (string) $r['name'],
            'value' => (float) $r['cnt'],
        ], $specRows);

        // 4) Indikatorlar RAG taqsimoti (donut).
        $ragRows = DB::select("SELECT rag_status, COUNT(*) AS cnt FROM accreditation_indicators GROUP BY rag_status");
        $ragMap = ['green' => 0, 'yellow' => 0, 'red' => 0, 'grey' => 0];
        foreach ($ragRows as $r) {
            $ragMap[$r['rag_status']] = (int) $r['cnt'];
        }
        $ragDist = [
            ['label' => 'Yashil', 'value' => (float) $ragMap['green'], 'color' => '#2E9E5B'],
            ['label' => 'Sariq', 'value' => (float) $ragMap['yellow'], 'color' => '#E0A800'],
            ['label' => 'Qizil', 'value' => (float) $ragMap['red'], 'color' => '#D64545'],
            ['label' => 'Kulrang', 'value' => (float) $ragMap['grey'], 'color' => '#9AA5B1'],
        ];

        return [
            'by_type' => $byType,
            'by_result' => $byResult,
            'by_specialty' => $bySpecialty,
            'rag_distribution' => $ragDist,
        ];
    }

    /**
     * Mezonlar bo'yicha tayyorlik (progress barlar): og'irlikli o'rtacha ball.
     *
     * @return array<int,array{name:string,percent:?float,rag:string}>
     */
    public static function criteriaProgress(): array
    {
        $crits = DB::select('SELECT id, name FROM accreditation_criteria ORDER BY display_order, id');
        $out = [];
        foreach ($crits as $c) {
            $rows = DB::select(
                'SELECT weight, score FROM accreditation_indicators WHERE criteria_id = :cid',
                ['cid' => (int) $c['id']]
            );
            $items = array_map(static fn ($r) => [
                'weight' => $r['weight'] !== null ? (float) $r['weight'] : 1.0,
                'score' => $r['score'] !== null ? (float) $r['score'] : null,
            ], $rows);
            $pct = ScoringEngine::weightedReadiness($items);
            $out[] = [
                'name' => (string) $c['name'],
                'percent' => $pct,
                'rag' => ScoringEngine::ragStatus($pct),
            ];
        }
        return $out;
    }

    /**
     * Filtr paneli uchun tanlov ro'yxatlari.
     *
     * @return array<string,array<int,array{value:string,label:string}>>
     */
    public static function filterOptions(): array
    {
        $academicYears = array_map(
            static fn ($r) => ['value' => (string) $r['academic_year'], 'label' => (string) $r['academic_year']],
            DB::select("SELECT DISTINCT academic_year FROM individual_plans WHERE academic_year <> '' ORDER BY academic_year")
        );
        $specialties = array_map(
            static fn ($r) => ['value' => (string) $r['id'], 'label' => (string) $r['name']],
            DB::select('SELECT id, name FROM specialties ORDER BY name')
        );
        $departments = array_map(
            static fn ($r) => ['value' => (string) $r['id'], 'label' => (string) $r['name']],
            DB::select('SELECT id, name FROM departments ORDER BY name')
        );
        $types = [
            ['value' => 'tayanch_doktorant', 'label' => 'Tayanch doktorant (PhD)'],
            ['value' => 'doktorant', 'label' => 'Doktorant (DSc)'],
            ['value' => 'mustaqil_izlanuvchi', 'label' => 'Mustaqil izlanuvchi'],
        ];
        $courses = array_map(
            static fn ($r) => ['value' => (string) $r['course_stage'], 'label' => $r['course_stage'] . '-kurs/bosqich'],
            DB::select('SELECT DISTINCT course_stage FROM doctoral_students WHERE course_stage IS NOT NULL ORDER BY course_stage')
        );
        $supervisors = array_map(
            static fn ($r) => ['value' => (string) $r['id'], 'label' => (string) $r['full_name']],
            DB::select('SELECT id, full_name FROM supervisors ORDER BY full_name')
        );
        $accStatuses = array_map(
            static fn ($r) => ['value' => (string) $r['status'], 'label' => (string) $r['status']],
            DB::select("SELECT DISTINCT status FROM accreditations WHERE status <> '' ORDER BY status")
        );

        return [
            'academic_year' => $academicYears,
            'specialty' => $specialties,
            'department' => $departments,
            'dtype' => $types,
            'course' => $courses,
            'supervisor' => $supervisors,
            'acc_status' => $accStatuses,
        ];
    }
}
