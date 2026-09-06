<?php

namespace App\Models;

use App\Core\DB;

/**
 * Doktorant (item 4) — to'liq elektron profil modeli.
 */
final class DoctoralStudent
{
    public const TYPES = [
        'tayanch_doktorant' => 'Tayanch doktorant (PhD)',
        'doktorant' => 'Doktorant (DSc)',
        'mustaqil_izlanuvchi' => 'Mustaqil izlanuvchi',
    ];

    public const STATUSES = [
        'active' => 'Faol',
        'graduated' => 'Bitirgan',
        'expelled' => 'Chetlashtirilgan',
    ];

    public static function find(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM doctoral_students WHERE id = :id', ['id' => $id]);
    }

    /**
     * Foydalanuvchi id'si bo'yicha doktorant yozuvi (o'z kabineti uchun).
     */
    public static function findByUser(int $userId): ?array
    {
        return DB::selectOne('SELECT * FROM doctoral_students WHERE user_id = :uid LIMIT 1', ['uid' => $userId]);
    }

    /**
     * Ro'yxat: qidiruv (F.I.Sh./JSHSHIR/mavzu) + filtr (tur, ixtisoslik,
     * kafedra, holat). Bog'liq nomlar bilan JOIN.
     *
     * @param array<string,string> $f
     * @return array<int,array<string,mixed>>
     */
    public static function search(array $f = []): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(s.full_name LIKE :q OR s.national_id LIKE :q OR s.dissertation_topic LIKE :q)';
            $params['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['type'])) {
            $where[] = 's.student_type = :type';
            $params['type'] = $f['type'];
        }
        if (!empty($f['specialty'])) {
            $where[] = 's.specialty_id = :spec';
            $params['spec'] = (int) $f['specialty'];
        }
        if (!empty($f['department'])) {
            $where[] = 's.department_id = :dep';
            $params['dep'] = (int) $f['department'];
        }
        if (!empty($f['status'])) {
            $where[] = 's.status = :st';
            $params['st'] = $f['status'];
        }

        $sql = 'SELECT s.*, sp.name AS specialty_name, sp.code AS specialty_code,
                       d.name AS department_name, sup.full_name AS supervisor_name
                FROM doctoral_students s
                LEFT JOIN specialties sp ON sp.id = s.specialty_id
                LEFT JOIN departments d ON d.id = s.department_id
                LEFT JOIN supervisors sup ON sup.id = s.supervisor_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY s.full_name';
        return DB::select($sql, $params);
    }

    /**
     * Bog'liq nomlar bilan bitta doktorant yozuvi.
     */
    public static function findWithRelations(int $id): ?array
    {
        return DB::selectOne(
            'SELECT s.*, sp.name AS specialty_name, sp.code AS specialty_code,
                    d.name AS department_name, sup.full_name AS supervisor_name,
                    pr.name AS program_name, pr.program_type AS program_type
             FROM doctoral_students s
             LEFT JOIN specialties sp ON sp.id = s.specialty_id
             LEFT JOIN departments d ON d.id = s.department_id
             LEFT JOIN supervisors sup ON sup.id = s.supervisor_id
             LEFT JOIN doctoral_programs pr ON pr.id = s.program_id
             WHERE s.id = :id',
            ['id' => $id]
        );
    }

    /**
     * "Doktorant faoliyati bajarilishi: NN%" progress indikatori.
     *
     * Hisoblash: doktorantning tasdiqlangan individual rejalari vazifalari
     * bajarilish foizining o'rtachasi. Vazifa yo'q bo'lsa dissertation_percent
     * qiymatiga qaytadi; u ham bo'lmasa 0.
     */
    public static function activityPercent(int $studentId): float
    {
       $tasks = DB::select(
    'SELECT t.status, t.progress_percent
     FROM plan_tasks t
     INNER JOIN individual_plans p ON p.id = t.plan_id
     WHERE p.student_id = :sid
       AND p.status = :status',
    [
        'sid' => $studentId,
        'status' => 'approved',
    ]
);
        $pct = PlanTask::planCompletionPercent($tasks);
        if ($pct !== null) {
            return $pct;
        }
        $student = self::find($studentId);
        return (float) ($student['dissertation_percent'] ?? 0);
    }

    /**
     * Profil uchun bog'liq ma'lumotlar (rejalar, natijalar, attestatsiyalar,
     * maqolalar, konferensiyalar, hujjatlar).
     *
     * @return array<string,mixed>
     */
    public static function profileData(int $studentId): array
    {
        return [
            'plans' => DB::select('SELECT * FROM individual_plans WHERE student_id = :sid ORDER BY id DESC', ['sid' => $studentId]),
            'publications' => DB::select('SELECT * FROM publications WHERE student_id = :sid ORDER BY published_at DESC', ['sid' => $studentId]),
            'conferences' => DB::select('SELECT * FROM conferences WHERE student_id = :sid ORDER BY event_date DESC', ['sid' => $studentId]),
            'attestations' => DB::select('SELECT * FROM attestations WHERE student_id = :sid ORDER BY attestation_date DESC', ['sid' => $studentId]),
            'results' => DB::select('SELECT * FROM scientific_results WHERE student_id = :sid ORDER BY achieved_at DESC', ['sid' => $studentId]),
            'documents' => DB::select('SELECT * FROM documents WHERE student_id = :sid ORDER BY id DESC', ['sid' => $studentId]),
        ];
    }
}
