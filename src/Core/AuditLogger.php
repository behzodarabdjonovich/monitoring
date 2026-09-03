<?php

namespace App\Core;

/**
 * O'zgarmas (immutable) audit jurnali. create/update/upload/approve/delete
 * kabi muhim amallarda audit_logs jadvaliga yozuv qo'shadi. Yozuvlar faqat
 * qo'shiladi (INSERT); yangilash yoki o'chirish ko'zda tutilmagan.
 */
final class AuditLogger
{
    /**
     * Audit yozuvini qo'shadi.
     *
     * @param string      $action     create|update|upload|approve|delete|login|logout
     * @param string|null $entityType Obyekt turi (masalan "users")
     * @param int|null    $entityId   Obyekt id'si
     * @param array|null  $oldValues  Eski qiymatlar (JSON sifatida saqlanadi)
     * @param array|null  $newValues  Yangi qiymatlar (JSON sifatida saqlanadi)
     */
    public static function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
        ?string $ip = null
    ): int {
        return DB::insert('audit_logs', [
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $ip,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
