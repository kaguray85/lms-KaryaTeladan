<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/middleware/murid.php';
require_once __DIR__ . '/../../app/models/Nilai.php';
$user=requireMurid(); $db=Database::connection();
$stmt=$db->prepare('SELECT id FROM murid WHERE user_id=:user_id LIMIT 1'); $stmt->execute([':user_id'=>$user['id']]); $murid=$stmt->fetch();
if(!$murid) errorResponse('Profil murid tidak ditemukan.',404);
try{ $filters=$_GET; $filters['murid_id']=(int)$murid['id']; successResponse('Nilai pribadi berhasil dimuat.', Nilai::all($db,$filters)); }catch(Throwable $e){ errorResponse('Nilai gagal dimuat.',500); }
