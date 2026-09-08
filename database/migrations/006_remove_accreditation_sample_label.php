<?php

use App\Core\DB;

return function (): void {
    $now = date('Y-m-d H:i:s');

    // 1. Akkreditatsiya sarlavhasidan [NAMUNA] ni olib tashlash
    DB::run(
        "UPDATE accreditations
         SET title = REPLACE(title, '[NAMUNA] ', ''),
             is_placeholder = FALSE,
             updated_at = :updated_at
         WHERE title LIKE '%[NAMUNA]%'",
        ['updated_at' => $now]
    );

    // 2. Mezon kodlari:
    // NAMUNA-1 -> MEZON-1
    // NAMUNA-2 -> MEZON-2
    // NAMUNA-3 -> MEZON-3
    DB::run(
        "UPDATE accreditation_criteria
         SET code = REPLACE(code, 'NAMUNA-', 'MEZON-'),
             is_placeholder = FALSE
         WHERE code LIKE 'NAMUNA-%'"
    );

    // 3. Mezon nomlaridan [NAMUNA] ni olib tashlash
    DB::run(
        "UPDATE accreditation_criteria
         SET name = REPLACE(name, '[NAMUNA] ', '')
         WHERE name LIKE '%[NAMUNA]%'"
    );

    // 4. Indikator kodlari:
    // NAMUNA-1.1 -> MEZON-1.1 va hokazo
    DB::run(
        "UPDATE accreditation_indicators
         SET code = REPLACE(code, 'NAMUNA-', 'MEZON-'),
             is_placeholder = FALSE
         WHERE code LIKE 'NAMUNA-%'"
    );

    // 5. Indikator nomlaridan [NAMUNA] ni olib tashlash
    // [NAMUNA] Indikator 1 -> Indikator 1
    DB::run(
        "UPDATE accreditation_indicators
         SET name = REPLACE(name, '[NAMUNA] ', '')
         WHERE name LIKE '%[NAMUNA]%'"
    );
    // 6. Kamchiliklar, chora-tadbirlar va ichki audit matnlaridan [NAMUNA] ni olib tashlash
    DB::run(
        "UPDATE deficiencies
         SET title = REPLACE(title, '[NAMUNA] ', ''),
             description = REPLACE(description, '[NAMUNA] ', ''),
             updated_at = :updated_at
         WHERE title LIKE '%[NAMUNA]%'
            OR description LIKE '%[NAMUNA]%'",
        ['updated_at' => $now]
    );

    DB::run(
        "UPDATE action_plans
         SET title = REPLACE(title, '[NAMUNA] ', ''),
             description = REPLACE(description, '[NAMUNA] ', ''),
             updated_at = :updated_at
         WHERE title LIKE '%[NAMUNA]%'
            OR description LIKE '%[NAMUNA]%'",
        ['updated_at' => $now]
    );

    DB::run(
        "UPDATE internal_audits
         SET title = REPLACE(title, '[NAMUNA] ', ''),
             scope = REPLACE(scope, '[NAMUNA] ', ''),
             summary = REPLACE(summary, '[NAMUNA] ', '')
         WHERE title LIKE '%[NAMUNA]%'
            OR scope LIKE '%[NAMUNA]%'
            OR summary LIKE '%[NAMUNA]%'"
    );
};
