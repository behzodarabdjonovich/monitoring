<?php
/**
 * HTTP marshrutlar.
 *
 * Bu bosqichda (foundation) faqat autentifikatsiya va dashboard stub
 * ulangan. Qolgan modul marshrutlari keyingi bosqichlarda docs/04 bo'yicha
 * qo'shiladi.
 */

use App\Controllers\AttestationController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\PlanController;
use App\Controllers\PlanTaskController;
use App\Controllers\ProgramController;
use App\Controllers\SpecialtyController;
use App\Controllers\StudentController;
use App\Controllers\SupervisorController;
use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\RbacMiddleware;
use App\Core\Router;

/** @var Router $router */

// --- Autentifikatsiya (mehmon) ---
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/forgot-password', [AuthController::class, 'showForgot']);
$router->post('/forgot-password', [AuthController::class, 'sendReset']);
$router->get('/reset-password', [AuthController::class, 'showReset']);
$router->post('/reset-password', [AuthController::class, 'reset']);
$router->post('/logout', [AuthController::class, 'logout'], [new AuthMiddleware()]);

// --- Dashboard (autentifikatsiya + RBAC) ---
$router->get('/', [DashboardController::class, 'index'], [
    new AuthMiddleware(),
    new RbacMiddleware('dashboard.view'),
]);
$router->get('/dashboard', [DashboardController::class, 'index'], [
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
$router->post('/tasks/{id}', [PlanTaskController::class, 'update'], [$auth(), $rbac('individual_plans.view')]);

// --- Attestatsiya (item 4/21) ---
$router->get('/attestations', [AttestationController::class, 'index'], [$auth(), $rbac('attestations.view')]);
$router->post('/attestations', [AttestationController::class, 'store'], [$auth(), $rbac('attestations.create')]);
$router->get('/attestations/{id}', [AttestationController::class, 'show'], [$auth(), $rbac('attestations.view')]);
$router->post('/attestations/{id}/approve', [AttestationController::class, 'approve'], [$auth(), $rbac('attestations.approve')]);
$router->post('/attestations/{id}', [AttestationController::class, 'update'], [$auth(), $rbac('attestations.edit')]);
