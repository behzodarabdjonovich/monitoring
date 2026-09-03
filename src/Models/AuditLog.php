<?php

namespace App\Models;

use App\Core\DB;

/**
 * Audit jurnali ko'rigi (item 17) — FAQAT o'qish.
 *
 * audit_logs jadvali o'zgarmas (immutable): AuditLogger faqat INSERT qiladi.
 * Bu model faqat ro'yxatlash va filtrlash uchun SELECT so'rovlarini beradi;
 * o'chirish yoki yangilash metodi ATAYLAB mavjud emas (item 17 talab —
 * oddiy foydalanuvchi audit ma'lumotlarini o'chira olmasin).
 */
final class AuditLog
{
    /** Ruxsat etilgan filtr kalitlari. */
    private const FILTER_KEYS = ['action', 'entity_type', 'user_id'];

    /**
     * Audit yozuvlari (foydalanuvchi nomi bilan), sahifalash bilan.
     *
     * @param array<string,string> $filters
     * @return array<int,array<string,mixed>>
     */
    public static function all(array $filters = [], int $limit = 200, int $offset = 0): array
    {
        [$where, $params] = self::whereClause($filters);
        $params['lim'] = $limit;
        $params['off'] = $offset;
        return DB::select(
            "SELECT a.*, u.full_name AS user_name, u.username AS username
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE $where
             ORDER BY a.id DESC
             LIMIT :lim OFFSET :off",
            $params
        );
    }

    /**
     * @param array<string,string> $filters
     */
    public static function count(array $filters = []): int
    {
        [$where, $params] = self::whereClause($filters);
        return (int) DB::scalar("SELECT COUNT(*) FROM audit_logs a WHERE $where", $params);
    }

    /**
     * Filtr panelidagi tanlovlar (aniq amallar/obyekt turlari).
     *
     * @return array{actions:array<int,string>,entities:array<int,string>}
     */
    public static function filterOptions(): array
    {
        $actions = array_map(
            static fn ($r) => (string) $r['action'],
            DB::select('SELECT DISTINCT action FROM audit_logs ORDER BY action')
        );
        $entities = array_map(
            static fn ($r) => (string) $r['entity_type'],
            DB::select("SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL AND entity_type <> '' ORDER BY entity_type")
        );
        return ['actions' => $actions, 'entities' => $entities];
    }

    /**
     * @param array<string,string> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private static function whereClause(array $filters): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['action'])) {
            $where[] = 'a.action = :f_action';
            $params['f_action'] = (string) $filters['action'];
        }
        if (!empty($filters['entity_type'])) {
            $where[] = 'a.entity_type = :f_entity';
            $params['f_entity'] = (string) $filters['entity_type'];
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = :f_user';
            $params['f_user'] = (int) $filters['user_id'];
        }
        return [implode(' AND ', $where), $params];
    }

    /**
     * Faqat kutilgan filtr kalitlarini oladi.
     *
     * @param array<string,mixed> $query
     * @return array<string,string>
     */
    public static function sanitizeFilters(array $query): array
    {
        $out = [];
        foreach (self::FILTER_KEYS as $key) {
            $val = trim((string) ($query[$key] ?? ''));
            if ($val !== '') {
                $out[$key] = $val;
            }
        }
        return $out;
    }
}
