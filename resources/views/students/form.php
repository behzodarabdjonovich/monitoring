<?php
/**
 * Doktorant yaratish/tahrirlash formasi (item 4).
 * Fotosurat va hujjat yuklash FileStorage orqali (multipart/form-data).
 *
 * @var \App\Core\View $this
 * @var array<string,mixed>|null $student
 * @var array<string,string> $types
 * @var array<string,string> $statuses
 * @var array<int,array<string,mixed>> $specialties
 * @var array<int,array<string,mixed>> $departments
 * @var array<int,array<string,mixed>> $programs
 * @var array<int,array<string,mixed>> $supervisors
 */
use App\Core\Csrf;

$this->layout('layouts.app');
$isEdit = $student !== null;
$action = $isEdit ? '/students/' . $student['id'] : '/students';
$v = fn (string $k) => e($student[$k] ?? '');
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/students">Doktorantlar</a> / <span><?= $isEdit ? 'Tahrirlash' : 'Yangi' ?></span></nav>
    <h1 class="page-title"><?= $isEdit ? 'Doktorantni tahrirlash' : 'Yangi doktorant' ?></h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
        <?= Csrf::field() ?>

        <div class="form-group">
            <label for="full_name">F.I.Sh. *</label>
            <input type="text" id="full_name" name="full_name" value="<?= $v('full_name') ?>" required>
        </div>

        <div class="form-group">
            <label for="national_id">JSHSHIR / ichki identifikator</label>
            <input type="text" id="national_id" name="national_id" value="<?= $v('national_id') ?>">
        </div>

        <div class="form-group">
            <label for="student_type">Doktorantura turi *</label>
            <select id="student_type" name="student_type" required>
                <?php foreach ($types as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= ($student['student_type'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="enrollment_year">Qabul yili</label>
            <input type="number" id="enrollment_year" name="enrollment_year" value="<?= $v('enrollment_year') ?>">
        </div>

        <div class="form-group">
            <label for="course_stage">Bosqichi / kursi</label>
            <input type="number" id="course_stage" name="course_stage" value="<?= $v('course_stage') ?>">
        </div>

        <div class="form-group">
            <label for="specialty_id">Ixtisoslik</label>
            <select id="specialty_id" name="specialty_id">
                <option value="">—</option>
                <?php foreach ($specialties as $sp): ?>
                    <option value="<?= e($sp['id']) ?>" <?= (string) ($student['specialty_id'] ?? '') === (string) $sp['id'] ? 'selected' : '' ?>><?= e($sp['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="department_id">Kafedra</label>
            <select id="department_id" name="department_id">
                <option value="">—</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= e($d['id']) ?>" <?= (string) ($student['department_id'] ?? '') === (string) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="program_id">Dastur</label>
            <select id="program_id" name="program_id">
                <option value="">—</option>
                <?php foreach ($programs as $pr): ?>
                    <option value="<?= e($pr['id']) ?>" <?= (string) ($student['program_id'] ?? '') === (string) $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="supervisor_id">Ilmiy rahbar</label>
            <select id="supervisor_id" name="supervisor_id">
                <option value="">—</option>
                <?php foreach ($supervisors as $su): ?>
                    <option value="<?= e($su['id']) ?>" <?= (string) ($student['supervisor_id'] ?? '') === (string) $su['id'] ? 'selected' : '' ?>><?= e($su['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="advisor_name">Ilmiy maslahatchi</label>
            <input type="text" id="advisor_name" name="advisor_name" value="<?= $v('advisor_name') ?>">
        </div>

        <div class="form-group">
            <label for="dissertation_topic">Dissertatsiya mavzusi</label>
            <textarea id="dissertation_topic" name="dissertation_topic" rows="2"><?= $v('dissertation_topic') ?></textarea>
        </div>

        <div class="form-group">
            <label for="admission_order">Qabul buyrug'i</label>
            <input type="text" id="admission_order" name="admission_order" value="<?= $v('admission_order') ?>">
        </div>

        <div class="form-group">
            <label for="study_start_date">Ta'lim boshi</label>
            <input type="date" id="study_start_date" name="study_start_date" value="<?= $v('study_start_date') ?>">
        </div>

        <div class="form-group">
            <label for="study_end_date">Ta'lim oxiri</label>
            <input type="date" id="study_end_date" name="study_end_date" value="<?= $v('study_end_date') ?>">
        </div>

        <div class="form-group">
            <label for="dissertation_percent">Dissertatsiya bajarilish foizi</label>
            <input type="number" id="dissertation_percent" name="dissertation_percent" min="0" max="100" value="<?= $v('dissertation_percent') ?>">
        </div>

        <div class="form-group">
            <label for="defense_readiness">Himoyaga tayyorgarlik holati</label>
            <input type="text" id="defense_readiness" name="defense_readiness" value="<?= $v('defense_readiness') ?>">
        </div>

        <div class="form-group">
            <label for="scientific_results_summary">Ilmiy natijalar</label>
            <textarea id="scientific_results_summary" name="scientific_results_summary" rows="2"><?= $v('scientific_results_summary') ?></textarea>
        </div>

        <div class="form-group">
            <label for="status">Holati</label>
            <select id="status" name="status">
                <?php foreach ($statuses as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= ($student['status'] ?? 'active') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="photo">Fotosurat (jpg/png)</label>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png">
        </div>

        <div class="form-group">
            <label for="document">Hujjat yuklash (pdf/jpg/png)</label>
            <input type="file" id="document" name="document" accept="application/pdf,image/jpeg,image/png">
            <input type="text" name="document_title" placeholder="Hujjat nomi (ixtiyoriy)" style="margin-top:0.5rem;">
        </div>

        <button type="submit" class="btn btn-primary">Saqlash</button>
    </form>
</div>
