<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/guru.php';
require_once __DIR__ . '/../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method tidak diizinkan.', 405);
}

$user = requireGuru();

try {
    $db = Database::connection();
    $stmtGuru = $db->prepare('SELECT id, nama_guru, nip FROM guru WHERE user_id = :user_id LIMIT 1');
    $stmtGuru->execute([':user_id' => $user['id']]);
    $guru = $stmtGuru->fetch();

    if (!$guru) {
        errorResponse('Profil guru belum ditemukan.', 404);
    }

    $stmtKelas = $db->prepare('SELECT COUNT(DISTINCT kelas_id) AS total FROM jadwal_pelajaran WHERE guru_id = :guru_id AND status = "active"');
    $stmtKelas->execute([':guru_id' => $guru['id']]);

    $stmtTugas = $db->prepare('SELECT COUNT(*) AS total FROM tugas WHERE guru_id = :guru_id AND status = "active"');
    $stmtTugas->execute([':guru_id' => $guru['id']]);

    $stmtAktivitas = $db->prepare(
        "SELECT aktivitas, role, created_at
         FROM (
             SELECT
                 CONCAT('Membuat tugas: ', judul_tugas) AS aktivitas,
                 'Guru' AS role,
                 created_at
             FROM tugas
             WHERE guru_id = :guru_tugas
             UNION ALL
             SELECT
                 CONCAT('Mengunggah materi: ', judul_materi) AS aktivitas,
                 'Guru' AS role,
                 created_at
             FROM materi
             WHERE guru_id = :guru_materi
         ) aktivitas_guru
         ORDER BY created_at DESC
         LIMIT 5"
    );
    $stmtAktivitas->execute([
        ':guru_tugas' => $guru['id'],
        ':guru_materi' => $guru['id'],
    ]);

    successResponse('Dashboard guru berhasil dimuat.', [
        'user' => $user,
        'guru' => $guru,
        'summary' => [
            'jumlah_kelas_diajar' => (int) $stmtKelas->fetch()['total'],
            'jumlah_tugas_dibuat' => (int) $stmtTugas->fetch()['total'],
        ],
        'aktivitas_terbaru' => $stmtAktivitas->fetchAll(),
    ]);
} catch (Throwable $exception) {
    errorResponse('Gagal memuat dashboard guru.', 500);
}
