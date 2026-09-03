<?php
/**
 * Hisobotlar ro'yxati (item 14).
 *
 * @var \App\Core\View $this
 * @var array<string,array{title:string,desc:string}> $types
 */
$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Hisobotlar</span></nav>
    <h1 class="page-title">Hisobotlar</h1>
    <p class="text-muted">Har bir hisobot chop etish (print), Excel va PDF formatlarida shakllantiriladi.</p>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Hisobot</th>
                    <th>Izoh</th>
                    <th style="white-space:nowrap;">Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($types as $key => $t): ?>
                    <tr>
                        <td><a href="/reports/<?= e($key) ?>"><?= e($t['title']) ?></a></td>
                        <td class="text-muted"><?= e($t['desc']) ?></td>
                        <td style="white-space:nowrap;">
                            <a class="btn btn-sm" href="/reports/<?= e($key) ?>">Ko'rish</a>
                            <a class="btn btn-sm" href="/reports/<?= e($key) ?>?format=print" target="_blank" rel="noopener">Chop etish</a>
                            <a class="btn btn-sm" href="/reports/<?= e($key) ?>?format=excel">Excel</a>
                            <a class="btn btn-sm" href="/reports/<?= e($key) ?>?format=pdf">PDF</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
