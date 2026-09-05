<?php
/**
 * Doktorant uchun alohida kabinet.
 *
 * @var \App\Core\View $this
 * @var array|null $user
 */
?>

<div class="page-header">
    <div>
        <h1>Doktorant kabineti</h1>
        <p class="text-muted">
            Individual reja va ilmiy faoliyat monitoringi
        </p>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <h3>Individual reja</h3>
        <p>Individual rejangiz va bajarilish holatini ko‘ring.</p>
        <a class="btn btn-primary" href="/plans">Rejani ko‘rish</a>
    </div>

    <div class="card">
        <h3>Ilmiy natijalar</h3>
        <p>Maqola, konferensiya va boshqa ilmiy natijalarni boshqaring.</p>
        <a class="btn btn-primary" href="/results">Natijalarni ko‘rish</a>
    </div>

    <div class="card">
        <h3>Hujjatlar</h3>
        <p>Tasdiqlovchi hujjatlar va fayllarni boshqaring.</p>
        <a class="btn btn-primary" href="/documents">Hujjatlarni ko‘rish</a>
    </div>
</div>
