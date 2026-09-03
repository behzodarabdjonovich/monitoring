<?php

namespace App\Core;

/**
 * O'z ichida joylashtirilgan (self-hosted) inline-SVG grafik yordamchisi.
 *
 * Oflayn muhit cheklovi (docs/01): Chart.js kabi tashqi JS kutubxona yoki CDN
 * ISHLATILMAYDI. Barcha grafiklar server tomonda oddiy SVG string sifatida
 * render qilinadi. Matnli qiymatlar e() bilan ekranlanadi (XSS himoyasi).
 *
 * Qo'llab-quvvatlanadigan turlar:
 *   - bar()   : gorizontal ustunli diagramma (yorliq + qiymat + foiz).
 *   - donut() : halqa (donut) diagramma segmentlar bilan.
 *   - line()  : chiziqli diagramma (dinamika).
 *   - gauge() : yarim/to'liq halqa ko'rinishidagi katta tayyorlik ko'rsatkichi.
 *   - progress(): CSS progress bar (SVG emas, lekin bir joyda).
 */
final class Chart
{
    /** RAG rang kodlari CSS o'zgaruvchilariga mos. */
    public const RAG_COLORS = [
        'green' => '#2E9E5B',
        'yellow' => '#E0A800',
        'red' => '#D64545',
        'grey' => '#9AA5B1',
    ];

    private const PALETTE = ['#2E75B6', '#1F4E79', '#2E9E5B', '#E0A800', '#D64545', '#7A5FB0', '#0F9D9D', '#B5651D'];

    /**
     * Gorizontal ustunli diagramma.
     *
     * @param array<int,array{label:string,value:float,color?:string}> $data
     */
    public static function bar(array $data, array $opts = []): string
    {
        $width = (int) ($opts['width'] ?? 460);
        $rowH = 30;
        $gap = 10;
        $labelW = (int) ($opts['labelWidth'] ?? 150);
        $valueW = 56;
        $barMax = $width - $labelW - $valueW - 12;
        $height = max(1, count($data)) * ($rowH + $gap) + 8;

        $max = 0.0;
        foreach ($data as $d) {
            $max = max($max, (float) $d['value']);
        }
        if ($max <= 0.0) {
            $max = 1.0;
        }

        $svg = self::open($width, $height, $opts['title'] ?? 'Ustunli diagramma');
        $y = 4;
        $ci = 0;
        foreach ($data as $d) {
            $val = (float) $d['value'];
            $w = (int) round(($val / $max) * $barMax);
            $color = $d['color'] ?? self::PALETTE[$ci % count(self::PALETTE)];
            $label = e($d['label']);
            $valTxt = e(self::num($val));
            $barY = $y + ($rowH - 18) / 2;
            $svg .= '<text x="0" y="' . ($y + $rowH / 2 + 4) . '" class="c-label">' . $label . '</text>';
            $svg .= '<rect x="' . $labelW . '" y="' . $barY . '" width="' . $barMax . '" height="18" rx="4" class="c-track"/>';
            $svg .= '<rect x="' . $labelW . '" y="' . $barY . '" width="' . max(0, $w) . '" height="18" rx="4" fill="' . e($color) . '"/>';
            $svg .= '<text x="' . ($width - 4) . '" y="' . ($y + $rowH / 2 + 4) . '" class="c-value" text-anchor="end">' . $valTxt . '</text>';
            $y += $rowH + $gap;
            $ci++;
        }
        return self::close($svg);
    }

