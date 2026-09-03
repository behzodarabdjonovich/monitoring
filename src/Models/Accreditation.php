<?php

namespace App\Models;

use App\Core\DB;
use App\Core\ScoringEngine;

/**
 * Akkreditatsiya sikli (item 9-10) — markaziy modul.
 *
 * Iyerarxiya: Accreditation -> Criteria -> Indicator -> (Requirement/Evidence/
 * Assessment/Deficiency/Action plan). Barcha mezon/indikatorlar DB'da
 * saqlanadi va admin tomonidan sozlanadi (data-driven, uydirma emas).
 */
final class Accreditation
{
    public static function find(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM accreditations WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function all(): array
    {
        return DB::select('SELECT * FROM accreditations ORDER BY id DESC');
    }

    /**
     * Barcha akkreditatsiyalar tayyorlik indeksi + bog'langan ixtisosliklar
     * bilan (ro'yxat sahifasi uchun).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function allWithReadiness(): array
    {
        $rows = self::all();
        foreach ($rows as &$r) {
            $a = ScoringEngine::assessAccreditation((int) $r['id']);
            $r['readiness_percent'] = $a['readiness_index'];
            $r['readiness_rag'] = $a['rag_status'];
            $r['readiness_label'] = $a['label'];
            $r['criteria_count'] = (int) DB::scalar(
                'SELECT COUNT(*) FROM accreditation_criteria WHERE accreditation_id = :aid',
                ['aid' => (int) $r['id']]
            );
            $r['specialties'] = self::specialties((int) $r['id']);
        }
        unset($r);
        return $rows;
    }

    /**
     * Ushbu akkreditatsiya sikliga bog'langan ixtisosliklar (item 8 linki).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function specialties(int $accreditationId): array
    {
        return DB::select(
            'SELECT id, code, name FROM specialties WHERE accreditation_id = :aid ORDER BY code, name',
            ['aid' => $accreditationId]
        );
    }

    /**
     * Mezonlar (og'irliklari va indikator soni bilan).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function criteria(int $accreditationId): array
    {
        $crits = DB::select(
            'SELECT * FROM accreditation_criteria WHERE accreditation_id = :aid ORDER BY display_order, id',
            ['aid' => $accreditationId]
        );
        foreach ($crits as &$c) {
            $rows = DB::select(
                'SELECT i.weight AS weight, i.score AS score, i.rag_status AS rag_status,
                        (SELECT COUNT(*) FROM indicator_evidence ie WHERE ie.indicator_id = i.id) AS evidence_count
                 FROM accreditation_indicators i WHERE i.criteria_id = :cid',
                ['cid' => (int) $c['id']]
            );
            $items = array_map(static fn ($r) => [
                'weight' => $r['weight'] !== null ? (float) $r['weight'] : 1.0,
                'score' => ScoringEngine::indicatorScore([
                    'score' => $r['score'] === null ? null : (float) $r['score'],
                    'rag_status' => $r['rag_status'] ?? null,
                    'evidence_count' => (int) ($r['evidence_count'] ?? 0),
                ]),
            ], $rows);
            $pct = ScoringEngine::weightedReadiness($items);
            $c['indicator_count'] = count($rows);
            $c['percent'] = $pct;
            $c['rag'] = ScoringEngine::ragStatus($pct);
        }
        unset($c);
        return $crits;
    }

    public static function findCriterion(int $criterionId): ?array
    {
        return DB::selectOne('SELECT * FROM accreditation_criteria WHERE id = :id', ['id' => $criterionId]);
    }
}
