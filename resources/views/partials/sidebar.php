<?php
/**
 * Chap yon panel (sidebar) — docs/04 va 18-band bo'yicha ANIQ 16 ta bo'lim,
 * ANIQ tartibda. Har element ikonka (inline-SVG) + o'zbekcha matn.
 *
 * @var string $active Faol bo'lim kaliti
 */
$active = $active ?? '';

// [kalit, matn (uz), URL]
$sections = [
    ['dashboard', 'Dashboard', '/dashboard'],
    ['students', 'Doktorantlar', '/students'],
    ['supervisors', 'Ilmiy rahbarlar', '/supervisors'],
    ['specialties', 'Ixtisosliklar', '/specialties'],
    ['plans', 'Individual rejalar', '/plans'],
    ['results', 'Ilmiy natijalar', '/results'],
    ['attestations', 'Attestatsiya', '/attestations'],
    ['accreditations', 'Akkreditatsiya', '/accreditations'],
    ['documents', 'Dalillar bazasi', '/documents'],
    ['deficiencies', 'Kamchiliklar', '/deficiencies'],
    ['action-plans', 'Action Plan', '/action-plans'],
    ['audits', 'Ichki audit', '/audits'],
    ['reports', 'Hisobotlar', '/reports'],
    ['notifications', 'Bildirishnomalar', '/notifications'],
    ['users', 'Foydalanuvchilar', '/users'],
    ['settings', 'Sozlamalar', '/settings'],
];

// Audit jurnali faqat Super Admin uchun (item 17).
if (\App\Core\Auth::role() === 'super_admin') {
    $sections[] = ['audit-logs', 'Audit jurnali', '/audit-logs'];
}
?>
<aside class="sidebar" id="sidebar" aria-label="Asosiy navigatsiya">
    <div class="sidebar-brand">
        <span class="sidebar-brand-mark" aria-hidden="true">ADPI</span>
        <span class="sidebar-brand-text">Doktorantura<br>monitoringi</span>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <?php foreach ($sections as [$key, $label, $url]): ?>
                <li>
                    <a class="sidebar-link<?= $active === $key ? ' is-active' : '' ?>"
                       href="<?= e($url) ?>"
                       <?= $active === $key ? 'aria-current="page"' : '' ?>>
                        <span class="sidebar-icon" aria-hidden="true">&#9679;</span>
                        <span class="sidebar-label"><?= e($label) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
<div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
