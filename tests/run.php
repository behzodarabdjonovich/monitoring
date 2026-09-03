<?php
/**
 * Dependency-free test runner.
 *
 * PHPUnit oflayn o'rnatilmaydi (Packagist bloklangan), shuning uchun oddiy
 * assertion asosidagi runner ishlatiladi. Ishga tushirish: php tests/run.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Middleware\RbacMiddleware;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;

Config::setPath($root . '/config');
date_default_timezone_set('UTC');

// ---------------------------------------------------------------
// Minimal test harness.
// ---------------------------------------------------------------
$tests = [];
$passed = 0;
$failed = 0;
$failures = [];

function test(string $name, callable $fn): void
{
    global $tests;
    $tests[$name] = $fn;
}

function assertTrue($cond, string $msg = ''): void
{
    if ($cond !== true) {
        throw new RuntimeException('assertTrue muvaffaqiyatsiz. ' . $msg);
    }
}

function assertFalse($cond, string $msg = ''): void
{
    if ($cond !== false) {
        throw new RuntimeException('assertFalse muvaffaqiyatsiz. ' . $msg);
    }
}

function assertEquals($expected, $actual, string $msg = ''): void
{
    if ($expected != $actual) {
        throw new RuntimeException(sprintf(
            "assertEquals muvaffaqiyatsiz. Kutilgan: %s, olingan: %s. %s",
            var_export($expected, true),
            var_export($actual, true),
            $msg
        ));
    }
}

// ---------------------------------------------------------------
// Alohida (izolyatsiyalangan) in-memory DB ishga tushiramiz, shunda
// testlar dev DB fayliga ta'sir qilmaydi.
// ---------------------------------------------------------------
function bootTestDatabase(): void
{
    putenv('DB_DRIVER=sqlite');
    putenv('DB_DATABASE=:memory:');
    // Config kesh'ini yangilash uchun qayta o'rnatamiz.
    $_ENV['DB_DRIVER'] = 'sqlite';
    Config::setPath(dirname(__DIR__) . '/config');
    DB::reset();

    $root = dirname(__DIR__);
    $migration = require $root . '/database/migrations/001_create_schema.php';
    $migration();
    $seed = require $root . '/database/seeds/001_seed.php';
    $seed();
}

// ---------------------------------------------------------------
// Testlar.
// ---------------------------------------------------------------

test('e() HTML chiqishni ekranlaydi (XSS)', function () {
    $out = e('<script>alert("x")</script>');
    assertEquals('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $out);
    assertEquals('', e(null));
});

test('Router marshrutni va parametrlarni topadi', function () {
    $router = new Router();
    $router->get('/students/{id}', fn () => 'ok');
    $req = new Request('GET', '/students/42', [], [], ['REQUEST_METHOD' => 'GET']);
    $matched = $router->match($req);
    assertTrue($matched !== null, 'Marshrut topilishi kerak edi');
    assertEquals('42', $matched['params']['id']);

    $reqPost = new Request('POST', '/students/42', [], [], ['REQUEST_METHOD' => 'POST']);
    assertTrue($router->match($reqPost) === null, 'POST GET marshrutiga mos kelmasligi kerak');
});

test('Csrf verify yaroqsiz tokenni rad etadi', function () {
    Session::start();
    $token = Csrf::token();
    assertTrue(Csrf::verify($token), 'To\'g\'ri token qabul qilinishi kerak');
    assertFalse(Csrf::verify('yaroqsiz-token'), 'Noto\'g\'ri token rad etilishi kerak');
    assertFalse(Csrf::verify(null), 'Bo\'sh token rad etilishi kerak');
});

test('Auth hash/verify roundtrip', function () {
    $hash = Auth::hash('Parol123!');
    assertTrue(Auth::verify('Parol123!', $hash), 'To\'g\'ri parol tasdiqlanishi kerak');
    assertFalse(Auth::verify('boshqa', $hash), 'Noto\'g\'ri parol rad etilishi kerak');
    assertTrue($hash !== 'Parol123!', 'Parol xesh qilinishi kerak (ochiq matn emas)');
});

test('migrate + seed kutilgan yozuv sonlarini beradi', function () {
    bootTestDatabase();

    $tables = [
        'roles', 'permissions', 'role_permission', 'users', 'departments',
        'specialties', 'doctoral_programs', 'supervisors', 'doctoral_students',
        'individual_plans', 'plan_tasks', 'publications', 'conferences',
        'scientific_results', 'attestations', 'accreditations',
        'accreditation_criteria', 'accreditation_indicators', 'indicator_evidence',
        'documents', 'internal_audits', 'deficiencies', 'action_plans',
        'notifications', 'audit_logs', 'settings', 'password_resets',
    ];
    foreach ($tables as $table) {
        // Har bir jadval mavjudligini tekshiramiz (so'rov xato bermasligi kerak).
        DB::scalar("SELECT COUNT(*) FROM $table");
    }

    assertEquals(9, (int) DB::scalar('SELECT COUNT(*) FROM roles'), '9 ta rol seed qilinishi kerak');
    assertEquals(9, (int) DB::scalar('SELECT COUNT(*) FROM users'), '9 ta demo foydalanuvchi');
    assertTrue((int) DB::scalar('SELECT COUNT(*) FROM permissions') > 0, 'Ruxsatlar seed qilinishi kerak');
    assertTrue((int) DB::scalar('SELECT COUNT(*) FROM role_permission') > 0, 'role_permission matritsasi to\'ldirilishi kerak');
    assertEquals(1, (int) DB::scalar('SELECT COUNT(*) FROM accreditations WHERE is_placeholder = 1'), 'Placeholder akkreditatsiya');
    assertEquals(3, (int) DB::scalar('SELECT COUNT(*) FROM accreditation_criteria'), '3 ta namuna mezon');
    assertEquals(9, (int) DB::scalar('SELECT COUNT(*) FROM accreditation_indicators'), '9 ta namuna indikator');
    assertTrue((int) DB::scalar('SELECT COUNT(*) FROM doctoral_students') >= 20, 'Demo doktorantlar seed qilinishi kerak');
    assertTrue((int) DB::scalar('SELECT COUNT(*) FROM publications') > 0, 'Demo nashrlar seed qilinishi kerak');
});

test('Auth attempt seed qilingan foydalanuvchi bilan ishlaydi va can() to\'g\'ri', function () {
    bootTestDatabase();
    assertTrue(Auth::attempt('admin', 'Parol123!'), 'super_admin login qila olishi kerak');
    assertTrue(Auth::can('settings.configure'), 'super_admin barcha ruxsatlarga ega');
    Auth::logout();

    assertFalse(Auth::attempt('admin', 'noto\'g\'ri'), 'Noto\'g\'ri parol rad etilishi kerak');
});

test('RbacMiddleware ruxsatsiz rolni rad etadi (403)', function () {
    bootTestDatabase();
    // doktorant super-admin-only ruxsatga ega emas.
    Auth::attempt('doktorant', 'Parol123!');
    Auth::flushCache();

    $mw = new RbacMiddleware('users.create');
    $req = new Request('GET', '/users', [], [], ['REQUEST_METHOD' => 'GET']);
    $resp = $mw->handle($req);
    assertTrue($resp !== null, 'Ruxsatsiz foydalanuvchi Response (403) olishi kerak');
    assertEquals(403, $resp->status(), 'Status 403 bo\'lishi kerak');

    // Ruxsati bor amal (doktorant dashboard'ni ko'ra oladi).
    $mwOk = new RbacMiddleware('dashboard.view');
    assertTrue($mwOk->handle($req) === null, 'Ruxsatli foydalanuvchi o\'tishi kerak (null)');
    Auth::logout();
});

test('AuditLogger login yozuvini qo\'shadi', function () {
    bootTestDatabase();
    Auth::attempt('admin', 'Parol123!');
    \App\Core\AuditLogger::log('login', 'users', Auth::id(), null, null, Auth::id(), '127.0.0.1');
    $count = (int) DB::scalar("SELECT COUNT(*) FROM audit_logs WHERE action = 'login'");
    assertTrue($count >= 1, 'Kamida bitta login audit yozuvi bo\'lishi kerak');
    Auth::logout();
});

test('DashboardStats KPI\'lari seed ma\'lumotiga mos keladi', function () {
    bootTestDatabase();
    $data = \App\Models\DashboardStats::compute([]);
    $k = $data['kpis'];

    // Jami doktorantlar = doctoral_students soni.
    $expectedTotal = (int) DB::scalar('SELECT COUNT(*) FROM doctoral_students');
    assertEquals($expectedTotal, $k['total_students'], 'Jami doktorantlar seed soni bilan bir xil');

    // PhD (tayanch_doktorant) soni.
    $expectedPhd = (int) DB::scalar("SELECT COUNT(*) FROM doctoral_students WHERE student_type = 'tayanch_doktorant'");
    assertEquals($expectedPhd, $k['phd'], 'PhD soni to\'g\'ri');

    // DSc + mustaqil izlanuvchi + jami = total.
    assertEquals($k['total_students'], $k['phd'] + $k['dsc'] + $k['independent'], 'Turlar yig\'indisi jamiga teng');

    // Ixtisosliklar / rahbarlar > 0.
    assertTrue($k['specialties'] > 0, 'Ixtisosliklar soni musbat');
    assertTrue($k['supervisors'] > 0, 'Ilmiy rahbarlar soni musbat');

    // Xalqaro maqolalar <= jami maqolalar.
    assertTrue($k['publications_intl'] <= $k['publications'], 'Xalqaro maqolalar jamidan oshmasligi kerak');
    assertEquals(
        (int) DB::scalar("SELECT COUNT(*) FROM publications WHERE publication_type IN ('scopus','wos')"),
        $k['publications_intl'],
        'Xalqaro (scopus/wos) maqolalar soni to\'g\'ri'
    );

    // Dissertatsiya himoyalari.
    assertEquals(
        (int) DB::scalar("SELECT COUNT(*) FROM scientific_results WHERE result_type = 'dissertatsiya_himoyasi'"),
        $k['defenses'],
        'Dissertatsiya himoyalari soni to\'g\'ri'
    );

    // Muammoli indikatorlar = red RAG.
    assertEquals(
        (int) DB::scalar("SELECT COUNT(*) FROM accreditation_indicators WHERE rag_status = 'red'"),
        $k['problem_indicators'],
        'Muammoli indikatorlar (red) soni to\'g\'ri'
    );
});

test('Dashboard HERO tayyorlik foizi va RAG hisoblanadi', function () {
    bootTestDatabase();
    $data = \App\Models\DashboardStats::compute([]);
    $hero = $data['hero'];
    assertTrue($hero['percent'] !== null, 'Tayyorlik foizi hisoblanishi kerak (seed ballari bor)');
    assertTrue($hero['percent'] >= 0 && $hero['percent'] <= 100, 'Foiz 0..100 oralig\'ida');
    assertTrue(in_array($hero['rag'], ['green', 'yellow', 'red', 'grey'], true), 'RAG holati to\'g\'ri');

    // Kamida 3 ta mezon progress bari mavjud.
    assertTrue(count($data['progress']) >= 3, 'Mezonlar bo\'yicha progress barlar mavjud');
});

test('Dashboard filtrlari natijalarni o\'zgartiradi', function () {
    bootTestDatabase();
    $all = \App\Models\DashboardStats::compute([]);

    // dtype = doktorant (DSc) filtri: jami = DSc soni.
    $dsc = \App\Models\DashboardStats::compute(['dtype' => 'doktorant']);
    assertEquals($all['kpis']['dsc'], $dsc['kpis']['total_students'], 'DSc filtri jami doktorantlarni DSc soniga tenglashtiradi');
    assertEquals(0, $dsc['kpis']['phd'], 'DSc filtrida PhD soni 0');
    assertTrue($dsc['kpis']['total_students'] < $all['kpis']['total_students'], 'Filtr to\'plamni toraytiradi');

    // Ixtisoslik filtri: bitta ixtisoslik bo'yicha jami < umumiy.
    $specId = (int) DB::scalar('SELECT id FROM specialties ORDER BY id LIMIT 1');
    $bySpec = \App\Models\DashboardStats::compute(['specialty' => (string) $specId]);
    assertTrue($bySpec['kpis']['total_students'] > 0, 'Tanlangan ixtisoslikda doktorantlar bor');
    assertTrue($bySpec['kpis']['total_students'] <= $all['kpis']['total_students'], 'Ixtisoslik filtri jamini oshirmaydi');

    // sanitizeFilters faqat kutilgan kalitlarni oladi.
    $clean = \App\Models\DashboardStats::sanitizeFilters(['dtype' => 'doktorant', 'zararli' => '1', 'course' => '']);
    assertTrue(isset($clean['dtype']), 'dtype saqlanadi');
    assertFalse(isset($clean['zararli']), 'Noma\'lum kalit tashlanadi');
    assertFalse(isset($clean['course']), 'Bo\'sh qiymat tashlanadi');
});

test('DashboardController /dashboard 200 va grafiklarni render qiladi', function () {
    bootTestDatabase();
    Auth::attempt('admin', 'Parol123!');
    Auth::flushCache();

    $controller = new \App\Controllers\DashboardController();
    $req = new Request('GET', '/dashboard', [], [], ['REQUEST_METHOD' => 'GET']);
    $resp = $controller->index($req);
    assertEquals(200, $resp->status(), 'Dashboard 200 qaytarishi kerak');
    $html = $resp->body();

    assertTrue(str_contains($html, 'MAXSUS DAVLAT AKKREDITATSIYASIGA TAYYORLIK'), 'HERO sarlavhasi mavjud');
    // Kamida 3 ta inline-SVG grafik.
    assertTrue(substr_count($html, '<svg') >= 3, 'Kamida 3 ta inline-SVG grafik render qilinishi kerak');
    // Tashqi CDN/JS yuklanmaydi.
    assertFalse(str_contains($html, 'cdn.'), 'Tashqi CDN havolasi bo\'lmasligi kerak');
    assertFalse(str_contains($html, 'chart.js'), 'Chart.js kutubxonasi ishlatilmasligi kerak');
    assertFalse(str_contains($html, 'https://'), 'Tashqi https resurs havolasi bo\'lmasligi kerak');

    // Filtr bilan: DSc jami PhD'siz.
    $req2 = new Request('GET', '/dashboard', ['dtype' => 'doktorant'], [], ['REQUEST_METHOD' => 'GET']);
    $resp2 = $controller->index($req2);
    assertEquals(200, $resp2->status(), 'Filtrlangan dashboard 200');
    assertTrue($resp2->body() !== $html, 'Filtrlangan sahifa boshqacha bo\'lishi kerak');
    Auth::logout();
});

// ---------------------------------------------------------------
// FEAT-004: holat mashinasi, overdue, faoliyat/samaradorlik hisoblari.
// ---------------------------------------------------------------

test('PlanTask holat mashinasi yaroqli o\'tishlarni qabul qiladi', function () {
    $PT = \App\Models\PlanTask::class;
    assertTrue($PT::canTransition($PT::PLANNED, $PT::IN_PROGRESS), 'planned->in_progress yaroqli');
    assertTrue($PT::canTransition($PT::IN_PROGRESS, $PT::COMPLETED), 'in_progress->completed yaroqli');
    assertTrue($PT::canTransition($PT::COMPLETED, $PT::SUPERVISOR_APPROVED), 'completed->supervisor_approved yaroqli');
    assertTrue($PT::canTransition($PT::SUPERVISOR_APPROVED, $PT::FINALIZED), 'supervisor_approved->finalized yaroqli');
    // Overdue (legacy) planned bilan teng boshlang'ich holat.
    assertTrue($PT::canTransition($PT::OVERDUE, $PT::IN_PROGRESS), 'overdue->in_progress yaroqli');
});

test('PlanTask holat mashinasi yaroqsiz (sakrash/orqaga) o\'tishlarni rad etadi', function () {
    $PT = \App\Models\PlanTask::class;
    // Bosqichni sakrab o'tish taqiqlanadi.
    assertFalse($PT::canTransition($PT::PLANNED, $PT::COMPLETED), 'planned->completed sakrash taqiq');
    assertFalse($PT::canTransition($PT::PLANNED, $PT::FINALIZED), 'planned->finalized sakrash taqiq');
    assertFalse($PT::canTransition($PT::IN_PROGRESS, $PT::SUPERVISOR_APPROVED), 'in_progress->supervisor_approved sakrash taqiq');
    // Orqaga qaytish taqiqlanadi.
    assertFalse($PT::canTransition($PT::COMPLETED, $PT::IN_PROGRESS), 'orqaga qaytish taqiq');
    assertFalse($PT::canTransition($PT::FINALIZED, $PT::SUPERVISOR_APPROVED), 'yakuniydan orqaga taqiq');
    // Yakuniy holatdan chiqish yo'q.
    assertFalse($PT::canTransition($PT::FINALIZED, $PT::FINALIZED), 'finalized->finalized o\'zgarishsiz');
});

test('PlanTask rol gating: doktorant faqat Bajarilgancha', function () {
    $PT = \App\Models\PlanTask::class;
    // Doktorant: planned->in_progress va in_progress->completed OK.
    assertTrue($PT::roleCanTransition('doctoral_student', $PT::PLANNED, $PT::IN_PROGRESS));
    assertTrue($PT::roleCanTransition('doctoral_student', $PT::IN_PROGRESS, $PT::COMPLETED));
    // Doktorant Bajarilgandan keyingi tasdiqni bajara olmaydi.
    assertFalse($PT::roleCanTransition('doctoral_student', $PT::COMPLETED, $PT::SUPERVISOR_APPROVED), 'doktorant rahbar tasdig\'ini bera olmaydi');
});

test('PlanTask rol gating: rahbar va bo\'lim faqat o\'z bosqichida', function () {
    $PT = \App\Models\PlanTask::class;
    // Ilmiy rahbar: completed->supervisor_approved OK, boshqasi yo'q.
    assertTrue($PT::roleCanTransition('supervisor', $PT::COMPLETED, $PT::SUPERVISOR_APPROVED));
    assertFalse($PT::roleCanTransition('supervisor', $PT::IN_PROGRESS, $PT::COMPLETED), 'rahbar doktorant bosqichini bajara olmaydi');
    assertFalse($PT::roleCanTransition('supervisor', $PT::SUPERVISOR_APPROVED, $PT::FINALIZED), 'rahbar yakuniy tasdiqni bera olmaydi');
    // Doktorantura bo'limi: supervisor_approved->finalized OK.
    assertTrue($PT::roleCanTransition('doctorate_office', $PT::SUPERVISOR_APPROVED, $PT::FINALIZED));
    assertFalse($PT::roleCanTransition('doctorate_office', $PT::COMPLETED, $PT::SUPERVISOR_APPROVED), 'bo\'lim rahbar bosqichini bajara olmaydi');
    // Nazorat rollari (super_admin) barcha yaroqli o'tishni bajaradi.
    assertTrue($PT::roleCanTransition('super_admin', $PT::COMPLETED, $PT::SUPERVISOR_APPROVED));
    assertTrue($PT::roleCanTransition('super_admin', $PT::SUPERVISOR_APPROVED, $PT::FINALIZED));
    // Ammo yaroqsiz o'tish nazorat roli uchun ham taqiq.
    assertFalse($PT::roleCanTransition('super_admin', $PT::PLANNED, $PT::FINALIZED), 'nazorat roli ham sakrab o\'tolmaydi');
});

test('PlanTask muddati o\'tgan (overdue) vazifani aniqlaydi (qizil)', function () {
    $PT = \App\Models\PlanTask::class;
    $past = date('Y-m-d', strtotime('-10 days'));
    $future = date('Y-m-d', strtotime('+10 days'));

    // Muddati o'tgan va bajarilmagan => overdue (qizil).
    assertTrue($PT::isOverdue(['due_date' => $past, 'status' => $PT::PLANNED]), 'muddati o\'tgan planned = overdue');
    assertTrue($PT::isOverdue(['due_date' => $past, 'status' => $PT::IN_PROGRESS]), 'muddati o\'tgan in_progress = overdue');
    // Bajarilgan/tasdiqlangan => overdue emas.
    assertFalse($PT::isOverdue(['due_date' => $past, 'status' => $PT::COMPLETED]), 'bajarilgan overdue emas');
    assertFalse($PT::isOverdue(['due_date' => $past, 'status' => $PT::FINALIZED]), 'yakuniy overdue emas');
    // Kelajakdagi muddat => overdue emas.
    assertFalse($PT::isOverdue(['due_date' => $future, 'status' => $PT::PLANNED]), 'kelajak muddat overdue emas');
});

test('PlanTaskController yaroqsiz o\'tishni rad etadi, yaroqlisini yozadi + audit', function () {
    bootTestDatabase();
    // Doktorant sifatida kiramiz.
    Auth::attempt('doktorant', 'Parol123!');
    Auth::flushCache();

    // Planned holatdagi vazifa yaratamiz.
    $planId = (int) DB::scalar('SELECT id FROM individual_plans ORDER BY id LIMIT 1');
    $taskId = DB::insert('plan_tasks', [
        'plan_id' => $planId,
        'title' => 'Test vazifa',
        'status' => 'planned',
        'progress_percent' => 0,
        'due_date' => date('Y-m-d', strtotime('+30 days')),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $ctrl = new \App\Controllers\PlanTaskController();

    // Yaroqsiz: planned->finalized (sakrash + rol mos emas) — rad etilishi kerak.
    $reqBad = new Request('POST', "/tasks/$taskId", [], ['target_status' => 'finalized'], ['REQUEST_METHOD' => 'POST']);
    $reqBad->setParams(['id' => (string) $taskId]);
    $ctrl->update($reqBad);
    $after = DB::selectOne('SELECT status FROM plan_tasks WHERE id = :id', ['id' => $taskId]);
    assertEquals('planned', $after['status'], 'Yaroqsiz o\'tish holatni o\'zgartirmasligi kerak');

    // Yaroqli: planned->in_progress (doktorant).
    $reqOk = new Request('POST', "/tasks/$taskId", [], ['target_status' => 'in_progress'], ['REQUEST_METHOD' => 'POST']);
    $reqOk->setParams(['id' => (string) $taskId]);
    $ctrl->update($reqOk);
    $after2 = DB::selectOne('SELECT status FROM plan_tasks WHERE id = :id', ['id' => $taskId]);
    assertEquals('in_progress', $after2['status'], 'Yaroqli o\'tish holatni yangilashi kerak');

    // Audit yozuvi (approve) qo'shilgan bo'lishi kerak.
    $auditCount = (int) DB::scalar("SELECT COUNT(*) FROM audit_logs WHERE entity_type = 'plan_tasks' AND action = 'approve'");
    assertTrue($auditCount >= 1, 'O\'tish audit_logs yozuvini qo\'shishi kerak');
    Auth::logout();
});

test('DoctoralStudent faoliyat foizi vazifalar bajarilishidan hisoblanadi', function () {
    bootTestDatabase();
    // Yangi reja + vazifalar yaratamiz va foizni tekshiramiz.
    $studentId = (int) DB::scalar('SELECT id FROM doctoral_students ORDER BY id LIMIT 1');
    $planId = DB::insert('individual_plans', [
        'student_id' => $studentId,
        'academic_year' => '2099/2100',
        'status' => 'approved',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    // Eski rejalarni bu studentdan olib tashlaymiz (aniq hisob uchun).
    DB::run('DELETE FROM plan_tasks WHERE plan_id IN (SELECT id FROM individual_plans WHERE student_id = :sid AND id <> :pid)', ['sid' => $studentId, 'pid' => $planId]);
    DB::run('DELETE FROM individual_plans WHERE student_id = :sid AND id <> :pid', ['sid' => $studentId, 'pid' => $planId]);

    foreach ([100, 50, 0, 50] as $pct) {
        DB::insert('plan_tasks', [
            'plan_id' => $planId,
            'title' => 'V',
            'status' => 'planned',
            'progress_percent' => $pct,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
    // (100+50+0+50)/4 = 50.0
    $activity = \App\Models\DoctoralStudent::activityPercent($studentId);
    assertEquals(50.0, $activity, 'Faoliyat foizi vazifalar o\'rtachasiga teng');
});

test('Supervisor umumiy samaradorlik ko\'rsatkichi hisoblanadi', function () {
    bootTestDatabase();
    // Doktoranti bor rahbar uchun 0..100 oralig'ida qiymat, doktoranti yo'q -> null.
    $supWith = (int) DB::scalar('SELECT supervisor_id FROM doctoral_students WHERE supervisor_id IS NOT NULL ORDER BY supervisor_id LIMIT 1');
    $eff = \App\Models\Supervisor::effectiveness($supWith);
    assertTrue($eff !== null, 'Doktoranti bor rahbar uchun ko\'rsatkich hisoblanadi');
    assertTrue($eff >= 0 && $eff <= 100, 'Samaradorlik 0..100 oralig\'ida');

    // Doktoranti bo'lmagan yangi rahbar => null.
    $emptySup = DB::insert('supervisors', ['full_name' => 'Bo\'sh Rahbar', 'created_at' => date('Y-m-d H:i:s')]);
    assertTrue(\App\Models\Supervisor::effectiveness($emptySup) === null, 'Doktoranti yo\'q rahbar uchun null');
});

test('Specialty akkreditatsiyaga tayyorlik foizi ScoringEngine\'dan keladi', function () {
    bootTestDatabase();
    $specId = (int) DB::scalar('SELECT id FROM specialties WHERE accreditation_id IS NOT NULL ORDER BY id LIMIT 1');
    $r = \App\Models\Specialty::accreditationReadiness($specId);
    assertTrue($r['accreditation_id'] !== null, 'Ixtisoslik akkreditatsiyaga bog\'langan');
    assertTrue($r['percent'] !== null, 'Tayyorlik foizi hisoblanadi (seed ballari bor)');
    assertTrue(in_array($r['rag'], ['green', 'yellow', 'red', 'grey'], true), 'RAG holati to\'g\'ri');
    // ScoringEngine bilan bir xil natija.
    $expected = \App\Core\ScoringEngine::assessAccreditation((int) $r['accreditation_id']);
    assertEquals($expected['readiness_index'], $r['percent'], 'Foiz ScoringEngine bilan mos');
});

// ---------------------------------------------------------------
// FEAT-005: ilmiy natijalar + dalillar bazasi + M:N + himoyalangan yuklab olish.
// ---------------------------------------------------------------

/**
 * Test yordamchisi: vaqtinchalik faylni FileStorage-mos $_FILES massiviga
 * o'raydi (CLI'da is_uploaded_file ishlamaydi — FileStorage copy() fallback).
 */
