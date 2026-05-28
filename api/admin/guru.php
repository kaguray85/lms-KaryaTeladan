<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/admin.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/validation.php';
require_once __DIR__ . '/../../app/helpers/security.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Guru.php';

$admin = requireAdmin();
$db = Database::connection();

function normalizeStatus(?string $status): string
{
    return in_array($status, ['active', 'inactive'], true) ? $status : 'active';
}

function validateGuruPayload(PDO $db, array $data, string $action, ?array $currentGuru = null): array
{
    $required = ['nama_guru', 'nip', 'email'];
    if ($action === 'create') {
        $required[] = 'password';
    }

    $errors = validateRequired($data, $required);

    $namaGuru = getValue($data, 'nama_guru', '');
    $nip = getValue($data, 'nip', '');
    $email = strtolower(getValue($data, 'email', ''));
    $password = getValue($data, 'password', '');
    $status = normalizeStatus(getValue($data, 'status', 'active'));

    if ($email !== '' && !validateEmailFormat($email)) {
        $errors['email'] = 'Format email tidak valid.';
    }

    if ($action === 'create' && $password !== '' && !validateMinLength($password, 6)) {
        $errors['password'] = 'Password minimal 6 karakter.';
    }

    if ($action === 'update' && $password !== '' && !validateMinLength($password, 6)) {
        $errors['password'] = 'Password baru minimal 6 karakter.';
    }

    $excludeGuruId = $currentGuru ? (int) $currentGuru['id'] : null;
    $excludeUserId = $currentGuru ? (int) $currentGuru['user_id'] : null;

    if ($email !== '') {
        if (User::emailExists($db, $email, $excludeUserId)) {
            $errors['email'] = 'Email sudah digunakan oleh akun lain.';
        }

        if (Guru::emailExists($db, $email, $excludeGuruId)) {
            $errors['email'] = 'Email guru sudah terdaftar.';
        }
    }

    if ($nip !== '' && Guru::nipExists($db, $nip, $excludeGuruId)) {
        $errors['nip'] = 'NIP sudah terdaftar.';
    }

    return [
        'errors' => $errors,
        'payload' => [
            'nama_guru' => $namaGuru,
            'nip' => $nip,
            'email' => $email,
            'password' => $password,
            'no_hp' => getValue($data, 'no_hp', ''),
            'mata_pelajaran_utama' => getValue($data, 'mata_pelajaran_utama', ''),
            'status' => $status,
        ],
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $data = Guru::all($db, [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
        ]);

        successResponse('Data guru berhasil dimuat.', $data);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        errorResponse('Method tidak diizinkan.', 405);
    }

    $data = requestData();
    $action = getValue($data, 'action', 'create');

    if ($action === 'create') {
        [$errors, $payload] = array_values(validateGuruPayload($db, $data, 'create'));
        if ($errors) {
            errorResponse('Validasi data guru gagal.', 422, $errors);
        }

        $db->beginTransaction();
        $guruId = Guru::create($db, $payload);
        logActivity($db, (int) $admin['id'], 'Menambahkan data guru: ' . $payload['nama_guru'], 'admin');
        $db->commit();

        successResponse('Data guru berhasil ditambahkan.', Guru::findById($db, $guruId), 201);
    }

    if ($action === 'update') {
        $id = (int) getValue($data, 'id', 0);
        if ($id <= 0) {
            errorResponse('ID guru tidak valid.', 422, ['id' => 'ID guru wajib valid.']);
        }

        $currentGuru = Guru::findById($db, $id);
        if (!$currentGuru) {
            errorResponse('Data guru tidak ditemukan.', 404);
        }

        [$errors, $payload] = array_values(validateGuruPayload($db, $data, 'update', $currentGuru));
        if ($errors) {
            errorResponse('Validasi data guru gagal.', 422, $errors);
        }

        $db->beginTransaction();
        Guru::update($db, $id, $payload);
        logActivity($db, (int) $admin['id'], 'Mengubah data guru: ' . $payload['nama_guru'], 'admin');
        $db->commit();

        successResponse('Data guru berhasil diperbarui.', Guru::findById($db, $id));
    }

    if ($action === 'delete') {
        $id = (int) getValue($data, 'id', 0);
        if ($id <= 0) {
            errorResponse('ID guru tidak valid.', 422, ['id' => 'ID guru wajib valid.']);
        }

        $currentGuru = Guru::findById($db, $id);
        if (!$currentGuru) {
            errorResponse('Data guru tidak ditemukan.', 404);
        }

        $db->beginTransaction();
        Guru::softDelete($db, $id);
        logActivity($db, (int) $admin['id'], 'Menonaktifkan data guru: ' . $currentGuru['nama_guru'], 'admin');
        $db->commit();

        successResponse('Data guru berhasil dinonaktifkan.');
    }

    errorResponse('Action tidak dikenali.', 400);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    if ($exception instanceof PDOException && $exception->getCode() === '23000') {
        errorResponse('Data duplikat terdeteksi. Periksa email atau NIP.', 409);
    }

    errorResponse('Gagal memproses data guru.', 500);
}
