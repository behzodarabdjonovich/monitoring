<?php

use App\Core\DB;

return function (): void {
    $driver = DB::driver();

    if ($driver === 'pgsql') {
        $columnExists = (int) DB::scalar(
            "SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = 'scientific_results'
               AND column_name = 'rejection_reason'"
        ) > 0;
    } elseif ($driver === 'mysql') {
        $columnExists = (int) DB::scalar(
            "SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = 'scientific_results'
               AND column_name = 'rejection_reason'"
        ) > 0;
    } else {
        $columns = DB::select("PRAGMA table_info(scientific_results)");

        $columnExists = false;

        foreach ($columns as $column) {
            if (($column['name'] ?? '') === 'rejection_reason') {
                $columnExists = true;
                break;
            }
        }
    }

    if (!$columnExists) {
        DB::connection()->exec(
            "ALTER TABLE scientific_results
             ADD COLUMN rejection_reason TEXT NULL"
        );
    }
};
