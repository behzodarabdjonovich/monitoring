<?php

namespace App\Models;

use App\Core\DB;
use App\Core\ScoringEngine;

/**
 * Ichki akkreditatsiya auditi (item 13).
 *
 * Har bir ixtisoslik bo'yicha "Ichki akkreditatsiya auditi" o'tkazadi va audit
 * yakunida quyidagi bo'limlarni AVTOMATIK shakllantiradi:
 *   - kuchli tomonlar (strengths)     — yashil (green) indikatorlar;
 *   - kamchiliklar (weaknesses)       — qizil/sariq (red/yellow) indikatorlar;
 *   - bajarilmagan indikatorlar (unmet) — red indikatorlar (talabga mos emas);
 *   - yetishmayotgan dalillar (missing) — kulrang/dalilsiz (grey) indikatorlar;
 *   - xavf darajasi (risk_level)      — tayyorlik indeksi bandidan;
 *   - tavsiyalar (recommendations)    — buketlardan hosil qilinadi;
 *   - chora-tadbirlar rejasi          — kamchiliklardan ActionPlan urug'lanadi;
 *   - akkreditatsiyaga tayyorlik foizi (ScoringEngine).
 */
final class InternalAudit
{
    /** Xavf darajasi yorliqlari. */
    public const RISK_LABELS = [
        'low' => 'Past xavf',
        'medium' => 'O\'rta xavf',
        'high' => 'Yuqori xavf',
        'unknown' => 'Aniqlanmagan',
    ];

