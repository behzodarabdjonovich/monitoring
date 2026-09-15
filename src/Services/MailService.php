<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

final class MailService
{
    private static function send(
        string $email,
        string $subject,
        string $html
    ): bool {
        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('MAIL_USERNAME');
            $mail->Password = getenv('MAIL_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) (getenv('MAIL_PORT') ?: 587);

            $mail->CharSet = 'UTF-8';

            $fromAddress = getenv('MAIL_FROM_ADDRESS')
                ?: getenv('MAIL_USERNAME');

            $mail->setFrom($fromAddress, 'ADPI Monitoring');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = strip_tags($html);

            $mail->send();

            return true;
        } catch (\Throwable $e) {
            error_log('SMTP email error: ' . $e->getMessage());
            return false;
        }
    }

    public static function sendPasswordReset(
        string $email,
        string $resetUrl
    ): bool {
        return self::send(
            $email,
            'Parolni tiklash — ADPI Monitoring',
            self::resetEmailHtml($resetUrl)
        );
    }

    public static function sendCredentials(
        string $email,
        string $fullName,
        string $login,
        string $temporaryPassword
    ): bool {
        $safeName = htmlspecialchars(
            $fullName,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeLogin = htmlspecialchars(
            $login,
            ENT_QUOTES,
            'UTF-8'
        );

        $safePassword = htmlspecialchars(
            $temporaryPassword,
            ENT_QUOTES,
            'UTF-8'
        );

        $html = <<<HTML
<!DOCTYPE html>
<html lang="uz">
<body>
    <h2>ADPI Monitoring</h2>

    <p>Hurmatli {$safeName},</p>

    <p>Siz uchun doktorant kabineti yaratildi.</p>

    <p><strong>Login:</strong> {$safeLogin}</p>

    <p><strong>Vaqtinchalik parol:</strong> {$safePassword}</p>

    <p>
        Tizimga kirgandan so‘ng parolingizni
        o‘zgartirishingiz tavsiya etiladi.
    </p>
</body>
</html>
HTML;

        return self::send(
            $email,
            'ADPI Monitoring — login ma’lumotlari',
            $html
        );
    }

    private static function resetEmailHtml(
        string $resetUrl
    ): string {
        $safeUrl = htmlspecialchars(
            $resetUrl,
            ENT_QUOTES,
            'UTF-8'
        );

        return <<<HTML
<!DOCTYPE html>
<html lang="uz">
<body>
    <h2>ADPI Monitoring</h2>

    <p>
        Parolingizni tiklash uchun quyidagi
        havolani bosing:
    </p>

    <p>
        <a href="{$safeUrl}">Parolni tiklash</a>
    </p>

    <p>Ushbu havola 1 soat davomida amal qiladi.</p>

    <p>
        Agar parolni tiklashni siz so‘ramagan
        bo‘lsangiz, ushbu xatni e'tiborsiz qoldiring.
    </p>
</body>
</html>
HTML;
    }
}
