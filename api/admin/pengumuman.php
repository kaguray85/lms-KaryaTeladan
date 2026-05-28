<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/request.php';
require_once __DIR__ . '/../../app/middleware/admin.php';
require_once __DIR__ . '/../../app/models/Pengumuman.php';

$user = requireAdmin();
$db = Database::connection();
$targetRoles = ['all','admin','guru','murid'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        successResponse('Data pengumuman berhasil dimuat.', Pengumuman::all($db, $_GET));
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') errorResponse('Method tidak diizinkan.', 405);
    $data = requestData(); $action = inputString($data, 'action', 'create');
    if ($action === 'delete') {
        $id = inputInt($data, 'id'); if ($id <= 0) errorResponse('ID pengumuman tidak valid.', 422);
        Pengumuman::delete($db, $id); successResponse('Pengumuman berhasil dihapus.');
    }
    $payload = [
        'user_id' => (int)$user['id'], 'judul' => inputString($data,'judul'), 'isi' => inputString($data,'isi'),
        'target_role' => inputString($data,'target_role','all'), 'tanggal' => inputString($data,'tanggal', date('Y-m-d')),
    ];
    $errors=[];
    if ($payload['judul']==='') $errors['judul']='Judul wajib diisi.';
    if ($payload['isi']==='') $errors['isi']='Isi pengumuman wajib diisi.';
    if (!in_array($payload['target_role'], $targetRoles, true)) $errors['target_role']='Target role tidak valid.';
    if ($payload['tanggal']==='') $errors['tanggal']='Tanggal wajib diisi.';
    if ($errors) errorResponse('Validasi gagal.', 422, $errors);
    if ($action === 'update') {
        $id=inputInt($data,'id'); if($id<=0) errorResponse('ID pengumuman tidak valid.',422);
        Pengumuman::update($db,$id,$payload); successResponse('Pengumuman berhasil diperbarui.');
    }
    Pengumuman::create($db,$payload); successResponse('Pengumuman berhasil dibuat.', [], 201);
} catch (Throwable $e) { errorResponse('Data pengumuman gagal diproses.', 500); }
