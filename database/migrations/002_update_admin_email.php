<?php

use App\Core\DB;

return function (): void {
    DB::run(
        'UPDATE users
         SET email = :email,
             updated_at = :updated_at
         WHERE username = :username',
        [
            'email' => 'behzodarabdjonovich@gmail.com',
            'updated_at' => date('Y-m-d H:i:s'),
            'username' => 'admin',
        ]
    );
};
