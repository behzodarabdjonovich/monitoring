<?php

use App\Core\DB;

return function (): void {
    DB::run(
        "UPDATE accreditations
         SET title = :title,
             is_placeholder = FALSE,
             updated_at = :updated_at
         WHERE title = :old_title",
        [
            'title' => 'Maxsus davlat akkreditatsiyasiga tayyorgarlik sikli',
            'updated_at' => date('Y-m-d H:i:s'),
            'old_title' => '[NAMUNA] Maxsus davlat akkreditatsiyasiga tayyorgarlik sikli',
        ]
    );
};
