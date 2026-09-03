<?php

namespace App\Models;

use App\Core\DB;

/**
 * Individual reja vazifasi (item 5) + holat mashinasi (state machine).
 *
 * ANIQ 5 holat (docs/04, user instruction):
 *   Rejalashtirilgan  (planned)
 *   -> Jarayonda      (in_progress)
 *   -> Bajarilgan     (completed)
 *   -> Rahbar tasdiqlagan (supervisor_approved)
 *   -> Yakuniy tasdiqlangan (finalized)
 *
 * Rol gating:
 *   - doktorant (doctoral_student): planned->in_progress, in_progress->completed
 *   - ilmiy rahbar (supervisor):     completed->supervisor_approved
 *   - doktorantura bo'lim (doctorate_office): supervisor_approved->finalized
 *   - super_admin / research_vice_head: barcha o'tishlar (nazorat).
 *
 * Muddati o'tgan (overdue) vazifa: due_date o'tgan VA holat completed/
 * supervisor_approved/finalized emas => qizil (RAG red) sifatida ko'rsatiladi.
 * Seed'da bunday vazifalar 'overdue' holatida saqlanadi; mashinada u
 * 'planned' bilan bir xil boshlang'ich holat sifatida qabul qilinadi.
 */
final class PlanTask
{
    public const PLANNED = 'planned';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';
    public const SUPERVISOR_APPROVED = 'supervisor_approved';
    public const FINALIZED = 'finalized';
    /** Seed/legacy: muddati o'tgan bajarilmagan vazifa (planned bilan teng). */
    public const OVERDUE = 'overdue';

    /** Kanonik holat tartibi (ketma-ketlik). */
    public const STATES = [
        self::PLANNED,
        self::IN_PROGRESS,
        self::COMPLETED,
        self::SUPERVISOR_APPROVED,
        self::FINALIZED,
    ];

    /** O'zbekcha holat yorliqlari. */
    public const LABELS = [
        self::PLANNED => 'Rejalashtirilgan',
        self::IN_PROGRESS => 'Jarayonda',
        self::COMPLETED => 'Bajarilgan',
        self::SUPERVISOR_APPROVED => 'Rahbar tasdiqlagan',
        self::FINALIZED => 'Yakuniy tasdiqlangan',
        self::OVERDUE => 'Muddati o\'tgan',
    ];

    /**
     * Ruxsat etilgan o'tishlar: joriy holat => [maqsad holatlar].
     * Faqat oldinga (ketma-ket) siljish mumkin.
     *
     * @return array<string,string[]>
     */
    public static function transitions(): array
    {
        return [
            self::PLANNED => [self::IN_PROGRESS],
            self::OVERDUE => [self::IN_PROGRESS],
            self::IN_PROGRESS => [self::COMPLETED],
            self::COMPLETED => [self::SUPERVISOR_APPROVED],
            self::SUPERVISOR_APPROVED => [self::FINALIZED],
            self::FINALIZED => [],
        ];
    }

    /**
     * Berilgan rol qaysi maqsad holatga o'tkaza oladi.
     * super_admin va research_vice_head barcha o'tishlarga ruxsat.
     *
     * @return array<string,string[]> rol => ruxsat etilgan maqsad holatlar
     */
    public static function roleTargets(): array
    {
        return [
            'doctoral_student' => [self::IN_PROGRESS, self::COMPLETED],
            'supervisor' => [self::SUPERVISOR_APPROVED],
            'department_head' => [self::SUPERVISOR_APPROVED],
            'doctorate_office' => [self::FINALIZED],
        ];
    }

    /**
     * O'tish holat mashinasi bo'yicha ruxsat etilganmi (roldan mustaqil)?
     */
    public static function canTransition(string $from, string $to): bool
    {
        $map = self::transitions();
        return in_array($to, $map[$from] ?? [], true);
    }

    /**
     * Berilgan rol ushbu o'tishni amalga oshira oladimi (holat mashinasi +
     * rol gating).
     */
    public static function roleCanTransition(?string $role, string $from, string $to): bool
    {
        if (!self::canTransition($from, $to)) {
            return false;
        }
        // To'liq nazorat rollari — istalgan yaroqli o'tish.
        if (in_array($role, ['super_admin', 'research_vice_head'], true)) {
            return true;
        }
        $targets = self::roleTargets()[$role] ?? [];
        return in_array($to, $targets, true);
    }

    /**
     * Vazifa muddati o'tganmi (deadline o'tgan va bajarilmagan/tasdiqlanmagan)?
     *
     * @param array $task plan_tasks yozuvi
     */
    public static function isOverdue(array $task, ?int $now = null): bool
    {
        $now = $now ?? time();
        $due = $task['due_date'] ?? null;
        if ($due === null || $due === '') {
            return false;
        }
        $status = (string) ($task['status'] ?? self::PLANNED);
        $doneStates = [self::COMPLETED, self::SUPERVISOR_APPROVED, self::FINALIZED];
        if (in_array($status, $doneStates, true)) {
            return false;
        }
        return strtotime($due) < $now;
    }

    /**
     * Vazifani qidiradi.
     */
    public static function find(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM plan_tasks WHERE id = :id', ['id' => $id]);
    }

    /**
     * Reja bo'yicha vazifalar.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function forPlan(int $planId): array
    {
        return DB::select('SELECT * FROM plan_tasks WHERE plan_id = :pid ORDER BY id', ['pid' => $planId]);
    }

    /**
     * Reja bajarilish foizi: vazifalar bajarilish foizining o'rtachasi.
     * Vazifa yo'q bo'lsa null.
     *
     * @param array<int,array<string,mixed>> $tasks
     */
    public static function planCompletionPercent(array $tasks): ?float
    {
        if ($tasks === []) {
            return null;
        }
        $sum = 0.0;
        foreach ($tasks as $t) {
            $p = $t['progress_percent'];
            if ($p === null || $p === '') {
                // Bajarilgan/tasdiqlangan holatlar to'liq deb hisoblanadi.
                $p = in_array($t['status'], [self::COMPLETED, self::SUPERVISOR_APPROVED, self::FINALIZED], true) ? 100 : 0;
            }
            $sum += (float) $p;
        }
        return round($sum / count($tasks), 1);
    }
}
