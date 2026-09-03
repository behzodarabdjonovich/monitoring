<?php
/**
 * Ilmiy rahbar yaratish/tahrirlash formasi (item 7).
 *
 * @var \App\Core\View $this
 * @var array<string,mixed>|null $supervisor
 * @var array<int,array<string,mixed>> $departments
 * @var array<int,array<string,mixed>> $specialties
 */
use App\Core\Csrf;

$this->layout('layouts.app');
$isEdit = $supervisor !== null;
$action = $isEdit ? '/supervisors/' . $supervisor['id'] : '/supervisors';
$v = fn (string $k) => e($supervisor[$k] ?? '');
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/supervisors">Ilmiy rahbarlar</a> / <span><?= $isEdit ? 'Tahrirlash' : 'Yangi' ?></span></nav>
    <h1 class="page-title"><?= $isEdit ? 'Ilmiy rahbarni tahrirlash' : 'Yangi ilmiy rahbar' ?></h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="<?= e($action) ?>">
        <?= Csrf::field() ?>
        <div class="form-group"><label for="full_name">F.I.Sh. *</label><input type="text" id="full_name" name="full_name" value="<?= $v('full_name') ?>" required></div>
        <div class="form-group"><label for="academic_degree">Ilmiy daraja</label><input type="text" id="academic_degree" name="academic_degree" value="<?= $v('academic_degree') ?>"></div>
        <div class="form-group"><label for="academic_title">Unvon</label><input type="text" id="academic_title" name="academic_title" value="<?= $v('academic_title') ?>"></div>
        <div class="form-group">
            <label for="department_id">Kafedra</label>
            <select id="department_id" name="department_id"><option value="">—</option>
                <?php foreach ($departments as $d): ?><option value="<?= e($d['id']) ?>" <?= (string) ($supervisor['department_id'] ?? '') === (string) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="specialty_id">Ixtisoslik</label>
            <select id="specialty_id" name="specialty_id"><option value="">—</option>
                <?php foreach ($specialties as $sp): ?><option value="<?= e($sp['id']) ?>" <?= (string) ($supervisor['specialty_id'] ?? '') === (string) $sp['id'] ? 'selected' : '' ?>><?= e($sp['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label for="research_field">Ilmiy yo'nalish</label><input type="text" id="research_field" name="research_field" value="<?= $v('research_field') ?>"></div>
        <div class="form-group"><label for="meetings_note">Uchrashuvlar</label><textarea id="meetings_note" name="meetings_note" rows="2"><?= $v('meetings_note') ?></textarea></div>
        <div class="form-group"><label for="assignments_note">Berilgan topshiriqlar</label><textarea id="assignments_note" name="assignments_note" rows="2"><?= $v('assignments_note') ?></textarea></div>
        <div class="form-group"><label for="approvals_note">Tasdiqlashlar</label><textarea id="approvals_note" name="approvals_note" rows="2"><?= $v('approvals_note') ?></textarea></div>
        <button type="submit" class="btn btn-primary">Saqlash</button>
    </form>
</div>
