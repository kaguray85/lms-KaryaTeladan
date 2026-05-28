<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/middleware/admin.php';
require_once __DIR__ . '/../../app/models/Presensi.php';

requireAdmin();
$db = Database::connection();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') errorResponse('Method tidak diizinkan.', 405);
try {
    successResponse('Data presensi berhasil dimuat.', Presensi::all($db, $_GET));
} catch (Throwable $e) { errorResponse('Data presensi gagal dimuat.', 500); }
