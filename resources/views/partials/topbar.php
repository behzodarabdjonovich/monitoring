<?php
/**
 * Yuqori panel (topbar): gamburger (mobil), global qidiruv, bildirishnoma
 * qo'ng'irog'i, profil menyusi.
 *
 * @var array|null $user
 */
$user = $user ?? null;
$fullName = $user['full_name'] ?? 'Foydalanuvchi';
$roleTitle = $user['role_title'] ?? '';
?>
<header class="topbar">
    <button class="topbar-toggle" id="sidebar-toggle" type="button" aria-label="Menyuni ochish/yopish" aria-controls="sidebar">
        <span aria-hidden="true">&#9776;</span>
    </button>

    <form class="topbar-search" role="search" action="/search" method="get">
        <label class="visually-hidden" for="global-search">Global qidiruv</label>
        <input type="search" id="global-search" name="q" placeholder="Qidirish..." autocomplete="off">
    </form>

    <div class="topbar-actions">
        <a class="topbar-bell" href="/notifications" aria-label="Bildirishnomalar">
            <span aria-hidden="true">&#128276;</span>
            <span class="topbar-bell-badge" id="notif-count" hidden>0</span>
        </a>

        <div class="topbar-user" id="user-menu">
            <button class="topbar-user-btn" type="button" id="user-menu-btn" aria-haspopup="true" aria-expanded="false">
                <span class="topbar-user-avatar" aria-hidden="true"><?= e(mb_substr($fullName, 0, 1)) ?></span>
                <span class="topbar-user-name">
                    <span class="topbar-user-fullname"><?= e($fullName) ?></span>
                    <span class="topbar-user-role"><?= e($roleTitle) ?></span>
                </span>
            </button>
            <div class="topbar-user-dropdown" id="user-menu-dropdown" hidden>
                <a href="/profile">Profil</a>
                <form action="/logout" method="post">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="topbar-logout">Chiqish</button>
                </form>
            </div>
        </div>
    </div>
</header>
