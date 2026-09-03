<?php
/**
 * Dalil hujjati ko'rinishi (item 11) — metama'lumot + M:N indikator
 * bog'lash/uzish. Per-document: hujjat qaysi indikatorlarni qo'llab-quvvatlaydi.
 *
 * @var \App\Core\View $this
 * @var array<string,mixed> $document
 * @var array<string,string> $categories
 * @var array<int,array<string,mixed>> $linkedIndicators
 * @var array<int,array<string,mixed>> $allIndicators
 * @var bool $canLink
 */
use App\Core\Csrf;

$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/documents">Dalillar bazasi</a> / <span><?= e($document['title']) ?></span></nav>
    <h1 class="page-title"><?= e($document['title']) ?></h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <h3>Metama'lumotlar</h3>
    <p><strong>Toifa:</strong> <?= e($categories[$document['category']] ?? $document['category']) ?></p>
    <p><strong>Egasi:</strong> <?= e($document['uploader_name'] ?? '—') ?></p>
    <p><strong>Yuklangan sana:</strong> <?= e($document['created_at'] ?? '—') ?></p>
    <p><strong>Fayl nomi:</strong> <?= e($document['original_name'] ?? '—') ?></p>
    <p><strong>MIME:</strong> <?= e($document['mime_type'] ?? '—') ?> &nbsp;|&nbsp; <strong>Hajmi:</strong> <?= e((int) ($document['file_size'] ?? 0)) ?> bayt</p>
    <a class="btn btn-primary" href="/documents/<?= e($document['id']) ?>/download">Yuklab olish</a>
</div>

<div class="card">
    <h3>Qo'llab-quvvatlanadigan indikatorlar (<?= count($linkedIndicators) ?>)</h3>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Kod</th><th>Nomi</th><th>RAG</th><?php if ($canLink): ?><th>Amal</th><?php endif; ?></tr>
            </thead>
            <tbody>
                <?php if ($linkedIndicators === []): ?>
                    <tr><td colspan="<?= $canLink ? 4 : 3 ?>" class="text-muted">Hech bir indikatorga bog'lanmagan.</td></tr>
                <?php endif; ?>
                <?php foreach ($linkedIndicators as $ind): ?>
                    <tr>
                        <td><?= e($ind['code']) ?></td>
                        <td><?= e($ind['name']) ?></td>
                        <td><span class="badge badge-<?= e($ind['rag_status']) ?>"><?= e($ind['rag_status']) ?></span></td>
                        <?php if ($canLink): ?>
                        <td>
                            <form method="post" action="/documents/<?= e($document['id']) ?>/unlink" style="display:inline;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="indicator_id" value="<?= e($ind['id']) ?>">
                                <button type="submit" class="btn">Uzish</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($canLink): ?>
        <h4 style="margin-top:1rem;">Indikatorga bog'lash</h4>
        <form method="post" action="/documents/<?= e($document['id']) ?>/link">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label for="indicator_id">Indikator</label>
                <select id="indicator_id" name="indicator_id" required>
                    <option value="">—</option>
                    <?php foreach ($allIndicators as $ai): ?>
                        <option value="<?= e($ai['id']) ?>"><?= e($ai['code']) ?> — <?= e($ai['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="note">Izoh</label>
                <input type="text" id="note" name="note" maxlength="191">
            </div>
            <button type="submit" class="btn btn-primary">Bog'lash</button>
        </form>
    <?php endif; ?>
</div>
