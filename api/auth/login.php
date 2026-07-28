<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/validation.php';
require_once __DIR__ . '/../../app/helpers/security.php';
require_once __DIR__ . '/../../app/helpers/recaptcha.php';
require_once __DIR__ . '/../../app/models/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method tidak diizinkan.', 405);
}

$data = requestData();
$errors = validateRequired($data, ['email', 'password', 'recaptcha_token']);

$email = strtolower(getValue($data, 'email', ''));
$password = getValue($data, 'password', '');
$recaptchaToken = getValue($data, 'recaptcha_token', '');

if ($email !== '' && !validateEmailFormat($email)) {
    $errors['email'] = 'Format email tidak valid.';
}

if ($password !== '' && !validateMinLength($password, 6)) {
    $errors['password'] = 'Password minimal 6 karakter.';
}

if (!empty($errors)) {
    errorResponse('Validasi login gagal.', 422, $errors);
}

try {
    // Token checkbox diverifikasi sebelum pencarian user dan pengecekan password.
    $recaptcha = verifyRecaptchaCheckbox($recaptchaToken, $_SERVER['REMOTE_ADDR'] ?? '');
    if (!$recaptcha['valid']) {
        errorResponse($recaptcha['message'], 403);
    }

    $db = Database::connection();
    $user = User::findActiveByEmail($db, $email);

    if (!$user || !password_verify($password, $user['password'])) {
        errorResponse('Email atau password salah.', 401);
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_at'] = time();

    logActivity($db, (int) $user['id'], 'Login ke sistem', $user['role']);

    successResponse('Login berhasil.', User::safeUser($user));
} catch (Throwable $exception) {
    error_log('Login error: ' . $exception->getMessage());
    errorResponse('Login gagal. Silakan coba lagi.', 500);
}
