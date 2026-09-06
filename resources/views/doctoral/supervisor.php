<?php

use App\Core\Csrf;

$this->layout('layouts.app');

/**
 * Doktorantning ilmiy rahbar sahifasi.
 *
 * @var \App\Core\View $this
 * @var array|null $user
 * @var array $student
 * @var array|null $currentSupervisor
 * @var array $supervisors
 * @var array|null $pendingRequest
 * @var array $requests
 */

$currentSupervisor = $currentSupervisor ?? null;
$supervisors = $supervisors ?? [];
$pendingRequest = $pendingRequest ?? null;
$requests = $requests ?? [];
?>

<div class="page-header">
    <div>
        <h1>Ilmiy rahbar</h1>
        <p class="text-muted">
            Ilmiy rahbaringizni ko‘ring yoki yangi rahbar bo‘yicha so‘rov yuboring.
        </p>
    </div>
</div>

<div class="card">
    <h2>Hozirgi ilmiy rahbar</h2>

    <?php if ($currentSupervisor): ?>
        <div class="card-grid">
            <div>
                <strong>F.I.Sh.</strong>
                <p><?= e($currentSupervisor['full_name'] ?? '—') ?></p>
            </div>

            <div>
                <strong>Ilmiy daraja</strong>
                <p><?= e($currentSupervisor['academic_degree'] ?? '—') ?></p>
            </div>

            <div>
                <strong>Ilmiy unvon</strong>
                <p><?= e($currentSupervisor['academic_title'] ?? '—') ?></p>
            </div>

            <div>
                <strong>Kafedra</strong>
                <p><?= e($currentSupervisor['department_name'] ?? '—') ?></p>
            </div>

            <div>
                <strong>Ixtisoslik</strong>
                <p><?= e($currentSupervisor['specialty_name'] ?? '—') ?></p>
            </div>

            <div>
                <strong>Ilmiy yo‘nalish</strong>
                <p><?= e($currentSupervisor['research_field'] ?? '—') ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            Sizga hozircha rasmiy ilmiy rahbar biriktirilmagan.
        </div>
    <?php endif; ?>
</div>

<?php if ($pendingRequest): ?>
    <div class="card">
        <h2>Ko‘rib chiqilayotgan so‘rov</h2>

        <p>
            <strong>Taklif qilingan ilmiy rahbar:</strong>
            <?= e($pendingRequest['supervisor_name'] ?? '—') ?>
        </p>

        <p>
            <strong>Holat:</strong>
            Ko‘rib chiqilmoqda
        </p>

        <?php if (!empty($pendingRequest['student_note'])): ?>
            <p>
                <strong>Sizning izohingiz:</strong><br>
                <?= nl2br(e($pendingRequest['student_note'])) ?>
            </p>
        <?php endif; ?>

        <div class="alert alert-info">
            Ilmiy bo‘lim ushbu so‘rovni ko‘rib chiqmoqda.
            Tasdiqlanmaguncha rasmiy ilmiy rahbar o‘zgarmaydi.
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <h2>Ilmiy rahbar bo‘yicha so‘rov yuborish</h2>

        <form method="post" action="/doktorant/ilmiy-rahbar">
           <?= Csrf::field() ?>

            <div class="form-group">
                <label for="supervisor_id">
                    Ilmiy rahbar
                </label>

                <select
                    name="supervisor_id"
                    id="supervisor_id"
                    class="form-control"
                    required
                >
                    <option value="">Rahbarni tanlang</option>

                    <?php foreach ($supervisors as $supervisor): ?>
                        <option value="<?= (int) $supervisor['id'] ?>">
                            <?= e($supervisor['full_name'] ?? '') ?>

                            <?php if (!empty($supervisor['academic_degree'])): ?>
                                — <?= e($supervisor['academic_degree']) ?>
                            <?php endif; ?>

                            <?php if (!empty($supervisor['specialty_name'])): ?>
                                — <?= e($supervisor['specialty_name']) ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="student_note">
                    Izoh
                </label>

                <textarea
                    name="student_note"
                    id="student_note"
                    class="form-control"
                    rows="4"
                    placeholder="Ilmiy rahbarni tanlash bo‘yicha qisqacha izoh yozishingiz mumkin."
                ></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                Ilmiy bo‘limga yuborish
            </button>
        </form>
    </div>
<?php endif; ?>

<div class="card">
    <h2>So‘rovlar tarixi</h2>

    <?php if ($requests === []): ?>
        <p class="text-muted">
            Hozircha ilmiy rahbar bo‘yicha so‘rov yuborilmagan.
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ilmiy rahbar</th>
                        <th>Holat</th>
                        <th>Doktorant izohi</th>
                        <th>Ilmiy bo‘lim izohi</th>
                        <th>Sana</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($requests as $item): ?>
                        <tr>
                            <td>
                                <?= e($item['supervisor_name'] ?? '—') ?>
                            </td>

                            <td>
                                <?php
                                $status = $item['status'] ?? '';

                                if ($status === 'approved') {
                                    echo 'Tasdiqlangan';
                                } elseif ($status === 'rejected') {
                                    echo 'Rad etilgan';
                                } else {
                                    echo 'Ko‘rib chiqilmoqda';
                                }
                                ?>
                            </td>

                            <td>
                                <?= e($item['student_note'] ?? '—') ?>
                            </td>

                            <td>
                                <?= e($item['review_note'] ?? '—') ?>
                            </td>

                            <td>
                                <?= e($item['created_at'] ?? '—') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
