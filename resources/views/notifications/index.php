<?php
/**
 * Bildirishnomalar (item 15) — foydalanuvchi shaxsiy kabineti.
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $notifications
 * @var int $unread
 */
$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Bildirishnomalar</span></nav>
    <h1 class="page-title">Bildirishnomalar</h1>
    <p class="text-muted">O'qilmagan: <?= e($unread) ?> ta</p>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.75rem;">
        <form action="/notifications/generate" method="post" style="display:inline;">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn-primary">Bildirishnomalarni yangilash</button>
        </form>
        <?php if ($unread > 0): ?>
            <form action="/notifications/read-all" method="post" style="display:inline;">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn">Barchasini o'qilgan deb belgilash</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($notifications === []): ?>
        <p class="text-muted">Bildirishnoma yo'q.</p>
    <?php endif; ?>

    <ul class="notif-list" style="list-style:none;padding:0;margin:0;">
        <?php foreach ($notifications as $n): ?>
            <li class="notif-item<?= (int) $n['is_read'] === 0 ? ' is-unread' : '' ?>"
                style="border-bottom:1px solid #e5e7eb;padding:0.75rem 0;<?= (int) $n['is_read'] === 0 ? 'background:#f6f9ff;' : '' ?>">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;">
                    <div>
                        <strong><?= e($n['title']) ?></strong>
                        <?php if (!empty($n['body'])): ?><div class="text-muted"><?= e($n['body']) ?></div><?php endif; ?>
                        <small class="text-muted"><?= e($n['created_at']) ?></small>
                        <?php if (!empty($n['link'])): ?>
                            <div><a href="<?= e($n['link']) ?>">Batafsil ko'rish</a></div>
                        <?php endif; ?>
                    </div>
                    <?php if ((int) $n['is_read'] === 0): ?>
                        <form action="/notifications/<?= e($n['id']) ?>/read" method="post" style="flex-shrink:0;">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-sm">O'qildi</button>
                        </form>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
