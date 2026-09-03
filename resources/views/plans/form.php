<?php
/**
 * Individual reja yaratish/tahrirlash formasi (item 5).
 *
 * @var \App\Core\View $this
 * @var array<string,mixed>|null $plan
 * @var array<int,array<string,mixed>> $students
 * @var array<int,array<string,mixed>> $supervisors
 * @var array<string,string> $statuses
 */
use App\Core\Csrf;

$this->layout('layouts.app');
$isEdit = $plan !== null;
$action = $isEdit ? '/plans/' . $plan['id'] : '/plans';
$v = fn (string $k) => e($plan[$k] ?? '');
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/plans">Individual rejalar</a> / <span><?= $isEdit ? 'Tahrirlash' : 'Yangi' ?></span></nav>
    <h1 class="page-title"><?= $isEdit ? 'Rejani tahrirlash' : 'Yangi reja' ?></h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="<?= e($action) ?>">
        <?= Csrf::field() ?>
        <div class="form-group">
            <label for="student_id">Doktorant *</label>
            <select id="student_id" name="student_id" required>
                <option value="">—</option>
                <?php foreach ($students as $s): ?>
                    <option value="<?= e($s['id']) ?>" <?= (string) ($plan['student_id'] ?? '') === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="supervisor_id">Ilmiy rahbar</label>
            <select id="supervisor_id" name="supervisor_id">
                <option value="">—</option>
                <?php foreach ($supervisors as $su): ?>
                    <option value="<?= e($su['id']) ?>" <?= (string) ($plan['supervisor_id'] ?? '') === (string) $su['id'] ? 'selected' : '' ?>><?= e($su['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="academic_year">O'quv yili *</label>
            <input type="text" id="academic_year" name="academic_year" value="<?= $v('academic_year') ?>" placeholder="2024/2025" required>
        </div>
        <div class="form-group">
            <label for="start_date">Boshlanish sanasi</label>
            <input type="date" id="start_date" name="start_date" value="<?= $v('start_date') ?>">
        </div>
        <div class="form-group">
            <label for="end_date">Tugash sanasi</label>
            <input type="date" id="end_date" name="end_date" value="<?= $v('end_date') ?>">
        </div>
        <div class="form-group">
            <label for="status">Holat</label>
            <select id="status" name="status">
                <?php foreach ($statuses as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= ($plan['status'] ?? 'draft') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Saqlash</button>
    </form>
</div>
