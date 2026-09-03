<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Pdf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Spreadsheet;
use App\Models\Report;

/**
 * Hisobotlar moduli (item 14).
 *
 * Har bir hisobot turi uchun uchta chiqish:
 *   - HTML print-view (chop etishga mos CSS, format=print yoki oddiy ko'rish);
 *   - Excel eksporti (.xlsx — kutubxonasiz OpenXML yozuvchi, format=excel);
 *   - PDF eksporti (.pdf — kutubxonasiz minimal PDF yozuvchi, format=pdf).
 *
 * "Chop etish imkoniyati" print-view orqali (brauzer print-to-PDF ham) beriladi.
 */
final class ReportController extends Controller
{
    /**
     * Hisobotlar ro'yxati (barcha turlar).
     */
    public function index(Request $request): Response
    {
        return $this->view('reports.index', [
            'user' => Auth::user(),
            'title' => 'Hisobotlar',
            'active' => 'reports',
            'types' => Report::types(),
        ]);
    }

    /**
     * Bitta hisobotni ko'rsatadi/eksport qiladi. format: view (standart) |
     * print | excel | pdf.
     */
    public function show(Request $request): Response
    {
        $type = (string) $request->param('type');
        if (!Report::exists($type)) {
            return $this->notFound();
        }
        $format = (string) $request->query('format', 'view');
        $data = Report::build($type);
        $title = Report::title($type);
        $generatedAt = date('Y-m-d H:i');

        return match ($format) {
            'excel' => $this->exportExcel($type, $title, $data),
            'pdf' => $this->exportPdf($title, $data, $generatedAt),
            'print' => $this->view('reports.print', [
                'reportTitle' => $title,
                'headers' => $data['headers'],
                'rows' => $data['rows'],
                'generatedAt' => $generatedAt,
                'type' => $type,
            ]),
            default => $this->view('reports.show', [
                'user' => Auth::user(),
                'title' => $title,
                'active' => 'reports',
                'reportTitle' => $title,
                'reportType' => $type,
                'headers' => $data['headers'],
                'rows' => $data['rows'],
                'generatedAt' => $generatedAt,
            ]),
        };
    }

    private function exportExcel(string $type, string $title, array $data): Response
    {
        [$body, $mime, $ext] = Spreadsheet::build($title, $data['headers'], $data['rows']);
        $filename = $this->filename($type, $ext);
        return (new Response($body, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($body),
        ]));
    }

    private function exportPdf(string $title, array $data, string $generatedAt): Response
    {
        $meta = [
            'Andijon davlat pedagogika instituti — doktorantura monitoringi',
            'Shakllantirilgan sana: ' . $generatedAt,
        ];
        $body = Pdf::table($title, $data['headers'], $data['rows'], $meta);
        $filename = $this->filename($this->slug($title), 'pdf');
        return (new Response($body, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($body),
        ]));
    }

    private function filename(string $type, string $ext): string
    {
        return 'hisobot-' . $type . '-' . date('Ymd') . '.' . $ext;
    }

    private function slug(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? 'hisobot';
        return trim($text, '-') ?: 'hisobot';
    }

    private function notFound(): Response
    {
        return $this->view('errors.404', [], 404);
    }
}
