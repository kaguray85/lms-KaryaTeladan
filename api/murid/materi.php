<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/middleware/murid.php';
require_once __DIR__ . '/../../app/models/Materi.php';
$user=requireMurid(); $db=Database::connection();
$stmt=$db->prepare('SELECT kelas_id FROM murid WHERE user_id=:user_id LIMIT 1'); $stmt->execute([':user_id'=>$user['id']]); $murid=$stmt->fetch();
if(!$murid) errorResponse('Profil murid tidak ditemukan.',404);
try{ $filters=$_GET; $filters['kelas_id']=(int)$murid['kelas_id']; successResponse('Materi murid berhasil dimuat.', Materi::all($db,$filters)); }catch(Throwable $e){ errorResponse('Materi gagal dimuat.',500); }
