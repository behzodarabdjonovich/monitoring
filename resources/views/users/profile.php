<?php
/**
 * @var \App\Core\View $this
 * @var array<string,mixed> $profile
 * @var bool $twofaEnabled
 */
$this->layout('layouts.app');
?>

<div class="page-header">
    <nav class="breadcrumb">
        <span>Profil</span>
    </nav>
    <h1 class="page-title">Mening profilim</h1>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <tbody>
                <tr>
                    <th>F.I.Sh.</th>
                    <td><?= e($profile['full_name'] ?? '—') ?></td>
                </tr>

                <tr>
                    <th>Login</th>
                    <td><?= e($profile['username'] ?? '—') ?></td>
                </tr>

                <tr>
                    <th>Rol</th>
                    <td>
                        <?= e($profile['role_title'] ?? $profile['role_name'] ?? '—') ?>
                    </td>
                </tr>

                <tr>
                    <th>Holat</th>
                    <td>
                        <?php if ((int) ($profile['is_blocked'] ?? 0) === 1): ?>
                            <span class="badge rag-red">Bloklangan</span>
                        <?php elseif ((int) ($profile['is_active'] ?? 1) === 0): ?>
                            <span class="badge rag-grey">Nofaol</span>
                        <?php else: ?>
                            <span class="badge rag-green">Faol</span>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th>2FA</th>
                    <td>
                        <?= !empty($profile['twofa_secret']) ? 'Yoqilgan' : 'O‘chirilgan' ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
