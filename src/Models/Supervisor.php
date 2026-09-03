<?php

namespace App\Models;

use App\Core\DB;

/**
 * Ilmiy rahbar/maslahatchi (item 7).
 */
final class Supervisor
{
    public static function find(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM supervisors WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function all(): array
    {
        return DB::select(
            'SELECT sup.*, d.name AS department_name, sp.name AS specialty_name,
                    (SELECT COUNT(*) FROM doctoral_students s WHERE s.supervisor_id = sup.id) AS student_count
             FROM supervisors sup
             LEFT JOIN departments d ON d.id = sup.department_id
             LEFT JOIN specialties sp ON sp.id = sup.specialty_id
             ORDER BY sup.full_name'
        );
    }

    public static function findWithRelations(int $id): ?array
    {
        return DB::selectOne(
            'SELECT sup.*, d.name AS department_name, sp.name AS specialty_name
             FROM supervisors sup
             LEFT JOIN departments d ON d.id = sup.department_id
             LEFT JOIN specialties sp ON sp.id = sup.specialty_id
             WHERE sup.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Rahbarlik qilayotgan doktorantlar (relation).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function students(int $supervisorId): array
    {
        return DB::select(
            'SELECT * FROM doctoral_students WHERE supervisor_id = :sid ORDER BY full_name',
            ['sid' => $supervisorId]
        );
    }

    /**
     * Umumiy samaradorlik ko'rsatkichi (0..100).
     *
     * Namunaviy formula (og'irlikli):
     *   - 60%: rahbar doktorantlarining o'rtacha faoliyat foizi
     *          (reja vazifalari bajarilishi);
     *   - 40%: doktorant boshiga ilmiy natija (maqola+konferensiya) ko'rsatkichi
     *          (2 natija = 100% deb normallashtiriladi).
     * Doktorant bo'lmasa null.
     */
    public static function effectiveness(int $supervisorId): ?float
    {
        $students = self::students($supervisorId);
        if ($students === []) {
            return null;
        }
        $count = count($students);

        // 1) O'rtacha faoliyat foizi.
        $activitySum = 0.0;
        $resultsTotal = 0;
        foreach ($students as $s) {
            $activitySum += DoctoralStudent::activityPercent((int) $s['id']);
            $resultsTotal += (int) DB::scalar('SELECT COUNT(*) FROM publications WHERE student_id = :sid', ['sid' => (int) $s['id']]);
            $resultsTotal += (int) DB::scalar('SELECT COUNT(*) FROM conferences WHERE student_id = :sid', ['sid' => (int) $s['id']]);
        }
        $avgActivity = $activitySum / $count;

        // 2) Doktorant boshiga natija (2 => 100%).
        $resultsPerStudent = $resultsTotal / $count;
        $resultsScore = min(100.0, ($resultsPerStudent / 2.0) * 100.0);

        return round($avgActivity * 0.6 + $resultsScore * 0.4, 1);
    }
}
