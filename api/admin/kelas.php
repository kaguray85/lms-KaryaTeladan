<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/admin.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/validation.php';
require_once __DIR__ . '/../../app/helpers/security.php';
require_once __DIR__ . '/../../app/models/Kelas.php';

$admin = requireAdmin();
$db = Database::connection();

function normalizeStatus(?string $status): string
{
    return in_array($status, ['active', 'inactive'], true) ? $status : 'active';
}

function validateKelasPayload(PDO $db, array $data, string $action, ?array $current = null): array
{
    $errors = validateRequired($data, ['nama_kelas', 'jurusan', 'tahun_ajaran']);

    $payload = [
        'nama_kelas' => getValue($data, 'nama_kelas', ''),
        'jurusan' => getValue($data, 'jurusan', ''),
        'wali_kelas_id' => (int) getValue($data, 'wali_kelas_id', 0),
        'tahun_ajaran' => getValue($data, 'tahun_ajaran', ''),
        'status' => normalizeStatus(getValue($data, 'status', 'active')),
    ];

    if ($payload['tahun_ajaran'] !== '' && !preg_match('/^\d{4}\/\d{4}$/', $payload['tahun_ajaran'])) {
        $errors['tahun_ajaran'] = 'Format tahun ajaran harus seperti 2025/2026.';
    }

    $excludeId = $current ? (int) $current['id'] : null;
    if ($payload['nama_kelas'] !== '' && $payload['jurusan'] !== '' && $payload['tahun_ajaran'] !== '') {
        if (Kelas::isDuplicate($db, $payload['nama_kelas'], $payload['jurusan'], $payload['tahun_ajaran'], $excludeId)) {
            $errors['nama_kelas'] = 'Kelas dengan jurusan dan tahun ajaran tersebut sudah ada.';
        }
    }

    return ['errors' => $errors, 'payload' => $payload];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (($_GET['mode'] ?? '') === 'options') {
            successResponse('Pilihan kelas berhasil dimuat.', Kelas::activeOptions($db));
        }

        $data = Kelas::all($db, [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
        ]);

        successResponse('Data kelas berhasil dimuat.', $data);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        errorResponse('Method tidak diizinkan.', 405);
    }

    $data = requestData();
    $action = getValue($data, 'action', 'create');

    if ($action === 'create') {
        [$errors, $payload] = array_values(validateKelasPayload($db, $data, 'create'));
        if ($errors) {
            errorResponse('Validasi data kelas gagal.', 422, $errors);
        }

        $db->beginTransaction();
        $id = Kelas::create($db, $payload);
        logActivity($db, (int) $admin['id'], 'Menambahkan data kelas: ' . $payload['nama_kelas'], 'admin');
        $db->commit();

        successResponse('Data kelas berhasil ditambahkan.', Kelas::findById($db, $id), 201);
    }

    if ($action === 'update') {
        $id = (int) getValue($data, 'id', 0);
        if ($id <= 0) {
            errorResponse('ID kelas tidak valid.', 422, ['id' => 'ID kelas wajib valid.']);
        }

        $current = Kelas::findById($db, $id);
        if (!$current) {
            errorResponse('Data kelas tidak ditemukan.', 404);
        }

        [$errors, $payload] = array_values(validateKelasPayload($db, $data, 'update', $current));
        if ($errors) {
            errorResponse('Validasi data kelas gagal.', 422, $errors);
        }

        $db->beginTransaction();
        Kelas::update($db, $id, $payload);
        logActivity($db, (int) $admin['id'], 'Mengubah data kelas: ' . $payload['nama_kelas'], 'admin');
        $db->commit();

        successResponse('Data kelas berhasil diperbarui.', Kelas::findById($db, $id));
    }

    if ($action === 'delete') {
        $id = (int) getValue($data, 'id', 0);
        if ($id <= 0) {
            errorResponse('ID kelas tidak valid.', 422, ['id' => 'ID kelas wajib valid.']);
        }

        if (!Kelas::findById($db, $id)) {
            errorResponse('Data kelas tidak ditemukan.', 404);
        }

        $db->beginTransaction();
        Kelas::softDelete($db, $id);
        logActivity($db, (int) $admin['id'], 'Menonaktifkan data kelas ID: ' . $id, 'admin');
        $db->commit();

        successResponse('Data kelas berhasil dinonaktifkan.');
    }

    errorResponse('Action tidak dikenali.', 400);
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    errorResponse('Terjadi kesalahan saat memproses data kelas.', 500);
}
