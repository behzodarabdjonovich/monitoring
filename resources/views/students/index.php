<?php
/**
 * Doktorantlar ro'yxati (item 4) — qidiruv + filtr + faoliyat foizi.
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $students
 * @var array<string,string> $filters
 * @var array<int,array<string,mixed>> $specialties
 * @var array<int,array<string,mixed>> $departments
 * @var array<string,string> $types
 * @var array<string,string> $statuses
 */
$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Doktorantlar</span></nav>
    <h1 class="page-title">Doktorantlar</h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <form method="get" action="/students" class="filter-grid">
        <div class="filter-field form-group">
            <label for="q">Qidiruv (F.I.Sh. / JSHSHIR / mavzu)</label>
            <input type="text" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Qidirish...">
        </div>
        <div class="filter-field form-group">
            <label for="type">Doktorantura turi</label>
            <select id="type" name="type">
                <option value="">Barchasi</option>
                <?php foreach ($types as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= $filters['type'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field form-group">
            <label for="specialty">Ixtisoslik</label>
            <select id="specialty" name="specialty">
                <option value="">Barchasi</option>
                <?php foreach ($specialties as $sp): ?>
                    <option value="<?= e($sp['id']) ?>" <?= $filters['specialty'] === (string) $sp['id'] ? 'selected' : '' ?>><?= e($sp['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field form-group">
            <label for="status">Holat</label>
            <select id="status" name="status">
                <option value="">Barchasi</option>
                <?php foreach ($statuses as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field form-group" style="align-self: end;">
            <button type="submit" class="btn btn-primary">Filtrlash</button>
        </div>
    </form>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
        <h3>Ro'yxat (<?= count($students) ?>)</h3>
        <?php if (\App\Core\Auth::can('doctoral_students.create')): ?>
            <a class="btn btn-primary" href="/students/create">+ Yangi doktorant</a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>F.I.Sh.</th>
                    <th>Turi</th>
                    <th>Ixtisoslik</th>
                    <th>Kafedra</th>
                    <th>Ilmiy rahbar</th>
                    <th>Faoliyat</th>
                    <th>Holat</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($students === []): ?>
                    <tr><td colspan="7" class="text-muted">Doktorant topilmadi.</td></tr>
                <?php endif; ?>
                <?php foreach ($students as $s): ?>
                    <?php $pct = (float) $s['activity_percent']; ?>
                    <tr>
                        <td><a href="/students/<?= e($s['id']) ?>"><?= e($s['full_name']) ?></a></td>
                        <td><?= e($types[$s['student_type']] ?? $s['student_type']) ?></td>
                        <td><?= e($s['specialty_name'] ?? '—') ?></td>
                        <td><?= e($s['department_name'] ?? '—') ?></td>
                        <td><?= e($s['supervisor_name'] ?? '—') ?></td>
                        <td>
                            <div class="progress" style="min-width:90px;" role="progressbar" aria-valuenow="<?= e(round($pct)) ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar rag-fill-<?= $pct >= 80 ? 'green' : ($pct >= 50 ? 'yellow' : 'red') ?>" style="width: <?= e(max(0, min(100, $pct))) ?>%"></div>
                            </div>
                            <small><?= e(round($pct)) ?>%</small>
                        </td>
                        <td><?= e($statuses[$s['status']] ?? $s['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
