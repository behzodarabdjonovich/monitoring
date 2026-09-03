<?php

namespace App\Models;

use App\Core\DB;

/**
 * Kamchilik (Deficiency) — item 12.
 *
 * To'liq zanjir: Muammo (title) -> Sabab (cause) -> Chora-tadbir (action_plans)
 * -> Mas'ul -> Boshlanish sanasi -> Yakuniy muddat -> Dalil -> Natija (result).
 *
 * Kamchilik ikki manbadan kelib chiqadi:
 *   - akkreditatsiya indikatoridan (indicator_id) — red/yellow holatdagi;
 *   - ichki auditdan (internal_audit_id).
 *
 * Chora-tadbir (action_plans) elementlari muddat holatiga qarab ranglanadi:
 *   - muddati yaqin (deadline ~7 kun ichida) => sariq (yellow);
 *   - muddati o'tgan (bajarilmagan) => qizil (red).
 */
final class Deficiency
{
    /** Yakuniy (bajarilgan/hal qilingan) chora-tadbir holatlari. */
    public const DONE_STATUSES = ['done', 'completed', 'resolved'];

    /** Kamchilik holatlari (o'zbekcha yorliqlar bilan). */
    public const STATUS_LABELS = [
        'open' => 'Ochiq',
        'in_progress' => 'Jarayonda',
        'resolved' => 'Bartaraf etilgan',
    ];

    /** Jiddiylik yorliqlari. */
    public const SEVERITY_LABELS = [
        'low' => 'Past',
        'medium' => 'O\'rta',
        'high' => 'Yuqori',
    ];

    /** Muddat yaqinlashuv oynasi (kunlarda). */
    public const DUE_SOON_DAYS = 7;

    public static function find(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM deficiencies WHERE id = :id', ['id' => $id]);
    }

    /**
     * Kamchilikni kontekst (aniqlagan foydalanuvchi, indikator, audit) bilan.
     */
    public static function findWithContext(int $id): ?array
    {
        return DB::selectOne(
            'SELECT d.*, u.full_name AS identified_by_name,
                    i.code AS indicator_code, i.name AS indicator_name, i.rag_status AS indicator_rag,
                    ia.title AS audit_title
             FROM deficiencies d
             LEFT JOIN users u ON u.id = d.identified_by
             LEFT JOIN accreditation_indicators i ON i.id = d.indicator_id
             LEFT JOIN internal_audits ia ON ia.id = d.internal_audit_id
             WHERE d.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Barcha kamchiliklar (filtr bilan) — manba, ochiq chora-tadbir soni va
     * muddat holatlari bilan.
     *
     * @param array<string,string> $f
     * @return array<int,array<string,mixed>>
     */
    public static function all(array $f = []): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($f['status'])) {
            $where[] = 'd.status = :st';
            $params['st'] = $f['status'];
        }
        if (!empty($f['source'])) {
            if ($f['source'] === 'audit') {
                $where[] = 'd.internal_audit_id IS NOT NULL';
            } elseif ($f['source'] === 'indicator') {
                $where[] = 'd.indicator_id IS NOT NULL';
            }
        }
        $rows = DB::select(
            'SELECT d.*, u.full_name AS identified_by_name,
                    i.code AS indicator_code, i.rag_status AS indicator_rag,
                    ia.title AS audit_title,
                    (SELECT COUNT(*) FROM action_plans ap WHERE ap.deficiency_id = d.id) AS action_count
             FROM deficiencies d
             LEFT JOIN users u ON u.id = d.identified_by
             LEFT JOIN accreditation_indicators i ON i.id = d.indicator_id
             LEFT JOIN internal_audits ia ON ia.id = d.internal_audit_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY d.id DESC',
            $params
        );
        foreach ($rows as &$r) {
            $r['action_plans'] = self::actionPlans((int) $r['id']);
        }
        unset($r);
        return $rows;
    }

    /**
     * Kamchilikka bog'langan chora-tadbirlar (action plans) — mas'ul nomi,
     * dalil hujjati va muddat holati (due state) bilan.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function actionPlans(int $deficiencyId, ?int $now = null): array
    {
        $rows = DB::select(
            'SELECT ap.*, u.full_name AS responsible_name, doc.title AS document_title
             FROM action_plans ap
             LEFT JOIN users u ON u.id = ap.responsible_user_id
             LEFT JOIN documents doc ON doc.id = ap.document_id
             WHERE ap.deficiency_id = :did ORDER BY ap.id',
            ['did' => $deficiencyId]
        );
        foreach ($rows as &$r) {
            $r['due_state'] = self::dueState($r, $now);
        }
        unset($r);
        return $rows;
    }

    /**
     * Chora-tadbir muddat holati (rang kaliti):
     *   'done'     — bajarilgan/hal qilingan (RAG: green);
     *   'overdue'  — muddati o'tgan va bajarilmagan (RAG: red);
     *   'due_soon' — muddati DUE_SOON_DAYS kun ichida yaqinlashadi (RAG: yellow);
     *   'normal'   — muddat uzoq yoki belgilanmagan (RAG: neytral).
     *
     * @param array<string,mixed> $plan action_plans yozuvi
     */
    public static function dueState(array $plan, ?int $now = null): string
    {
        $now = $now ?? time();
        $status = (string) ($plan['status'] ?? 'planned');
        if (in_array($status, self::DONE_STATUSES, true)) {
            return 'done';
        }
        $due = $plan['due_date'] ?? null;
        if ($due === null || $due === '') {
            return 'normal';
        }
        $dueTs = strtotime((string) $due);
        if ($dueTs === false) {
            return 'normal';
        }
        // Kunlar bo'yicha solishtirish uchun kun boshiga tekislaymiz.
        $today = strtotime(date('Y-m-d', $now));
        $dueDay = strtotime(date('Y-m-d', $dueTs));
        if ($dueDay < $today) {
            return 'overdue';
        }
        $diffDays = (int) floor(($dueDay - $today) / 86400);
        if ($diffDays <= self::DUE_SOON_DAYS) {
            return 'due_soon';
        }
        return 'normal';
    }

    /**
     * Muddat holatini RAG rang kalitiga (badge/CSS) xaritalaydi.
     */
    public static function dueRag(string $dueState): string
    {
        return match ($dueState) {
            'done' => 'green',
            'overdue' => 'red',
            'due_soon' => 'yellow',
            default => 'grey',
        };
    }
}