    /**
     * Halqa (donut) diagramma.
     *
     * @param array<int,array{label:string,value:float,color?:string}> $data
     */
    public static function donut(array $data, array $opts = []): string
    {
        $size = (int) ($opts['size'] ?? 200);
        $cx = $size / 2;
        $cy = $size / 2;
        $r = $size / 2 - 8;
        $inner = $r * 0.58;

        $total = 0.0;
        foreach ($data as $d) {
            $total += (float) $d['value'];
        }

        $svg = self::open($size, $size, $opts['title'] ?? 'Halqa diagramma');
        if ($total <= 0.0) {
            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="#E1E6EC"/>';
            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $inner . '" fill="#fff"/>';
            $svg .= '<text x="' . $cx . '" y="' . ($cy + 4) . '" class="c-center" text-anchor="middle">—</text>';
            return self::close($svg);
        }

        $angle = -90.0; // yuqoridan boshlash
        $ci = 0;
        foreach ($data as $d) {
            $val = (float) $d['value'];
            if ($val <= 0.0) {
                $ci++;
                continue;
            }
            $sweep = ($val / $total) * 360.0;
            $end = $angle + $sweep;
            $color = $d['color'] ?? self::PALETTE[$ci % count(self::PALETTE)];
            $large = $sweep > 180 ? 1 : 0;
            [$x1, $y1] = self::polar($cx, $cy, $r, $angle);
            [$x2, $y2] = self::polar($cx, $cy, $r, $end);
            $svg .= '<path d="M ' . $cx . ' ' . $cy . ' L ' . self::c($x1) . ' ' . self::c($y1)
                . ' A ' . $r . ' ' . $r . ' 0 ' . $large . ' 1 ' . self::c($x2) . ' ' . self::c($y2) . ' Z" '
                . 'fill="' . e($color) . '"/>';
            $angle = $end;
            $ci++;
        }
        // markazdagi teshik + umumiy son
        $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $inner . '" fill="#fff"/>';
        $center = $opts['center'] ?? self::num($total);
        $svg .= '<text x="' . $cx . '" y="' . ($cy + 5) . '" class="c-center" text-anchor="middle">' . e($center) . '</text>';
        return self::close($svg);
    }

    /**
     * Chiziqli diagramma (dinamika).
     *
     * @param array<int,array{label:string,value:float}> $data
     */
    public static function line(array $data, array $opts = []): string
    {
        $width = (int) ($opts['width'] ?? 460);
        $height = (int) ($opts['height'] ?? 180);
        $padL = 34;
        $padB = 26;
        $padT = 10;
        $padR = 10;
        $plotW = $width - $padL - $padR;
        $plotH = $height - $padT - $padB;
        $n = count($data);

        $max = 0.0;
        foreach ($data as $d) {
            $max = max($max, (float) $d['value']);
        }
        if ($max <= 0.0) {
            $max = 1.0;
        }

        $svg = self::open($width, $height, $opts['title'] ?? 'Chiziqli diagramma');
        // o'qlar
        $svg .= '<line x1="' . $padL . '" y1="' . $padT . '" x2="' . $padL . '" y2="' . ($padT + $plotH) . '" class="c-axis"/>';
        $svg .= '<line x1="' . $padL . '" y1="' . ($padT + $plotH) . '" x2="' . ($padL + $plotW) . '" y2="' . ($padT + $plotH) . '" class="c-axis"/>';

        if ($n === 0) {
            return self::close($svg);
        }

        $stepX = $n > 1 ? $plotW / ($n - 1) : 0;
        $points = [];
        foreach ($data as $i => $d) {
            $x = $padL + ($n > 1 ? $i * $stepX : $plotW / 2);
            $y = $padT + $plotH - (((float) $d['value'] / $max) * $plotH);
            $points[] = [$x, $y];
        }

        // maydon (area) + chiziq
        if ($n > 1) {
            $path = 'M ' . self::c($points[0][0]) . ' ' . self::c($points[0][1]);
            foreach (array_slice($points, 1) as $p) {
                $path .= ' L ' . self::c($p[0]) . ' ' . self::c($p[1]);
            }
            $svg .= '<path d="' . $path . '" fill="none" stroke="#2E75B6" stroke-width="2"/>';
        }
        foreach ($points as $i => $p) {
            $svg .= '<circle cx="' . self::c($p[0]) . '" cy="' . self::c($p[1]) . '" r="3" fill="#1F4E79"/>';
            $lbl = e($data[$i]['label']);
            $svg .= '<text x="' . self::c($p[0]) . '" y="' . ($padT + $plotH + 16) . '" class="c-tick" text-anchor="middle">' . $lbl . '</text>';
        }
        return self::close($svg);
    }

