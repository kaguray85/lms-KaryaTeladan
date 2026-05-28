<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method tidak diizinkan.', 405);
}

try {
    $db = Database::connection();
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
        logActivity($db, (int) $_SESSION['user_id'], 'Logout dari sistem', $_SESSION['role']);
    }
} catch (Throwable $exception) {
    // Logout tetap dilanjutkan meskipun log gagal.
}

destroySession();
successResponse('Logout berhasil.');
