<?php
/**
 * Akkreditatsiya sikllari ro'yxati (item 9-10) — ENG ASOSIY modul.
 * Har sikl uchun tayyorlik indeksi (%) + RAG yorliq (Tayyor / Takomillashtirish
 * kerak / Yuqori xavf) va bog'langan ixtisosliklar ko'rsatiladi.
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $accreditations
 * @var bool $canCreate
 */
$this->layout('layouts.app');
$active = 'accreditations';
?>
<div class="page-header">
    <nav class="breadcrumb" aria-label="Breadcrumb"><span>Akkreditatsiya</span></nav>
    <h1 class="page-title">Maxsus davlat akkreditatsiyasi</h1>
    <?php if ($canCreate): ?>
        <a class="btn btn-primary" href="/accreditations/create">Yangi akkreditatsiya sikli</a>
    <?php endif; ?>
</div>

<div class="card">
    <p class="text-muted">Iyerarxiya: <strong>Akkreditatsiya → Mezon → Indikator → Talab → Dalil → Baho → Kamchilik → Chora-tadbir</strong>. Har ixtisoslik uchun "Akkreditatsiyaga tayyorlik indeksi" avtomatik hisoblanadi.</p>
    <?= $this->partial('partials.rag-legend') ?>
</div>

<?php if ($accreditations === []): ?>
    <div class="card"><p class="text-muted">Akkreditatsiya sikli kiritilmagan.</p></div>
<?php else: ?>
    <?php foreach ($accreditations as $a):
        $pct = $a['readiness_percent'];
        $rag = $a['readiness_rag'];
        $w = $pct === null ? 0 : max(0, min(100, $pct));
    ?>
        <div class="card hero-card hero-<?= e($rag) ?>">
            <div class="hero-body">
                <h2 class="hero-title">
                    <a href="/accreditations/<?= e($a['id']) ?>"><?= e($a['title']) ?></a>
                    <?php if ((int) $a['is_placeholder'] === 1): ?>
                        <span class="badge badge-yellow">NAMUNA</span>
                    <?php endif; ?>
                </h2>
                <p>
                    Tayyorlik indeksi:
                    <strong><?= $pct === null ? 'Ma\'lumot yo\'q' : e(round($pct)) . '%' ?></strong>
                    <span class="badge badge-<?= e($rag) ?>"><?= e($a['readiness_label']) ?></span>
                    &nbsp;|&nbsp; Mezonlar: <strong><?= e($a['criteria_count']) ?></strong>
                    &nbsp;|&nbsp; Sikl: <strong><?= e($a['cycle_year'] ?? '—') ?></strong>
                </p>
                <div class="progress hero-progress" role="progressbar" aria-valuenow="<?= e(round($w)) ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar rag-fill-<?= e($rag) ?>" style="width: <?= e($w) ?>%"></div>
                </div>
                <?php if ($a['specialties'] !== []): ?>
                    <p class="text-muted">Ixtisosliklar:
                        <?php foreach ($a['specialties'] as $sp): ?>
                            <a href="/specialties/<?= e($sp['id']) ?>"><?= e($sp['code']) ?></a>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>
                <a class="btn btn-primary" href="/accreditations/<?= e($a['id']) ?>">Ochish</a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
