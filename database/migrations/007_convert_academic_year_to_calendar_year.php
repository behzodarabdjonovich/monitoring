<?php

use App\Core\DB;

return function (): void {
    DB::run(
        "UPDATE individual_plans
         SET academic_year = RIGHT(academic_year, 4)
         WHERE academic_year LIKE '%/%'"
    );
};
