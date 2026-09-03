<?php
/**
 * Kamchiliklar (item 12) — ro'yxat + yangi kamchilik formasi.
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $deficiencies
 * @var array<string,string> $filters
 * @var array<string,string> $statusLabels
 * @var array<string,string> $severityLabels
 * @var bool $canCreate
 * @var bool $canEdit
 */
use App\Core\Csrf;
use App\Models\Deficiency;

$this->layout('layouts.app');
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Kamchiliklar</span></nav>
    <h1 class="page-title">Kamchiliklar va chora-tadbirlar</h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <p class="text-muted">Har bir muammo uchun zanjir yuritiladi: <strong>Muammo → Sabab → Chora-tadbir → Mas'ul → Boshlanish sanasi → Yakuniy muddat → Dalil → Natija</strong>. Muddati yaqinlashayotgan chora-tadbirlar <span class="badge badge-yellow">sariq</span>, muddati o'tganlari <span class="badge badge-red">qizil</span> rangda ko'rsatiladi.</p>
</div>

<?php if ($canCreate): ?>
<div class="card">
    <h3>Yangi kamchilik qayd etish</h3>
    <form method="post" action="/deficiencies">
        <?= Csrf::field() ?>
        <div class="form-group"><label for="title">Muammo *</label><input type="text" id="title" name="title" required maxlength="191"></div>
        <div class="form-group"><label for="cause">Sabab</label><textarea id="cause" name="cause" rows="2"></textarea></div>
        <div class="form-group"><label for="description">Tavsif</label><textarea id="description" name="description" rows="2"></textarea></div>
        <div class="form-group">
            <label for="severity">Jiddiylik</label>
            <select id="severity" name="severity">
                <?php foreach ($severityLabels as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $key === 'medium' ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Qayd etish</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <form method="get" action="/deficiencies" style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.75rem;">
        <select name="status">
            <option value="">— Barcha holatlar —</option>
            <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="source">
            <option value="">— Barcha manbalar —</option>
            <option value="indicator" <?= $filters['source'] === 'indicator' ? 'selected' : '' ?>>Indikatordan</option>
            <option value="audit" <?= $filters['source'] === 'audit' ? 'selected' : '' ?>>Ichki auditdan</option>
        </select>
        <button type="submit" class="btn">Filtr</button>
    </form>

    <h3>Kamchiliklar (<?= count($deficiencies) ?>)</h3>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Muammo</th>
                    <th>Manba</th>
                    <th>Jiddiylik</th>
                    <th>Holat</th>
                    <th>Chora-tadbirlar</th>
                    <th>Aniqlangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($deficiencies === []): ?>
                    <tr><td colspan="6" class="text-muted">Kamchilik topilmadi.</td></tr>
                <?php endif; ?>
                <?php foreach ($deficiencies as $d): ?>
                    <?php
                    $source = $d['indicator_id'] !== null
                        ? 'Indikator ' . ($d['indicator_code'] ?? '')
                        : ($d['internal_audit_id'] !== null ? 'Ichki audit' : 'Qo\'lda');
                    // Muddat holatlariga qarab eng kritik chora-tadbir rangini aniqlaymiz.
                    $worst = 'normal';
                    foreach ($d['action_plans'] as $ap) {
                        if ($ap['due_state'] === 'overdue') { $worst = 'overdue'; break; }
                        if ($ap['due_state'] === 'due_soon') { $worst = 'due_soon'; }
                    }
                    ?>
                    <tr>
                        <td><a href="/deficiencies/<?= e($d['id']) ?>"><?= e($d['title']) ?></a></td>
                        <td><?= e($source) ?></td>
                        <td><span class="badge badge-<?= $d['severity'] === 'high' ? 'red' : ($d['severity'] === 'low' ? 'grey' : 'yellow') ?>"><?= e($severityLabels[$d['severity']] ?? $d['severity']) ?></span></td>
                        <td><span class="badge badge-<?= $d['status'] === 'resolved' ? 'green' : 'grey' ?>"><?= e($statusLabels[$d['status']] ?? $d['status']) ?></span></td>
                        <td>
                            <?= e((int) $d['action_count']) ?>
                            <?php if ($worst === 'overdue'): ?><span class="badge badge-red">muddati o'tgan</span><?php elseif ($worst === 'due_soon'): ?><span class="badge badge-yellow">muddati yaqin</span><?php endif; ?>
                        </td>
                        <td><?= e($d['identified_at'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