function makeUpload(string $name, string $mime, string $contents): array
{
    $tmp = tempnam(sys_get_temp_dir(), 'upl');
    file_put_contents($tmp, $contents);
    return [
        'name' => $name,
        'type' => $mime,
        'tmp_name' => $tmp,
        'error' => UPLOAD_ERR_OK,
        'size' => strlen($contents),
    ];
}

// Minimal yaroqli PDF/PNG/JPEG baytlari (MIME sniffing uchun sarlavha muhim).
// PNG/JPEG uchun GD bilan haqiqiy (kichik) rasm hosil qilamiz — finfo aniq
// image/png va image/jpeg qaytarishi uchun.
$PDF_BYTES = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF";
$makeImage = static function (string $format): string {
    $im = imagecreatetruecolor(2, 2);
    ob_start();
    if ($format === 'png') {
        imagepng($im);
    } else {
        imagejpeg($im);
    }
    $bytes = (string) ob_get_clean();
    imagedestroy($im);
    return $bytes;
};
$PNG_BYTES = $makeImage('png');
$JPG_BYTES = $makeImage('jpeg');

test('FileStorage ruxsat etilgan turlarni (pdf/jpg/png) qabul qiladi', function () use ($PDF_BYTES, $PNG_BYTES, $JPG_BYTES) {
    bootTestDatabase();

    $pdf = \App\Core\FileStorage::store(makeUpload('dalil.pdf', 'application/pdf', $PDF_BYTES));
    assertEquals('application/pdf', $pdf['mime'], 'PDF MIME aniqlanishi kerak');
    assertTrue(str_ends_with($pdf['path'], '.pdf'), 'PDF kengaytmasi saqlanishi kerak');

    $png = \App\Core\FileStorage::store(makeUpload('rasm.png', 'image/png', $PNG_BYTES));
    assertEquals('image/png', $png['mime'], 'PNG MIME aniqlanishi kerak');

    $jpg = \App\Core\FileStorage::store(makeUpload('rasm.jpg', 'image/jpeg', $JPG_BYTES));
    assertEquals('image/jpeg', $jpg['mime'], 'JPEG MIME aniqlanishi kerak');
});

