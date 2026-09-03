<?php

namespace App\Core;

/**
 * Baholash mexanizmi (to'liq — FEAT-006).
 *
 * To'liq SOZLANADIGAN: og'irliklar (accreditation_criteria.weight,
 * accreditation_indicators.weight) va chegaralar (settings jadvalidagi
 * scoring.* kalitlari) DB'dan o'qiladi — kodda qattiq kodlangan qiymat yo'q.
 *
 * Hisoblash bosqichlari (docs/06-accreditation-module.md, 4-bo'lim):
 *   1) Indikator bali (0..100): baho (RAG) qo'yilganda mos ball, aks holda
 *      score ustuni; dalil (evidence) YO'Q bo'lsa indikator "grey" (indeksga
 *      hissa qo'shmaydi — grey handling sozlanadi).
 *   2) Mezon bali = indikatorlarning og'irlikli o'rtachasi.
 *   3) Umumiy tayyorlik% = mezonlarning og'irlikli o'rtachasi.
 * Natija RAG yorlig'iga (Tayyor / Takomillashtirish kerak / Yuqori xavf)
 * xaritalanadi.
 */
final class ScoringEngine
{
    /** 4 ta RAG baholash holati (item 10). */
    public const RAG_STATES = ['green', 'yellow', 'red', 'grey'];

    /**
     * RAG holatlarining o'zbekcha nomlari (item 10).
     *
     * @return array<string,string>
     */
    public static function ragStateLabels(): array
    {
        return [
            'green' => 'Talabga to\'liq mos',
            'yellow' => 'Qisman mos',
            'red' => 'Talabga mos emas',
            'grey' => 'Baholanmagan',
        ];
    }

