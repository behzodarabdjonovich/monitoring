<?php

namespace App\Core;

use ZipArchive;

/**
 * Kutubxonasiz (dependency-free) minimal OpenXML/SpreadsheetML (.xlsx) yozuvchi.
 *
 * Faqat jadval ma'lumotlari (satr/ustun) uchun mo'ljallangan — hisobotlarni
 * Excel formatida eksport qilishga xizmat qiladi. ZipArchive kengaytmasi
 * mavjud bo'lsa haqiqiy .xlsx (OpenXML) hosil qilinadi; aks holda CSV
 * (.csv) fallback qaytariladi (documented in README).
 *
 * Ishlatilishi:
 *   [$body, $mime, $ext] = Spreadsheet::build('Hisobot', ['A', 'B'], [[1, 2]]);
 */
final class Spreadsheet
{
    /**
     * @param string        $sheetName Varaq nomi
     * @param array<int,string> $headers Ustun sarlavhalari
     * @param array<int,array<int,scalar|null>> $rows Ma'lumot satrlari
     * @return array{0:string,1:string,2:string} [body, mime, extension]
     */
    public static function build(string $sheetName, array $headers, array $rows): array
    {
        if (class_exists(ZipArchive::class)) {
            return [
                self::buildXlsx($sheetName, $headers, $rows),
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xlsx',
            ];
        }
        // Fallback: CSV (Excel ochadi). Oldingi qatorda BOM UTF-8 uchun.
        return [self::buildCsv($headers, $rows), 'text/csv; charset=UTF-8', 'csv'];
    }

    /**
     * @param array<int,string> $headers
     * @param array<int,array<int,scalar|null>> $rows
     */
    public static function buildCsv(array $headers, array $rows): string
    {
        $lines = [];
        $all = array_merge([$headers], $rows);
        foreach ($all as $row) {
            $cells = array_map(static function ($v): string {
                $s = (string) ($v ?? '');
                if (preg_match('/[",\n\r]/', $s)) {
                    $s = '"' . str_replace('"', '""', $s) . '"';
                }
                return $s;
            }, $row);
            $lines[] = implode(',', $cells);
        }
        return "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n";
    }

    /**
     * @param array<int,string> $headers
     * @param array<int,array<int,scalar|null>> $rows
     */
    private static function buildXlsx(string $sheetName, array $headers, array $rows): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) {
            return self::buildCsv($headers, $rows);
        }
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            return self::buildCsv($headers, $rows);
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRels());
        $zip->addFromString('xl/workbook.xml', self::workbook($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheet($headers, $rows));
        $zip->close();

        $data = file_get_contents($tmp);
        @unlink($tmp);
        return $data === false ? self::buildCsv($headers, $rows) : $data;
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbook(string $sheetName): string
    {
        $name = self::xml(mb_substr($sheetName, 0, 31));
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $name . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    /**
     * @param array<int,string> $headers
     * @param array<int,array<int,scalar|null>> $rows
     */
    private static function sheet(array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>';

        $rowNum = 1;
        $xml .= self::rowXml($headers, $rowNum, true);
        foreach ($rows as $row) {
            $rowNum++;
            $xml .= self::rowXml(array_values($row), $rowNum, false);
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    /**
     * @param array<int,scalar|null> $cells
     */
    private static function rowXml(array $cells, int $rowNum, bool $header): string
    {
        $xml = '<row r="' . $rowNum . '">';
        $col = 0;
        foreach ($cells as $value) {
            $ref = self::columnLetter($col) . $rowNum;
            $col++;
            $style = $header ? ' s="1"' : '';
            if (is_int($value) || is_float($value)) {
                $xml .= '<c r="' . $ref . '"' . $style . '><v>' . $value . '</v></c>';
            } else {
                $text = self::xml((string) ($value ?? ''));
                $xml .= '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">' . $text . '</t></is></c>';
            }
        }
        $xml .= '</row>';
        return $xml;
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int) (($index - $mod) / 26);
        }
        return $letter;
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
