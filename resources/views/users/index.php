<?php
/**
 * Foydalanuvchilar boshqaruvi (item 19) — bloklash, parol yangilashni
 * majburlash, 2FA skafoldi.
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $users
 * @var bool $twofaEnabled
 * @var bool $canManage
 */
$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Foydalanuvchilar</span></nav>
    <h1 class="page-title">Foydalanuvchilar</h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <?php if (!$twofaEnabled): ?>
        <p class="text-muted">2FA sozlamasi hozircha o'chirilgan (APP_2FA=1 muhit o'zgaruvchisi bilan yoqiladi).</p>
    <?php endif; ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>F.I.Sh.</th>
                    <th>Login</th>
                    <th>Rol</th>
                    <th>Holat</th>
                    <th>2FA</th>
                    <th>Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e($u['full_name']) ?></td>
                        <td><?= e($u['username']) ?></td>
                        <td><?= e($u['role_title'] ?? $u['role_name'] ?? '—') ?></td>
                        <td>
                            <?php if ((int) $u['is_blocked'] === 1): ?>
                                <span class="badge rag-red">Bloklangan</span>
                            <?php elseif ((int) $u['is_active'] === 0): ?>
                                <span class="badge rag-grey">Nofaol</span>
                            <?php else: ?>
                                <span class="badge rag-green">Faol</span>
                            <?php endif; ?>
                            <?php if ((int) $u['must_reset'] === 1): ?>
                                <span class="badge rag-yellow">Parol yangilanishi kerak</span>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($u['twofa_secret']) ? 'Yoqilgan' : 'O\'chirilgan' ?></td>
                        <td style="white-space:nowrap;">
                            <?php if ($canManage): ?>
                                <?php if ((int) $u['is_blocked'] === 1): ?>
                                    <form action="/users/<?= e($u['id']) ?>/unblock" method="post" style="display:inline;">
                                        <?= \App\Core\Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm">Blokdan chiqarish</button>
                                    </form>
                                <?php else: ?>
                                    <form action="/users/<?= e($u['id']) ?>/block" method="post" style="display:inline;">
                                        <?= \App\Core\Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm">Bloklash</button>
                                    </form>
                                <?php endif; ?>
                                <form action="/users/<?= e($u['id']) ?>/force-reset" method="post" style="display:inline;">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm">Parol yangilashni majburlash</button>
                                </form>
                                <?php if ($twofaEnabled): ?>
                                    <form action="/users/<?= e($u['id']) ?>/twofa" method="post" style="display:inline;">
                                        <?= \App\Core\Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm"><?= !empty($u['twofa_secret']) ? '2FA o\'chirish' : '2FA yoqish' ?></button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
