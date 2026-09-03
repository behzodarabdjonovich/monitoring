<?php
/**
 * Dalillar bazasi (item 11) — markazlashtirilgan ro'yxat + yuklash formasi.
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $documents
 * @var array<string,string> $filters
 * @var array<string,string> $categories
 * @var bool $canUpload
 */
use App\Core\Csrf;

$this->layout('layouts.app');

$fmtSize = static function ($bytes): string {
    $bytes = (int) $bytes;
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
};
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Dalillar bazasi</span></nav>
    <h1 class="page-title">Dalillar bazasi</h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<?php if ($canUpload): ?>
<div class="card">
    <h3>Yangi dalil yuklash</h3>
    <form method="post" action="/documents" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <div class="form-group">
            <label for="title">Sarlavha *</label>
            <input type="text" id="title" name="title" required maxlength="255">
        </div>
        <div class="form-group">
            <label for="category">Toifa *</label>
            <select id="category" name="category" required>
                <?php foreach ($categories as $key => $label): ?>
                    <option value="<?= e($key) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="file">Fayl (PDF/JPG/PNG) *</label>
            <input type="file" id="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        <button type="submit" class="btn btn-primary">Yuklash</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <form method="get" action="/documents" style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.75rem;">
        <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Nom bo'yicha qidirish">
        <select name="category">
            <option value="">— Barcha toifalar —</option>
            <?php foreach ($categories as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $filters['category'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Filtr</button>
    </form>

    <h3>Hujjatlar (<?= count($documents) ?>)</h3>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Sarlavha</th>
                    <th>Toifa</th>
                    <th>Egasi</th>
                    <th>Yuklangan sana</th>
                    <th>Hajmi</th>
                    <th>MIME</th>
                    <th>Indikatorlar</th>
                    <th>Amal</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($documents === []): ?>
                    <tr><td colspan="8" class="text-muted">Hujjat topilmadi.</td></tr>
                <?php endif; ?>
                <?php foreach ($documents as $d): ?>
                    <tr>
                        <td><a href="/documents/<?= e($d['id']) ?>"><?= e($d['title']) ?></a></td>
                        <td><?= e($categories[$d['category']] ?? $d['category']) ?></td>
                        <td><?= e($d['uploader_name'] ?? '—') ?></td>
                        <td><?= e($d['created_at'] ?? '—') ?></td>
                        <td><?= e($fmtSize($d['file_size'] ?? 0)) ?></td>
                        <td><?= e($d['mime_type'] ?? '—') ?></td>
                        <td><span class="badge badge-grey"><?= e((int) ($d['indicator_count'] ?? 0)) ?></span></td>
                        <td><a href="/documents/<?= e($d['id']) ?>/download">Yuklab olish</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
