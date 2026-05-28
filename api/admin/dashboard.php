<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/admin.php';
require_once __DIR__ . '/../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method tidak diizinkan.', 405);
}

$user = requireAdmin();

try {
    $db = Database::connection();

    $count = function (string $table, string $where = "status = 'active'") use ($db): int {
        $statement = $db->query("SELECT COUNT(*) AS total FROM {$table} WHERE {$where}");
        return (int) $statement->fetch()['total'];
    };

    $latestLogs = $db->query(
        'SELECT aktivitas, role, created_at FROM aktivitas_log ORDER BY created_at DESC LIMIT 5'
    )->fetchAll();

    successResponse('Dashboard admin berhasil dimuat.', [
        'user' => $user,
        'summary' => [
            'jumlah_guru' => $count('guru'),
            'jumlah_murid' => $count('murid'),
            'jumlah_kelas' => $count('kelas'),
            'jumlah_mata_pelajaran' => $count('mata_pelajaran'),
            'jumlah_tugas_aktif' => $count('tugas'),
            'jumlah_materi' => (int) $db->query('SELECT COUNT(*) AS total FROM materi')->fetch()['total'],
        ],
        'aktivitas_terbaru' => $latestLogs,
    ]);
} catch (Throwable $exception) {
    errorResponse('Gagal memuat dashboard admin.', 500);
}