test('FileStorage ruxsat etilmagan turlarni rad etadi (kengaytma va MIME)', function () use ($PDF_BYTES) {
    bootTestDatabase();

    // Ruxsat etilmagan kengaytma (.exe).
    $threw = false;
    try {
        \App\Core\FileStorage::store(makeUpload('virus.exe', 'application/octet-stream', 'MZ' . str_repeat("\x00", 32)));
    } catch (\RuntimeException) {
        $threw = true;
    }
    assertTrue($threw, 'Ruxsat etilmagan kengaytma rad etilishi kerak');

    // Ruxsat etilgan kengaytma, LEKIN mos kelmaydigan/ruxsatsiz MIME (.pdf
    // nomi ostidagi matn fayl => text/plain, oq ro'yxatda yo'q).
    $threw2 = false;
    try {
        \App\Core\FileStorage::store(makeUpload('soxta.pdf', 'application/pdf', 'shunchaki oddiy matn, PDF emas'));
    } catch (\RuntimeException) {
        $threw2 = true;
    }
    assertTrue($threw2, 'MIME oq ro\'yxatda bo\'lmagan fayl rad etilishi kerak');

    // Hajmi juda katta fayl rad etilishi kerak.
    $big = makeUpload('katta.pdf', 'application/pdf', $PDF_BYTES);
    $big['size'] = 50 * 1024 * 1024; // 50 MB (chegara 10 MB)
    $threw3 = false;
    try {
        \App\Core\FileStorage::store($big);
    } catch (\RuntimeException) {
        $threw3 = true;
    }
    assertTrue($threw3, 'Chegaradan katta fayl rad etilishi kerak');
});

