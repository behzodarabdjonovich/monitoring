<?php

use App\Core\DB;

return function (): void {
    $driver = DB::pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'pgsql') {
        DB::run(
            'ALTER TABLE documents ADD COLUMN IF NOT EXISTS file_data BYTEA'
        );
        return;
    }

    $columns = DB::select('PRAGMA table_info(documents)');

    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'file_data') {
            return;
        }
    }

    DB::run(
        'ALTER TABLE documents ADD COLUMN file_data BLOB'
    );
};
