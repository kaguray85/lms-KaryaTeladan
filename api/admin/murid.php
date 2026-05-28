<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/admin.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/validation.php';
require_once __DIR__ . '/../../app/helpers/security.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Murid.php';

$admin = requireAdmin();
$db = Database::connection();

function normalizeMuridStatus(?string $status): string
{
    return in_array($status, ['active', 'inactive'], true) ? $status : 'active';
}

function validateMuridPayload(PDO $db, array $data, string $action, ?array $currentMurid = null): array
{
    $required = ['kelas_id', 'nama_murid', 'nis', 'email'];
    if ($action === 'create') {
        $required[] = 'password';
    }

    $errors = validateRequired($data, $required);

    $kelasId = (int) getValue($data, 'kelas_id', 0);
    $namaMurid = getValue($data, 'nama_murid', '');
    $nis = getValue($data, 'nis', '');
    $email = strtolower(getValue($data, 'email', ''));
    $password = getValue($data, 'password', '');
    $status = normalizeMuridStatus(getValue($data, 'status', 'active'));

    if ($kelasId <= 0) {
        $errors['kelas_id'] = 'Kelas wajib dipilih.';
    } elseif (!Murid::kelasExists($db, $kelasId)) {
        $errors['kelas_id'] = 'Kelas tidak ditemukan atau sedang nonaktif.';
    }

    if ($email !== '' && !validateEmailFormat($email)) {
        $errors['email'] = 'Format email tidak valid.';
    }

    if ($action === 'create' && $password !== '' && !validateMinLength($password, 6)) {
        $errors['password'] = 'Password minimal 6 karakter.';
    }

    if ($action === 'update' && $password !== '' && !validateMinLength($password, 6)) {
        $errors['password'] = 'Password baru minimal 6 karakter.';
    }

    $excludeMuridId = $currentMurid ? (int) $currentMurid['id'] : null;
    $excludeUserId = $currentMurid ? (int) $currentMurid['user_id'] : null;

    if ($email !== '') {
        if (User::emailExists($db, $email, $excludeUserId)) {
            $errors['email'] = 'Email sudah digunakan oleh akun lain.';
        }

        if (Murid::emailExists($db, $email, $excludeMuridId)) {
            $errors['email'] = 'Email murid sudah terdaftar.';
        }
    }

    if ($nis !== '' && Murid::nisExists($db, $nis, $excludeMuridId)) {
        $errors['nis'] = 'NIS sudah terdaftar.';
    }

    return [
        'errors' => $errors,
        'payload' => [
            'kelas_id' => $kelasId,
            'nama_murid' => $namaMurid,
            'nis' => $nis,
            'email' => $email,
            'password' => $password,
            'no_hp' => getValue($data, 'no_hp', ''),
            'jurusan' => getValue($data, 'jurusan', ''),
            'status' => $status,
        ],
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $items = Murid::all($db, [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'kelas_id' => trim((string) ($_GET['kelas_id'] ?? '')),
        ]);

        successResponse('Data murid berhasil dimuat.', [
            'items' => $items,
            'kelas_options' => Murid::kelasOptions($db),
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        errorResponse('Method tidak diizinkan.', 405);
    }

    $data = requestData();
    $action = getValue($data, 'action', 'create');

    if ($action === 'create') {
        [$errors, $payload] = array_values(validateMuridPayload($db, $data, 'create'));
        if ($errors) {
            errorResponse('Validasi data murid gagal.', 422, $errors);
        }

        $db->beginTransaction();
        $muridId = Murid::create($db, $payload);
        logActivity($db, (int) $admin['id'], 'Menambahkan data murid: ' . $payload['nama_murid'], 'admin');
        $db->commit();

        successResponse('Data murid berhasil ditambahkan.', Murid::findById($db, $muridId), 201);
    }

    if ($action === 'update') {
        $id = (int) getValue($data, 'id', 0);
        if ($id <= 0) {
            errorResponse('ID murid tidak valid.', 422, ['id' => 'ID murid wajib valid.']);
        }

        $currentMurid = Murid::findById($db, $id);
        if (!$currentMurid) {
            errorResponse('Data murid tidak ditemukan.', 404);
        }

        [$errors, $payload] = array_values(validateMuridPayload($db, $data, 'update', $currentMurid));
        if ($errors) {
            errorResponse('Validasi data murid gagal.', 422, $errors);
        }

        $db->beginTransaction();
        Murid::update($db, $id, $payload);
        logActivity($db, (int) $admin['id'], 'Mengubah data murid: ' . $payload['nama_murid'], 'admin');
        $db->commit();

        successResponse('Data murid berhasil diperbarui.', Murid::findById($db, $id));
    }

    if ($action === 'delete') {
        $id = (int) getValue($data, 'id', 0);
        if ($id <= 0) {
            errorResponse('ID murid tidak valid.', 422, ['id' => 'ID murid wajib valid.']);
        }

        $currentMurid = Murid::findById($db, $id);
        if (!$currentMurid) {
            errorResponse('Data murid tidak ditemukan.', 404);
        }

        $db->beginTransaction();
        Murid::softDelete($db, $id);
        logActivity($db, (int) $admin['id'], 'Menonaktifkan data murid: ' . $currentMurid['nama_murid'], 'admin');
        $db->commit();

        successResponse('Data murid berhasil dinonaktifkan.');
    }

    errorResponse('Action tidak dikenali.', 400);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    if ($exception instanceof PDOException && $exception->getCode() === '23000') {
        errorResponse('Data duplikat terdeteksi. Periksa email atau NIS.', 409);
    }

    errorResponse('Gagal memproses data murid.', 500);
}
