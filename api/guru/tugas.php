<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/request.php';
require_once __DIR__ . '/../../app/helpers/upload.php';
require_once __DIR__ . '/../../app/middleware/guru.php';
require_once __DIR__ . '/../../app/models/Tugas.php';

$user=requireGuru(); $db=Database::connection();
$stmt=$db->prepare('SELECT id FROM guru WHERE user_id=:user_id LIMIT 1'); $stmt->execute([':user_id'=>$user['id']]); $guru=$stmt->fetch();
if(!$guru) errorResponse('Profil guru tidak ditemukan.',404); $guruId=(int)$guru['id'];
try{
 if($_SERVER['REQUEST_METHOD']==='GET'){
  if(!empty($_GET['submissions']) && !empty($_GET['tugas_id'])) successResponse('Data pengumpulan tugas berhasil dimuat.', Tugas::submissions($db,(int)$_GET['tugas_id'],$guruId));
  $filters=$_GET; $filters['guru_id']=$guruId; successResponse('Data tugas berhasil dimuat.', Tugas::all($db,$filters));
 }
 if($_SERVER['REQUEST_METHOD']!=='POST') errorResponse('Method tidak diizinkan.',405);
 $data=requestData(); $action=inputString($data,'action','create');
 if($action==='delete'){ $id=inputInt($data,'id'); Tugas::softDelete($db,$id,$guruId); successResponse('Tugas berhasil dinonaktifkan.'); }
 if($action==='grade_submission'){
  $id=inputInt($data,'submission_id'); $nilai=(float)($data['nilai']??0); if($id<=0 || $nilai<0 || $nilai>100) errorResponse('Data nilai pengumpulan tidak valid.',422);
  Tugas::gradeSubmission($db,$id,$guruId,$nilai,inputString($data,'komentar_guru')); successResponse('Pengumpulan tugas berhasil dinilai.');
 }
 $filePath=''; if(isset($_FILES['file_tugas']) && $_FILES['file_tugas']['error'] !== UPLOAD_ERR_NO_FILE){ $filePath=saveUploadedFile($_FILES['file_tugas'],'tugas',['pdf','doc','docx','zip','rar']); }
 $payload=['guru_id'=>$guruId,'kelas_id'=>inputInt($data,'kelas_id'),'mapel_id'=>inputInt($data,'mapel_id'),'judul_tugas'=>inputString($data,'judul_tugas'),'deskripsi'=>inputString($data,'deskripsi'),'file_tugas'=>$filePath,'deadline'=>inputString($data,'deadline'),'status'=>inputString($data,'status','active')];
 $errors=[]; if($payload['kelas_id']<=0)$errors['kelas_id']='Kelas wajib dipilih.'; if($payload['mapel_id']<=0)$errors['mapel_id']='Mata pelajaran wajib dipilih.'; if($payload['judul_tugas']==='')$errors['judul_tugas']='Judul tugas wajib diisi.'; if($payload['deadline']==='')$errors['deadline']='Deadline wajib diisi.'; if(!in_array($payload['status'],['active','inactive'],true))$errors['status']='Status tidak valid.'; if($errors) errorResponse('Validasi gagal.',422,$errors);
 if($action==='update'){ $id=inputInt($data,'id'); Tugas::update($db,$id,$payload); successResponse('Tugas berhasil diperbarui.'); }
 Tugas::create($db,$payload); successResponse('Tugas berhasil dibuat.',[],201);
}catch(Throwable $e){ errorResponse($e instanceof RuntimeException ? $e->getMessage() : 'Tugas gagal diproses.',500); }
