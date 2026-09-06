<?php

use App\Core\DB;

return function (): void {
    DB::connection()->exec(
        "ALTER TABLE scientific_results
         ADD COLUMN rejection_reason TEXT NULL"
    );
};
