<?php
/**
 * Ilmiy natija qo'shish formasi (item 6).
 *
 * Har natija turi (TYPES lookup) tanlanadi; doktorant va/yoki ilmiy rahbarga
 * bog'lanadi; tasdiqlovchi PDF/JPG/PNG fayl YOKI havola (URL) biriktiriladi.
 *
 * @var \App\Core\View $this
 * @var array<string,string> $types
 * @var array<int,array<string,mixed>> $students
 * @var array<int,array<string,mixed>> $supervisors
 */
use App\Core\Csrf;

$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/results">Ilmiy natijalar</a> / <span>Yangi</span></nav>
    <h1 class="page-title">Yangi ilmiy natija</h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="/results" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <div class="form-group">
            <label for="result_type">Natija turi *</label>
            <select id="result_type" name="result_type" required>
                <?php foreach ($types as $key => $label): ?>
                    <option value="<?= e($key) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="title">Sarlavha *</label>
            <input type="text" id="title" name="title" required maxlength="255">
        </div>
        <?php if (\App\Core\Auth::role() !== 'doctoral_student'): ?>
    <div class="form-group">
        <label for="student_id">Doktorant</label>
        <select id="student_id" name="student_id">
            <option value="">—</option>
            <?php foreach ($students as $s): ?>
                <option value="<?= e($s['id']) ?>"><?= e($s['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
<?php endif; ?>
        <div class="form-group">
            <label for="supervisor_id">Ilmiy rahbar</label>
            <select id="supervisor_id" name="supervisor_id">
                <option value="">—</option>
                <?php foreach ($supervisors as $sup): ?>
                    <option value="<?= e($sup['id']) ?>"><?= e($sup['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="achieved_at">Sana</label>
            <input type="date" id="achieved_at" name="achieved_at">
        </div>
        <div class="form-group">
            <label for="description">Izoh</label>
            <textarea id="description" name="description" rows="2"></textarea>
        </div>
        <fieldset style="border:1px solid #e2e2e2;padding:0.75rem;border-radius:6px;">
            <legend>Tasdiqlash (fayl YOKI havola)</legend>
            <div class="form-group">
                <label for="evidence_file">Tasdiqlovchi fayl (PDF/JPG/PNG)</label>
                <input type="file" id="evidence_file" name="evidence_file" accept=".pdf,.jpg,.jpeg,.png">
            </div>
            <div class="form-group">
                <label for="url">yoki havola (URL)</label>
                <input type="url" id="url" name="url" placeholder="https://...">
            </div>
        </fieldset>
        <button type="submit" class="btn btn-primary">Saqlash</button>
        <a class="btn" href="/results">Bekor qilish</a>
    </form>
</div>
