<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/murid.php';
require_once __DIR__ . '/../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method tidak diizinkan.', 405);
}

$user = requireMurid();

try {
    $db = Database::connection();
    $stmtMurid = $db->prepare(
        'SELECT m.id, m.nama_murid, m.nis, m.kelas_id, k.nama_kelas
         FROM murid m
         LEFT JOIN kelas k ON k.id = m.kelas_id
         WHERE m.user_id = :user_id
         LIMIT 1'
    );
    $stmtMurid->execute([':user_id' => $user['id']]);
    $murid = $stmtMurid->fetch();

    if (!$murid) {
        errorResponse('Profil murid belum ditemukan.', 404);
    }

    $stmtTugas = $db->prepare('SELECT COUNT(*) AS total FROM tugas WHERE kelas_id = :kelas_id AND status = "active"');
    $stmtTugas->execute([':kelas_id' => $murid['kelas_id']]);

    $stmtMateri = $db->prepare('SELECT COUNT(*) AS total FROM materi WHERE kelas_id = :kelas_id');
    $stmtMateri->execute([':kelas_id' => $murid['kelas_id']]);

    $stmtAktivitas = $db->prepare(
        "SELECT aktivitas, role, created_at
         FROM (
             SELECT
                 CONCAT('Tugas baru: ', judul_tugas) AS aktivitas,
                 'Guru' AS role,
                 created_at
             FROM tugas
             WHERE kelas_id = :kelas_tugas AND status = 'active'
             UNION ALL
             SELECT
                 CONCAT('Materi baru: ', judul_materi) AS aktivitas,
                 'Guru' AS role,
                 created_at
             FROM materi
             WHERE kelas_id = :kelas_materi
             UNION ALL
             SELECT
                 CONCAT('Mengumpulkan tugas: ', t.judul_tugas) AS aktivitas,
                 'Murid' AS role,
                 COALESCE(pt.submitted_at, pt.updated_at) AS created_at
             FROM pengumpulan_tugas pt
             INNER JOIN tugas t ON t.id = pt.tugas_id
             WHERE pt.murid_id = :murid_id
         ) aktivitas_murid
         ORDER BY created_at DESC
         LIMIT 5"
    );
    $stmtAktivitas->execute([
        ':kelas_tugas' => $murid['kelas_id'],
        ':kelas_materi' => $murid['kelas_id'],
        ':murid_id' => $murid['id'],
    ]);

    successResponse('Dashboard murid berhasil dimuat.', [
        'user' => $user,
        'murid' => $murid,
        'summary' => [
            'tugas_aktif' => (int) $stmtTugas->fetch()['total'],
            'materi_tersedia' => (int) $stmtMateri->fetch()['total'],
        ],
        'aktivitas_terbaru' => $stmtAktivitas->fetchAll(),
    ]);
} catch (Throwable $exception) {
    errorResponse('Gagal memuat dashboard murid.', 500);
}
