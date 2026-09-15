<?php

/** @var \App\Core\View $this */

$this->layout('layouts.auth');
?>

<h1 class="auth-title">Yangi parol o‘rnating</h1>
<p class="auth-subtitle">Davom etish uchun vaqtinchalik parolingizni almashtiring.</p>

<form class="auth-form" action="/doktorant/change-password" method="post">
    <?= \App\Core\Csrf::field() ?>

    <div class="form-group">
        <label for="password">Yangi parol</label>
        <input type="password" id="password" name="password" required autocomplete="new-password">
    </div>

    <div class="form-group">
        <label for="password_confirmation">Yangi parolni takrorlang</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-primary btn-block">Parolni saqlash</button>
</form>
