<?php
/**
 * Autentifikatsiya sahifalari uchun sodda (sidebar'siz) layout.
 *
 * @var \App\Core\View $this
 * @var string $content
 */
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirish — <?= e(config('app.short_name', 'ADPI Monitoring')) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-brand">ADPI Monitoring</div>
            <?= $content ?>
        </div>
        <p class="auth-footer text-muted">
            &copy; <?= e(date('Y')) ?> <?= e(config('app.institute')) ?>
        </p>
    </div>
</body>
</html>
