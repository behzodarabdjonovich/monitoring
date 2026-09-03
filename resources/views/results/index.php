<?php
/**
 * Ilmiy natijalar ro'yxati (item 6).
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $results
 * @var array<string,string> $filters
 * @var array<string,string> $types
 * @var array<int,array<string,mixed>> $students
 * @var bool $canCreate
 */
$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Ilmiy natijalar</span></nav>
    <h1 class="page-title">Ilmiy natijalar</h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <form method="get" action="/results" class="filter-row" style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.75rem;">
        <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Sarlavha bo'yicha qidirish">
        <select name="type">
            <option value="">— Barcha turlar —</option>
            <?php foreach ($types as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $filters['type'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="student">
            <option value="">— Barcha doktorantlar —</option>
            <?php foreach ($students as $s): ?>
                <option value="<?= e($s['id']) ?>" <?= $filters['student'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Filtr</button>
    </form>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
        <h3>Natijalar (<?= count($results) ?>)</h3>
        <?php if ($canCreate): ?>
            <a class="btn btn-primary" href="/results/create">+ Yangi natija</a>
        <?php endif; ?>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Sarlavha</th>
                    <th>Turi</th>
                    <th>Doktorant</th>
                    <th>Ilmiy rahbar</th>
                    <th>Sana</th>
                    <th>Tasdiq</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results === []): ?>
                    <tr><td colspan="6" class="text-muted">Ilmiy natija topilmadi.</td></tr>
                <?php endif; ?>
                <?php foreach ($results as $r): ?>
                    <tr>
                        <td><?= e($r['title']) ?></td>
                        <td><?= e($types[$r['result_type']] ?? $r['result_type']) ?></td>
                        <td><?= e($r['student_name'] ?? '—') ?></td>
                        <td><?= e($r['supervisor_name'] ?? '—') ?></td>
                        <td><?= e($r['achieved_at'] ?? '—') ?></td>
                        <td>
                            <?php if (!empty($r['document_id'])): ?>
                                <a href="/documents/<?= e($r['document_id']) ?>/download">Fayl</a>
                            <?php elseif (!empty($r['url'])): ?>
                                <a href="<?= e($r['url']) ?>" target="_blank" rel="noopener noreferrer">Havola</a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
