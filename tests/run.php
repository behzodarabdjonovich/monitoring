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
    assertEquals(6, (int) DB::scalar('SELECT COUNT(*) FROM accreditation_indicators'), '6 ta namuna indikator');
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
