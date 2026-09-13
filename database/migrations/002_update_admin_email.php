<?php

use App\Core\DB;

return function (): void {
    DB::statement(
        "UPDATE users
         SET email = :email
         WHERE username = :username",
        [
            'email' => 'behzodarabdjonovich@gmail.com',
            'username' => 'admin',
        ]
    );
};
