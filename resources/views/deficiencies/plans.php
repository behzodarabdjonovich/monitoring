<?php
/**
 * Action Plan (item 12) — barcha chora-tadbirlar muddat holatiga qarab
 * ranglangan holda. Muddati yaqin => sariq, muddati o'tgan => qizil.
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $plans
 * @var bool $canEdit
 */
use App\Models\Deficiency;

$this->layout('layouts.app');
$dueLabels = ['done' => 'Bajarilgan', 'overdue' => 'Muddati o\'tgan', 'due_soon' => 'Muddati yaqin', 'normal' => 'Rejada'];
$overdue = 0;
$dueSoon = 0;
foreach ($plans as $p) {
    if ($p['due_state'] === 'overdue') { $overdue++; }
    if ($p['due_state'] === 'due_soon') { $dueSoon++; }
}
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Action Plan</span></nav>
    <h1 class="page-title">Chora-tadbirlar (Action Plan)</h1>
</div>

<div class="card">
    <p>
        Jami: <strong><?= count($plans) ?></strong> &nbsp;
        <span class="badge badge-red">Muddati o'tgan: <?= e($overdue) ?></span> &nbsp;
        <span class="badge badge-yellow">Muddati yaqin: <?= e($dueSoon) ?></span>
    </p>
</div>

<div class="card">
    <h3>Barcha chora-tadbirlar</h3>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Chora-tadbir</th>
                    <th>Kamchilik</th>
                    <th>Mas'ul</th>
                    <th>Boshlanish</th>
                    <th>Yakuniy muddat</th>
                    <th>Holat</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($plans === []): ?>
                    <tr><td colspan="6" class="text-muted">Chora-tadbir topilmadi.</td></tr>
                <?php endif; ?>
                <?php foreach ($plans as $ap): ?>
                    <?php $rag = Deficiency::dueRag($ap['due_state']); ?>
                    <tr class="due-<?= e($ap['due_state']) ?>">
                        <td><?= e($ap['title']) ?></td>
                        <td><a href="/deficiencies/<?= e($ap['deficiency_id']) ?>"><?= e($ap['deficiency_title']) ?></a></td>
                        <td><?= e($ap['responsible_name'] ?? '—') ?></td>
                        <td><?= e($ap['start_date'] ?? '—') ?></td>
                        <td>
                            <?= e($ap['due_date'] ?? '—') ?>
                            <span class="badge badge-<?= e($rag) ?>"><?= e($dueLabels[$ap['due_state']] ?? $ap['due_state']) ?></span>
                        </td>
                        <td><?= e($ap['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
