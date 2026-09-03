<?php

namespace App\Controllers;

use App\Core\Response;
use App\Core\View;

/**
 * Bazaviy kontroller: view render qilish va redirect uchun yordamchilar.
 */
abstract class Controller
{
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return Response::html(View::render($template, $data), $status);
    }

    protected function redirect(string $to): Response
    {
        return Response::redirect($to);
    }
}
