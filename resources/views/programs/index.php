<?php
/**
 * Doktorantura dasturlari ro'yxati (item 8).
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $programs
 * @var array<int,array<string,mixed>> $specialties
 */
use App\Core\Csrf;

$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/specialties">Ixtisosliklar</a> / <span>Dasturlar</span></nav>
    <h1 class="page-title">Doktorantura dasturlari</h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <h3>Dasturlar (<?= count($programs) ?>)</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Nomi</th><th>Turi</th><th>Ixtisoslik</th><th>Muddat</th></tr></thead>
            <tbody>
                <?php foreach ($programs as $pr): ?>
                    <tr><td><?= e($pr['name']) ?></td><td><?= e($pr['program_type']) ?></td><td><?= e($pr['specialty_name'] ?? '—') ?></td><td><?= e($pr['duration_years']) ?> yil</td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (\App\Core\Auth::can('specialties.create')): ?>
<div class="card">
    <h3>Yangi dastur</h3>
    <form method="post" action="/programs">
        <?= Csrf::field() ?>
        <div class="form-group"><label for="name">Nomi *</label><input type="text" id="name" name="name" required></div>
        <div class="form-group">
            <label for="specialty_id">Ixtisoslik *</label>
            <select id="specialty_id" name="specialty_id" required><option value="">—</option>
                <?php foreach ($specialties as $sp): ?><option value="<?= e($sp['id']) ?>"><?= e($sp['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="program_type">Turi *</label>
            <select id="program_type" name="program_type" required><option value="PhD">PhD</option><option value="DSc">DSc</option></select>
        </div>
        <div class="form-group"><label for="duration_years">Muddat (yil)</label><input type="number" id="duration_years" name="duration_years" value="3"></div>
        <button type="submit" class="btn btn-primary">Saqlash</button>
    </form>
</div>
<?php endif; ?>
