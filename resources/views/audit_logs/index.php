<?php
/**
 * Audit jurnali ko'rigi (item 17) — FAQAT o'qish, FAQAT Super Admin.
 * O'chirish tugmasi/marshruti YO'Q (audit_logs o'zgarmas).
 *
 * @var \App\Core\View $this
 * @var array<int,array<string,mixed>> $logs
 * @var array<string,string> $filters
 * @var array{actions:array<int,string>,entities:array<int,string>} $options
 * @var array<int,array<string,mixed>> $users
 * @var array<string,string> $actionLabels
 * @var int $total
 * @var int $page
 * @var int $pages
 */
$this->layout('layouts.app');
$fmtValues = static function ($json): string {
    if ($json === null || $json === '') {
        return '—';
    }
    $data = json_decode((string) $json, true);
    if (!is_array($data)) {
        return (string) $json;
    }
    $parts = [];
    foreach ($data as $k => $v) {
        $parts[] = $k . ': ' . (is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE));
    }
    return implode('; ', $parts);
};
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Audit jurnali</span></nav>
    <h1 class="page-title">Audit jurnali</h1>
    <p class="text-muted">Muhim harakatlar tarixi (o'zgarmas). Jami <?= e($total) ?> ta yozuv.</p>
</div>

<div class="card">
    <form method="get" action="/audit-logs" class="filter-grid">
        <div class="filter-field form-group">
            <label for="action">Amal</label>
            <select id="action" name="action">
                <option value="">Barchasi</option>
                <?php foreach ($options['actions'] as $a): ?>
                    <option value="<?= e($a) ?>" <?= ($filters['action'] ?? '') === $a ? 'selected' : '' ?>><?= e($actionLabels[$a] ?? $a) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field form-group">
            <label for="entity_type">Obyekt turi</label>
            <select id="entity_type" name="entity_type">
                <option value="">Barchasi</option>
                <?php foreach ($options['entities'] as $ent): ?>
                    <option value="<?= e($ent) ?>" <?= ($filters['entity_type'] ?? '') === $ent ? 'selected' : '' ?>><?= e($ent) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field form-group">
            <label for="user_id">Foydalanuvchi</label>
            <select id="user_id" name="user_id">
                <option value="">Barchasi</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= e($u['id']) ?>" <?= ($filters['user_id'] ?? '') === (string) $u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field form-group" style="align-self:end;">
            <button type="submit" class="btn btn-primary">Filtrlash</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Kim</th>
                    <th>Qachon</th>
                    <th>Amal</th>
                    <th>Obyekt</th>
                    <th>Oldingi qiymat</th>
                    <th>Yangi qiymat</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs === []): ?>
                    <tr><td colspan="7" class="text-muted">Yozuv topilmadi.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= e($log['user_name'] ?? ('#' . ($log['user_id'] ?? '—'))) ?></td>
                        <td style="white-space:nowrap;"><?= e($log['created_at']) ?></td>
                        <td><?= e($actionLabels[$log['action']] ?? $log['action']) ?></td>
                        <td><?= e($log['entity_type'] ?? '—') ?><?= $log['entity_id'] !== null ? ' #' . e($log['entity_id']) : '' ?></td>
                        <td class="text-muted"><?= e($fmtValues($log['old_values'])) ?></td>
                        <td class="text-muted"><?= e($fmtValues($log['new_values'])) ?></td>
                        <td><?= e($log['ip_address'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <div style="margin-top:0.75rem;display:flex;gap:0.4rem;flex-wrap:wrap;">
            <?php $qs = static fn (int $p) => '?' . http_build_query(array_merge($filters, ['page' => $p])); ?>
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <a class="btn btn-sm<?= $p === $page ? ' btn-primary' : '' ?>" href="/audit-logs<?= e($qs($p)) ?>"><?= e($p) ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
