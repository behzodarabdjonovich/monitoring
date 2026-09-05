<?php
/**
 * Front-kontroller (yagona kirish nuqtasi).
 *
 * Bootstrap: autoload + config + sessiya -> Request -> Router -> Response.
 */

declare(strict_types=1);

// PHP built-in serverda mavjud statik fayllarni to'g'ridan-to'g'ri berish.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $file = __DIR__ . $path;

    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

use App\Core\Config;
use App\Core\Middleware\CsrfMiddleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

// Konfiguratsiya va view yo'llari.
Config::setPath($root . '/config');
View::setPath($root . '/resources/views');
date_default_timezone_set(Config::get('app.timezone', 'UTC'));

// Xatolarni ko'rsatish (faqat local).
if (Config::get('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

Session::start();

$router = new Router();
// CSRF barcha o'zgartiruvchi so'rovlarda majburiy (global middleware).
$router->addGlobalMiddleware(new CsrfMiddleware());

require $root . '/routes/web.php';

$request = Request::capture();

try {
    $response = $router->dispatch($request);
} catch (\Throwable $ex) {
    if (Config::get('app.debug')) {
        $response = Response::html(
            '<pre>' . e($ex->getMessage() . "\n\n" . $ex->getTraceAsString()) . '</pre>',
            500
        );
    } else {
        $response = Response::html(View::render('errors.500'), 500);
    }
}

$response->send();
