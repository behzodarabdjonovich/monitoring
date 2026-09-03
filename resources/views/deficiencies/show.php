<?php
/**
 * Kamchilik kartasi (item 12) — to'liq zanjir + chora-tadbirlar (action plans).
 * Muammo → Sabab → Chora-tadbir → Mas'ul → Boshlanish sanasi → Yakuniy
 * muddat → Dalil → Natija.
 *
 * @var \App\Core\View $this
 * @var array<string,mixed> $deficiency
 * @var array<int,array<string,mixed>> $actionPlans
 * @var array<string,string> $statusLabels
 * @var array<string,string> $severityLabels
 * @var array<int,array<string,mixed>> $users
 * @var array<int,array<string,mixed>> $documents
 * @var bool $canEdit
 * @var bool $canPlan
 */
use App\Core\Csrf;
use App\Models\Deficiency;

$this->layout('layouts.app');
$d = $deficiency;
$field = function (string $label, $value) {
    echo '<tr><th style="width:28%">' . e($label) . '</th><td>' . e(($value === null || $value === '') ? '—' : $value) . '</td></tr>';
};
$dueLabels = ['done' => 'Bajarilgan', 'overdue' => 'Muddati o\'tgan', 'due_soon' => 'Muddati yaqin', 'normal' => 'Rejada'];
?>
<div class="page-header">
    <nav class="breadcrumb"><a href="/deficiencies">Kamchiliklar</a> / <span><?= e($d['title']) ?></span></nav>
    <h1 class="page-title"><?= e($d['title']) ?>
        <span class="badge badge-<?= $d['status'] === 'resolved' ? 'green' : 'grey' ?>"><?= e($statusLabels[$d['status']] ?? $d['status']) ?></span>
    </h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<div class="card">
    <h3>Kamchilik zanjiri</h3>
    <div class="table-wrap">
        <table class="table">
            <?php
            $field('Muammo', $d['title']);
            $field('Sabab', $d['cause']);
            $field('Tavsif', $d['description']);
            $field('Jiddiylik', $severityLabels[$d['severity']] ?? $d['severity']);
            $field('Holat', $statusLabels[$d['status']] ?? $d['status']);
            $source = $d['indicator_id'] !== null
                ? ('Indikator ' . ($d['indicator_code'] ?? '') . ' ' . ($d['indicator_name'] ?? ''))
                : ($d['internal_audit_id'] !== null ? ('Ichki audit: ' . ($d['audit_title'] ?? '')) : 'Qo\'lda kiritilgan');
            $field('Manba', $source);
            $field('Aniqladi', $d['identified_by_name']);
            $field('Aniqlangan sana', $d['identified_at']);
            $field('Natija', $d['result']);
            ?>
        </table>
    </div>
    <?php if ($d['indicator_id'] !== null): ?>
        <a class="btn" href="/indicators/<?= e($d['indicator_id']) ?>">Indikatorga o'tish</a>
    <?php endif; ?>
</div>

<!-- CHORA-TADBIRLAR (Action Plan) — muddat holatiga qarab ranglangan -->
<div class="card">
    <h3>Chora-tadbirlar rejasi (<?= count($actionPlans) ?>)</h3>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Chora-tadbir</th>
                    <th>Mas'ul</th>
                    <th>Boshlanish</th>
                    <th>Yakuniy muddat</th>
                    <th>Dalil</th>
                    <th>Natija</th>
                    <th>Holat</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($actionPlans === []): ?>
                    <tr><td colspan="7" class="text-muted">Chora-tadbir biriktirilmagan.</td></tr>
                <?php endif; ?>
                <?php foreach ($actionPlans as $ap): ?>
                    <?php $rag = Deficiency::dueRag($ap['due_state']); ?>
                    <tr class="due-<?= e($ap['due_state']) ?>">
                        <td><?= e($ap['title']) ?></td>
                        <td><?= e($ap['responsible_name'] ?? '—') ?></td>
                        <td><?= e($ap['start_date'] ?? '—') ?></td>
                        <td>
                            <?= e($ap['due_date'] ?? '—') ?>
                            <span class="badge badge-<?= e($rag) ?>"><?= e($dueLabels[$ap['due_state']] ?? $ap['due_state']) ?></span>
                        </td>
                        <td><?php if ($ap['document_id'] !== null): ?><a href="/documents/<?= e($ap['document_id']) ?>"><?= e($ap['document_title'] ?? 'Hujjat') ?></a><?php else: ?>—<?php endif; ?></td>
                        <td><?= e($ap['result'] ?? '—') ?></td>
                        <td><?= e($ap['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($canPlan): ?>
<div class="card">
    <h3>Yangi chora-tadbir qo'shish</h3>
    <form method="post" action="/deficiencies/<?= e($d['id']) ?>/plans">
        <?= Csrf::field() ?>
        <div class="form-group"><label for="ap_title">Chora-tadbir *</label><input type="text" id="ap_title" name="title" required maxlength="191"></div>
        <div class="form-group"><label for="ap_desc">Tavsif</label><textarea id="ap_desc" name="description" rows="2"></textarea></div>
        <div class="form-group">
            <label for="ap_resp">Mas'ul</label>
            <select id="ap_resp" name="responsible_user_id">
                <option value="">—</option>
                <?php foreach ($users as $u): ?><option value="<?= e($u['id']) ?>"><?= e($u['full_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label for="ap_start">Boshlanish sanasi</label><input type="date" id="ap_start" name="start_date"></div>
        <div class="form-group"><label for="ap_due">Yakuniy muddat</label><input type="date" id="ap_due" name="due_date"></div>
        <div class="form-group">
            <label for="ap_doc">Dalil (hujjat)</label>
            <select id="ap_doc" name="document_id">
                <option value="">—</option>
                <?php foreach ($documents as $doc): ?><option value="<?= e($doc['id']) ?>"><?= e($doc['title']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Qo'shish</button>
    </form>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<div class="card">
    <h3>Kamchilikni yangilash / yopish</h3>
    <form method="post" action="/deficiencies/<?= e($d['id']) ?>">
        <?= Csrf::field() ?>
        <div class="form-group"><label for="e_title">Muammo *</label><input type="text" id="e_title" name="title" value="<?= e($d['title']) ?>" required maxlength="191"></div>
        <div class="form-group"><label for="e_cause">Sabab</label><textarea id="e_cause" name="cause" rows="2"><?= e($d['cause'] ?? '') ?></textarea></div>
        <div class="form-group"><label for="e_desc">Tavsif</label><textarea id="e_desc" name="description" rows="2"><?= e($d['description'] ?? '') ?></textarea></div>
        <div class="form-group">
            <label for="e_status">Holat</label>
            <select id="e_status" name="status">
                <?php foreach ($statusLabels as $key => $label): ?><option value="<?= e($key) ?>" <?= $d['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="e_severity">Jiddiylik</label>
            <select id="e_severity" name="severity">
                <?php foreach ($severityLabels as $key => $label): ?><option value="<?= e($key) ?>" <?= $d['severity'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label for="e_result">Natija</label><textarea id="e_result" name="result" rows="2"><?= e($d['result'] ?? '') ?></textarea></div>
        <button type="submit" class="btn btn-primary">Saqlash</button>
    </form>
    <form method="post" action="/deficiencies/<?= e($d['id']) ?>/close" style="margin-top:0.5rem;">
        <?= Csrf::field() ?>
        <button type="submit" class="btn">Bartaraf etilgan deb yopish</button>
    </form>
</div>
<?php endif; ?>
