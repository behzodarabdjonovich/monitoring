<?php
/**
 * Attestatsiya ko'rish (item 4/21).
 *
 * @var \App\Core\View $this
 * @var array<string,mixed> $attestation
 * @var array<string,string> $results
 * @var bool $canApprove
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
