<?php
/**
 * Attestatsiya ko'rish (item 4/21).
 *
 * @var \App\Core\View $this
 * @var array<string,mixed> $attestation
 * @var array<string,string> $results
 * @var bool $canApprove
 * @var bool $canEdit
 * @var array<int,array<string,mixed>> $students
 */
use App\Core\Csrf;

$this->layout('layouts.app');
$row = function (string $label, $value) {
    echo '<tr><th style="width:35%">' . e($label) . '</th><td>' . e(($value === null || $value === '') ? '—' : $value) . '</td></tr>';
};
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/attestations">Attestatsiya</a> / <span><?= e($attestation['student_name'] ?? '') ?></span></nav>
    <h1 class="page-title">Attestatsiya</h1>
    <?php if ($canEdit): ?>
        <hr>
        <h3>Attestatsiyani tahrirlash</h3>
        <form method="post" action="/attestations/<?= e($attestation['id']) ?>">
            <?= Csrf::field() ?>
            <div class="form-group"><label>Doktorant</label><select name="student_id" required><?php foreach ($students as $s): ?><option value="<?= e($s['id']) ?>" <?= (int)$s['id'] === (int)$attestation['student_id'] ? 'selected' : '' ?>><?= e($s['full_name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Davr</label><input type="text" name="period" value="<?= e($attestation['period']) ?>" required></div>
            <div class="form-group"><label>Sana</label><input type="date" name="attestation_date" value="<?= e($attestation['attestation_date'] ?? '') ?>"></div>
            <div class="form-group"><label>Natija</label><select name="result"><?php foreach ($results as $key => $label): ?><option value="<?= e($key) ?>" <?= $attestation['result'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Komissiya izohi</label><textarea name="commission_notes" rows="2"><?= e($attestation['commission_notes'] ?? '') ?></textarea></div>
            <button type="submit" class="btn btn-primary">Saqlash</button>
        </form>
    <?php endif; ?>
    <?php if (\App\Core\Auth::role() === 'super_admin'): ?>
        <form method="post" action="/attestations/<?= e($attestation['id']) ?>/delete" style="margin-top:0.75rem;" onsubmit="return confirm('Attestatsiyani o‘chirishni tasdiqlaysizmi?');">
            <?= Csrf::field() ?><button type="submit" class="btn btn-danger">O‘chirish</button>
        </form>
    <?php endif; ?>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <?php
            $row('Doktorant', $attestation['student_name']);
            $row('Davr', $attestation['period']);
            $row('Sana', $attestation['attestation_date']);
            $row('Natija', $results[$attestation['result']] ?? $attestation['result']);
            $row('Komissiya izohi', $attestation['commission_notes']);
            ?>
        </table>
    </div>
    <?php if ($canApprove && $attestation['result'] !== 'ijobiy'): ?>
        <form method="post" action="/attestations/<?= e($attestation['id']) ?>/approve">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-primary">Tasdiqlash (ijobiy)</button>
        </form>
    <?php endif; ?>
</div>
