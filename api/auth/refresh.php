<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method tidak diizinkan.', 405);
}

$user = requireAuth();
session_regenerate_id(true);
successResponse('Session berhasil diperbarui.', $user);
