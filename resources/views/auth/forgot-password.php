<?php
/**
 * Parolni tiklash so'rovi formasi.
 *
 * @var \App\Core\View $this
 * @var string|null $error
 * @var string|null $success
 */
$this->layout('layouts.auth');
$error = $error ?? null;
$success = $success ?? null;
?>
<h1 class="auth-title">Parolni tiklash</h1>
<p class="auth-subtitle">Email manzilingizni kiriting.</p>

<?php if ($error): ?>
    <div class="alert alert-error" role="alert"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success" role="status"><?= e($success) ?></div>
<?php endif; ?>

<form class="auth-form" action="/forgot-password" method="post" novalidate>
    <?= \App\Core\Csrf::field() ?>
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autocomplete="email">
    </div>
    <button type="submit" class="btn btn-primary btn-block">Tiklash havolasini yuborish</button>
</form>

<p class="auth-links">
    <a href="/login">Kirishga qaytish</a>
</p>
