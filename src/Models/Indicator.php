<?php

namespace App\Models;

use App\Core\DB;
use App\Core\ScoringEngine;

/**
 * Akkreditatsiya indikatori (item 9-10).
 *
 * Indikator kartasi barcha item-9 maydonlarini ko'rsatadi: kodi, nomi, talab
 * mazmuni, amaldagi holat, o'z-o'zini baholash, tasdiqlovchi dalillar (linked
 * documents), yuklangan hujjatlar, mas'ul bo'lim/shaxs, ekspert izohi,
 * aniqlangan kamchilik, chora-tadbir, bajarish muddati, bajarilish holati.
 */
final class Indicator
{
    public static function find(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM accreditation_indicators WHERE id = :id', ['id' => $id]);
    }

    /**
     * Mezonga tegishli indikatorlar (dalil soni bilan).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function forCriterion(int $criterionId): array
    {
        return DB::select(
            'SELECT i.*, r.title_uz AS responsible_role_title,
                    (SELECT COUNT(*) FROM indicator_evidence ie WHERE ie.indicator_id = i.id) AS evidence_count,
                    (SELECT COUNT(*) FROM deficiencies d WHERE d.indicator_id = i.id AND d.status <> :closed) AS open_deficiencies
             FROM accreditation_indicators i
             LEFT JOIN roles r ON r.id = i.responsible_role_id
             WHERE i.criteria_id = :cid
             ORDER BY i.code, i.id',
            ['cid' => $criterionId, 'closed' => 'resolved']
        );
    }

    /**
     * Indikatorni ota-mezon va akkreditatsiya konteksti bilan qaytaradi.
     */
    public static function findWithContext(int $id): ?array
    {
        return DB::selectOne(
            'SELECT i.*, c.name AS criterion_name, c.code AS criterion_code,
                    c.accreditation_id AS accreditation_id, c.id AS criterion_id,
                    a.title AS accreditation_title, r.title_uz AS responsible_role_title
             FROM accreditation_indicators i
             INNER JOIN accreditation_criteria c ON c.id = i.criteria_id
             INNER JOIN accreditations a ON a.id = c.accreditation_id
             LEFT JOIN roles r ON r.id = i.responsible_role_id
             WHERE i.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Ushbu indikator bo'yicha kamchiliklar (aniqlangan kamchilik) + har
     * biriga bog'langan chora-tadbirlar (action plans).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function deficiencies(int $indicatorId): array
    {
        $defs = DB::select(
            'SELECT d.*, u.full_name AS identified_by_name
             FROM deficiencies d
             LEFT JOIN users u ON u.id = d.identified_by
             WHERE d.indicator_id = :iid ORDER BY d.id DESC',
            ['iid' => $indicatorId]
        );
        foreach ($defs as &$d) {
            $d['action_plans'] = DB::select(
                'SELECT ap.*, u.full_name AS responsible_name
                 FROM action_plans ap
                 LEFT JOIN users u ON u.id = ap.responsible_user_id
                 WHERE ap.deficiency_id = :did ORDER BY ap.id',
                ['did' => (int) $d['id']]
            );
        }
        unset($d);
        return $defs;
    }

    /**
     * Indikatorga RAG baho qo'yadi (item 10). Baho holatidan (green/yellow/
     * red/grey) kanonik ball hosil qilinadi va score ustuniga yoziladi, shunda
     * tayyorlik indeksi bahoga mos keladi.
     *
     * grey (Baholanmagan) => score = null.
     */
    public static function setAssessment(int $indicatorId, string $ragState): void
    {
        if (!in_array($ragState, ScoringEngine::RAG_STATES, true)) {
            return;
        }
        $score = null;
        if ($ragState !== 'grey') {
            $score = ScoringEngine::stateScores()[$ragState] ?? null;
        }
        // Dalil yo'q bo'lsa RAG grey'ga majburlanadi (indicatorRag qoidasi).
        DB::run(
            'UPDATE accreditation_indicators SET rag_status = :r, score = :s, updated_at = :u WHERE id = :id',
            ['r' => $ragState, 's' => $score, 'u' => date('Y-m-d H:i:s'), 'id' => $indicatorId]
        );
        // Dalil mavjudligini hisobga olib qayta hisoblaymiz (dalilsiz => grey).
        $effective = ScoringEngine::indicatorRag($indicatorId);
        if ($effective !== $ragState) {
            DB::run(
                'UPDATE accreditation_indicators SET rag_status = :r, updated_at = :u WHERE id = :id',
                ['r' => $effective, 'u' => date('Y-m-d H:i:s'), 'id' => $indicatorId]
            );
        }
    }
}
