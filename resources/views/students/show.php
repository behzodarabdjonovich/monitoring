<?php
/**
 * Doktorant profili (item 4) — to'liq elektron profil + progress indikator.
 *
 * @var \App\Core\View $this
 * @var array<string,mixed> $student
 * @var float $activityPercent
 * @var array<string,mixed> $related
 * @var array<string,string> $types
 * @var array<string,string> $statuses
 * @var bool $canEdit
 */
$this->layout('layouts.app');
$pct = (float) $activityPercent;
$rag = $pct >= 80 ? 'green' : ($pct >= 50 ? 'yellow' : 'red');

$row = function (string $label, $value) {
    echo '<tr><th style="width:40%">' . e($label) . '</th><td>' . e(($value === null || $value === '') ? '—' : $value) . '</td></tr>';
};
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/students">Doktorantlar</a> / <span><?= e($student['full_name']) ?></span></nav>
    <h1 class="page-title"><?= e($student['full_name']) ?></h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<!-- Faoliyat progress indikatori -->
<div class="card hero-card hero-<?= e($rag) ?>">
    <div class="hero-body">
        <h2 class="hero-title">Doktorant faoliyati bajarilishi: <?= e(round($pct)) ?>%</h2>
        <div class="progress hero-progress" role="progressbar" aria-valuenow="<?= e(round($pct)) ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar rag-fill-<?= e($rag) ?>" style="width: <?= e(max(0, min(100, $pct))) ?>%"></div>
        </div>
        <?php if ($canEdit): ?>
            <a class="btn btn-primary" href="/students/<?= e($student['id']) ?>/edit">Profilni tahrirlash</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>Umumiy ma'lumotlar</h3>
    <div class="table-wrap">
        <table class="table">
            <?php
            $row('F.I.Sh.', $student['full_name']);
            $row('JSHSHIR / ichki identifikator', $student['national_id']);
            $row('Doktorantura turi', $types[$student['student_type']] ?? $student['student_type']);
            $row('Qabul yili', $student['enrollment_year']);
            $row('Bosqichi / kursi', $student['course_stage']);
            $row('Ixtisoslik shifri va nomi', trim(($student['specialty_code'] ?? '') . ' ' . ($student['specialty_name'] ?? '')));
            $row('Kafedra', $student['department_name']);
            $row('Dastur', trim(($student['program_type'] ?? '') . ' ' . ($student['program_name'] ?? '')));
            $row('Dissertatsiya mavzusi', $student['dissertation_topic']);
            $row('Ilmiy rahbar', $student['supervisor_name']);
            $row('Ilmiy maslahatchi', $student['advisor_name']);
            $row('Qabul buyrug\'i', $student['admission_order']);
            $row('Ta\'lim muddati', trim((string) ($student['study_start_date'] ?? '') . ' — ' . (string) ($student['study_end_date'] ?? '')));
            $row('Dissertatsiya bajarilish foizi', $student['dissertation_percent'] === null ? null : $student['dissertation_percent'] . '%');
            $row('Himoyaga tayyorgarlik holati', $student['defense_readiness']);
            $row('Holati', $statuses[$student['status']] ?? $student['status']);
            ?>
        </table>
    </div>
    <?php if (!empty($student['scientific_results_summary'])): ?>
        <p><strong>Ilmiy natijalar:</strong> <?= e($student['scientific_results_summary']) ?></p>
    <?php endif; ?>
    <?php if (!empty($student['photo_path'])): ?>
        <p class="text-muted">Fotosurat yuklangan (himoyalangan saqlash).</p>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Individual rejalar</h3>
    <?php if ($related['plans'] === []): ?>
        <p class="text-muted">Reja mavjud emas.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($related['plans'] as $p): ?>
                <li><a href="/plans/<?= e($p['id']) ?>"><?= e($p['academic_year']) ?></a> — <?= e($p['status']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Attestatsiya natijalari</h3>
    <?php if ($related['attestations'] === []): ?>
        <p class="text-muted">Attestatsiya yozuvi yo'q.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($related['attestations'] as $a): ?>
                <li><?= e($a['period']) ?> — <?= e($a['result']) ?> (<?= e($a['attestation_date']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Ilmiy maqolalar (<?= count($related['publications']) ?>)</h3>
    <?php if ($related['publications'] === []): ?>
        <p class="text-muted">Maqola yo'q.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($related['publications'] as $p): ?>
                <li><?= e($p['title']) ?> — <?= e($p['publication_type']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Konferensiyalar / seminarlar (<?= count($related['conferences']) ?>)</h3>
    <?php if ($related['conferences'] === []): ?>
        <p class="text-muted">Konferensiya yozuvi yo'q.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($related['conferences'] as $c): ?>
                <li><?= e($c['title']) ?> — <?= e($c['level']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Yuklangan hujjatlar (<?= count($related['documents']) ?>)</h3>
    <?php if ($related['documents'] === []): ?>
        <p class="text-muted">Hujjat yuklanmagan.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($related['documents'] as $d): ?>
                <li><?= e($d['title']) ?> (<?= e($d['mime_type']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
