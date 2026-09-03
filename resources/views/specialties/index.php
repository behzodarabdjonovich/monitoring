<?php
/**
 * Ixtisosliklar ro'yxati (item 8) — akkreditatsiyaga tayyorlik foizi bilan.
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $specialties
 */
$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Ixtisosliklar</span></nav>
    <h1 class="page-title">Ixtisosliklar va ta'lim dasturlari</h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;gap:0.5rem;">
        <h3>Ro'yxat (<?= count($specialties) ?>)</h3>
        <span>
            <a class="btn btn-primary" href="/programs">Dasturlar</a>
            <?php if (\App\Core\Auth::can('specialties.create')): ?><a class="btn btn-primary" href="/specialties/create">+ Yangi ixtisoslik</a><?php endif; ?>
        </span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Shifr</th><th>Nomi</th><th>Mas'ul kafedra</th><th>Doktorantlar</th><th>Rahbarlar</th><th>Akkreditatsiyaga tayyorlik</th></tr>
            </thead>
            <tbody>
                <?php foreach ($specialties as $sp): ?>
                    <?php $p = $sp['readiness_percent']; ?>
                    <tr>
                        <td><?= e($sp['code'] ?? '—') ?></td>
                        <td><a href="/specialties/<?= e($sp['id']) ?>"><?= e($sp['name']) ?></a></td>
                        <td><?= e($sp['department_name'] ?? '—') ?></td>
                        <td><?= e((int) $sp['student_count']) ?></td>
                        <td><?= e((int) $sp['supervisor_count']) ?></td>
                        <td><span class="badge badge-<?= e($sp['readiness_rag']) ?>"><?= $p === null ? 'Ma\'lumot yo\'q' : e(round($p)) . '%' ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
