<?php

namespace App\Models;

use App\Core\DB;

final class SupervisorRequest
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    public static function find(int $id): ?array
    {
        return DB::selectOne(
            'SELECT * FROM supervisor_requests WHERE id = :id',
            ['id' => $id]
        );
    }

    public static function findWithRelations(int $id): ?array
    {
        return DB::selectOne(
            'SELECT sr.*,
                    ds.full_name AS student_name,
                    ds.specialty_id AS student_specialty_id,
                    ds.department_id AS student_department_id,
                    sup.full_name AS supervisor_name,
                    sup.specialty_id AS supervisor_specialty_id,
                    sup.department_id AS supervisor_department_id,
                    u.full_name AS reviewed_by_name
             FROM supervisor_requests sr
             INNER JOIN doctoral_students ds ON ds.id = sr.student_id
             INNER JOIN supervisors sup ON sup.id = sr.supervisor_id
             LEFT JOIN users u ON u.id = sr.reviewed_by
             WHERE sr.id = :id',
            ['id' => $id]
        );
    }

    public static function forStudent(int $studentId): array
    {
        return DB::select(
            'SELECT sr.*,
                    sup.full_name AS supervisor_name,
                    sup.academic_degree,
                    sup.academic_title
             FROM supervisor_requests sr
             INNER JOIN supervisors sup ON sup.id = sr.supervisor_id
             WHERE sr.student_id = :sid
             ORDER BY sr.created_at DESC',
            ['sid' => $studentId]
        );
    }

    public static function pendingForStudent(int $studentId): ?array
    {
        return DB::selectOne(
            'SELECT sr.*,
                    sup.full_name AS supervisor_name
             FROM supervisor_requests sr
             INNER JOIN supervisors sup ON sup.id = sr.supervisor_id
             WHERE sr.student_id = :sid
               AND sr.status = :status
             ORDER BY sr.created_at DESC
             LIMIT 1',
            [
                'sid' => $studentId,
                'status' => self::PENDING,
            ]
        );
    }

    public static function allWithRelations(): array
    {
        return DB::select(
            'SELECT sr.*,
                    ds.full_name AS student_name,
                    sup.full_name AS supervisor_name,
                    d.name AS department_name,
                    sp.name AS specialty_name,
                    u.full_name AS reviewed_by_name
             FROM supervisor_requests sr
             INNER JOIN doctoral_students ds ON ds.id = sr.student_id
             INNER JOIN supervisors sup ON sup.id = sr.supervisor_id
             LEFT JOIN departments d ON d.id = ds.department_id
             LEFT JOIN specialties sp ON sp.id = ds.specialty_id
             LEFT JOIN users u ON u.id = sr.reviewed_by
             ORDER BY sr.created_at DESC'
        );
    }

    public static function create(
        int $studentId,
        int $supervisorId,
        ?string $studentNote = null
    ): int {
        $now = date('Y-m-d H:i:s');

        return DB::insert('supervisor_requests', [
            'student_id' => $studentId,
            'supervisor_id' => $supervisorId,
            'status' => self::PENDING,
            'student_note' => $studentNote,
            'review_note' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public static function approve(
        int $id,
        int $reviewedBy,
        ?string $reviewNote = null
    ): bool {
        $request = self::find($id);

        if ($request === null || $request['status'] !== self::PENDING) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        DB::run(
            'UPDATE supervisor_requests
             SET status = :status,
                 review_note = :review_note,
                 reviewed_by = :reviewed_by,
                 reviewed_at = :reviewed_at,
                 updated_at = :updated_at
             WHERE id = :id
               AND status = :pending',
            [
                'status' => self::APPROVED,
                'review_note' => $reviewNote,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => $now,
                'updated_at' => $now,
                'id' => $id,
                'pending' => self::PENDING,
            ]
        );

        return true;
    }

    public static function reject(
        int $id,
        int $reviewedBy,
        ?string $reviewNote = null
    ): bool {
        $request = self::find($id);

        if ($request === null || $request['status'] !== self::PENDING) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        DB::run(
            'UPDATE supervisor_requests
             SET status = :status,
                 review_note = :review_note,
                 reviewed_by = :reviewed_by,
                 reviewed_at = :reviewed_at,
                 updated_at = :updated_at
             WHERE id = :id
               AND status = :pending',
            [
                'status' => self::REJECTED,
                'review_note' => $reviewNote,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => $now,
                'updated_at' => $now,
                'id' => $id,
                'pending' => self::PENDING,
            ]
        );

        return true;
    }
}
