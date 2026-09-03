<?php
/**
 * Indikator kartasi (item 9) — BARCHA maydonlar: indikator kodi, nomi, talab
 * mazmuni, amaldagi holat, o'z-o'zini baholash, tasdiqlovchi dalillar (linked
 * documents), yuklangan hujjatlar, mas'ul bo'lim, mas'ul shaxs, ekspert izohi,
 * aniqlangan kamchilik, chora-tadbir, bajarish muddati, bajarilish holati.
 *
 * 4 ta RAG baholash holati (item 10): Talabga to'liq mos / Qisman mos /
 * Talabga mos emas / Baholanmagan. Baho RBAC bilan cheklangan (accreditation.approve).
 *
 * @var \App\Core\View $this
 * @var array<string,mixed> $indicator
 * @var array<int,array<string,mixed>> $evidence
 * @var array<int,array<string,mixed>> $deficiencies
 * @var array<string,string> $ragLabels
 * @var array<int,array<string,mixed>> $roles
 * @var bool $canEdit
 * @var bool $canAssess
 */
use App\Core\Csrf;

$this->layout('layouts.app');
$active = 'accreditations';
$rag = $indicator['rag_status'] ?? 'grey';
$critId = (int) $indicator['criterion_id'];
$accId = (int) $indicator['accreditation_id'];
$field = function (string $label, $value) {
    echo '<tr><th style="width:32%">' . e($label) . '</th><td>' . e(($value === null || $value === '') ? '—' : $value) . '</td></tr>';
};
?>
<div class="page-header">
    <nav class="breadcrumb">
        <a href="/accreditations">Akkreditatsiya</a> /
        <a href="/accreditations/<?= e($accId) ?>">Sikl</a> /
        <a href="/criteria/<?= e($critId) ?>"><?= e($indicator['criterion_name']) ?></a> /
        <span><?= e($indicator['code'] ?? '') ?></span>
    </nav>
    <h1 class="page-title"><?= e($indicator['code'] ?? '') ?> <?= e($indicator['name']) ?>
        <span class="badge badge-<?= e($rag) ?>"><?= e($ragLabels[$rag] ?? $rag) ?></span>
    </h1>
</div>

<?php $flashError = \App\Core\Session::flash('error'); ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>

<?php if ((int) $indicator['is_placeholder'] === 1): ?>
    <div class="alert alert-warning" role="alert"><strong>Diqqat:</strong> Ushbu indikator NAMUNA (placeholder) — rasmiy tasdiqlangan qiymat bilan almashtiriladi.</div>
<?php endif; ?>

<div class="card indicator-<?= e($rag) ?>">
    <h3>Indikator kartasi (barcha maydonlar)</h3>
    <div class="table-wrap">
        <table class="table">
            <?php
            $field('Indikator kodi', $indicator['code']);
            $field('Indikator nomi', $indicator['name']);
            $field('Mezon', ($indicator['criterion_code'] ?? '') . ' ' . $indicator['criterion_name']);
            $field('Talab mazmuni', $indicator['requirement']);
            $field('Amaldagi holat', $indicator['description']);
            $field('O\'z-o\'zini baholash', $indicator['self_assessment']);
            $field('Maqsadli qiymat', $indicator['target_value']);
            $field('Amaldagi qiymat', $indicator['actual_value']);
            $field('Og\'irlik (weight)', $indicator['weight']);
            $field('Baho (RAG holati)', $ragLabels[$rag] ?? $rag);
            $field('Bal (score)', $indicator['score']);
            $field('Mas\'ul bo\'lim', $indicator['responsible_dept']);
            $field('Mas\'ul shaxs', $indicator['responsible_person']);
            $field('Mas\'ul rol', $indicator['responsible_role_title']);
            ?>
        </table>
    </div>
</div>

