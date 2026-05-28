<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../helpers/upload.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../models/User.php';

function getProfileData(PDO $db, array $user): array
{
    $profile = null;

    if ($user['role'] === 'guru') {
        $statement = $db->prepare(
            'SELECT id AS guru_id, nama_guru, nip, email, no_hp, mata_pelajaran_utama, status
             FROM guru
             WHERE user_id = :user_id
             LIMIT 1'
        );
        $statement->execute([':user_id' => $user['id']]);
        $profile = $statement->fetch() ?: null;
    }

    if ($user['role'] === 'murid') {
        $statement = $db->prepare(
            'SELECT m.id AS murid_id, m.kelas_id, m.nama_murid, m.nis, m.email, m.no_hp, m.jurusan, m.status,
                    k.nama_kelas, k.tahun_ajaran
             FROM murid m
             LEFT JOIN kelas k ON k.id = m.kelas_id
             WHERE m.user_id = :user_id
             LIMIT 1'
        );
        $statement->execute([':user_id' => $user['id']]);
        $profile = $statement->fetch() ?: null;
    }

    return [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'status' => $user['status'],
        'profile_photo' => $user['profile_photo'],
        'profile_photo_url' => publicFileUrl($user['profile_photo']),
        'profile' => $profile,
    ];
}

function validateProfilePayload(array $data): array
{
    $errors = [];
    $name = inputString($data, 'name');
    $email = strtolower(inputString($data, 'email'));

    if ($name === '') {
        $errors['name'] = 'Nama wajib diisi.';
    }

    if ($email === '') {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid.';
    }

    $noHp = inputString($data, 'no_hp');
    if ($noHp !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $noHp)) {
        $errors['no_hp'] = 'Nomor HP hanya boleh berisi angka, spasi, +, atau - dengan panjang 8-20 karakter.';
    }

    return $errors;
}

function updateProfileData(PDO $db, array $user, array $data, ?array $profilePhoto = null): array
{
    $errors = validateProfilePayload($data);
    if (!empty($errors)) {
        errorResponse('Validasi profil gagal.', 422, $errors);
    }

    $name = inputString($data, 'name');
    $email = strtolower(inputString($data, 'email'));
    $noHp = inputString($data, 'no_hp');
    $mataPelajaranUtama = inputString($data, 'mata_pelajaran_utama');

    if (User::emailExists($db, $email, (int) $user['id'])) {
        errorResponse('Email sudah digunakan oleh akun lain.', 409, ['email' => 'Email duplikat.']);
    }

    $newPhotoPath = null;
    if ($profilePhoto && isset($profilePhoto['error']) && $profilePhoto['error'] !== UPLOAD_ERR_NO_FILE) {
        $newPhotoPath = saveUploadedFile($profilePhoto, 'profile', ['jpg', 'jpeg', 'png', 'webp']);
    }

    $db->beginTransaction();

    try {
        $fields = ['name = :name', 'email = :email', 'updated_at = NOW()'];
        $params = [':id' => $user['id'], ':name' => $name, ':email' => $email];

        if ($newPhotoPath !== null) {
            $fields[] = 'profile_photo = :profile_photo';
            $params[':profile_photo'] = $newPhotoPath;
        }

        $statement = $db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $statement->execute($params);

        if ($user['role'] === 'guru') {
            $statement = $db->prepare(
                'UPDATE guru
                 SET nama_guru = :name, email = :email, no_hp = :no_hp, mata_pelajaran_utama = :mapel, updated_at = NOW()
                 WHERE user_id = :user_id'
            );
            $statement->execute([
                ':name' => $name,
                ':email' => $email,
                ':no_hp' => $noHp,
                ':mapel' => $mataPelajaranUtama,
                ':user_id' => $user['id'],
            ]);
        }

        if ($user['role'] === 'murid') {
            $statement = $db->prepare(
                'UPDATE murid
                 SET nama_murid = :name, email = :email, no_hp = :no_hp, updated_at = NOW()
                 WHERE user_id = :user_id'
            );
            $statement->execute([
                ':name' => $name,
                ':email' => $email,
                ':no_hp' => $noHp,
                ':user_id' => $user['id'],
            ]);
        }

        logActivity($db, (int) $user['id'], 'Memperbarui profil pribadi', $user['role']);
        $db->commit();

        $freshUser = User::findActiveById($db, (int) $user['id']);
        return getProfileData($db, $freshUser ?: $user);
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        errorResponse('Profil gagal diperbarui.', 500);
    }
}

function changeUserPassword(PDO $db, array $user, array $data): void
{
    $currentPassword = inputString($data, 'current_password');
    $newPassword = inputString($data, 'new_password');
    $confirmPassword = inputString($data, 'confirm_password');

    $errors = [];
    if ($currentPassword === '') {
        $errors['current_password'] = 'Password lama wajib diisi.';
    }
    if (strlen($newPassword) < 6) {
        $errors['new_password'] = 'Password baru minimal 6 karakter.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Konfirmasi password tidak sama.';
    }

    if (!empty($errors)) {
        errorResponse('Validasi password gagal.', 422, $errors);
    }

    $statement = $db->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $user['id']]);
    $credential = $statement->fetch();

    if (!$credential || !password_verify($currentPassword, $credential['password'])) {
        errorResponse('Password lama tidak sesuai.', 401);
    }

    $statement = $db->prepare('UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id');
    $statement->execute([
        ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':id' => $user['id'],
    ]);

    logActivity($db, (int) $user['id'], 'Mengganti password akun', $user['role']);
}

function handleProfileEndpoint(array $user): void
{
    try {
        $db = Database::connection();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            successResponse('Profil berhasil dimuat.', getProfileData($db, $user));
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            errorResponse('Method tidak diizinkan.', 405);
        }

        $data = requestData();
        $action = inputString($data, 'action', 'update');

        if ($action === 'update') {
            $photo = $_FILES['profile_photo'] ?? null;
            $profile = updateProfileData($db, $user, $data, $photo);
            successResponse('Profil berhasil diperbarui.', $profile);
        }

        if ($action === 'change_password') {
            changeUserPassword($db, $user, $data);
            successResponse('Password berhasil diganti.');
        }

        errorResponse('Action tidak dikenal.', 400);
    } catch (Throwable $exception) {
        errorResponse('Endpoint profil gagal diproses.', 500);
    }
}
