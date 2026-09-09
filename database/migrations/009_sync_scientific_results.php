<?php

use App\Core\DB;

return function (): void {
 $publications = DB::select('SELECT * FROM publications ORDER BY id');

foreach ($publications as $pub) {
    $exists = DB::selectOne(
        'SELECT id FROM scientific_results WHERE publication_id = :id LIMIT 1',
        ['id' => $pub['id']]
    );

    if ($exists) {
        continue;
    }

    $resultType = match ($pub['publication_type'] ?? '') {
    'scopus' => 'scopus_maqola',
    'wos' => 'wos_maqola',
    'milliy' => 'oak_maqola',
    default => 'ilmiy_maqola',
};

    DB::insert('scientific_results', [
        'student_id' => $pub['student_id'],
        'plan_task_id' => null,
        'result_type' => $resultType,
        'publication_id' => $pub['id'],
        'conference_id' => null,
        'title' => $pub['title'],
        'achieved_at' => $pub['published_at'] ?? null,
        'verified' => true,
        'status' => 'approved',
        'created_at' => $pub['created_at'] ?? date('Y-m-d H:i:s'),
    ]);
 $conferences = DB::select('SELECT * FROM conferences ORDER BY id');

foreach ($conferences as $conf) {
    $exists = DB::selectOne(
        'SELECT id FROM scientific_results WHERE conference_id = :id LIMIT 1',
        ['id' => $conf['id']]
    );

    if ($exists) {
        continue;
    }

    $resultType = match ($conf['level'] ?? '') {
        'xalqaro' => 'xalqaro_konferensiya',
        default => 'respublika_konferensiya',
    };

    DB::insert('scientific_results', [
        'student_id' => $conf['student_id'],
        'plan_task_id' => null,
        'result_type' => $resultType,
        'publication_id' => null,
        'conference_id' => $conf['id'],
        'title' => $conf['title'],
        'achieved_at' => $conf['event_date'] ?? null,
        'verified' => true,
        'status' => 'approved',
        'created_at' => $conf['created_at'] ?? date('Y-m-d H:i:s'),
    ]);

     }
};
 
