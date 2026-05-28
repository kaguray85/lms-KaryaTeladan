<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method tidak diizinkan.', 405);
}

$user = requireAuth();
successResponse('User sedang login.', $user);
