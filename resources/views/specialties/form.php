<?php
/**
 * Ixtisoslik yaratish/tahrirlash formasi (item 8).
 *
 * @var \App\Core\View $this
 * @var array<string,mixed>|null $specialty
 * @var array<int,array<string,mixed>> $departments
 * @var array<int,array<string,mixed>> $supervisors
 * @var array<int,array<string,mixed>> $accreditations
 */
use App\Core\Csrf;

$this->layout('layouts.app');
$isEdit = $specialty !== null;
$action = $isEdit ? '/specialties/' . $specialty['id'] : '/specialties';
$v = fn (string $k) => e($specialty[$k] ?? '');
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/specialties">Ixtisosliklar</a> / <span><?= $isEdit ? 'Tahrirlash' : 'Yangi' ?></span></nav>
    <h1 class="page-title"><?= $isEdit ? 'Ixtisoslikni tahrirlash' : 'Yangi ixtisoslik' ?></h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="<?= e($action) ?>">
        <?= Csrf::field() ?>
        <div class="form-group"><label for="code">Ixtisoslik shifri</label><input type="text" id="code" name="code" value="<?= $v('code') ?>"></div>
        <div class="form-group"><label for="name">Nomi *</label><input type="text" id="name" name="name" value="<?= $v('name') ?>" required></div>
        <div class="form-group"><label for="branch">Soha</label><input type="text" id="branch" name="branch" value="<?= $v('branch') ?>"></div>
        <div class="form-group">
            <label for="responsible_department_id">Mas'ul kafedra</label>
            <select id="responsible_department_id" name="responsible_department_id"><option value="">—</option>
                <?php foreach ($departments as $d): ?><option value="<?= e($d['id']) ?>" <?= (string) ($specialty['responsible_department_id'] ?? '') === (string) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="program_lead_supervisor_id">Dastur rahbari</label>
            <select id="program_lead_supervisor_id" name="program_lead_supervisor_id"><option value="">—</option>
                <?php foreach ($supervisors as $su): ?><option value="<?= e($su['id']) ?>" <?= (string) ($specialty['program_lead_supervisor_id'] ?? '') === (string) $su['id'] ? 'selected' : '' ?>><?= e($su['full_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="accreditation_id">Akkreditatsiya sikli (indikatorlar linki)</label>
            <select id="accreditation_id" name="accreditation_id"><option value="">—</option>
                <?php foreach ($accreditations as $a): ?><option value="<?= e($a['id']) ?>" <?= (string) ($specialty['accreditation_id'] ?? '') === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['title']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label for="scientific_potential">Ilmiy salohiyat</label><textarea id="scientific_potential" name="scientific_potential" rows="2"><?= $v('scientific_potential') ?></textarea></div>
        <div class="form-group"><label for="normative_docs">Normativ hujjatlar</label><textarea id="normative_docs" name="normative_docs" rows="2"><?= $v('normative_docs') ?></textarea></div>
        <div class="form-group"><label for="material_base">Moddiy-texnik baza</label><textarea id="material_base" name="material_base" rows="2"><?= $v('material_base') ?></textarea></div>
        <div class="form-group"><label for="research_infrastructure">Ilmiy infratuzilma</label><textarea id="research_infrastructure" name="research_infrastructure" rows="2"><?= $v('research_infrastructure') ?></textarea></div>
        <div class="form-group"><label for="international_cooperation">Xalqaro hamkorlik</label><textarea id="international_cooperation" name="international_cooperation" rows="2"><?= $v('international_cooperation') ?></textarea></div>
        <div class="form-group"><label for="scientific_results">Ilmiy natijalar</label><textarea id="scientific_results" name="scientific_results" rows="2"><?= $v('scientific_results') ?></textarea></div>
        <button type="submit" class="btn btn-primary">Saqlash</button>
    </form>
</div>
