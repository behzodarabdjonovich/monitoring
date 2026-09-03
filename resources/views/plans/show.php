<?php
/**
 * Individual reja monitoringi (item 5): vazifalar jadvali, per-task maydonlar,
 * 5-holatli holat mashinasi bo'yicha o'tish tugmalari (rol gating), muddati
 * o'tgan vazifalar QIZIL (RAG red) sifatida ko'rsatiladi.
 *
 * @var \App\Core\View $this
 * @var array<string,mixed> $plan
 * @var array<int,array<string,mixed>> $tasks
 * @var ?float $completion
 * @var array<string,string> $statuses
 * @var array<string,string> $taskLabels
 * @var bool $canEdit
 * @var bool $canApprove
 */
use App\Core\Csrf;

$this->layout('layouts.app');
$pct = $completion === null ? 0.0 : (float) $completion;
$rag = $pct >= 80 ? 'green' : ($pct >= 50 ? 'yellow' : 'red');
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/plans">Individual rejalar</a> / <span><?= e($plan['academic_year']) ?></span></nav>
    <h1 class="page-title">Individual reja — <?= e($plan['student_name'] ?? '') ?></h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <p><strong>Doktorant:</strong> <?= e($plan['student_name'] ?? '—') ?>
        &nbsp;|&nbsp; <strong>Ilmiy rahbar:</strong> <?= e($plan['supervisor_name'] ?? '—') ?>
        &nbsp;|&nbsp; <strong>Holat:</strong> <?= e($statuses[$plan['status']] ?? $plan['status']) ?></p>
    <p><strong>Vazifalar bajarilishi:</strong> <?= e(round($pct)) ?>%</p>
    <div class="progress" role="progressbar" aria-valuenow="<?= e(round($pct)) ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar rag-fill-<?= e($rag) ?>" style="width: <?= e(max(0, min(100, $pct))) ?>%"></div>
    </div>
    <?php if ($canApprove && $plan['status'] !== 'approved'): ?>
        <form method="post" action="/plans/<?= e($plan['id']) ?>/approve" style="margin-top:0.75rem;">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-primary">Rejani tasdiqlash</button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h3>RAG holat afsonasi</h3>
    <?= $this->partial('partials.rag-legend') ?>
    <p class="text-muted" style="margin-top:0.5rem;">Muddati o'tgan (deadline o'tgan va bajarilmagan) vazifalar avtomatik qizil rangda.</p>
</div>

<div class="card">
    <h3>Vazifalar (<?= count($tasks) ?>)</h3>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Vazifa nomi</th>
                    <th>Rejalashtirilgan muddat</th>
                    <th>Amaldagi sana</th>
                    <th>Foiz</th>
                    <th>Holat</th>
                    <th>Amal</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tasks === []): ?>
                    <tr><td colspan="6" class="text-muted">Vazifa yo'q.</td></tr>
                <?php endif; ?>
                <?php foreach ($tasks as $t): ?>
                    <?php
                    $overdue = !empty($t['is_overdue']);
                    $statusKey = (string) $t['status'];
                    $statusLabel = $taskLabels[$statusKey] ?? $statusKey;
                    // Muddati o'tgan => qizil qator; aks holda holatga qarab badge.
                    $rowStyle = $overdue ? ' style="background: #fdecec;"' : '';
                    $badge = $overdue ? 'badge-red' : match ($statusKey) {
                        'finalized', 'supervisor_approved', 'completed' => 'badge-green',
                        'in_progress' => 'badge-yellow',
                        default => 'badge-grey',
                    };
                    ?>
                    <tr class="<?= $overdue ? 'is-overdue' : '' ?>"<?= $rowStyle ?>>
                        <td>
                            <?= e($t['title']) ?>
                            <?php if ($overdue): ?><span class="badge badge-red">Muddati o'tgan</span><?php endif; ?>
                            <?php if (!empty($t['student_comment'])): ?><br><small class="text-muted">Izoh: <?= e($t['student_comment']) ?></small><?php endif; ?>
                            <?php if (!empty($t['supervisor_conclusion'])): ?><br><small class="text-muted">Rahbar xulosasi: <?= e($t['supervisor_conclusion']) ?></small><?php endif; ?>
                            <?php if (!empty($t['office_note'])): ?><br><small class="text-muted">Bo'lim tasdig'i: <?= e($t['office_note']) ?></small><?php endif; ?>
                            <?php if (!empty($t['evidence_path'])): ?><br><small class="text-muted">Tasdiqlovchi hujjat yuklangan.</small><?php endif; ?>
                        </td>
                        <td><?= e($t['due_date'] ?? '—') ?></td>
                        <td><?= e($t['completed_date'] ?? '—') ?></td>
                        <td><?= e((int) ($t['progress_percent'] ?? 0)) ?>%</td>
                        <td><span class="badge <?= e($badge) ?>"><?= e($statusLabel) ?></span></td>
                        <td>
                            <?php if (!empty($t['allowed_targets'])): ?>
                                <form method="post" action="/tasks/<?= e($t['id']) ?>" enctype="multipart/form-data">
                                    <?= Csrf::field() ?>
                                    <select name="target_status">
                                        <?php foreach ($t['allowed_targets'] as $target): ?>
                                            <option value="<?= e($target) ?>"><?= e($taskLabels[$target] ?? $target) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary">O'tkazish</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($canEdit): ?>
        <h4 style="margin-top:1.25rem;">Yangi vazifa qo'shish</h4>
        <form method="post" action="/plans/<?= e($plan['id']) ?>/tasks">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label for="title">Vazifa nomi *</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="due_date">Rejalashtirilgan muddat</label>
                <input type="date" id="due_date" name="due_date">
            </div>
            <div class="form-group">
                <label for="task_type">Turi</label>
                <input type="text" id="task_type" name="task_type" placeholder="maqola / konferensiya / bob ...">
            </div>
            <div class="form-group">
                <label for="student_comment">Doktorant izohi</label>
                <textarea id="student_comment" name="student_comment" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Vazifa qo'shish</button>
        </form>
    <?php endif; ?>
</div>