test('Document M:N: bir hujjat bir nechta indikatorga bog\'lanadi va uziladi', function () {
    bootTestDatabase();
    Auth::attempt('admin', 'Parol123!');
    Auth::flushCache();

    $docId = DB::insert('documents', [
        'title' => 'Test dalil', 'category' => 'buyruqlar',
        'file_path' => 'storage/uploads/test.pdf', 'original_name' => 'test.pdf',
        'mime_type' => 'application/pdf', 'file_size' => 100, 'doc_type' => 'dalil',
        'uploaded_by' => Auth::id(), 'created_at' => date('Y-m-d H:i:s'),
    ]);
    // Toza (dalilsiz) ikkita yangi indikator yaratamiz — seed dalillari
    // aralashmasligi uchun.
    $critId = (int) DB::scalar('SELECT id FROM accreditation_criteria ORDER BY id LIMIT 1');
    $mk = static fn (string $code) => DB::insert('accreditation_indicators', [
        'criteria_id' => $critId, 'code' => $code, 'name' => 'MN ' . $code,
        'weight' => 1.0, 'rag_status' => 'grey',
        'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
    ]);
    $i1 = $mk('MN.1');
    $i2 = $mk('MN.2');

    // Ikki indikatorga bog'laymiz.
    assertTrue(\App\Models\Document::linkToIndicator($docId, $i1, Auth::id()), 'Birinchi bog\'lash muvaffaqiyatli');
    assertTrue(\App\Models\Document::linkToIndicator($docId, $i2, Auth::id()), 'Ikkinchi bog\'lash muvaffaqiyatli');

    // Takroriy bog'lash rad etiladi (UNIQUE yaxlitligi).
    assertFalse(\App\Models\Document::linkToIndicator($docId, $i1, Auth::id()), 'Takroriy bog\'lash rad etilishi kerak');

    // Hujjat ostida ikkala indikator ko'rinadi.
    $linked = \App\Models\Document::linkedIndicators($docId);
    assertEquals(2, count($linked), 'Hujjat 2 ta indikatorga bog\'langan bo\'lishi kerak');

    // Har indikator ostida hujjat ko'rinadi.
    assertEquals(1, count(\App\Models\Document::forIndicator($i1)), 'Indikator 1 ostida hujjat ko\'rinadi');
    assertEquals(1, count(\App\Models\Document::forIndicator($i2)), 'Indikator 2 ostida hujjat ko\'rinadi');

    // Uzish ishlaydi.
    assertTrue(\App\Models\Document::unlinkFromIndicator($docId, $i1), 'Uzish muvaffaqiyatli');
    assertEquals(1, count(\App\Models\Document::linkedIndicators($docId)), 'Uzilgandan keyin 1 ta qoladi');
    assertEquals(0, count(\App\Models\Document::forIndicator($i1)), 'Uzilgan indikator ostida hujjat qolmaydi');
    Auth::logout();
});

