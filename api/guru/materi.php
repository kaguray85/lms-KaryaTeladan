<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/request.php';
require_once __DIR__ . '/../../app/helpers/upload.php';
require_once __DIR__ . '/../../app/middleware/guru.php';
require_once __DIR__ . '/../../app/models/Materi.php';

$user=requireGuru(); $db=Database::connection();
$stmt=$db->prepare('SELECT id FROM guru WHERE user_id=:user_id LIMIT 1'); $stmt->execute([':user_id'=>$user['id']]); $guru=$stmt->fetch();
if(!$guru) errorResponse('Profil guru tidak ditemukan.',404); $guruId=(int)$guru['id'];
try{
 if($_SERVER['REQUEST_METHOD']==='GET'){ $filters=$_GET; $filters['guru_id']=$guruId; successResponse('Data materi berhasil dimuat.', Materi::all($db,$filters)); }
 if($_SERVER['REQUEST_METHOD']!=='POST') errorResponse('Method tidak diizinkan.',405);
 $data=requestData(); $action=inputString($data,'action','create');
 if($action==='delete'){ $id=inputInt($data,'id'); Materi::delete($db,$id,$guruId); successResponse('Materi berhasil dihapus.'); }
 $filePath=''; if(isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] !== UPLOAD_ERR_NO_FILE){ $filePath=saveUploadedFile($_FILES['file_materi'],'materi',['pdf','doc','docx','ppt','pptx']); }
 $payload=['guru_id'=>$guruId,'kelas_id'=>inputInt($data,'kelas_id'),'mapel_id'=>inputInt($data,'mapel_id'),'judul_materi'=>inputString($data,'judul_materi'),'deskripsi'=>inputString($data,'deskripsi'),'file_materi'=>$filePath,'tanggal_upload'=>inputString($data,'tanggal_upload',date('Y-m-d'))];
 $errors=[]; if($payload['kelas_id']<=0)$errors['kelas_id']='Kelas wajib dipilih.'; if($payload['mapel_id']<=0)$errors['mapel_id']='Mata pelajaran wajib dipilih.'; if($payload['judul_materi']==='')$errors['judul_materi']='Judul materi wajib diisi.'; if($errors) errorResponse('Validasi gagal.',422,$errors);
 if($action==='update'){ $id=inputInt($data,'id'); Materi::update($db,$id,$payload); successResponse('Materi berhasil diperbarui.'); }
 Materi::create($db,$payload); successResponse('Materi berhasil dibuat.',[],201);
}catch(Throwable $e){ errorResponse($e instanceof RuntimeException ? $e->getMessage() : 'Materi gagal diproses.',500); }
