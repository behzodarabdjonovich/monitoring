<?php

namespace App\Models;

use App\Core\DB;

/**
 * Bildirishnomalar (item 15).
 *
 * Foydalanuvchining shaxsiy kabinetida ko'rsatiladigan avtomatik
 * ogohlantirishlar. Generator (generate()) joriy ma'lumotlardan hodisalarni
 * hisoblaydi:
 *   - vazifa muddati yaqinlashmoqda (7 kun qoldi);
 *   - indikatorda dalil yetishmayapti;
 *   - ixtisoslikda N ta bajarilmagan indikator;
 *   - ilmiy rahbar tasdig'ini kutayotgan hujjatlar/vazifalar.
 *
 * Takroriy bildirishnoma yaratmaslik uchun (type + link) bo'yicha tekshiriladi.
 */
final class Notification
{
    /** Vazifa muddati ogohlantirish oynasi (kun). */
    public const DEADLINE_DAYS = 7;

    /** Ixtisoslikda ogohlantirish uchun bajarilmagan indikator chegarasi. */
    public const UNMET_THRESHOLD = 1;

    /**
     * Foydalanuvchining bildirishnomalari.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function forUser(int $userId, bool $onlyUnread = false): array
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = :uid';
        if ($onlyUnread) {
            $sql .= ' AND is_read = 0';
        }
        $sql .= ' ORDER BY is_read, created_at DESC, id DESC';
        return DB::select($sql, ['uid' => $userId]);
    }

    public static function unreadCount(int $userId): int
    {
        return (int) DB::scalar(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0',
            ['uid' => $userId]
        );
    }

    public static function markRead(int $id, int $userId): bool
    {
        DB::run(
            'UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid',
            ['id' => $id, 'uid' => $userId]
        );
        return true;
    }

    public static function markAllRead(int $userId): void
    {
        DB::run('UPDATE notifications SET is_read = 1 WHERE user_id = :uid', ['uid' => $userId]);
    }

    /**
     * Takroriy oldini olib bitta bildirishnoma yaratadi (o'qilmagan holatda
     * bir xil type+link mavjud bo'lsa yaratilmaydi).
     */
    public static function create(int $userId, string $type, string $title, ?string $body, ?string $link): bool
    {
        $exists = (int) DB::scalar(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND type = :t AND link = :l AND is_read = 0',
            ['uid' => $userId, 't' => $type, 'l' => (string) $link]
        );
        if ($exists > 0) {
            return false;
        }
        DB::insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /**
     * Joriy ma'lumotlardan bildirishnomalarni hisoblaydi va yaratadi.
     * Console (bin/console notify) yoki controller orqali chaqirilishi mumkin.
     *
     * @return int Yaratilgan bildirishnomalar soni
     */
    public static function generate(): int
    {
        $created = 0;
        $created += self::generateDeadlineNotifications();
        $created += self::generateMissingEvidenceNotifications();
        $created += self::generateUnmetSpecialtyNotifications();
        $created += self::generatePendingApprovalNotifications();
        return $created;
    }

    /**
     * Vazifa muddati yaqinlashmoqda (7 kun qoldi) — doktorant va uning ilmiy
     * rahbariga (agar user bog'langan bo'lsa).
     */
    private static function generateDeadlineNotifications(): int
    {
        $today = date('Y-m-d');
        $limit = date('Y-m-d', strtotime('+' . self::DEADLINE_DAYS . ' days'));
        $rows = DB::select(
            "SELECT t.id AS task_id, t.title, t.due_date, s.user_id AS student_user_id,
                    su.user_id AS supervisor_user_id, s.full_name AS student_name
             FROM plan_tasks t
             INNER JOIN individual_plans p ON p.id = t.plan_id
             INNER JOIN doctoral_students s ON s.id = p.student_id
             LEFT JOIN supervisors su ON su.id = s.supervisor_id
             WHERE t.due_date IS NOT NULL AND t.due_date >= :today AND t.due_date <= :limit
                   AND t.status NOT IN ('completed','supervisor_approved','finalized')",
            ['today' => $today, 'limit' => $limit]
        );
        $created = 0;
        foreach ($rows as $r) {
            $days = (int) max(0, floor((strtotime((string) $r['due_date']) - strtotime($today)) / 86400));
            $title = 'Vazifa muddati yaqinlashmoqda: ' . $days . ' kun qoldi';
            $body = 'Vazifa "' . $r['title'] . '" muddati ' . $r['due_date'] . ' sanasida tugaydi.';
            $link = '/plans';
            $type = 'task_deadline';
            foreach ([$r['student_user_id'], $r['supervisor_user_id']] as $uid) {
                if ($uid !== null && self::create((int) $uid, $type, $title, $body, $link . '#task-' . $r['task_id'])) {
                    $created++;
                }
            }
        }
        return $created;
    }

    /**
     * Indikatorda dalil yetishmayapti — akkreditatsiyaga mas'ul rollarga
     * (super_admin, research_vice_head, quality_control).
     */
    private static function generateMissingEvidenceNotifications(): int
    {
        $count = (int) DB::scalar(
            'SELECT COUNT(*) FROM accreditation_indicators i
             WHERE NOT EXISTS (SELECT 1 FROM indicator_evidence e WHERE e.indicator_id = i.id)'
        );
        if ($count === 0) {
            return 0;
        }
        $title = 'Yetishmayotgan dalillar: ' . $count . ' ta indikator';
        $body = $count . ' ta indikatorda tasdiqlovchi dalil (hujjat) biriktirilmagan.';
        return self::notifyRoles(
            ['super_admin', 'research_vice_head', 'quality_control'],
            'missing_evidence',
            $title,
            $body,
            '/reports/yetishmayotgan_dalillar'
        );
    }

    /**
     * N ta bajarilmagan (qizil) indikatori bor ixtisosliklar — mas'ul rollarga.
     */
    private static function generateUnmetSpecialtyNotifications(): int
    {
        $rows = DB::select(
            "SELECT sp.id, sp.name, COUNT(i.id) AS unmet
             FROM specialties sp
             INNER JOIN accreditation_criteria c ON c.accreditation_id = sp.accreditation_id
             INNER JOIN accreditation_indicators i ON i.criteria_id = c.id AND i.rag_status = 'red'
             WHERE sp.accreditation_id IS NOT NULL
             GROUP BY sp.id, sp.name
             HAVING COUNT(i.id) >= :thr",
            ['thr' => self::UNMET_THRESHOLD]
        );
        $created = 0;
        foreach ($rows as $r) {
            $title = 'Ixtisoslikda bajarilmagan indikatorlar: ' . (int) $r['unmet'] . ' ta';
            $body = '"' . $r['name'] . '" ixtisosligida ' . (int) $r['unmet'] . ' ta talabga mos emas (qizil) indikator mavjud.';
            $created += self::notifyRoles(
                ['super_admin', 'research_vice_head', 'quality_control'],
                'unmet_specialty',
                $title,
                $body,
                '/specialties/' . (int) $r['id']
            );
        }
        return $created;
    }

    /**
 * Doktorant bajargan va tasdiqlashni kutayotgan vazifalar —
 * Doktorantura bo'limiga bildirishnoma yuboradi.
 */
    private static function generatePendingApprovalNotifications(): int
{
    $rows = DB::select(
        "SELECT t.id AS task_id,
                t.title,
                p.id AS plan_id,
                s.full_name AS student_name
         FROM plan_tasks t
         INNER JOIN individual_plans p ON p.id = t.plan_id
         INNER JOIN doctoral_students s ON s.id = p.student_id
         WHERE t.status = 'completed'"
    );

    $created = 0;

    foreach ($rows as $r) {
        $title = 'Tasdiqlashni kutayotgan vazifa';

        $body = '"' . $r['student_name'] . '" doktorantining "' .
            $r['title'] .
            '" vazifasi bajarildi va tasdiqlashni kutmoqda.';

        $created += self::notifyRoles(
            ['doctorate_office'],
            'pending_approval',
            $title,
            $body,
            '/plans/' . (int) $r['plan_id'] . '#task-' . (int) $r['task_id']
        );
    }

    return $created;
}
