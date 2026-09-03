<?php
/**
 * Analitik dashboard (item 3): HERO tayyorlik ko'rsatkichi, KPI kartalar,
 * inline-SVG grafiklar, mezonlar bo'yicha progress barlar, RAG afsona va
 * yetti filtrli panel. Barcha raqamlar DashboardStats'dan (PDO) keladi.
 * Barcha dinamik chiqish e() bilan ekranlanadi (XSS himoyasi).
 *
 * @var \App\Core\View $this
 * @var array|null $user
 * @var array<string,int> $kpis
 * @var array{percent:?float,rag:string,status:?string,cycle:?string,is_placeholder:bool} $hero
 * @var array<string,mixed> $charts
 * @var array<int,array{name:string,percent:?float,rag:string}> $progress
 * @var array<string,string> $filters
 * @var array<string,mixed> $filterOptions
 */

use App\Core\Chart;

$this->layout('layouts.app');
$active = 'dashboard';

$heroPct = $hero['percent'];
$heroRag = $hero['rag'];
$heroLabel = $heroPct === null ? '—' : rtrim(rtrim(number_format($heroPct, 1, '.', ''), '0'), '.') . '%';
$statusLabels = [
    'planning' => 'Rejalashtirilmoqda',
    'in_progress' => 'Jarayonda',
    'submitted' => 'Topshirilgan',
    'completed' => 'Yakunlangan',
];

/** KPI karta yordamchisi. */
$kpiCard = function (string $label, $value, string $badgeClass, string $badgeText) {
    $val = e((string) $value);
    echo '<div class="card kpi-card">';
    echo '<span class="kpi-label">' . e($label) . '</span>';
    echo '<span class="kpi-value">' . $val . '</span>';
    echo '<span class="badge ' . e($badgeClass) . '">' . e($badgeText) . '</span>';
    echo '</div>';
};
?>
<div class="page-header">
    <nav class="breadcrumb" aria-label="Breadcrumb"><span>Bosh sahifa</span></nav>
    <h1 class="page-title">Analitik dashboard</h1>
</div>

<?= $this->partial('partials.dashboard-filters', ['filters' => $filters, 'filterOptions' => $filterOptions]) ?>

<!-- HERO: umumiy tayyorlik -->
<div class="card hero-card hero-<?= e($heroRag) ?>">
    <div class="hero-gauge">
        <?= Chart::gauge($heroPct, $heroRag, ['size' => 200]) ?>
    </div>
    <div class="hero-body">
        <h2 class="hero-title">MAXSUS DAVLAT AKKREDITATSIYASIGA TAYYORLIK — <?= e($heroLabel) ?></h2>
        <div class="progress hero-progress" role="progressbar"
             aria-valuenow="<?= e($heroPct === null ? 0 : round($heroPct)) ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar rag-fill-<?= e($heroRag) ?>" style="width: <?= e($heroPct === null ? 0 : max(0, min(100, $heroPct))) ?>%"></div>
        </div>
        <p class="hero-meta text-muted">
            Holat: <strong><?= e($statusLabels[$hero['status']] ?? ($hero['status'] ?? '—')) ?></strong>
            &nbsp;|&nbsp; Sikl: <strong><?= e($hero['cycle'] ?? '—') ?></strong>
            &nbsp;|&nbsp; Holat rangi:
            <span class="badge badge-<?= e($heroRag) ?>"><?= e(['green' => 'Yashil', 'yellow' => 'Sariq', 'red' => 'Qizil', 'grey' => 'Kulrang'][$heroRag] ?? $heroRag) ?></span>
        </p>
    </div>
</div>

<!-- KPI kartalar gridi (item 3'dagi barcha ko'rsatkichlar) -->
<h3 class="section-title">Doktorantura ko'rsatkichlari</h3>
<div class="card-grid">
    <?php
    $kpiCard('Jami doktorantlar', $kpis['total_students'], 'badge-green', 'Umumiy');
    $kpiCard('PhD doktorantlari', $kpis['phd'], 'badge-green', 'Tayanch');
    $kpiCard('DSc doktorantlari', $kpis['dsc'], 'badge-green', 'Doktorant');
    $kpiCard('Mustaqil izlanuvchilar', $kpis['independent'], 'badge-yellow', 'Izlanuvchi');
    $kpiCard('Jami ixtisosliklar', $kpis['specialties'], 'badge-green', 'Ixtisoslik');
    $kpiCard('Ilmiy rahbarlar', $kpis['supervisors'], 'badge-green', 'Rahbar');
    $kpiCard('Himoyaga tayyor doktorantlar', $kpis['ready_to_defend'], $kpis['ready_to_defend'] > 0 ? 'badge-green' : 'badge-grey', 'Tayyor');
    $kpiCard("Individual rejani to'liq bajarganlar", $kpis['plan_fully_done'], $kpis['plan_fully_done'] > 0 ? 'badge-green' : 'badge-grey', 'Bajarilgan');
    $kpiCard('Rejadan ortda qolayotganlar', $kpis['behind_schedule'], $kpis['behind_schedule'] > 0 ? 'badge-red' : 'badge-green', $kpis['behind_schedule'] > 0 ? 'E\'tibor' : 'Nazoratda');
    ?>