    /**
     * Baholash holati (RAG) qo'yilganda indikatorga beriladigan kanonik ball
     * (0..100). SOZLANADIGAN — settings jadvalidan o'qiladi.
     *
     * @return array{green:float,yellow:float,red:float}
     */
    public static function stateScores(): array
    {
        return [
            'green' => (float) self::setting('scoring.score_green', 100),
            'yellow' => (float) self::setting('scoring.score_yellow', 60),
            'red' => (float) self::setting('scoring.score_red', 20),
        ];
    }

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
     * Kulrang (baholanmagan/dalilsiz) indikatorni hisoblash siyosati:
     *   'exclude' — indeksga kirmaydi (standart);
     *   'zero'    — 0 ball sifatida hisoblanadi (indeksni pasaytiradi).
     */
    public static function greyPolicy(): string
    {
        $p = (string) self::setting('scoring.grey_policy', 'exclude');
        return $p === 'zero' ? 'zero' : 'exclude';
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
     * Umumiy tayyorlik indeksini (0..100) RAG yorlig'iga xaritalaydi
     * (item 10 misollari: 91% Tayyor, 73% Takomillashtirish kerak, 48%
     * Yuqori xavf). Chegaralar SOZLANADIGAN.
     *
     * @return array{rag:string,label:string}
     */
    public static function readinessLabel(?float $percent): array
    {
        $rag = self::ragStatus($percent);
        $labels = [
            'green' => 'Tayyor',
            'yellow' => 'Takomillashtirish kerak',
            'red' => 'Yuqori xavf',
            'grey' => 'Baholanmagan',
        ];
        return ['rag' => $rag, 'label' => $labels[$rag]];
    }

    /**
     * Indikator ballaridan mezon/akkreditatsiya darajasida og'irlikli
     * o'rtacha tayyorlik indeksini (0..100) hisoblaydi.
     *
     * grey (score = null) indikatorlar greyPolicy'ga qarab hisobdan
     * chiqariladi (exclude) yoki 0 sifatida qo'shiladi (zero).
     *
     * @param array<int,array{weight:float,score:?float}> $items
     */
    public static function weightedReadiness(array $items, ?string $greyPolicy = null): ?float
    {
        $greyPolicy ??= self::greyPolicy();
        $totalWeight = 0.0;
        $weightedSum = 0.0;
        foreach ($items as $item) {
            $score = $item['score'] ?? null;
            $w = (float) ($item['weight'] ?? 1.0);
            if ($score === null) {
                if ($greyPolicy === 'zero') {
                    // grey => 0 ball, lekin og'irligi hisobga olinadi.
                    $totalWeight += $w;
                }
                continue;
            }
            $totalWeight += $w;
            $weightedSum += $w * (float) $score;
        }
        if ($totalWeight <= 0.0) {
            return null;
        }
        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * Bitta indikatorning tayyorlik indeksiga qo'shadigan bali (0..100) yoki
     * grey bo'lsa null. Dalil (indicator_evidence) yo'q bo'lsa har doim grey.
     *
     * @param array{score:?float,rag_status?:?string,evidence_count?:int} $row
     */
    public static function indicatorScore(array $row): ?float
    {
        $evidence = (int) ($row['evidence_count'] ?? 0);
        if ($evidence === 0) {
            return null;
        }
        $rag = $row['rag_status'] ?? null;
        // Aniq baholanmagan (grey) => null.
        if ($rag === 'grey') {
            return null;
        }
        // Baho qo'yilgan bo'lsa (green/yellow/red) — score ustunidan foydalanamiz;
        // score bo'lmasa RAG holatidan kanonik ballni olamiz.
        if ($row['score'] !== null) {
            return (float) $row['score'];
        }
        if ($rag !== null && isset(self::stateScores()[$rag])) {
            return self::stateScores()[$rag];
        }
        return null;
    }

    /**
     * Bir akkreditatsiya uchun tayyorlik indeksi + umumiy RAG holati + yorliq.
     * Ikki bosqichli og'irlikli o'rtacha: indikator -> mezon -> akkreditatsiya.
     *
     * @return array{readiness_index:?float,rag_status:string,label:string}
     */
    public static function assessAccreditation(int $accreditationId): array
    {
        $greyPolicy = self::greyPolicy();

        // Mezonlar (og'irliklari bilan).
        $criteria = DB::select(
            'SELECT id, weight FROM accreditation_criteria WHERE accreditation_id = :aid',
            ['aid' => $accreditationId]
        );

        $criteriaItems = [];
        foreach ($criteria as $c) {
            $rows = DB::select(
                'SELECT i.weight AS weight, i.score AS score, i.rag_status AS rag_status,
                        (SELECT COUNT(*) FROM indicator_evidence ie WHERE ie.indicator_id = i.id) AS evidence_count
                 FROM accreditation_indicators i WHERE i.criteria_id = :cid',
                ['cid' => (int) $c['id']]
            );
            $items = array_map(static fn ($r) => [
                'weight' => $r['weight'] !== null ? (float) $r['weight'] : 1.0,
                'score' => self::indicatorScore([
                    'score' => $r['score'] === null ? null : (float) $r['score'],
                    'rag_status' => $r['rag_status'] ?? null,
                    'evidence_count' => (int) ($r['evidence_count'] ?? 0),
                ]),
            ], $rows);
            $criteriaItems[] = [
                'weight' => $c['weight'] !== null ? (float) $c['weight'] : 1.0,
                'score' => self::weightedReadiness($items, $greyPolicy),
            ];
        }

        $readiness = self::weightedReadiness($criteriaItems, $greyPolicy);
        $label = self::readinessLabel($readiness);
        return [
            'readiness_index' => $readiness,
            'rag_status' => $label['rag'],
            'label' => $label['label'],
        ];
    }

    /**
     * Bitta indikator uchun RAG holatini dalil mavjudligi + baho/ball asosida
     * hisoblaydi. Dalil (indicator_evidence) yo'q bo'lsa har doim "grey".
     */
    public static function indicatorRag(int $indicatorId): string
    {
        $row = DB::selectOne(
            'SELECT i.score AS score, i.rag_status AS rag_status,
                    (SELECT COUNT(*) FROM indicator_evidence ie WHERE ie.indicator_id = i.id) AS evidence_count
             FROM accreditation_indicators i WHERE i.id = :id',
            ['id' => $indicatorId]
        );
        if ($row === null || (int) ($row['evidence_count'] ?? 0) === 0) {
            return 'grey';
        }
        // Aniq baho qo'yilgan bo'lsa (green/yellow/red) — o'sha holatni saqlaymiz.
        $rag = $row['rag_status'] ?? null;
        if (in_array($rag, ['green', 'yellow', 'red'], true)) {
            return (string) $rag;
        }
        // Aks holda ball asosida hisoblaymiz.
        return self::ragStatus($row['score'] === null ? null : (float) $row['score']);
    }

    /**
     * Indikator rag_status ustunini dalil holatiga qarab qayta hisoblab
     * saqlaydi (dalil bog'langan/uzilganda chaqiriladi).
     */
    public static function refreshIndicator(int $indicatorId): string
    {
        $rag = self::indicatorRag($indicatorId);
        DB::run(
            'UPDATE accreditation_indicators SET rag_status = :r, updated_at = :u WHERE id = :id',
            ['r' => $rag, 'u' => date('Y-m-d H:i:s'), 'id' => $indicatorId]
        );
        return $rag;
    }

    /**
     * Akkreditatsiya readiness_index ustunini qayta hisoblab saqlaydi
     * (baho/og'irlik/chegara o'zgarganda chaqiriladi).
     */
    public static function refreshAccreditation(int $accreditationId): array
    {
        $assessment = self::assessAccreditation($accreditationId);
        DB::run(
            'UPDATE accreditations SET readiness_index = :ri, updated_at = :u WHERE id = :id',
            ['ri' => $assessment['readiness_index'], 'u' => date('Y-m-d H:i:s'), 'id' => $accreditationId]
        );
        return $assessment;
    }

    /**
     * Barcha akkreditatsiyalarni qayta hisoblaydi (sozlamalar o'zgarganda).
     */
    public static function refreshAll(): void
    {
        $rows = DB::select('SELECT id FROM accreditations');
        foreach ($rows as $r) {
            self::refreshAccreditation((int) $r['id']);
        }
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
