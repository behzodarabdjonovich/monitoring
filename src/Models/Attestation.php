<?php

namespace App\Models;

use App\Core\DB;

/**
 * Attestatsiya (item 4/21) — doktorantga bog'langan.
 */
final class Attestation
{
    public const RESULTS = [
        'ijobiy' => 'Ijobiy',
        'salbiy' => 'Salbiy',
        'shartli' => 'Shartli',
    ];

    public static function find(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM attestations WHERE id = :id', ['id' => $id]);
    }

    public static function findWithRelations(int $id): ?array
    {
        return DB::selectOne(
            'SELECT a.*, s.full_name AS student_name
             FROM attestations a
             LEFT JOIN doctoral_students s ON s.id = a.student_id
             WHERE a.id = :id',
            ['id' => $id]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function all(): array
    {
        return DB::select(
            'SELECT a.*, s.full_name AS student_name
             FROM attestations a
             LEFT JOIN doctoral_students s ON s.id = a.student_id
             ORDER BY a.attestation_date DESC, a.id DESC'
        );
    }
}
