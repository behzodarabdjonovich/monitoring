<?php
/**
 * Doktorant uchun alohida kabinet.
 *
 * @var \App\Core\View $this
 * @var array|null $user
 * @var array|null $student
 * @var float|int $activityPercent
 * @var array $profileData
 */

$student = $student ?? null;
$activityPercent = $activityPercent ?? 0;

$fullName = $student['full_name']
    ?? $user['full_name']
    ?? $user['name']
    ?? 'Doktorant';

$specialty = $student['specialty_name'] ?? '—';
$specialtyCode = $student['specialty_code'] ?? '';
$department = $student['department_name'] ?? '—';
$supervisor = $student['supervisor_name'] ?? '—';
$programType = $student['program_type'] ?? '—';

$percent = max(0, min(100, (int) round((float) $activityPercent)));
?>

<div class="page-header">
    <div>
        <h1>Doktorant kabineti</h1>
        <p class="text-muted">
            Individual reja, ilmiy faoliyat va hujjatlar monitoringi
        </p>
    </div>
</div>

<?php if ($student): ?>
    <div class="card">
        <h2><?= e($fullName) ?></h2>

        <div class="card-grid">
            <div>
                <strong>Mutaxassislik</strong>
                <p>
                    <?= e($specialtyCode) ?>
                    <?= $specialtyCode !== '' ? ' — ' : '' ?>
                    <?= e($specialty) ?>
                </p>
            </div>

            <div>
                <strong>Kafedra</strong>
                <p><?= e($department) ?></p>
            </div>

            <div>
                <strong>Ilmiy rahbar</strong>
                <p><?= e($supervisor) ?></p>
            </div>

            <div>
                <strong>Dastur turi</strong>
                <p><?= e($programType) ?></p>
            </div>
        </div>

        <div style="margin-top: 1rem;">
            <strong>Individual reja bajarilishi — <?= $percent ?>%</strong>

            <div class="progress" style="margin-top: .5rem;">
                <div
                    class="progress-bar"
                    style="width: <?= $percent ?>%;"
                    role="progressbar"
                    aria-valuenow="<?= $percent ?>"
                    aria-valuemin="0"
                    aria-valuemax="100"
                ></div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        Ushbu foydalanuvchiga doktorant profili biriktirilmagan.
    </div>
<?php endif; ?>

<h2 class="section-title">Kabinet bo‘limlari</h2>

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
