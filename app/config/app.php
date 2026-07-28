<?php
require_once __DIR__ . '/env.php';

/**
 * Global application configuration.
 * Sesuaikan BASE_PATH jika nama folder project diganti.
 */
define('APP_NAME', 'LMS SMK Karya Teladan');
define('APP_ENV', (string) env('APP_ENV', 'local'));
define('BASE_PATH', '/lms-KaryaTeladan');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('PASSWORD_RESET_OTP_TTL_MINUTES', 5);
define('PASSWORD_RESET_OTP_COOLDOWN_SECONDS', 60);
define('PASSWORD_RESET_OTP_MAX_ATTEMPTS', 5);
define('PASSWORD_RESET_SESSION_TTL_SECONDS', 600);

// Secret Key hanya dibaca dari environment dan tidak boleh disimpan di source code.
define('RECAPTCHA_SECRET_KEY', (string) env('RECAPTCHA_SECRET_KEY', ''));

date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
