<?php

namespace App\Core;

use App\Core\Middleware\Middleware;
use App\Core\Middleware\SecurityHeadersMiddleware;

/**
 * Marshrutlash: GET/POST (va PUT/PATCH/DELETE) marshrutlarini ro'yxatga
 * oladi, yo'l parametrlarini ({id}) ajratadi, middleware quvurini ishga
 * tushiradi va Controller@action'ni chaqiradi.
 */
final class Router
{
    /** @var array<int,array{method:string,pattern:string,regex:string,params:string[],handler:mixed,middleware:array}> */
    private array $routes = [];

    /** @var callable(Request):?Response|null Global middleware fabrikasi (masalan CSRF) */
    private $globalMiddleware = [];

    public function get(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /**
     * Har so'rovda ishlaydigan global middleware (Middleware instansiyasi).
     */
    public function addGlobalMiddleware(Middleware $mw): void
    {
        $this->globalMiddleware[] = $mw;
    }

    public function add(string $method, string $path, mixed $handler, array $middleware = []): void
    {
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            $path = '/';
        }

        $params = [];
        $regex = preg_replace_callback('#\{(\w+)\}#', static function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $path);
        $regex = '#^' . $regex . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $path,
            'regex' => $regex,
            'params' => $params,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * So'rovga mos marshrutni topadi.
     *
     * @return array{route:array,params:array<string,string>}|null
     */
    public function match(Request $request): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }
            if (preg_match($route['regex'], $request->path(), $matches)) {
                array_shift($matches);
                $params = [];
                foreach ($route['params'] as $i => $name) {
                    $params[$name] = $matches[$i] ?? null;
                }
                return ['route' => $route, 'params' => $params];
            }
        }
        return null;
    }

    /**
     * So'rovni dispatch qiladi: middleware quvuri -> controller -> Response.
     */
    public function dispatch(Request $request): Response
    {
        // Har qanday chiqishga (404, 419, controller javobi) xavfsizlik
        // sarlavhalari qo'llaniladi (item 19 — global himoya).
        return SecurityHeadersMiddleware::apply($this->route($request));
    }

    private function route(Request $request): Response
    {
        $matched = $this->match($request);
        if ($matched === null) {
            // Path mavjud, lekin metod mos kelmasa 405 aniqlash mumkin edi;
            // soddalik uchun 404.
            return $this->notFound();
        }

        $route = $matched['route'];
        $request->setParams($matched['params']);

        // Global middleware (masalan CSRF).
        foreach ($this->globalMiddleware as $mw) {
            $result = $mw->handle($request);
            if ($result instanceof Response) {
                return $result;
            }
        }

        // Marshrut middleware zanjiri (Auth, Rbac, ...).
        foreach ($route['middleware'] as $mw) {
            $instance = $mw instanceof Middleware ? $mw : new $mw();
            $result = $instance->handle($request);
            if ($result instanceof Response) {
                return $result;
            }
        }

        return $this->invoke($route['handler'], $request);
    }

    private function invoke(mixed $handler, Request $request): Response
    {
        if (is_callable($handler)) {
            $result = $handler($request);
        } elseif (is_array($handler)) {
            [$class, $action] = $handler;
            $controller = is_object($class) ? $class : new $class();
            $result = $controller->$action($request);
        } elseif (is_string($handler) && str_contains($handler, '@')) {
            [$class, $action] = explode('@', $handler, 2);
            $class = 'App\\Controllers\\' . $class;
            $controller = new $class();
            $result = $controller->$action($request);
        } else {
            throw new \RuntimeException('Yaroqsiz marshrut ishlovchisi (handler).');
        }

        if ($result instanceof Response) {
            return $result;
        }
        if (is_string($result)) {
            return Response::html($result);
        }
        if (is_array($result)) {
            return Response::json($result);
        }
        return Response::html('');
    }

    private function notFound(): Response
    {
        $html = View::render('errors.404');
        return Response::html($html, 404);
    }
}
