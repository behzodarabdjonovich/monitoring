<?php

use App\Core\DB;

return function (): void {
    DB::update(
        'users',
        ['email' => 'behzodarabdjonovich@gmail.com'],
        'username = :username',
        ['username' => 'admin']
    );
};
