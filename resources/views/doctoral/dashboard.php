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
            Individual reja, ilmiy faoliyat va hujjatlar monitoringi
        </p>
    </div>
</div>

<div class="card-grid">

    <div class="card">
        <h3>Individual reja</h3>
        <p>Shaxsiy rejangiz va uning bajarilish holatini kuzating.</p>
        <a class="btn btn-primary" href="/plans">Ochish</a>
    </div>

    <div class="card">
        <h3>Ilmiy natijalar</h3>
        <p>Maqola, konferensiya va boshqa ilmiy natijalarni ko‘ring.</p>
        <a class="btn btn-primary" href="/results">Ochish</a>
    </div>

    <div class="card">
        <h3>Hujjatlar</h3>
        <p>Tasdiqlovchi hujjatlar va yuklangan fayllarni boshqaring.</p>
        <a class="btn btn-primary" href="/documents">Ochish</a>
    </div>

    <div class="card">
        <h3>Attestatsiya</h3>
        <p>Attestatsiya natijalari va holatini ko‘ring.</p>
        <a class="btn btn-primary" href="/attestations">Ochish</a>
    </div>

    <div class="card">
        <h3>Bildirishnomalar</h3>
        <p>Tizimdagi yangi xabar va eslatmalarni ko‘ring.</p>
        <a class="btn btn-primary" href="/notifications">Ochish</a>
    </div>

    <div class="card">
        <h3>Kabinet</h3>
        <p>Doktorant profiliga oid asosiy ma’lumotlar va holat.</p>
        <a class="btn btn-primary" href="/doktorant/dashboard">Ochish</a>
    </div>

</div>
