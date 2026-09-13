<?php

return [
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'noreply@example.com',
    'from_name'  => getenv('MAIL_FROM_NAME') ?: 'ADPI Monitoring',
];
