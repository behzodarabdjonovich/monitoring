<?php

use App\Core\Csrf;

$this->layout('layouts.app');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Ilmiy rahbar so‘rovlari</h1>
            <p class="text-muted mb-0">
                Doktorantlar yuborgan ilmiy rahbar so‘rovlarini ko‘rib chiqing.
            </p>
        </div>
    </div>

    <?php if ($success = \App\Core\Session::flash('success')): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error = \App\Core\Session::flash('error')): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <?php if (empty($requests)): ?>

                <div class="text-center py-5 text-muted">
                    Hozircha ilmiy rahbar bo‘yicha so‘rovlar mavjud emas.
                </div>

            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                        <tr>
                            <th>Doktorant</th>
                            <th>Bo‘lim</th>
                            <th>Ixtisoslik</th>
                            <th>Taklif qilingan rahbar</th>
                            <th>Izoh</th>
                            <th>Holat</th>
                            <th>Sana</th>
                            <th style="min-width: 280px;">Amal</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($requests as $item): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($item['student_name'] ?? '') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($item['department_name'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($item['specialty_name'] ?? '-') ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($item['supervisor_name'] ?? '') ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($item['student_note'] ?? '-') ?>
                                </td>

                                <td>
                                    <?php if (($item['status'] ?? '') === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">
                                            Ko‘rib chiqilmoqda
                                        </span>

                                    <?php elseif (($item['status'] ?? '') === 'approved'): ?>
                                        <span class="badge bg-success">
                                            Tasdiqlangan
                                        </span>

                                    <?php elseif (($item['status'] ?? '') === 'rejected'): ?>
                                        <span class="badge bg-danger">
                                            Rad etilgan
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($item['status'] ?? '-') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($item['created_at'] ?? '-') ?>
                                </td>

                                <td>
                                    <?php if (($item['status'] ?? '') === 'pending'): ?>

                                        <form
                                            method="post"
                                            action="/ilmiy-bolim/rahbar-sorovlari/<?= (int) $item['id'] ?>/approve"
                                            class="mb-2"
                                        >
                                            <?= Csrf::field() ?>

                                            <input
                                                type="text"
                                                name="review_note"
                                                class="form-control form-control-sm mb-2"
                                                placeholder="Tasdiqlash izohi (ixtiyoriy)"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-success btn-sm"
                                            >
                                                Tasdiqlash
                                            </button>
                                        </form>

                                        <form
                                            method="post"
                                            action="/ilmiy-bolim/rahbar-sorovlari/<?= (int) $item['id'] ?>/reject"
                                        >
                                            <?= Csrf::field() ?>

                                            <input
                                                type="text"
                                                name="review_note"
                                                class="form-control form-control-sm mb-2"
                                                placeholder="Rad etish sababini kiriting"
                                                required
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-sm"
                                            >
                                                Rad etish
                                            </button>
                                        </form>

                                    <?php else: ?>

                                        <?php if (!empty($item['review_note'])): ?>
                                            <div>
                                                <strong>Izoh:</strong><br>
                                                <?= htmlspecialchars($item['review_note']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($item['reviewed_by_name'])): ?>
                                            <div class="small text-muted mt-1">
                                                Ko‘rib chiqdi:
                                                <?= htmlspecialchars($item['reviewed_by_name']) ?>
                                            </div>
                                        <?php endif; ?>

                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>
