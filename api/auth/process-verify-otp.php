<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/validation.php';
require_once __DIR__ . '/../../app/helpers/security.php';
require_once __DIR__ . '/../../app/helpers/password_reset.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method tidak diizinkan.', 405);
}

$userId = (int) ($_SESSION['password_reset_user_id'] ?? 0);
if ($userId <= 0) {
    errorResponse('Sesi reset password tidak ditemukan. Silakan minta OTP baru.', 401);
}

$data = requestData();
$otp = getValue($data, 'otp', '');

if (!preg_match('/^\d{6}$/', $otp)) {
    errorResponse('OTP harus terdiri dari 6 digit angka.', 422);
}

try {
    $db = Database::connection();
    $result = verifyOtp($db, $userId, $otp);

    if ($result['status'] === 'expired') {
        clearPasswordResetSession();
        errorResponse('OTP sudah kedaluwarsa. Silakan minta kode baru.', 410);
    }

    if ($result['status'] === 'max_attempts') {
        clearPasswordResetSession();
        errorResponse('Batas percobaan OTP telah habis. Silakan minta kode baru.', 429);
    }

    if ($result['status'] !== 'valid') {
        $attemptsLeft = $result['attempts_left'] ?? null;
        $message = $attemptsLeft === null
            ? 'OTP tidak valid. Silakan minta kode baru.'
            : "OTP tidak valid. Sisa percobaan: {$attemptsLeft}.";

        if ($attemptsLeft === 0) {
            clearPasswordResetSession();
        }
        errorResponse($message, 422);
    }

    session_regenerate_id(true);
    $_SESSION['password_reset_verified_at'] = time();
    logActivity($db, $userId, 'Verifikasi OTP reset password berhasil', 'guest');
    successResponse('OTP berhasil diverifikasi.');
} catch (Throwable $exception) {
    error_log('Verify reset OTP error: ' . $exception->getMessage());
    errorResponse('Verifikasi OTP gagal. Silakan coba lagi.', 500);
}
