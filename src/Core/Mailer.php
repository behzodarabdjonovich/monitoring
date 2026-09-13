<?php

namespace App\Core;

final class Mailer
{
    public static function send(
        string $to,
        string $subject,
        string $message
    ): bool {
        $fromEmail = Config::get(
            'mail.from_email',
            'noreply@example.com'
        );

        $fromName = Config::get(
            'mail.from_name',
            'ADPI Monitoring'
        );

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'X-Mailer: PHP/' . phpversion(),
        ];

        return mail(
            $to,
            $subject,
            $message,
            implode("\r\n", $headers)
        );
    }
}
