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

$data = requestData();
$errors = validateRequired($data, ['email']);
$email = strtolower(getValue($data, 'email', ''));

if ($email !== '' && !validateEmailFormat($email)) {
    $errors['email'] = 'Format email tidak valid.';
}

if (!empty($errors)) {
    errorResponse('Validasi permintaan OTP gagal.', 422, $errors);
}

$genericMessage = 'Jika email terdaftar, kode OTP akan dikirim ke email tersebut.';

try {
    $db = Database::connection();
    $user = User::findActiveByEmail($db, $email);

    if (!$user) {
        clearPasswordResetSession();
        if (APP_ENV !== 'production') {
            errorResponse('Email tidak terdaftar atau akun tidak aktif.', 404);
        }
        successResponse($genericMessage);
    }

    $latestOtp = PasswordResetOtp::latestForUser($db, (int) $user['id']);
    if ($latestOtp) {
        $availableAt = strtotime($latestOtp['created_at']) + PASSWORD_RESET_OTP_COOLDOWN_SECONDS;
        if ($availableAt > time()) {
            if (APP_ENV === 'production') {
                successResponse($genericMessage);
            }

            $waitSeconds = $availableAt - time();
            errorResponse("Tunggu {$waitSeconds} detik sebelum meminta OTP baru.", 429);
        }
    }

    $otp = generateOtp();
    $db->beginTransaction();
    saveOtp($db, (int) $user['id'], $otp);
    sendOtpEmail($user['email'], $user['name'], $otp);
    $db->commit();

    clearPasswordResetSession();
    session_regenerate_id(true);
    $_SESSION['password_reset_user_id'] = (int) $user['id'];
    $_SESSION['password_reset_email'] = $user['email'];

    logActivity($db, (int) $user['id'], 'Meminta OTP reset password', $user['role']);
    successResponse($genericMessage);
} catch (Throwable $exception) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log('Send reset OTP error: ' . $exception->getMessage());
    if (APP_ENV === 'production') {
        successResponse($genericMessage);
    }

    errorResponse('Kode OTP gagal dikirim. Periksa konfigurasi SMTP lalu coba lagi.', 500);
}
