<?php
/**
 * Ixtisoslik profili (item 8) — barcha maydonlar, dasturlar, rahbarlar,
 * akkreditatsiya indikatorlari linki, akkreditatsiyaga tayyorlik foizi.
 *
 * @var \App\Core\View $this
 * @var array<string,mixed> $specialty
 * @var array<int,array<string,mixed>> $programs
 * @var array<int,array<string,mixed>> $supervisors
 * @var int $studentCount
 * @var array{percent:?float,rag:string,accreditation_id:?int} $readiness
 * @var bool $canEdit
 */
$this->layout('layouts.app');
$p = $readiness['percent'];
$rag = $readiness['rag'];
$row = function (string $label, $value) {
    echo '<tr><th style="width:35%">' . e($label) . '</th><td>' . e(($value === null || $value === '') ? '—' : $value) . '</td></tr>';
};
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/specialties">Ixtisosliklar</a> / <span><?= e($specialty['name']) ?></span></nav>
    <h1 class="page-title"><?= e($specialty['code'] ?? '') ?> <?= e($specialty['name']) ?></h1>
</div>

<div class="card hero-card hero-<?= e($rag) ?>">
    <div class="hero-body">
        <h2 class="hero-title">Akkreditatsiyaga tayyorlik indeksi: <?= $p === null ? 'Ma\'lumot yo\'q' : e(round($p)) . '%' ?>
            <?php if (!empty($readiness['label'])): ?><span class="badge badge-<?= e($rag) ?>"><?= e($readiness['label']) ?></span><?php endif; ?>
        </h2>
        <div class="progress hero-progress" role="progressbar" aria-valuenow="<?= e($p === null ? 0 : round($p)) ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar rag-fill-<?= e($rag) ?>" style="width: <?= e($p === null ? 0 : max(0, min(100, $p))) ?>%"></div>
        </div>
        <?php if ($readiness['accreditation_id'] !== null): ?>
            <a class="btn btn-primary" href="/accreditations/<?= e($readiness['accreditation_id']) ?>">Akkreditatsiya indikatorlari</a>
        <?php endif; ?>
        <?php if ($canEdit): ?><a class="btn btn-primary" href="/specialties/<?= e($specialty['id']) ?>/edit">Tahrirlash</a><?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>Profil</h3>
    <div class="table-wrap">
        <table class="table">
            <?php
            $row('Ixtisoslik shifri', $specialty['code']);
            $row('Nomi', $specialty['name']);
            $row('Soha', $specialty['branch']);
            $row('Mas\'ul kafedra', $specialty['department_name']);
            $row('Dastur rahbari', $specialty['program_lead_name']);
            $row('Doktorantlar soni', $studentCount);
            $row('Ilmiy salohiyat', $specialty['scientific_potential']);
            $row('Normativ hujjatlar', $specialty['normative_docs']);
            $row('Moddiy-texnik baza', $specialty['material_base']);
            $row('Ilmiy infratuzilma', $specialty['research_infrastructure']);
            $row('Xalqaro hamkorlik', $specialty['international_cooperation']);
            $row('Ilmiy natijalar', $specialty['scientific_results']);
            ?>
        </table>
    </div>
</div>

<div class="card">
    <h3>Doktorantura dasturlari (PhD/DSc)</h3>
    <?php if ($programs === []): ?>
        <p class="text-muted">Dastur yo'q.</p>
    <?php else: ?>
        <ul><?php foreach ($programs as $pr): ?><li><?= e($pr['program_type']) ?> — <?= e($pr['name']) ?> (<?= e($pr['duration_years']) ?> yil)</li><?php endforeach; ?></ul>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Ilmiy rahbarlar (<?= count($supervisors) ?>)</h3>
    <?php if ($supervisors === []): ?>
        <p class="text-muted">Rahbar biriktirilmagan.</p>
    <?php else: ?>
        <ul><?php foreach ($supervisors as $su): ?><li><a href="/supervisors/<?= e($su['id']) ?>"><?= e($su['full_name']) ?></a></li><?php endforeach; ?></ul>
    <?php endif; ?>
</div>