test('ScoringEngine: dalilsiz indikator grey, dalil bog\'langach qayta hisoblanadi', function () {
    bootTestDatabase();
    Auth::attempt('admin', 'Parol123!');
    Auth::flushCache();

    // Dalilsiz indikator yaratamiz (ball bilan, lekin evidence yo'q).
    $critId = (int) DB::scalar('SELECT id FROM accreditation_criteria ORDER BY id LIMIT 1');
    $indId = DB::insert('accreditation_indicators', [
        'criteria_id' => $critId, 'code' => 'TEST.EV', 'name' => 'Test indikator',
        'weight' => 1.0, 'rag_status' => 'green', 'score' => 90.0,
        'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
    ]);
    // Dalil yo'q => refresh grey qaytaradi.
    assertEquals('grey', \App\Core\ScoringEngine::refreshIndicator($indId), 'Dalilsiz indikator grey bo\'lishi kerak');
    assertEquals('grey', DB::scalar('SELECT rag_status FROM accreditation_indicators WHERE id = :id', ['id' => $indId]), 'DB rag_status grey');

    // Dalil bog'laymiz => yuqori ball green bo'ladi.
    $docId = DB::insert('documents', [
        'title' => 'EV', 'category' => 'boshqa', 'file_path' => 'storage/uploads/ev.pdf',
        'original_name' => 'ev.pdf', 'mime_type' => 'application/pdf', 'file_size' => 100,
        'doc_type' => 'dalil', 'uploaded_by' => Auth::id(), 'created_at' => date('Y-m-d H:i:s'),
    ]);
    \App\Models\Document::linkToIndicator($docId, $indId, Auth::id());
    assertEquals('green', \App\Core\ScoringEngine::refreshIndicator($indId), 'Dalil + 90 ball => green');
    Auth::logout();
});

