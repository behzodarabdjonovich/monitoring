<?php
/**
 * Namuna (placeholder) ma'lumot ogohlantirishi. Akkreditatsiya mezon va
 * indikatorlari NAMUNA sifatida kiritilgan bo'lsa ko'rsatiladi (uydirmadan
 * qochish tamoyili UI darajasida).
 */
$hasPlaceholder = false;
try {
    $hasPlaceholder = (int) \App\Core\DB::scalar(
        'SELECT COUNT(*) FROM accreditations WHERE is_placeholder = 1'
    ) > 0;
} catch (\Throwable) {
    $hasPlaceholder = false;
}
?>
<?php if ($hasPlaceholder): ?>
    <div class="alert alert-warning placeholder-banner" role="alert">
        <strong>Diqqat:</strong> Akkreditatsiya mezonlari va indikatorlari
        <em>NAMUNA (placeholder)</em> sifatida kiritilgan. Ishlatishdan oldin
        ularni rasmiy tasdiqlangan qiymatlar bilan almashtiring.
    </div>
<?php endif; ?>
