<?php

return [
    'enabled' => false,

    'host' => getenv('MAIL_HOST') ?: '',
    'port' => (int) (getenv('MAIL_PORT') ?: 587),
    'username' => getenv('MAIL_USERNAME') ?: '',
    'password' => getenv('MAIL_PASSWORD') ?: '',

    'from_address' => getenv('MAIL_FROM_ADDRESS') ?: '',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'ADPI Monitoring',
];
