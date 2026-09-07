<?php

/**
 * Ilmiy natija yaratish / tahrirlash formasi.
 *
 * @var \App\Core\View $this
 * @var array<string,string> $types
 * @var array<int,array<string,mixed>> $students
 * @var array<int,array<string,mixed>> $supervisors
 * @var array<string,mixed>|null $result
 */

use App\Core\Csrf;

$this->layout('layouts.app');

$isEdit = !empty($result['id']);

$action = $isEdit
    ? '/results/' . (int) $result['id'] . '/update'
    : '/results';

$pageTitle = $isEdit
    ? 'Ilmiy natijani tuzatish'
    : 'Yangi ilmiy natija';

$submitLabel = $isEdit
    ? 'Tuzatish va qayta yuborish'
    : 'Saqlash';
?>

<div class="page-header">

    <nav class="breadcrumb">
        <a href="/results">Ilmiy natijalar</a>
        /
        <span><?= e($isEdit ? 'Tahrirlash' : 'Yangi') ?></span>
    </nav>

    <h1 class="page-title">
        <?= e($pageTitle) ?>
    </h1>

</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>

<?php if ($flashError): ?>
    <div class="alert alert-error">
        <?= e($flashError) ?>
    </div>
<?php endif; ?>


<?php if ($isEdit && !empty($result['rejection_reason'])): ?>

    <div class="alert alert-error">
        <strong>Rad etish sababi:</strong>
        <?= e($result['rejection_reason']) ?>
    </div>

<?php endif; ?>


<div class="card">

    <form
        method="post"
        action="<?= e($action) ?>"
        enctype="multipart/form-data"
    >

        <?= Csrf::field() ?>


        <div class="form-group">

            <label for="result_type">
                Natija turi *
            </label>

            <select
                id="result_type"
                name="result_type"
                required
            >

                <?php foreach ($types as $key => $label): ?>

                    <option
                        value="<?= e($key) ?>"
                        <?= (($result['result_type'] ?? '') === $key) ? 'selected' : '' ?>
                    >
                        <?= e($label) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div class="form-group">

            <label for="title">
                Sarlavha *
            </label>

            <input
                type="text"
                id="title"
                name="title"
                value="<?= e($result['title'] ?? '') ?>"
                required
                maxlength="255"
            >

        </div>


        <?php if (\App\Core\Auth::role() !== 'doctoral_student'): ?>

            <div class="form-group">

                <label for="student_id">
                    Doktorant
                </label>

                <select
                    id="student_id"
                    name="student_id"
                >

                    <option value="">—</option>

                    <?php foreach ($students as $s): ?>

                        <option
                            value="<?= e($s['id']) ?>"
                            <?= ((string) ($result['student_id'] ?? '') === (string) $s['id']) ? 'selected' : '' ?>
                        >
                            <?= e($s['full_name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        <?php endif; ?>


        <?php if (\App\Core\Auth::role() !== 'doctoral_student'): ?>

            <div class="form-group">

                <label for="supervisor_id">
                    Ilmiy rahbar
                </label>

                <select
                    id="supervisor_id"
                    name="supervisor_id"
                >

                    <option value="">—</option>

                    <?php foreach ($supervisors as $sup): ?>

                        <option
                            value="<?= e($sup['id']) ?>"
                            <?= ((string) ($result['supervisor_id'] ?? '') === (string) $sup['id']) ? 'selected' : '' ?>
                        >
                            <?= e($sup['full_name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        <?php endif; ?>


        <div class="form-group">

            <label for="achieved_at">
                Sana
            </label>

            <input
                type="date"
                id="achieved_at"
                name="achieved_at"
                value="<?= e($result['achieved_at'] ?? '') ?>"
            >

        </div>


        <div class="form-group">

            <label for="description">
                Izoh
            </label>

            <textarea
                id="description"
                name="description"
                rows="3"
            ><?= e($result['description'] ?? '') ?></textarea>

        </div>


        <fieldset
            style="border:1px solid #e2e2e2;padding:0.75rem;border-radius:6px;"
        >

            <legend>
                Tasdiqlash (fayl YOKI havola)
            </legend>


            <?php if ($isEdit && !empty($result['document_id'])): ?>

                <div class="form-group">

                    <strong>Hozirgi fayl:</strong>

                    <a
                        href="/documents/<?= e($result['document_id']) ?>/download"
                        target="_blank"
                    >
                        Faylni ko‘rish
                    </a>

                </div>

            <?php endif; ?>


            <div class="form-group">

                <label for="evidence_file">
                    <?= $isEdit
                        ? 'Yangi tasdiqlovchi fayl (ixtiyoriy)'
                        : 'Tasdiqlovchi fayl (PDF/JPG/PNG)'
                    ?>
                </label>

                <input
                    type="file"
                    id="evidence_file"
                    name="evidence_file"
                    accept=".pdf,.jpg,.jpeg,.png"
                >

            </div>


            <div class="form-group">

                <label for="url">
                    yoki havola (URL)
                </label>

                <input
                    type="url"
                    id="url"
                    name="url"
                    value="<?= e($result['url'] ?? '') ?>"
                    placeholder="https://..."
                >

            </div>

        </fieldset>


        <div style="margin-top:1rem;">

            <button
                type="submit"
                class="btn btn-primary"
            >
                <?= e($submitLabel) ?>
            </button>

            <a
                class="btn"
                href="/results"
            >
                Bekor qilish
            </a>

        </div>

    </form>

</div>
