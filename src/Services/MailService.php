<?php

namespace App\Services;

use Resend;

final class MailService
{
    public static function sendPasswordReset(string $email, string $resetUrl): bool
    {
        $apiKey = getenv('RESEND_API_KEY');

        if (!$apiKey) {
            error_log('RESEND_API_KEY is not configured.');
            return false;
        }

        try {
            $resend = Resend::client($apiKey);

            $resend->emails->send([
                'from' => 'ADPI Monitoring <noreply@eduservis.uz>',
                'to' => [$email],
                'subject' => 'Parolni tiklash — ADPI Monitoring',
                'html' => self::resetEmailHtml($resetUrl),
            ]);

            return true;
        } catch (\Throwable $e) {
            error_log('Resend email error: ' . $e->getMessage());
            return false;
        }
    }

    private static function resetEmailHtml(string $resetUrl): string
    {
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="uz">
<body>
    <h2>ADPI Monitoring</h2>

    <p>Parolingizni tiklash uchun quyidagi havolani bosing:</p>

    <p>
        <a href="{$safeUrl}">Parolni tiklash</a>
    </p>

    <p>Ushbu havola 1 soat davomida amal qiladi.</p>

    <p>
        Agar parolni tiklashni siz so‘ramagan bo‘lsangiz,
        ushbu xatni e'tiborsiz qoldiring.
    </p>
</body>
</html>
HTML;
    }
public static function sendCredentials(
    string $email,
    string $fullName,
    string $login,
    string $temporaryPassword
): bool {
    $apiKey = getenv('RESEND_API_KEY');

    if (!$apiKey) {
        error_log('RESEND_API_KEY is not configured.');
        return false;
    }

    try {
        $resend = Resend::client($apiKey);

        $resend->emails->send([
            'from' => 'ADPI Monitoring <noreply@send.adpi-monitoring.uz>',
            'to' => [$email],
            'subject' => 'ADPI Monitoring — login ma’lumotlari',
            'html' => '<h2>ADPI Monitoring</h2>'
                . '<p>Hurmatli ' . htmlspecialchars($fullName) . ',</p>'
                . '<p>Siz uchun doktorant kabineti yaratildi.</p>'
                . '<p><strong>Login:</strong> ' . htmlspecialchars($login) . '</p>'
                . '<p><strong>Vaqtinchalik parol:</strong> '
                . htmlspecialchars($temporaryPassword) . '</p>'
                . '<p>Tizimga kirgandan so‘ng parolingizni o‘zgartirishingiz tavsiya etiladi.</p>',
        ]);

        return true;
    } catch (\Throwable $e) {
        error_log('Resend credentials error: ' . $e->getMessage());
        return false;
    }
}
}
