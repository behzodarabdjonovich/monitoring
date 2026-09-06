<?php

namespace App\Models;

use App\Core\DB;

/**
 * Dalillar (Evidence) bazasi (item 11) modeli.
 *
 * Barcha tasdiqlovchi hujjatlarni markazlashtirilgan holda saqlaydi. Har
 * hujjat toifasi (CATEGORIES), metama'lumotlari (title, category, owner,
 * upload date, size, mime) bilan yuritiladi. Bitta hujjat bir nechta
 * akkreditatsiya indikatoriga indicator_evidence pivot orqali bog'lanadi.
 */
final class Document
{
    /**
     * Dalil toifalari — foydalanuvchi topshirig'idagi ANIQ ro'yxat.
     * Kalit (DB) => o'zbekcha ko'rsatiladigan nom.
     */
    public const CATEGORIES = [
        'buyruqlar' => 'Buyruqlar',
        'kengash_qarorlari' => 'Kengash qarorlari',
        'bayonnomalar' => 'Bayonnomalar',
        'individual_reja' => 'Doktorant individual rejasi',
        'attestatsiya_hujjatlari' => 'Attestatsiya hujjatlari',
        'seminar_bayonnomalari' => 'Ilmiy seminar bayonnomalari',
        'maqolalar' => 'Maqolalar',
        'sertifikatlar' => 'Sertifikatlar',
        'shartnomalar' => 'Shartnomalar',
        'xalqaro_hamkorlik' => 'Xalqaro hamkorlik hujjatlari',
        'loyiha_hujjatlari' => 'Ilmiy loyiha hujjatlari',
        'statistik_malumotlar' => 'Statistik ma\'lumotlar',
        'fotosuratlar' => 'Fotosuratlar',
        'boshqa' => 'Boshqa dalillar',
    ];

    public static function find(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM documents WHERE id = :id', ['id' => $id]);
    }

    public static function findWithRelations(int $id): ?array
    {
        return DB::selectOne(
            'SELECT d.*, u.full_name AS uploader_name
             FROM documents d
             LEFT JOIN users u ON u.id = d.uploaded_by
             WHERE d.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Ro'yxat: toifa/qidiruv bo'yicha filtr, yuklovchi nomi bilan.
     *
     * @param array<string,string> $f
     * @return array<int,array<string,mixed>>
     */
    public static function search(array $f = []): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($f['category'])) {
            $where[] = 'd.category = :cat';
            $params['cat'] = $f['category'];
        }
       if (!empty($f['q'])) {
    $where[] = '(d.title LIKE :q OR d.original_name LIKE :q)';
    $params['q'] = '%' . $f['q'] . '%';
}

if (!empty($f['student_id'])) {
    $where[] = 'd.student_id = :student_id';
    $params['student_id'] = (int) $f['student_id'];
}

$sql = 'SELECT d.*, u.full_name AS uploader_name,
                       (SELECT COUNT(*) FROM indicator_evidence ie WHERE ie.document_id = d.id) AS indicator_count
                FROM documents d
                LEFT JOIN users u ON u.id = d.uploaded_by
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY d.id DESC';
        return DB::select($sql, $params);
    }

    public static function categoryLabel(string $key): string
    {
        return self::CATEGORIES[$key] ?? $key;
    }

    /**
     * Hujjatga bog'langan barcha indikatorlar (per-document ko'rinish).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function linkedIndicators(int $documentId): array
    {
        return DB::select(
            'SELECT i.id, i.code, i.name, i.rag_status, ie.id AS link_id, ie.note
             FROM indicator_evidence ie
             INNER JOIN accreditation_indicators i ON i.id = ie.indicator_id
             WHERE ie.document_id = :did
             ORDER BY i.code',
            ['did' => $documentId]
        );
    }

    /**
     * Indikatorga bog'langan barcha hujjatlar (per-indicator ko'rinish).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function forIndicator(int $indicatorId): array
    {
        return DB::select(
            'SELECT d.*, ie.id AS link_id, ie.note
             FROM indicator_evidence ie
             INNER JOIN documents d ON d.id = ie.document_id
             WHERE ie.indicator_id = :iid
             ORDER BY d.id DESC',
            ['iid' => $indicatorId]
        );
    }

    /**
     * Hujjatni indikatorga bog'laydi (M:N). Takroriy bog'lanish (UNIQUE)
     * bo'lsa false qaytaradi.
     */
    public static function linkToIndicator(int $documentId, int $indicatorId, ?int $userId, ?string $note = null): bool
    {
        $exists = DB::scalar(
            'SELECT COUNT(*) FROM indicator_evidence WHERE document_id = :d AND indicator_id = :i',
            ['d' => $documentId, 'i' => $indicatorId]
        );
        if ((int) $exists > 0) {
            return false;
        }
        DB::insert('indicator_evidence', [
            'indicator_id' => $indicatorId,
            'document_id' => $documentId,
            'note' => $note,
            'linked_by' => $userId,
            'linked_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /**
     * Hujjatni indikatordan uzadi (M:N). Uzilgan bo'lsa true.
     */
    public static function unlinkFromIndicator(int $documentId, int $indicatorId): bool
    {
        $stmt = DB::run(
            'DELETE FROM indicator_evidence WHERE document_id = :d AND indicator_id = :i',
            ['d' => $documentId, 'i' => $indicatorId]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Indikatorda kamida bitta dalil bormi? (ScoringEngine grey mantiqida
     * ishlatiladi).
     */
    public static function indicatorHasEvidence(int $indicatorId): bool
    {
        return (int) DB::scalar(
            'SELECT COUNT(*) FROM indicator_evidence WHERE indicator_id = :i',
            ['i' => $indicatorId]
        ) > 0;
    }
}