    public static function find(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM internal_audits WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function all(): array
    {
        return DB::select(
            'SELECT ia.*, s.code AS specialty_code, s.name AS specialty_name,
                    u.full_name AS auditor_name
             FROM internal_audits ia
             LEFT JOIN specialties s ON s.id = ia.specialty_id
             LEFT JOIN users u ON u.id = ia.auditor_id
             ORDER BY ia.id DESC'
        );
    }

    public static function findWithContext(int $id): ?array
    {
        return DB::selectOne(
            'SELECT ia.*, s.code AS specialty_code, s.name AS specialty_name,
                    u.full_name AS auditor_name, a.title AS accreditation_title
             FROM internal_audits ia
             LEFT JOIN specialties s ON s.id = ia.specialty_id
             LEFT JOIN users u ON u.id = ia.auditor_id
             LEFT JOIN accreditations a ON a.id = ia.accreditation_id
             WHERE ia.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Tayyorlik indeksi (0..100) bandidan xavf darajasini keltirib chiqaradi:
     *   >= threshold_green   => low (past xavf, tayyor);
     *   >= threshold_yellow  => medium (o'rta xavf, takomillashtirish);
     *   <  threshold_yellow  => high (yuqori xavf);
     *   null                 => unknown (baholanmagan).
     */
    public static function riskLevel(?float $readiness): string
    {
        if ($readiness === null) {
            return 'unknown';
        }
        $t = ScoringEngine::thresholds();
        if ($readiness >= $t['green']) {
            return 'low';
        }
        if ($readiness >= $t['yellow']) {
            return 'medium';
        }
        return 'high';
    }

    /**
     * Ixtisoslik indikatorlarini RAG holati bo'yicha buketlarga ajratadi.
     * Ixtisoslik akkreditatsiya sikliga bog'langan bo'lishi kerak.
     *
     * @return array{
     *   strengths: array<int,array<string,mixed>>,
     *   weaknesses: array<int,array<string,mixed>>,
     *   unmet: array<int,array<string,mixed>>,
     *   missing_evidence: array<int,array<string,mixed>>
     * }
     */
    public static function bucketIndicators(int $accreditationId): array
    {
        $rows = DB::select(
            'SELECT i.id, i.code, i.name, i.rag_status,
                    (SELECT COUNT(*) FROM indicator_evidence ie WHERE ie.indicator_id = i.id) AS evidence_count
             FROM accreditation_indicators i
             INNER JOIN accreditation_criteria c ON c.id = i.criteria_id
             WHERE c.accreditation_id = :aid
             ORDER BY i.code, i.id',
            ['aid' => $accreditationId]
        );

        $buckets = ['strengths' => [], 'weaknesses' => [], 'unmet' => [], 'missing_evidence' => []];
        foreach ($rows as $r) {
            // Effektiv RAG: dalil (evidence) yo'q bo'lsa har doim grey.
            $rag = (int) ($r['evidence_count'] ?? 0) === 0 ? 'grey' : (string) $r['rag_status'];
            $entry = [
                'id' => (int) $r['id'],
                'code' => (string) ($r['code'] ?? ''),
                'name' => (string) ($r['name'] ?? ''),
                'rag' => $rag,
            ];
            if ($rag === 'green') {
                $buckets['strengths'][] = $entry;
            } elseif ($rag === 'grey') {
                // Dalilsiz/baholanmagan => yetishmayotgan dalil.
                $buckets['missing_evidence'][] = $entry;
            } else {
                // red / yellow => kamchilik.
                $buckets['weaknesses'][] = $entry;
                if ($rag === 'red') {
                    // Talabga mos emas => bajarilmagan indikator.
                    $buckets['unmet'][] = $entry;
                }
            }
        }
        return $buckets;
    }

    /**
     * Buketlar va tayyorlik/xavf asosida tavsiyalar hosil qiladi.
     *
     * @param array<string,array<int,array<string,mixed>>> $buckets
     * @return array<int,string>
     */
    public static function buildRecommendations(array $buckets, string $risk): array
    {
        $rec = [];
        if ($buckets['missing_evidence'] !== []) {
            $rec[] = 'Yetishmayotgan dalillarni yuklang: dalilsiz ' . count($buckets['missing_evidence'])
                . ' ta indikator uchun tasdiqlovchi hujjatlarni biriktiring.';
        }
        if ($buckets['unmet'] !== []) {
            $rec[] = 'Talabga mos emas (qizil) ' . count($buckets['unmet'])
                . ' ta indikator bo\'yicha zudlik bilan chora-tadbir rejasini ishga tushiring.';
        }
        if ($buckets['weaknesses'] !== []) {
            $rec[] = 'Kamchiliklar (qizil/sariq) bo\'yicha mas\'ullarni belgilang va yakuniy muddatlarni nazorat qiling.';
        }
        if ($buckets['strengths'] !== []) {
            $rec[] = 'Kuchli tomonlarni (' . count($buckets['strengths'])
                . ' ta indikator) hujjatlashtirib, akkreditatsiya hisobotida namuna sifatida keltiring.';
        }
        if ($risk === 'high') {
            $rec[] = 'Xavf darajasi yuqori — akkreditatsiyaga tayyorlikni oshirish bo\'yicha kengaytirilgan yo\'l xaritasini tasdiqlang.';
        } elseif ($risk === 'low' && $buckets['weaknesses'] === []) {
            $rec[] = 'Tayyorlik darajasi yaxshi — mavjud holatni saqlab, davriy monitoringni davom ettiring.';
        }
        if ($rec === []) {
            $rec[] = 'Baholash uchun yetarli ma\'lumot yo\'q — indikatorlarga baho va dalil kiriting.';
        }
        return $rec;
    }

    /**
     * Ichki auditni ISHGA TUSHIRADI: buketlarni hisoblaydi, internal_audits
     * yozuvini yaratadi va kamchiliklardan (red/yellow indikatorlar) chora-
     * tadbir rejasi urug'ini (deficiency + action_plan) shakllantiradi.
     *
     * @return array{audit_id:int, readiness:?float, risk:string, buckets:array<string,mixed>, deficiency_ids:array<int,int>}
     */
    public static function run(int $specialtyId, ?int $auditorId): array
    {
        $spec = DB::selectOne('SELECT * FROM specialties WHERE id = :id', ['id' => $specialtyId]);
        $accId = $spec['accreditation_id'] ?? null;

        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        if ($accId === null) {
            // Akkreditatsiyaga bog'lanmagan ixtisoslik — bo'sh hisobot.
            $buckets = ['strengths' => [], 'weaknesses' => [], 'unmet' => [], 'missing_evidence' => []];
            $readiness = null;
            $risk = 'unknown';
        } else {
            $accId = (int) $accId;
            $assessment = ScoringEngine::assessAccreditation($accId);
            $readiness = $assessment['readiness_index'];
            $risk = self::riskLevel($readiness);
            $buckets = self::bucketIndicators($accId);
        }

        $recommendations = self::buildRecommendations($buckets, $risk);

        $summary = sprintf(
            'Ichki akkreditatsiya auditi: kuchli tomonlar %d, kamchiliklar %d, bajarilmagan %d, yetishmayotgan dalillar %d. Tayyorlik: %s.',
            count($buckets['strengths']),
            count($buckets['weaknesses']),
            count($buckets['unmet']),
            count($buckets['missing_evidence']),
            $readiness === null ? 'baholanmagan' : round($readiness) . '%'
        );

        $auditId = DB::insert('internal_audits', [
            'accreditation_id' => $accId,
            'specialty_id' => $specialtyId,
            'title' => 'Ichki akkreditatsiya auditi — ' . (string) ($spec['name'] ?? ('Ixtisoslik #' . $specialtyId)),
            'audit_date' => $today,
            'auditor_id' => $auditorId,
            'scope' => 'Avtomatik shakllantirilgan ichki audit hisoboti (item 13).',
            'status' => 'completed',
            'summary' => $summary,
            'readiness_index' => $readiness,
            'risk_level' => $risk,
            'strengths' => self::encode($buckets['strengths']),
            'weaknesses' => self::encode($buckets['weaknesses']),
            'unmet_indicators' => self::encode($buckets['unmet']),
            'missing_evidence' => self::encode($buckets['missing_evidence']),
            'recommendations' => self::encode($recommendations),
            'created_at' => $now,
        ]);

        // Chora-tadbirlar rejasi urug'i: har kamchilik (red/yellow indikator)
        // uchun kamchilik yozuvi + boshlang'ich chora-tadbir. Audit natijasida
        // aniqlangan kamchiliklar Deficiencies moduliga oqib o'tadi.
        $deficiencyIds = [];
        foreach ($buckets['weaknesses'] as $w) {
            $isRed = ($w['rag'] ?? '') === 'red';
            $defId = DB::insert('deficiencies', [
                'indicator_id' => (int) $w['id'],
                'internal_audit_id' => $auditId,
                'title' => 'Kamchilik: ' . ($w['code'] !== '' ? $w['code'] . ' — ' : '') . $w['name'],
                'description' => 'Ichki audit natijasida aniqlangan kamchilik ('
                    . ($isRed ? 'talabga mos emas' : 'qisman mos') . ').',
                'cause' => 'Indikator ' . $w['rag'] . ' holatida — baho/dalil yetarli emas.',
                'result' => null,
                'severity' => $isRed ? 'high' : 'medium',
                'status' => 'open',
                'identified_by' => $auditorId,
                'identified_at' => $today,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $deficiencyIds[] = $defId;

            DB::insert('action_plans', [
                'deficiency_id' => $defId,
                'title' => 'Chora-tadbir: ' . $w['name'] . ' bo\'yicha kamchilikni bartaraf etish',
                'description' => 'Audit tavsiyasi asosida shakllantirilgan chora-tadbir.',
                'responsible_user_id' => null,
                'start_date' => $today,
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'document_id' => null,
                'result' => null,
                'status' => 'planned',
                'completed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Yetishmayotgan dalillar bo'yicha ham kamchilik yozuvlari (Deficiencies
        // moduliga oqib o'tadi) — dalil kiritilishi kerakligi qayd etiladi.
        foreach ($buckets['missing_evidence'] as $m) {
            $defId = DB::insert('deficiencies', [
                'indicator_id' => (int) $m['id'],
                'internal_audit_id' => $auditId,
                'title' => 'Yetishmayotgan dalil: ' . ($m['code'] !== '' ? $m['code'] . ' — ' : '') . $m['name'],
                'description' => 'Indikatorda tasdiqlovchi dalil (evidence) yo\'q — baholanmagan.',
                'cause' => 'Tasdiqlovchi hujjat biriktirilmagan.',
                'result' => null,
                'severity' => 'medium',
                'status' => 'open',
                'identified_by' => $auditorId,
                'identified_at' => $today,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $deficiencyIds[] = $defId;
        }

        return [
            'audit_id' => $auditId,
            'readiness' => $readiness,
            'risk' => $risk,
            'buckets' => $buckets,
            'deficiency_ids' => $deficiencyIds,
        ];
    }

    /**
     * Saqlangan JSON bo'limni massivga dekod qiladi (view uchun).
     *
     * @return array<int,mixed>
     */
    public static function decode(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private static function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
