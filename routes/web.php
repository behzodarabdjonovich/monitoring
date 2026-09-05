<?php
/**
 * HTTP marshrutlar.
 *
 * Bu bosqichda (foundation) faqat autentifikatsiya va dashboard stub
 * ulangan. Qolgan modul marshrutlari keyingi bosqichlarda docs/04 bo'yicha
 * qo'shiladi.
 */

use App\Controllers\AccreditationController;
use App\Controllers\AttestationController;
use App\Controllers\AuditLogController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\DeficiencyController;
use App\Controllers\DocumentController;
use App\Controllers\InternalAuditController;
use App\Controllers\NotificationController;
use App\Controllers\PlanController;
use App\Controllers\PlanTaskController;
use App\Controllers\ProgramController;
use App\Controllers\ReportController;
use App\Controllers\ScientificResultController;
use App\Controllers\SearchController;
use App\Controllers\SettingsController;
use App\Controllers\SpecialtyController;
use App\Controllers\StudentController;
use App\Controllers\SupervisorController;
use App\Controllers\UserController;
use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\RbacMiddleware;
use App\Core\Middleware\SuperAdminMiddleware;
use App\Core\Router;
use App\Controllers\DoctoralAuthController;
use App\Controllers\DoctoralDashboardController;

/** @var Router $router */

// --- Autentifikatsiya (mehmon) ---

$router->get('/', function () {
    return \App\Core\Response::redirect('/login');
});

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);

// --- Doktorant portali ---

$router->get('/doktorant/login', [
    DoctoralAuthController::class,
    'showLogin'
]);

$router->post('/doktorant/login', [
    DoctoralAuthController::class,
    'login'
]);

// --- Asosiy dashboard ---
$router->get('/dashboard', [
    DashboardController::class,
    'index'
], [
    new AuthMiddleware(),
    new RbacMiddleware('dashboard.view'),
]);

// --- Doktorant dashboard ---
$router->get('/doktorant/dashboard', [
    DoctoralDashboardController::class,
    'index'
], [
    new AuthMiddleware(),
    new RbacMiddleware('dashboard.view'),
]);

$router->get('/doktorant/dashboard', [
    DashboardController::class,
    'index'
], [
    new AuthMiddleware(),
    new RbacMiddleware('dashboard.view'),
]);

/**
 * Modul marshrutlari uchun yordamchi: har biri Auth + RBAC middleware bilan.
 */
$auth = static fn () => new AuthMiddleware();
$rbac = static fn (string $perm) => new RbacMiddleware($perm);

// --- Doktorantlar (item 4) ---
$router->get('/students', [StudentController::class, 'index'], [$auth(), $rbac('doctoral_students.view')]);
$router->get('/students/create', [StudentController::class, 'create'], [$auth(), $rbac('doctoral_students.create')]);
$router->post('/students', [StudentController::class, 'store'], [$auth(), $rbac('doctoral_students.create')]);
$router->get('/students/{id}', [StudentController::class, 'show'], [$auth(), $rbac('doctoral_students.view')]);
$router->get('/students/{id}/edit', [StudentController::class, 'edit'], [$auth(), $rbac('doctoral_students.edit')]);
$router->post('/students/{id}', [StudentController::class, 'update'], [$auth(), $rbac('doctoral_students.edit')]);

// --- Ilmiy rahbarlar (item 7) ---
$router->get('/supervisors', [SupervisorController::class, 'index'], [$auth(), $rbac('supervisors.view')]);
$router->get('/supervisors/create', [SupervisorController::class, 'create'], [$auth(), $rbac('supervisors.create')]);
$router->post('/supervisors', [SupervisorController::class, 'store'], [$auth(), $rbac('supervisors.create')]);
$router->get('/supervisors/{id}', [SupervisorController::class, 'show'], [$auth(), $rbac('supervisors.view')]);
$router->get('/supervisors/{id}/edit', [SupervisorController::class, 'edit'], [$auth(), $rbac('supervisors.edit')]);
$router->post('/supervisors/{id}', [SupervisorController::class, 'update'], [$auth(), $rbac('supervisors.edit')]);

// --- Ixtisosliklar va dasturlar (item 8) ---
$router->get('/specialties', [SpecialtyController::class, 'index'], [$auth(), $rbac('specialties.view')]);
$router->get('/specialties/create', [SpecialtyController::class, 'create'], [$auth(), $rbac('specialties.create')]);
$router->post('/specialties', [SpecialtyController::class, 'store'], [$auth(), $rbac('specialties.create')]);
$router->get('/specialties/{id}', [SpecialtyController::class, 'show'], [$auth(), $rbac('specialties.view')]);
$router->get('/specialties/{id}/edit', [SpecialtyController::class, 'edit'], [$auth(), $rbac('specialties.edit')]);
$router->post('/specialties/{id}', [SpecialtyController::class, 'update'], [$auth(), $rbac('specialties.edit')]);
$router->get('/programs', [ProgramController::class, 'index'], [$auth(), $rbac('specialties.view')]);
$router->post('/programs', [ProgramController::class, 'store'], [$auth(), $rbac('specialties.create')]);

