<?php
require_once __DIR__ . '/../config/app.php';

function validateUpload(array $file, array $allowedExtensions): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['valid' => false, 'message' => 'File tidak valid.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'message' => 'Upload file gagal.'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['valid' => false, 'message' => 'Ukuran file maksimal 5MB.'];
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'message' => 'Sumber upload tidak valid.'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['valid' => false, 'message' => 'Format file tidak diizinkan.'];
    }

    return ['valid' => true, 'message' => 'File valid.', 'extension' => $extension];
}

function makeSafeFileName(string $extension): string
{
    return bin2hex(random_bytes(16)) . '.' . $extension;
}

function saveUploadedFile(array $file, string $storageFolder, array $allowedExtensions): string
{
    $validation = validateUpload($file, $allowedExtensions);
    if (!$validation['valid']) {
        throw new RuntimeException($validation['message']);
    }

    $projectRoot = dirname(__DIR__, 2);
    $targetDir = $projectRoot . '/storage/' . trim($storageFolder, '/');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $fileName = makeSafeFileName($validation['extension']);
    $targetPath = $targetDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('File gagal disimpan ke server.');
    }

    return 'storage/' . trim($storageFolder, '/') . '/' . $fileName;
}

function publicFileUrl(?string $relativePath): ?string
{
    if (!$relativePath) {
        return null;
    }

    return BASE_PATH . '/' . ltrim($relativePath, '/');
}
