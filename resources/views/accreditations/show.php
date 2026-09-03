<?php
/**
 * Akkreditatsiya sikli — mezonlar ro'yxati (nested navigation: Akkreditatsiya
 * -> Mezon). Tayyorlik indeksi + RAG yorliq. NAMUNA (placeholder) banner
 * doimiy ko'rinadi.
 *
 * @var \App\Core\View $this
 * @var array<string,mixed> $accreditation
 * @var array{readiness_index:?float,rag_status:string,label:string} $assessment
 * @var array<int,array<string,mixed>> $criteria
 * @var array<int,array<string,mixed>> $specialties
 * @var bool $canEdit
 * @var bool $canConfigure
 */
use App\Core\Csrf;

$this->layout('layouts.app');
$active = 'accreditations';
$pct = $assessment['readiness_index'];
$rag = $assessment['rag_status'];
$w = $pct === null ? 0 : max(0, min(100, $pct));
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/accreditations">Akkreditatsiya</a> / <span><?= e($accreditation['title']) ?></span></nav>
    <h1 class="page-title"><?= e($accreditation['title']) ?>
        <?php if ((int) $accreditation['is_placeholder'] === 1): ?><span class="badge badge-yellow">NAMUNA</span><?php endif; ?>
    </h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card hero-card hero-<?= e($rag) ?>">
    <div class="hero-body">
        <h2 class="hero-title">Akkreditatsiyaga tayyorlik indeksi: <?= $pct === null ? 'Ma\'lumot yo\'q' : e(round($pct)) . '%' ?>
            <span class="badge badge-<?= e($rag) ?>"><?= e($assessment['label']) ?></span>
        </h2>
        <div class="progress hero-progress" role="progressbar" aria-valuenow="<?= e(round($w)) ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar rag-fill-<?= e($rag) ?>" style="width: <?= e($w) ?>%"></div>
        </div>
        <p class="hero-meta text-muted">Sikl: <strong><?= e($accreditation['cycle_year'] ?? '—') ?></strong> &nbsp;|&nbsp; Holat: <strong><?= e($accreditation['status']) ?></strong></p>
        <?php if ($canEdit): ?><a class="btn btn-primary" href="/accreditations/<?= e($accreditation['id']) ?>/edit">Tahrirlash</a><?php endif; ?>
        <?php if ($canConfigure): ?><a class="btn" href="/settings">Baholash metodikasi (Sozlamalar)</a><?php endif; ?>
    </div>
</div>

<?php if ($specialties !== []): ?>
<div class="card">
    <h3>Bog'langan ixtisosliklar</h3>
    <ul><?php foreach ($specialties as $sp): ?><li><a href="/specialties/<?= e($sp['id']) ?>"><?= e($sp['code']) ?> — <?= e($sp['name']) ?></a></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="card">
    <h3>Mezonlar (<?= count($criteria) ?>)</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Kod</th><th>Mezon nomi</th><th>Og'irlik</th><th>Indikatorlar</th><th>Tayyorlik</th></tr></thead>
            <tbody>
                <?php if ($criteria === []): ?>
                    <tr><td colspan="5" class="text-muted">Mezon kiritilmagan.</td></tr>
                <?php endif; ?>
                <?php foreach ($criteria as $c): ?>
                    <tr>
                        <td><?= e($c['code'] ?? '—') ?></td>
                        <td><a href="/criteria/<?= e($c['id']) ?>"><?= e($c['name']) ?></a>
                            <?php if ((int) $c['is_placeholder'] === 1): ?><span class="badge badge-yellow">NAMUNA</span><?php endif; ?>
                        </td>
                        <td><?= e($c['weight']) ?></td>
                        <td><?= e($c['indicator_count']) ?></td>
                        <td>
                            <span class="badge badge-<?= e($c['rag']) ?>"><?= $c['percent'] === null ? 'Ma\'lumot yo\'q' : e(round($c['percent'])) . '%' ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($canEdit): ?>
        <h4 style="margin-top:1rem;">Yangi mezon qo'shish</h4>
        <form method="post" action="/accreditations/<?= e($accreditation['id']) ?>/criteria">
            <?= Csrf::field() ?>
            <div class="form-group"><label for="c_code">Kod</label><input type="text" id="c_code" name="code" maxlength="64"></div>
            <div class="form-group"><label for="c_name">Mezon nomi *</label><input type="text" id="c_name" name="name" maxlength="255" required></div>
            <div class="form-group"><label for="c_weight">Og'irlik</label><input type="number" step="0.1" min="0.1" id="c_weight" name="weight" value="1.0"></div>
            <div class="form-group"><label for="c_order">Tartib</label><input type="number" id="c_order" name="display_order" value="0"></div>
            <button type="submit" class="btn btn-primary">Mezon qo'shish</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($canConfigure && (int) $accreditation['is_placeholder'] === 1): ?>
<div class="card">
    <h3>NAMUNA (placeholder) holatini tozalash</h3>
    <p class="text-muted">Mezon va indikatorlar rasmiy Ta'lim sifatini ta'minlash milliy agentligi / Vazirlar Mahkamasi qiymatlari bilan tekshirilib almashtirilgach, NAMUNA bayrog'ini tozalang.</p>
    <form method="post" action="/accreditations/<?= e($accreditation['id']) ?>/clear-placeholder" onsubmit="return confirm('NAMUNA bayrog\'ini tozalashni tasdiqlaysizmi? Bu ma\'lumotlarni rasmiy sifatida belgilaydi.');">
        <?= Csrf::field() ?>
        <button type="submit" class="btn">NAMUNA bayrog'ini tozalash</button>
    </form>
</div>
<?php endif; ?>
