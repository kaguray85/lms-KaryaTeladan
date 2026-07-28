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
$verifiedAt = (int) ($_SESSION['password_reset_verified_at'] ?? 0);

if ($userId <= 0 || $verifiedAt <= 0 || $verifiedAt + PASSWORD_RESET_SESSION_TTL_SECONDS < time()) {
    clearPasswordResetSession();
    errorResponse('Sesi reset password tidak valid atau sudah kedaluwarsa.', 401);
}

$data = requestData();
$errors = validateRequired($data, ['password', 'password_confirmation']);
$password = getValue($data, 'password', '');
$passwordConfirmation = getValue($data, 'password_confirmation', '');

if ($password !== '' && !validateMinLength($password, 8)) {
    $errors['password'] = 'Password minimal 8 karakter.';
}

if ($password !== '' && $passwordConfirmation !== '' && $password !== $passwordConfirmation) {
    $errors['password_confirmation'] = 'Konfirmasi password tidak sama.';
}

if (!empty($errors)) {
    errorResponse('Validasi password baru gagal.', 422, $errors);
}

try {
    $db = Database::connection();
    $user = User::findActiveById($db, $userId);
    if (!$user) {
        clearPasswordResetSession();
        errorResponse('Akun tidak ditemukan atau tidak aktif.', 404);
    }

    $db->beginTransaction();
    updatePassword($db, $userId, $password);
    PasswordResetOtp::invalidateUnusedForUser($db, $userId);
    $db->commit();

    logActivity($db, $userId, 'Reset password berhasil', $user['role']);
    clearPasswordResetSession();
    session_regenerate_id(true);

    successResponse('Password berhasil diubah. Silakan login menggunakan password baru.');
} catch (Throwable $exception) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log('Reset password error: ' . $exception->getMessage());
    errorResponse('Reset password gagal. Silakan coba lagi.', 500);
}
