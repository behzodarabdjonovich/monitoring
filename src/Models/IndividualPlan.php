<?php

namespace App\Models;

use App\Core\DB;

/**
 * Individual reja (item 5).
 */
final class IndividualPlan
{
    public const STATUSES = [
        'draft' => 'Qoralama',
        'submitted' => 'Topshirilgan',
        'approved' => 'Tasdiqlangan',
    ];

    public static function find(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM individual_plans WHERE id = :id', ['id' => $id]);
    }

    public static function findWithRelations(int $id): ?array
    {
        return DB::selectOne(
            'SELECT p.*, s.full_name AS student_name, sup.full_name AS supervisor_name
             FROM individual_plans p
             LEFT JOIN doctoral_students s ON s.id = p.student_id
             LEFT JOIN supervisors sup ON sup.id = p.supervisor_id
             WHERE p.id = :id',
            ['id' => $id]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function all(): array
    {
        return DB::select(
            'SELECT p.*, s.full_name AS student_name, sup.full_name AS supervisor_name
             FROM individual_plans p
             LEFT JOIN doctoral_students s ON s.id = p.student_id
             LEFT JOIN supervisors sup ON sup.id = p.supervisor_id
             ORDER BY p.id DESC'
        );
    }

    /**
     * Doktorantning rejalari.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function forStudent(int $studentId): array
    {
        return DB::select('SELECT * FROM individual_plans WHERE student_id = :sid ORDER BY id DESC', ['sid' => $studentId]);
    }
}
