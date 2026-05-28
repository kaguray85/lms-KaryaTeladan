<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../models/User.php';

function requireAuth(): array
{
    if (empty($_SESSION['user_id'])) {
        errorResponse('Unauthorized. Silakan login terlebih dahulu.', 401);
    }

    try {
        $db = Database::connection();
        $user = User::findActiveById($db, (int) $_SESSION['user_id']);

        if (!$user) {
            $_SESSION = [];
            session_destroy();
            errorResponse('Session tidak valid. Silakan login ulang.', 401);
        }

        return $user;
    } catch (Throwable $exception) {
        errorResponse('Gagal memvalidasi session.', 500);
    }
}

function requireRole(string|array $roles): array
{
    $user = requireAuth();
    $allowedRoles = is_array($roles) ? $roles : [$roles];

    if (!in_array($user['role'], $allowedRoles, true)) {
        errorResponse('Forbidden. Role tidak memiliki akses ke resource ini.', 403);
    }

    return $user;
}
