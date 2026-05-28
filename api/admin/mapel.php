<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/admin.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/validation.php';
require_once __DIR__ . '/../../app/helpers/security.php';
require_once __DIR__ . '/../../app/models/Mapel.php';
require_once __DIR__ . '/../../app/models/Guru.php';
require_once __DIR__ . '/../../app/models/Kelas.php';

$admin = requireAdmin();
$db = Database::connection();

function normalizeStatus(?string $status): string
{
    return in_array($status, ['active', 'inactive'], true) ? $status : 'active';
}

function validateMapelPayload(PDO $db, array $data, ?array $current = null): array
{
    $errors = validateRequired($data, ['kode_mapel', 'nama_mapel', 'guru_id', 'kelas_id', 'semester']);

    $payload = [
        'kode_mapel' => strtoupper(getValue($data, 'kode_mapel', '')),
        'nama_mapel' => getValue($data, 'nama_mapel', ''),
        'guru_id' => (int) getValue($data, 'guru_id', 0),
        'kelas_id' => (int) getValue($data, 'kelas_id', 0),
        'semester' => getValue($data, 'semester', ''),
        'status' => normalizeStatus(getValue($data, 'status', 'active')),
    ];

    if (!in_array($payload['semester'], ['Ganjil', 'Genap'], true)) {
        $errors['semester'] = 'Semester harus Ganjil atau Genap.';
    }

    $excludeId = $current ? (int) $current['id'] : null;
    if ($payload['kode_mapel'] !== '' && Mapel::kodeExists($db, $payload['kode_mapel'], $excludeId)) {
        $errors['kode_mapel'] = 'Kode mata pelajaran sudah digunakan.';
    }

    if ($payload['guru_id'] <= 0 || !Guru::findById($db, $payload['guru_id'])) {
        $errors['guru_id'] = 'Guru pengajar tidak valid.';
    }

    if ($payload['kelas_id'] <= 0 || !Kelas::findById($db, $payload['kelas_id'])) {
        $errors['kelas_id'] = 'Kelas tidak valid.';
    }

    return ['errors' => $errors, 'payload' => $payload];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (($_GET['mode'] ?? '') === 'options') {
            $kelasId = isset($_GET['kelas_id']) && $_GET['kelas_id'] !== '' ? (int) $_GET['kelas_id'] : null;
            successResponse('Pilihan mata pelajaran berhasil dimuat.', Mapel::activeOptions($db, $kelasId));
        }

        $data = Mapel::all($db, [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'kelas_id' => trim((string) ($_GET['kelas_id'] ?? '')),
        ]);

        successResponse('Data mata pelajaran berhasil dimuat.', $data);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        errorResponse('Method tidak diizinkan.', 405);
    }

    $data = requestData();
    $action = getValue($data, 'action', 'create');

    if ($action === 'create') {
        [$errors, $payload] = array_values(validateMapelPayload($db, $data));
        if ($errors) {
            errorResponse('Validasi data mata pelajaran gagal.', 422, $errors);
        }

        $db->beginTransaction();
        $id = Mapel::create($db, $payload);
        logActivity($db, (int) $admin['id'], 'Menambahkan mata pelajaran: ' . $payload['nama_mapel'], 'admin');
        $db->commit();

        successResponse('Data mata pelajaran berhasil ditambahkan.', Mapel::findById($db, $id), 201);
    }

    if ($action === 'update') {
        $id = (int) getValue($data, 'id', 0);
        if ($id <= 0) {
            errorResponse('ID mata pelajaran tidak valid.', 422, ['id' => 'ID mata pelajaran wajib valid.']);
        }

        $current = Mapel::findById($db, $id);
        if (!$current) {
            errorResponse('Data mata pelajaran tidak ditemukan.', 404);
        }

        [$errors, $payload] = array_values(validateMapelPayload($db, $data, $current));
        if ($errors) {
            errorResponse('Validasi data mata pelajaran gagal.', 422, $errors);
        }

        $db->beginTransaction();
        Mapel::update($db, $id, $payload);
        logActivity($db, (int) $admin['id'], 'Mengubah mata pelajaran: ' . $payload['nama_mapel'], 'admin');
        $db->commit();

        successResponse('Data mata pelajaran berhasil diperbarui.', Mapel::findById($db, $id));
    }

    if ($action === 'delete') {
        $id = (int) getValue($data, 'id', 0);
        if ($id <= 0) {
            errorResponse('ID mata pelajaran tidak valid.', 422, ['id' => 'ID mata pelajaran wajib valid.']);
        }

        if (!Mapel::findById($db, $id)) {
            errorResponse('Data mata pelajaran tidak ditemukan.', 404);
        }

        $db->beginTransaction();
        Mapel::softDelete($db, $id);
        logActivity($db, (int) $admin['id'], 'Menonaktifkan mata pelajaran ID: ' . $id, 'admin');
        $db->commit();

        successResponse('Data mata pelajaran berhasil dinonaktifkan.');
    }

    errorResponse('Action tidak dikenali.', 400);
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    errorResponse('Terjadi kesalahan saat memproses data mata pelajaran.', 500);
}
