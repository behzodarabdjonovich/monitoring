<?php

namespace App\Models;

use App\Core\DB;

/**
 * Ilmiy natijalar (item 6) modeli.
 *
 * Barcha ilmiy natija turlarini yagona type enum/lookup (TYPES) sifatida
 * hisobga oladi. Har natija doktorant va/yoki ilmiy rahbarga bog'lanadi va
 * tasdiqlovchi PDF/JPG/PNG fayl (documents orqali) YOKI havola (URL) ega
 * bo'ladi. Publications va Conferences shu natijalarni to'ldiruvchi
 * (specializatsiya) jadvallardir.
 */
final class ScientificResult
{
    /**
     * Ilmiy natija turlari — foydalanuvchi topshirig'idagi ANIQ ro'yxat.
     * Kalit (DB) => o'zbekcha ko'rsatiladigan nom.
     */
    public const TYPES = [
        'ilmiy_maqola' => 'Ilmiy maqola',
        'oak_maqola' => 'OAK ro\'yxatidagi jurnal maqolasi',
        'scopus_maqola' => 'Scopus maqolasi',
        'wos_maqola' => 'Web of Science maqolasi',
        'xalqaro_konferensiya' => 'Xalqaro konferensiya',
        'respublika_konferensiya' => 'Respublika konferensiyasi',
        'monografiya' => 'Monografiya',
        'oquv_uslubiy_nashr' => 'O\'quv/uslubiy nashr',
        'patent' => 'Patent',
        'mualliflik_guvohnomasi' => 'Mualliflik guvohnomasi',
        'grant' => 'Grant',
        'xalqaro_loyiha' => 'Xalqaro loyiha',
        'ilmiy_seminar' => 'Ilmiy seminar',
        'boshqa' => 'Boshqa ilmiy natijalar',
    ];

    /**
     * Berilgan turdagi natija maqola specializatsiyasiga tegishlimi?
     * (publications jadvaliga hissa qo'shadi).
     */
    public const PUBLICATION_TYPES = ['ilmiy_maqola', 'oak_maqola', 'scopus_maqola', 'wos_maqola', 'monografiya', 'oquv_uslubiy_nashr'];

    /**
     * Konferensiya specializatsiyasiga tegishli turlar (conferences).
     */
    public const CONFERENCE_TYPES = ['xalqaro_konferensiya', 'respublika_konferensiya'];

    public static function find(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM scientific_results WHERE id = :id', ['id' => $id]);
    }

    public static function findWithRelations(int $id): ?array
    {
        return DB::selectOne(
            'SELECT r.*, s.full_name AS student_name, sup.full_name AS supervisor_name,
                    d.id AS doc_id, d.title AS doc_title, d.file_path AS doc_path
             FROM scientific_results r
             LEFT JOIN doctoral_students s ON s.id = r.student_id
             LEFT JOIN supervisors sup ON sup.id = r.supervisor_id
             LEFT JOIN documents d ON d.id = r.document_id
             WHERE r.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Ro'yxat: tur/doktorant bo'yicha filtr, bog'liq nomlar bilan.
     *
     * @param array<string,string> $f
     * @return array<int,array<string,mixed>>
     */
    public static function search(array $f = []): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($f['type'])) {
            $where[] = 'r.result_type = :type';
            $params['type'] = $f['type'];
        }
        if (!empty($f['student'])) {
            $where[] = 'r.student_id = :sid';
            $params['sid'] = (int) $f['student'];
        }
        if (!empty($f['q'])) {
            $where[] = 'r.title LIKE :q';
            $params['q'] = '%' . $f['q'] . '%';
        }

        $sql = 'SELECT r.*, s.full_name AS student_name, sup.full_name AS supervisor_name,
                       d.file_path AS doc_path
                FROM scientific_results r
                LEFT JOIN doctoral_students s ON s.id = r.student_id
                LEFT JOIN supervisors sup ON sup.id = r.supervisor_id
                LEFT JOIN documents d ON d.id = r.document_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY r.id DESC';
        return DB::select($sql, $params);
    }

    public static function typeLabel(string $key): string
    {
        return self::TYPES[$key] ?? $key;
    }
}
