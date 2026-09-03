<?php
/**
 * Ilmiy rahbarlar ro'yxati (item 7) — samaradorlik ko'rsatkichi bilan.
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $supervisors
 */
$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Ilmiy rahbarlar</span></nav>
    <h1 class="page-title">Ilmiy rahbarlar</h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
        <h3>Ro'yxat (<?= count($supervisors) ?>)</h3>
        <?php if (\App\Core\Auth::can('supervisors.create')): ?>
            <a class="btn btn-primary" href="/supervisors/create">+ Yangi rahbar</a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>F.I.Sh.</th><th>Ilmiy daraja</th><th>Unvon</th><th>Kafedra</th><th>Doktorantlar</th><th>Samaradorlik</th></tr>
            </thead>
            <tbody>
                <?php foreach ($supervisors as $s): ?>
                    <?php $eff = $s['effectiveness']; $effRag = $eff === null ? 'grey' : ($eff >= 80 ? 'green' : ($eff >= 50 ? 'yellow' : 'red')); ?>
                    <tr>
                        <td><a href="/supervisors/<?= e($s['id']) ?>"><?= e($s['full_name']) ?></a></td>
                        <td><?= e($s['academic_degree'] ?? '—') ?></td>
                        <td><?= e($s['academic_title'] ?? '—') ?></td>
                        <td><?= e($s['department_name'] ?? '—') ?></td>
                        <td><?= e((int) $s['student_count']) ?></td>
                        <td><span class="badge badge-<?= e($effRag) ?>"><?= $eff === null ? 'Ma\'lumot yo\'q' : e(round($eff)) . '%' ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
