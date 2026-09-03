<?php
/**
 * Dashboard (stub). To'liq KPI kartalar, tayyorlik gauge va grafiklar
 * keyingi bosqichda (docs/05) qo'shiladi.
 *
 * @var \App\Core\View $this
 * @var array|null $user
 */
$this->layout('layouts.app');
$active = 'dashboard';
$fullName = $user['full_name'] ?? 'Foydalanuvchi';
?>
<div class="page-header">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <span>Bosh sahifa</span>
    </nav>
    <h1 class="page-title">Dashboard</h1>
</div>

<div class="card welcome-card">
    <h2>Xush kelibsiz, <?= e($fullName) ?>!</h2>
    <p>
        Bu — <strong><?= e(config('app.institute')) ?></strong> Doktorantura monitoringi
        va maxsus davlat akkreditatsiyasiga tayyorgarlik axborot tizimi.
    </p>
    <p class="text-muted">
        Bu bosqich (foundation) yakunlangach, keyingi bosqichlarda modullar
        (doktorantlar, rejalar, akkreditatsiya va h.k.) hamda dashboard KPI
        kartalari va grafiklari to'ldiriladi.
    </p>
</div>

<div class="card-grid">
    <div class="card kpi-card">
        <span class="kpi-label">Doktorantlar</span>
        <span class="kpi-value">&mdash;</span>
        <span class="badge badge-grey">Ma'lumot yo'q</span>
    </div>
    <div class="card kpi-card">
        <span class="kpi-label">Tayyorlik indeksi</span>
        <span class="kpi-value">&mdash;</span>
        <span class="badge badge-grey">Hisoblanmagan</span>
    </div>
    <div class="card kpi-card">
        <span class="kpi-label">Ochiq kamchiliklar</span>
        <span class="kpi-value">&mdash;</span>
        <span class="badge badge-grey">Ma'lumot yo'q</span>
    </div>
    <div class="card kpi-card">
        <span class="kpi-label">Chora-tadbirlar</span>
        <span class="kpi-value">&mdash;</span>
        <span class="badge badge-grey">Ma'lumot yo'q</span>
    </div>
</div>

<div class="card">
    <h3>RAG holat namunasi</h3>
    <p class="text-muted">Holat indikatorlari rang + matn bilan ko'rsatiladi:</p>
    <div class="rag-legend">
        <span class="badge badge-green">Yashil — bajarilgan</span>
        <span class="badge badge-yellow">Sariq — qisman</span>
        <span class="badge badge-red">Qizil — bajarilmagan</span>
        <span class="badge badge-grey">Kulrang — ma'lumot yo'q</span>
    </div>
</div>
