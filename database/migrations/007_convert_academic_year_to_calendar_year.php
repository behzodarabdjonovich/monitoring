<?php

use App\Core\DB;

return function (): void {
    $driver = DB::driver();

    $yearExpression = $driver === 'sqlite'
        ? "substr(academic_year, -4)"
        : "RIGHT(academic_year, 4)";

    DB::run(
        "UPDATE individual_plans
         SET academic_year = {$yearExpression}
         WHERE academic_year LIKE '%/%'"
    );
};
