<?php
/**
 * Parolni yangilash formasi (token bilan).
 *
 * @var \App\Core\View $this
 * @var string $token
 * @var string|null $error
 */
$this->layout('layouts.auth');
$error = $error ?? null;
$token = $token ?? '';
?>
<h1 class="auth-title">Yangi parol</h1>

<?php if ($error): ?>
    <div class="alert alert-error" role="alert"><?= e($error) ?></div>
<?php endif; ?>

<form class="auth-form" action="/reset-password" method="post" novalidate>
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="form-group">
        <label for="password">Yangi parol</label>
        <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
    </div>
    <div class="form-group">
        <label for="password_confirmation">Parolni tasdiqlang</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary btn-block">Parolni yangilash</button>
</form>

<p class="auth-links">
    <a href="/login">Kirishga qaytish</a>
</p>
