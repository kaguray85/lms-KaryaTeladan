<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../models/PasswordResetOtp.php';
require_once __DIR__ . '/../models/User.php';

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

function generateOtp(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function sendOtpEmail(string $email, string $nama, string $otp): void
{
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('PHPMailer belum terpasang. Jalankan composer install.');
    }

    require_once $autoload;
    $config = mailConfig();

    if ($config['host'] === '' || $config['from_email'] === '') {
        throw new RuntimeException('Konfigurasi SMTP belum lengkap.');
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = $config['username'] !== '';
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($email, $nama);
        $mail->isHTML(true);
        $mail->Subject = 'Kode OTP Reset Password LMS Karya Teladan';
        $mail->Body = '<p>Halo ' . htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Kode OTP untuk reset password Anda adalah:</p>'
            . '<p style="font-size:28px;font-weight:bold;letter-spacing:6px">' . $otp . '</p>'
            . '<p>Kode berlaku selama 5 menit dan hanya dapat digunakan satu kali.</p>'
            . '<p>Abaikan email ini jika Anda tidak meminta reset password.</p>';
        $mail->AltBody = "Halo {$nama},\n\nKode OTP reset password Anda: {$otp}\n"
            . "Kode berlaku selama 5 menit dan hanya dapat digunakan satu kali.";
        $mail->send();
    } catch (MailException $exception) {
        throw new RuntimeException('Email OTP gagal dikirim.', 0, $exception);
    }
}

function saveOtp(PDO $db, int $userId, string $otp): int
{
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $expiredAt = (new DateTimeImmutable('+' . PASSWORD_RESET_OTP_TTL_MINUTES . ' minutes'))
        ->format('Y-m-d H:i:s');

    return PasswordResetOtp::create($db, $userId, $otpHash, $expiredAt);
}

/**
 * @return array{status:string, otp_id?:int, attempts_left?:int}
 */
function verifyOtp(PDO $db, int $userId, string $otp): array
{
    $record = PasswordResetOtp::latestForUser($db, $userId);

    if (!$record || (int) $record['is_used'] === 1) {
        return ['status' => 'invalid'];
    }

    if (strtotime($record['expired_at']) < time()) {
        PasswordResetOtp::markUsed($db, (int) $record['id']);
        return ['status' => 'expired'];
    }

    if ((int) $record['attempts'] >= PASSWORD_RESET_OTP_MAX_ATTEMPTS) {
        PasswordResetOtp::markUsed($db, (int) $record['id']);
        return ['status' => 'max_attempts'];
    }

    if (!password_verify($otp, $record['otp_hash'])) {
        PasswordResetOtp::incrementAttempts($db, (int) $record['id']);
        $attemptsLeft = max(0, PASSWORD_RESET_OTP_MAX_ATTEMPTS - ((int) $record['attempts'] + 1));
        if ($attemptsLeft === 0) {
            PasswordResetOtp::markUsed($db, (int) $record['id']);
        }

        return ['status' => 'invalid', 'attempts_left' => $attemptsLeft];
    }

    if (!PasswordResetOtp::markUsed($db, (int) $record['id'])) {
        return ['status' => 'invalid'];
    }

    return ['status' => 'valid', 'otp_id' => (int) $record['id']];
}

function updatePassword(PDO $db, int $userId, string $newPassword): void
{
    User::updatePassword($db, $userId, $newPassword);
}

function clearPasswordResetSession(): void
{
    unset(
        $_SESSION['password_reset_user_id'],
        $_SESSION['password_reset_email'],
        $_SESSION['password_reset_verified_at']
    );
}
