<?php

use App\Core\DB;

return [
    'fix_behzod_email' => function (): void {
        DB::run(
            'UPDATE users
             SET email = :email
             WHERE username = :username OR email = :old_email',
            [
                'email' => 'behzodarabdjonovich@gmail.com',
                'username' => 'behzodarabdjonovich@gmail.com',
                'old_email' => 'behzodarabdjonovich@demo.adpi.local',
            ]
        );
    },
];