    /**
     * Katta tayyorlik halqasi (gauge) — HERO uchun. Foiz + RAG rangi.
     */
    public static function gauge(?float $percent, string $rag, array $opts = []): string
    {
        $size = (int) ($opts['size'] ?? 200);
        $cx = $size / 2;
        $cy = $size / 2;
        $r = $size / 2 - 14;
        $stroke = 16;
        $color = self::RAG_COLORS[$rag] ?? self::RAG_COLORS['grey'];
        $pct = $percent === null ? 0.0 : max(0.0, min(100.0, $percent));
        $circ = 2 * M_PI * $r;
        $dash = ($pct / 100.0) * $circ;

        $label = $percent === null ? '—' : self::num(round($percent)) . '%';
        $svg = self::open($size, $size, $opts['title'] ?? 'Tayyorlik ko\'rsatkichi');
        $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="#E1E6EC" stroke-width="' . $stroke . '"/>';
        if ($percent !== null) {
            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="' . e($color) . '" '
                . 'stroke-width="' . $stroke . '" stroke-linecap="round" '
                . 'stroke-dasharray="' . self::c($dash) . ' ' . self::c($circ) . '" '
                . 'transform="rotate(-90 ' . $cx . ' ' . $cy . ')"/>';
        }
        $svg .= '<text x="' . $cx . '" y="' . ($cy + 8) . '" class="c-gauge" text-anchor="middle" fill="' . e($color) . '">' . e($label) . '</text>';
        return self::close($svg);
    }

    /**
     * Stacked (yig'ma) gorizontal ustun — kafedra/segment bo'yicha RAG.
     *
     * @param array<int,array{label:string,segments:array<int,array{value:float,color:string}>}> $rows
     */
    public static function stackedBar(array $rows, array $opts = []): string
    {
        $width = (int) ($opts['width'] ?? 460);
        $rowH = 26;
        $gap = 12;
        $labelW = (int) ($opts['labelWidth'] ?? 150);
        $barMax = $width - $labelW - 10;
        $height = max(1, count($rows)) * ($rowH + $gap) + 8;

        $svg = self::open($width, $height, $opts['title'] ?? 'Yig\'ma ustunli diagramma');
        $y = 4;
        foreach ($rows as $row) {
            $total = 0.0;
            foreach ($row['segments'] as $s) {
                $total += (float) $s['value'];
            }
            if ($total <= 0.0) {
                $total = 1.0;
            }
            $svg .= '<text x="0" y="' . ($y + $rowH / 2 + 4) . '" class="c-label">' . e($row['label']) . '</text>';
            $x = $labelW;
            foreach ($row['segments'] as $s) {
                $val = (float) $s['value'];
                if ($val <= 0.0) {
                    continue;
                }
                $w = ($val / $total) * $barMax;
                $svg .= '<rect x="' . self::c($x) . '" y="' . ($y + 4) . '" width="' . self::c($w) . '" height="' . ($rowH - 8) . '" fill="' . e($s['color']) . '"/>';
                $x += $w;
            }
            $y += $rowH + $gap;
        }
        return self::close($svg);
    }

    // ------------------------------------------------------------------

    private static function open(int $w, int $h, string $title): string
    {
        return '<svg class="chart" role="img" viewBox="0 0 ' . $w . ' ' . $h . '" '
            . 'width="100%" preserveAspectRatio="xMinYMin meet" xmlns="http://www.w3.org/2000/svg" '
            . 'aria-label="' . e($title) . '"><title>' . e($title) . '</title>';
    }

    private static function close(string $inner): string
    {
        return $inner . '</svg>';
    }

    private static function polar(float $cx, float $cy, float $r, float $angleDeg): array
    {
        $rad = deg2rad($angleDeg);
        return [$cx + $r * cos($rad), $cy + $r * sin($rad)];
    }

    private static function c(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    private static function num(float $n): string
    {
        if (floor($n) == $n) {
            return (string) (int) $n;
        }
        return self::c($n);
    }
}
