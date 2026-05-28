<?php
/**
 * CORS config untuk mode lokal.
 * Karena frontend dan API berada dalam origin yang sama di XAMPP,
 * file ini lebih berfungsi sebagai pengaman preflight sederhana.
 */
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
    $allowedOrigins = [
        'http://localhost',
        'http://127.0.0.1',
    ];

    foreach ($allowedOrigins as $allowedOrigin) {
        if (str_starts_with($origin, $allowedOrigin)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Credentials: true');
            break;
        }
    }
}

header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
