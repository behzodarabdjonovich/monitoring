<?php
/**
 * Kafedralar ro'yxati.
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $departments
 */
$this->layout('layouts.app');
?>

<div class="page-header">
    <nav class="breadcrumb">
        <a href="/specialties">Ixtisosliklar</a> / <span>Kafedralar</span>
    </nav>
    <h1 class="page-title">Kafedralar</h1>
</div>

<?php $flashSuccess = \App\Core\Session::flash('success'); ?>
<?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?= e($flashSuccess) ?></div>
<?php endif; ?>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?>
    <div class="alert alert-error"><?= e($flashError) ?></div>
<?php endif; ?>
<div class="card">
   
    <h3>Ro'yxat (<?= count($departments) ?>)</h3>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nomi</th>
                    <th>Kod</th>
                    <th>Ixtisosliklar</th>
                    <th>Ilmiy rahbarlar</th>
                    <th>Doktorantlar</th>
                    <th>Amal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $department): ?>
                    <tr>
                        <td><?= e($department['name']) ?></td>
                        <td><?= e($department['code'] ?? '—') ?></td>
                        <td><?= e((int) $department['specialty_count']) ?></td>
                        <td><?= e((int) $department['supervisor_count']) ?></td>
                        <td><?= e((int) $department['student_count']) ?></td>
                        <td>
                           <a
    href="/departments/<?= e($department['id']) ?>/edit"
    class="btn btn-primary"
>
    Tahrirlash
</a>
                            <form
                                method="post"
                                action="/departments/<?= e($department['id']) ?>/delete"
                                onsubmit="return confirm('Kafedrani o‘chirishni tasdiqlaysizmi?');"
                            >
                                <?= \App\Core\Csrf::field() ?>
                                <button type="submit" class="btn btn-danger">
                                    O‘chirish
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
