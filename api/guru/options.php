<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/middleware/guru.php';

$user = requireGuru();
$db = Database::connection();
$stmt = $db->prepare('SELECT id FROM guru WHERE user_id = :user_id LIMIT 1');
$stmt->execute([':user_id' => $user['id']]);
$guru = $stmt->fetch();
if (!$guru) {
    errorResponse('Profil guru tidak ditemukan.', 404);
}
$guruId = (int)$guru['id'];
$type = $_GET['type'] ?? 'kelas';

try {
    if ($type === 'kelas') {
        $stmt = $db->prepare("SELECT DISTINCT k.id, k.nama_kelas, k.jurusan, k.tahun_ajaran
                              FROM mata_pelajaran mp
                              INNER JOIN kelas k ON k.id = mp.kelas_id
                              WHERE mp.guru_id = :guru_id AND k.status = 'active' AND mp.status = 'active'
                              ORDER BY k.nama_kelas ASC");
        $stmt->execute([':guru_id' => $guruId]);
        successResponse('Opsi kelas berhasil dimuat.', $stmt->fetchAll());
    }

    if ($type === 'mapel') {
        $stmt = $db->prepare("SELECT mp.id, mp.kode_mapel, mp.nama_mapel, mp.kelas_id, k.nama_kelas, k.jurusan
                              FROM mata_pelajaran mp
                              INNER JOIN kelas k ON k.id = mp.kelas_id
                              WHERE mp.guru_id = :guru_id AND mp.status = 'active'
                              ORDER BY mp.nama_mapel ASC");
        $stmt->execute([':guru_id' => $guruId]);
        successResponse('Opsi mata pelajaran berhasil dimuat.', $stmt->fetchAll());
    }

    if ($type === 'murid') {
        $stmt = $db->prepare("SELECT DISTINCT m.id, m.nama_murid, m.nis, m.kelas_id, k.nama_kelas, k.jurusan
                              FROM mata_pelajaran mp
                              INNER JOIN kelas k ON k.id = mp.kelas_id
                              INNER JOIN murid m ON m.kelas_id = k.id
                              WHERE mp.guru_id = :guru_id AND mp.status = 'active' AND m.status = 'active'
                              ORDER BY k.nama_kelas ASC, m.nama_murid ASC");
        $stmt->execute([':guru_id' => $guruId]);
        successResponse('Opsi murid berhasil dimuat.', $stmt->fetchAll());
    }

    errorResponse('Tipe opsi tidak valid.', 422);
} catch (Throwable $exception) {
    errorResponse('Opsi data guru gagal dimuat.', 500);
}
