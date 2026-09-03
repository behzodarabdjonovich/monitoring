<?php

namespace App\Core;

/**
 * Baholash mexanizmi (SKELET).
 *
 * Bu bosqichda skeleton: sozlanadigan og'irliklar (weights) va chegaralarni
 * (thresholds) settings jadvalidan hamda akkreditatsiya mezon/indikatorlaridan
 * o'qiydi va RAG holati + tayyorlik indeksini qaytaradi. To'liq mantiq
 * akkreditatsiya bosqichida ulanadi (qarang docs/06-accreditation-module.md).
 */
final class ScoringEngine
{
    /**
     * settings jadvalidan chegara (threshold) qiymatlarini o'qiydi.
     * Standart: green >= 80, yellow >= 50, aks holda red; ma'lumot yo'q -> grey.
     *
     * @return array{green:float,yellow:float}
     */
    public static function thresholds(): array
    {
        return [
            'green' => (float) self::setting('scoring.threshold_green', 80),
            'yellow' => (float) self::setting('scoring.threshold_yellow', 50),
        ];
    }

    /**
     * Ballga (0..100) qarab RAG holatini qaytaradi.
     * $score null bo'lsa "grey" (ma'lumot kiritilmagan).
     */
    public static function ragStatus(?float $score): string
    {
        if ($score === null) {
            return 'grey';
        }
        $t = self::thresholds();
        if ($score >= $t['green']) {
            return 'green';
        }
        if ($score >= $t['yellow']) {
            return 'yellow';
        }
        return 'red';
    }

    /**
     * Indikator ballaridan mezon/akkreditatsiya darajasida og'irlikli
     * o'rtacha tayyorlik indeksini (0..100) hisoblaydi.
     *
     * @param array<int,array{weight:float,score:?float}> $items
     */
    public static function weightedReadiness(array $items): ?float
    {
        $totalWeight = 0.0;
        $weightedSum = 0.0;
        foreach ($items as $item) {
            if (($item['score'] ?? null) === null) {
                continue;
            }
            $w = (float) ($item['weight'] ?? 1.0);
            $totalWeight += $w;
            $weightedSum += $w * (float) $item['score'];
        }
        if ($totalWeight <= 0.0) {
            return null;
        }
        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * Bir akkreditatsiya uchun tayyorlik indeksi + umumiy RAG holatini
     * qaytaradi (skeleton hisoblash).
     *
     * @return array{readiness_index:?float,rag_status:string}
     */
    public static function assessAccreditation(int $accreditationId): array
    {
        // Indikator ballari va og'irliklarini o'qiymiz (prepared statement).
        $rows = DB::select(
            'SELECT i.weight AS weight, i.score AS score
             FROM accreditation_indicators i
             INNER JOIN accreditation_criteria c ON c.id = i.criteria_id
             WHERE c.accreditation_id = :aid',
            ['aid' => $accreditationId]
        );

        $items = array_map(static fn ($r) => [
            'weight' => $r['weight'] !== null ? (float) $r['weight'] : 1.0,
            'score' => $r['score'] !== null ? (float) $r['score'] : null,
        ], $rows);

        $readiness = self::weightedReadiness($items);
        return [
            'readiness_index' => $readiness,
            'rag_status' => self::ragStatus($readiness),
        ];
    }

    private static function setting(string $key, mixed $default): mixed
    {
        try {
            $val = DB::scalar('SELECT value FROM settings WHERE key = :k LIMIT 1', ['k' => $key]);
        } catch (\Throwable) {
            $val = false;
        }
        return $val === false || $val === null ? $default : $val;
    }
}