<!-- BAHO (item 10): 4 ta RAG holat — RBAC bilan (Ekspert / Ta'lim sifati) -->
<div class="card">
    <h3>Baholash (item 10)</h3>
    <?php if ($canAssess): ?>
        <form method="post" action="/indicators/<?= e($indicator['id']) ?>/assess">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label for="rag_status">Baho holati</label>
                <select id="rag_status" name="rag_status" required>
                    <?php foreach ($ragLabels as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $rag === $key ? 'selected' : '' ?>>
                            <?= e($label) ?> (<?= e(['green' => '🟢', 'yellow' => '🟡', 'red' => '🔴', 'grey' => '⚪'][$key] ?? '') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="self_assessment_expert">Ekspert izohi / o'z-o'zini baholash</label>
                <textarea id="self_assessment_expert" name="self_assessment" rows="3"><?= e($indicator['self_assessment'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Bahoni saqlash</button>
        </form>
        <p class="text-muted"><small>Eslatma: indikatorda tasdiqlovchi dalil (evidence) bo'lmasa, RAG holati "Baholanmagan" (kulrang) bo'lib qoladi va tayyorlik indeksiga hissa qo'shmaydi.</small></p>
    <?php else: ?>
        <p class="text-muted">Baho qo'yish uchun ruxsatingiz yo'q (Ekspert / Ta'lim sifati roli talab qilinadi).</p>
    <?php endif; ?>
</div>

<!-- TASDIQLOVCHI DALILLAR (linked documents) + yuklangan hujjatlar -->
<div class="card">
    <h3>Tasdiqlovchi dalillar / yuklangan hujjatlar (<?= count($evidence) ?>)</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Hujjat</th><th>Toifa</th><th>Izoh</th><th>Amal</th></tr></thead>
            <tbody>
                <?php if ($evidence === []): ?>
                    <tr><td colspan="4" class="text-muted">Dalil biriktirilmagan.</td></tr>
                <?php endif; ?>
                <?php foreach ($evidence as $d): ?>
                    <tr>
                        <td><a href="/documents/<?= e($d['id']) ?>"><?= e($d['title']) ?></a></td>
                        <td><?= e(\App\Models\Document::categoryLabel((string) $d['category'])) ?></td>
                        <td><?= e($d['note'] ?? '—') ?></td>
                        <td><a class="btn" href="/documents/<?= e($d['id']) ?>/download">Yuklab olish</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted"><small>Dalil biriktirish/uzish dalillar bazasi (hujjat sahifasi) orqali amalga oshiriladi.</small></p>
</div>

<!-- ANIQLANGAN KAMCHILIK -> CHORA-TADBIR -->
<div class="card">
    <h3>Aniqlangan kamchiliklar va chora-tadbirlar (<?= count($deficiencies) ?>)</h3>
    <?php if ($deficiencies === []): ?>
        <p class="text-muted">Kamchilik qayd etilmagan.</p>
    <?php else: ?>
        <?php foreach ($deficiencies as $def): ?>
            <div class="card">
                <p><strong><?= e($def['title']) ?></strong> <span class="badge badge-<?= $def['status'] === 'resolved' ? 'green' : ($def['severity'] === 'high' ? 'red' : 'yellow') ?>"><?= e($def['status']) ?></span></p>
                <p class="text-muted"><?= e($def['description'] ?? '') ?></p>
                <p><small>Aniqladi: <?= e($def['identified_by_name'] ?? '—') ?> &nbsp;|&nbsp; Sana: <?= e($def['identified_at'] ?? '—') ?></small></p>
                <?php if ($def['action_plans'] !== []): ?>
                    <h5>Chora-tadbirlar:</h5>
                    <ul>
                        <?php foreach ($def['action_plans'] as $ap): ?>
                            <li><?= e($ap['title']) ?> — mas'ul: <?= e($ap['responsible_name'] ?? '—') ?>, muddat: <?= e($ap['due_date'] ?? '—') ?>, holat: <?= e($ap['status']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted"><small>Chora-tadbir biriktirilmagan.</small></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- INDIKATORNI TAHRIRLASH (item-9 maydonlari) -->
<?php if ($canEdit): ?>
<div class="card">
    <h3>Indikator maydonlarini tahrirlash</h3>
    <form method="post" action="/indicators/<?= e($indicator['id']) ?>">
        <?= Csrf::field() ?>
        <div class="form-group"><label for="e_code">Indikator kodi</label><input type="text" id="e_code" name="code" maxlength="64" value="<?= e($indicator['code'] ?? '') ?>"></div>
        <div class="form-group"><label for="e_name">Indikator nomi *</label><input type="text" id="e_name" name="name" maxlength="255" value="<?= e($indicator['name'] ?? '') ?>" required></div>
        <div class="form-group"><label for="e_req">Talab mazmuni</label><textarea id="e_req" name="requirement" rows="2"><?= e($indicator['requirement'] ?? '') ?></textarea></div>
        <div class="form-group"><label for="e_desc">Amaldagi holat</label><textarea id="e_desc" name="description" rows="2"><?= e($indicator['description'] ?? '') ?></textarea></div>
        <div class="form-group"><label for="e_target">Maqsadli qiymat</label><input type="text" id="e_target" name="target_value" maxlength="191" value="<?= e($indicator['target_value'] ?? '') ?>"></div>
        <div class="form-group"><label for="e_actual">Amaldagi qiymat</label><input type="text" id="e_actual" name="actual_value" maxlength="191" value="<?= e($indicator['actual_value'] ?? '') ?>"></div>
        <div class="form-group"><label for="e_weight">Og'irlik</label><input type="number" step="0.1" min="0.1" id="e_weight" name="weight" value="<?= e($indicator['weight']) ?>"></div>
        <div class="form-group"><label for="e_dept">Mas'ul bo'lim</label><input type="text" id="e_dept" name="responsible_dept" maxlength="191" value="<?= e($indicator['responsible_dept'] ?? '') ?>"></div>
        <div class="form-group"><label for="e_person">Mas'ul shaxs</label><input type="text" id="e_person" name="responsible_person" maxlength="191" value="<?= e($indicator['responsible_person'] ?? '') ?>"></div>
        <div class="form-group">
            <label for="e_role">Mas'ul rol</label>
            <select id="e_role" name="responsible_role_id">
                <option value="">—</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= e($r['id']) ?>" <?= (int) ($indicator['responsible_role_id'] ?? 0) === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['title_uz']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Saqlash</button>
    </form>
</div>
<?php endif; ?>
