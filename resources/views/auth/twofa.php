<?php
/**
 * 2FA (TOTP) tasdiqlash bosqichi (item 19). Faqat 2FA yoqilgan va twofa_secret
 * o'rnatilgan foydalanuvchi uchun ko'rsatiladi.
 *
 * @var \App\Core\View $this
 * @var string|null $error
 */
$this->layout('layouts.auth');
$error = $error ?? null;
?>
<h1 class="auth-title">Ikki bosqichli tasdiqlash</h1>
<p class="auth-subtitle">Autentifikator ilovasidagi 6 xonali kodni kiriting.</p>

<?php if ($error): ?>
    <div class="alert alert-error" role="alert"><?= e($error) ?></div>
<?php endif; ?>

<form class="auth-form" action="/login/2fa" method="post" novalidate>
    <?= \App\Core\Csrf::field() ?>
    <div class="form-group">
        <label for="code">Tasdiqlash kodi</label>
        <input type="text" id="code" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6"
               required autofocus autocomplete="one-time-code">
    </div>
    <button type="submit" class="btn btn-primary btn-block">Tasdiqlash</button>
</form>

<p class="auth-links"><a href="/login">Ortga</a></p>