// --- Individual rejalar + vazifalar (item 5) ---
$router->get('/plans', [PlanController::class, 'index'], [$auth(), $rbac('individual_plans.view')]);
$router->get('/plans/create', [PlanController::class, 'create'], [$auth(), $rbac('individual_plans.create')]);
$router->post('/plans', [PlanController::class, 'store'], [$auth(), $rbac('individual_plans.create')]);
$router->get('/plans/{id}', [PlanController::class, 'show'], [$auth(), $rbac('individual_plans.view')]);
$router->get('/plans/{id}/edit', [PlanController::class, 'edit'], [$auth(), $rbac('individual_plans.edit')]);
$router->post('/plans/{id}', [PlanController::class, 'update'], [$auth(), $rbac('individual_plans.edit')]);
$router->post('/plans/{id}/approve', [PlanController::class, 'approve'], [$auth(), $rbac('individual_plans.approve')]);
$router->post('/plans/{id}/tasks', [PlanTaskController::class, 'store'], [$auth(), $rbac('individual_plans.edit')]);
// Marshrut individual_plans.view bilan ochiq (holat o'tishi so'rovi uchun),
// biroq vazifa MAYDONLARINI yozish kontrollerda individual_plans.edit bilan
// gate qilinadi; holat o'tishi esa holat mashinasi + rol gating orqali.
$router->post('/tasks/{id}', [PlanTaskController::class, 'update'], [$auth(), $rbac('individual_plans.view')]);

// --- Ilmiy natijalar (item 6) ---
$router->get('/results', [ScientificResultController::class, 'index'], [$auth(), $rbac('scientific_results.view')]);
$router->get('/results/create', [ScientificResultController::class, 'create'], [$auth(), $rbac('scientific_results.create')]);
$router->post('/results', [ScientificResultController::class, 'store'], [$auth(), $rbac('scientific_results.create')]);

// --- Dalillar bazasi (item 11) + M:N indikator bog'lash ---
$router->get('/documents', [DocumentController::class, 'index'], [$auth(), $rbac('documents.view')]);
$router->post('/documents', [DocumentController::class, 'store'], [$auth(), $rbac('documents.upload')]);
$router->get('/documents/{id}', [DocumentController::class, 'show'], [$auth(), $rbac('documents.view')]);
$router->get('/documents/{id}/download', [DocumentController::class, 'download'], [$auth(), $rbac('documents.view')]);
// Bog'lash/uzish marshrut guard'i kontroller ichidagi qat'iyroq tekshiruvga
// (documents.edit YOKI accreditation.edit) moslashtirildi.
$router->post('/documents/{id}/link', [DocumentController::class, 'link'], [$auth(), new RbacMiddleware('documents.edit', 'accreditation.edit')]);
$router->post('/documents/{id}/unlink', [DocumentController::class, 'unlink'], [$auth(), new RbacMiddleware('documents.edit', 'accreditation.edit')]);

// --- Attestatsiya (item 4/21) ---
$router->get('/attestations', [AttestationController::class, 'index'], [$auth(), $rbac('attestations.view')]);
$router->post('/attestations', [AttestationController::class, 'store'], [$auth(), $rbac('attestations.create')]);
$router->get('/attestations/{id}', [AttestationController::class, 'show'], [$auth(), $rbac('attestations.view')]);
$router->post('/attestations/{id}/approve', [AttestationController::class, 'approve'], [$auth(), $rbac('attestations.approve')]);
$router->post('/attestations/{id}', [AttestationController::class, 'update'], [$auth(), $rbac('attestations.edit')]);

// --- MAXSUS DAVLAT AKKREDITATSIYASI (item 9-10) — ENG ASOSIY modul ---
// Akkreditatsiya -> Mezon -> Indikator -> Talab -> Dalil -> Baho -> Kamchilik -> Chora-tadbir.
$router->get('/accreditations', [AccreditationController::class, 'index'], [$auth(), $rbac('accreditation.view')]);
$router->get('/accreditations/create', [AccreditationController::class, 'create'], [$auth(), $rbac('accreditation.create')]);
$router->post('/accreditations', [AccreditationController::class, 'store'], [$auth(), $rbac('accreditation.create')]);
$router->get('/accreditations/{id}', [AccreditationController::class, 'show'], [$auth(), $rbac('accreditation.view')]);
$router->get('/accreditations/{id}/edit', [AccreditationController::class, 'edit'], [$auth(), $rbac('accreditation.edit')]);
$router->post('/accreditations/{id}', [AccreditationController::class, 'update'], [$auth(), $rbac('accreditation.edit')]);
$router->post('/accreditations/{id}/criteria', [AccreditationController::class, 'storeCriterion'], [$auth(), $rbac('accreditation.edit')]);
$router->post('/accreditations/{id}/clear-placeholder', [AccreditationController::class, 'clearPlaceholder'], [$auth(), $rbac('accreditation.configure')]);
// Mezon (Criteria) -> indikatorlar ro'yxati (nested navigation).
$router->get('/criteria/{id}', [AccreditationController::class, 'criterion'], [$auth(), $rbac('accreditation.view')]);
$router->post('/criteria/{id}', [AccreditationController::class, 'updateCriterion'], [$auth(), $rbac('accreditation.edit')]);
$router->post('/criteria/{id}/indicators', [AccreditationController::class, 'storeIndicator'], [$auth(), $rbac('accreditation.edit')]);
// Indikator kartasi (barcha item-9 maydonlari) + baho + tahrir.
$router->get('/indicators/{id}', [AccreditationController::class, 'indicator'], [$auth(), $rbac('accreditation.view')]);
$router->post('/indicators/{id}', [AccreditationController::class, 'updateIndicator'], [$auth(), $rbac('accreditation.edit')]);
$router->post('/indicators/{id}/assess', [AccreditationController::class, 'assess'], [$auth(), $rbac('accreditation.approve')]);

