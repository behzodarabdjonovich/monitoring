<?php

namespace App\Models;

use App\Core\DB;

/**
 * Global qidiruv (item 16).
 *
 * Doktorantlar, ilmiy rahbarlar, ixtisosliklar, hujjatlar (dalillar) va
 * akkreditatsiya indikatorlari bo'yicha qidiradi; natijalar tur bo'yicha
 * guruhlanadi. Barcha so'rovlar PDO prepared-statement va LIKE (parametr)
 * orqali (SQLi himoyasi).
 */
final class Search
{
    /**
     * @return array{
     *   students: array<int,array{title:string,subtitle:string,link:string}>,
     *   supervisors: array<int,array{title:string,subtitle:string,link:string}>,
     *   specialties: array<int,array{title:string,subtitle:string,link:string}>,
     *   documents: array<int,array{title:string,subtitle:string,link:string}>,
     *   indicators: array<int,array{title:string,subtitle:string,link:string}>,
     *   total: int
     * }
     */
    public static function query(string $term, int $perGroup = 10): array
    {
        $term = trim($term);
        $groups = [
            'students' => [],
            'supervisors' => [],
            'specialties' => [],
            'documents' => [],
            'indicators' => [],
        ];
        if ($term === '') {
            return $groups + ['total' => 0];
        }
        $like = '%' . self::escapeLike($term) . '%';

        // Doktorantlar (F.I.Sh., dissertatsiya mavzusi, JSHSHIR).
        foreach (DB::select(
            "SELECT s.id, s.full_name, sp.name AS specialty
             FROM doctoral_students s
             LEFT JOIN specialties sp ON sp.id = s.specialty_id
             WHERE s.full_name LIKE :q ESCAPE '\\' OR s.dissertation_topic LIKE :q ESCAPE '\\' OR s.national_id LIKE :q ESCAPE '\\'
             ORDER BY s.full_name LIMIT :lim",
            ['q' => $like, 'lim' => $perGroup]
        ) as $r) {
            $groups['students'][] = [
                'title' => (string) $r['full_name'],
                'subtitle' => (string) ($r['specialty'] ?? 'Doktorant'),
                'link' => '/students/' . (int) $r['id'],
            ];
        }

        // Ilmiy rahbarlar.
        foreach (DB::select(
            "SELECT id, full_name, research_field FROM supervisors
             WHERE full_name LIKE :q ESCAPE '\\' OR research_field LIKE :q ESCAPE '\\'
             ORDER BY full_name LIMIT :lim",
            ['q' => $like, 'lim' => $perGroup]
        ) as $r) {
            $groups['supervisors'][] = [
                'title' => (string) $r['full_name'],
                'subtitle' => (string) ($r['research_field'] ?? 'Ilmiy rahbar'),
                'link' => '/supervisors/' . (int) $r['id'],
            ];
        }

        // Ixtisosliklar.
        foreach (DB::select(
            "SELECT id, code, name FROM specialties
             WHERE name LIKE :q ESCAPE '\\' OR code LIKE :q ESCAPE '\\' OR branch LIKE :q ESCAPE '\\'
             ORDER BY name LIMIT :lim",
            ['q' => $like, 'lim' => $perGroup]
        ) as $r) {
            $groups['specialties'][] = [
                'title' => (string) $r['name'],
                'subtitle' => (string) ($r['code'] ?? 'Ixtisoslik'),
                'link' => '/specialties/' . (int) $r['id'],
            ];
        }

        // Hujjatlar (dalillar).
        foreach (DB::select(
            "SELECT id, title, category FROM documents
             WHERE title LIKE :q ESCAPE '\\' OR original_name LIKE :q ESCAPE '\\'
             ORDER BY id DESC LIMIT :lim",
            ['q' => $like, 'lim' => $perGroup]
        ) as $r) {
            $groups['documents'][] = [
                'title' => (string) $r['title'],
                'subtitle' => (string) ($r['category'] ?? 'Hujjat'),
                'link' => '/documents/' . (int) $r['id'],
            ];
        }

        // Akkreditatsiya indikatorlari.
        foreach (DB::select(
            "SELECT id, code, name FROM accreditation_indicators
             WHERE name LIKE :q ESCAPE '\\' OR code LIKE :q ESCAPE '\\' OR requirement LIKE :q ESCAPE '\\'
             ORDER BY code LIMIT :lim",
            ['q' => $like, 'lim' => $perGroup]
        ) as $r) {
            $groups['indicators'][] = [
                'title' => (string) ($r['name'] ?? $r['code']),
                'subtitle' => (string) ($r['code'] ?? 'Indikator'),
                'link' => '/indicators/' . (int) $r['id'],
            ];
        }

        $total = 0;
        foreach ($groups as $g) {
            $total += count($g);
        }
        return $groups + ['total' => $total];
    }

    /**
     * Guruh kalitlari uchun o'zbekcha yorliqlar.
     *
     * @return array<string,string>
     */
    public static function groupLabels(): array
    {
        return [
            'students' => 'Doktorantlar',
            'supervisors' => 'Ilmiy rahbarlar',
            'specialties' => 'Ixtisosliklar',
            'documents' => 'Dalillar (hujjatlar)',
            'indicators' => 'Akkreditatsiya indikatorlari',
        ];
    }

    private static function escapeLike(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }
}
