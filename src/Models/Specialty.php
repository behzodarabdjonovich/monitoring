<?php

namespace App\Models;

use App\Core\DB;
use App\Core\ScoringEngine;

/**
 * Ixtisoslik va ta'lim dasturi (item 8).
 */
final class Specialty
{
    public static function find(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM specialties WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function all(): array
    {
        return DB::select(
            'SELECT sp.*, d.name AS department_name,
                    (SELECT COUNT(*) FROM doctoral_students s WHERE s.specialty_id = sp.id) AS student_count,
                    (SELECT COUNT(*) FROM supervisors su WHERE su.specialty_id = sp.id) AS supervisor_count
             FROM specialties sp
             LEFT JOIN departments d ON d.id = sp.responsible_department_id
             ORDER BY sp.code, sp.name'
        );
    }

    public static function findWithRelations(int $id): ?array
    {
        return DB::selectOne(
            'SELECT sp.*, d.name AS department_name, l.full_name AS program_lead_name
             FROM specialties sp
             LEFT JOIN departments d ON d.id = sp.responsible_department_id
             LEFT JOIN supervisors l ON l.id = sp.program_lead_supervisor_id
             WHERE sp.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Ixtisoslikning doktorantura dasturlari (PhD/DSc).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function programs(int $specialtyId): array
    {
        return DB::select('SELECT * FROM doctoral_programs WHERE specialty_id = :sid ORDER BY program_type', ['sid' => $specialtyId]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function supervisors(int $specialtyId): array
    {
        return DB::select('SELECT * FROM supervisors WHERE specialty_id = :sid ORDER BY full_name', ['sid' => $specialtyId]);
    }

    public static function studentCount(int $specialtyId): int
    {
        return (int) DB::scalar('SELECT COUNT(*) FROM doctoral_students WHERE specialty_id = :sid', ['sid' => $specialtyId]);
    }

    /**
     * Akkreditatsiyaga tayyorlik foizi (ScoringEngine orqali) — bog'langan
     * akkreditatsiya sikli indikatorlaridan hisoblanadi. Bog'lanmagan bo'lsa
     * null.
     *
     * @return array{percent:?float,rag:string,accreditation_id:?int}
     */
    public static function accreditationReadiness(int $specialtyId): array
    {
        $spec = self::find($specialtyId);
        $accId = $spec['accreditation_id'] ?? null;
        if ($accId === null) {
            return ['percent' => null, 'rag' => 'grey', 'accreditation_id' => null];
        }
        $assessment = ScoringEngine::assessAccreditation((int) $accId);
        return [
            'percent' => $assessment['readiness_index'],
            'rag' => $assessment['rag_status'],
            'accreditation_id' => (int) $accId,
        ];
    }
}
