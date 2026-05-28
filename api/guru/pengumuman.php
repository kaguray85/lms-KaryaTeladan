<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/middleware/guru.php';
require_once __DIR__ . '/../../app/models/Pengumuman.php';
requireGuru(); $db=Database::connection();
try{ successResponse('Pengumuman berhasil dimuat.', Pengumuman::all($db, ['role_view'=>'guru'])); }catch(Throwable $e){ errorResponse('Pengumuman gagal dimuat.',500); }
