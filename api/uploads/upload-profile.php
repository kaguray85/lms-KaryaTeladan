<?php
require_once __DIR__ . '/../../app/config/cors.php';
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/helpers/response.php';
require_once __DIR__ . '/../../app/helpers/upload.php';
require_once __DIR__ . '/../../app/helpers/security.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method tidak diizinkan.', 405);
}

if (empty($_FILES['profile_photo'])) {
    errorResponse('File foto profil wajib dikirim.', 422);
}

try {
    $db = Database::connection();
    $path = saveUploadedFile($_FILES['profile_photo'], 'profile', ['jpg', 'jpeg', 'png', 'webp']);

    $statement = $db->prepare('UPDATE users SET profile_photo = :photo, updated_at = NOW() WHERE id = :id');
    $statement->execute([
        ':photo' => $path,
        ':id' => $user['id'],
    ]);

    logActivity($db, (int) $user['id'], 'Mengunggah foto profil', $user['role']);

    successResponse('Foto profil berhasil diupload.', [
        'path' => $path,
        'url' => publicFileUrl($path),
    ]);
} catch (Throwable $exception) {
    errorResponse($exception->getMessage() ?: 'Upload foto profil gagal.', 422);
}
