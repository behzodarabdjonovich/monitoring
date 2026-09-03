<?php
/**
 * Ilmiy rahbar profili (item 7) — barcha maydonlar + rahbarlik qilayotgan
 * doktorantlar + umumiy samaradorlik ko'rsatkichi.
 *
 * @var \App\Core\View $this
 * @var array<string,mixed> $supervisor
 * @var array<int,array<string,mixed>> $students
 * @var ?float $effectiveness
 * @var bool $canEdit
 */
$this->layout('layouts.app');
$eff = $effectiveness;
$effRag = $eff === null ? 'grey' : ($eff >= 80 ? 'green' : ($eff >= 50 ? 'yellow' : 'red'));
$row = function (string $label, $value) {
    echo '<tr><th style="width:40%">' . e($label) . '</th><td>' . e(($value === null || $value === '') ? '—' : $value) . '</td></tr>';
};
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/supervisors">Ilmiy rahbarlar</a> / <span><?= e($supervisor['full_name']) ?></span></nav>
    <h1 class="page-title"><?= e($supervisor['full_name']) ?></h1>
</div>

<div class="card hero-card hero-<?= e($effRag) ?>">
    <div class="hero-body">
        <h2 class="hero-title">Umumiy samaradorlik ko'rsatkichi: <?= $eff === null ? 'Ma\'lumot yo\'q' : e(round($eff)) . '%' ?></h2>
        <div class="progress hero-progress" role="progressbar" aria-valuenow="<?= e($eff === null ? 0 : round($eff)) ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar rag-fill-<?= e($effRag) ?>" style="width: <?= e($eff === null ? 0 : max(0, min(100, $eff))) ?>%"></div>
        </div>
        <?php if ($canEdit): ?><a class="btn btn-primary" href="/supervisors/<?= e($supervisor['id']) ?>/edit">Tahrirlash</a><?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>Profil</h3>
    <div class="table-wrap">
        <table class="table">
            <?php
            $row('F.I.Sh.', $supervisor['full_name']);
            $row('Ilmiy daraja', $supervisor['academic_degree']);
            $row('Unvon', $supervisor['academic_title']);
            $row('Kafedra', $supervisor['department_name']);
            $row('Ixtisoslik', $supervisor['specialty_name']);
            $row('Ilmiy yo\'nalish', $supervisor['research_field']);
            $row('Uchrashuvlar', $supervisor['meetings_note']);
            $row('Berilgan topshiriqlar', $supervisor['assignments_note']);
            $row('Tasdiqlashlar', $supervisor['approvals_note']);
            ?>
        </table>
    </div>
</div>

<div class="card">
    <h3>Rahbarlik qilayotgan doktorantlar (<?= count($students) ?>)</h3>
    <?php if ($students === []): ?>
        <p class="text-muted">Doktorant biriktirilmagan.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($students as $s): ?>
                <li><a href="/students/<?= e($s['id']) ?>"><?= e($s['full_name']) ?></a> — <?= e($s['status']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
