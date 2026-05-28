<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/admin.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/validation.php';
require_once __DIR__ . '/../../app/helpers/security.php';
require_once __DIR__ . '/../../app/models/Jadwal.php';
require_once __DIR__ . '/../../app/models/Kelas.php';
require_once __DIR__ . '/../../app/models/Mapel.php';
require_once __DIR__ . '/../../app/models/Guru.php';

$admin = requireAdmin();
$db = Database::connection();

function normalizeStatus(?string $status): string
{
    return in_array($status, ['active', 'inactive'], true) ? $status : 'active';
}

function validateJadwalPayload(PDO $db, array $data, ?array $current = null): array
{
    $errors = validateRequired($data, ['hari', 'jam_mulai', 'jam_selesai', 'kelas_id', 'mapel_id', 'guru_id']);

    $payload = [
        'hari' => getValue($data, 'hari', ''),
        'jam_mulai' => getValue($data, 'jam_mulai', ''),
        'jam_selesai' => getValue($data, 'jam_selesai', ''),
        'kelas_id' => (int) getValue($data, 'kelas_id', 0),
        'mapel_id' => (int) getValue($data, 'mapel_id', 0),
        'guru_id' => (int) getValue($data, 'guru_id', 0),
        'ruangan' => getValue($data, 'ruangan', ''),
        'status' => normalizeStatus(getValue($data, 'status', 'active')),
    ];

    if (!in_array($payload['hari'], ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'], true)) {
        $errors['hari'] = 'Hari tidak valid.';
    }

    if ($payload['jam_mulai'] !== '' && $payload['jam_selesai'] !== '' && $payload['jam_mulai'] >= $payload['jam_selesai']) {
        $errors['jam_selesai'] = 'Jam selesai harus lebih besar dari jam mulai.';
    }

    $kelas = $payload['kelas_id'] > 0 ? Kelas::findById($db, $payload['kelas_id']) : null;
    $mapel = $payload['mapel_id'] > 0 ? Mapel::findById($db, $payload['mapel_id']) : null;
    $guru = $payload['guru_id'] > 0 ? Guru::findById($db, $payload['guru_id']) : null;

    if (!$kelas) {
        $errors['kelas_id'] = 'Kelas tidak valid.';
    }
    if (!$mapel) {
        $errors['mapel_id'] = 'Mata pelajaran tidak valid.';
    }
    if (!$guru) {
        $errors['guru_id'] = 'Guru tidak valid.';
    }

    if ($mapel && $payload['kelas_id'] > 0 && (int) $mapel['kelas_id'] !== $payload['kelas_id']) {
        $errors['kelas_id'] = 'Kelas pada jadwal harus sama dengan kelas pada mata pelajaran.';
    }

    if ($mapel && $payload['guru_id'] > 0 && (int) $mapel['guru_id'] !== $payload['guru_id']) {
        $errors['guru_id'] = 'Guru pada jadwal harus sama dengan guru pengajar mata pelajaran.';
    }

    $excludeId = $current ? (int) $current['id'] : null;
    if (!$errors && $payload['status'] === 'active' && Jadwal::hasConflict($db, $payload, $excludeId)) {
        $errors['jadwal'] = 'Jadwal bentrok dengan kelas, guru, atau ruangan pada jam yang sama.';
    }

    return ['errors' => $errors, 'payload' => $payload];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $data = Jadwal::all($db, [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'hari' => trim((string) ($_GET['hari'] ?? '')),
            'kelas_id' => trim((string) ($_GET['kelas_id'] ?? '')),
        ]);

        successResponse('Data jadwal pelajaran berhasil dimuat.', $data);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        errorResponse('Method tidak diizinkan.', 405);
    }

    $data = requestData();
    $action = getValue($data, 'action', 'create');

    if ($action === 'create') {
        [$errors, $payload] = array_values(validateJadwalPayload($db, $data));
        if ($errors) {
            errorResponse('Validasi jadwal pelajaran gagal.', 422, $errors);
        }

        $db->beginTransaction();
        $id = Jadwal::create($db, $payload);
        logActivity($db, (int) $admin['id'], 'Menambahkan jadwal pelajaran: ' . $payload['hari'] . ' ' . $payload['jam_mulai'], 'admin');
        $db->commit();

        successResponse('Jadwal pelajaran berhasil ditambahkan.', Jadwal::findById($db, $id), 201);
    }

    if ($action === 'update') {
        $id = (int) getValue($data, 'id', 0);
        if ($id <= 0) {
            errorResponse('ID jadwal tidak valid.', 422, ['id' => 'ID jadwal wajib valid.']);
        }

        $current = Jadwal::findById($db, $id);
        if (!$current) {
            errorResponse('Data jadwal tidak ditemukan.', 404);
        }

        [$errors, $payload] = array_values(validateJadwalPayload($db, $data, $current));
        if ($errors) {
            errorResponse('Validasi jadwal pelajaran gagal.', 422, $errors);
        }

        $db->beginTransaction();
        Jadwal::update($db, $id, $payload);
        logActivity($db, (int) $admin['id'], 'Mengubah jadwal pelajaran ID: ' . $id, 'admin');
        $db->commit();

        successResponse('Jadwal pelajaran berhasil diperbarui.', Jadwal::findById($db, $id));
    }

    if ($action === 'delete') {
        $id = (int) getValue($data, 'id', 0);
        if ($id <= 0) {
            errorResponse('ID jadwal tidak valid.', 422, ['id' => 'ID jadwal wajib valid.']);
        }

        if (!Jadwal::findById($db, $id)) {
            errorResponse('Data jadwal tidak ditemukan.', 404);
        }

        $db->beginTransaction();
        Jadwal::softDelete($db, $id);
        logActivity($db, (int) $admin['id'], 'Menonaktifkan jadwal pelajaran ID: ' . $id, 'admin');
        $db->commit();

        successResponse('Jadwal pelajaran berhasil dinonaktifkan.');
    }

    errorResponse('Action tidak dikenali.', 400);
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    errorResponse('Terjadi kesalahan saat memproses jadwal pelajaran.', 500);
}
