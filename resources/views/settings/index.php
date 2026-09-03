<?php
/**
 * Sozlamalar — baholash metodikasi (Super Admin). RAG chegaralari, baho
 * holatlari ballari, kulrang siyosati va standart og'irlik SOZLANADI. Saqlangach
 * barcha akkreditatsiya tayyorlik indekslari qayta hisoblanadi.
 *
 * @var \App\Core\View $this
 * @var array<string,string> $settings
 * @var array<string,array{0:string,1:string}> $keys
 * @var array{green:float,yellow:float} $thresholds
 * @var array<int,array<string,mixed>> $accreditations
 * @var bool $canConfigure
 */
use App\Core\Csrf;

$this->layout('layouts.app');
$active = 'settings';
?>
<div class="page-header">
    <nav class="breadcrumb"><span>Sozlamalar</span></nav>
    <h1 class="page-title">Baholash metodikasi sozlamalari</h1>
</div>

<div class="card">
    <p class="text-muted">Ushbu qiymatlar <strong>ScoringEngine</strong> tomonidan har hisoblashda o'qiladi. Metodika administrator tomonidan to'liq sozlanadi; rasmiy baholash metodikasi mavjud bo'lsa, aynan o'sha qiymatlar kiritiladi.</p>
</div>

<div class="card">
    <h3>Joriy tayyorlik indekslari (mavjud sozlama bilan)</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Akkreditatsiya</th><th>Indeks</th><th>Yorliq</th></tr></thead>
            <tbody>
                <?php foreach ($accreditations as $a): ?>
                    <tr>
                        <td><a href="/accreditations/<?= e($a['id']) ?>"><?= e($a['title']) ?></a></td>
                        <td><?= $a['readiness_percent'] === null ? '—' : e(round($a['readiness_percent'])) . '%' ?></td>
                        <td><span class="badge badge-<?= e($a['readiness_rag']) ?>"><?= e($a['readiness_label']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3>Baholash parametrlari</h3>
    <?php if (!$canConfigure): ?>
        <p class="text-muted">Sozlamalarni o'zgartirish uchun ruxsatingiz yo'q (Super Admin talab qilinadi).</p>
    <?php endif; ?>
    <form method="post" action="/settings">
        <?= Csrf::field() ?>
        <?php foreach ($keys as $key => [$label, $type]): ?>
            <?php $fieldName = str_replace('.', '_', $key); ?>
            <div class="form-group">
                <label for="<?= e($fieldName) ?>"><?= e($label) ?></label>
                <?php if ($type === 'select' && $key === 'scoring.grey_policy'): ?>
                    <select id="<?= e($fieldName) ?>" name="<?= e($fieldName) ?>" <?= $canConfigure ? '' : 'disabled' ?>>
                        <option value="exclude" <?= ($settings[$key] ?? 'exclude') === 'exclude' ? 'selected' : '' ?>>Hisobdan chiqarish (exclude)</option>
                        <option value="zero" <?= ($settings[$key] ?? '') === 'zero' ? 'selected' : '' ?>>0 ball sifatida (zero)</option>
                    </select>
                <?php else: ?>
                    <input type="number" step="0.1" id="<?= e($fieldName) ?>" name="<?= e($fieldName) ?>" value="<?= e($settings[$key] ?? '') ?>" <?= $canConfigure ? '' : 'disabled' ?>>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if ($canConfigure): ?>
            <button type="submit" class="btn btn-primary">Saqlash va qayta hisoblash</button>
        <?php endif; ?>
    </form>
    <p class="text-muted"><small>RAG yorliqlari: indeks ≥ yashil chegara → <strong>Tayyor</strong>; ≥ sariq chegara → <strong>Takomillashtirish kerak</strong>; aks holda → <strong>Yuqori xavf</strong>.</small></p>
</div>
