<?php
/**
 * Login formasi. CSRF token majburiy; barcha chiqish e() bilan ekranlanadi.
 *
 * @var \App\Core\View $this
 * @var string|null $error
 * @var string|null $success
 * @var string $old_username
 */
$this->layout('layouts.auth');
$error = $error ?? null;
$success = $success ?? null;
$old_username = $old_username ?? '';
?>
<h1 class="auth-title">Tizimga kirish</h1>
<p class="auth-subtitle"><?= e(config('app.institute')) ?></p>

<?php if ($error): ?>
    <div class="alert alert-error" role="alert"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success" role="status"><?= e($success) ?></div>
<?php endif; ?>

<form class="auth-form" action="/login" method="post" novalidate>
    <?= \App\Core\Csrf::field() ?>

    <div class="form-group">
        <label for="username">Login yoki email</label>
        <input type="text" id="username" name="username" value="<?= e($old_username) ?>"
               required autofocus autocomplete="username">
    </div>

    <div class="form-group">
        <label for="password">Parol</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
    </div>

    <button type="submit" class="btn btn-primary btn-block">Kirish</button>
</form>

<p class="auth-links">
    <a href="/forgot-password">Parolni unutdingizmi?</a>
</p>
