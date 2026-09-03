<?php
/**
 * Individual rejalar ro'yxati (item 5).
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $plans
 * @var array<string,string> $statuses
 */
$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Individual rejalar</span></nav>
    <h1 class="page-title">Individual rejalar</h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
        <h3>Rejalar (<?= count($plans) ?>)</h3>
        <?php if (\App\Core\Auth::can('individual_plans.create')): ?>
            <a class="btn btn-primary" href="/plans/create">+ Yangi reja</a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Doktorant</th><th>O'quv yili</th><th>Ilmiy rahbar</th><th>Holat</th></tr>
            </thead>
            <tbody>
                <?php if ($plans === []): ?>
                    <tr><td colspan="4" class="text-muted">Reja topilmadi.</td></tr>
                <?php endif; ?>
                <?php foreach ($plans as $p): ?>
                    <tr>
                        <td><a href="/plans/<?= e($p['id']) ?>"><?= e($p['student_name'] ?? '—') ?></a></td>
                        <td><?= e($p['academic_year']) ?></td>
                        <td><?= e($p['supervisor_name'] ?? '—') ?></td>
                        <td><?= e($statuses[$p['status']] ?? $p['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
