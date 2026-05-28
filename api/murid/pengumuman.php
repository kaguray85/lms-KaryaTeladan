<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/middleware/murid.php';
require_once __DIR__ . '/../../app/models/Pengumuman.php';
requireMurid(); $db=Database::connection();
try{ successResponse('Pengumuman berhasil dimuat.', Pengumuman::all($db, ['role_view'=>'murid'])); }catch(Throwable $e){ errorResponse('Pengumuman gagal dimuat.',500); }
