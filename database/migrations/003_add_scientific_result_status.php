<?php

use App\Core\DB;

return function (): void {
    DB::connection()->exec(
        "ALTER TABLE scientific_results
         ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'pending'"
    );

    // Eski verified ma'lumotlarini yangi statusga o'tkazamiz.
    DB::connection()->exec(
        "UPDATE scientific_results
         SET status = CASE
             WHEN verified = 1 THEN 'approved'
             ELSE 'pending'
         END"
    );
};
