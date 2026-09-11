<?php
/**
 * @var \App\Core\View $this
 * @var array<string,mixed> $department
 */
$this->layout('layouts.app');
?>

<div class="page-header">
    <nav class="breadcrumb">
        <a href="/departments">Kafedralar</a> / <span>Tahrirlash</span>
    </nav>
    <h1 class="page-title">Kafedrani tahrirlash</h1>
</div>

<div class="card">
    <?php $flashError = \App\Core\Session::flash('error'); ?>
    <?php if ($flashError): ?>
        <div class="alert alert-danger"><?= e($flashError) ?></div>
    <?php endif; ?>

    <form method="post" action="/departments/<?= e($department['id']) ?>/edit">
        <?= \App\Core\Csrf::field() ?>

        <div class="form-group">
            <label>Kafedra nomi</label>
            <input
                type="text"
                name="name"
                class="form-control"
                value="<?= e($department['name']) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label>Kod</label>
            <input
                type="text"
                name="code"
                class="form-control"
                value="<?= e($department['code'] ?? '') ?>"
            >
        </div>

        <button type="submit" class="btn btn-primary">Saqlash</button>
        <a href="/departments" class="btn btn-secondary">Bekor qilish</a>
    </form>
</div>
