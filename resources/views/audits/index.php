<?php
/**
 * Ichki audit (item 13) — o'tkazilgan auditlar ro'yxati + yangi audit
 * o'tkazish (per-ixtisoslik "Ichki akkreditatsiya auditi").
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $audits
 * @var array<int,array<string,mixed>> $specialties
 * @var array<string,string> $riskLabels
 * @var bool $canAudit
 */
use App\Core\Csrf;

$this->layout('layouts.app');
$riskRag = ['low' => 'green', 'medium' => 'yellow', 'high' => 'red', 'unknown' => 'grey'];
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Ichki audit</span></nav>
    <h1 class="page-title">Ichki akkreditatsiya auditi</h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<?php if ($canAudit): ?>
<div class="card">
    <h3>Ixtisoslik bo'yicha audit o'tkazish</h3>
    <p class="text-muted">Audit yakunida avtomatik shakllanadi: kuchli tomonlar, kamchiliklar, bajarilmagan indikatorlar, yetishmayotgan dalillar, xavf darajasi, tavsiyalar, chora-tadbirlar rejasi va akkreditatsiyaga tayyorlik foizi.</p>
    <form method="post" action="/audits/run" style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:flex-end;">
        <?= Csrf::field() ?>
        <div class="form-group" style="margin-bottom:0;">
            <label for="specialty_id">Ixtisoslik</label>
            <select id="specialty_id" name="specialty_id" required>
                <option value="">— tanlang —</option>
                <?php foreach ($specialties as $s): ?>
                    <option value="<?= e($s['id']) ?>"><?= e($s['code'] ?? '') ?> <?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Ichki akkreditatsiya auditi o'tkazish</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h3>O'tkazilgan auditlar (<?= count($audits) ?>)</h3>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Sarlavha</th>
                    <th>Ixtisoslik</th>
                    <th>Sana</th>
                    <th>Auditor</th>
                    <th>Tayyorlik</th>
                    <th>Xavf</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($audits === []): ?>
                    <tr><td colspan="6" class="text-muted">Audit o'tkazilmagan.</td></tr>
                <?php endif; ?>
                <?php foreach ($audits as $a): ?>
                    <?php $risk = $a['risk_level'] ?? 'unknown'; ?>
                    <tr>
                        <td><a href="/audits/<?= e($a['id']) ?>"><?= e($a['title']) ?></a></td>
                        <td><?= e($a['specialty_name'] ?? '—') ?></td>
                        <td><?= e($a['audit_date'] ?? '—') ?></td>
                        <td><?= e($a['auditor_name'] ?? '—') ?></td>
                        <td><?= $a['readiness_index'] === null ? '—' : e(round((float) $a['readiness_index'])) . '%' ?></td>
                        <td><span class="badge badge-<?= e($riskRag[$risk] ?? 'grey') ?>"><?= e($riskLabels[$risk] ?? $risk) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
