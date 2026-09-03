<?php
/**
 * Bitta hisobotni ko'rish (item 14) — eksport tugmalari bilan.
 *
 * @var \App\Core\View $this
 * @var string $reportTitle
 * @var string $reportType
 * @var array<int,string> $headers
 * @var array<int,array<int,scalar|null>> $rows
 * @var string $generatedAt
 */
$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/reports">Hisobotlar</a> / <span><?= e($reportTitle) ?></span></nav>
    <h1 class="page-title"><?= e($reportTitle) ?></h1>
    <p class="text-muted">Shakllantirilgan: <?= e($generatedAt) ?> — jami <?= count($rows) ?> ta yozuv.</p>
</div>

<div class="card">
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.75rem;">
        <a class="btn btn-primary" href="/reports/<?= e($reportType) ?>?format=print" target="_blank" rel="noopener">Chop etish (print)</a>
        <a class="btn" href="/reports/<?= e($reportType) ?>?format=excel">Excel yuklab olish</a>
        <a class="btn" href="/reports/<?= e($reportType) ?>?format=pdf">PDF yuklab olish</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= count($headers) ?>" class="text-muted">Ma'lumot topilmadi.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($row as $cell): ?><td><?= e($cell) ?></td><?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
