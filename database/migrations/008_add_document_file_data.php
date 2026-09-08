<?php

use App\Core\DB;

return function (): void {
    DB::run(
        'ALTER TABLE documents ADD COLUMN IF NOT EXISTS file_data BYTEA'
    );
};
