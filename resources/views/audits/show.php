<?php
/**
 * Ichki akkreditatsiya auditi hisoboti (item 13) — report-style page.
 * Avtomatik shakllangan bo'limlar: kuchli tomonlar, kamchiliklar,
 * bajarilmagan indikatorlar, yetishmayotgan dalillar, xavf darajasi,
 * tavsiyalar, chora-tadbirlar rejasi, akkreditatsiyaga tayyorlik foizi.
 *
 * @var \App\Core\View $this
 * @var array<string,mixed> $audit
 * @var array<int,array<string,mixed>> $strengths
 * @var array<int,array<string,mixed>> $weaknesses
 * @var array<int,array<string,mixed>> $unmet
 * @var array<int,array<string,mixed>> $missing
 * @var array<int,string> $recommendations
 * @var array<int,array<string,mixed>> $deficiencies
 * @var array<string,string> $riskLabels
 */
$this->layout('layouts.app');
$risk = $audit['risk_level'] ?? 'unknown';
$riskRag = ['low' => 'green', 'medium' => 'yellow', 'high' => 'red', 'unknown' => 'grey'];
$rag = $riskRag[$risk] ?? 'grey';
$p = $audit['readiness_index'];

$indicatorList = function (array $items) {
    if ($items === []) {
        echo '<p class="text-muted">Yo\'q.</p>';
        return;
    }
    echo '<ul>';
    foreach ($items as $it) {
        $code = (string) ($it['code'] ?? '');
        $name = (string) ($it['name'] ?? '');
        $id = (int) ($it['id'] ?? 0);
        echo '<li><a href="/indicators/' . e($id) . '">' . e(trim($code . ' ' . $name)) . '</a></li>';
    }
    echo '</ul>';
};
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/audits">Ichki audit</a> / <span><?= e($audit['title']) ?></span></nav>
    <h1 class="page-title"><?= e($audit['title']) ?></h1>
</div>

<div class="card hero-card hero-<?= e($rag) ?>">
    <div class="hero-body">
        <h2 class="hero-title">Akkreditatsiyaga tayyorlik: <?= $p === null ? 'Baholanmagan' : e(round((float) $p)) . '%' ?>
            <span class="badge badge-<?= e($rag) ?>"><?= e($riskLabels[$risk] ?? $risk) ?></span>
        </h2>
        <div class="progress hero-progress" role="progressbar" aria-valuenow="<?= e($p === null ? 0 : round((float) $p)) ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar rag-fill-<?= e($rag) ?>" style="width: <?= e($p === null ? 0 : max(0, min(100, (float) $p))) ?>%"></div>
        </div>
        <p>
            Ixtisoslik: <strong><?= e($audit['specialty_name'] ?? '—') ?></strong> &nbsp;|&nbsp;
            Sana: <?= e($audit['audit_date'] ?? '—') ?> &nbsp;|&nbsp;
            Auditor: <?= e($audit['auditor_name'] ?? '—') ?>
        </p>
    </div>
</div>

<div class="card">
    <h3>Audit xulosasi</h3>
    <p><?= e($audit['summary'] ?? '') ?></p>
</div>

<div class="card">
    <h3>1. Kuchli tomonlar (<?= count($strengths) ?>)</h3>
    <p class="text-muted">Talabga to'liq mos (yashil) indikatorlar.</p>
    <?php $indicatorList($strengths); ?>
</div>

<div class="card">
    <h3>2. Kamchiliklar (<?= count($weaknesses) ?>)</h3>
    <p class="text-muted">Talabga mos emas / qisman mos (qizil/sariq) indikatorlar.</p>
    <?php $indicatorList($weaknesses); ?>
</div>

<div class="card">
    <h3>3. Bajarilmagan indikatorlar (<?= count($unmet) ?>)</h3>
    <p class="text-muted">Talabga mos emas (qizil) indikatorlar.</p>
    <?php $indicatorList($unmet); ?>
</div>

<div class="card">
    <h3>4. Yetishmayotgan dalillar (<?= count($missing) ?>)</h3>
    <p class="text-muted">Tasdiqlovchi dalil (evidence) biriktirilmagan / baholanmagan (kulrang) indikatorlar.</p>
    <?php $indicatorList($missing); ?>
</div>

<div class="card">
    <h3>5. Xavf darajasi</h3>
    <p><span class="badge badge-<?= e($rag) ?>"><?= e($riskLabels[$risk] ?? $risk) ?></span> — tayyorlik indeksi bandidan aniqlangan.</p>
</div>

<div class="card">
    <h3>6. Tavsiyalar</h3>
    <?php if ($recommendations === []): ?>
        <p class="text-muted">Tavsiya yo'q.</p>
    <?php else: ?>
        <ol><?php foreach ($recommendations as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ol>
    <?php endif; ?>
</div>

<div class="card">
    <h3>7. Chora-tadbirlar rejasi (<?= count($deficiencies) ?>)</h3>
    <p class="text-muted">Audit natijasida aniqlangan kamchiliklar Kamchiliklar moduliga oqib o'tadi.</p>
    <?php if ($deficiencies === []): ?>
        <p class="text-muted">Kamchilik shakllanmadi.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($deficiencies as $d): ?>
                <li><a href="/deficiencies/<?= e($d['id']) ?>"><?= e($d['title']) ?></a> — <?= e($d['severity']) ?> / <?= e($d['status']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h3>8. Akkreditatsiyaga tayyorlik foizi</h3>
    <p class="hero-title"><?= $p === null ? 'Baholanmagan' : e(round((float) $p, 1)) . '%' ?></p>
</div>
