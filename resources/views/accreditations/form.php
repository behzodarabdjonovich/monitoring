<?php
/**
 * Akkreditatsiya sikli — yaratish/tahrirlash formasi.
 *
 * @var \App\Core\View $this
 * @var array<string,mixed>|null $accreditation
 */
use App\Core\Csrf;

$this->layout('layouts.app');
$active = 'accreditations';
$a = $accreditation;
$action = $a === null ? '/accreditations' : '/accreditations/' . $a['id'];
$statuses = ['planning' => 'Rejalashtirilmoqda', 'in_progress' => 'Jarayonda', 'submitted' => 'Topshirilgan', 'completed' => 'Yakunlangan'];
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/accreditations">Akkreditatsiya</a> / <span><?= $a === null ? 'Yangi' : 'Tahrirlash' ?></span></nav>
    <h1 class="page-title"><?= $a === null ? 'Yangi akkreditatsiya sikli' : 'Akkreditatsiyani tahrirlash' ?></h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="<?= e($action) ?>">
        <?= Csrf::field() ?>
        <div class="form-group"><label for="title">Nomi *</label><input type="text" id="title" name="title" maxlength="191" value="<?= e($a['title'] ?? '') ?>" required></div>
        <div class="form-group"><label for="cycle_year">Sikl (yil)</label><input type="text" id="cycle_year" name="cycle_year" maxlength="32" value="<?= e($a['cycle_year'] ?? date('Y')) ?>"></div>
        <div class="form-group"><label for="scope">Qamrov / izoh</label><textarea id="scope" name="scope" rows="3"><?= e($a['scope'] ?? '') ?></textarea></div>
        <div class="form-group">
            <label for="status">Holat</label>
            <select id="status" name="status">
                <?php foreach ($statuses as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= ($a['status'] ?? 'planning') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Saqlash</button>
        <a class="btn" href="/accreditations">Bekor qilish</a>
    </form>
</div>
