<?php
/**
 * HTTP marshrutlar.
 *
 * Bu bosqichda (foundation) faqat autentifikatsiya va dashboard stub
 * ulangan. Qolgan modul marshrutlari keyingi bosqichlarda docs/04 bo'yicha
 * qo'shiladi.
 */

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
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
