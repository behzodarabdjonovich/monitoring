<?php
/**
 * Asosiy layout (autentifikatsiyalangan shell).
 * Topbar (global qidiruv + bildirishnoma + profil) + chap sidebar (16 bo'lim)
 * + kontent. Barcha dinamik chiqish e() bilan ekranlanadi.
 *
 * @var \App\Core\View $this
 * @var string $content
 * @var array|null $user
 * @var string $title
 */
$title = $title ?? 'ADPI Monitoring';
$user = $user ?? \App\Core\Auth::user();
$appName = config('app.short_name', 'ADPI Monitoring');
$institute = config('app.institute', '');
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — <?= e($appName) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-shell">
    <?= $this->partial('partials.sidebar', ['active' => $active ?? '']) ?>

    <div class="app-main">
        <?= $this->partial('partials.topbar', ['user' => $user]) ?>

        <main class="app-content" id="app-content">
            <?= $this->partial('partials.placeholder-banner') ?>

            <?php $flashSuccess = \App\Core\Session::flash('success'); ?>
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success" role="status"><?= e($flashSuccess) ?></div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
