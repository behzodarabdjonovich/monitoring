<?php
/**
 * Mezon sahifasi — indikator kartalari (nested navigation: Mezon -> Indikator).
 * Har karta indikatorning asosiy maydonlari + RAG holatini ko'rsatadi.
 * NAMUNA (placeholder) banner doimiy ko'rinadi (layout partial orqali).
 *
 * @var \App\Core\View $this
 * @var array<string,mixed> $criterion
 * @var array<int,array<string,mixed>> $indicators
 * @var array<string,string> $ragLabels
 * @var bool $canEdit
 */
use App\Core\Csrf;

$this->layout('layouts.app');
$active = 'accreditations';
$accId = (int) $criterion['accreditation_id'];
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/accreditations">Akkreditatsiya</a> / <a href="/accreditations/<?= e($accId) ?>">Sikl</a> / <span><?= e($criterion['name']) ?></span></nav>
    <h1 class="page-title"><?= e($criterion['code'] ?? '') ?> <?= e($criterion['name']) ?>
        <?php if ((int) $criterion['is_placeholder'] === 1): ?><span class="badge badge-yellow">NAMUNA</span><?php endif; ?>
    </h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <p><strong>Mezon og'irligi:</strong> <?= e($criterion['weight']) ?></p>
    <?= $this->partial('partials.rag-legend') ?>
</div>

<h3 class="section-title">Indikatorlar (<?= count($indicators) ?>)</h3>

<?php if ($indicators === []): ?>
    <div class="card"><p class="text-muted">Indikator kiritilmagan.</p></div>
<?php endif; ?>

<div class="card-grid">
    <?php foreach ($indicators as $i):
        $rag = $i['rag_status'] ?? 'grey';
    ?>
        <div class="card indicator-card indicator-<?= e($rag) ?>">
            <div class="indicator-card-head">
                <a href="/indicators/<?= e($i['id']) ?>"><strong><?= e($i['code'] ?? '') ?> <?= e($i['name']) ?></strong></a>
                <span class="badge badge-<?= e($rag) ?>"><?= e($ragLabels[$rag] ?? $rag) ?></span>
            </div>
            <p class="text-muted"><?= e($i['requirement'] ?? '—') ?></p>
            <p>
                Og'irlik: <strong><?= e($i['weight']) ?></strong>
                &nbsp;|&nbsp; Dalillar: <strong><?= e($i['evidence_count']) ?></strong>
                &nbsp;|&nbsp; Ochiq kamchiliklar: <strong><?= e($i['open_deficiencies']) ?></strong>
            </p>
            <?php if ((int) $i['is_placeholder'] === 1): ?>
                <p class="text-muted"><small>⚠ NAMUNA ma'lumot — rasmiy qiymat bilan almashtiriladi.</small></p>
            <?php endif; ?>
            <a class="btn" href="/indicators/<?= e($i['id']) ?>">Karta</a>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($canEdit): ?>
<div class="card">
    <h4>Yangi indikator qo'shish</h4>
    <form method="post" action="/criteria/<?= e($criterion['id']) ?>/indicators">
        <?= Csrf::field() ?>
        <div class="form-group"><label for="i_code">Indikator kodi</label><input type="text" id="i_code" name="code" maxlength="64"></div>
        <div class="form-group"><label for="i_name">Indikator nomi *</label><input type="text" id="i_name" name="name" maxlength="255" required></div>
        <div class="form-group"><label for="i_req">Talab mazmuni</label><textarea id="i_req" name="requirement" rows="2"></textarea></div>
        <div class="form-group"><label for="i_weight">Og'irlik</label><input type="number" step="0.1" min="0.1" id="i_weight" name="weight" value="1.0"></div>
        <button type="submit" class="btn btn-primary">Indikator qo'shish</button>
    </form>
</div>
<?php endif; ?>
