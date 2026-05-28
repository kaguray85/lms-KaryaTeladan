<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/request.php';
require_once __DIR__ . '/../../app/middleware/guru.php';
require_once __DIR__ . '/../../app/models/Presensi.php';

$user=requireGuru(); $db=Database::connection();
$stmt=$db->prepare('SELECT id FROM guru WHERE user_id=:user_id LIMIT 1'); $stmt->execute([':user_id'=>$user['id']]); $guru=$stmt->fetch();
if(!$guru) errorResponse('Profil guru tidak ditemukan.',404);
$guruId=(int)$guru['id'];
$statusAllowed=['Hadir','Izin','Sakit','Alpha'];
try{
    if($_SERVER['REQUEST_METHOD']==='GET'){
        if(!empty($_GET['jadwal_id']) && isset($_GET['students'])){
            successResponse('Data murid berhasil dimuat.', Presensi::studentsBySchedule($db,(int)$_GET['jadwal_id'],$guruId));
        }
        $filters=$_GET; $filters['guru_id']=$guruId; successResponse('Data presensi berhasil dimuat.', Presensi::all($db,$filters));
    }
    if($_SERVER['REQUEST_METHOD']!=='POST') errorResponse('Method tidak diizinkan.',405);
    $data=requestData(); $jadwalId=inputInt($data,'jadwal_id'); $tanggal=inputString($data,'tanggal',date('Y-m-d')); $items=$data['items'] ?? [];
    if($jadwalId<=0 || !Presensi::scheduleBelongsToGuru($db,$jadwalId,$guruId)) errorResponse('Jadwal tidak valid atau bukan milik guru ini.',422);
    if(!is_array($items) || count($items)===0) errorResponse('Data presensi murid wajib diisi.',422);
    $db->beginTransaction();
    foreach($items as $item){
        $muridId=(int)($item['murid_id'] ?? 0); $status=trim((string)($item['status'] ?? '')); $ket=trim((string)($item['keterangan'] ?? ''));
        if($muridId<=0 || !in_array($status,$statusAllowed,true)){ throw new RuntimeException('Data presensi tidak valid.'); }
        Presensi::upsert($db,['jadwal_id'=>$jadwalId,'murid_id'=>$muridId,'guru_id'=>$guruId,'tanggal'=>$tanggal,'status'=>$status,'keterangan'=>$ket]);
    }
    $db->commit(); successResponse('Presensi berhasil disimpan.');
}catch(Throwable $e){ if($db->inTransaction()) $db->rollBack(); errorResponse($e instanceof RuntimeException ? $e->getMessage() : 'Presensi gagal diproses.',500); }
