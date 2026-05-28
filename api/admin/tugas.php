<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/middleware/admin.php';
require_once __DIR__ . '/../../app/models/Tugas.php';

requireAdmin();
$db = Database::connection();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') errorResponse('Method tidak diizinkan.', 405);
try {
    if (!empty($_GET['submissions']) && !empty($_GET['tugas_id'])) {
        successResponse('Data pengumpulan tugas berhasil dimuat.', Tugas::submissions($db, (int)$_GET['tugas_id']));
    }
    successResponse('Data tugas berhasil dimuat.', Tugas::all($db, $_GET));
} catch (Throwable $e) { errorResponse('Data tugas gagal dimuat.', 500); }