</div>

<h3 class="section-title">Ilmiy natijalar</h3>
<div class="card-grid">
    <?php
    $kpiCard('Ilmiy maqolalar', $kpis['publications'], 'badge-green', 'Maqola');
    $kpiCard('Xalqaro bazadagi maqolalar', $kpis['publications_intl'], 'badge-green', 'Scopus/WoS');
    $kpiCard('Konferensiya materiallari', $kpis['conferences'], 'badge-green', 'Tezis');
    $kpiCard('Dissertatsiya himoyalari', $kpis['defenses'], $kpis['defenses'] > 0 ? 'badge-green' : 'badge-grey', 'Himoya');
    ?>
</div>

<h3 class="section-title">Akkreditatsiya holati</h3>
<div class="card-grid">
    <?php
    $kpiCard('Akkreditatsiyaga tayyor ixtisosliklar', $kpis['acc_ready_specialties'], $kpis['acc_ready_specialties'] > 0 ? 'badge-green' : 'badge-yellow', 'Tayyor');
    $kpiCard('Muammoli indikatorlar', $kpis['problem_indicators'], $kpis['problem_indicators'] > 0 ? 'badge-red' : 'badge-green', $kpis['problem_indicators'] > 0 ? 'Muammo' : 'Toza');
    $kpiCard('Yetishmayotgan hujjatlar', $kpis['missing_docs'], $kpis['missing_docs'] > 0 ? 'badge-yellow' : 'badge-green', $kpis['missing_docs'] > 0 ? 'Yetishmaydi' : 'To\'liq');
    ?>
</div>

<!-- RAG afsona -->
<div class="card">
    <h3>RAG holat afsonasi</h3>
    <?= $this->partial('partials.rag-legend') ?>
</div>

<!-- Grafiklar (inline-SVG, tashqi kutubxonasiz) -->
<h3 class="section-title">Grafiklar</h3>
<div class="chart-grid">
    <div class="card chart-card">
        <h3>Doktorantura turi taqsimoti</h3>
        <?= Chart::donut($charts['by_type'], ['size' => 200, 'center' => (string) $kpis['total_students']]) ?>
        <div class="chart-legend">
            <?php foreach ($charts['by_type'] as $seg): ?>
                <span class="chart-legend-item"><span class="chart-swatch" style="background: <?= e($seg['color']) ?>"></span><?= e($seg['label']) ?> (<?= e((int) $seg['value']) ?>)</span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card chart-card">
        <h3>Natijalar turi bo'yicha</h3>
        <?= Chart::bar($charts['by_result'], ['width' => 460, 'labelWidth' => 170]) ?>
    </div>

    <div class="card chart-card">
        <h3>Ixtisoslik bo'yicha doktorantlar</h3>
        <?= Chart::bar($charts['by_specialty'], ['width' => 460, 'labelWidth' => 220]) ?>
    </div>

    <div class="card chart-card">
        <h3>Indikatorlar RAG taqsimoti</h3>
        <?= Chart::donut($charts['rag_distribution'], ['size' => 200]) ?>
        <div class="chart-legend">
            <?php foreach ($charts['rag_distribution'] as $seg): ?>
                <span class="chart-legend-item"><span class="chart-swatch" style="background: <?= e($seg['color']) ?>"></span><?= e($seg['label']) ?> (<?= e((int) $seg['value']) ?>)</span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Mezonlar bo'yicha progress barlar -->
<div class="card">
    <h3>Akkreditatsiya mezonlari bo'yicha tayyorlik</h3>
    <?php if ($progress === []): ?>
        <p class="text-muted">Mezonlar kiritilmagan.</p>
    <?php else: ?>
        <?php foreach ($progress as $c): ?>
            <?php $pct = $c['percent']; $w = $pct === null ? 0 : max(0, min(100, $pct)); ?>
            <div class="progress-row">
                <div class="progress-row-head">
                    <span class="progress-row-name"><?= e($c['name']) ?></span>
                    <span class="badge badge-<?= e($c['rag']) ?>"><?= $pct === null ? 'Ma\'lumot yo\'q' : e(round($pct)) . '%' ?></span>
                </div>
                <div class="progress" role="progressbar" aria-valuenow="<?= e(round($w)) ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar rag-fill-<?= e($c['rag']) ?>" style="width: <?= e($w) ?>%"></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
