<?php
/**
 * Attestatsiyalar ro'yxati + yaratish (item 4/21) — doktorantga bog'langan.
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $attestations
 * @var array<string,string> $results
 * @var array<int,array<string,mixed>> $students
 * @var bool $canCreate
 * @var bool $canApprove
 */
use App\Core\Csrf;

$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Attestatsiya</span></nav>
    <h1 class="page-title">Attestatsiya</h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <h3>Ro'yxat (<?= count($attestations) ?>)</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Doktorant</th><th>Davr</th><th>Sana</th><th>Natija</th></tr></thead>
            <tbody>
                <?php if ($attestations === []): ?><tr><td colspan="4" class="text-muted">Attestatsiya yo'q.</td></tr><?php endif; ?>
                <?php foreach ($attestations as $a): ?>
                    <tr>
                        <td><a href="/attestations/<?= e($a['id']) ?>"><?= e($a['student_name'] ?? '—') ?></a></td>
                        <td><?= e($a['period']) ?></td>
                        <td><?= e($a['attestation_date'] ?? '—') ?></td>
                        <td><?= e($results[$a['result']] ?? $a['result']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($canCreate): ?>
<div class="card">
    <h3>Yangi attestatsiya</h3>
    <form method="post" action="/attestations">
        <?= Csrf::field() ?>
        <div class="form-group">
            <label for="student_id">Doktorant *</label>
            <select id="student_id" name="student_id" required><option value="">—</option>
                <?php foreach ($students as $s): ?><option value="<?= e($s['id']) ?>"><?= e($s['full_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label for="period">Davr *</label><input type="text" id="period" name="period" placeholder="2024/2025" required></div>
        <div class="form-group"><label for="attestation_date">Sana</label><input type="date" id="attestation_date" name="attestation_date"></div>
        <div class="form-group">
            <label for="result">Natija</label>
            <select id="result" name="result"><?php foreach ($results as $val => $label): ?><option value="<?= e($val) ?>"><?= e($label) ?></option><?php endforeach; ?></select>
        </div>
        <div class="form-group"><label for="commission_notes">Komissiya izohi</label><textarea id="commission_notes" name="commission_notes" rows="2"></textarea></div>
        <button type="submit" class="btn btn-primary">Saqlash</button>
    </form>
</div>
<?php endif; ?>
