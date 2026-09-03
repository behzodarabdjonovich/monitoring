<?php
/**
 * Global qidiruv natijalari (item 16) — tur bo'yicha guruhlangan.
 *
 * @var \App\Core\View $this
 * @var string $term
 * @var array<string,mixed> $results
 * @var array<string,string> $groupLabels
 */
$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Qidiruv</span></nav>
    <h1 class="page-title">Global qidiruv</h1>
</div>

<div class="card">
    <form method="get" action="/search" class="filter-grid">
        <div class="filter-field form-group" style="flex:1;">
            <label for="q">Qidiruv so'zi</label>
            <input type="search" id="q" name="q" value="<?= e($term) ?>" placeholder="Doktorant, rahbar, ixtisoslik, hujjat yoki indikator...">
        </div>
        <div class="filter-field form-group" style="align-self:end;">
            <button type="submit" class="btn btn-primary">Qidirish</button>
        </div>
    </form>
</div>

<?php if ($term === ''): ?>
    <div class="card"><p class="text-muted">Qidiruv so'zini kiriting.</p></div>
<?php else: ?>
    <div class="card">
        <p class="text-muted">"<?= e($term) ?>" bo'yicha jami <?= e($results['total']) ?> ta natija topildi.</p>
    </div>
    <?php foreach ($groupLabels as $key => $label): ?>
        <?php $items = $results[$key] ?? []; ?>
        <?php if ($items !== []): ?>
            <div class="card">
                <h3><?= e($label) ?> (<?= count($items) ?>)</h3>
                <ul style="list-style:none;padding:0;margin:0.5rem 0 0;">
                    <?php foreach ($items as $item): ?>
                        <li style="padding:0.4rem 0;border-bottom:1px solid #eef0f3;">
                            <a href="<?= e($item['link']) ?>"><?= e($item['title']) ?></a>
                            <span class="text-muted"> — <?= e($item['subtitle']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if ((int) $results['total'] === 0): ?>
        <div class="card"><p class="text-muted">Hech narsa topilmadi.</p></div>
    <?php endif; ?>
<?php endif; ?>
