<?php
/** @var string|null $permission */
$this->layout('layouts.auth');
?>
<h1 class="auth-title">403</h1>
<p class="auth-subtitle">Ushbu amal uchun ruxsatingiz yo'q.</p>
<?php if (!empty($permission)): ?>
    <p class="text-muted">Talab qilingan ruxsat: <code><?= e($permission) ?></code></p>
<?php endif; ?>
<p class="auth-links"><a href="/dashboard">Bosh sahifaga qaytish</a></p>
