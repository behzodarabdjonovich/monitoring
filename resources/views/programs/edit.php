<?php
/**
 * @var \App\Core\View $this
 * @var array<string,mixed> $program
 * @var array<int,array<string,mixed>> $specialties
 */
$this->layout('layouts.app');
?>

<div class="page-header">
    <nav class="breadcrumb">
        <a href="/programs">Dasturlar</a> / <span>Tahrirlash</span>
    </nav>
    <h1 class="page-title">Dastur tahrirlash</h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger"><?= e($flashError) ?></div>
<?php endif; ?>

<div class="card">
    <form method="post" action="/programs/<?= e($program['id']) ?>/edit">
        <?= \App\Core\Csrf::field() ?>

        <div class="form-group">
            <label for="name">Nomi</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= e($program['name']) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="specialty_id">Ixtisoslik</label>
            <select id="specialty_id" name="specialty_id" required>
                <?php foreach ($specialties as $sp): ?>
                    <option
                        value="<?= e($sp['id']) ?>"
                        <?= (int) $sp['id'] === (int) $program['specialty_id'] ? 'selected' : '' ?>
                    >
                        <?= e($sp['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="program_type">Turi</label>
            <select id="program_type" name="program_type" required>
                <option value="PhD" <?= $program['program_type'] === 'PhD' ? 'selected' : '' ?>>PhD</option>
                <option value="DSc" <?= $program['program_type'] === 'DSc' ? 'selected' : '' ?>>DSc</option>
            </select>
        </div>

        <div class="form-group">
            <label for="duration_years">Muddat (yil)</label>
            <input
                type="number"
                id="duration_years"
                name="duration_years"
                value="<?= e($program['duration_years'] ?? '') ?>"
            >
        </div>

        <button type="submit" class="btn btn-primary">Saqlash</button>
        <a href="/programs" class="btn btn-secondary">Bekor qilish</a>
    </form>
</div>
