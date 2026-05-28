<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/request.php';
require_once __DIR__ . '/../../app/middleware/guru.php';
require_once __DIR__ . '/../../app/models/Nilai.php';

$user=requireGuru(); $db=Database::connection();
$stmt=$db->prepare('SELECT id FROM guru WHERE user_id=:user_id LIMIT 1'); $stmt->execute([':user_id'=>$user['id']]); $guru=$stmt->fetch();
if(!$guru) errorResponse('Profil guru tidak ditemukan.',404); $guruId=(int)$guru['id'];
try{
 if($_SERVER['REQUEST_METHOD']==='GET'){ $filters=$_GET; $filters['guru_id']=$guruId; successResponse('Data nilai berhasil dimuat.', Nilai::all($db,$filters)); }
 if($_SERVER['REQUEST_METHOD']!=='POST') errorResponse('Method tidak diizinkan.',405);
 $data=requestData(); $action=inputString($data,'action','create'); $id=inputInt($data,'id');
 $payload=[
  'murid_id'=>inputInt($data,'murid_id'), 'guru_id'=>$guruId, 'kelas_id'=>inputInt($data,'kelas_id'), 'mapel_id'=>inputInt($data,'mapel_id'), 'tugas_id'=>inputInt($data,'tugas_id'),
  'nilai_tugas'=>(float)($data['nilai_tugas'] ?? 0), 'nilai_uts'=>(float)($data['nilai_uts'] ?? 0), 'nilai_uas'=>(float)($data['nilai_uas'] ?? 0), 'komentar'=>inputString($data,'komentar'),
 ];
 $errors=[]; foreach(['murid_id','kelas_id','mapel_id'] as $k){ if($payload[$k]<=0) $errors[$k]='Data wajib dipilih.'; }
 foreach(['nilai_tugas','nilai_uts','nilai_uas'] as $k){ if($payload[$k]<0 || $payload[$k]>100) $errors[$k]='Nilai harus 0 sampai 100.'; }
 if(!Nilai::guruCanAccessStudent($db,$guruId,$payload['kelas_id'],$payload['mapel_id'],$payload['murid_id'])) $errors['akses']='Guru hanya bisa memberi nilai murid pada kelas dan mapel yang diajar.';
 if($errors) errorResponse('Validasi gagal.',422,$errors);
 Nilai::save($db,$payload,$action==='update' ? $id : null); successResponse($action==='update'?'Nilai berhasil diperbarui.':'Nilai berhasil disimpan.');
}catch(Throwable $e){ errorResponse($e instanceof RuntimeException ? $e->getMessage() : 'Nilai gagal diproses.',500); }
