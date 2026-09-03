<?php
/**
 * Dashboard filtr paneli (item 3): yetti filtr GET query params orqali.
 * Tanlangan qiymatlar UI'da saqlanadi. "Qo'llash" bosilganda /dashboard'ga
 * GET yuboriladi va KPI/grafiklar qayta hisoblanadi.
 *
 * @var \App\Core\View $this
 * @var array<string,string> $filters
 * @var array<string,array<int,array{value:string,label:string}>> $filterOptions
 */
$filters = $filters ?? [];
$filterOptions = $filterOptions ?? [];

$fields = [
    'academic_year' => "O'quv yili",
    'specialty' => 'Ixtisoslik',
    'department' => 'Kafedra',
    'dtype' => 'Doktorantura turi',
    'course' => 'Kurs/bosqich',
    'supervisor' => 'Ilmiy rahbar',
    'acc_status' => 'Akkreditatsiya holati',
];
$hasActive = $filters !== [];
?>
<form class="card filter-bar" method="get" action="/dashboard" aria-label="Dashboard filtrlari">
    <div class="filter-bar-head">
        <strong>Filtrlar</strong>
        <?php if ($hasActive): ?>
            <a class="filter-clear" href="/dashboard">Tozalash</a>
        <?php endif; ?>
    </div>
    <div class="filter-grid">
        <?php foreach ($fields as $key => $label): ?>
            <?php $options = $filterOptions[$key] ?? []; $selected = $filters[$key] ?? ''; ?>
            <div class="form-group filter-field">
                <label for="filter-<?= e($key) ?>"><?= e($label) ?></label>
                <select id="filter-<?= e($key) ?>" name="<?= e($key) ?>">
                    <option value="">— Barchasi —</option>
                    <?php foreach ($options as $opt): ?>
                        <option value="<?= e($opt['value']) ?>" <?= ((string) $selected === (string) $opt['value']) ? 'selected' : '' ?>>
                            <?= e($opt['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="filter-actions">
        <button type="submit" class="btn btn-primary">Qo'llash</button>
    </div>
</form>
