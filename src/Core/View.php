<?php

namespace App\Core;

/**
 * Server tomonda PHP shablonlarni render qiladi (layout + partiallar).
 * Barcha dinamik chiqish view'lar ichida e() helperi bilan ekranlanadi.
 *
 * Shablon ichida layout tanlash uchun: $this->layout('layouts.app');
 * Partial chaqirish uchun:               $this->partial('partials.sidebar', [...]);
 */
final class View
{
    private static ?string $viewsPath = null;
    private ?string $chosenLayout = null;

    public static function setPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/');
    }

    public static function path(): string
    {
        return self::$viewsPath ?? dirname(__DIR__, 2) . '/resources/views';
    }

    private static function file(string $template): string
    {
        $file = self::path() . '/' . str_replace('.', '/', $template) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View topilmadi: $template ($file)");
        }
        return $file;
    }

    /**
     * View'ni (ixtiyoriy layout bilan) render qiladi.
     *
     * @param array<string,mixed> $data
     */
    public static function render(string $template, array $data = []): string
    {
        $view = new self();
        $content = $view->evaluate(self::file($template), $data);

        if ($view->chosenLayout !== null) {
            $data['content'] = $content;
            $layoutView = new self();
            return $layoutView->evaluate(self::file($view->chosenLayout), $data);
        }

        return $content;
    }

    /**
     * Layout tanlash (shablon ichidan chaqiriladi).
     */
    public function layout(string $name): void
    {
        $this->chosenLayout = $name;
    }

    /**
     * HTML-ekranlash (shablon ichidan qulay kirish).
     */
    public function e(mixed $value): string
    {
        return e($value);
    }

    /**
     * Partial render qiladi (topbar, sidebar, banner va h.k.).
     *
     * @param array<string,mixed> $vars
     */
    public function partial(string $name, array $vars = []): string
    {
        $partial = new self();
        return $partial->evaluate(self::file($name), $vars);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function evaluate(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $ex) {
            ob_end_clean();
            throw $ex;
        }
        return (string) ob_get_clean();
    }
}