test('DocumentController himoyalangan yuklab olish ruxsatsiz rolni rad etadi', function () use ($PDF_BYTES) {
    bootTestDatabase();

    // Fayl yuklaymiz (admin sifatida) va documents yozuvini yaratamiz.
    Auth::attempt('admin', 'Parol123!');
    Auth::flushCache();
    $stored = \App\Core\FileStorage::store(makeUpload('himoya.pdf', 'application/pdf', $PDF_BYTES));
    $docId = DB::insert('documents', [
        'title' => 'Himoyalangan', 'category' => 'buyruqlar', 'file_path' => $stored['path'],
        'original_name' => $stored['original_name'], 'mime_type' => $stored['mime'],
        'file_size' => $stored['size'], 'doc_type' => 'dalil', 'uploaded_by' => Auth::id(),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    Auth::logout();

    $ctrl = new \App\Controllers\DocumentController();
    $req = new Request('GET', "/documents/$docId/download", [], [], ['REQUEST_METHOD' => 'GET']);
    $req->setParams(['id' => (string) $docId]);

    // Ruxsatli rol (documents.view bor) — 200 va fayl mazmuni.
    Auth::attempt('admin', 'Parol123!');
    Auth::flushCache();
    $ok = $ctrl->download($req);
    assertEquals(200, $ok->status(), 'Ruxsatli rol faylni ola olishi kerak');
    assertTrue($ok->body() === $PDF_BYTES, 'Yuklab olingan mazmun asl fayl bilan mos kelishi kerak');
    Auth::logout();

    // Ruxsatsiz holat: hech kim kirmagan (documents.view yo'q) — 403.
    Auth::flushCache();
    $denied = $ctrl->download($req);
    assertEquals(403, $denied->status(), 'Ruxsatsiz (documents.view yo\'q) so\'rov 403 olishi kerak');
    assertTrue($denied->body() !== $PDF_BYTES, 'Ruxsatsiz so\'rovga fayl mazmuni berilmasligi kerak');
});

test('ScientificResult barcha turlarni lookup sifatida qamrab oladi', function () {
    $types = \App\Models\ScientificResult::TYPES;
    // Foydalanuvchi topshirig'idagi 14 ta tur mavjud bo'lishi kerak.
    $expected = [
        'ilmiy_maqola', 'oak_maqola', 'scopus_maqola', 'wos_maqola',
        'xalqaro_konferensiya', 'respublika_konferensiya', 'monografiya',
        'oquv_uslubiy_nashr', 'patent', 'mualliflik_guvohnomasi', 'grant',
        'xalqaro_loyiha', 'ilmiy_seminar', 'boshqa',
    ];
    foreach ($expected as $key) {
        assertTrue(isset($types[$key]), "Tur mavjud bo'lishi kerak: $key");
    }
    assertEquals(14, count($types), '14 ta ilmiy natija turi bo\'lishi kerak');
});

test('ScientificResultController fayl bilan va havola bilan natija yozadi', function () use ($PDF_BYTES) {
    bootTestDatabase();
    Auth::attempt('doktorant', 'Parol123!');
    Auth::flushCache();

    $studentId = (int) DB::scalar('SELECT id FROM doctoral_students ORDER BY id LIMIT 1');
    $ctrl = new \App\Controllers\ScientificResultController();

    // 1) Fayl bilan (Scopus maqola => publications specializatsiyasi).
    $before = (int) DB::scalar('SELECT COUNT(*) FROM scientific_results');
    $reqFile = new Request('POST', '/results', [], [
        'result_type' => 'scopus_maqola', 'title' => 'Test Scopus maqola',
        'student_id' => (string) $studentId, 'achieved_at' => '2024-01-01',
    ], ['REQUEST_METHOD' => 'POST'], ['evidence_file' => makeUpload('m.pdf', 'application/pdf', $PDF_BYTES)]);
    $ctrl->store($reqFile);
    $r1 = DB::selectOne('SELECT * FROM scientific_results ORDER BY id DESC LIMIT 1');
    assertEquals('scopus_maqola', $r1['result_type'], 'Tur saqlanishi kerak');
    assertTrue($r1['document_id'] !== null, 'Fayl bog\'langan hujjat bo\'lishi kerak');
    assertTrue($r1['publication_id'] !== null, 'Scopus maqola publications specializatsiyasini to\'ldiradi');

    // 2) Havola bilan (grant => URL).
    $reqUrl = new Request('POST', '/results', [], [
        'result_type' => 'grant', 'title' => 'Test grant',
        'student_id' => (string) $studentId, 'url' => 'https://example.org/grant',
    ], ['REQUEST_METHOD' => 'POST']);
    $ctrl->store($reqUrl);
    $r2 = DB::selectOne('SELECT * FROM scientific_results ORDER BY id DESC LIMIT 1');
    assertEquals('grant', $r2['result_type'], 'Grant turi saqlanishi kerak');
    assertEquals('https://example.org/grant', $r2['url'], 'Havola saqlanishi kerak');
    assertTrue($r2['document_id'] === null, 'Havola variantida fayl bo\'lmasligi kerak');

    $after = (int) DB::scalar('SELECT COUNT(*) FROM scientific_results');
    assertEquals($before + 2, $after, 'Ikkita natija qo\'shilishi kerak');
    Auth::logout();
});

// ---------------------------------------------------------------
// Runner.
// ---------------------------------------------------------------
echo "ADPI Monitoring — test runner\n";
echo str_repeat('-', 48) . "\n";

foreach ($tests as $name => $fn) {
    try {
        $fn();
        $passed++;
        echo "  [PASS] $name\n";
    } catch (\Throwable $ex) {
        $failed++;
        $failures[] = "$name: " . $ex->getMessage();
        echo "  [FAIL] $name\n";
    }
}

echo str_repeat('-', 48) . "\n";
echo "Jami: " . ($passed + $failed) . ", muvaffaqiyatli: $passed, muvaffaqiyatsiz: $failed\n";

if ($failed > 0) {
    echo "\nMuvaffaqiyatsizliklar:\n";
    foreach ($failures as $f) {
        echo "  - $f\n";
    }
    exit(1);
}

echo "Barcha testlar muvaffaqiyatli o'tdi.\n";
exit(0);
