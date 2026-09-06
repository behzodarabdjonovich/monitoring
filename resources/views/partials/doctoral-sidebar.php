<?php
$active = $active ?? '';

$sections = [
    ['dashboard', 'Kabinet', '/doktorant/dashboard'],
    ['supervisor', 'Ilmiy rahbar', '/doktorant/ilmiy-rahbar'],
    ['plans', 'Individual reja', '/doktorant/reja'],
    ['results', 'Ilmiy natijalar', '/results'],
    ['documents', 'Hujjatlar', '/documents'],
    ['attestations', 'Attestatsiya', '/attestations'],
    ['notifications', 'Bildirishnomalar', '/notifications'],
];
?>

<aside class="sidebar" id="sidebar" aria-label="Doktorant navigatsiyasi">
    <div class="sidebar-brand">
        <span class="sidebar-brand-mark" aria-hidden="true">ADPI</span>
        <span class="sidebar-brand-text">
            Doktorant<br>kabineti
        </span>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <?php foreach ($sections as [$key, $label, $url]): ?>
                <li>
                    <a
                        class="sidebar-link<?= $active === $key ? ' is-active' : '' ?>"
                        href="<?= e($url) ?>"
                        <?= $active === $key ? 'aria-current="page"' : '' ?>
                    >
                        <span class="sidebar-icon" aria-hidden="true">&#9679;</span>
                        <span class="sidebar-label"><?= e($label) ?></span>
                    </a>
                </li>
                        <?php endforeach; ?>
        </ul>
    </nav>

    <div style="padding: 16px;">
        <a href="/doktorant/logout" class="btn btn-outline-danger w-100">
            Chiqish
        </a>
    </div>
</aside>

<div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
