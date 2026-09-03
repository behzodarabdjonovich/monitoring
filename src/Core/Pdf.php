<?php

namespace App\Core;

/**
 * Kutubxonasiz (dependency-free) minimal PDF yozuvchi — jadval ko'rinishidagi
 * hisobotlar uchun. Faqat oddiy matn (Helvetica standart shrifti, WinAnsi
 * kodlash) va ko'p sahifali oqim qo'llab-quvvatlanadi.
 *
 * PDF chop etish ("chop etish imkoniyati") uchun HTML print-view ham mavjud;
 * bu klass to'g'ridan-to'g'ri yuklab olinadigan PDF faylini beradi (README'da
 * hujjatlangan yondashuv).
 *
 * Diqqat: standart PDF shriftlari (Helvetica) faqat WinAnsi (Latin-1)
 * belgilarini ishonchli chizadi. O'zbek lotin matni asosan Latin bo'lgani
 * uchun mos keladi; kodlanmaydigan belgilar '?' bilan almashtiriladi.
 */
final class Pdf
{
    private const PAGE_W = 595.28;   // A4 kengligi (pt)
    private const PAGE_H = 841.89;   // A4 balandligi (pt)
    private const MARGIN = 40.0;
    private const LINE_H = 14.0;

    /**
     * Jadval ma'lumotlaridan PDF hosil qiladi.
     *
     * @param string $title Hisobot sarlavhasi
     * @param array<int,string> $headers Ustun sarlavhalari
     * @param array<int,array<int,scalar|null>> $rows Ma'lumot satrlari
     * @param array<int,string> $meta Qo'shimcha metama'lumot satrlari (masalan sana, filtr)
     */
    public static function table(string $title, array $headers, array $rows, array $meta = []): string
    {
        $lines = [];
        $lines[] = ['text' => $title, 'size' => 16, 'bold' => true];
        foreach ($meta as $m) {
            $lines[] = ['text' => $m, 'size' => 9, 'bold' => false];
        }
        $lines[] = ['text' => '', 'size' => 6, 'bold' => false];

        $headerText = implode('  |  ', array_map(static fn ($h) => (string) $h, $headers));
        $lines[] = ['text' => $headerText, 'size' => 10, 'bold' => true];
        $lines[] = ['text' => str_repeat('_', 100), 'size' => 8, 'bold' => false];

        if ($rows === []) {
            $lines[] = ['text' => "Ma'lumot topilmadi.", 'size' => 10, 'bold' => false];
        }
        foreach ($rows as $row) {
            $cells = array_map(static fn ($c) => (string) ($c ?? ''), array_values($row));
            $lines[] = ['text' => implode('  |  ', $cells), 'size' => 9, 'bold' => false];
        }

        return self::render($lines);
    }

    /**
     * @param array<int,array{text:string,size:int,bold:bool}> $lines
     */
    private static function render(array $lines): string
    {
        // Sahifalarga bo'lamiz.
        $usableHeight = self::PAGE_H - 2 * self::MARGIN;
        $maxLines = (int) floor($usableHeight / self::LINE_H);
        $pages = array_chunk($lines, max(1, $maxLines));

        $objects = [];
        // 1: Catalog, 2: Pages, keyin har sahifa uchun Page + Contents, oxirida Font.
        $pageCount = count($pages);
        $fontObjNum = 3 + $pageCount * 2; // sahifa obyektlaridan keyin

        $catalog = "<< /Type /Catalog /Pages 2 0 R >>";

        $kids = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $kids[] = (3 + $i * 2) . ' 0 R';
        }
        $pagesObj = "<< /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count $pageCount >>";

        $objects[1] = $catalog;
        $objects[2] = $pagesObj;

        for ($i = 0; $i < $pageCount; $i++) {
            $pageObjNum = 3 + $i * 2;
            $contentObjNum = $pageObjNum + 1;

            $stream = self::contentStream($pages[$i]);
            $objects[$pageObjNum] = sprintf(
                "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 %d 0 R >> >> /Contents %d 0 R >>",
                self::PAGE_W,
                self::PAGE_H,
                $fontObjNum,
                $contentObjNum
            );
            $objects[$contentObjNum] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }

        $objects[$fontObjNum] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";

        return self::assemble($objects);
    }

    /**
     * @param array<int,array{text:string,size:int,bold:bool}> $lines
     */
    private static function contentStream(array $lines): string
    {
        $x = self::MARGIN;
        $y = self::PAGE_H - self::MARGIN;
        $out = "BT\n";
        foreach ($lines as $line) {
            $size = $line['size'];
            $text = self::escape(self::encode($line['text']));
            $out .= sprintf("/F1 %d Tf\n1 0 0 1 %.2f %.2f Tm\n(%s) Tj\n", $size, $x, $y, $text);
            $y -= self::LINE_H;
        }
        $out .= "ET";
        return $out;
    }

    /**
     * @param array<int,string> $objects 1-indeksli obyektlar
     */
    private static function assemble(array $objects): string
    {
        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $num . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 $count\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";
        return $pdf;
    }

    private static function encode(string $text): string
    {
        // UTF-8 -> Windows-1252 (WinAnsi). Kodlanmaydigan belgilar '?'.
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        return $converted === false ? preg_replace('/[^\x20-\x7E]/', '?', $text) : $converted;
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $text);
    }
}
