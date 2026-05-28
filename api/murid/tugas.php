<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/request.php';
require_once __DIR__ . '/../../app/helpers/upload.php';
require_once __DIR__ . '/../../app/middleware/murid.php';
require_once __DIR__ . '/../../app/models/Tugas.php';
$user=requireMurid(); $db=Database::connection();
$stmt=$db->prepare('SELECT id, kelas_id FROM murid WHERE user_id=:user_id LIMIT 1'); $stmt->execute([':user_id'=>$user['id']]); $murid=$stmt->fetch();
if(!$murid) errorResponse('Profil murid tidak ditemukan.',404); $muridId=(int)$murid['id']; $kelasId=(int)$murid['kelas_id'];
try{
 if($_SERVER['REQUEST_METHOD']==='GET') successResponse('Data tugas murid berhasil dimuat.', Tugas::forMurid($db,$muridId,$kelasId));
 if($_SERVER['REQUEST_METHOD']!=='POST') errorResponse('Method tidak diizinkan.',405);
 $data=requestData(); $tugasId=inputInt($data,'tugas_id'); if($tugasId<=0) errorResponse('Tugas tidak valid.',422);
 $tugas=Tugas::findById($db,$tugasId); if(!$tugas || (int)$tugas['kelas_id']!==$kelasId || $tugas['status']!=='active') errorResponse('Tugas tidak ditemukan untuk kelas kamu.',404);
 $filePath=null; if(isset($_FILES['file_jawaban']) && $_FILES['file_jawaban']['error'] !== UPLOAD_ERR_NO_FILE){ $filePath=saveUploadedFile($_FILES['file_jawaban'],'tugas',['pdf','doc','docx','zip','rar']); }
 Tugas::submit($db,$tugasId,$muridId,$filePath,inputString($data,'catatan_murid')); successResponse('Jawaban tugas berhasil dikumpulkan.');
}catch(Throwable $e){ errorResponse($e instanceof RuntimeException ? $e->getMessage() : 'Pengumpulan tugas gagal diproses.',500); }
