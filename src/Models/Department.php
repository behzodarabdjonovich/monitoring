<?php

namespace App\Models;

use App\Core\DB;

final class Department
{
    public static function find(int $id): ?array
    {
        return DB::selectOne(
            'SELECT * FROM departments WHERE id = :id',
            ['id' => $id]
        );
    }
public static function all(): array
{
    return DB::select(
        'SELECT d.*,
                (SELECT COUNT(*) FROM specialties sp
                 WHERE sp.responsible_department_id = d.id) AS specialty_count,
                (SELECT COUNT(*) FROM supervisors su
                 WHERE su.department_id = d.id) AS supervisor_count,
                (SELECT COUNT(*) FROM doctoral_students ds
                 WHERE ds.department_id = d.id) AS student_count
         FROM departments d
         ORDER BY d.name'
    );
}
    public static function canDelete(int $departmentId): bool
    {
        $specialtyCount = (int) DB::scalar(
            'SELECT COUNT(*) FROM specialties WHERE responsible_department_id = :id',
            ['id' => $departmentId]
        );

        $supervisorCount = (int) DB::scalar(
            'SELECT COUNT(*) FROM supervisors WHERE department_id = :id',
            ['id' => $departmentId]
        );

        $studentCount = (int) DB::scalar(
            'SELECT COUNT(*) FROM doctoral_students WHERE department_id = :id',
            ['id' => $departmentId]
        );

        return $specialtyCount === 0
            && $supervisorCount === 0
            && $studentCount === 0;
    }

    public static function delete(int $departmentId): bool
    {
        if (!self::canDelete($departmentId)) {
            return false;
        }

        DB::run(
            'DELETE FROM departments WHERE id = :id',
            ['id' => $departmentId]
        );

        return true;
    }
}