// --- Kamchiliklar (Deficiencies) va chora-tadbirlar (Action Plan) (item 12) ---
// Zanjir: Muammo -> Sabab -> Chora-tadbir -> Mas'ul -> Boshlanish -> Muddat -> Dalil -> Natija.
$router->get('/deficiencies', [DeficiencyController::class, 'index'], [$auth(), $rbac('deficiencies.view')]);
$router->post('/deficiencies', [DeficiencyController::class, 'store'], [$auth(), $rbac('deficiencies.create')]);
$router->get('/deficiencies/{id}', [DeficiencyController::class, 'show'], [$auth(), $rbac('deficiencies.view')]);
$router->post('/deficiencies/{id}', [DeficiencyController::class, 'update'], [$auth(), $rbac('deficiencies.edit')]);
$router->post('/deficiencies/{id}/close', [DeficiencyController::class, 'close'], [$auth(), $rbac('deficiencies.edit')]);
$router->post('/deficiencies/{id}/plans', [DeficiencyController::class, 'storePlan'], [$auth(), $rbac('action_plans.view')]);
// Action Plan bo'limi (muddat holatlari bilan barcha chora-tadbirlar).
$router->get('/action-plans', [DeficiencyController::class, 'plans'], [$auth(), $rbac('action_plans.view')]);
$router->post('/action-plans/{id}', [DeficiencyController::class, 'updatePlan'], [$auth(), $rbac('action_plans.edit')]);

// --- Ichki akkreditatsiya auditi (item 13) — per-ixtisoslik report ---
$router->get('/audits', [InternalAuditController::class, 'index'], [$auth(), $rbac('internal_audits.view')]);
$router->post('/audits/run', [InternalAuditController::class, 'run'], [$auth(), $rbac('internal_audits.audit')]);
$router->get('/audits/{id}', [InternalAuditController::class, 'show'], [$auth(), $rbac('internal_audits.view')]);

// --- Sozlamalar (Sozlamalar) — baholash metodikasi (Super Admin) ---
$router->get('/settings', [SettingsController::class, 'index'], [$auth(), $rbac('settings.view')]);
$router->post('/settings', [SettingsController::class, 'update'], [$auth(), $rbac('settings.configure')]);

// --- Hisobotlar (item 14) — print / Excel / PDF eksport ---
$router->get('/reports', [ReportController::class, 'index'], [$auth(), $rbac('reports.view')]);
$router->get('/reports/{type}', [ReportController::class, 'show'], [$auth(), $rbac('reports.view')]);

// --- Bildirishnomalar (item 15) — shaxsiy kabinet ---
$router->get('/notifications', [NotificationController::class, 'index'], [$auth(), $rbac('notifications.view')]);
$router->post('/notifications/generate', [NotificationController::class, 'generate'], [$auth(), $rbac('notifications.view')]);
$router->post('/notifications/read-all', [NotificationController::class, 'markAllRead'], [$auth(), $rbac('notifications.view')]);
$router->post('/notifications/{id}/read', [NotificationController::class, 'markRead'], [$auth(), $rbac('notifications.view')]);

// --- Global qidiruv (item 16) — topbar qidiruv qutisi ---
$router->get('/search', [SearchController::class, 'index'], [$auth()]);

// --- Audit jurnali ko'rigi (item 17) — FAQAT o'qish, FAQAT Super Admin ---
// O'chirish/yangilash marshruti ATAYLAB YO'Q (audit_logs o'zgarmas).
$router->get('/audit-logs', [AuditLogController::class, 'index'], [$auth(), new SuperAdminMiddleware()]);

// --- Foydalanuvchilar boshqaruvi (item 19) — bloklash, parol majburlash, 2FA ---
$router->get('/users', [UserController::class, 'index'], [$auth(), $rbac('users.view')]);
$router->post('/users/{id}/block', [UserController::class, 'block'], [$auth(), $rbac('users.edit')]);
$router->post('/users/{id}/unblock', [UserController::class, 'unblock'], [$auth(), $rbac('users.edit')]);
$router->post('/users/{id}/force-reset', [UserController::class, 'forceReset'], [$auth(), $rbac('users.edit')]);
$router->post('/users/{id}/twofa', [UserController::class, 'toggleTwofa'], [$auth(), $rbac('users.edit')]);
