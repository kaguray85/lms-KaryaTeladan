<?php
/**
 * Global application configuration.
 * Sesuaikan BASE_PATH jika nama folder project diganti.
 */
define('APP_NAME', 'LMS SMK Karya Teladan');
define('BASE_PATH', '/lms-smk-karya-teladan');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

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
